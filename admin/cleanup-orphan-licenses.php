<?php
/**
 * اسکریپت تمیزسازی فایل‌های گواهینامه یتیم (Orphan)
 * این اسکریپت فایل‌هایی که بیش از ۲۴ ساعت قدمت دارند و رکوردی در دیتابیس ندارند را حذف می‌کند.
 * 
 * استفاده:
 * 1. از طریق مرورگر: cleanup-orphan-licenses.php?confirm=yes
 * 2. از طریق CLI: php cleanup-orphan-licenses.php
 */

require_once('../config/config.php');

$confirm = isset($_GET['confirm']) && $_GET['confirm'] === 'yes';

if (!$confirm && php_sapi_name() !== 'cli') {
    ?>
    <!DOCTYPE html>
    <html lang="fa" dir="rtl">
    <head>
        <meta charset="UTF-8">
        <title>تمیزسازی تصاویر گواهینامه یتیم</title>
        <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    </head>
    <body>
        <div class="container mt-5">
            <div class="card">
                <div class="card-header bg-warning">
                    <h4 class="mb-0">تمیزسازی تصاویر گواهینامه یتیم</h4>
                </div>
                <div class="card-body">
                    <p>این اسکریپت فایل‌هایی در پوشه licenses را که بیش از ۲۴ ساعت از ایجاد آن‌ها گذشته و به هیچ درخواستی متصل نیستند حذف می‌کند.</p>
                    <p><strong>آیا مطمئن هستید؟</strong></p> 
                    <a href="?confirm=yes" class="btn btn-danger">بله، اجرا کن</a>
                    <a href="javascript:history.back()" class="btn btn-secondary">انصراف</a>
                </div>
            </div>
        </div>
    </body>
    </html>
    <?php
    exit;
}

echo "<pre>";
echo "=== شروع تمیزسازی فایل‌های یتیم ===\n\n";

$upload_dir = __DIR__ . '/../miad/storage/app/licenses/';
if (!is_dir($upload_dir)) {
    echo "پوشه آپلود یافت نشد: {$upload_dir}\n";
    exit;
}

$files = scandir($upload_dir);
$deleted_count = 0;
$scanned_count = 0;

$now = time();
$expiry_time = 24 * 3600; // 24 hours in seconds

foreach ($files as $file) {
    if ($file === '.' || $file === '..') {
        continue;
    }

    $filepath = $upload_dir . $file;
    if (is_file($filepath)) {
        $scanned_count++;
        $file_age = $now - filemtime($filepath);

        if ($file_age > $expiry_time) {
            // بررسی وجود رکورد در دیتابیس
            $db_path = 'licenses/' . $file;
            $stmt = $pdo->prepare("SELECT id FROM license_translation_requests WHERE front_image_path = ? OR back_image_path = ? LIMIT 1");
            $stmt->execute([$db_path, $db_path]);
            $record = $stmt->fetch();

            if (!$record) {
                // فایل یتیم است و باید حذف شود
                if (unlink($filepath)) {
                    echo "✓ حذف فایل یتیم: {$file} (قدمت: " . round($file_age / 3600) . " ساعت)\n";
                    $deleted_count++;
                } else {
                    echo "✗ خطا در حذف فایل: {$file}\n";
                }
            }
        }
    }
}

echo "\n=== خلاصه عملیات ===\n";
echo "تعداد کل فایل‌های اسکن شده: {$scanned_count}\n";
echo "تعداد فایل‌های یتیم حذف شده: {$deleted_count}\n";
echo "=============================\n";
