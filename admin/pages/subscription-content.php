<?php

use Google\Service\AdExchangeBuyerII\TimeOfDay;

$user_sub = get_user_active_subscription($_SESSION['user_id'], $pdo);
$pending_sub = get_user_pending_subscription($_SESSION['user_id'], $pdo);
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
<style>
  .btn-buy-vip {
    background: linear-gradient(45deg, #696cff, #8e91ff);
    color: #fff !important;
    border: none;
    border-radius: 8px;
    padding: 10px 25px;
    font-weight: 600;
    transition: all 0.3s cubic-bezier(0.175, 0.885, 0.32, 1.275);
    display: inline-flex;
    align-items: center;
    justify-content: center;
    box-shadow: 0 4px 15px rgba(105, 108, 255, 0.3);
    min-width: 140px;
    height: 45px;
  }
  .btn-buy-vip:hover {
    transform: translateY(-3px) scale(1.05);
    box-shadow: 0 8px 20px rgba(105, 108, 255, 0.4);
    background: linear-gradient(45deg, #5f61e6, #8082ff);
  }
  .btn-buy-vip:active {
    transform: translateY(-1px) scale(1);
  }
  .btn-buy-vip i {
    transition: transform 0.3s ease;
  }
  .btn-buy-vip:hover i {
    transform: translateX(-5px);
  }
  
  .pricing-option-row {
    transition: all 0.2s ease;
    border-left: 4px solid transparent !important;
  }
  .pricing-option-row:hover {
    background-color: rgba(105, 108, 255, 0.05);
    border-left-color: #696cff !important;
    transform: translateX(-5px);
  }
  
  .price-text {
    font-size: 1.3rem;
    color: #696cff;
    font-weight: 800;
  }

  .btn-buy-ajax {
    min-width: 80px;
  }
</style>

<div class="container-xxl flex-grow-1 container-p-y">
  <h4 class="py-3 breadcrumb-wrapper mb-4">
    <span class="text-muted fw-light"></span> اشتراک ها و صورت حساب ها
  </h4>

  <div class="row">
    <!-- Current Plan -->
    <div class="col-md-12 mb-4">
      <div class="card shadow-sm">
        <div class="card-body">
          <div class="row">
            <div class="col-md-6 mb-1">
              <div class="mb-4">
                <h6 class="fw-semibold mb-2">اشتراک فعال: 
                  <span class="badge bg-label-primary fs-6 ms-2"><?= htmlspecialchars($user_sub['plan_name']) ?></span>
                </h6>
                <?php if ($user_sub['plan_slug'] != 'free'): ?>
                  <p class="text-muted">مبلغ پرداختی: <?= number_format($user_sub['amount_paid']) ?> یورو</p>
                <?php endif; ?>
              </div>
              <div class="mb-4">
                <?php if ($user_sub['plan_slug'] == 'free'): ?>
                  <h6 class="fw-semibold mb-2 text-warning">دسترسی محدود</h6>
                  <p>شما در حال حاضر به ۵۰۰ سوال اول دسترسی دارید.</p>
                <?php else: ?>
                  <h6 class="fw-semibold mb-1">تاریخ انقضا:</h6>
                  <h5 class="fw-bold mb-2 text-primary">
                    <?= !empty($user_sub['expires_at']) ? htmlspecialchars($user_sub['expires_at']) : 'نامشخص' ?>
                  </h5>
                  <?php if (!empty($user_sub['started_at'])): ?>
                    <p class="small text-muted">تاریخ شروع: <?= htmlspecialchars($user_sub['started_at']) ?></p>
                  <?php endif; ?>
                <?php endif ?>
              </div>
            </div>
            <div class="col-md-6 mb-1">
              <?php if ($user_sub['plan_slug'] == 'free'): ?>
                <div class="alert alert-warning d-flex align-items-center" role="alert">
                  <span class="badge badge-center rounded-pill bg-warning me-2"><i class="bx bx-star"></i></span>
                  <span>جهت دسترسی نامحدود به تمام سوالات، اشتراک VIP خود را فعال کنید.</span>
                </div>
              <?php else: ?>
                <div class="plan-statistics">
                  <?php
                  $total_days = 30;
                  if (!empty($user_sub['started_at']) && !empty($user_sub['expires_at'])) {
                    try {
                      $start = new DateTime($user_sub['started_at']);
                      $end = new DateTime($user_sub['expires_at']);
                      $total_days = $start->diff($end)->days;
                    } catch (Exception $e) { $total_days = 30; }
                  }
                  $used_days = max(0, $total_days - $days_left);
                  $progress_percentage = $total_days > 0 ? min(100, round(($used_days / $total_days) * 100)) : 0;
                  ?>
                  <div class="d-flex justify-content-between mb-2">
                    <span class="fw-semibold small">وضعیت مصرف</span>
                    <span class="fw-semibold small"><?= $days_left ?> روز باقی‌مانده</span>
                  </div>
                  <div class="progress mb-2" style="height: 8px;">
                    <div class="progress-bar bg-primary" role="progressbar"
                      style="width: <?= $progress_percentage ?>%"
                      aria-valuenow="<?= $progress_percentage ?>" aria-valuemin="0" aria-valuemax="100"></div>
                  </div>
                  <p class="small text-muted">شما <?= $used_days ?> روز از <?= $total_days ?> روز دوره خود را سپری کرده‌اید.</p>
                </div>
              <?php endif ?>
            </div>
            <div class="col-12 mt-3 text-center text-md-start">
              <?php if ($user_plan_status !== 'free'): ?>
                <!-- دکمه لغو اشتراک حذف شد طبق درخواست -->
              <?php endif ?>
            </div>
          </div>
        </div>
      </div>
    </div>

    <?php if ($pending_sub): ?>
    <div class="col-12 mb-4">
      <div class="alert alert-info d-flex flex-column align-items-center text-center py-4 shadow-sm" role="alert" style="border: 2px dashed #03c3ec;">
        <h5 class="alert-heading fw-bold mb-3"><i class="bx bx-paper-plane me-2 fs-3"></i>درخواست شما در حال بازبینی است</h5>
        <p class="mb-4">جهت فعال‌سازی اشتراک، الزامی است از طریق دکمه‌های زیر به پشتیبانی اطلاع‌رسانی کنید:</p>
        <div class="d-flex gap-3 flex-wrap justify-content-center">
          <?php 
            $wa_msg = "سلام، من درخواست اشتراک " . $pending_sub['plan_name'] . " با مبلغ " . number_format($pending_sub['amount_paid']) . " تومان را در سایت فارسی‌فهر ثبت کردم.\nایمیل من: " . ($_SESSION['email'] ?? 'نامشخص') . "\nلطفا فعال کنید.";
          ?>
          <a href="https://wa.me/989177876760?text=<?= urlencode($wa_msg) ?>" target="_blank" class="btn btn-success btn-lg shadow">
            <i class="bx bxl-whatsapp me-2 fs-4"></i> اطلاع‌رسانی در واتس‌اپ
          </a>
          <a href="https://t.me/farsifahr" target="_blank" class="btn btn-info btn-lg shadow">
            <i class="bx bxl-telegram me-2 fs-4"></i> اطلاع‌رسانی در تلگرام
          </a>
        </div>
      </div>
    </div>
    <?php endif; ?>
  </div>

  <!-- Pricing Plans Grid -->
  <div class="mb-5">
    <div class="section-head mb-4">
      <h4 class="fw-bold mb-2">انتخاب اشتراک جدید</h4>
      <p class="text-muted">طرحی را انتخاب کنید که به بهترین وجه با نیازهای شما مطابقت داشته باشد.</p>
    </div>

    <div class="row">
      <?php
      $stmt = $pdo->prepare("SELECT * FROM subscription_plans WHERE is_active = 1 ORDER BY sort_order ASC");
      $stmt->execute();
      $plans = $stmt->fetchAll(PDO::FETCH_ASSOC);

      foreach ($plans as $plan):
        $plan_class = ($plan['slug'] == 'vip') ? 'text-primary' : '';
        $card_class = ($plan['slug'] == 'vip') ? 'border-primary' : '';
        ?>
        <div class="col-lg-6 col-12 mb-4">
          <div class="card <?= $card_class ?> h-100 shadow-sm">
            <div class="card-header bg-label-primary py-3">
              <h4 class="<?= $plan_class ?> mb-0 fw-bold"><?= htmlspecialchars($plan['name']) ?></h4>
            </div>
            <div class="card-body pt-4">
              <p class="mb-4 text-muted small"><?= htmlspecialchars($plan['description']) ?></p>
              
              <ul class="list-unstyled mb-4" style="line-height: 2; font-size: 1.1rem;">
                <?php 
                $plan_features = get_plan_features($plan['slug']);
                foreach ($plan_features as $feature):
                ?>
                        <li class="mb-2"><i class="fa fa-check-circle text-success me-2 fs-5"></i> <?= htmlspecialchars($feature) ?></li>
                <?php 
                endforeach;
                ?>
              </ul>

              <?php if ($plan['slug'] == 'free'): ?>
                <div class="text-center py-4 bg-light rounded">
                  <h2 class="fw-bold mb-1">رایگان</h2>
                  <p class="text-muted small mb-3">مناسب برای آشنایی اولیه</p>
                  <?php if ($user_plan_status === 'active'): ?>
                    <button class="btn btn-outline-secondary w-100" disabled>غیرقابل استفاده</button>
                  <?php else: ?>
                    <button class="btn btn-secondary w-100" disabled>پلن فعلی شما</button>
                  <?php endif; ?>
                </div>
              <?php else: ?>
                <div class="vip-durations">
                  <?php 
                  $plan_durations = [];
                  if (!empty($plan['durations'])) {
                      $decoded_durations = json_decode($plan['durations'], true);
                      if (is_array($decoded_durations)) {
                          foreach ($decoded_durations as $index => $d) {
                              $plan_durations[] = [
                                  'key' => 'dyn_' . $index,
                                  'label' => $d['label'],
                                  'duration_days' => $d['days'],
                                  'price' => $d['price'],
                                  'color' => ($index % 2 == 0) ? 'primary' : 'info'
                              ];
                          }
                      }
                  }

                  // Fallback if no dynamic durations found
                  if (empty($plan_durations)) {
                      $fallback_keys = [
                          ['key' => 'price_2_weeks', 'label' => '۲ هفته (۱۴ روز)', 'days' => 14, 'color' => 'primary'],
                          ['key' => 'price_1_month', 'label' => '۱ ماه (۳۰ روز)', 'days' => 30, 'color' => 'primary'],
                          ['key' => 'price_3_months', 'label' => '۳ ماه (۹۰ روز)', 'days' => 90, 'color' => 'success'],
                          ['key' => 'price_6_months', 'label' => '۶ ماه (۱۸۰ روز)', 'days' => 180, 'color' => 'info'],
                          ['key' => 'price_1_year', 'label' => '۱ سال (۳۶۵ روز)', 'days' => 365, 'color' => 'warning']
                      ];
                      foreach ($fallback_keys as $fb) {
                          if (isset($plan[$fb['key']]) && $plan[$fb['key']] > 0) {
                              $plan_durations[] = [
                                  'key' => $fb['key'],
                                  'label' => $fb['label'],
                                  'duration_days' => $fb['days'],
                                  'price' => $plan[$fb['key']],
                                  'color' => $fb['color']
                              ];
                          }
                      }
                  }

                  // پیدا کردن قیمت یک ماه برای محاسبه تخفیف
                  $price_1_month = 0;
                  foreach ($plan_durations as $pd) {
                      if ($pd['duration_days'] == 30) {
                          $price_1_month = $pd['price'];
                          break;
                      }
                  }
                  if ($price_1_month <= 0 && !empty($plan['price_1_month'])) {
                      $price_1_month = $plan['price_1_month'];
                  }

                  foreach ($plan_durations as $dur):
                    $duration_days = $dur['duration_days'];
                    $is_active_dur = ($user_sub && $user_sub['plan_id'] == $plan['id'] && $user_sub['duration_days'] == $duration_days);
                    $is_pending_dur = ($pending_sub && $pending_sub['plan_id'] == $plan['id'] && $pending_sub['duration_days'] == $duration_days);

                    $discount = 0;
                    if ($duration_days != 30 && $price_1_month > 0) {
                      $saving = ($price_1_month / 30 * $duration_days) - $dur['price'];
                      if ($saving > 0) {
                        $discount = round(($saving / ($price_1_month / 30 * $duration_days)) * 100);
                      }
                    }

                    $row_class = '';
                    if ($is_active_dur) {
                        $row_class = 'border-success bg-label-success';
                    } elseif ($is_pending_dur) {
                        $row_class = 'border-warning bg-label-warning';
                    }
                  ?>
                  <div class="pricing-option-row p-3 mb-3 border rounded <?= $row_class ?>">
                    <div class="d-flex justify-content-between align-items-center">
                      <div class="flex-grow-1">
                        <h6 class="mb-0 fs-6 fw-bold"><?= $dur['label'] ?></h6>
                        <?php if ($discount > 0): ?>
                          <span class="badge bg-<?= $dur['color'] ?> p-1 mt-1" style="font-size: 12px;"><?= $discount ?>% تخفیف</span>
                        <?php endif; ?>
                      </div>
                      <div class="text-end me-3">
                        <div class="fw-bold text-primary fs-5"><?= number_format($dur['price']) ?> یورو</div>
                        <?php 
                        $euro_rate = defined('EURO_TO_TOMAN_RATE') ? EURO_TO_TOMAN_RATE : 75000;
                        $toman_price = $dur['price'] * $euro_rate;
                        ?>
                        <div style="font-size: 0.85rem; color: #a1acb8;">معادل <?= number_format($toman_price) ?> تومان</div>
                      </div>
                      
                      <?php if ($is_active_dur): ?>
                        <button type="button" class="btn btn-success fs-5 py-2 px-3" disabled>
                           <span>فعال است</span>
                        </button>
                      <?php elseif ($is_pending_dur): ?>
                        <div class="d-flex gap-1">
                          <button type="button" class="btn btn-warning fs-5 py-2 px-3" disabled>
                             <span>در حال بازبینی</span>
                          </button>
                          <form action="cancel-pending-subscription.php" method="POST" class="d-inline">
                            <button type="submit" class="btn btn-danger fs-5 py-2 px-3" onclick="event.preventDefault(); Swal.fire({title: 'توجه', text: 'آیا از لغو این درخواست اطمینان دارید؟', icon: 'warning', showCancelButton: true, confirmButtonText: 'بله', cancelButtonText: 'خیر'}).then((result) => { if(result.isConfirmed) { this.closest('form').submit(); } })">
                               <i class="bx bx-x"></i>
                            </button>
                          </form>
                        </div>
                      <?php elseif ($user_plan_status === 'active'): ?>
                        <button type="button" class="btn btn-secondary fs-5 py-2 px-3" disabled title="شما یک اشتراک فعال دارید">
                           <span>غیرقابل خرید</span>
                        </button>
                      <?php else: ?>
                        <button type="button" 
                           data-plan-id="<?= $plan['id'] ?>" 
                           data-duration="<?= $dur['key'] ?>"
                           data-plan-name="<?= htmlspecialchars($plan['name']) ?>"
                           data-duration-label="<?= htmlspecialchars($dur['label']) ?>"
                           data-price="<?= number_format($dur['price']) ?>"
                           data-toman-price="<?= number_format($toman_price) ?>"
                           class="btn btn-primary btn-buy-ajax fs-5 py-2 px-4">
                           <span>خرید</span>
                        </button>
                      <?php endif; ?>
                    </div>
                  </div>
                  <?php endforeach; ?>
                </div>
              <?php endif; ?>
            </div>
          </div>
        </div>
      <?php endforeach; ?>
    </div>
  </div>

  <!-- Subscription History -->
  <div class="row">
    <div class="col-12">
      <div class="card shadow-sm mb-4">
        <div class="card-header border-bottom">
          <h5 class="mb-0">تاریخچه اشتراک‌ها</h5>
        </div>
        <div class="card-body">
          <div class="table-responsive">
            <?php
            $subscription_history = get_user_subscription_history($_SESSION['user_id'], $pdo);
            if (empty($subscription_history)):
            ?>
              <div class="alert alert-info text-center" role="alert">هیچ تاریخچه اشتراکی یافت نشد.</div>
            <?php else: ?>
              <table class="table table-hover">
                <thead class="table-light">
                  <tr>
                    <th>نام پلن</th>
                    <th>مدت</th>
                    <th>مبلغ</th>
                    <th>تاریخ شروع</th>
                    <th>تاریخ انقضا</th>
                    <th>وضعیت</th>
                  </tr>
                </thead>
                <tbody>
                  <?php foreach ($subscription_history as $history): 
                    $status_badges = [
                      'active' => ['class' => 'success', 'text' => 'فعال'],
                      'expired' => ['class' => 'danger', 'text' => 'منقضی شده'],
                      'cancelled' => ['class' => 'secondary', 'text' => 'لغو شده'],
                      'pending' => ['class' => 'warning', 'text' => 'در انتظار تایید']
                    ];
                    $status = $status_badges[$history['status']] ?? ['class' => 'secondary', 'text' => 'نامشخص'];
                    
                    $duration_label = $history['duration_days'] > 0 ? $history['duration_days'] . ' روز' : 'نامحدود';
                    if ($history['duration_days'] == 14) $duration_label = '2 هفته';
                    if ($history['duration_days'] == 30) $duration_label = '1 ماه';
                    if ($history['duration_days'] == 90) $duration_label = '3 ماه';
                  ?>
                  <tr>
                    <td><span class="fw-semibold"><?= htmlspecialchars($history['plan_name']) ?></span></td>
                    <td><?= $duration_label ?></td>
                    <td><?= number_format($history['amount_paid']) ?> <small>یورو</small></td>
                    <td><small><?= !empty($history['started_at']) ? date('Y/m/d', strtotime($history['started_at'])) : '-' ?></small></td>
                    <td><small><?= !empty($history['expires_at']) ? date('Y/m/d', strtotime($history['expires_at'])) : '-' ?></small></td>
                    <td><span class="badge bg-<?= $status['class'] ?>"><?= $status['text'] ?></span></td>
                  </tr>
                  <?php endforeach; ?>
                </tbody>
              </table>
            <?php endif; ?>
          </div>
        </div>
      </div>
    </div>
  </div>

  <!-- Statistics -->
  <div class="row">
    <?php if (!empty($subscription_history)): ?>
      <div class="col-md-4 mb-4">
        <div class="card bg-label-primary">
          <div class="card-body text-center">
            <h5 class="card-title">کل خریدها</h5>
            <h3 class="mb-0"><?= count($subscription_history) ?></h3>
          </div>
        </div>
      </div>
      <div class="col-md-4 mb-4">
        <div class="card bg-label-success">
          <div class="card-body text-center">
            <h5 class="card-title">کل پرداختی</h5>
            <h3 class="mb-0">
              <?php
              $total_paid = 0;
              foreach ($subscription_history as $sub) { if (in_array($sub['status'], ['active', 'expired'])) $total_paid += $sub['amount_paid']; }
              echo number_format($total_paid);
              ?> <small>یورو</small>
            </h3>
          </div>
        </div>
      </div>
      <div class="col-md-4 mb-4">
        <div class="card bg-label-info">
          <div class="card-body text-center">
            <h5 class="card-title">اشتراک‌های فعال</h5>
            <h3 class="mb-0">
              <?= count(array_filter($subscription_history, function($sub) { return $sub['status'] === 'active'; })) ?>
            </h3>
          </div>
        </div>
      </div>
    <?php endif; ?>
  </div>

</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
  const userEmail = '<?= $_SESSION['email'] ?? 'نامشخص' ?>';
  const buyButtons = document.querySelectorAll('.btn-buy-ajax');
  buyButtons.forEach(button => {
    button.addEventListener('click', function() {
      const planId = this.getAttribute('data-plan-id');
      const duration = this.getAttribute('data-duration');
      const planName = this.getAttribute('data-plan-name');
      const durationLabel = this.getAttribute('data-duration-label');
      const planPrice = this.getAttribute('data-price');
      const tomanPrice = this.getAttribute('data-toman-price');
      
      Swal.fire({
        title: 'تایید درخواست اشتراک',
        html: `
          <div class="mb-3">
            آیا از ثبت درخواست اشتراک <b>${planName}</b> برای دوره <b>${durationLabel}</b> با مبلغ <b>${planPrice} یورو (معادل ${tomanPrice} تومان)</b> اطمینان دارید؟
          </div>
          <div class="mt-3">
            <label for="referral_code" class="form-label text-start d-block">کد معرف (اختیاری):</label>
            <input type="text" id="referral_code" class="form-control" placeholder="اگر کد معرف دارید وارد کنید">
            <small class="text-muted d-block mt-1">با وارد کردن کد معرف معتبر، ۷ روز هدیه به اشتراک شما اضافه خواهد شد.</small>
          </div>
        `,
        icon: 'question',
        showCancelButton: true,
        confirmButtonText: 'بله، ثبت درخواست',
        cancelButtonText: 'انصراف',
        customClass: { confirmButton: 'btn btn-primary me-3', cancelButton: 'btn btn-label-secondary' },
        buttonsStyling: false
      }).then(function (result) {
        if (result.value) {
          const referralCode = document.getElementById('referral_code').value;
          Swal.fire({ title: 'در حال ثبت درخواست...', allowOutsideClick: false, didOpen: () => { Swal.showLoading(); } });
          $.ajax({
            url: 'ajax-process-subscription.php',
            type: 'POST',
            data: { 
              plan_id: planId, 
              duration: duration,
              referral_code: referralCode
            },
            success: function(response) {
              if (response.success) {
                const waText = `سلام، من درخواست اشتراک ${planName} با مبلغ ${planPrice} یورو (معادل ${tomanPrice} تومان) را در سایت ثبت کردم.\nایمیل من: ${userEmail}\nلطفا فعال کنید.`;
                Swal.fire({ 
                  icon: 'success', 
                  title: 'موفقیت‌آمیز', 
                  html: `
                    <p class="mb-4">${response.message}</p>
                    <div class="d-grid gap-2 text-center">
                      <p class="small text-muted mb-2">جهت فعال‌سازی اشتراک، الزامی است به پشتیبانی اطلاع‌رسانی کنید:</p>
                      <a href="https://wa.me/989177876760?text=${encodeURIComponent(waText)}" target="_blank" class="btn btn-success btn-sm mb-1">
                        <i class="bx bxl-whatsapp me-2"></i> واتس‌اپ
                      </a>
                      <a href="https://t.me/farsifahr" target="_blank" class="btn btn-info btn-sm">
                        <i class="bx bxl-telegram me-2"></i> تلگرام
                      </a>
                    </div>
                  `, 
                  confirmButtonText: 'متوجه شدم', 
                  customClass: { confirmButton: 'btn btn-primary mt-3' } 
                })
                .then(() => { location.reload(); });
              } else {
                Swal.fire({ icon: 'error', title: 'خطا', text: response.message, confirmButtonText: 'باشه', customClass: { confirmButton: 'btn btn-danger' } });
              }
            },
            error: function() {
              Swal.fire({ icon: 'error', title: 'خطا', text: 'ایرادی در برقراری ارتباط با سرور پیش آمد.', confirmButtonText: 'باشه', customClass: { confirmButton: 'btn btn-danger' } });
            }
          });
        }
      });
    });
  });
});
</script>
<script src="assets/js/pages-pricing.js"></script>