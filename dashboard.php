<?php
// dashboard.php
require_once(__DIR__ . '/incloud/functions.php');
require_once(__DIR__ . '/incloud/subscription-functions.php');

// بررسی ورود کاربر
if (!is_logged_in() || !validate_session($pdo)) {
    header("Location: index.php");
    exit();
}

// دریافت اطلاعات کاربر
$user_id = $_SESSION['user_id'];
$user_name = $_SESSION['name'] ?? 'کاربر';
$user_role = $_SESSION['role'] ?? 'user';
$user_email = $_SESSION['email'] ?? '';

// دریافت اطلاعات اشتراک
$active_sub = get_user_active_subscription($user_id, $pdo);
$sub_text = '';
if ($active_sub && $active_sub['plan_slug'] !== 'free') {
    $days_remaining = get_days_until_expiry($user_id, $pdo);
    $sub_text = ' <span class="badge bg-success ms-2">' . htmlspecialchars($active_sub['plan_name']);
    if ($days_remaining !== null) {
        $sub_text .= ' (' . $days_remaining . ' روز باقی‌مانده)';
    }
    $sub_text .= '</span>';
}

// دریافت آخرین ورودها
$stmt = $pdo->prepare("
    SELECT * FROM user_logs 
    WHERE user_id = ? AND action = 'login' 
    ORDER BY created_at DESC 
    LIMIT 5
");
$stmt->execute([$_SESSION['user_id']]);
$recent_logins = $stmt->fetchAll();

// دریافت گزارش‌های اخیر کاربر
$stmtReports = $pdo->prepare("
    SELECT qr.*, q.number as q_number 
    FROM question_reports qr
    JOIN questions q ON qr.question_id = q.id
    WHERE qr.user_id = ? 
    ORDER BY qr.created_at DESC 
    LIMIT 10
");
$stmtReports->execute([$_SESSION['user_id']]);
$user_reports = $stmtReports->fetchAll();
?>
<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>داشبورد - <?php echo SITE_NAME; ?></title>
    
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="manifest" href="/manifest.json">
    <meta name="theme-color" content="#667eea">
    
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    
    <!-- Custom CSS -->
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
    <!-- Navbar -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary">
        <div class="container">
            <a class="navbar-brand" href="#"><?php echo SITE_NAME; ?></a>
            <div class="ms-auto d-flex align-items-center">
                <span class="text-white me-3">
                    <i class="bi bi-person-circle"></i> <?php echo htmlspecialchars($user_name); ?>
                    <?php if (is_super_admin()): ?>
                        <span class="badge bg-warning">مدیر</span>
                    <?php endif; ?>
                    <?php echo $sub_text; ?>
                </span>
                <a href="logout.php" class="btn btn-outline-light btn-sm">
                    <i class="bi bi-box-arrow-right"></i> خروج
                </a>
            </div>
        </div>
    </nav>

    <!-- Main Content -->
    <div class="container my-5">
        <div class="row">
            <div class="col-lg-8 mx-auto">
                <!-- Welcome Card -->
                <div class="card mb-4">
                    <div class="card-body">
                        <h2 class="card-title">سلام <?php echo htmlspecialchars($user_name); ?> عزیز!</h2>
                        <p class="card-text">به داشبورد خود خوش آمدید. شما با موفقیت وارد سیستم شده‌اید.</p>
                        
                        <div class="row mt-4">
                            <div class="col-md-6">
                                <div class="d-flex align-items-center mb-2">
                                    <i class="bi bi-envelope text-primary me-2"></i>
                                    <strong>ایمیل:</strong>
                                    <span class="ms-2"><?php echo htmlspecialchars($user_email); ?></span>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="d-flex align-items-center mb-2">
                                    <i class="bi bi-shield-check text-primary me-2"></i>
                                    <strong>نقش:</strong>
                                    <span class="ms-2">
                                        <?php echo is_super_admin() ? 'مدیر سیستم' : 'کاربر عادی'; ?>
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Recent Logins -->
                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="mb-0">
                            <i class="bi bi-clock-history"></i> آخرین ورودهای شما
                        </h5>
                    </div>
                    <div class="card-body">
                        <?php if (count($recent_logins) > 0): ?>
                            <div class="table-responsive">
                                <table class="table table-hover">
                                    <thead>
                                        <tr>
                                            <th>تاریخ و زمان</th>
                                            <th>آدرس IP</th>
                                            <th>وضعیت</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($recent_logins as $login): ?>
                                            <tr>
                                                <td>
                                                    <?php 
                                                    $date = new DateTime($login['created_at']);
                                                    echo $date->format('Y/m/d - H:i:s');
                                                    ?>
                                                </td>
                                                <td><?php echo htmlspecialchars($login['ip_address']); ?></td>
                                                <td>
                                                    <?php if ($login['status'] === 'success'): ?>
                                                        <span class="badge bg-success">موفق</span>
                                                    <?php else: ?>
                                                        <span class="badge bg-danger">ناموفق</span>
                                                    <?php endif; ?>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php else: ?>
                            <p class="text-muted text-center">هیچ سابقه ورودی یافت نشد.</p>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Question Reports Tracking -->
                <div class="card mb-4">
                    <div class="card-header bg-danger text-white">
                        <h5 class="mb-0">
                            <i class="bi bi-bug"></i> پیگیری گزارش‌های خطای سوالات
                        </h5>
                    </div>
                    <div class="card-body">
                        <?php if (count($user_reports) > 0): ?>
                            <div class="table-responsive">
                                <table class="table table-hover">
                                    <thead>
                                        <tr>
                                            <th>شماره سوال</th>
                                            <th>تاریخ ثبت</th>
                                            <th>وضعیت</th>
                                            <th>توضیحات / علت رد</th>
                                        </tr>
                                    </thead>
                                    <tbody>
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
                                                        <span class="badge bg-warning text-dark">در انتظار بررسی</span>
                                                    <?php elseif ($report['status'] === 'approved'): ?>
                                                        <span class="badge bg-success">تایید شده <i class="bi bi-gift-fill"></i></span>
                                                    <?php else: ?>
                                                        <span class="badge bg-danger">رد شده</span>
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
                                                        <span class="text-muted small"><?php echo htmlspecialchars(mb_strimwidth($report['message'], 0, 40, "...")); ?></span>
                                                    <?php endif; ?>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php else: ?>
                            <p class="text-muted text-center">شما هنوز هیچ گزارشی ثبت نکرده‌اید.</p>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Admin Panel Notice -->
                <?php if (is_super_admin()): ?>
                    <div class="alert alert-info mt-4" role="alert">
                        <i class="bi bi-info-circle"></i>
                        شما به عنوان مدیر وارد شده‌اید. پنل مدیریت در حال توسعه است و به زودی در دسترس خواهد بود.
                    </div>
                <?php endif; ?>

                <!-- User Panel Notice -->
                <div class="alert alert-warning mt-4" role="alert">
                    <i class="bi bi-exclamation-triangle"></i>
                    این یک صفحه موقت است. پنل کاربری کامل در حال توسعه است.
                </div>
            </div>
        </div>
    </div>

    <!-- Scripts -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
    // Service Worker Registration
    if ('serviceWorker' in navigator) {
        window.addEventListener('load', () => {
            navigator.serviceWorker.register('/sw.js?v=4')
                .then(reg => console.log('Service Worker registered'))
                .catch(err => console.log('Service Worker registration failed: ', err));
        });
    }

    // PWA Install Prompt Logic
    let deferredPrompt;
    const isIOS = /iPad|iPhone|iPod/.test(navigator.userAgent) && !window.MSStream;
    const isStandalone = window.matchMedia('(display-mode: standalone)').matches || window.navigator.standalone;

    window.addEventListener('beforeinstallprompt', (e) => {
        e.preventDefault();
        deferredPrompt = e;

        if (!sessionStorage.getItem('pwaPromptShown')) {
            showInstallPrompt('android');
            sessionStorage.setItem('pwaPromptShown', 'true');
        }
    });

    document.addEventListener('DOMContentLoaded', function() {
        if (isIOS && !isStandalone && !sessionStorage.getItem('pwaPromptShown')) {
            showInstallPrompt('ios');
            sessionStorage.setItem('pwaPromptShown', 'true');
        }
    });

    function showInstallPrompt(platform) {
        if (platform === 'android') {
            Swal.fire({
                title: 'نصب اپلیکیشن',
                text: 'آیا مایل هستید برای دسترسی سریع‌تر، اپلیکیشن را نصب کنید؟',
                icon: 'info',
                showCancelButton: true,
                confirmButtonText: 'بله، نصب شود',
                cancelButtonText: 'بعداً',
                customClass: {
                    confirmButton: 'btn btn-primary me-3',
                    cancelButton: 'btn btn-label-secondary'
                },
                buttonsStyling: false
            }).then((result) => {
                if (result.isConfirmed && deferredPrompt) {
                    deferredPrompt.prompt();
                    deferredPrompt.userChoice.then((choiceResult) => {
                        deferredPrompt = null;
                    });
                }
            });
        } else if (platform === 'ios') {
            Swal.fire({
                title: 'نصب در آیفون',
                html: `
                    <div class="text-end" style="direction: rtl;">
                        <p>برای نصب اپلیکیشن در آیفون، مراحل زیر را دنبال کنید:</p>
                        <ol class="pr-3">
                            <li>در نوار پایین مرورگر دکمه <b>Share</b> <i class="bi bi-share"></i> را بزنید.</li>
                            <li>در منوی باز شده، گزینه <b>Add to Home Screen</b> را انتخاب کنید.</li>
                            <li>در بالا سمت راست، دکمه <b>Add</b> را بزنید.</li>
                        </ol>
                    </div>
                `,
                icon: 'info',
                confirmButtonText: 'متوجه شدم',
                customClass: {
                    confirmButton: 'btn btn-primary'
                },
                buttonsStyling: false
            });
        }
    }
</script>
</body>
</html>