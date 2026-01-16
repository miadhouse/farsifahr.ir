<?php

if (isset($_GET['plan-id']) && is_numeric($_GET['plan-id'])) {
  $pending = false;
  $plan_id = $_GET['plan-id'];
  $plan = get_subscription_plan($plan_id, $pdo);
  if (is_null($plan) || $plan == false) {
    $error = 1;
  } else {
    $error = 0;
    $pending_invoice = get_user_pending_subscription($_SESSION['user_id'], $pdo);
    if ($pending_invoice != null || $pending_invoice != false && $pending_invoice['status'] == 'pending') {
      $pending = true;
      $plan_features = get_plan_features($pending_invoice['plan_id'], $pdo);
    } else {
      $pending = false;
      $plan_features = get_plan_features($plan_id, $pdo);

    }
  }
} else {
  $error = 1;
}
?>
<div class="row invoice-preview">
  <!-- Invoice -->
  <div class="col-xl-9 col-md-8 col-12 mb-md-0 mb-4">
    <?php if ($error == 0): ?>
      <?php if ($pending == true): ?>
        <div class="row">
          <h5 class="alert alert-warning">کاربر گرامی، شما قبلا یک درخواست اشتراک در حالت معلق دارید، ابتدا درخواست قبلی را
            توسط پشتیبانی پیگیری کنید یا اینکه توسط گزینه لغو درخواست در همین صفحه یا در سبد خرید درخواست قبلی را لغو کرده و
            سپس دوباره برای اشتراک جدید اقدام کنید.</h4>
        </div>
        <div class="card invoice-preview-card">
          <div class="card-body">
            <div class="d-flex justify-content-between flex-xl-row flex-md-column flex-sm-row flex-column p-sm-3 p-0">
              <div class="mb-xl-0 mb-4">
                <div class="d-flex align-items-center svg-illustration mb-3 gap-2">
                  <h3 class="app-brand-text h3 mb-0 fw-bold">اشتراک <?= $pending_invoice['plan_name'] ?></span>
                    <h4 class="alert alert-primary p-1"> <?= $pending_invoice['amount_paid'] ?> تومان </span>
                </div>
                <p class="mb-1"><?= $pending_invoice['plan_description'] ?></p>
                <div class="card-body">
                  <div class="row">
                    <div class="col-12 lh-1-85">
                      <span class="fw-semibold">یادداشت:</span>
                      <span>لورم ایپسوم متن ساختگی با تولید سادگی نامفهوم از صنعت چاپ و با استفاده از طراحان گرافیک است.
                        چاپگرها و
                        متون بلکه روزنامه و مجله</span>
                    </div>
                  </div>
                </div>
              </div>

              <div>
                <div class="table-responsive">
                  <table class="table border-top m-0">
                    <h3 class="mx-4">ویژگی ها</h3>
                    <tbody>
                      <?php foreach ($plan_features as $feature): ?>
                        <tr>
                          <td class="text-nowrap"><?= $feature['name'] ?></td>
                        </tr>
                      <?php endforeach; ?>
                    </tbody>
                  </table>
                </div>
              </div>
            </div>
          </div>
        </div>
      <?php else: ?>
        <div class="card invoice-preview-card">
          <div class="card-body">
            <div class="d-flex justify-content-between flex-xl-row flex-md-column flex-sm-row flex-column p-sm-3 p-0">
              <div class="mb-xl-0 mb-4">
                <div class="d-flex align-items-center svg-illustration mb-3 gap-2">
                  <h3 class="app-brand-text h3 mb-0 fw-bold">اشتراک <?= $plan['name'] ?></span>
                    <h5 class="alert alert-primary p-1"> <?= $plan['monthly_price'] ?> تومان </span>
                </div>
                <p class="mb-1"><?= $plan['description'] ?></p>
                <div class="card-body">
                  <div class="row ">
                    <div class="col-12 lh-1-85">
                      <span class="fw-semibold">یادداشت:</span>
                      <span>لورم ایپسوم متن ساختگی با تولید سادگی نامفهوم از صنعت چاپ و با استفاده از طراحان گرافیک است.
                        چاپگرها و
                        متون بلکه روزنامه و مجله</span>
                    </div>
                  </div>
                  <div class="row mt-5">
                    <div class="btn-group" role="group" aria-label="Basic radio toggle button group">
                      <input type="radio" class="btn-check" name="btnradio" id="btnradio1" checked="">
                      <label class="btn btn-outline-primary" for="btnradio1">1 ماهه</label>

                      <input type="radio" class="btn-check" name="btnradio" id="btnradio2">
                      <label class="btn btn-outline-primary" for="btnradio2">3 ماهه</label>
                      <input type="radio" class="btn-check" name="btnradio" id="btnradio3">
                      <label class="btn btn-outline-primary" for="btnradio3">6 ماهه</label>
                      <input type="radio" class="btn-check" name="btnradio" id="btnradio4">
                      <label class="btn btn-outline-primary" for="btnradio4">9 ماهه</label>
                    </div>
                  </div>
                </div>
              </div>

              <div>
                <div class="table-responsive">
                  <table class="table border-top m-0">
                    <h3 class="mx-4">ویژگی ها</h3>
                    <tbody>
                      <?php foreach ($plan_features as $feature): ?>
                        <tr>
                          <td class="text-nowrap"><?= $feature['name'] ?></td>
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

    </div>
    <!-- /Invoice -->

    <!-- Invoice Actions -->
    <div class="col-xl-3 col-md-4 col-12 invoice-actions">
      <div class="card">
        <div class="card-body">
          <?php if ($pending == true): ?>
            <button class="btn disabled btn-primary d-grid w-100 mb-3" data-bs-toggle="offcanvas"
              data-bs-target="#sendInvoiceOffcanvas">
              <span class="d-flex align-items-center justify-content-center text-nowrap">در حال بازبینی...</span>
            </button>
          <?php else: ?>
            <button class="btn btn-primary d-grid w-100 mb-3" data-bs-toggle="offcanvas"
              data-bs-target="#sendInvoiceOffcanvas">
              <span class="d-flex align-items-center justify-content-center text-nowrap"><i
                  class="bx bx-save  bx-xs me-2"></i>ثبت اشتراک</span>
            </button>
          <?php endif; ?>
          <button class="btn btn-secondary d-grid w-100" data-bs-toggle="offcanvas" data-bs-target="#addPaymentOffcanvas">
            <span class="d-flex align-items-center justify-content-center text-nowrap"><i
                class="bx bx-x bx-xs me-2"></i>لغو درخواست</span>
          </button>
        </div>
      </div>
    <?php else: ?>'هیچ پلنی انتخاب نشده'<?php endif; ?>
  </div>
</div>