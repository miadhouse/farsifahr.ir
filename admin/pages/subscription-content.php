<?php

use Google\Service\AdExchangeBuyerII\TimeOfDay;

$user_sub = get_user_active_subscription($_SESSION['user_id'], $pdo);
$user_plan_status = '';
$days_left = 0;
$expires_at = '';
$interval = '';

// بررسی صحیح وجود اشتراک
if ($user_sub !== false && $user_sub !== null) {
  if ($user_sub['plan_slug'] !== 'free') {
    $user_plan_status = 'active';
    $today = new DateTime();
    
    // بررسی اینکه expires_at null نباشد
    if (!empty($user_sub['expires_at'])) {
      $expires_at = new DateTime($user_sub['expires_at']);
      $interval = $today->diff($expires_at);
      $days_left = (int) $interval->format('%r%a');
    } else {
      $expires_at = 'بدون تاریخ انقضاء';
      $interval = '0';
      $days_left = 0;
    }
  } else {
    $user_plan_status = 'free';
    $expires_at = 'بدون تاریخ انقضاء';
    $interval = '0';
    $days_left = 0;
  }
  
  // اطمینان از وجود کلیدهای مورد نیاز
  if (!isset($user_sub['started_at'])) {
    $user_sub['started_at'] = null;
  }
  if (!isset($user_sub['amount_paid'])) {
    $user_sub['amount_paid'] = 0;
  }
} else {
  // اگر هیچ اشتراکی وجود نداشت، پلن رایگان را تنظیم کنید
  $user_plan_status = 'free';
  $user_sub = [
    'plan_name' => 'رایگان',
    'plan_slug' => 'free',
    'amount_paid' => 0,
    'expires_at' => null,
    'started_at' => null
  ];
  $expires_at = 'بدون تاریخ انقضاء';
}
?>
<div class="container-xxl flex-grow-1 container-p-y">
  <h4 class="py-3 breadcrumb-wrapper mb-4">
    <span class="text-muted fw-light"></span> اشتراک ها و صورت حساب ها
  </h4>
  <div class="row">
    <div class="col-md-12">
      <div class="card mb-4">
        <!-- Current Plan -->
        <div class="card-body">
          <div class="row">
            <div class="col-md-6 mb-1">
              <div class="mb-4">
                <h6 class="fw-semibold mb-2">اشتراک کنونی شما <span class="badge bg-gradient p-2 fs-3">
                    <h4><?= htmlspecialchars($user_sub['plan_name']) ?></h4>
                  </span> است</h6>
                <p>قیمت کنونی اشتراک: <?= number_format($user_sub['amount_paid']) ?> تومان</p>
              </div>
              <div class="mb-4">
                <?php if ($user_sub['plan_slug'] == 'free'): ?>
                  <h6 class="fw-semibold mb-2">بدون تاریخ انقضا</h6>
                  <p>این اشتراک به صورت پیش فرض برای تمام کاربران فعال است.</p>
                  <p>در این اشتراک فقط به 200 سوال اول دسترسی دارید. جهت استفاده نامحدود لطفا اشتراک VIP خود را فعال کنید.</p>
                <?php else: ?>
                  <h6 class="fw-semibold mb-2">تاریخ انقضا :</h6>
                  <h4 class="fw-semibold mb-2">
                    <?= !empty($user_sub['expires_at']) ? htmlspecialchars($user_sub['expires_at']) : 'نامشخص' ?>
                  </h4>
                  <h6 class="fw-semibold mb-2">
                    <?php if (!empty($user_sub['started_at'])): ?>
                      شما در تاریخ <?= htmlspecialchars($user_sub['started_at']) ?> این اشتراک را خریداری کرده اید.
                    <?php endif; ?>
                  </h6>
                <?php endif ?>
              </div>
              <div class="mb-3">
                <p><?= $user_sub['plan_slug'] == 'free' ? 'دسترسی محدود به 200 سوال اول' : 'دسترسی نامحدود به تمام سوالات' ?></p>
              </div>
            </div>
            <div class="col-md-6 mb-1">

              <?php if ($user_sub['plan_slug'] == 'free'): ?>
                <div class="alert alert-warning mb-4" role="alert">
                  <h6 class="alert-heading mb-1">جهت استفاده نامحدود از تمام سوالات، اشتراک VIP خود را فعال کنید</h6>
                </div>
              <?php else: ?>
                <?php if ($days_left >= 6): ?>
                  <div
                    class="alert alert-<?= $days_left > 20 ? 'success' : ($days_left >= 6 ? 'primary' : 'warning') ?> mb-4"
                    role="alert">
                    <h6 class="alert-heading mb-1">
                      <?php echo " {$days_left} روز تا پایان اشتراک شما باقی مانده است."; ?>
                    </h6>
                  </div>
                <?php else: ?>
                  <div class="alert alert-danger mb-4" role="alert">
                    <h6 class="alert-heading mb-1">اشتراک شما در حال پایان است! تنها <?= $days_left ?> روز باقی مانده است.
                    </h6>
                  </div>
                <?php endif; ?>

                <div class="plan-statistics">
                  <?php
                  // محاسبه درصد استفاده از دوره اشتراک
                  $total_days = 30; // مقدار پیش‌فرض
                  if (!empty($user_sub['started_at']) && !empty($user_sub['expires_at'])) {
                    try {
                      $start = new DateTime($user_sub['started_at']);
                      $end = new DateTime($user_sub['expires_at']);
                      $total_days = $start->diff($end)->days;
                    } catch (Exception $e) {
                      $total_days = 30; // در صورت خطا مقدار پیش‌فرض
                    }
                  }
                  $used_days = max(0, $total_days - $days_left); // اطمینان از مثبت بودن
                  $progress_percentage = $total_days > 0 ? round(($used_days / $total_days) * 100) : 0;
                  ?>
                  <div class="d-flex justify-content-between">
                    <span class="fw-semibold mb-2">روز</span>
                    <span class="fw-semibold mb-2"><?= $used_days ?> از <?= $total_days ?> روز</span>
                  </div>
                  <div class="progress">
                    <div class="progress-bar w-<?= $progress_percentage ?>" role="progressbar"
                      aria-valuenow="<?= $progress_percentage ?>" aria-valuemin="0" aria-valuemax="100"></div>
                  </div>
                  <p class="mt-2 mb-0"><?= $days_left ?> روز باقی مانده تا اشتراک شما نیازمند تمدید باشد</p>
                </div>
              <?php endif ?>

            </div>
            <div class="col-12">
              <button class="btn btn-primary me-2 mt-2" data-bs-toggle="modal" data-bs-target="#pricingModal">
                <?php echo ($user_plan_status === 'free') ? 'ارتقاء اشتراک' : 'تغییر اشتراک'; ?>
              </button>
              <?php if ($user_plan_status !== 'free'): ?>
                <button class="btn btn-label-secondary cancel-subscription mt-2">لغو اشتراک</button>
              <?php endif ?>
            </div>
          </div>
        </div>
        <!-- /Current Plan -->
      </div>


    </div>
  </div>

