<?php
// dashboard.php
require_once(__DIR__ . '/incloud/functions.php');

// بررسی ورود کاربر
if (!is_logged_in() || !validate_session($pdo)) {
    header("Location: index.php");
    exit();
}

// دریافت اطلاعات کاربر
$user_name = $_SESSION['name'] ?? 'کاربر';
$user_role = $_SESSION['role'] ?? 'user';
$user_email = $_SESSION['email'] ?? '';

// دریافت آخرین ورودها
$stmt = $pdo->prepare("
    SELECT * FROM user_logs 
    WHERE user_id = ? AND action = 'login' 
    ORDER BY created_at DESC 
    LIMIT 5
");
$stmt->execute([$_SESSION['user_id']]);
$recent_logins = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>داشبورد - <?php echo SITE_NAME; ?></title>
    
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    
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
                    <?php if ($user_role === 'admin'): ?>
                        <span class="badge bg-warning">مدیر</span>
                    <?php endif; ?>
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
                                        <?php echo $user_role === 'admin' ? 'مدیر سیستم' : 'کاربر عادی'; ?>
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Recent Logins -->
                <div class="card">
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

                <!-- Admin Panel Notice -->
                <?php if ($user_role === 'admin'): ?>
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
</body>
</html>