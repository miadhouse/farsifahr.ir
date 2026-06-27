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
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    return isset($_SESSION['user_id']) && isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true;
}

// بررسی نقش کاربر
function is_admin()
{
    return isset($_SESSION['role']) && $_SESSION['role'] === 'admin';
}

// بررسی ادمین اصلی (Super Admin)
function is_super_admin()
{
    return isset($_SESSION['email']) && $_SESSION['email'] === 'miadaleali@gmail.com';
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

// بررسی Cloudflare Turnstile
function verify_turnstile($response)
{
    if (empty($response)) {
        return false;
    }

    $url = 'https://challenges.cloudflare.com/turnstile/v0/siteverify';
    $data = [
        'secret' => TURNSTILE_SECRET_KEY,
        'response' => $response,
        'remoteip' => get_user_ip()
    ];

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    
    $result = curl_exec($ch);
    curl_close($ch);
    
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
    // دریافت اطلاعات کاربر برای بررسی ایمیل
    $stmtUser = $pdo->prepare("SELECT email FROM users WHERE id = ?");
    $stmtUser->execute([$user_id]);
    $user = $stmtUser->fetch();
    $is_super = ($user && $user['email'] === 'miadaleali@gmail.com');

    // پاک کردن سشن‌های قبلی این کاربر برای جلوگیری از لاگین همزمان (به جز ادمین اصلی)
    if (!$is_super) {
        $stmtDel = $pdo->prepare("DELETE FROM sessions WHERE user_id = ?");
        $stmtDel->execute([$user_id]);
    }

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
        // چک کنیم آیا کاربر در دستگاه دیگری لاگین کرده است (به جز ادمین اصلی)
        if (!is_super_admin()) {
            $stmtCheck = $pdo->prepare("SELECT id FROM sessions WHERE user_id = ?");
            $stmtCheck->execute([$_SESSION['user_id']]);
            if ($stmtCheck->rowCount() > 0) {
                setcookie('concurrent_login', '1', time() + 60, '/');
                $GLOBALS['concurrent_login_flag'] = true;
            }
        }
        
        // برای جلوگیری از حلقه ریدایرکت، باید وضعیت لاگین را کاملا پاک کنیم
        $_SESSION = array();
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
    // اگر سایت پشت کلادفلر است، آی‌پی واقعی در این هدر قرار دارد
    if (!empty($_SERVER['HTTP_CF_CONNECTING_IP'])) {
        return $_SERVER['HTTP_CF_CONNECTING_IP'];
    }
    
    if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
        $ip = $_SERVER['HTTP_CLIENT_IP'];
    } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
        $ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
    } else {
        $ip = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
    }

    // اگر آی‌پی شامل چند بخش با کاما بود (مثلاً پشت چند پروکسی)، اولین آی‌پی معتبر غیر محلی را استخراج می‌کنیم
    if (strpos($ip, ',') !== false) {
        $ips = array_map('trim', explode(',', $ip));
        foreach ($ips as $single_ip) {
            // حذف آی‌پی‌های محلی یا خالی
            if ($single_ip !== '127.0.0.1' && $single_ip !== '::1' && !empty($single_ip)) {
                return $single_ip;
            }
        }
        return $ips[0]; // در نهایت اگر همه محلی بودند، اولین مورد
    }

    return $ip;
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
        file_put_contents('/home/farsifahr.com/public_html/chat/api/telegram_error.log', "[" . date('Y-m-d H:i:s') . "] Constants not defined\n", FILE_APPEND);
        return false;
    }

    $token = TELEGRAM_BOT_TOKEN;
    $chat_id = TELEGRAM_ADMIN_CHAT_ID;

    if (empty($token) || empty($chat_id)) {
        file_put_contents('/home/farsifahr.com/public_html/chat/api/telegram_error.log', "[" . date('Y-m-d H:i:s') . "] Token or Chat ID empty\n", FILE_APPEND);
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
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);
    
    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curl_err = curl_error($ch);
    curl_close($ch);

    if ($http_code != 200 || !$response) {
        $log_msg = "[" . date('Y-m-d H:i:s') . "] Failed: HTTP code: {$http_code}, Error: {$curl_err}, Response: {$response}, Message: {$message}\n";
        file_put_contents('/home/farsifahr.com/public_html/chat/api/telegram_error.log', $log_msg, FILE_APPEND);
    }

    return ($http_code == 200);
}