<!-- Pricing Modal -->
  <div class="modal fade" id="pricingModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-simple modal-pricing modal-dialog-scrollable">
      <div class="modal-content bg-body p-2 p-md-5">
        <div class="modal-body">
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
          <!-- Pricing Plans -->
          <section class="our-price-plan-area tmp-section-gapTop" id="pricing">
            <div class="container-fluid">
              <div class="section-head">
                <div class="section-sub-title center-title tmp-scroll-trigger tmp-fade-in animation-order-1">
                  <span class="subtitle">جدول اشتراک ها</span>
                </div>
                <h2 class="title split-collab tmp-scroll-trigger tmp-fade-in animation-order-2">قیمتگذاری ساده و شفاف
                </h2>
                <p>طرحی را انتخاب کنید که به بهترین وجه با نیازهای شما مطابقت داشته باشد.</p>
              </div>

              <div class="row align-items-center justify-content-center">
                <?php
                // دریافت همه پلن‌های فعال
                $stmt = $pdo->prepare("SELECT * FROM subscription_plans WHERE is_active = 1 ORDER BY sort_order ASC");
                $stmt->execute();
                $plans = $stmt->fetchAll(PDO::FETCH_ASSOC);

                foreach ($plans as $plan):
                  // کلاس رنگ پلن
                  $plan_class = '';
                  $card_class = '';
                  if ($plan['slug'] == 'vip') {
                    $plan_class = 'text-primary';
                    $card_class = 'border-primary';
                  }
                  ?>

                  <div class="col-lg-6 col-md-12 mb-4">
                    <div class="card <?= $card_class ?> h-100">
                      <div class="card-header text-center bg-label-primary">
                        <h3 class="<?= $plan_class ?> mb-0"><?= htmlspecialchars($plan['name']) ?></h3>
                      </div>
                      <div class="card-body">
                        <p class="text-center mb-4"><?= htmlspecialchars($plan['description']) ?></p>

                        <?php if ($plan['slug'] == 'free'): ?>
                          <!-- پلن رایگان -->
                          <div class="text-center mb-4">
                            <h2 class="display-4 fw-bold">رایگان</h2>
                            <p class="text-muted">دسترسی محدود به 200 سوال اول</p>
                          </div>
                          <ul class="list-unstyled mb-4">
                            <li class="mb-2">
                              <i class="fa fa-check-circle text-success"></i> دسترسی به 200 سوال اول
                            </li>
                            <li class="mb-2">
                              <i class="fa fa-check-circle text-success"></i> بدون محدودیت زمانی
                            </li>
                            <li class="mb-2">
                              <i class="fa fa-times-circle text-muted"></i> دسترسی به سوالات بالاتر از 200
                            </li>
                          </ul>
                          <div class="text-center">
                            <button class="btn btn-outline-secondary btn-lg w-100" disabled>پلن فعلی شما</button>
                          </div>

                        <?php else: ?>
                          <!-- پلن VIP -->
                          <div class="mb-4">
                            <h5 class="text-center mb-3">انتخاب دوره اشتراک:</h5>
                            
                            <!-- دوره 2 هفته‌ای -->
                            <?php if ($plan['price_2_weeks'] > 0): ?>
                            <div class="pricing-option mb-3 p-2 p-md-3 border rounded">
                              <div class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center gap-2">
                                <div class="flex-grow-1">
                                  <h6 class="mb-1">2 هفته (14 روز)</h6>
                                  <p class="text-muted small mb-0">دسترسی نامحدود به تمام سوالات</p>
                                </div>
                                <div class="text-start text-md-end mt-2 mt-md-0">
                                  <h5 class="mb-2 text-primary"><?= number_format($plan['price_2_weeks']) ?> تومان</h5>
                                  <a href="invoice-request.php?plan-id=<?= $plan['id'] ?>&duration=2_weeks" 
                                     class="btn btn-sm btn-primary w-100">خرید</a>
                                </div>
                              </div>
                            </div>
                            <?php endif; ?>

                            <!-- دوره 1 ماهه -->
                            <?php if ($plan['price_1_month'] > 0): ?>
                            <div class="pricing-option mb-3 p-2 p-md-3 border rounded">
                              <div class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center gap-2">
                                <div class="flex-grow-1">
                                  <h6 class="mb-1">1 ماه (30 روز)</h6>
                                  <p class="text-muted small mb-0">دسترسی نامحدود به تمام سوالات</p>
                                </div>
                                <div class="text-start text-md-end mt-2 mt-md-0">
                                  <h5 class="mb-2 text-primary"><?= number_format($plan['price_1_month']) ?> تومان</h5>
                                  <a href="invoice-request.php?plan-id=<?= $plan['id'] ?>&duration=1_month" 
                                     class="btn btn-sm btn-primary w-100">خرید</a>
                                </div>
                              </div>
                            </div>
                            <?php endif; ?>

                            <!-- دوره 3 ماهه -->
                            <?php if ($plan['price_3_months'] > 0 && $plan['price_1_month'] > 0): 
                              $saving_3m = ($plan['price_1_month'] * 3) - $plan['price_3_months'];
                              $discount_3m = round(($saving_3m / ($plan['price_1_month'] * 3)) * 100);
                            ?>
                            <div class="pricing-option mb-3 p-2 p-md-3 border rounded border-success bg-light">
                              <div class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center gap-2">
                                <div class="flex-grow-1">
                                  <h6 class="mb-1">
                                    3 ماه (90 روز) 
                                    <span class="badge bg-success">پیشنهاد ویژه - <?= $discount_3m ?>% تخفیف</span>
                                  </h6>
                                  <p class="text-muted small mb-0">دسترسی نامحدود به تمام سوالات</p>
                                  <?php if ($saving_3m > 0): ?>
                                  <p class="text-success small mb-0 fw-bold">
                                    <i class="fa fa-tag"></i> صرفه‌جویی: <?= number_format($saving_3m) ?> تومان
                                  </p>
                                  <?php endif; ?>
                                </div>
                                <div class="text-start text-md-end mt-2 mt-md-0">
                                  <?php if ($saving_3m > 0): ?>
                                  <small class="text-decoration-line-through text-muted d-block">
                                    <?= number_format($plan['price_1_month'] * 3) ?> تومان
                                  </small>
                                  <?php endif; ?>
                                  <h5 class="mb-2 text-success"><?= number_format($plan['price_3_months']) ?> تومان</h5>
                                  <a href="invoice-request.php?plan-id=<?= $plan['id'] ?>&duration=3_months" 
                                     class="btn btn-sm btn-success w-100">خرید</a>
                                </div>
                              </div>
                            </div>
                            <?php endif; ?>

                            <!-- دوره 6 ماهه -->
                            <?php if ($plan['price_6_months'] > 0 && $plan['price_1_month'] > 0): 
                              $saving_6m = ($plan['price_1_month'] * 6) - $plan['price_6_months'];
                              $discount_6m = round(($saving_6m / ($plan['price_1_month'] * 6)) * 100);
                            ?>
                            <div class="pricing-option mb-3 p-2 p-md-3 border rounded border-info">
                              <div class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center gap-2">
                                <div class="flex-grow-1">
                                  <h6 class="mb-1">
                                    6 ماه (180 روز) 
                                    <span class="badge bg-info"><?= $discount_6m ?>% تخفیف</span>
                                  </h6>
                                  <p class="text-muted small mb-0">دسترسی نامحدود به تمام سوالات</p>
                                  <?php if ($saving_6m > 0): ?>
                                  <p class="text-success small mb-0 fw-bold">
                                    <i class="fa fa-tag"></i> صرفه‌جویی: <?= number_format($saving_6m) ?> تومان
                                  </p>
                                  <?php endif; ?>
                                </div>
                                <div class="text-start text-md-end mt-2 mt-md-0">
                                  <?php if ($saving_6m > 0): ?>
                                  <small class="text-decoration-line-through text-muted d-block">
                                    <?= number_format($plan['price_1_month'] * 6) ?> تومان
                                  </small>
                                  <?php endif; ?>
                                  <h5 class="mb-2 text-info"><?= number_format($plan['price_6_months']) ?> تومان</h5>
                                  <a href="invoice-request.php?plan-id=<?= $plan['id'] ?>&duration=6_months" 
                                     class="btn btn-sm btn-info w-100">خرید</a>
                                </div>
                              </div>
                            </div>
                            <?php endif; ?>

                            <!-- دوره 1 ساله -->
                            <?php if ($plan['price_1_year'] > 0 && $plan['price_1_month'] > 0): 
                              $saving_1y = ($plan['price_1_month'] * 12) - $plan['price_1_year'];
                              $discount_1y = round(($saving_1y / ($plan['price_1_month'] * 12)) * 100);
                            ?>
                            <div class="pricing-option mb-3 p-2 p-md-3 border rounded border-warning bg-light">
                              <div class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center gap-2">
                                <div class="flex-grow-1">
                                  <h6 class="mb-1">
                                    1 سال (365 روز) 
                                    <span class="badge bg-warning text-dark">
                                      <i class="fa fa-star"></i> بهترین قیمت - <?= $discount_1y ?>% تخفیف
                                    </span>
                                  </h6>
                                  <p class="text-muted small mb-0">دسترسی نامحدود به تمام سوالات</p>
                                  <?php if ($saving_1y > 0): ?>
                                  <p class="text-success small mb-0 fw-bold">
                                    <i class="fa fa-tag"></i> صرفه‌جویی: <?= number_format($saving_1y) ?> تومان
                                  </p>
                                  <?php endif; ?>
                                </div>
                                <div class="text-start text-md-end mt-2 mt-md-0">
                                  <?php if ($saving_1y > 0): ?>
                                  <small class="text-decoration-line-through text-muted d-block">
                                    <?= number_format($plan['price_1_month'] * 12) ?> تومان
                                  </small>
                                  <?php endif; ?>
                                  <h5 class="mb-2 text-warning"><?= number_format($plan['price_1_year']) ?> تومان</h5>
                                  <a href="invoice-request.php?plan-id=<?= $plan['id'] ?>&duration=1_year" 
                                     class="btn btn-sm btn-warning w-100">خرید</a>
                                </div>
                              </div>
                            </div>
                            <?php endif; ?>
                          </div>

                          <ul class="list-unstyled mb-4">
                            <li class="mb-2">
                              <i class="fa fa-check-circle text-success"></i> دسترسی نامحدود به تمام سوالات
                            </li>
                            <li class="mb-2">
                              <i class="fa fa-check-circle text-success"></i> آپدیت‌های رایگان
                            </li>
                            <li class="mb-2">
                              <i class="fa fa-check-circle text-success"></i> پشتیبانی اختصاصی
                            </li>
                            <li class="mb-2">
                              <i class="fa fa-check-circle text-success"></i> دانلود نامحدود محتوا
                            </li>
                          </ul>

                        <?php endif; ?>
                      </div>
                    </div>
                  </div>

                <?php endforeach; ?>
              </div>

              <!-- Price Comparison Table -->
              <div class="row mt-5">
                <div class="col-12">
                  <div class="card">
                    <div class="card-header">
                      <h5 class="card-title mb-0">مقایسه قیمت‌ها</h5>
                    </div>
                    <div class="card-body p-0 p-md-3">
                      <div class="table-responsive">
                        <table class="table table-bordered text-center mb-0">
                          <thead class="table-light">
                            <tr>
                              <th class="small">دوره</th>
                              <th class="small">قیمت</th>
                              <th class="small d-none d-md-table-cell">قیمت روزانه</th>
                              <th class="small d-none d-lg-table-cell">تخفیف</th>
                            </tr>
                          </thead>
                          <tbody>
                            <?php
                            $vip_plan = null;
                            foreach ($plans as $p) {
                              if ($p['slug'] == 'vip') {
                                $vip_plan = $p;
                                break;
                              }
                            }
                            
                            if ($vip_plan):
                              $durations = [
                                ['key' => 'price_2_weeks', 'days' => 14, 'label' => '2 هفته'],
                                ['key' => 'price_1_month', 'days' => 30, 'label' => '1 ماه'],
                                ['key' => 'price_3_months', 'days' => 90, 'label' => '3 ماه'],
                                ['key' => 'price_6_months', 'days' => 180, 'label' => '6 ماه'],
                                ['key' => 'price_1_year', 'days' => 365, 'label' => '1 سال']
                              ];
                              
                              foreach ($durations as $dur):
                                if ($vip_plan[$dur['key']] <= 0) continue;
                                
                                $price = $vip_plan[$dur['key']];
                                $daily_price = $price / $dur['days'];
                                $monthly_equivalent = ($price / $dur['days']) * 30;
                                $discount_vs_monthly = 0;
                                
                                if ($vip_plan['price_1_month'] > 0 && $dur['key'] != 'price_1_month') {
                                  $discount_vs_monthly = round((1 - ($monthly_equivalent / $vip_plan['price_1_month'])) * 100);
                                }
                            ?>
                            <tr>
                              <td class="fw-semibold small"><?= $dur['label'] ?></td>
                              <td class="small">
                                <div class="d-flex flex-column">
                                  <span class="fw-bold"><?= number_format($price) ?></span>
                                  <small class="text-muted">تومان</small>
                                </div>
                              </td>
                              <td class="small d-none d-md-table-cell">
                                <div class="d-flex flex-column">
                                  <span><?= number_format($daily_price, 0) ?></span>
                                  <small class="text-muted">تومان</small>
                                </div>
                              </td>
                              <td class="small d-none d-lg-table-cell">
                                <?php if ($discount_vs_monthly > 0): ?>
                                  <span class="badge bg-success"><?= $discount_vs_monthly ?>%</span>
                                <?php else: ?>
                                  <span class="text-muted">-</span>
                                <?php endif; ?>
                              </td>
                            </tr>
                            <?php 
                              endforeach;
                            endif; 
                            ?>
                          </tbody>
                        </table>
                      </div>
                    </div>
                  </div>
                </div>
              </div>

            </div>
          </section>
          <!--/ Pricing Plans -->
        </div>
      </div>
    </div>
  </div>
  <!--/ Pricing Modal -->
   <!-- Subscription History -->
    <div class="col-md-12">
      <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
          <h5 class="mb-0">تاریخچه اشتراک‌ها</h5>
        </div>
        <div class="card-body">
          <div class="table-responsive">
            <?php
            // دریافت تاریخچه اشتراک‌های کاربر
            $subscription_history = get_user_subscription_history($_SESSION['user_id'], $pdo);
            
            if (empty($subscription_history)):
            ?>
              <div class="alert alert-info text-center" role="alert">
                <i class="fa fa-info-circle me-2"></i>
                هیچ تاریخچه اشتراکی یافت نشد.
              </div>
            <?php else: ?>
              <table class="table table-hover">
                <thead class="table-light">
                  <tr>
                    <th>ردیف</th>
                    <th>نام پلن</th>
                    <th>مدت زمان</th>
                    <th>مبلغ پرداختی</th>
                    <th>تاریخ شروع</th>
                    <th>تاریخ انقضا</th>
                    <th>وضعیت</th>
                    <th>روش پرداخت</th>
                  </tr>
                </thead>
                <tbody>
                  <?php 
                  $counter = 1;
                  foreach ($subscription_history as $history): 
                    // تعیین رنگ badge بر اساس وضعیت
                    $status_badges = [
                      'active' => ['class' => 'success', 'text' => 'فعال'],
                      'expired' => ['class' => 'danger', 'text' => 'منقضی شده'],
                      'cancelled' => ['class' => 'secondary', 'text' => 'لغو شده'],
                      'pending' => ['class' => 'warning', 'text' => 'در انتظار تایید']
                    ];
                    
                    $status = $status_badges[$history['status']] ?? ['class' => 'secondary', 'text' => 'نامشخص'];
                    
                    // فرمت تاریخ‌ها
                    $started_at = !empty($history['started_at']) ? date('Y/m/d H:i', strtotime($history['started_at'])) : 'نامشخص';
                    $expires_at = !empty($history['expires_at']) ? date('Y/m/d H:i', strtotime($history['expires_at'])) : 'بدون انقضا';
                    
                    // نمایش مدت زمان
                    $duration_text = '';
                    if ($history['duration_days'] > 0) {
                      if ($history['duration_days'] == 14) {
                        $duration_text = '2 هفته';
                      } elseif ($history['duration_days'] == 30) {
                        $duration_text = '1 ماه';
                      } elseif ($history['duration_days'] == 90) {
                        $duration_text = '3 ماه';
                      } elseif ($history['duration_days'] == 180) {
                        $duration_text = '6 ماه';
                      } elseif ($history['duration_days'] == 365) {
                        $duration_text = '1 سال';
                      } else {
                        $duration_text = $history['duration_days'] . ' روز';
                      }
                    } else {
                      $duration_text = 'نامحدود';
                    }
                    
                    // نمایش روش پرداخت
                    $payment_method = !empty($history['payment_method']) ? $history['payment_method'] : '-';
                  ?>
                  <tr>
                    <td><?= $counter++ ?></td>
                    <td>
                      <span class="fw-semibold"><?= htmlspecialchars($history['plan_name']) ?></span>
                      <?php if ($history['plan_slug'] == 'vip'): ?>
                        <i class="fa fa-star text-warning ms-1"></i>
                      <?php endif; ?>
                    </td>
                    <td><?= $duration_text ?></td>
                    <td>
                      <?php if ($history['amount_paid'] > 0): ?>
                        <span class="fw-semibold text-success"><?= number_format($history['amount_paid']) ?> تومان</span>
                      <?php else: ?>
                        <span class="text-muted">رایگان</span>
                      <?php endif; ?>
                    </td>
                    <td><small class="text-muted"><?= $started_at ?></small></td>
                    <td><small class="text-muted"><?= $expires_at ?></small></td>
                    <td>
                      <span class="badge bg-<?= $status['class'] ?>"><?= $status['text'] ?></span>
                    </td>
                    <td><small class="text-muted"><?= $payment_method ?></small></td>
                  </tr>
                  <?php endforeach; ?>
                </tbody>
              </table>
              
          <!-- Statistics -->
              <div class="row mt-4">
                <div class="col-md-4">
                  <div class="card bg-label-primary">
                    <div class="card-body text-center">
                      <h5 class="card-title">کل خریدها</h5>
                      <h3 class="mb-0"><?= count($subscription_history) ?></h3>
                    </div>
                  </div>
                </div>
                <div class="col-md-4">
                  <div class="card bg-label-success">
                    <div class="card-body text-center">
                      <h5 class="card-title">کل پرداختی</h5>
                      <h3 class="mb-0">
                        <?php
                        // فقط اشتراک‌های active و expired را حساب کن
                        $total_paid = 0;
                        foreach ($subscription_history as $sub) {
                          if (in_array($sub['status'], ['active', 'expired'])) {
                            $total_paid += $sub['amount_paid'];
                          }
                        }
                        echo number_format($total_paid);
                        ?> تومان
                      </h3>
                    </div>
                  </div>
                </div>
                <div class="col-md-4">
                  <div class="card bg-label-info">
                    <div class="card-body text-center">
                      <h5 class="card-title">اشتراک‌های فعال</h5>
                      <h3 class="mb-0">
                        <?php
                        $active_count = count(array_filter($subscription_history, function($sub) {
                          return $sub['status'] === 'active';
                        }));
                        echo $active_count;
                        ?>
                      </h3>
                    </div>
                  </div>
                </div>
              </div>
            <?php endif; ?>
          </div>
        </div>
      </div>
    </div>
    <!-- /Subscription History -->
  <script src="../../assets/js/pages-pricing.js"></script>
</div>