<?php
/**
 * صفحه ادمین برای تایید اشتراک‌های معلق
 * استفاده: approve-subscription.php?id=123
 */

session_start();
require_once('../config/config.php');
require_once('../incloud/subscription-functions.php');

// بررسی دسترسی ادمین (این بخش را متناسب با سیستم خود تنظیم کنید)
if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'admin') {
    die('دسترسی غیرمجاز');
}

$message = '';
$error = '';

// تایید اشتراک
if (isset($_GET['id']) && is_numeric($_GET['id'])) {
    $subscription_id = intval($_GET['id']);
    
    if (activate_pending_subscription($subscription_id, $pdo)) {
        $message = 'اشتراک با موفقیت تایید و فعال شد.';
    } else {
        $error = 'خطا در فعال‌سازی اشتراک.';
    }
}

// دریافت لیست اشتراک‌های معلق
$stmt = $pdo->prepare("
    SELECT us.*, u.username, u.email, sp.name as plan_name
    FROM user_subscriptions us
    JOIN users u ON us.user_id = u.id
    JOIN subscription_plans sp ON us.plan_id = sp.id
    WHERE us.status = 'pending'
    ORDER BY us.created_at DESC
");
$stmt->execute();
$pending_subscriptions = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>تایید اشتراک‌ها - پنل ادمین</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <div class="container mt-5">
        <h2 class="mb-4">تایید اشتراک‌های معلق</h2>
        
        <?php if ($message): ?>
            <div class="alert alert-success alert-dismissible fade show">
                <?= htmlspecialchars($message) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>
        
        <?php if ($error): ?>
            <div class="alert alert-danger alert-dismissible fade show">
                <?= htmlspecialchars($error) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>
        
        <?php if (empty($pending_subscriptions)): ?>
            <div class="alert alert-info">
                هیچ اشتراک معلقی وجود ندارد.
            </div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table table-bordered table-hover">
                    <thead class="table-light">
                        <tr>
                            <th>شناسه</th>
                            <th>کاربر</th>
                            <th>ایمیل</th>
                            <th>پلن</th>
                            <th>مبلغ</th>
                            <th>مدت (روز)</th>
                            <th>تاریخ ایجاد</th>
                            <th>عملیات</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($pending_subscriptions as $sub): ?>
                            <tr>
                                <td><?= $sub['id'] ?></td>
                                <td><?= htmlspecialchars($sub['username']) ?></td>
                                <td><?= htmlspecialchars($sub['email']) ?></td>
                                <td><?= htmlspecialchars($sub['plan_name']) ?></td>
                                <td><?= number_format($sub['amount_paid']) ?> تومان</td>
                                <td><?= $sub['duration_days'] ?> روز</td>
                                <td><?= $sub['created_at'] ?></td>
                                <td>
                                    <a href="?id=<?= $sub['id'] ?>" 
                                       class="btn btn-success btn-sm"
                                       onclick="event.preventDefault(); let url = this.href; Swal.fire({title: 'توجه', text: 'آیا مطمئن هستید؟', icon: 'warning', showCancelButton: true, confirmButtonText: 'بله', cancelButtonText: 'خیر'}).then((result) => { if(result.isConfirmed) { window.location.href = url; } })">
                                        تایید
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>