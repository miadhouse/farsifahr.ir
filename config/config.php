<?php
// config.php
session_start();

// تنظیمات دیتابیس
define('DB_HOST', 'localhost');
define('DB_NAME', 'farsi-fahr2');
define('DB_USER', 'root');
define('DB_PASS', '');

// تنظیمات سایت
define('SITE_URL', 'http://localhost/farsifahr2/');
define('SITE_NAME', value: 'گواهینامه آلمانی به فارسی');

// تنظیمات ایمیل
define('SMTP_HOST', 'smtp.gmail.com');
define('SMTP_USER', 'persian.techfact@gmail.com');
define('SMTP_PASS', 'mnwp skvr anly yjwl');
define('SMTP_PORT', 587);
define('SMTP_FROM', 'persian.techfact@gmail.com');



// تنظیمات امنیتی
define('MAX_LOGIN_ATTEMPTS', 5);
define('LOGIN_ATTEMPT_TIMEOUT', 15); // دقیقه
define('SESSION_LIFETIME', 3600); // ثانیه
define('PASSWORD_RESET_EXPIRY', 3600); // ثانیه

// اتصال به دیتابیس
try {
    $pdo = new PDO(
        "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4",
        DB_USER,
        DB_PASS,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false
        ]
    );
} catch (PDOException $e) {
    die("خطا در اتصال به دیتابیس: " . $e->getMessage());
}

// تولید CSRF token
if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// تنظیم timezone
date_default_timezone_set('Asia/Tehran');