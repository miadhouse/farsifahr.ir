<?php
// my-requests-content.php
require_once(__DIR__ . '/../../incloud/functions.php');
require_once(__DIR__ . '/../../incloud/subscription-functions.php');

$user_id = $_SESSION['user_id'];

// ترجمه گواهینامه
$stmt = $pdo->prepare("SELECT * FROM license_translation_requests WHERE user_id = ? ORDER BY created_at DESC");
$stmt->execute([$user_id]);
$translations = $stmt->fetchAll();

// تست چشم
$stmt = $pdo->prepare("SELECT * FROM eye_test_appointment_requests WHERE user_id = ? ORDER BY created_at DESC");
$stmt->execute([$user_id]);
$eyetests = $stmt->fetchAll();

// کمک‌های اولیه
$stmt = $pdo->prepare("SELECT * FROM first_aid_course_appointment_requests WHERE user_id = ? ORDER BY created_at DESC");
$stmt->execute([$user_id]);
$firstaids = $stmt->fetchAll();

$whatsapp_base = (defined('WHATSAPP_URL') && WHATSAPP_URL !== '#') ? rtrim(str_replace('-', '', WHATSAPP_URL), '/') : 'https://wa.me/989177876760';
$telegram_support = (defined('TELEGRAM_SUPPORT_URL') && TELEGRAM_SUPPORT_URL !== '#') ? TELEGRAM_SUPPORT_URL : 'https://t.me/farsifahr';
?>

