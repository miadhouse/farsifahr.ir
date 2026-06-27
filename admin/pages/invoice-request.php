<?php

$error = 0;
$pending = false;
$plan = null;
$duration = null;
$duration_days = 0;
$duration_label = '';
$plan_price = 0;
$plan_features = [];

// بررسی پارامترهای ورودی
if (isset($_GET['plan-id']) && is_numeric($_GET['plan-id'])) {
  $plan_id = $_GET['plan-id'];
  $plan = get_subscription_plan($plan_id, $pdo);
  
  if (is_null($plan) || $plan == false) {
    $error = 1;
  } else {
    // بررسی duration
    if (isset($_GET['duration']) && validate_duration($_GET['duration'])) {
      $duration = $_GET['duration'];
      $duration_days = get_duration_days($duration);
      $duration_label = get_duration_label($duration);
      $plan_price = get_plan_price_by_duration($plan, $duration);
      
      // اگر قیمت 0 باشد، یعنی این duration برای این پلن فعال نیست
      if ($plan_price <= 0 && $plan['slug'] !== 'free') {
        $error = 2; // duration نامعتبر برای این پلن
      }
    } else {
      // اگر duration نداریم، خطا می‌دهیم (مگر اینکه پلن رایگان باشد)
      if ($plan['slug'] !== 'free') {
        $error = 3; // duration مشخص نشده
      }
    }
    
    // بررسی اشتراک معلق
    $pending_invoice = get_user_pending_subscription($_SESSION['user_id'], $pdo);
    if ($pending_invoice != null && $pending_invoice != false && $pending_invoice['status'] == 'pending') {
      $pending = true;
      $plan_features = get_plan_features($pending_invoice['plan_slug']);
    } else {
      $pending = false;
      $plan_features = get_plan_features($plan['slug']);
    }
  }
} else {
  $error = 1;
}

// محاسبه تخفیف
$discount_percentage = 0;
if ($error == 0 && !$pending && $plan && $duration && $plan['slug'] !== 'free') {
  $discount_percentage = calculate_discount_percentage($plan, $duration);
}
?>

