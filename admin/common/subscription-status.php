<?php
$navbar_active_sub = get_user_active_subscription($_SESSION['user_id'], $pdo);
$navbar_pending_sub = get_user_pending_subscription($_SESSION['user_id'], $pdo);

$status_label = __('free_subscription', 'اشتراک رایگان');
$status_class = 'bg-label-secondary';

if ($navbar_active_sub && $navbar_active_sub['plan_slug'] !== 'free') {
    $dur_label = '';
    if ($navbar_active_sub['duration_days'] > 0) {
        if ($navbar_active_sub['duration_days'] == 14) $dur_label = ' (' . __('duration_2_weeks', '۲ هفته') . ')';
        elseif ($navbar_active_sub['duration_days'] == 30) $dur_label = ' (' . __('duration_1_month', '۱ ماه') . ')';
        elseif ($navbar_active_sub['duration_days'] == 90) $dur_label = ' (' . __('duration_3_months', '۳ ماه') . ')';
        elseif ($navbar_active_sub['duration_days'] == 180) $dur_label = ' (' . __('duration_6_months', '۶ ماه') . ')';
        elseif ($navbar_active_sub['duration_days'] == 365) $dur_label = ' (' . __('duration_1_year', '۱ سال') . ')';
        else $dur_label = ' (' . $navbar_active_sub['duration_days'] . ' ' . __('days', 'روز') . ')';
    }

    // محاسبه روزهای باقی‌مانده
    $days_remaining = '';
    if ($navbar_active_sub['expires_at']) {
        $now = new DateTime();
        $expires = new DateTime($navbar_active_sub['expires_at']);
        $diff = $now->diff($expires);
        if ($expires > $now) {
            $days_remaining = ' - ' . $diff->days . ' ' . __('days_left', 'روز مانده');
        } else {
            $days_remaining = ' - ' . __('expired', 'منقضی شده');
        }
    }

    $status_label = $navbar_active_sub['plan_name'] . $dur_label . $days_remaining;
    $status_class = 'bg-label-success';
} elseif ($navbar_pending_sub) {
    $dur_label = '';
    if ($navbar_pending_sub['duration_days'] > 0) {
        if ($navbar_pending_sub['duration_days'] == 14) $dur_label = ' ' . __('duration_2_weeks', '۲ هفته');
        elseif ($navbar_pending_sub['duration_days'] == 30) $dur_label = ' ' . __('duration_1_month', '۱ ماه');
        elseif ($navbar_pending_sub['duration_days'] == 90) $dur_label = ' ' . __('duration_3_months', '۳ ماه');
        elseif ($navbar_pending_sub['duration_days'] == 180) $dur_label = ' ' . __('duration_6_months', '۶ ماه');
        elseif ($navbar_pending_sub['duration_days'] == 365) $dur_label = ' ' . __('duration_1_year', '۱ سال');
    }
    $status_label = $navbar_pending_sub['plan_name'] . $dur_label . ' (' . __('in_review', 'در حال بازبینی') . ')';
    $status_class = 'bg-label-warning';
}
?>

<li class="nav-item me-2 me-xl-0 d-flex align-items-center">
    <div class="d-flex align-items-center bg-label-light rounded-pill px-3 py-1 border shadow-sm" style="background-color: rgba(255, 255, 255, 0.05);">
        <span class="badge <?= $status_class ?> me-1">
            <i class="bx bx-wallet me-1 small"></i>
            <?= htmlspecialchars($status_label) ?>
        </span>
        
        <?php if (($navbar_active_sub === false || $navbar_active_sub['plan_slug'] === 'free') && !$navbar_pending_sub): ?>
            <div class="d-flex align-items-center ms-2 border-start ps-2">
                <a href="subscription.php" class="btn btn-xs btn-primary py-0 px-2 rounded-pill">
                    <?= __('upgrade', 'ارتقاء') ?> <i class="bx bx-up-arrow-alt ms-1 small"></i>
                </a>
            </div>
        <?php endif; ?>

        <?php if ($navbar_pending_sub): ?>
            <?php 
                $nav_wa_msg = "سلام، من درخواست اشتراک " . $navbar_pending_sub['plan_name'] . " با مبلغ " . number_format($navbar_pending_sub['amount_paid']) . " تومان را در سایت فارسی‌فهر ثبت کردم.\nایمیل من: " . ($_SESSION['email'] ?? 'نامشخص') . "\nلطفا فعال کنید.";
            ?>
            <div class="d-flex align-items-center ms-2 border-start ps-2">
                <a href="https://wa.me/989177876760?text=<?= urlencode($nav_wa_msg) ?>" 
                   target="_blank" 
                   class="btn btn-icon btn-xs btn-success rounded-circle me-1" 
                   title="<?= __('whatsapp', 'واتس‌اپ') ?>">
                    <i class="bx bxl-whatsapp"></i>
                </a>
                <a href="https://t.me/farsifahr" 
                   target="_blank" 
                   class="btn btn-icon btn-xs btn-info rounded-circle" 
                   title="<?= __('telegram_support', 'تلگرام') ?>">
                    <i class="bx bxl-telegram"></i>
                </a>
            </div>
        <?php endif; ?>
    </div>
</li>