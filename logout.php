<?php
// logout.php
require_once(__DIR__ . '/incloud/functions.php');

// ثبت لاگ خروج
if (isset($_SESSION['user_id'])) {
    log_user_action($_SESSION['user_id'], $_SESSION['email'] ?? '', 'logout', 'success', $pdo);
}

// خروج از حساب
logout();

// پاک کردن کوکی و توکن remember me
clear_remember_token($pdo);

// هدایت به صفحه اصلی
header("Location: index.php");
exit();