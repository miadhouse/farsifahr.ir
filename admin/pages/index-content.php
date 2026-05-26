<?php
// dashboard.php
require_once('../incloud/user-config-handler.php');

// بررسی ورود کاربر
if (!is_logged_in()) {
    header('Location: login.php');
    exit;
}

// چک کردن تنظیمات کاربر
check_user_configuration();

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
    
    <!-- کارت تنظیمات -->
    <div class="row mb-4">
        <div class="col ">
            <div class="card h-100">
                <div class="card-header">
                    <div class="row">
                        <div class="col-md-5 mb-2">
                            <span class="fs-6">تاریخ امتحان :</span>
                            <span class=" ">
                                <?php echo $isConfigured ? $examDateText : 'تنظیم نشده'; ?>
                            </span>
                            <span class="fs-6 badge bg-label-primary rounded-pill text-uppercase">
                                <?php echo date('d.m.Y', strtotime($referenceDate)); ?>
                            </span>
                            <button type=" button" onclick="openConfigModal()"
                                class=" btn btn-sm rounded-pill btn-icon btn-label-secondary">
                                <span class="tf-icons bx bx-cog"></span>
                            </button>
                        </div>
                        <div class="col-md-5">
                            <span class="fs-6 ">زبان مطالعه :</span>
                            <span class="fs-6 badge bg-label-primary rounded-pill text-uppercase">
                                <?php echo $isConfigured ? $languageText : 'تنظیم نشده'; ?>
                            </span>
                            <button type=" button" onclick="openConfigModal()"
                                class=" btn btn-sm rounded-pill btn-icon btn-label-secondary">
                                <span class="tf-icons bx bx-cog"></span>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- بقیه محتوای داشبورد -->
    <div class="row">
        <!-- Activity -->
        <div class="col-md-8 col-lg-8 col-xl-8 col-xxl-8 mb-4 base-btn">
            <div class="row h-100">
                <div class="col-12 mb-3 col-md-6 ">
                    <div class="col-12  h-100 vh-25  w-100 position-relative text-white nav-hover-zoom nav-blue-soft">
                        <img class="nav-img" src="assets/img/backgrounds/about-4-1.png" alt="...">
                        <div class="card-img-overlay d-flex align-items-center h-100 px-5"><img class="nav-icons"
                                src="assets/img/illustrations/1.png" alt="icon">
                            <a class="text-white fs-3 fs-xl-2 fs-xxl-3 stretched-link items fw-bold  ps-4"
                                href="practice.php"><?= __('practice_questions') ?></a>
                        </div>
                    </div>
                </div>

                <div class="col-12 mb-3 col-md-6  ">
                    <div class="col-12  h-100 vh-25  w-100 position-relative text-white nav-hover-zoom nav-cyan-soft">
                        <img class="nav-img" src="assets/img/backgrounds/about-4-1.png" alt="...">
                        <div class="card-img-overlay d-flex align-items-center h-100 px-5"><img class="nav-icons"
                                src="assets/img/illustrations/2.png" alt="icon">
                            <a class="text-white fs-3 fs-xl-2 fs-xxl-3 stretched-link items fw-bold ps-4"
                                href="../exam/exam_simulator.php"><?= __('exam_simulator') ?></a>
                        </div>
                    </div>
                </div>
                <div class="col-12 mb-3 mb-md-0 col-md-6 ">
                    <div class="col-12  h-100 vh-25  w-100 position-relative text-white nav-hover-zoom nav-purple-soft">
                        <img class="nav-img" src="assets/img/backgrounds/about-4-1.png" alt="...">
                        <div class="card-img-overlay d-flex align-items-center h-100 px-5"><img class="nav-icons"
                                src="assets/img/illustrations/3.png" alt="icon">
                            <a class="text-white fs-3 fs-xl-2 fs-xxl-3 stretched-link items fw-bold ps-4"
                                href="vocabulary.php"><?= __('vocabulary_learning') ?></a>
                        </div>
                    </div>
                </div>
                <div class="col-12  col-md-6 ">
                    <div class="col-12  h-100 vh-25  w-100 position-relative text-white nav-hover-zoom nav-indigo-soft">
                        <img class="nav-img" src="assets/img/backgrounds/about-4-1.png" alt="...">
                        <div class="card-img-overlay d-flex align-items-center h-100 px-5"><img class="nav-icons"
                                src="assets/img/illustrations/4.png" alt="icon">
                            <span class="text-white fs-3 fs-xl-2 fs-xxl-3 items fw-bold ps-4" style="opacity: 0.6; cursor: not-allowed;">
                                کارگاه آموزش <small style="font-size: 0.6em; font-weight: normal;">(به زودی)</small>
                            </span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <!--/ Activity -->

        <!-- Activity -->
        <div class="col-md-4 col-lg-4 col-xl-4 col-xxl-4 mb-4">
            <div class="card h-100">
                <div class="card-body text-center">
                    <div id="growthRadialChart"></div>
                    <h6 class="mb-0">آمادگی شما جهت امتحان</h6>
                </div>
            </div>
        </div>
        <!--/ Activity -->
    </div>
    <div class="row">
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
        <div class="col-12 col-md-8 col-12 mb-4">
            <div class="card h-100">
                <div class="row row-bordered m-0">
                    <!-- Order Summary -->
                    <div class="col-md-8 col-12 pe-0">
                        <div class="card-header d-flex justify-content-between align-items-center">
                            <h5 class="card-title mb-0">گزارش ماهیانه پیشرفت</h5>
                        </div>
                        <div class="card-body p-0">
                            <div id="orderSummaryChart"></div>
                        </div>
                    </div>
                    <!-- Sales History -->
                    <div class="col-md-4 col-12 px-0">
                        <div class="card-header d-flex justify-content-between align-items-center">
                            <h5 class="card-title mb-0">مقایسه با هفته گذشته</h5>
                        </div>
                        <div class="card-body">
                            <h6 class="mt-1">هفته قبل</h6>
                            <p class="mb-4">کارایی 45% بهتر نسبت به هفته قبل</p>
                            <ul class="list-unstyled m-0 pt-0">
                                <li class="mb-4">
                                    <div class="d-flex align-items-center mb-2">
                                        <div class="avatar avatar-sm flex-shrink-0 me-2">
                                            <span class="avatar-initial rounded bg-label-primary"><i
                                                    class="bx bx-trending-up"></i></span>
                                        </div>
                                        <div>
                                            <p class="mb-0 text-muted text-nowrap">مطالعه این هفته</p>
                                            <small class="fw-semibold text-nowrap">116 سوال</small>
                                        </div>
                                    </div>
                                    <div class="progress" style="height: 6px">
                                        <div class="progress-bar bg-primary" style="width: 75%" role="progressbar"
                                            aria-valuenow="75" aria-valuemin="0" aria-valuemax="100"></div>
                                    </div>
                                </li>
                                <li>
                                    <div class="d-flex align-items-center mb-2">
                                        <div class="avatar avatar-sm flex-shrink-0 me-2">
                                            <span class="avatar-initial rounded bg-label-success"><i
                                                    class="bx bx-dollar"></i></span>
                                        </div>
                                        <div>
                                            <p class="mb-0 text-muted text-nowrap">مطالعه هفته گذشته</p>
                                            <small class="fw-semibold text-nowrap">98 سوال</small>
                                        </div>
                                    </div>
                                    <div class="progress" style="height: 6px">
                                        <div class="progress-bar bg-success" style="width: 75%" role="progressbar"
                                            aria-valuenow="75" aria-valuemin="0" aria-valuemax="100"></div>
                                    </div>
                                </li>
                            </ul>
                        </div>
                    </div>
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

        const options = {
            chart: {
                height: 265,
                type: 'radialBar',
                sparkline: {
                    show: true
                },
                fontFamily: 'IRANSans, Tahoma, Arial'
            },
            grid: {
                show: false,
                padding: {
                    top: -23,
                    bottom: -2
                }
            },
            plotOptions: {
                radialBar: {
                    size: 100,
                    startAngle: -135,
                    endAngle: 135,
                    offsetY: 10,
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
                            offsetY: -22,
                            color: headingColor,
                            fontWeight: 500,
                            fontSize: '26px',
                            formatter: function (val) {
                                return parseInt(val) + '%';
                            }
                        },
                        name: {
                            fontSize: '15px',
                            color: legendColor,
                            offsetY: 20
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
        const container = document.querySelector('.col-md-4.col-12.px-0 .card-body');
        if (!container) return;

        const improvementText = data.improvement >= 0
            ? `کارایی ${Math.abs(data.improvement)}% بهتر نسبت به هفته قبل`
            : `کارایی ${Math.abs(data.improvement)}% کمتر نسبت به هفته قبل`;

        const weekTitle = container.querySelector('h6.mt-1');
        const weekDesc = container.querySelector('p.mb-4');
        
        if (weekTitle) weekTitle.textContent = 'هفته قبل';
        if (weekDesc) weekDesc.textContent = improvementText;

        const items = container.querySelectorAll('ul li');
        
        if (items[0]) {
            const thisWeekSmall = items[0].querySelector('small');
            const thisWeekProgress = items[0].querySelector('.progress-bar');
            if (thisWeekSmall) thisWeekSmall.textContent = data.this_week.toLocaleString('fa-IR') + ' سوال';
            if (thisWeekProgress) {
                const thisWeekPercentage = Math.min(100, Math.round((data.this_week / 500) * 100));
                thisWeekProgress.style.width = thisWeekPercentage + '%';
            }
        }

        if (items[1]) {
            const lastWeekSmall = items[1].querySelector('small');
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
