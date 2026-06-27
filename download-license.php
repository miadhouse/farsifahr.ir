<?php
// download-license.php
require_once __DIR__ . '/incloud/functions.php';

if (!is_logged_in()) {
    header("HTTP/1.1 401 Unauthorized");
    exit("دسترسی غیرمجاز. لطفا ابتدا وارد حساب خود شوید.");
}

$file = $_GET['file'] ?? '';
if (empty($file)) {
    header("HTTP/1.1 400 Bad Request");
    exit("فایل نامشخص است.");
}

// نرمال‌سازی مسیر فایل برای جلوگیری از Directory Traversal
$file = str_replace(['..', '\\', '//'], '', $file);

// دریافت اطلاعات از دیتابیس برای بررسی مالکیت
$stmt = $pdo->prepare("SELECT user_id FROM license_translation_requests WHERE front_image_path = ? OR back_image_path = ? LIMIT 1");
$stmt->execute([$file, $file]);
$request = $stmt->fetch();

if (!$request) {
    header("HTTP/1.1 404 Not Found");
    exit("رکورد درخواست برای این فایل یافت نشد.");
}

// بررسی دسترسی: فقط خود کاربر یا ادمین اصلی
if ($request['user_id'] != $_SESSION['user_id'] && !is_super_admin()) {
    header("HTTP/1.1 403 Forbidden");
    exit("شما دسترسی به این فایل را ندارید.");
}

$filePath = __DIR__ . '/miad/storage/app/' . $file;

if (!file_exists($filePath)) {
    header("HTTP/1.1 404 Not Found");
    exit("فایل روی سرور یافت نشد.");
}

$mime = mime_content_type($filePath);
header("Content-Type: " . $mime);
header("Content-Length: " . filesize($filePath));
header("Content-Disposition: inline; filename=\"" . basename($filePath) . "\"");
readfile($filePath);
exit;
