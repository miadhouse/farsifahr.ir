<?php
// auth.php
require_once '../incloud/functions.php';

header('Content-Type: application/json; charset=utf-8');

// بررسی درخواست POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'متد غیرمجاز']);
    exit;
}

// بررسی CSRF token
if (!isset($_POST['csrf_token']) || !verify_csrf_token($_POST['csrf_token'])) {
    echo json_encode(['success' => false, 'message' => 'توکن امنیتی نامعتبر است']);
    exit;
}

$action = $_POST['action'] ?? '';
$ip = get_user_ip();

switch ($action) {
    case 'login':
        handle_login($pdo, $ip);
        break;

    case 'register':
        handle_register($pdo, $ip);
        break;

    case 'reset':
        handle_reset($pdo, $ip);
        break;

    default:
        echo json_encode(['success' => false, 'message' => 'عملیات نامعتبر']);
}

// تابع ورود
function handle_login($pdo, $ip)
{
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $turnstile_response = $_POST['cf-turnstile-response'] ?? '';
    $remember = isset($_POST['remember']) && $_POST['remember'] === 'on';

    // اعتبارسنجی ورودی‌ها
    if (empty($email) || empty($password) || empty($turnstile_response)) {
        echo json_encode(['success' => false, 'message' => 'لطفا تمام فیلدها را پر کنید و کپچا را حل کنید']);
        return;
    }

    // بررسی Cloudflare Turnstile
    if (!verify_turnstile($turnstile_response)) {
        echo json_encode(['success' => false, 'message' => 'تاییدیه کپچا نامعتبر است']);
        return;
    }

    // بررسی تعداد تلاش‌های ورود
    if (!check_login_attempts($email, $ip, $pdo)) {
        log_user_action(null, $email, 'login', 'failed', $pdo);
        echo json_encode([
            'success' => false,
            'message' => 'تعداد تلاش‌های ورود بیش از حد مجاز است. لطفا ' . LOGIN_ATTEMPT_TIMEOUT . ' دقیقه صبر کنید'
        ]);
        return;
    }

    // جستجوی کاربر
    $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
    $stmt->execute([$email]);
    $user = $stmt->fetch();

    if (!$user || !password_verify($password, $user['password'])) {
        log_login_attempt($email, $ip, $pdo);
        log_user_action(null, $email, 'login', 'failed', $pdo);
        echo json_encode(['success' => false, 'message' => 'ایمیل یا رمز عبور اشتباه است']);
        return;
    }

    // بررسی وضعیت تایید ایمیل
    if ($user['email_verified'] == 0) {
        echo json_encode([
            'success' => false, 
            'status' => 'unverified',
            'email' => $email,
            'message' => 'حساب کاربری شما تایید نشده است. لطفا ایمیل خود را بررسی کنید.'
        ]);
        return;
    }

    // ورود موفق
    $_SESSION['user_id'] = $user['id'];
    $_SESSION['email'] = $user['email'];
    $_SESSION['name'] = $user['name'];
    $_SESSION['role'] = $user['role'];
    $_SESSION['logged_in'] = true;

    // ذخیره سشن در دیتابیس
    save_session($user['id'], $pdo);

    // تنظیم کوکی remember me
    if ($remember) {
        $token = generate_token();
        // در محیط واقعی، این توکن را در دیتابیس ذخیره کنید
        setcookie('remember_token', $token, time() + (30 * 24 * 60 * 60), '/', '', true, true);
    }

    // پاک کردن تلاش‌های ورود
    clear_login_attempts($email, $ip, $pdo);

    // ثبت لاگ
    log_user_action($user['id'], $email, 'login', 'success', $pdo);
    // ارسال اعلان ورود به تلگرام
    $tg_msg = "🔐 <b>ورود موفق کاربر</b>\n\n👤 نام: {$user['name']}\n📧 ایمیل: {$email}\n🌐 آی‌پی: {$ip}\n🕒 زمان: " . date('Y-m-d H:i:s');
    send_telegram_admin_message($tg_msg);

    echo json_encode([
        'success' => true,
        'message' => 'ورود موفقیت‌آمیز بود',
        'redirect' => 'admin/'
    ]);
}

