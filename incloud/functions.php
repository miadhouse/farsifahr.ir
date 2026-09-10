<?php
// functions.php

// IP Blacklist - Block specific abusive/malicious IP addresses or subnets (ending with .)
$blocked_ips = ['195.178.110.157', '207.154.197.113', '185.177.72.'];
$user_ip = $_SERVER['HTTP_CF_CONNECTING_IP'] ?? $_SERVER['HTTP_CLIENT_IP'] ?? $_SERVER['HTTP_X_FORWARDED_FOR'] ?? $_SERVER['REMOTE_ADDR'] ?? '';
if (strpos($user_ip, ',') !== false) {
    $ips = array_map('trim', explode(',', $user_ip));
    foreach ($ips as $single_ip) {
        if ($single_ip !== '127.0.0.1' && $single_ip !== '::1' && !empty($single_ip)) {
            $user_ip = $single_ip;
            break;
        }
    }
}
$is_blocked = false;
foreach ($blocked_ips as $blocked) {
    if (strpos($user_ip, $blocked) === 0) {
        $is_blocked = true;
        break;
    }
}
if ($is_blocked) {
    header('HTTP/1.1 403 Forbidden');
    exit('Access Denied');
}

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

// ایجاد یا تمدید توکن ماندگاری لاگین (۳ ماهه)
function create_remember_token($user_id, $pdo)
{
    try {
        if (!$pdo || empty($user_id)) {
            return false;
        }

        $stmtUser = $pdo->prepare("SELECT email, is_blocked FROM users WHERE id = ?");
        $stmtUser->execute([$user_id]);
        $user = $stmtUser->fetch();
        if (!$user || (isset($user['is_blocked']) && $user['is_blocked'] == 1)) {
            return false;
        }

        $is_super = ($user['email'] === 'miadaleali@gmail.com');

        // حذف توکن‌های قبلی کاربر عادی (جلوگیری از استفاده همزمان در چند دستگاه)
        if (!$is_super) {
            $stmtDel = $pdo->prepare("DELETE FROM remember_tokens WHERE user_id = ?");
            $stmtDel->execute([$user_id]);
        }

        $raw_token = bin2hex(random_bytes(32));
        $token_hash = hash('sha256', $raw_token);
        $lifetime = defined('SESSION_LIFETIME') ? SESSION_LIFETIME : 7776000;
        $expires_at = date('Y-m-d H:i:s', time() + $lifetime);

        $stmt = $pdo->prepare("
            INSERT INTO remember_tokens (user_id, token_hash, expires_at) 
            VALUES (?, ?, ?)
        ");
        $stmt->execute([$user_id, $token_hash, $expires_at]);

        if (!headers_sent()) {
            $is_secure = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') || (isset($_SERVER['SERVER_PORT']) && $_SERVER['SERVER_PORT'] == 443);
            setcookie('remember_token', $raw_token, [
                'expires' => time() + $lifetime,
                'path' => '/',
                'domain' => '',
                'secure' => $is_secure,
                'httponly' => true,
                'samesite' => 'Lax'
            ]);
            $_COOKIE['remember_token'] = $raw_token;
        }

        return $raw_token;
    } catch (Exception $e) {
        error_log("create_remember_token error: " . $e->getMessage());
        return false;
    }
}

// پاک کردن کوکی و توکن ماندگاری لاگین
function clear_remember_token($pdo = null)
{
    if (!empty($_COOKIE['remember_token']) && $pdo) {
        try {
            $token_hash = hash('sha256', $_COOKIE['remember_token']);
            $stmt = $pdo->prepare("DELETE FROM remember_tokens WHERE token_hash = ?");
            $stmt->execute([$token_hash]);
        } catch (Exception $e) {
            error_log("clear_remember_token error: " . $e->getMessage());
        }
    }

    if (!headers_sent()) {
        $is_secure = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') || (isset($_SERVER['SERVER_PORT']) && $_SERVER['SERVER_PORT'] == 443);
        setcookie('remember_token', '', [
            'expires' => time() - 3600,
            'path' => '/',
            'domain' => '',
            'secure' => $is_secure,
            'httponly' => true,
            'samesite' => 'Lax'
        ]);
    }
    unset($_COOKIE['remember_token']);
}

// بازگردانی سشن با توکن ماندگاری لاگین
function restore_session_from_remember_token($pdo)
{
    if (empty($_COOKIE['remember_token']) || !$pdo) {
        return false;
    }

    try {
        $raw_token = $_COOKIE['remember_token'];
        $token_hash = hash('sha256', $raw_token);

        $stmt = $pdo->prepare("
            SELECT rt.id AS token_id, rt.user_id, u.* 
            FROM remember_tokens rt 
            JOIN users u ON rt.user_id = u.id 
            WHERE rt.token_hash = ? AND rt.expires_at > NOW() 
            LIMIT 1
        ");
        $stmt->execute([$token_hash]);
        $user = $stmt->fetch();

        if (!$user) {
            clear_remember_token($pdo);
            return false;
        }

        if (isset($user['is_blocked']) && $user['is_blocked'] == 1) {
            clear_remember_token($pdo);
            return false;
        }

        if (isset($user['email_verified']) && $user['email_verified'] == 0) {
            clear_remember_token($pdo);
            return false;
        }

        if (session_status() === PHP_SESSION_NONE && !headers_sent()) {
            session_start();
        }

        $_SESSION['user_id'] = $user['id'];
        $_SESSION['email'] = $user['email'];
        $_SESSION['name'] = $user['name'];
        $_SESSION['role'] = $user['role'];
        $_SESSION['logged_in'] = true;

        // ذخیره سشن در دیتابیس
        save_session($user['id'], $pdo);

        // بروزرسانی زمان آخرین استفاده
        $stmtUpdate = $pdo->prepare("UPDATE remember_tokens SET last_used_at = CURRENT_TIMESTAMP WHERE id = ?");
        $stmtUpdate->execute([$user['token_id']]);

        return true;
    } catch (Exception $e) {
        error_log("restore_session_from_remember_token error: " . $e->getMessage());
        return false;
    }
}

// بررسی ورود کاربر
function is_logged_in()
{
    global $pdo;

    if (session_status() === PHP_SESSION_NONE && !headers_sent()) {
        session_start();
    }
    
    $logged = isset($_SESSION['user_id']) && isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true;
    
    // اگر در سشن نبود، بازیابی خودکار با توکن ماندگاری ۳ ماهه
    if (!$logged && !empty($_COOKIE['remember_token']) && isset($pdo)) {
        if (restore_session_from_remember_token($pdo)) {
            $logged = true;
        }
    }

    if ($logged) {
        // بررسی اینکه آیا کاربر مسدود شده است یا خیر
        static $block_checked = null; // برای اینکه در یک اجرای PHP فقط یکبار کوئری بزنیم
        if ($block_checked === null && isset($pdo)) {
            $stmt = $pdo->prepare("SELECT is_blocked, block_message FROM users WHERE id = ?");
            $stmt->execute([$_SESSION['user_id']]);
            $user = $stmt->fetch();
            if ($user && isset($user['is_blocked']) && $user['is_blocked'] == 1) {
                // کاربر مسدود شده است! سشن را پاک می‌کنیم
                $block_msg = !empty($user['block_message']) ? $user['block_message'] : 'حساب کاربری شما مسدود شده است. لطفاً با پشتیبانی تماس بگیرید.';
                
                clear_remember_token($pdo);

                // پاک کردن سشن
                $_SESSION = [];
                if (ini_get("session.use_cookies")) {
                    $params = session_get_cookie_params();
                    setcookie(session_name(), '', time() - 42000,
                        $params["path"], $params["domain"],
                        $params["secure"], $params["httponly"]
                    );
                }
                session_destroy();
                
                // تنظیم کوکی یادآور مسدودسازی برای نمایش به کاربر
                setcookie('blocked_msg', $block_msg, time() + 60, '/');
                header('Location: ' . SITE_URL . 'login.php?blocked=1');
                exit;
            }
            $block_checked = true;
        }

        // برای کاربران فعال که هنوز کوکی ماندگاری ندارند، آن را خودکار تنظیم می‌کنیم
        if (empty($_COOKIE['remember_token']) && !headers_sent() && isset($pdo) && !empty($_SESSION['user_id'])) {
            create_remember_token($_SESSION['user_id'], $pdo);
        }
    }
    return $logged;
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
    global $pdo;

    // پاک کردن توکن ماندگاری لاگین
    if (isset($pdo)) {
        clear_remember_token($pdo);
    }

    // پاک کردن سشن از دیتابیس
    if (isset($_SESSION['session_id']) && isset($pdo)) {
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
    $ip = get_user_ip();
    $user_agent = $_SERVER['HTTP_USER_AGENT'] ?? '';

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
                clear_remember_token($pdo);
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
        'parse_mode' => 'HTML',
        'disable_web_page_preview' => true
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

    $uri = $_SERVER['REQUEST_URI'] ?? '/';
    
    // نادیده گرفتن درخواست‌های API و فایل‌های پردازشی پس‌زمینه
    if (strpos($uri, 'handler.php') !== false || 
        strpos($uri, '/api/') !== false || 
        strpos($uri, 'webhook.php') !== false || 
        strpos($uri, 'auth/') !== false ||
        strpos($uri, 'sw.js') !== false) {
        return;
    }

    $is_logged = is_logged_in();
    if ($is_logged) {
        return; // بازدید کاربران لاگین شده (قدیمی) نیازی به اعلان ندارد
    }
    
    $is_important_page = (strpos($uri, 'dashboard') !== false || strpos($uri, '/admin/') !== false);

    // منطق هوشمند ارسال نوتیفیکیشن:
    // ۱. اگر ۳۰ دقیقه از آخرین نوتیف گذشته باشد
    
    $should_notify = false;
    if (!isset($_SESSION['last_tg_notif'])) {
        $should_notify = true;
    } elseif ((time() - $_SESSION['last_tg_notif']) > 1800) {
        $should_notify = true;
    }

    if (!$should_notify) return;

    // اگر کاربر مهمان است و می‌خواهد به صفحات اپلیکیشن یا مدیریت دسترسی پیدا کند، چون ریدایرکت می‌شود نیازی به اعلان نیست
    if (!$is_logged && (strpos($uri, '/app') !== false || strpos($uri, '/admin') !== false)) {
        return;
    }

    // تشخیص بات‌ها و اسکنرهای مشکوک
    $ua = $_SERVER['HTTP_USER_AGENT'] ?? '';
    if (empty($ua) || 
        preg_match('/bot|crawl|slurp|spider|mediapartners|google|bing|yandex|duckduckgo|whatsapp|telegram|facebook|twitter|scan/i', $ua) ||
        (strpos($ua, 'Firefox/24.0') !== false && strpos($ua, 'Chrome/') !== false)) {
        return;
    }

    $ip = get_user_ip();
    
    // محدودیت زمانی ارسال نوتیفیکیشن برای هر آی‌پی (حداکثر یک بار در ۱۰ دقیقه)
    $ip_clean = preg_replace('/[^a-zA-Z0-9_.-]/', '_', $ip);
    $cache_dir = sys_get_temp_dir() . '/tg_cache';
    if (!is_dir($cache_dir)) {
        @mkdir($cache_dir, 0777, true);
        @chmod($cache_dir, 0777);
    }
    $cache_file = $cache_dir . '/' . $ip_clean . '.time';
    if (file_exists($cache_file) && (time() - filemtime($cache_file)) < 600) {
        return; // این آی‌پی در ۱۰ دقیقه گذشته اعلان داشته است
    }

    $location = get_visitor_location($ip);
    
    // فیلتر کردن مهمان‌ها بر اساس کشورهای هدف جهت مهار ربات‌های اسکنر خارجی
    if (!$is_logged) {
        $allowed_countries = ['Germany', 'Deutschland', 'Iran', 'Austria', 'Switzerland', 'Localhost'];
        $is_allowed = false;
        foreach ($allowed_countries as $country) {
            if (stripos($location, $country) !== false) {
                $is_allowed = true;
                break;
            }
        }
        if (!$is_allowed) {
            return; // مهمان خارج از محدوده جغرافیایی هدف است، تلگرام را شلوغ نمی‌کنیم
        }
    }

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
        @file_put_contents($cache_file, time()); // ثبت زمان ارسال برای جلوگیری از اسپم آی‌پی
        $_SESSION['last_tg_notif'] = time();
        $_SESSION['last_tg_was_logged'] = $is_logged;
        if ($is_important_page) {
            $_SESSION['last_tg_was_important'] = true;
        }
    }
}

// اجرای خودکار لاگ بازدیدکننده
log_visitor_to_telegram();

/**
 * دریافت اعلان‌های فعال برای یک صفحه خاص
 */
function get_active_announcements($page_name)
{
    global $pdo;
    
    // تشخیص وضعیت لاگین کاربر
    $is_logged = is_logged_in();
    $audience = $is_logged ? 'members' : 'guests';
    $now = date('Y-m-d H:i:s');
    
    try {
        // واکشی اعلان‌های فعال که با مخاطب و تاریخ سازگارند
        $stmt = $pdo->prepare("
            SELECT * FROM announcements 
            WHERE is_active = 1 
              AND (end_date IS NULL OR end_date > ?)
              AND (audience = 'all' OR audience = ?)
            ORDER BY id DESC
        ");
        $stmt->execute([$now, $audience]);
        $announcements = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        $filtered = [];
        foreach ($announcements as $ann) {
            // فیلتر کردن صفحات هدف (فیلد target_pages ذخیره شده به صورت JSON در لاراول)
            $pages = json_decode($ann['target_pages'], true) ?: [];
            if (in_array($page_name, $pages)) {
                $filtered[] = $ann;
            }
        }
        return $filtered;
    } catch (PDOException $e) {
        error_log("Error fetching announcements: " . $e->getMessage());
        return [];
    }
}

/**
 * رندر کردن کدهای HTML/CSS/JS اعلان‌های فعال
 */
function render_announcements($page_name)
{
    $announcements = get_active_announcements($page_name);
    if (empty($announcements)) return;
    
    // گروه بندی اعلان ها بر اساس موقعیت (همه اعلان‌ها رندر می‌شوند و صف‌بندی در جاوااسکریپت کنترل می‌شود)
    $positions = [
        'top' => [],
        'middle' => [],
        'bottom' => []
    ];
    
    foreach ($announcements as $ann) {
        if (isset($positions[$ann['position']])) {
            $positions[$ann['position']][] = $ann;
        }
    }
    
    ?>
    <!-- کدهای استایل اعلان‌ها -->
    <style>
        .announcement-item {
            display: none; /* در ابتدا پنهان تا در جاوااسکریپت بررسی شود */
            box-sizing: border-box;
            position: relative;
            z-index: 99999;
            transition: all 0.3s ease;
        }
        
        /* استایل نوار بالا */
        .announcement-top-bar {
            width: 100%;
            background: linear-gradient(135deg, #1e3c72 0%, #2a5298 100%);
            color: white;
            padding: 12px 40px 12px 15px;
            text-align: center;
            font-size: 0.95rem;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
        }
        
        /* استایل نوار پایین */
        .announcement-bottom-bar {
            position: fixed;
            bottom: 0;
            left: 0;
            right: 0;
            background: linear-gradient(135deg, #2c3e50 0%, #3498db 100%);
            color: white;
            padding: 12px 40px 12px 15px;
            text-align: center;
            font-size: 0.95rem;
            box-shadow: 0 -2px 10px rgba(0,0,0,0.15);
        }
        
        /* استایل پاپ‌آپ وسط صفحه */
        .announcement-modal-backdrop {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.6);
            display: flex;
            align-items: center;
            justify-content: center;
            z-index: 100000;
            backdrop-filter: blur(4px);
        }
        
        .announcement-modal-card {
            background: white;
            color: #333;
            width: 90%;
            max-width: 550px;
            border-radius: 12px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.3);
            overflow: hidden;
            animation: announcementPopupScale 0.3s cubic-bezier(0.34, 1.56, 0.64, 1) forwards;
        }
        
        .dark-style .announcement-modal-card {
            background: #232a3b;
            color: #d8deea;
            border: 1px solid #36445d;
        }
        
        .announcement-modal-header {
            padding: 15px;
            background: #f8f9fa;
            border-bottom: 1px solid #eee;
            font-weight: bold;
            font-size: 1.1rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .dark-style .announcement-modal-header {
            background: #1c222f;
            border-bottom-color: #36445d;
        }
        
        .announcement-modal-body {
            padding: 20px;
            max-height: 70vh;
            overflow-y: auto;
            font-size: 1rem;
            line-height: 1.6;
        }
        
        /* دکمه بستن عمومی */
        .announcement-close-btn {
            position: absolute;
            top: 50%;
            transform: translateY(-50%);
            right: 15px;
            background: none;
            border: none;
            color: rgba(255,255,255,0.8);
            font-size: 1.6rem;
            cursor: pointer;
            line-height: 1;
            transition: color 0.2s;
        }
        
        .announcement-close-btn:hover {
            color: white;
        }
        
        .announcement-modal-close-icon {
            background: none;
            border: none;
            font-size: 1.5rem;
            cursor: pointer;
            color: #888;
            transition: color 0.2s;
        }
        
        .announcement-modal-close-icon:hover {
            color: #333;
        }
        
        .dark-style .announcement-modal-close-icon:hover {
            color: white;
        }
        
        /* انیمیشن رندر پاپ‌آپ */
        @keyframes announcementPopupScale {
            from {
                transform: scale(0.8);
                opacity: 0;
            }
            to {
                transform: scale(1);
                opacity: 1;
            }
        }
        
        /* ریسپانسیو بودن مدیا درون اعلان‌ها */
        .announcement-item img, .announcement-item video, .announcement-item iframe {
            max-width: 100%;
            height: auto;
            border-radius: 8px;
            margin: 10px 0;
        }
    </style>
    
    <!-- خروجی اعلان‌های بالا -->
    <?php foreach ($positions['top'] as $ann): ?>
        <div class="announcement-item announcement-top-bar" id="announcement-<?= $ann['id'] ?>" 
             data-id="<?= $ann['id'] ?>"
             data-display-type="<?= $ann['display_type'] ?>"
             data-views-limit="<?= $ann['custom_views_limit'] ?? 0 ?>"
             data-updated-at="<?= strtotime($ann['updated_at']) ?>">
            <div class="announcement-content-wrapper" style="direction: rtl;">
                <?= $ann['content'] ?>
                <span style="margin-right: 15px;">
                    <a href="javascript:void(0)" style="color: #ffeb3b; text-decoration: underline; font-size: 0.8rem;" onclick="dismissAnnouncementPermanently(<?= $ann['id'] ?>)">دیگر نشان نده</a>
                </span>
            </div>
            <button class="announcement-close-btn" onclick="dismissAnnouncement(<?= $ann['id'] ?>)">&times;</button>
        </div>
    <?php endforeach; ?>
    
    <!-- خروجی اعلان‌های پایین -->
    <?php foreach ($positions['bottom'] as $ann): ?>
        <div class="announcement-item announcement-bottom-bar" id="announcement-<?= $ann['id'] ?>" 
             data-id="<?= $ann['id'] ?>"
             data-display-type="<?= $ann['display_type'] ?>"
             data-views-limit="<?= $ann['custom_views_limit'] ?? 0 ?>"
             data-updated-at="<?= strtotime($ann['updated_at']) ?>">
            <div class="announcement-content-wrapper" style="direction: rtl;">
                <?= $ann['content'] ?>
                <span style="margin-right: 15px;">
                    <a href="javascript:void(0)" style="color: #ffeb3b; text-decoration: underline; font-size: 0.8rem;" onclick="dismissAnnouncementPermanently(<?= $ann['id'] ?>)">دیگر نشان نده</a>
                </span>
            </div>
            <button class="announcement-close-btn" onclick="dismissAnnouncement(<?= $ann['id'] ?>)">&times;</button>
        </div>
    <?php endforeach; ?>
    
    <!-- خروجی اعلان‌های وسط صفحه (پاپ‌آپ) -->
    <?php foreach ($positions['middle'] as $ann): ?>
        <div class="announcement-item announcement-modal-backdrop" id="announcement-<?= $ann['id'] ?>" 
             data-id="<?= $ann['id'] ?>"
             data-display-type="<?= $ann['display_type'] ?>"
             data-views-limit="<?= $ann['custom_views_limit'] ?? 0 ?>"
             data-updated-at="<?= strtotime($ann['updated_at']) ?>">
            <div class="announcement-modal-card">
                <div class="announcement-modal-header" style="direction: rtl;">
                    <span><?= htmlspecialchars($ann['title']) ?></span>
                    <button class="announcement-modal-close-icon" onclick="dismissAnnouncement(<?= $ann['id'] ?>)">&times;</button>
                </div>
                <div class="announcement-modal-body" style="direction: rtl;">
                    <?= $ann['content'] ?>
                    
                    <div class="text-center mt-3 pt-2 border-top announcement-modal-footer">
                        <button class="btn btn-sm btn-outline-secondary px-3 py-1" onclick="dismissAnnouncementPermanently(<?= $ann['id'] ?>)" style="font-size: 0.82rem; border-radius: 20px;">
                            دیگر این اعلان را نشان نده
                        </button>
                    </div>
                </div>
            </div>
        </div>
    <?php endforeach; ?>
    
    <!-- جاوااسکریپت کنترل نمایش و ذخیره آمار -->
    <script>
        // شناسه کاربر لاگین شده برای ایزوله‌سازی لوکال‌استوریج
        const currentUserId = <?= (int)($_SESSION['user_id'] ?? 0) ?>;

        // کمکی جهت جلوگیری از بروز خطای SecurityError در حالت Private یا در صورت مسدود بودن LocalStorage
        const safeStorage = {
            getItem: function(key) {
                try {
                    return localStorage.getItem(key);
                } catch (e) {
                    return this._data[key] || null;
                }
            },
            setItem: function(key, value) {
                try {
                    localStorage.setItem(key, value);
                } catch (e) {
                    this._data[key] = String(value);
                }
            },
            removeItem: function(key) {
                try {
                    localStorage.removeItem(key);
                } catch (e) {
                    delete this._data[key];
                }
            },
            _data: {}
        };

        // صف اعلان‌ها و اندیس جاری برای نمایش نوبتی
        let announcementQueue = [];
        let activeAnnouncementIndex = -1;

        // بستن موقت اعلان (فقط برای صفحه فعلی و تا زمان لود بعدی)
        function dismissAnnouncement(id) {
            const el = document.getElementById('announcement-' + id);
            if (el) {
                if (el.classList.contains('announcement-modal-backdrop')) {
                    el.style.opacity = '0';
                    setTimeout(() => {
                        el.remove();
                        showNextInQueue();
                    }, 300);
                } else {
                    el.style.height = '0';
                    el.style.padding = '0';
                    el.style.opacity = '0';
                    setTimeout(() => {
                        el.remove();
                        showNextInQueue();
                    }, 300);
                }
            } else {
                showNextInQueue();
            }
        }

        // بستن دائمی اعلان (دیگر نشان نده)
        function dismissAnnouncementPermanently(id) {
            safeStorage.setItem('dismissed_announcement_' + currentUserId + '_' + id, '1');
            dismissAnnouncement(id);
        }

        // نمایش اعلان بعدی در صف
        function showNextInQueue() {
            activeAnnouncementIndex++;
            if (activeAnnouncementIndex < announcementQueue.length) {
                const el = announcementQueue[activeAnnouncementIndex];
                const id = el.getAttribute('data-id');
                const displayType = el.getAttribute('data-display-type');
                
                // افزایش و ثبت شمارش بازدید فقط زمانی که اعلان واقعاً به کاربر نشان داده می‌شود
                const views = parseInt(safeStorage.getItem('announcement_views_' + currentUserId + '_' + id) || '0', 10);
                safeStorage.setItem('announcement_views_' + currentUserId + '_' + id, views + 1);
                console.log('Showing announcement ID:', id, 'views updated to:', views + 1);
                
                // نمایش اعلان با اولویت بالا جهت جلوگیری از تداخل CSS
                const displayStyle = el.classList.contains('announcement-modal-backdrop') ? 'flex' : 'block';
                el.style.setProperty('display', displayStyle, 'important');
            }
        }
        
        function initAnnouncements() {
            if (window.announcementsInitialized) return;
            window.announcementsInitialized = true;

            console.log('--- FarsiFahr Announcements debug ---');
            
            // دریافت همه اعلان‌ها و معکوس کردن آنها برای نمایش از قدیمی به جدید (به دلیل ORDER BY DESC در سرور)
            const allElements = Array.from(document.querySelectorAll('.announcement-item')).reverse();
            
            allElements.forEach(el => {
                const id = el.getAttribute('data-id');
                const displayType = el.getAttribute('data-display-type');
                const viewsLimit = parseInt(el.getAttribute('data-views-limit') || '0', 10);
                const updatedAt = el.getAttribute('data-updated-at') || '0';
                
                // بررسی نسخه اعلان جهت فعالسازی مجدد (Reactivation)
                const storedUpdated = safeStorage.getItem('announcement_updated_' + currentUserId + '_' + id) || '0';
                if (storedUpdated !== updatedAt) {
                    safeStorage.removeItem('dismissed_announcement_' + currentUserId + '_' + id);
                    safeStorage.setItem('announcement_views_' + currentUserId + '_' + id, '0');
                    safeStorage.setItem('announcement_updated_' + currentUserId + '_' + id, updatedAt);
                }
                
                // بررسی بسته شدن دستی اعلان
                const dismissed = safeStorage.getItem('dismissed_announcement_' + currentUserId + '_' + id);
                if (dismissed === '1') {
                    el.remove();
                    return;
                }
                
                // شمارش تعداد بازدیدهای ذخیره شده در مرورگر کاربر
                const views = parseInt(safeStorage.getItem('announcement_views_' + currentUserId + '_' + id) || '0', 10);
                
                let show = true;
                if (displayType === 'once' && views >= 1) {
                    show = false;
                } else if (displayType === 'three_times' && views >= 3) {
                    show = false;
                } else if (displayType === 'custom' && views >= viewsLimit) {
                    show = false;
                }
                
                if (show) {
                    // اضافه کردن به صف نمایش
                    announcementQueue.push(el);
                } else {
                    el.remove();
                }
            });

            // نمایش اولین اعلان در صف
            showNextInQueue();
        }

        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', initAnnouncements);
        } else {
            initAnnouncements();
        }
    </script>
    <?php
}

/**
 * تعداد اعلان‌های خوانده‌نشده برای کاربر
 */
function get_unread_announcements_count($user_id)
{
    global $pdo;
    $now = date('Y-m-d H:i:s');
    try {
        // دریافت شناسه تمامی اعلان‌های فعال
        $stmt = $pdo->prepare("
            SELECT id FROM announcements 
            WHERE is_active = 1 
              AND (end_date IS NULL OR end_date > ?)
              AND (audience = 'all' OR audience = 'members')
        ");
        $stmt->execute([$now]);
        $announcements = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        if (empty($announcements)) return 0;
        
        // دریافت اعلان‌های خوانده شده توسط کاربر
        $stmtRead = $pdo->prepare("
            SELECT announcement_id FROM user_announcement_reads 
            WHERE user_id = ?
        ");
        $stmtRead->execute([$user_id]);
        $readIds = $stmtRead->fetchAll(PDO::FETCH_COLUMN) ?: [];
        
        $unreadCount = 0;
        foreach ($announcements as $ann) {
            if (!in_array($ann['id'], $readIds)) {
                $unreadCount++;
            }
        }
        return $unreadCount;
    } catch (PDOException $e) {
        error_log("Error in get_unread_announcements_count: " . $e->getMessage());
        return 0;
    }
}

/**
 * دریافت تمامی اعلان‌های فعال برای کاربر (به همراه وضعیت خوانده شدن)
 */
function get_user_announcements($user_id)
{
    global $pdo;
    $now = date('Y-m-d H:i:s');
    try {
        // دریافت تمامی اعلان‌های فعال مرتب شده از جدید به قدیم
        $stmt = $pdo->prepare("
            SELECT a.*, 
                   CASE WHEN r.id IS NOT NULL THEN 1 ELSE 0 END as is_read
            FROM announcements a
            LEFT JOIN user_announcement_reads r ON a.id = r.announcement_id AND r.user_id = ?
            WHERE a.is_active = 1 
              AND (a.end_date IS NULL OR a.end_date > ?)
              AND (a.audience = 'all' OR a.audience = 'members')
            ORDER BY a.id DESC
        ");
        $stmt->execute([$user_id, $now]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log("Error in get_user_announcements: " . $e->getMessage());
        return [];
    }
}

/**
 * علامت‌گذاری تمامی اعلان‌های فعال به عنوان خوانده شده برای کاربر
 */
function mark_all_announcements_as_read($user_id)
{
    global $pdo;
    $now = date('Y-m-d H:i:s');
    try {
        // دریافت شناسه تمام اعلان‌های فعال
        $stmt = $pdo->prepare("
            SELECT id FROM announcements 
            WHERE is_active = 1 
              AND (end_date IS NULL OR end_date > ?)
              AND (audience = 'all' OR audience = 'members')
        ");
        $stmt->execute([$now]);
        $active_ids = $stmt->fetchAll(PDO::FETCH_COLUMN);
        
        if (empty($active_ids)) return;
        
        // ثبت در جدول خوانده شده‌ها
        $pdo->beginTransaction();
        $stmtInsert = $pdo->prepare("
            INSERT IGNORE INTO user_announcement_reads (user_id, announcement_id) 
            VALUES (?, ?)
        ");
        foreach ($active_ids as $ann_id) {
            $stmtInsert->execute([$user_id, $ann_id]);
        }
        $pdo->commit();
    } catch (PDOException $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        error_log("Error in mark_all_announcements_as_read: " . $e->getMessage());
    }
}


