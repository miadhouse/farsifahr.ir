<?php
$user_id = $_SESSION['user_id'];

// دریافت کد معرف کاربر
$stmtCode = $pdo->prepare("SELECT referral_code FROM users WHERE id = ?");
$stmtCode->execute([$user_id]);
$user_data = $stmtCode->fetch();
$referral_code = $user_data['referral_code'] ?? 'نامشخص';

// تعداد کسانی که با کد کاربر ثبت‌نام کرده‌اند
$stmtReg = $pdo->prepare("SELECT COUNT(*) FROM users WHERE referred_by_id = ?");
$stmtReg->execute([$user_id]);
$registered_count = $stmtReg->fetchColumn();

// تعداد کسانی که خرید کرده‌اند
$stmtBuy = $pdo->prepare("
    SELECT COUNT(DISTINCT user_id) 
    FROM user_subscriptions 
    WHERE referred_by_id = ? AND status IN ('active', 'expired')
");
$stmtBuy->execute([$user_id]);
$purchased_count = $stmtBuy->fetchColumn();

// لیست افرادی که معرفی شده‌اند و وضعیت خریدشان
$stmtRefList = $pdo->prepare("
    SELECT u.name, u.email, u.created_at, 
           (SELECT COUNT(*) FROM user_subscriptions us WHERE us.user_id = u.id AND us.status IN ('active', 'expired')) as has_purchased
    FROM users u 
    WHERE u.referred_by_id = ?
    ORDER BY u.created_at DESC
");
$stmtRefList->execute([$user_id]);
$referrals_list = $stmtRefList->fetchAll();

// روزهای هدیه دریافتی (از معرفی دیگران)
// این روزها به صورت تمدید اشتراک یا ایجاد اشتراک جدید اعمال شده‌اند
// فعلاً یک تخمین ازPurchased count * bonus_referrer می‌زنیم یا اگر در دیتابیس دقیق‌تر داشتیم استفاده می‌کردیم
$bonus_referrer_days = (int)get_setting('referral_reward_referrer', $pdo, 14);
$total_bonus_days = $purchased_count * $bonus_referrer_days;

?>

<div class="col-12">
    <div class="card mb-4 shadow-sm">
        <div class="card-header bg-primary text-white">
            <h5 class="mb-0 text-white"><i class="bx bx-gift me-2"></i> سیستم معرفی و کسب اشتراک رایگان</h5>
        </div>
        <div class="card-body pt-4">
            <div class="row align-items-center">
                <div class="col-md-8">
                    <h4 class="fw-bold mb-3">دوستان خود را دعوت کنید و اشتراک رایگان بگیرید!</h4>
                    <p class="mb-4">با اشتراک‌گذاری کد معرف خود، هم شما و هم دوستانتان هدیه می‌گیرید:</p>
                    <ul class="list-unstyled mb-4">
                        <li class="mb-2"><i class="bx bx-check-circle text-success me-2"></i> <strong>دوست شما:</strong> با اولین خرید، ۷ روز اشتراک هدیه دریافت می‌کند.</li>
                        <li class="mb-2"><i class="bx bx-check-circle text-success me-2"></i> <strong>شما:</strong> بابت هر خرید دوستانتان، ۱۴ روز اشتراک VIP رایگان هدیه می‌گیرید!</li>
                    </ul>
                    
                    <div class="referral-box p-3 bg-light border rounded d-inline-block">
                        <span class="text-muted d-block small mb-1">کد معرف اختصاصی شما:</span>
                        <div class="d-flex align-items-center">
                            <h3 class="fw-bold mb-0 text-primary me-3" id="referralCode"><?= htmlspecialchars($referral_code) ?></h3>
                            <button class="btn btn-sm btn-outline-primary" onclick="copyReferralCode()">کپی کد</button>
                        </div>
                    </div>
                </div>
                <div class="col-md-4 text-center d-none d-md-block">
                    <img src="../assets/images/referral-reward.svg" alt="Referral" class="img-fluid" style="max-height: 200px;">
                </div>
            </div>
        </div>
    </div>

    <!-- Stats -->
    <div class="row mb-4">
        <div class="col-md-4">
            <div class="card text-center h-100 shadow-sm">
                <div class="card-body">
                    <div class="badge bg-label-info p-3 rounded mb-3">
                        <i class="bx bx-user-plus fs-3"></i>
                    </div>
                    <h5 class="card-title mb-1">ثبت‌نام شده‌ها</h5>
                    <h2 class="fw-bold mb-0"><?= $registered_count ?></h2>
                    <small class="text-muted">نفر</small>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card text-center h-100 shadow-sm border-success" style="border-width: 2px;">
                <div class="card-body">
                    <div class="badge bg-label-success p-3 rounded mb-3">
                        <i class="bx bx-cart fs-3"></i>
                    </div>
                    <h5 class="card-title mb-1">خریدهای موفق</h5>
                    <h2 class="fw-bold mb-0"><?= $purchased_count ?></h2>
                    <small class="text-muted">مورد</small>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card text-center h-100 shadow-sm">
                <div class="card-body">
                    <div class="badge bg-label-warning p-3 rounded mb-3">
                        <i class="bx bx-time fs-3"></i>
                    </div>
                    <h5 class="card-title mb-1">هدیه دریافتی شما</h5>
                    <h2 class="fw-bold mb-0"><?= $total_bonus_days ?></h2>
                    <small class="text-muted">روز اشتراک VIP</small>
                </div>
            </div>
        </div>
    </div>

    <!-- Referral List -->
    <div class="card shadow-sm">
        <div class="card-header border-bottom">
            <h5 class="mb-0">لیست افراد معرفی شده</h5>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <?php if (empty($referrals_list)): ?>
                    <div class="text-center py-4">
                        <p class="text-muted mb-0">هنوز کسی را معرفی نکرده‌اید. همین حالا شروع کنید!</p>
                    </div>
                <?php else: ?>
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>نام</th>
                                <th>ایمیل</th>
                                <th>تاریخ عضویت</th>
                                <th>وضعیت خرید</th>
                                <th>هدیه شما</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($referrals_list as $ref): ?>
                                <tr>
                                    <td><?= htmlspecialchars($ref['name']) ?></td>
                                    <td><?= htmlspecialchars(substr($ref['email'], 0, 3) . '***' . strstr($ref['email'], '@')) ?></td>
                                    <td><small><?= date('Y/m/d', strtotime($ref['created_at'])) ?></small></td>
                                    <td>
                                        <?php if ($ref['has_purchased'] > 0): ?>
                                            <span class="badge bg-success">خرید انجام شد</span>
                                        <?php else: ?>
                                            <span class="badge bg-secondary">در انتظار خرید</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if ($ref['has_purchased'] > 0): ?>
                                            <span class="text-success fw-bold">+ <?= $bonus_referrer_days ?> روز</span>
                                        <?php else: ?>
                                            <span class="text-muted">-</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<script>
function copyReferralCode() {
    const code = document.getElementById('referralCode').innerText;
    navigator.clipboard.writeText(code).then(() => {
        Swal.fire({
            icon: 'success',
            title: 'کپی شد!',
            text: 'کد معرف با موفقیت کپی شد.',
            timer: 1500,
            showConfirmButton: false
        });
    });
}
</script>