<?php
require_once('../incloud/functions.php');

// بررسی ورود کاربر
if (!is_logged_in()) {
    header('Location: /register.php');
    exit;
}

// دریافت گزارش‌های اخیر کاربر
$stmtReports = $pdo->prepare("
    SELECT qr.*, q.number as q_number 
    FROM question_reports qr
    JOIN questions q ON qr.question_id = q.id
    WHERE qr.user_id = ? 
    ORDER BY qr.created_at DESC 
");
$stmtReports->execute([$_SESSION['user_id']]);
$user_reports = $stmtReports->fetchAll();
?>

<div class="col-12 mb-4">
    <div class="card">
        <div class="card-header bg-label-danger d-flex justify-content-between align-items-center">
            <h5 class="mb-0">
                <i class="bx bx-bug me-2"></i> پیگیری گزارش‌های خطای سوالات
            </h5>
        </div>
        <div class="card-body mt-3">
            <?php if (count($user_reports) > 0): ?>
                <div class="table-responsive text-nowrap">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>شماره سوال</th>
                                <th>تاریخ ثبت</th>
                                <th>وضعیت</th>
                                <th>توضیحات / علت رد</th>
                            </tr>
                        </thead>
                        <tbody class="table-border-bottom-0">
                            <?php foreach ($user_reports as $report): ?>
                                <tr>
                                    <td><strong><?php echo htmlspecialchars($report['q_number']); ?></strong></td>
                                    <td>
                                        <?php 
                                        $date = new DateTime($report['created_at']);
                                        echo $date->format('Y/m/d');
                                        ?>
                                    </td>
                                    <td>
                                        <?php if ($report['status'] === 'pending'): ?>
                                            <span class="badge bg-label-warning">در انتظار بررسی</span>
                                        <?php elseif ($report['status'] === 'approved'): ?>
                                            <span class="badge bg-label-success">تایید شده <i class="bx bxs-gift ms-1"></i></span>
                                        <?php else: ?>
                                            <span class="badge bg-label-danger">رد شده</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if ($report['status'] === 'approved'): ?>
                                            <span class="text-success small">
                                                هدیه (اشتراک VIP) به حساب شما اضافه شد.
                                            </span>
                                        <?php elseif ($report['status'] === 'rejected' && $report['rejection_reason']): ?>
                                            <span class="text-danger small">
                                                <strong>علت رد:</strong> <?php echo htmlspecialchars($report['rejection_reason']); ?>
                                            </span>
                                        <?php else: ?>
                                            <span class="text-muted small"><?php echo htmlspecialchars(mb_strimwidth($report['message'], 0, 50, "...")); ?></span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <p class="text-muted text-center mb-0 py-3">شما هنوز هیچ گزارشی ثبت نکرده‌اید.</p>
            <?php endif; ?>
        </div>
    </div>
</div>