<div class="col-12">
  <h4 class="py-3 breadcrumb-wrapper mb-4">
    <span class="text-muted fw-light">داشبورد /</span> درخواست‌های من
  </h4>

  <?php if (isset($_SESSION['success_message'])): ?>
    <div class="alert alert-success alert-dismissible fade show shadow-sm mb-4" role="alert">
      <div class="d-flex align-items-center">
        <i class="bx bx-check-circle me-2 fs-4"></i>
        <span><?= htmlspecialchars($_SESSION['success_message']) ?></span>
      </div>
      <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
    <?php unset($_SESSION['success_message']); ?>
  <?php endif; ?>

  <?php if (isset($_SESSION['info_message'])): ?>
    <div class="alert alert-info alert-dismissible fade show shadow-sm mb-4" role="alert">
      <div class="d-flex align-items-center">
        <i class="bx bx-info-circle me-2 fs-4"></i>
        <span><?= htmlspecialchars($_SESSION['info_message']) ?></span>
      </div>
      <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
    <?php unset($_SESSION['info_message']); ?>
  <?php endif; ?>

  <!-- 1. License Translation Requests -->
  <div class="card mb-5 shadow-sm border-0">
    <div class="card-header bg-label-primary d-flex justify-content-between align-items-center py-3">
      <h5 class="mb-0 fw-bold"><i class="bx bx-file me-2 fs-4"></i> درخواست‌های ترجمه گواهینامه</h5>
      <a href="../service-translation.php" target="_blank" class="btn btn-primary btn-sm"><i class="bx bx-plus me-1"></i> ثبت درخواست جدید</a>
    </div>
    <div class="card-body pt-3">
      <?php if (count($translations) > 0): ?>
        <div class="table-responsive text-nowrap">
          <table class="table table-hover table-striped">
            <thead>
              <tr>
                <th>شناسه</th>
                <th>نام و نام خانوادگی</th>
                <th>تلفن</th>
                <th>مبلغ</th>
                <th>وضعیت</th>
                <th>تاریخ ثبت</th>
                <th>عملیات</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($translations as $req): ?>
                <tr>
                  <td><strong>#<?= $req['id'] ?></strong></td>
                  <td><?= htmlspecialchars($req['first_name'] . ' ' . $req['last_name']) ?></td>
                  <td><?= htmlspecialchars($req['phone']) ?></td>
                  <td><?= number_format($req['price']) ?> یورو</td>
                  <td>
                    <?php
                    $status_colors = [
                      'pending_payment' => 'bg-label-warning',
                      'pending_review' => 'bg-label-info',
                      'processing' => 'bg-label-primary',
                      'shipped' => 'bg-label-secondary',
                      'completed' => 'bg-label-success',
                      'cancelled' => 'bg-label-danger',
                    ];
                    $status_texts = [
                      'pending_payment' => 'در انتظار پرداخت',
                      'pending_review' => 'در انتظار بررسی',
                      'processing' => 'در حال ترجمه',
                      'shipped' => 'ارسال شده',
                      'completed' => 'تکمیل شده',
                      'cancelled' => 'لغو شده',
                    ];
                    $color = $status_colors[$req['status']] ?? 'bg-label-secondary';
                    $text = $status_texts[$req['status']] ?? $req['status'];
                    ?>
                    <span class="badge <?= $color ?>"><?= $text ?></span>
                  </td>
                  <td><?= date('Y/m/d H:i', strtotime($req['created_at'])) ?></td>
                  <td>
                    <?php if ($req['status'] === 'pending_payment'): ?>
                      <?php 
                      $wa_msg = "سلام، من درخواست ترجمه گواهینامه با نام " . $req['first_name'] . " " . $req['last_name'] . " و شناسه درخواست #" . $req['id'] . " را ثبت کردم. لطفا راهنمایی کنید.";
                      ?>
                      <a href="<?= $whatsapp_base ?>?text=<?= urlencode($wa_msg) ?>" target="_blank" class="btn btn-success btn-xs me-1">
                        <i class="bx bxl-whatsapp me-1"></i> پرداخت
                      </a>
                    <?php endif; ?>
                    <button class="btn btn-outline-secondary btn-xs" onclick="showTranslationDetails(<?= htmlspecialchars(json_encode($req)) ?>)">
                      <i class="bx bx-show me-1"></i> جزئیات
                    </button>
                  </td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      <?php else: ?>
        <p class="text-muted text-center py-4 mb-0">هیچ درخواستی برای ترجمه گواهینامه یافت نشد.</p>
      <?php endif; ?>
    </div>
  </div>

  <!-- 2. Eye Test Appointment Requests -->
  <div class="card mb-5 shadow-sm border-0">
    <div class="card-header bg-label-info d-flex justify-content-between align-items-center py-3">
      <h5 class="mb-0 fw-bold"><i class="bx bx-show me-2 fs-4"></i> نوبت‌های تست چشم</h5>
      <a href="../service-eyetest.php" target="_blank" class="btn btn-info btn-sm text-white" style="background-color: #03c3ec; border: none;"><i class="bx bx-plus me-1"></i> درخواست نوبت جدید</a>
    </div>
    <div class="card-body pt-3">
      <?php if (count($eyetests) > 0): ?>
        <div class="table-responsive text-nowrap">
          <table class="table table-hover table-striped">
            <thead>
              <tr>
                <th>شناسه</th>
                <th>نام و نام خانوادگی</th>
                <th>تلفن</th>
                <th>وضعیت</th>
                <th>تاریخ ثبت</th>
                <th>جزئیات</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($eyetests as $req): ?>
                <tr>
                  <td><strong>#<?= $req['id'] ?></strong></td>
                  <td><?= htmlspecialchars($req['first_name'] . ' ' . $req['last_name']) ?></td>
                  <td><?= htmlspecialchars($req['phone']) ?></td>
                  <td>
                    <?php
                    $status_colors = [
                      'pending' => 'bg-label-warning',
                      'approved' => 'bg-label-primary',
                      'completed' => 'bg-label-success',
                      'cancelled' => 'bg-label-danger',
                    ];
                    $status_texts = [
                      'pending' => 'در انتظار بررسی',
                      'approved' => 'تایید و رزرو شده',
                      'completed' => 'تکمیل شده',
                      'cancelled' => 'لغو شده',
                    ];
                    $color = $status_colors[$req['status']] ?? 'bg-label-secondary';
                    $text = $status_texts[$req['status']] ?? $req['status'];
                    ?>
                    <span class="badge <?= $color ?>"><?= $text ?></span>
                  </td>
                  <td><?= date('Y/m/d H:i', strtotime($req['created_at'])) ?></td>
                  <td>
                    <button class="btn btn-outline-secondary btn-xs" onclick="showVipDetails('تست چشم', <?= htmlspecialchars(json_encode($req)) ?>)">
                      <i class="bx bx-show me-1"></i> مشاهده
                    </button>
                  </td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      <?php else: ?>
        <p class="text-muted text-center py-4 mb-0">هیچ درخواستی برای نوبت تست چشم یافت نشد.</p>
      <?php endif; ?>
    </div>
  </div>

  <!-- 3. First Aid Course Appointment Requests -->
  <div class="card shadow-sm border-0">
    <div class="card-header bg-label-success d-flex justify-content-between align-items-center py-3">
      <h5 class="mb-0 fw-bold"><i class="bx bx-plus-medical me-2 fs-4"></i> نوبت‌های کورس کمک‌های اولیه</h5>
      <a href="../service-firstaid.php" target="_blank" class="btn btn-success btn-sm"><i class="bx bx-plus me-1"></i> درخواست نوبت جدید</a>
    </div>
    <div class="card-body pt-3">
      <?php if (count($firstaids) > 0): ?>
        <div class="table-responsive text-nowrap">
          <table class="table table-hover table-striped">
            <thead>
              <tr>
                <th>شناسه</th>
                <th>نام و نام خانوادگی</th>
                <th>تلفن</th>
                <th>وضعیت</th>
                <th>تاریخ ثبت</th>
                <th>جزئیات</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($firstaids as $req): ?>
                <tr>
                  <td><strong>#<?= $req['id'] ?></strong></td>
                  <td><?= htmlspecialchars($req['first_name'] . ' ' . $req['last_name']) ?></td>
                  <td><?= htmlspecialchars($req['phone']) ?></td>
                  <td>
                    <?php
                    $status_colors = [
                      'pending' => 'bg-label-warning',
                      'approved' => 'bg-label-primary',
                      'completed' => 'bg-label-success',
                      'cancelled' => 'bg-label-danger',
                    ];
                    $status_texts = [
                      'pending' => 'در انتظار بررسی',
                      'approved' => 'تایید و رزرو شده',
                      'completed' => 'تکمیل شده',
                      'cancelled' => 'لغو شده',
                    ];
                    $color = $status_colors[$req['status']] ?? 'bg-label-secondary';
                    $text = $status_texts[$req['status']] ?? $req['status'];
                    ?>
                    <span class="badge <?= $color ?>"><?= $text ?></span>
                  </td>
                  <td><?= date('Y/m/d H:i', strtotime($req['created_at'])) ?></td>
                  <td>
                    <button class="btn btn-outline-secondary btn-xs" onclick="showVipDetails('کمک‌های اولیه', <?= htmlspecialchars(json_encode($req)) ?>)">
                      <i class="bx bx-show me-1"></i> مشاهده
                    </button>
                  </td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      <?php else: ?>
        <p class="text-muted text-center py-4 mb-0">هیچ درخواستی برای نوبت دوره کمک‌های اولیه یافت نشد.</p>
      <?php endif; ?>
    </div>
  </div>
