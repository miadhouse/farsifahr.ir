<?php
// logout.php
require_once(__DIR__ . '/incloud/functions.php');

// ثبت لاگ خروج
if (isset($_SESSION['user_id'])) {
    log_user_action($_SESSION['user_id'], $_SESSION['email'] ?? '', 'logout', 'success', $pdo);
}

// خروج از حساب
logout();

// پاک کردن کوکی remember me
if (isset($_COOKIE['remember_token'])) {
    setcookie('remember_token', '', time() - 3600, '/', '', true, true);
}

// هدایت به صفحه اصلی
header("Location: index.php");
exit();