/**
 * دریافت موقعیت مکانی بر اساس آی‌پی
 */
function get_visitor_location($ip)
{
    if ($ip === '127.0.0.1' || $ip === '::1') return 'Localhost';
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, "http://ip-api.com/json/{$ip}?fields=status,message,country,city");
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 3);
    $response = curl_exec($ch);
    curl_close($ch);
    
    if ($response) {
        $data = json_decode($response, true);
        if ($data && $data['status'] === 'success') {
            return ($data['city'] ?? 'Unknown') . ', ' . ($data['country'] ?? 'Unknown');
        }
    }
    return 'Unknown';
}

/**
 * اطلاع‌رسانی بازدیدکننده به تلگرام
 */
function log_visitor_to_telegram()
{
    // فقط برای درخواست‌های عادی (نه AJAX و نه CLI)
    if (php_sapi_name() === 'cli') return;
    if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') return;

    $is_logged = is_logged_in();
    $uri = $_SERVER['REQUEST_URI'] ?? '/';
    $is_important_page = (strpos($uri, 'dashboard') !== false || strpos($uri, '/admin/') !== false);

    // منطق هوشمند ارسال نوتیفیکیشن:
    // ۱. اگر ۳۰ دقیقه از آخرین نوتیف گذشته باشد
    // ۲. یا اگر کاربر قبلاً مهمان بوده و حالا لاگین کرده است
    // ۳. یا اگر کاربر برای اولین بار در این نشست وارد بخش داشبورد/ادمین شده است
    
    $should_notify = false;
    if (!isset($_SESSION['last_tg_notif'])) {
        $should_notify = true;
    } elseif ((time() - $_SESSION['last_tg_notif']) > 1800) {
        $should_notify = true;
    } elseif ($is_logged && !($_SESSION['last_tg_was_logged'] ?? false)) {
        $should_notify = true;
    } elseif ($is_important_page && !($_SESSION['last_tg_was_important'] ?? false)) {
        $should_notify = true;
    }

    if (!$should_notify) return;

    // تشخیص بات‌ها
    $ua = $_SERVER['HTTP_USER_AGENT'] ?? '';
    if (empty($ua) || preg_match('/bot|crawl|slurp|spider|mediapartners|google|bing|yandex|duckduckgo|whatsapp|telegram|facebook|twitter/i', $ua)) {
        return;
    }

    $ip = get_user_ip();
    $location = get_visitor_location($ip);
    $host = $_SERVER['HTTP_HOST'] ?? 'farsifahr.com';
    $url = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . "://{$host}{$uri}";
    
    $status = "👤 <b>مهمان</b>";
    if ($is_logged) {
        $name = $_SESSION['name'] ?? 'نامشخص';
        $email = $_SESSION['email'] ?? 'بدون ایمیل';
        $status = "✅ <b>کاربر لاگین شده</b>\n👤 نام: {$name}\n📧 ایمیل: {$email}";
    }

    $message = "👀 <b>بازدید جدید از سایت</b>\n\n";
    $message .= "{$status}\n\n";
    $message .= "🌐 آی‌پی: <code>{$ip}</code>\n";
    $message .= "📍 موقعیت: {$location}\n";
    $message .= "📄 صفحه: {$url}\n";
    $message .= "🕒 زمان: " . date('Y-m-d H:i:s');

    if (send_telegram_admin_message($message)) {
        $_SESSION['last_tg_notif'] = time();
        $_SESSION['last_tg_was_logged'] = $is_logged;
        if ($is_important_page) {
            $_SESSION['last_tg_was_important'] = true;
        }
    }
}

// اجرای خودکار لاگ بازدیدکننده
log_visitor_to_telegram();