</div>

<!-- Modal for details -->
<div class="modal fade" id="detailsModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered" role="document">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title fw-bold" id="detailsModalTitle">جزئیات درخواست</h5>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close" onclick="closeDetailsModal()"></button>
      </div>
      <div class="modal-body" id="detailsModalBody">
        <!-- Content will be injected via JS -->
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" onclick="closeDetailsModal()">بستن</button>
      </div>
    </div>
  </div>
</div>

<script>
  let modalInstance = null;

  function showTranslationDetails(req) {
    const title = 'جزئیات درخواست ترجمه گواهینامه #' + req.id;
    let body = `
      <table class="table table-bordered">
        <tr><th>نام متقاضی</th><td>\${escapeHtml(req.first_name)} \${escapeHtml(req.last_name)}</td></tr>
        <tr><th>تلفن</th><td>\${escapeHtml(req.phone)}</td></tr>
        <tr><th>ایمیل</th><td>\${escapeHtml(req.email)}</td></tr>
        <tr><th>کد پستی</th><td>\${escapeHtml(req.postal_code)}</td></tr>
        <tr><th>شهر</th><td>\${escapeHtml(req.city)}</td></tr>
        <tr><th>آدرس</th><td>\${escapeHtml(req.street)} \${escapeHtml(req.house_number)} \${escapeHtml(req.additional_address || '')}</td></tr>
        <tr><th>مبلغ</th><td>\${req.price} یورو</td></tr>
        <tr><th>روش تماس</th><td>\${req.payment_contact_method === 'whatsapp' ? 'واتس‌اپ' : 'تلگرام'}</td></tr>
        <tr><th>مدارک</th><td>
          <a href="../download-license.php?file=\${encodeURIComponent(req.front_image_path)}" target="_blank" class="btn btn-outline-primary btn-xs mb-1 d-block">دانلود تصویر روی گواهینامه</a>
          <a href="../download-license.php?file=\${encodeURIComponent(req.back_image_path)}" target="_blank" class="btn btn-outline-primary btn-xs d-block">دانلود تصویر پشت گواهینامه</a>
        </td></tr>
      </table>
    `;
    document.getElementById('detailsModalTitle').textContent = title;
    document.getElementById('detailsModalBody').innerHTML = body;
    
    const myModal = new bootstrap.Modal(document.getElementById('detailsModal'));
    modalInstance = myModal;
    myModal.show();
  }

  function showVipDetails(serviceName, req) {
    const title = 'جزئیات درخواست نوبت ' + serviceName + ' #' + req.id;
    let body = `
      <table class="table table-bordered">
        <tr><th>نام متقاضی</th><td>\${escapeHtml(req.first_name)} \${escapeHtml(req.last_name)}</td></tr>
        <tr><th>تلفن</th><td>\${escapeHtml(req.phone)}</td></tr>
        <tr><th>ایمیل</th><td>\${escapeHtml(req.email)}</td></tr>
        <tr><th>کد پستی</th><td>\${escapeHtml(req.postal_code)}</td></tr>
        <tr><th>شهر</th><td>\${escapeHtml(req.city)}</td></tr>
        <tr><th>آدرس</th><td>\${escapeHtml(req.street)} \${escapeHtml(req.house_number)} \${escapeHtml(req.additional_address || '')}</td></tr>
      </table>
    `;
    document.getElementById('detailsModalTitle').textContent = title;
    document.getElementById('detailsModalBody').innerHTML = body;
    
    const myModal = new bootstrap.Modal(document.getElementById('detailsModal'));
    modalInstance = myModal;
    myModal.show();
  }

  function closeDetailsModal() {
    if (modalInstance) {
      modalInstance.hide();
    }
  }

  function escapeHtml(text) {
    if (!text) return '';
    return text
      .replace(/&/g, "&amp;")
      .replace(/</g, "&lt;")
      .replace(/>/g, "&gt;")
      .replace(/"/g, "&quot;")
      .replace(/'/g, "&#039;");
  }
</script>
