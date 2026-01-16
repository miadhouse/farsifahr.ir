<?php
// functions.php
require_once(__DIR__ . '/../config/config.php');

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

// ثبت لاگ کاربر
function log_user_action($user_id, $email, $action, $status, $pdo)
{
    $ip = $_SERVER['REMOTE_ADDR'];
    $user_agent = $_SERVER['HTTP_USER_AGENT'];

    $stmt = $pdo->prepare("
        INSERT INTO user_logs (user_id, email, action, ip_address, user_agent, status) 
        VALUES (?, ?, ?, ?, ?, ?)
    ");
    $stmt->execute([$user_id, $email, $action, $ip, $user_agent, $status]);
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

// ارسال ایمیل
function send_email($to, $subject, $body)
{
    // برای سادگی از mail() استفاده می‌کنیم
    // در محیط واقعی از PHPMailer یا SwiftMailer استفاده کنید
    $headers = "From: " . SMTP_FROM . "\r\n";
    $headers .= "Reply-To: " . SMTP_FROM . "\r\n";
    $headers .= "Content-Type: text/html; charset=UTF-8\r\n";

    return mail($to, $subject, $body, $headers);
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

// ذخیره سشن در دیتابیس
function save_session($user_id, $pdo)
{
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
        logout();
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
        return $_SERVER['REMOTE_ADDR'];
    }
}

