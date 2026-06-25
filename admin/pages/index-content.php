<?php
// dashboard.php
require_once('../incloud/user-config-handler.php');

// بررسی ورود کاربر
if (!is_logged_in()) {
    header('Location: /register.php');
    exit;
}

// چک کردن تنظیمات کاربر
check_user_configuration();

$pending_sub = get_user_pending_subscription($_SESSION['user_id'], $pdo);

// دریافت تنظیمات فعلی
$configHandler = new UserConfigHandler($pdo);
$userConfig = $configHandler->getUserConfig($_SESSION['user_id']);
$isConfigured = $configHandler->isConfigured(user_id: $_SESSION['user_id']);
$referenceDate = $configHandler->getReferenceDate();

// اگر تنظیم شده، داده‌ها را نمایش بده
$examDateText = '';
$languageText = '';
if ($userConfig) {
    $examDateText = $userConfig['exam_date_type'] == 'before' ? 'قبل از' : 'بعد از';
    $languageText = $userConfig['language'] == 'DE' ? 'آلمانی' : 'انگلیسی';
}

// دریافت گزارش‌های اخیر کاربر
$stmtReports = $pdo->prepare("
    SELECT qr.*, q.number as q_number 
    FROM question_reports qr
    JOIN questions q ON qr.question_id = q.id
    WHERE qr.user_id = ? 
    ORDER BY qr.created_at DESC 
    LIMIT 5
");
$stmtReports->execute([$_SESSION['user_id']]);
$user_reports = $stmtReports->fetchAll();
?>

<div class="container-xxl flex-grow-1 container-p-y">

    <?php if ($pending_sub): ?>
    <div class="row mb-4">
      <div class="col-12">
        <div class="alert alert-info d-flex flex-column align-items-center text-center py-4 shadow-sm mb-0" role="alert" style="border: 2px dashed #03c3ec;">
          <h5 class="alert-heading fw-bold mb-3"><i class="bx bx-paper-plane me-2 fs-3"></i>ارتباط با پشتیبانی</h5>
          <p class="mb-4">جهت فعال‌سازی اشتراک، الزامی است از طریق دکمه‌های زیر به پشتیبانی اطلاع‌رسانی کنید:</p>
          <div class="d-flex gap-3 flex-wrap justify-content-center">
            <?php 
              $wa_msg = "سلام، من درخواست اشتراک " . $pending_sub['plan_name'] . " با مبلغ " . number_format($pending_sub['amount_paid']) . " تومان را در سایت farsifahr ثبت کردم.\nایمیل من: " . ($_SESSION['email'] ?? 'نامشخص') . "\nلطفا فعال کنید.";
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
    </div>
    <?php endif; ?>
    
    <!-- کارت تنظیمات -->
    <div class="row mb-3 settings-row-container">
        <div class="col-12">
            <div class="card shadow-none border">
                <div class="card-body py-1 py-sm-2 px-3 d-flex align-items-center justify-content-between flex-nowrap gap-2" style="overflow-x: auto;">
                    <!-- Settings info -->
                    <div class="d-flex align-items-center gap-3 settings-info-container flex-grow-1">
                        <!-- Exam Date Item -->
                        <div class="d-flex align-items-center text-nowrap">
                            <i class="bx bx-calendar text-primary me-1 fs-5"></i>
                            <span class="fw-semibold me-1">تاریخ امتحان:</span>
                            <span class="badge bg-label-primary text-uppercase">
                                <?php echo $isConfigured ? $examDateText : 'تنظیم نشده'; ?>
                                (<?php echo date('d.m.Y', strtotime($referenceDate)); ?>)
                            </span>
                        </div>
                        
                        <!-- Separator Line -->
                        <div class="border-end vertical-separator" style="height: 18px;"></div>

                        <!-- Language Item -->
                        <div class="d-flex align-items-center text-nowrap">
                            <i class="bx bx-globe text-primary me-1 fs-5"></i>
                            <span class="fw-semibold me-1">زبان مطالعه:</span>
                            <span class="badge bg-label-info text-uppercase">
                                <?php echo $isConfigured ? $languageText : 'تنظیم نشده'; ?>
                            </span>
                        </div>
                    </div>

                    <!-- Settings button -->
                    <div class="settings-action-btn text-nowrap">
                        <button type="button" onclick="openConfigModal()" class="btn btn-label-primary btn-sm d-flex align-items-center">
                            <i class="bx bx-cog me-1"></i>
                            <span>تنظیمات</span>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- بقیه محتوای داشبورد -->
    <style>
    /* Settings Card Custom Styles */
    .settings-row-container {
        margin-top: -24px !important;
    }
    .settings-info-container {
        font-size: 0.82rem;
        gap: 15px !important;
    }
    .settings-info-container .badge {
        font-size: 0.78rem;
        padding: 4px 8px;
    }
    .settings-info-container i {
        font-size: 1.2rem !important;
    }
    .settings-action-btn .btn {
        font-size: 0.78rem;
        padding: 4px 10px;
    }

    @media (max-width: 767.98px) {
        .settings-row-container {
            margin-top: -12px !important;
        }
        .base-btn {
            height: auto !important;
        }
        .base-btn .row {
            height: auto !important;
            margin-left: -6px !important;
            margin-right: -6px !important;
        }
        .base-btn .col-6 {
            padding-left: 6px !important;
            padding-right: 6px !important;
        }
        .nav-hover-zoom {
            height: 120px !important;
        }
        .nav-hover-zoom .card-img-overlay {
            padding: 10px 8px !important;
            flex-direction: column;
            justify-content: center;
            align-items: center !important;
            text-align: center;
        }
        .nav-hover-zoom .nav-icons {
            width: 42px !important;
            height: auto !important;
            margin-bottom: 6px;
            margin-right: 0 !important;
            margin-left: 0 !important;
            opacity: 0.85 !important;
            position: relative !important;
        }
        .nav-hover-zoom .items {
            font-size: 0.9rem !important;
            padding-left: 0 !important;
            padding-right: 0 !important;
            line-height: 1.3;
            display: block;
            width: 100%;
        }

        /* Readiness & Comparison Cards side-by-side on mobile */
        .exam-readiness-card, .weekly-comparison-card {
            padding-left: 6px !important;
            padding-right: 6px !important;
        }
        .exam-readiness-card .card-body, .weekly-comparison-card .card-body {
            padding: 12px 8px !important;
        }
        .exam-readiness-card .chart-title {
            font-size: 0.78rem !important;
            font-weight: 600;
        }
        .weekly-comparison-card .card-header {
            padding: 10px 8px !important;
        }
        .weekly-comparison-card .card-title {
            font-size: 0.78rem !important;
            font-weight: 600;
        }
        .weekly-comparison-card .card-body h6 {
            font-size: 0.75rem !important;
            margin-bottom: 4px !important;
        }
        .weekly-comparison-card .card-body .desc-text {
            font-size: 0.65rem !important;
            margin-bottom: 8px !important;
        }
        .weekly-comparison-card .card-body .label-text {
            font-size: 0.62rem !important;
        }
        .weekly-comparison-card .card-body .val-text {
            font-size: 0.62rem !important;
        }
        .weekly-comparison-card .card-body .avatar {
            width: 20px !important;
            height: 20px !important;
        }
        .weekly-comparison-card .card-body .avatar-initial {
            font-size: 10px !important;
        }
        .weekly-comparison-card .card-body ul li {
            margin-bottom: 8px !important;
        }

        /* Compact settings card style on mobile */
        .settings-info-container {
            font-size: 0.65rem !important;
            gap: 6px !important;
        }
        .settings-info-container .badge {
            padding: 3px 5px !important;
            font-size: 0.62rem !important;
        }
        .settings-info-container i {
            font-size: 1rem !important;
        }
        .settings-info-container .vertical-separator {
            height: 12px !important;
        }
        .settings-action-btn .btn {
            padding: 4px 6px !important;
            font-size: 0.65rem !important;
        }
    }
    </style>
    <div class="row">
        <!-- Activity -->
        <div class="col-md-8 col-lg-8 col-xl-8 col-xxl-8 mb-4 base-btn">
            <div class="row h-100">
                <div class="col-6 mb-3 col-md-6 ">
                    <div class="col-12  h-100 vh-25  w-100 position-relative text-white nav-hover-zoom nav-blue-soft">
                        <img class="nav-img" src="assets/img/backgrounds/about-4-1.png" alt="...">
                        <div class="card-img-overlay d-flex align-items-center h-100 px-5"><img class="nav-icons"
                                src="assets/img/illustrations/1.png" alt="icon">
                            <a class="text-white fs-3 fs-xl-2 fs-xxl-3 stretched-link items fw-bold  ps-4"
                                href="practice.php"><?= __('practice_questions') ?></a>
                        </div>
                    </div>
                </div>

                <div class="col-6 mb-3 col-md-6  ">
                    <div class="col-12  h-100 vh-25  w-100 position-relative text-white nav-hover-zoom nav-cyan-soft">
                        <img class="nav-img" src="assets/img/backgrounds/about-4-1.png" alt="...">
                        <div class="card-img-overlay d-flex align-items-center h-100 px-5"><img class="nav-icons"
                                src="assets/img/illustrations/2.png" alt="icon">
                            <a class="text-white fs-3 fs-xl-2 fs-xxl-3 stretched-link items fw-bold ps-4"
                                href="../exam/exam_simulator.php"><?= __('exam_simulator') ?></a>
                        </div>
                    </div>
                </div>
                <div class="col-6 mb-3 mb-md-0 col-md-6 ">
                    <div class="col-12  h-100 vh-25  w-100 position-relative text-white nav-hover-zoom nav-purple-soft">
                        <img class="nav-img" src="assets/img/backgrounds/about-4-1.png" alt="...">
                        <div class="card-img-overlay d-flex align-items-center h-100 px-5"><img class="nav-icons"
                                src="assets/img/illustrations/3.png" alt="icon">
                            <a class="text-white fs-3 fs-xl-2 fs-xxl-3 stretched-link items fw-bold ps-4"
                                href="vocabulary.php"><?= __('vocabulary_learning') ?></a>
                        </div>
                    </div>
                </div>
                <div class="col-6  col-md-6 ">
                    <div class="col-12  h-100 vh-25  w-100 position-relative text-white nav-hover-zoom nav-indigo-soft">
                        <img class="nav-img" src="assets/img/backgrounds/about-4-1.png" alt="...">
                        <div class="card-img-overlay d-flex align-items-center h-100 px-5"><img class="nav-icons"
                                src="assets/img/illustrations/4.png" alt="icon">
                            <a class="text-white fs-3 fs-xl-2 fs-xxl-3 stretched-link items fw-bold ps-4"
                                href="workshop.php">کارگاه آموزش</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <!--/ Activity -->

        <!-- Activity (Readiness Chart) -->
        <div class="col-6 col-md-4 mb-4 exam-readiness-card">
            <div class="card h-100">
                <div class="card-body text-center">
                    <div id="growthRadialChart"></div>
                    <h6 class="mb-0 chart-title">آمادگی شما جهت امتحان</h6>
                </div>
            </div>
        </div>
        <!--/ Activity -->

        <!-- Weekly Comparison Card -->
        <div class="col-6 col-md-4 mb-4 weekly-comparison-card">
            <div class="card h-100">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="card-title mb-0">مقایسه با هفته گذشته</h5>
                </div>
                <div class="card-body">
                    <h6 class="mt-1">هفته قبل</h6>
                    <p class="mb-3 desc-text text-muted">کارایی نسبت به هفته قبل</p>
                    <ul class="list-unstyled m-0 pt-0">
                        <li class="mb-3">
                            <div class="d-flex align-items-center mb-2">
                                <div class="avatar avatar-sm flex-shrink-0 me-2">
                                    <span class="avatar-initial rounded bg-label-primary"><i
                                            class="bx bx-trending-up"></i></span>
                                </div>
                                <div class="w-100">
                                    <p class="mb-0 text-muted text-nowrap label-text">مطالعه این هفته</p>
                                    <small class="fw-semibold text-nowrap val-text">0 سوال</small>
                                </div>
                            </div>
                            <div class="progress" style="height: 6px">
                                <div class="progress-bar bg-primary" style="width: 0%" role="progressbar"
                                    aria-valuenow="0" aria-valuemin="0" aria-valuemax="100"></div>
                            </div>
                        </li>
                        <li>
                            <div class="d-flex align-items-center mb-2">
                                <div class="avatar avatar-sm flex-shrink-0 me-2">
                                    <span class="avatar-initial rounded bg-label-success"><i
                                            class="bx bx-dollar"></i></span>
                                </div>
                                <div class="w-100">
                                    <p class="mb-0 text-muted text-nowrap label-text">مطالعه هفته گذشته</p>
                                    <small class="fw-semibold text-nowrap val-text">0 سوال</small>
                                </div>
                            </div>
                            <div class="progress" style="height: 6px">
                                <div class="progress-bar bg-success" style="width: 0%" role="progressbar"
                                    aria-valuenow="0" aria-valuemin="0" aria-valuemax="100"></div>
                            </div>
                        </li>
                    </ul>
                </div>
            </div>
        </div>

        <!-- Monthly Progress Chart -->
        <div class="col-12 col-md-8 mb-4">
            <div class="card h-100">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="card-title mb-0">گزارش ماهیانه پیشرفت</h5>
                </div>
                <div class="card-body">
                    <div id="orderSummaryChart"></div>
                </div>
            </div>
        </div>

        <!-- Growth Chart-->
        <div class="col-12 col-md-4 mb-4">
            <div class="card h-100">
                <div class="card-header">
                    <h5 class="card-title mb-0">گزارش کلی مطالعه شما</h5>
                </div>
                <div class="card-body">
                    <ul class="p-0 m-0">
                        <li class="d-flex align-items-center mb-4 pb-2">
                            <div class="avatar avatar-sm flex-shrink-0 me-3">
                                <span class="avatar-initial rounded-circle bg-label-primary"><i
                                        class="bx bx-cube"></i></span>
                            </div>
                            <div class="d-flex flex-column w-100">
                                <div class="d-flex justify-content-between mb-2">
                                    <span>کل سوالاتی که پاسخ دادید</span>
                                    <span class="text-muted">1169</span>
                                </div>
                                <div class="progress" style="height: 6px">
                                    <div class="progress-bar bg-info" style="width: 40%" role="progressbar"
                                        aria-valuenow="40" aria-valuemin="0" aria-valuemax="100"></div>
                                </div>
                            </div>
                        </li>
                        <li class="d-flex align-items-center mb-4 pb-2">
                            <div class="avatar avatar-sm flex-shrink-0 me-3">
                                <span class="avatar-initial rounded-circle bg-label-success"><i
                                        class="bx bx-dollar"></i></span>
                            </div>
                            <div class="d-flex flex-column w-100">
                                <div class="d-flex justify-content-between mb-2">
                                    <span>سوالاتی که آماده اید برای امتحان</span>
                                    <span class="text-muted">8,478</span>
                                </div>
                                <div class="progress" style="height: 6px">
                                    <div class="progress-bar bg-success" style="width: 80%" role="progressbar"
                                        aria-valuenow="80" aria-valuemin="0" aria-valuemax="100"></div>
                                </div>
                            </div>
                        </li>
                        <li class="d-flex align-items-center mb-4 pb-2">
                            <div class="avatar avatar-sm flex-shrink-0 me-3">
                                <span class="avatar-initial rounded-circle bg-label-warning"><i
                                        class="bx bx-error"></i></span>
                            </div>
                            <div class="d-flex flex-column w-100">
                                <div class="d-flex justify-content-between mb-2">
                                    <span>سوالاتی که پنجاه درصد آماده اید</span>
                                    <span class="text-muted">8,478</span>
                                </div>
                                <div class="progress" style="height: 6px">
                                    <div class="progress-bar bg-warning" style="width: 80%" role="progressbar"
                                        aria-valuenow="80" aria-valuemin="0" aria-valuemax="100"></div>
                                </div>
                            </div>
                        </li>
                        <li class="d-flex align-items-center mb-4 pb-2">
                            <div class="avatar avatar-sm flex-shrink-0 me-3">
                                <span class="avatar-initial rounded-circle bg-label-danger"><i
                                        class="bx bx-x-circle"></i></span>
                            </div>
                            <div class="d-flex flex-column w-100">
                                <div class="d-flex justify-content-between mb-2">
                                    <span>سوالاتی که اصلا آماده نیستید</span>
                                    <span class="text-muted">8,478</span>
                                </div>
                                <div class="progress" style="height: 6px">
                                    <div class="progress-bar bg-danger" style="width: 80%" role="progressbar"
                                        aria-valuenow="80" aria-valuemin="0" aria-valuemax="100"></div>
                                </div>
                            </div>
                        </li>
                    </ul>
                </div>
            </div>
        </div>
    </div>

    <!-- Question Reports Tracking -->
    <div class="row">
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
    </div>
</div>

<!-- SweetAlert2 JS -->
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<!-- تنظیم متغیرهای جاوااسکریپت -->
<script>
    const csrfToken = '<?php echo $_SESSION['csrf_token']; ?>';
    const showConfigModal = <?php echo isset($_SESSION['show_config_modal']) && $_SESSION['show_config_modal'] ? 'true' : 'false'; ?>;
    const referenceDate = '<?php echo date('d.m.Y', strtotime($referenceDate)); ?>';

    // Export to window for use in dashboard_charts_updated.js
    window.csrfToken = csrfToken;
    window.showConfigModal = showConfigModal;
    window.referenceDate = referenceDate;
</script>

<!-- اسکریپت تنظیمات کاربر -->
<script>
    /**
 * Dashboard Charts - با استایل‌های یکپارچه و بهبود یافته
 */
'use strict';

(function () {
    let cardColor, headingColor, labelColor, legendColor, borderColor, shadeColor;

    // تشخیص حالت تاریک/روشن
    if (typeof isDarkStyle !== 'undefined' && isDarkStyle) {
        cardColor = config.colors_dark.cardColor;
        headingColor = config.colors_dark.headingColor;
        labelColor = config.colors_dark.textMuted;
        legendColor = config.colors_dark.bodyColor;
        borderColor = config.colors_dark.borderColor;
        shadeColor = 'dark';
    } else {
        cardColor = config.colors.white;
        headingColor = config.colors.headingColor;
        labelColor = config.colors.textMuted;
        legendColor = config.colors.bodyColor;
        borderColor = config.colors.borderColor;
        shadeColor = 'light';
    }

    // تنظیم locale فارسی برای تمام چارت‌ها
    if (typeof Apex !== 'undefined') {
        Apex.chart = {
            fontFamily: 'IRANSans, Tahoma, Arial',
            locales: [{
                "name": "fa",
                "options": {
                    "months": ["فروردین", "اردیبهشت", "خرداد", "تیر", "مرداد", "شهریور", "مهر", "آبان", "آذر", "دی", "بهمن", "اسفند"],
                    "shortMonths": ["فرو", "ارد", "خرد", "تیر", "مرد", "شهر", "مهر", "آبا", "آذر", "دی", "بهم", "اسف"],
                    "days": ["یکشنبه", "دوشنبه", "سه‌شنبه", "چهارشنبه", "پنج‌شنبه", "جمعه", "شنبه"],
                    "shortDays": ["ی", "د", "س", "چ", "پ", "ج", "ش"],
                    "toolbar": {
                        "exportToSVG": "دریافت SVG",
                        "exportToPNG": "دریافت PNG",
                        "menu": "فهرست",
                        "selection": "انتخاب",
                        "selectionZoom": "بزرگنمایی انتخاب شده",
                        "zoomIn": "بزرگ نمایی",
                        "zoomOut": "کوچک نمایی",
                        "pan": "جا به جایی",
                        "reset": "بازنشانی"
                    }
                }
            }],
            defaultLocale: "fa"
        };
    }

    async function loadDashboardStats() {
        try {
            const csrfToken = typeof window.csrfToken !== 'undefined' ? window.csrfToken : '';
            const formData = new FormData();
            formData.append('csrf_token', csrfToken);

            const response = await fetch('../incloud/get_dashboard_stats.php', {
                method: 'POST',
                body: formData
            });

            const result = await response.json();

            if (result.success) {
                updateDashboard(result.data);
            } else {
                console.error('خطا در دریافت آمار:', result.error);
                if (result.needs_config && typeof window.openConfigModal === 'function') {
                    window.openConfigModal();
                }
            }
        } catch (error) {
            console.error('خطا در بارگذاری آمار:', error);
        }
    }

    function updateDashboard(data) {
        updateReadinessChart(data.readiness_percentage);
        updateGeneralStats(data);
        updateWeeklyComparison(data);
        updateMonthlyChart(data.monthly_chart);
    }

    function updateReadinessChart(percentage) {
        const chartEl = document.querySelector('#growthRadialChart');
        if (!chartEl) return;

        chartEl.innerHTML = '';

        const isMobile = window.innerWidth < 768;
        const chartHeight = isMobile ? 180 : 265;

        const options = {
            chart: {
                height: chartHeight,
                type: 'radialBar',
                sparkline: {
                    show: true
                },
                fontFamily: 'IRANSans, Tahoma, Arial'
            },
            grid: {
                show: false,
                padding: {
                    top: isMobile ? -10 : -23,
                    bottom: isMobile ? -5 : -2
                }
            },
            plotOptions: {
                radialBar: {
                    size: isMobile ? 80 : 100,
                    startAngle: -135,
                    endAngle: 135,
                    offsetY: isMobile ? 5 : 10,
                    hollow: {
                        size: '55%'
                    },
                    track: {
                        strokeWidth: '50%',
                        background: cardColor,
                        opacity: 0.05
                    },
                    dataLabels: {
                        value: {
                            offsetY: isMobile ? -12 : -22,
                            color: headingColor,
                            fontWeight: 500,
                            fontSize: isMobile ? '16px' : '26px',
                            formatter: function (val) {
                                return parseInt(val) + '%';
                            }
                        },
                        name: {
                            fontSize: isMobile ? '11px' : '15px',
                            color: legendColor,
                            offsetY: isMobile ? 12 : 20
                        }
                    }
                }
            },
            colors: [config.colors.danger],
            fill: {
                type: 'gradient',
                gradient: {
                    shade: 'dark',
                    type: 'horizontal',
                    shadeIntensity: 0.5,
                    gradientToColors: [config.colors.primary],
                    inverseColors: true,
                    opacityFrom: 1,
                    opacityTo: 1,
                    stops: [0, 100]
                }
            },
            stroke: {
                show: false,
                width: 0
            },
            labels: ['آمادگی'],
            series: [percentage]
        };

        const chart = new ApexCharts(chartEl, options);
        chart.render();
    }

    function updateGeneralStats(data) {
        updateStatItem(0, 'کل سوالاتی که پاسخ دادید', data.total_answered, data.total_questions, 'bg-info');
        updateStatItem(1, 'سوالاتی که آماده اید برای امتحان', data.green_count, data.total_questions, 'bg-success');

        const halfReady = data.blue_count + data.yellow_count;
        updateStatItem(2, 'سوالاتی که پنجاه درصد آماده اید', halfReady, data.total_questions, 'bg-warning');

        const notReady = data.red_count + data.not_answered;
        updateStatItem(3, 'سوالاتی که اصلا آماده نیستید', notReady, data.total_questions, 'bg-danger');
    }

    function updateStatItem(index, label, value, total, colorClass) {
        const items = document.querySelectorAll('.card-body ul.p-0 > li');
        if (!items[index]) return;

        const percentage = total > 0 ? Math.round((value / total) * 100) : 0;

        const labelEl = items[index].querySelector('.d-flex.justify-content-between span:first-child');
        const valueEl = items[index].querySelector('.d-flex.justify-content-between .text-muted');
        const progressBar = items[index].querySelector('.progress-bar');

        if (labelEl) labelEl.textContent = label;
        if (valueEl) valueEl.textContent = value.toLocaleString('fa-IR');
        
        if (progressBar) {
            progressBar.className = `progress-bar ${colorClass}`;
            progressBar.style.width = percentage + '%';
            progressBar.setAttribute('aria-valuenow', percentage);
        }
    }

    function updateWeeklyComparison(data) {
        const container = document.querySelector('.weekly-comparison-card .card-body') || document.querySelector('.col-md-4.col-12.px-0 .card-body');
        if (!container) return;

        const improvementText = data.improvement >= 0
            ? `کارایی ${Math.abs(data.improvement)}% بهتر نسبت به هفته قبل`
            : `کارایی ${Math.abs(data.improvement)}% کمتر نسبت به هفته قبل`;

        const weekTitle = container.querySelector('h6.mt-1');
        const weekDesc = container.querySelector('.desc-text') || container.querySelector('p.mb-4');
        
        if (weekTitle) weekTitle.textContent = 'هفته قبل';
        if (weekDesc) weekDesc.textContent = improvementText;

        const items = container.querySelectorAll('ul li');
        
        if (items[0]) {
            const thisWeekSmall = items[0].querySelector('small') || items[0].querySelector('.val-text');
            const thisWeekProgress = items[0].querySelector('.progress-bar');
            if (thisWeekSmall) thisWeekSmall.textContent = data.this_week.toLocaleString('fa-IR') + ' سوال';
            if (thisWeekProgress) {
                const thisWeekPercentage = Math.min(100, Math.round((data.this_week / 500) * 100));
                thisWeekProgress.style.width = thisWeekPercentage + '%';
            }
        }

        if (items[1]) {
            const lastWeekSmall = items[1].querySelector('small') || items[1].querySelector('.val-text');
            const lastWeekProgress = items[1].querySelector('.progress-bar');
            if (lastWeekSmall) lastWeekSmall.textContent = data.last_week.toLocaleString('fa-IR') + ' سوال';
            if (lastWeekProgress) {
                const lastWeekPercentage = Math.min(100, Math.round((data.last_week / 500) * 100));
                lastWeekProgress.style.width = lastWeekPercentage + '%';
            }
        }
    }

    function updateMonthlyChart(chartData) {
        const chartEl = document.querySelector('#orderSummaryChart');
        if (!chartEl) return;

        chartEl.innerHTML = '';

        const options = {
            chart: {
                height: 255,
                type: 'area',
                toolbar: {
                    show: false
                },
                fontFamily: 'IRANSans, Tahoma, Arial',
                dropShadow: {
                    enabled: true,
                    top: 18,
                    left: 2,
                    blur: 3,
                    color: config.colors.primary,
                    opacity: 0.15
                }
            },
            markers: {
                size: 6,
                colors: 'transparent',
                strokeColors: 'transparent',
                strokeWidth: 4,
                discrete: [
                    {
                        fillColor: cardColor,
                        seriesIndex: 0,
                        dataPointIndex: chartData.data.length - 1,
                        strokeColor: config.colors.primary,
                        strokeWidth: 4,
                        size: 6,
                        radius: 2
                    }
                ],
                hover: {
                    size: 7
                }
            },
            series: [{
                name: 'تعداد سوالات',
                data: chartData.data
            }],
            dataLabels: {
                enabled: false
            },
            stroke: {
                curve: 'smooth',
                lineCap: 'round',
                width: 3
            },
            colors: [config.colors.primary],
            fill: {
                type: 'gradient',
                gradient: {
                    shade: shadeColor,
                    shadeIntensity: 0.8,
                    opacityFrom: 0.7,
                    opacityTo: 0.25,
                    stops: [0, 95, 100]
                }
            },
            grid: {
                show: true,
                borderColor: borderColor,
                padding: {
                    top: -15,
                    bottom: -10,
                    left: 15,
                    right: 10
                }
            },
            xaxis: {
                categories: chartData.labels,
                labels: {
                    offsetX: 0,
                    style: {
                        colors: labelColor,
                        fontSize: '13px',
                        fontFamily: 'IRANSans'
                    }
                },
                axisBorder: {
                    show: false
                },
                axisTicks: {
                    show: false
                },
                lines: {
                    show: false
                }
            },
            yaxis: {
                labels: {
                    offsetX: 7,
                    formatter: function (val) {
                        return val.toFixed(0) + ' سوال';
                    },
                    style: {
                        fontSize: '13px',
                        colors: labelColor,
                        fontFamily: 'IRANSans'
                    }
                },
                min: 0,
                tickAmount: 4
            },
            tooltip: {
                y: {
                    formatter: function (val) {
                        return val + ' سوال';
                    }
                }
            }
        };

        const chart = new ApexCharts(chartEl, options);
        chart.render();
    }

    document.addEventListener('DOMContentLoaded', function () {
        setTimeout(loadDashboardStats, 500);
        setInterval(loadDashboardStats, 300000);
    });

})();
    // مدیریت نمایش مودال‌ها به صورت متوالی
    document.addEventListener('DOMContentLoaded', async function () {
        if (showConfigModal) {
            // منتظر بمانید تا مودال تنظیمات بسته شود
            await openConfigModal();
        }
    });
    // تابع باز کردن مودال تنظیمات
    async function openConfigModal() {
        const { value: examDateType } = await Swal.fire({
            title: 'تنظیمات اولیه',
            html: `
            <div class="text-center mb-3">
                <p class="mb-3">تاریخ امتحان شما نسبت به <strong>${referenceDate}</strong> کی است؟</p>
            </div>
        `,
            icon: 'question',
            showCancelButton: false,
            allowOutsideClick: false,
            allowEscapeKey: false,
            confirmButtonText: 'قبل از این تاریخ',
            denyButtonText: 'بعد از این تاریخ',
            showDenyButton: true,
            reverseButtons: true,
            customClass: {
                confirmButton: 'btn btn-primary mx-2',
                denyButton: 'btn btn-success mx-2'
            },
            buttonsStyling: false,
            preConfirm: () => 'before',
            preDeny: () => 'after'
        });

        if (!examDateType) return;

        const { value: language } = await Swal.fire({
            title: 'انتخاب زبان مطالعه',
            html: `
            <div class="text-center mb-3">
                <p class="mb-3">به چه زبانی می‌خواهید مطالعه کنید؟</p>
            </div>
        `,
            icon: 'question',
            showCancelButton: false,
            allowOutsideClick: false,
            allowEscapeKey: false,
            confirmButtonText: 'آلمانی (DE)',
            denyButtonText: 'انگلیسی (EN)',
            showDenyButton: true,
            reverseButtons: true,
            customClass: {
                confirmButton: 'btn btn-primary mx-2',
                denyButton: 'btn btn-info mx-2'
            },
            buttonsStyling: false,
            preConfirm: () => 'DE',
            preDeny: () => 'EN'
        });

        if (!language) return;

        await saveUserConfig(examDateType, language);
    }

    // ذخیره تنظیمات کاربر
    async function saveUserConfig(examDateType, language) {
        try {
            Swal.fire({
                title: 'در حال ذخیره...',
                html: 'لطفاً صبر کنید',
                allowOutsideClick: false,
                allowEscapeKey: false,
                didOpen: () => {
                    Swal.showLoading();
                }
            });

            const formData = new FormData();
            formData.append('csrf_token', csrfToken);
            formData.append('exam_date_type', examDateType);
            formData.append('language', language);

            const response = await fetch('../incloud/save-user-config.php', {
                method: 'POST',
                body: formData
            });

            const result = await response.json();

            if (result.success) {
                await Swal.fire({
                    icon: 'success',
                    title: 'موفق!',
                    text: result.message,
                    confirmButtonText: 'باشه',
                    customClass: {
                        confirmButton: 'btn btn-success'
                    },
                    buttonsStyling: false
                });
                window.location.reload();
            } else {
                throw new Error(result.message);
            }
        } catch (error) {
            Swal.fire({
                icon: 'error',
                title: 'خطا!',
                text: error.message || 'خطا در ذخیره تنظیمات',
                confirmButtonText: 'باشه',
                customClass: {
                    confirmButton: 'btn btn-danger'
                },
                buttonsStyling: false
            });
        }
    }

    // Export openConfigModal to window
    window.openConfigModal = openConfigModal;
</script>

<!-- اسکریپت چارت‌های داشبورد با استایل‌های بهبود یافته -->
<!-- این فایل باید بعد از لود شدن ApexCharts فراخوانی شود -->
