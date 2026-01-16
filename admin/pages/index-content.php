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
                                href="practice.php">تمرین سوالات</a>
                        </div>
                    </div>
                </div>

                <div class="col-12 mb-3 col-md-6  ">
                    <div class="col-12  h-100 vh-25  w-100 position-relative text-white nav-hover-zoom nav-cyan-soft">
                        <img class="nav-img" src="assets/img/backgrounds/about-4-1.png" alt="...">
                        <div class="card-img-overlay d-flex align-items-center h-100 px-5"><img class="nav-icons"
                                src="assets/img/illustrations/2.png" alt="icon">
                            <a class="text-white fs-3 fs-xl-2 fs-xxl-3 stretched-link items fw-bold ps-4"
                                href="../exam/exam_simulator.php">شبیه ساز امتحان</a>
                        </div>
                    </div>
                </div>
                <div class="col-12 mb-3 mb-md-0 col-md-6 ">
                    <div class="col-12  h-100 vh-25  w-100 position-relative text-white nav-hover-zoom nav-purple-soft">
                        <img class="nav-img" src="assets/img/backgrounds/about-4-1.png" alt="...">
                        <div class="card-img-overlay d-flex align-items-center h-100 px-5"><img class="nav-icons"
                                src="assets/img/illustrations/3.png" alt="icon">
                            <a class="text-white fs-3 fs-xl-2 fs-xxl-3 stretched-link items fw-bold ps-4"
                                href="vocabulary.php">واژه آموزی</a>
                        </div>
                    </div>
                </div>
                <div class="col-12  col-md-6 ">
                    <div class="col-12  h-100 vh-25  w-100 position-relative text-white nav-hover-zoom nav-indigo-soft">
                        <img class="nav-img" src="assets/img/backgrounds/about-4-1.png" alt="...">
                        <div class="card-img-overlay d-flex align-items-center h-100 px-5"><img class="nav-icons"
                                src="assets/img/illustrations/4.png" alt="icon">
                            <a class="text-white fs-3 fs-xl-2 fs-xxl-3 stretched-link items fw-bold ps-4"
                                href="#about">کارگاه آموزش</a>
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
                                <span class="avatar-initial rounded-circle bg-label-success"><i
                                        class="bx bx-dollar"></i></span>
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
                                <span class="avatar-initial rounded-circle bg-label-success"><i
                                        class="bx bx-dollar"></i></span>
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
</div>
<!-- سایر بخش‌های داشبورد... -->
</div>