<div class="row invoice-preview">
  <!-- Invoice -->
  <div class="col-xl-9 col-md-8 col-12 mb-md-0 mb-4">
    <?php if ($error == 0): ?>
      
      <!-- اشتراک معلق -->
      <?php if ($pending == true): ?>
        <div class="alert alert-warning mb-4">
          <h5 class="alert-heading">
            <i class="bx bx-error-circle me-2"></i>
            اشتراک معلق
          </h5>
          <p class="mb-0">
            کاربر گرامی، شما قبلا یک درخواست اشتراک در حالت معلق دارید. ابتدا درخواست قبلی را
            توسط پشتیبانی پیگیری کنید یا اینکه توسط گزینه "لغو درخواست" در همین صفحه، درخواست قبلی را لغو کرده و
            سپس دوباره برای اشتراک جدید اقدام کنید.
          </p>
        </div>
        
        <div class="card invoice-preview-card">
          <div class="card-body">
            <div class="d-flex justify-content-between flex-xl-row flex-md-column flex-sm-row flex-column p-sm-3 p-0">
              <div class="mb-xl-0 mb-4 flex-grow-1">
                <div class="d-flex align-items-center mb-3">
                  <h3 class="mb-0 fw-bold">اشتراک <?= htmlspecialchars($pending_invoice['plan_name']) ?></h3>
                  <span class="badge bg-warning ms-2">در انتظار تایید</span>
                </div>
                
                <div class="alert alert-primary mb-3">
                  <h4 class="mb-0"><?= number_format($pending_invoice['amount_paid']) ?> تومان</h4>
                </div>
                
                <p class="mb-3"><?= htmlspecialchars($pending_invoice['plan_description']) ?></p>
                
                <div class="alert alert-info">
                  <span class="fw-semibold">یادداشت:</span>
                  <p class="mb-0 mt-2">
                    درخواست شما در حال بررسی توسط تیم پشتیبانی است. پس از تایید، اشتراک شما فعال خواهد شد.
                    در صورت نیاز به پیگیری با پشتیبانی تماس بگیرید.
                  </p>
                </div>
              </div>

              <div class="ms-xl-4">
                <div class="table-responsive">
                  <table class="table border m-0">
                    <thead class="table-light">
                      <tr>
                        <th colspan="2">
                          <h5 class="mb-0">ویژگی‌های اشتراک</h5>
                        </th>
                      </tr>
                    </thead>
                    <tbody>
                      <?php foreach ($plan_features as $feature): ?>
                        <tr>
                          <td>
                            <i class="bx bx-check text-success me-2"></i>
                            <?= htmlspecialchars($feature) ?>
                          </td>
                        </tr>
                      <?php endforeach; ?>
                    </tbody>
                  </table>
                </div>
              </div>
            </div>
          </div>
        </div>
      
      <!-- اشتراک جدید -->
      <?php else: ?>
        <div class="card invoice-preview-card">
          <div class="card-body">
            <div class="d-flex justify-content-between flex-xl-row flex-md-column flex-sm-row flex-column p-sm-3 p-0">
              <div class="mb-xl-0 mb-4 flex-grow-1">
                <div class="mb-3">
                  <h3 class="mb-2 fw-bold">اشتراک <?= htmlspecialchars($plan['name']) ?></h3>
                  <?php if ($duration_label): ?>
                    <span class="badge bg-primary fs-6"><?= htmlspecialchars($duration_label) ?></span>
                  <?php endif; ?>
                </div>
                
                <p class="mb-3"><?= htmlspecialchars($plan['description']) ?></p>
                
                <!-- نمایش قیمت -->
                <?php if ($plan['slug'] === 'free'): ?>
                  <div class="alert alert-success mb-3">
                    <h4 class="mb-0">رایگان</h4>
                    <small>بدون محدودیت زمانی</small>
                  </div>
                <?php else: ?>
                  <div class="mb-3">
                    <div class="d-flex align-items-center gap-2">
                      <?php if ($discount_percentage > 0): ?>
                        <span class="badge bg-success fs-6">
                          <?= $discount_percentage ?>% تخفیف
                        </span>
                      <?php endif; ?>
                    </div>
                    <div class="alert alert-primary mt-2">
                      <h3 class="mb-0"><?= number_format($plan_price) ?> تومان</h3>
                      <small>برای <?= htmlspecialchars($duration_label) ?> (<?= $duration_days ?> روز)</small>
                    </div>
                    
                    <?php if ($discount_percentage > 0): ?>
                      <div class="text-muted small">
                        قیمت روزانه: <?= number_format($plan_price / $duration_days) ?> تومان
                      </div>
                    <?php endif; ?>
                  </div>
                <?php endif; ?>
                
                <div class="alert alert-info">
                  <span class="fw-semibold">یادداشت:</span>
                  <p class="mb-0 mt-2">
                    پس از ثبت درخواست، اطلاعات پرداخت برای شما ارسال می‌شود.
                    پس از واریز و تایید پرداخت، اشتراک شما فعال خواهد شد.
                  </p>
                </div>
                
                <!-- گزینه‌های مدت زمان (اگر VIP باشد) -->
                <?php if ($plan['slug'] === 'vip'): ?>
                  <div class="mt-4">
                    <h6 class="mb-3">تغییر دوره اشتراک:</h6>
                    <div class="btn-group w-100" role="group">
                      <?php
                      $durations = [
                        ['key' => '2_weeks', 'label' => '2 هفته'],
                        ['key' => '1_month', 'label' => '1 ماه'],
                        ['key' => '3_months', 'label' => '3 ماه'],
                        ['key' => '6_months', 'label' => '6 ماه'],
                        ['key' => '1_year', 'label' => '1 سال']
                      ];
                      
                      foreach ($durations as $dur):
                        $dur_price = get_plan_price_by_duration($plan, $dur['key']);
                        if ($dur_price > 0):
                          $is_active = ($dur['key'] === $duration) ? 'active' : '';
                      ?>
                        <a href="?plan-id=<?= $plan_id ?>&duration=<?= $dur['key'] ?>" 
                           class="btn btn-outline-primary <?= $is_active ?>">
                          <?= $dur['label'] ?>
                          <br>
                          <small><?= number_format($dur_price) ?></small>
                        </a>
                      <?php 
                        endif;
                      endforeach; 
                      ?>
                    </div>
                  </div>
                <?php endif; ?>
              </div>

              <div class="ms-xl-4">
                <div class="table-responsive">
                  <table class="table border m-0">
                    <thead class="table-light">
                      <tr>
                        <th>
                          <h5 class="mb-0">ویژگی‌های اشتراک</h5>
                        </th>
                      </tr>
                    </thead>
                    <tbody>
                      <?php foreach ($plan_features as $feature): ?>
                        <tr>
                          <td>
                            <i class="bx bx-check text-success me-2"></i>
                            <?= htmlspecialchars($feature) ?>
                          </td>
                        </tr>
                      <?php endforeach; ?>
                    </tbody>
                  </table>
                </div>
              </div>
            </div>
          </div>
        </div>
      <?php endif; ?>

    <!-- خطاها -->
    <?php elseif ($error == 1): ?>
      <div class="card">
        <div class="card-body text-center py-5">
          <i class="bx bx-error-circle display-1 text-danger"></i>
          <h4 class="mt-3">پلن انتخاب نشده</h4>
          <p class="text-muted">لطفا از صفحه اشتراک‌ها یک پلن انتخاب کنید.</p>
          <a href="subscription.php" class="btn btn-primary mt-3">
            <i class="bx bx-arrow-back me-2"></i>
            بازگشت به اشتراک‌ها
          </a>
        </div>
      </div>
    
    <?php elseif ($error == 2): ?>
      <div class="card">
        <div class="card-body text-center py-5">
          <i class="bx bx-error-circle display-1 text-warning"></i>
          <h4 class="mt-3">دوره زمانی نامعتبر</h4>
          <p class="text-muted">این دوره زمانی برای پلن انتخابی فعال نیست.</p>
          <a href="subscription.php" class="btn btn-primary mt-3">
            <i class="bx bx-arrow-back me-2"></i>
            بازگشت به اشتراک‌ها
          </a>
        </div>
      </div>
    
    <?php elseif ($error == 3): ?>
      <div class="card">
        <div class="card-body text-center py-5">
          <i class="bx bx-error-circle display-1 text-warning"></i>
          <h4 class="mt-3">دوره زمانی مشخص نشده</h4>
          <p class="text-muted">لطفا دوره زمانی مورد نظر خود را انتخاب کنید.</p>
          <a href="subscription.php" class="btn btn-primary mt-3">
            <i class="bx bx-arrow-back me-2"></i>
            بازگشت به اشتراک‌ها
          </a>
        </div>
      </div>
    <?php endif; ?>

  </div>
  <!-- /Invoice -->

  <!-- Invoice Actions -->
  <div class="col-xl-3 col-md-4 col-12 invoice-actions">
    <?php if ($error == 0): ?>
      <div class="card">
        <div class="card-body">
          <h6 class="mb-3">عملیات</h6>
          
          <?php if ($pending == true): ?>
            <!-- دکمه‌های اشتراک معلق -->
            <button class="btn btn-outline-primary d-grid w-100 mb-3" disabled>
              <span class="d-flex align-items-center justify-content-center text-nowrap">
                <i class="bx bx-hourglass bx-xs me-2"></i>
                در حال بررسی...
              </span>
            </button>
            
            <button class="btn btn-danger d-grid w-100" data-bs-toggle="modal" data-bs-target="#cancelPendingModal">
              <span class="d-flex align-items-center justify-content-center text-nowrap">
                <i class="bx bx-x bx-xs me-2"></i>
                لغو درخواست
              </span>
            </button>
            
          <?php else: ?>
            <!-- دکمه‌های اشتراک جدید -->
            <?php if ($plan['slug'] === 'free'): ?>
              <button class="btn btn-outline-secondary d-grid w-100 mb-3" disabled>
                <span class="d-flex align-items-center justify-content-center text-nowrap">
                  پلن فعلی شما
                </span>
              </button>
            <?php else: ?>
              <form action="process-subscription.php" method="POST" id="subscriptionForm">
                <input type="hidden" name="plan_id" value="<?= $plan_id ?>">
                <input type="hidden" name="duration" value="<?= htmlspecialchars($duration) ?>">
                <input type="hidden" name="amount" value="<?= $plan_price ?>">
                
                <button type="submit" class="btn btn-primary d-grid w-100 mb-3">
                  <span class="d-flex align-items-center justify-content-center text-nowrap">
                    <i class="bx bx-save bx-xs me-2"></i>
                    ثبت درخواست
                  </span>
                </button>
              </form>
            <?php endif; ?>
            
            <a href="subscription.php" class="btn btn-secondary d-grid w-100">
              <span class="d-flex align-items-center justify-content-center text-nowrap">
                <i class="bx bx-arrow-back bx-xs me-2"></i>
                بازگشت
              </span>
            </a>
          <?php endif; ?>
        </div>
      </div>
      
      <!-- اطلاعات تکمیلی -->
      <div class="card mt-3">
        <div class="card-body">
          <h6 class="mb-3">اطلاعات پرداخت</h6>
          <div class="d-flex justify-content-between mb-2">
            <span>قیمت:</span>
            <span class="fw-semibold"><?= number_format($plan_price) ?> تومان</span>
          </div>
          <?php if ($plan['slug'] !== 'free'): ?>
            <div class="d-flex justify-content-between mb-2">
              <span>مدت:</span>
              <span class="fw-semibold"><?= $duration_days ?> روز</span>
            </div>
            <?php if ($discount_percentage > 0): ?>
              <div class="d-flex justify-content-between mb-2 text-success">
                <span>تخفیف:</span>
                <span class="fw-semibold"><?= $discount_percentage ?>%</span>
              </div>
            <?php endif; ?>
          <?php endif; ?>
          <hr>
          <div class="d-flex justify-content-between">
            <span class="fw-semibold">جمع کل:</span>
            <span class="fw-bold text-primary"><?= number_format($plan_price) ?> تومان</span>
          </div>
        </div>
      </div>
    <?php endif; ?>
  </div>
  <!-- /Invoice Actions -->
</div>

<!-- Modal لغو درخواست معلق -->
<div class="modal fade" id="cancelPendingModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">لغو درخواست</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <p>آیا مطمئن هستید که می‌خواهید درخواست اشتراک معلق خود را لغو کنید؟</p>
        <p class="text-muted small">این عمل قابل بازگشت نیست.</p>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">انصراف</button>
        <form action="/admin/cancel-pending-subscription.php" method="POST" class="d-inline">
          <button type="submit" class="btn btn-danger">بله، لغو کن</button>
        </form>
      </div>
    </div>
  </div>
</div>