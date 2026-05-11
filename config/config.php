<?php
// config.php
if (session_status() === PHP_SESSION_NONE) {
    session_set_cookie_params([
        'lifetime' => 604800,
        'path' => '/',
        'secure' => true,
        'httponly' => true,
        'samesite' => 'Lax'
    ]);
    session_start();
}

// لود کردن فایل .env به صورت دستی
$envFile = dirname(__DIR__) . '/.env';
if (file_exists($envFile)) {
    $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (strpos(trim($line), '#') === 0) continue;
        list($name, $value) = explode('=', $line, 2);
        $name = trim($name);
        $value = trim($value);
        if (!array_key_exists($name, $_SERVER) && !array_key_exists($name, $_ENV)) {
            putenv(sprintf('%s=%s', $name, $value));
            $_ENV[$name] = $value;
            $_SERVER[$name] = $value;
        }
    }
}

// تنظیمات دیتابیس
define('DB_HOST', getenv('DB_HOST') ?: 'localhost');
define('DB_NAME', getenv('DB_NAME') ?: 'fars_farsifahr');
define('DB_USER', getenv('DB_USER') ?: 'fars_miad');
define('DB_PASS', getenv('DB_PASS') ?: '');

// تنظیمات سایت
define('SITE_URL', 'https://farsifahr.com/');
define('SITE_NAME', 'گواهینامه آلمانی به فارسی');

// تنظیمات ایمیل
define('SMTP_HOST', getenv('SMTP_HOST') ?: 'smtp.gmail.com');
define('SMTP_USER', getenv('SMTP_USER') ?: '');
define('SMTP_PASS', trim(getenv('SMTP_PASS') ?: '', '"'));
define('SMTP_PORT', getenv('SMTP_PORT') ?: 587);
define('SMTP_FROM', getenv('SMTP_FROM') ?: '');

// تنظیمات امنیتی
define('RECAPTCHA_SITE_KEY', getenv('RECAPTCHA_SITE_KEY') ?: '');
define('RECAPTCHA_SECRET_KEY', getenv('RECAPTCHA_SECRET_KEY') ?: '');

define('MAX_LOGIN_ATTEMPTS', 5);
define('LOGIN_ATTEMPT_TIMEOUT', 15); // دقیقه
define('SESSION_LIFETIME', 604800); // ثانیه
define('PASSWORD_RESET_EXPIRY', 3600); // ثانیه
define('EURO_TO_TOMAN_RATE', 215000); // نرخ تبدیل یورو به تومان پیش‌فرض

// لود کردن تنظیمات داینامیک از فایل JSON
$settingsFile = __DIR__ . '/settings.json';
if (file_exists($settingsFile)) {
    $dynamicSettings = json_decode(file_get_contents($settingsFile), true);
    if ($dynamicSettings) {
        foreach ($dynamicSettings as $key => $value) {
            $constName = strtoupper($key);
            if (!defined($constName)) {
                define($constName, $value);
            }
        }
    }
}

// مقادیر پیش‌فرض برای جلوگیری از خطا در صورت عدم وجود در JSON
if (!defined('INSTAGRAM_URL')) define('INSTAGRAM_URL', '#');
if (!defined('TELEGRAM_CHANNEL_URL')) define('TELEGRAM_CHANNEL_URL', '#');
if (!defined('TELEGRAM_SUPPORT_URL')) define('TELEGRAM_SUPPORT_URL', '#');
if (!defined('WHATSAPP_URL')) define('WHATSAPP_URL', '#');
if (!defined('CONTACT_PHONE')) define('CONTACT_PHONE', '');
if (!defined('CONTACT_EMAIL')) define('CONTACT_EMAIL', '');
if (!defined('FOOTER_DESCRIPTION')) define('FOOTER_DESCRIPTION', '');
if (!defined('COPYRIGHT_TEXT')) define('COPYRIGHT_TEXT', '');

// تنظیمات گوگل
define('GOOGLE_CLIENT_ID', getenv('GOOGLE_CLIENT_ID') ?: '');
define('GOOGLE_CLIENT_SECRET', getenv('GOOGLE_CLIENT_SECRET') ?: '');
define('GOOGLE_REDIRECT_URI', SITE_URL . 'auth/google-callback.php');

define('TELEGRAM_BOT_TOKEN', getenv('TELEGRAM_BOT_TOKEN') ?: '');
define('TELEGRAM_ADMIN_CHAT_ID', getenv('TELEGRAM_ADMIN_CHAT_ID') ?: '');

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