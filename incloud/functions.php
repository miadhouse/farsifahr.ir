<?php
// functions.php
require_once(__DIR__ . '/../config/config.php');
require_once(__DIR__ . '/i18n.php');

// بررسی CSRF token
function verify_csrf_token($token)
{
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

// تولید token تصادفی
function generate_token($length = 32)
{
    return bin2hex(random_bytes($length));
}

// هش کردن رمز عبور
function hash_password($password)
{
    return password_hash($password, PASSWORD_BCRYPT);
}

// اعتبارسنجی ایمیل
function validate_email($email)
{
    return filter_var($email, FILTER_VALIDATE_EMAIL);
}

// اعتبارسنجی رمز عبور
function validate_password($password)
{
    // حداقل 8 کاراکتر، یک حرف بزرگ، یک حرف کوچک، یک عدد
    return strlen($password) >= 8 &&
        preg_match('/[A-Z]/', $password) &&
        preg_match('/[a-z]/', $password) &&
        preg_match('/[0-9]/', $password);
}

// بررسی تعداد تلاش‌های ورود
function check_login_attempts($email, $ip, $pdo)
{
    $stmt = $pdo->prepare("
        SELECT COUNT(*) as attempts 
        FROM login_attempts 
        WHERE email = ? AND ip_address = ? 
        AND attempted_at > DATE_SUB(NOW(), INTERVAL ? MINUTE)
    ");
    $stmt->execute([$email, $ip, LOGIN_ATTEMPT_TIMEOUT]);
    $result = $stmt->fetch();

    return $result['attempts'] < MAX_LOGIN_ATTEMPTS;
}

// ثبت تلاش ورود
function log_login_attempt($email, $ip, $pdo)
{
    $stmt = $pdo->prepare("INSERT INTO login_attempts (email, ip_address) VALUES (?, ?)");
    $stmt->execute([$email, $ip]);
}

// پاک کردن تلاش‌های ورود
function clear_login_attempts($email, $ip, $pdo)
{
    $stmt = $pdo->prepare("DELETE FROM login_attempts WHERE email = ? AND ip_address = ?");
    $stmt->execute([$email, $ip]);
}

// ثبت لاگ فعالیت کاربر
function log_user_action($user_id, $email, $action, $status, $pdo)
{
    $ip = get_user_ip();
    $user_agent = $_SERVER['HTTP_USER_AGENT'];

    // دریافت منطقه جغرافیایی
    $location = 'نامشخص';
    if ($ip !== '127.0.0.1' && $ip !== '::1') {
        $ch = curl_init("http://ip-api.com/json/{$ip}?lang=fa");
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 3);
        $response = curl_exec($ch);
        if ($response) {
            $data = json_decode($response, true);
            if ($data && $data['status'] === 'success') {
                $location = $data['country'] . '، ' . $data['city'];
            }
        }
        curl_close($ch);
    }

    $stmt = $pdo->prepare("
        INSERT INTO user_logs (user_id, email, action, ip_address, location, user_agent, status) 
        VALUES (?, ?, ?, ?, ?, ?, ?)
    ");
    $stmt->execute([$user_id, $email, $action, $ip, $location, $user_agent, $status]);
}

// بررسی ورود کاربر
function is_logged_in()
{
    return isset($_SESSION['user_id']) && isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true;
}

// بررسی نقش کاربر
function is_admin()
{
    return isset($_SESSION['role']) && $_SESSION['role'] === 'admin';
}

// خروج از حساب
function logout()
{
    // پاک کردن سشن از دیتابیس
    if (isset($_SESSION['session_id'])) {
        global $pdo;
        $stmt = $pdo->prepare("DELETE FROM sessions WHERE id = ?");
        $stmt->execute([$_SESSION['session_id']]);
    }

    // پاک کردن سشن
    $_SESSION = array();
    if (isset($_COOKIE[session_name()])) {
        setcookie(session_name(), '', time() - 3600, '/');
    }
    session_destroy();
}

// ارسال ایمیل با استفاده از PHPMailer (جایگزین تابع ناامن میل سرور)
function send_email($to, $subject, $body)
{
    require_once __DIR__ . '/mail-functions.php';
    $result = send_email_phpmailer($to, $subject, $body);
    return $result['success'];
}

// تولید کپچا
function generate_captcha()
{
    $code = substr(str_shuffle('23456789ABCDEFGHJKLMNPQRSTUVWXYZ'), 0, 6);
    $_SESSION['captcha'] = $code;
    return $code;
}

// بررسی کپچا
function verify_captcha($input)
{
    if (!isset($_SESSION['captcha'])) {
        return false;
    }
    $result = strtoupper($input) === $_SESSION['captcha'];
    unset($_SESSION['captcha']);
    return $result;
}

// بررسی Google reCAPTCHA
function verify_recaptcha($response)
{
    if (empty($response)) {
        return false;
    }

    $url = 'https://www.google.com/recaptcha/api/siteverify';
    $data = [
        'secret' => RECAPTCHA_SECRET_KEY,
        'response' => $response,
        'remoteip' => get_user_ip()
    ];

    $options = [
        'http' => [
            'header'  => "Content-type: application/x-www-form-urlencoded\r\n",
            'method'  => 'POST',
            'content' => http_build_query($data)
        ]
    ];

    $context  = stream_context_create($options);
    $result = file_get_contents($url, false, $context);
    
    if ($result === FALSE) {
        return false;
    }

    $resultJson = json_decode($result);
    return $resultJson->success;
}

// تولید کد معرف منحصر به فرد
function generate_referral_code($pdo, $length = 8)
{
    $chars = '0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZ';
    do {
        $code = '';
        for ($i = 0; $i < $length; $i++) {
            $code .= $chars[rand(0, strlen($chars) - 1)];
        }
        
        // بررسی منحصر به فرد بودن
        $stmt = $pdo->prepare("SELECT id FROM users WHERE referral_code = ?");
        $stmt->execute([$code]);
        $exists = $stmt->rowCount() > 0;
    } while ($exists);
    
    return $code;
}

// ذخیره سشن در دیتابیس
function save_session($user_id, $pdo)
{
    // پاک کردن سشن‌های قبلی این کاربر برای جلوگیری از لاگین همزمان
    $stmtDel = $pdo->prepare("DELETE FROM sessions WHERE user_id = ?");
    $stmtDel->execute([$user_id]);

    $session_id = session_id();
    $ip = $_SERVER['REMOTE_ADDR'];
    $user_agent = $_SERVER['HTTP_USER_AGENT'];

    $stmt = $pdo->prepare("
        INSERT INTO sessions (id, user_id, ip_address, user_agent) 
        VALUES (?, ?, ?, ?)
        ON DUPLICATE KEY UPDATE 
        last_activity = CURRENT_TIMESTAMP
    ");
    $stmt->execute([$session_id, $user_id, $ip, $user_agent]);

    $_SESSION['session_id'] = $session_id;
}

// بررسی اعتبار سشن
function validate_session($pdo)
{
    if (!isset($_SESSION['session_id']) || !isset($_SESSION['user_id'])) {
        return false;
    }

    $stmt = $pdo->prepare("
        SELECT * FROM sessions 
        WHERE id = ? AND user_id = ? 
        AND last_activity > DATE_SUB(NOW(), INTERVAL ? SECOND)
    ");
    $stmt->execute([$_SESSION['session_id'], $_SESSION['user_id'], SESSION_LIFETIME]);

    if ($stmt->rowCount() === 0) {
        // چک کنیم آیا کاربر در دستگاه دیگری لاگین کرده است
        $stmtCheck = $pdo->prepare("SELECT id FROM sessions WHERE user_id = ?");
        $stmtCheck->execute([$_SESSION['user_id']]);
        if ($stmtCheck->rowCount() > 0) {
            setcookie('concurrent_login', '1', time() + 60, '/');
        }
        
        // فقط سشن آیدی را پاک می‌کنیم نه کل سشن را
        unset($_SESSION['session_id']);
        return false;
    }

    // بروزرسانی زمان آخرین فعالیت
    $stmt = $pdo->prepare("UPDATE sessions SET last_activity = CURRENT_TIMESTAMP WHERE id = ?");
    $stmt->execute([$_SESSION['session_id']]);

    return true;
}

// دریافت IP کاربر
function get_user_ip()
{
    if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
        return $_SERVER['HTTP_CLIENT_IP'];
    } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
        return $_SERVER['HTTP_X_FORWARDED_FOR'];
    } else {
        return $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
    }
}

/**
 * ارسال پیام به ربات تلگرام مدیر
 *
 * @param string $message متن پیام
 * @return bool نتیجه ارسال
 */
function send_telegram_admin_message($message)
{
    if (!defined('TELEGRAM_BOT_TOKEN') || !defined('TELEGRAM_ADMIN_CHAT_ID')) {
        return false;
    }

    $token = TELEGRAM_BOT_TOKEN;
    $chat_id = TELEGRAM_ADMIN_CHAT_ID;

    if (empty($token) || empty($chat_id)) {
        return false;
    }

    $url = "https://api.telegram.org/bot{$token}/sendMessage";
    
    $data = [
        'chat_id' => $chat_id,
        'text' => $message,
        'parse_mode' => 'HTML'
    ];

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 5);
    
    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    return ($http_code == 200);
}