// تابع ثبت نام
function handle_register($pdo, $ip)
{
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $password_confirm = $_POST['password_confirm'] ?? '';
    $turnstile_response = $_POST['cf-turnstile-response'] ?? '';

    // اعتبارسنجی
    if (empty($name) || empty($email) || empty($password) || empty($turnstile_response)) {
        echo json_encode(['success' => false, 'message' => 'لطفا تمام فیلدها را پر کنید و کپچا را حل کنید']);
        return;
    }

    // بررسی Cloudflare Turnstile
    if (!verify_turnstile($turnstile_response)) {
        echo json_encode(['success' => false, 'message' => 'تاییدیه کپچا نامعتبر است']);
        return;
    }

    // اعتبارسنجی ایمیل
    if (!validate_email($email)) {
        echo json_encode(['success' => false, 'message' => 'فرمت ایمیل معتبر نیست']);
        return;
    }

    // اعتبارسنجی رمز عبور
    if (!validate_password($password)) {
        echo json_encode([
            'success' => false,
            'message' => 'رمز عبور باید حداقل 8 کاراکتر و شامل حروف بزرگ، کوچک و عدد باشد'
        ]);
        return;
    }

    // بررسی تطابق رمز عبور
    if ($password !== $password_confirm) {
        echo json_encode(['success' => false, 'message' => 'رمز عبور و تکرار آن مطابقت ندارند']);
        return;
    }

    // بررسی تکراری نبودن ایمیل
    $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
    $stmt->execute([$email]);
    if ($stmt->rowCount() > 0) {
        echo json_encode(['success' => false, 'message' => 'این ایمیل قبلا ثبت شده است']);
        return;
    }

    // ثبت کاربر جدید
    try {
        $hashed_password = hash_password($password);
        $verification_token = generate_token();
        $referral_code = generate_referral_code($pdo);

        $stmt = $pdo->prepare("
            INSERT INTO users (name, email, password, verification_token, referral_code, email_verified) 
            VALUES (?, ?, ?, ?, ?, 1)
        ");
        $stmt->execute([$name, $email, $hashed_password, $verification_token, $referral_code]);

        $user_id = $pdo->lastInsertId();
        // ارسال ایمیل تایید (غیرفعال شد)
        // require_once __DIR__ . '/../incloud/mail-functions.php';
        // $result = send_verification_email($email, $name, $verification_token);

        // ارسال پیام به مدیر در تلگرام
        $telegram_message = "🆕 <b>ثبت نام جدید در سایت</b>\n\n";
        $telegram_message .= "👤 نام: {$name}\n";
        $telegram_message .= "📧 ایمیل: {$email}\n";
        $telegram_message .= "🕒 زمان: " . date('Y-m-d H:i:s');
        send_telegram_admin_message($telegram_message);

        // ثبت لاگ
        log_user_action($user_id, $email, 'register', 'success', $pdo);

        echo json_encode([
            'success' => true,
            'message' => 'ثبت نام با موفقیت انجام شد.'
        ]);

    } catch (PDOException $e) {
        log_user_action(null, $email, 'register', 'failed', $pdo);
        echo json_encode(['success' => false, 'message' => 'خطا در ثبت نام. لطفا دوباره تلاش کنید']);
    }
}

// تابع بازیابی رمز عبور
function handle_reset($pdo, $ip)
{
    $email = trim($_POST['email'] ?? '');

    if (empty($email) || !validate_email($email)) {
        echo json_encode(['success' => false, 'message' => 'لطفا ایمیل معتبر وارد کنید']);
        return;
    }

    // جستجوی کاربر
    $stmt = $pdo->prepare("SELECT id, name FROM users WHERE email = ?");
    $stmt->execute([$email]);
    $user = $stmt->fetch();

    if (!$user) {
        // برای جلوگیری از افشای اطلاعات، پیام یکسان نمایش می‌دهیم
        echo json_encode([
            'success' => true,
            'message' => 'در صورت وجود حساب کاربری، لینک بازیابی به ایمیل شما ارسال شد'
        ]);
        return;
    }

    // تولید توکن بازیابی
    $reset_token = generate_token();
    $reset_expires = date('Y-m-d H:i:s', time() + PASSWORD_RESET_EXPIRY);

    // ذخیره توکن
    $stmt = $pdo->prepare("
        UPDATE users 
        SET reset_token = ?, reset_expires = ? 
        WHERE id = ?
    ");
    $stmt->execute([$reset_token, $reset_expires, $user['id']]);

    // ارسال ایمیل
    require_once __DIR__ . '/../incloud/mail-functions.php';
    $result = send_password_reset_email($email, $user['name'], $reset_token);

    // ثبت لاگ
    log_user_action($user['id'], $email, 'password_reset_request', 'success', $pdo);

    echo json_encode([
        'success' => true,
        'message' => 'لینک بازیابی رمز عبور به ایمیل شما ارسال شد'
    ]);
}