<!-- SweetAlert2 JS -->
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<script>
    const csrfToken = '<?php echo $_SESSION['csrf_token']; ?>';
    const showConfigModal = <?php echo isset($_SESSION['show_config_modal']) && $_SESSION['show_config_modal'] ? 'true' : 'false'; ?>;
    const referenceDate = '<?php echo date('d.m.Y', strtotime($referenceDate)); ?>';

    // نمایش مودال تنظیمات در صورت نیاز
    document.addEventListener('DOMContentLoaded', function () {
        if (showConfigModal) {
            openConfigModal();
        }
    });

    // تابع باز کردن مودال تنظیمات
    async function openConfigModal() {
        // مرحله 1: انتخاب تاریخ امتحان
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

        if (!examDateType) {
            return; // کاربر کنسل کرد
        }

        // مرحله 2: انتخاب زبان
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

        if (!language) {
            return; // کاربر کنسل کرد
        }

        // ذخیره تنظیمات
        await saveUserConfig(examDateType, language);
    }

    // ذخیره تنظیمات کاربر
    async function saveUserConfig(examDateType, language) {
        try {
            // نمایش لودینگ
            Swal.fire({
                title: 'در حال ذخیره...',
                html: 'لطفاً صبر کنید',
                allowOutsideClick: false,
                allowEscapeKey: false,
                didOpen: () => {
                    Swal.showLoading();
                }
            });

            // ارسال درخواست
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
                // نمایش پیام موفقیت
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

                // رفرش صفحه
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
</script>
<script>
    // dashboard_dynamic.js
    // تابع برای دریافت و نمایش آمار داشبورد
    async function loadDashboardStats() {
        try {
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
            }
        } catch (error) {
            console.error('خطا در بارگذاری آمار:', error);
        }
    }

    // تابع برای به‌روزرسانی المان‌های داشبورد
    function updateDashboard(data) {
        // به‌روزرسانی نمودار آمادگی (Radial Chart)
        updateReadinessChart(data.readiness_percentage);

        // به‌روزرسانی آمار کلی
        updateGeneralStats(data);

        // به‌روزرسانی مقایسه هفتگی
        updateWeeklyComparison(data);

        // به‌روزرسانی نمودار ماهانه
        updateMonthlyChart(data.monthly_chart);
    }

    // نمودار آمادگی (Radial/Growth Chart)
    function updateReadinessChart(percentage) {
        const chartEl = document.querySelector('#growthRadialChart');
        if (!chartEl) return;

        const options = {
            series: [percentage],
            chart: {
                height: 240,
                type: 'radialBar'
            },
            plotOptions: {
                radialBar: {
                    hollow: {
                        size: '65%'
                    },
                    dataLabels: {
                        show: true,
                        name: {
                            offsetY: -10,
                            show: true,
                            color: '#888',
                            fontSize: '13px'
                        },
                        value: {
                            color: '#111',
                            fontSize: '30px',
                            show: true,
                            formatter: function (val) {
                                return parseInt(val) + '%';
                            }
                        }
                    },
                    track: {
                        background: '#f2f2f2',
                        strokeWidth: '100%'
                    }
                }
            },
            colors: ['#696cff'],
            fill: {
                type: 'gradient',
                gradient: {
                    shade: 'dark',
                    type: 'horizontal',
                    shadeIntensity: 0.5,
                    gradientToColors: ['#28c76f'],
                    inverseColors: true,
                    opacityFrom: 1,
                    opacityTo: 1,
                    stops: [0, 100]
                }
            },
            stroke: {
                lineCap: 'round'
            },
            labels: ['آمادگی']
        };

        const chart = new ApexCharts(chartEl, options);
        chart.render();
    }

    // به‌روزرسانی آمار کلی
    function updateGeneralStats(data) {
        // کل سوالاتی که پاسخ دادید
        updateStatItem(0, 'کل سوالاتی که پاسخ دادید', data.total_answered, data.total_questions, 'bg-info');

        // سوالات آماده (سبز)
        updateStatItem(1, 'سوالاتی که آماده اید برای امتحان', data.green_count, data.total_questions, 'bg-success');

        // سوالات نیمه آماده (آبی + زرد)
        const halfReady = data.blue_count + data.yellow_count;
        updateStatItem(2, 'سوالاتی که پنجاه درصد آماده اید', halfReady, data.total_questions, 'bg-primary');

        // سوالات ناآماده (قرمز + خاکستری)
        const notReady = data.red_count + data.not_answered;
        updateStatItem(3, 'سوالاتی که اصلا آماده نیستید', notReady, data.total_questions, 'bg-danger');
    }

    // تابع کمکی برای به‌روزرسانی هر آیتم آمار
    function updateStatItem(index, label, value, total, colorClass) {
        const items = document.querySelectorAll('.card-body ul.p-0 > li');
        if (!items[index]) return;

        const percentage = total > 0 ? Math.round((value / total) * 100) : 0;

        items[index].querySelector('.d-flex.justify-content-between span:first-child').textContent = label;
        items[index].querySelector('.d-flex.justify-content-between .text-muted').textContent = value.toLocaleString('fa-IR');

        const progressBar = items[index].querySelector('.progress-bar');
        progressBar.className = `progress-bar ${colorClass}`;
        progressBar.style.width = percentage + '%';
        progressBar.setAttribute('aria-valuenow', percentage);
    }

    // به‌روزرسانی مقایسه هفتگی
    function updateWeeklyComparison(data) {
        const container = document.querySelector('.col-md-4.col-12.px-0 .card-body');
        if (!container) return;

        // محاسبه درصد بهبود
        const improvementText = data.improvement >= 0
            ? `کارایی ${Math.abs(data.improvement)}% بهتر نسبت به هفته قبل`
            : `کارایی ${Math.abs(data.improvement)}% کمتر نسبت به هفته قبل`;

        container.querySelector('h6.mt-1').textContent = 'هفته قبل';
        container.querySelector('p.mb-4').textContent = improvementText;

        // به‌روزرسانی مقادیر
        const thisWeekItem = container.querySelectorAll('ul li')[0];
        const lastWeekItem = container.querySelectorAll('ul li')[1];

        // این هفته
        thisWeekItem.querySelector('small').textContent = data.this_week.toLocaleString('fa-IR') + ' سوال';
        const thisWeekPercentage = Math.min(100, Math.round((data.this_week / 200) * 100));
        thisWeekItem.querySelector('.progress-bar').style.width = thisWeekPercentage + '%';

        // هفته گذشته
        lastWeekItem.querySelector('small').textContent = data.last_week.toLocaleString('fa-IR') + ' سوال';
        const lastWeekPercentage = Math.min(100, Math.round((data.last_week / 200) * 100));
        lastWeekItem.querySelector('.progress-bar').style.width = lastWeekPercentage + '%';
    }

    // به‌روزرسانی نمودار ماهانه
    function updateMonthlyChart(chartData) {
        const chartEl = document.querySelector('#orderSummaryChart');
        if (!chartEl) return;

        const options = {
            series: [{
                name: 'تعداد سوالات',
                data: chartData.data
            }],
            chart: {
                type: 'line',
                height: 350,
                toolbar: {
                    show: false
                },
                fontFamily: 'IRANSans, Tahoma, Arial',
                locales: [{
                    name: 'fa',
                    options: {
                        months: ['فروردین', 'اردیبهشت', 'خرداد', 'تیر', 'مرداد', 'شهریور', 'مهر', 'آبان', 'آذر', 'دی', 'بهمن', 'اسفند'],
                        shortMonths: ['فرو', 'ارد', 'خرد', 'تیر', 'مرد', 'شهر', 'مهر', 'آبا', 'آذر', 'دی', 'بهم', 'اسف'],
                        days: ['یکشنبه', 'دوشنبه', 'سه‌شنبه', 'چهارشنبه', 'پنج‌شنبه', 'جمعه', 'شنبه'],
                        shortDays: ['ی', 'د', 'س', 'چ', 'پ', 'ج', 'ش']
                    }
                }],
                defaultLocale: 'fa'
            },
            stroke: {
                curve: 'smooth',
                width: 3
            },
            colors: ['#696cff'],
            xaxis: {
                categories: chartData.labels,
                labels: {
                    style: {
                        fontFamily: 'IRANSans'
                    }
                }
            },
            yaxis: {
                labels: {
                    style: {
                        fontFamily: 'IRANSans'
                    },
                    formatter: function (val) {
                        return val.toFixed(0);
                    }
                }
            },
            dataLabels: {
                enabled: false
            },
            grid: {
                borderColor: '#f1f1f1',
                strokeDashArray: 4
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

    // بارگذاری آمار هنگام لود شدن صفحه
    document.addEventListener('DOMContentLoaded', function () {
        loadDashboardStats();

        // به‌روزرسانی خودکار هر 5 دقیقه
        setInterval(loadDashboardStats, 300000);
    });
</script>
<script>
    // dashboard_dynamic.js
    async function loadDashboardStats() {
        try {
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

                // اگر نیاز به تنظیمات است، مودال را باز کن
                if (result.needs_config) {
                    openConfigModal();
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

        // پاک کردن نمودار قبلی
        chartEl.innerHTML = '';

        const options = {
            series: [percentage],
            chart: {
                height: 240,
                type: 'radialBar'
            },
            plotOptions: {
                radialBar: {
                    hollow: {
                        size: '65%'
                    },
                    dataLabels: {
                        show: true,
                        name: {
                            offsetY: -10,
                            show: true,
                            color: '#888',
                            fontSize: '13px'
                        },
                        value: {
                            color: '#111',
                            fontSize: '30px',
                            show: true,
                            formatter: function (val) {
                                return parseInt(val) + '%';
                            }
                        }
                    },
                    track: {
                        background: '#f2f2f2',
                        strokeWidth: '100%'
                    }
                }
            },
            colors: ['#696cff'],
            fill: {
                type: 'gradient',
                gradient: {
                    shade: 'dark',
                    type: 'horizontal',
                    shadeIntensity: 0.5,
                    gradientToColors: ['#28c76f'],
                    inverseColors: true,
                    opacityFrom: 1,
                    opacityTo: 1,
                    stops: [0, 100]
                }
            },
            stroke: {
                lineCap: 'round'
            },
            labels: ['آمادگی']
        };

        const chart = new ApexCharts(chartEl, options);
        chart.render();
    }

    function updateGeneralStats(data) {
        updateStatItem(0, 'کل سوالاتی که پاسخ دادید', data.total_answered, data.total_questions, 'bg-info');
        updateStatItem(1, 'سوالاتی که آماده اید برای امتحان', data.green_count, data.total_questions, 'bg-success');

        const halfReady = data.blue_count + data.yellow_count;
        updateStatItem(2, 'سوالاتی که پنجاه درصد آماده اید', halfReady, data.total_questions, 'bg-primary');

        const notReady = data.red_count + data.not_answered;
        updateStatItem(3, 'سوالاتی که اصلا آماده نیستید', notReady, data.total_questions, 'bg-danger');
    }

    function updateStatItem(index, label, value, total, colorClass) {
        const items = document.querySelectorAll('.card-body ul.p-0 > li');
        if (!items[index]) return;

        const percentage = total > 0 ? Math.round((value / total) * 100) : 0;

        items[index].querySelector('.d-flex.justify-content-between span:first-child').textContent = label;
        items[index].querySelector('.d-flex.justify-content-between .text-muted').textContent = value.toLocaleString('fa-IR');

        const progressBar = items[index].querySelector('.progress-bar');
        progressBar.className = `progress-bar ${colorClass}`;
        progressBar.style.width = percentage + '%';
        progressBar.setAttribute('aria-valuenow', percentage);
    }

    function updateWeeklyComparison(data) {
        const container = document.querySelector('.col-md-4.col-12.px-0 .card-body');
        if (!container) return;

        const improvementText = data.improvement >= 0
            ? `کارایی ${Math.abs(data.improvement)}% بهتر نسبت به هفته قبل`
            : `کارایی ${Math.abs(data.improvement)}% کمتر نسبت به هفته قبل`;

        container.querySelector('h6.mt-1').textContent = 'هفته قبل';
        container.querySelector('p.mb-4').textContent = improvementText;

        const thisWeekItem = container.querySelectorAll('ul li')[0];
        const lastWeekItem = container.querySelectorAll('ul li')[1];

        thisWeekItem.querySelector('small').textContent = data.this_week.toLocaleString('fa-IR') + ' سوال';
        const thisWeekPercentage = Math.min(100, Math.round((data.this_week / 200) * 100));
        thisWeekItem.querySelector('.progress-bar').style.width = thisWeekPercentage + '%';

        lastWeekItem.querySelector('small').textContent = data.last_week.toLocaleString('fa-IR') + ' سوال';
        const lastWeekPercentage = Math.min(100, Math.round((data.last_week / 200) * 100));
        lastWeekItem.querySelector('.progress-bar').style.width = lastWeekPercentage + '%';
    }

    function updateMonthlyChart(chartData) {
        const chartEl = document.querySelector('#orderSummaryChart');
        if (!chartEl) return;

        // پاک کردن نمودار قبلی
        chartEl.innerHTML = '';

        const options = {
            series: [{
                name: 'تعداد سوالات',
                data: chartData.data
            }],
            chart: {
                type: 'line',
                height: 350,
                toolbar: {
                    show: false
                },
                fontFamily: 'IRANSans, Tahoma, Arial'
            },
            stroke: {
                curve: 'smooth',
                width: 3
            },
            colors: ['#696cff'],
            xaxis: {
                categories: chartData.labels,
                labels: {
                    style: {
                        fontFamily: 'IRANSans'
                    }
                }
            },
            yaxis: {
                labels: {
                    style: {
                        fontFamily: 'IRANSans'
                    },
                    formatter: function (val) {
                        return val.toFixed(0);
                    }
                }
            },
            dataLabels: {
                enabled: false
            },
            grid: {
                borderColor: '#f1f1f1',
                strokeDashArray: 4
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

    // بارگذاری آمار هنگام لود شدن صفحه
    document.addEventListener('DOMContentLoaded', function () {
        // صبر می‌کنیم تا اسکریپت تنظیمات اجرا شود
        setTimeout(loadDashboardStats, 500);

        // به‌روزرسانی خودکار هر 5 دقیقه
        setInterval(loadDashboardStats, 300000);
    });
</script>