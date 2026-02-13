<?php
/**
 * اسکریپت تمیزسازی اشتراک‌های تکراری
 * این فایل را یکبار اجرا کنید تا اشتراک‌های تکراری active را برطرف کند
 * 
 * استفاده:
 * 1. از طریق مرورگر: cleanup-duplicate-subscriptions.php?confirm=yes
 * 2. از طریق CLI: php cleanup-duplicate-subscriptions.php
 */

require_once('../config/config.php');
require_once('../functions/subscription-functions.php');

// برای امنیت، نیاز به تایید داریم
$confirm = isset($_GET['confirm']) && $_GET['confirm'] === 'yes';

if (!$confirm && php_sapi_name() !== 'cli') {
    ?>
    <!DOCTYPE html>
    <html lang="fa" dir="rtl">
    <head>
        <meta charset="UTF-8">
        <title>تمیزسازی اشتراک‌های تکراری</title>
        <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    </head>
    <body>
        <div class="container mt-5">
            <div class="card">
                <div class="card-header bg-warning">
                    <h4 class="mb-0">هشدار</h4>
                </div>
                <div class="card-body">
                    <p>این اسکریپت اشتراک‌های تکراری active را برطرف می‌کند.</p>
                    <p>برای هر کاربر، فقط جدیدترین اشتراک active نگه داشته می‌شود و بقیه لغو می‌شوند.</p>
                    <p><strong>آیا مطمئن هستید؟</strong></p> 
                    <a href="?confirm=yes" class="btn btn-danger">بله، ادامه بده</a>
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
echo "=== شروع تمیزسازی اشتراک‌های تکراری ===\n\n";

try {
    // پیدا کردن کاربرانی که بیش از یک اشتراک active دارند
    $stmt = $pdo->prepare("
        SELECT user_id, COUNT(*) as count
        FROM user_subscriptions
        WHERE status = 'active'
        GROUP BY user_id
        HAVING COUNT(*) > 1
    ");
    $stmt->execute();
    $users_with_duplicates = $stmt->fetchAll();
    
    echo "تعداد کاربران با اشتراک تکراری: " . count($users_with_duplicates) . "\n\n";
    
    $total_cancelled = 0;
    
    foreach ($users_with_duplicates as $user) {
        $user_id = $user['user_id'];
        $count = $user['count'];
        
        echo "کاربر #{$user_id} - تعداد اشتراک‌های active: {$count}\n";
        
        // برای هر کاربر، تنها یک اشتراک نگه دار
        $cancelled = ensure_single_active_subscription($user_id, $pdo);
        
        if ($cancelled !== false) {
            echo "  ✓ {$cancelled} اشتراک لغو شد\n";
            $total_cancelled += $cancelled;
        } else {
            echo "  ✗ خطا در پردازش\n";
        }
    }
    
    echo "\n=== خلاصه ===\n";
    echo "کاربران پردازش شده: " . count($users_with_duplicates) . "\n";
    echo "اشتراک‌های لغو شده: {$total_cancelled}\n";
    
    // بررسی نهایی
    $stmt = $pdo->prepare("
        SELECT COUNT(*) as total_active,
               COUNT(DISTINCT user_id) as unique_users
        FROM user_subscriptions
        WHERE status = 'active'
    ");
    $stmt->execute();
    $final_stats = $stmt->fetch();
    
    echo "\nآمار نهایی:\n";
    echo "- اشتراک‌های active: {$final_stats['total_active']}\n";
    echo "- کاربران یکتا: {$final_stats['unique_users']}\n";
    
    if ($final_stats['total_active'] > $final_stats['unique_users']) {
        echo "\n⚠️ هنوز هم اشتراک تکراری وجود دارد! لطفا دوباره اجرا کنید.\n";
    } else {
        echo "\n✓ همه اشتراک‌های تکراری برطرف شدند!\n";
    }
    
    echo "\n=== پایان ===\n";
    
} catch (Exception $e) {
    echo "خطا: " . $e->getMessage() . "\n";
}

echo "</pre>";