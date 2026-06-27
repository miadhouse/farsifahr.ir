<?php
// service-eyetest.php
require_once __DIR__ . '/incloud/functions.php';
require_once __DIR__ . '/incloud/subscription-functions.php';

$service_key = 'eyetest';
$stmt = $pdo->prepare("SELECT * FROM service_settings WHERE service_key = ? AND is_active = 1 LIMIT 1");
$stmt->execute([$service_key]);
$setting = $stmt->fetch();

if (!$setting) {
    header("Location: index.php");
    exit();
}

$user_id = $_SESSION['user_id'] ?? null;
$user_info = null;
$has_active_sub = false;

if ($user_id) {
    $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->execute([$user_id]);
    $user_info = $stmt->fetch();

    $active_sub = get_user_active_subscription($user_id, $pdo);
    $has_active_sub = ($active_sub && $active_sub['plan_slug'] !== 'free');
}

$errors = [];
$success = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // اعتبارسنجی توکن CSRF
    $csrf_token = $_POST['csrf_token'] ?? '';
    if (!verify_csrf_token($csrf_token)) {
        $errors[] = 'توکن امنیتی معتبر نیست. لطفا دوباره تلاش کنید.';
    } else {
        $first_name = trim($_POST['first_name'] ?? '');
        $last_name = trim($_POST['last_name'] ?? '');
        $phone = trim($_POST['phone'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $postal_code = trim($_POST['postal_code'] ?? '');
        $city = trim($_POST['city'] ?? '');
        $street = trim($_POST['street'] ?? '');
        $house_number = trim($_POST['house_number'] ?? '');
        $additional_address = trim($_POST['additional_address'] ?? '');

        // اعتبارسنجی
        if (empty($first_name) || empty($last_name) || empty($phone) || empty($email)) {
            $errors[] = 'پر کردن تمامی فیلدهای اطلاعات فردی الزامی است.';
        }
        if (!validate_email($email)) {
            $errors[] = 'ایمیل وارد شده نامعتبر است.';
        }
        if (empty($postal_code) || empty($city) || empty($street) || empty($house_number)) {
            $errors[] = 'پر کردن تمامی فیلدهای آدرس پستی الزامی است.';
        }

        if (empty($errors)) {
            if (!$user_id) {
                // جریان مهمان: ذخیره اطلاعات در سشن و هدایت به ثبت نام
                $_SESSION['pending_service_request'] = [
                    'service_type' => 'eyetest',
                    'first_name' => $first_name,
                    'last_name' => $last_name,
                    'phone' => $phone,
                    'email' => $email,
                    'postal_code' => $postal_code,
                    'city' => $city,
                    'street' => $street,
                    'house_number' => $house_number,
                    'additional_address' => $additional_address
                ];

                $_SESSION['info_message'] = 'درخواست نوبت شما موقتاً ذخیره شد. این خدمت مخصوص اعضای VIP است، لطفاً ابتدا ثبت‌نام کرده و اشتراک VIP تهیه کنید.';
                header("Location: register.php");
                exit();
            } else {
                // کاربر لاگین است، بررسی اشتراک فعال
                if (!$has_active_sub) {
                    // ذخیره درخواست در سشن جهت فعال‌سازی پس از خرید اشتراک
                    $_SESSION['pending_service_request'] = [
                        'service_type' => 'eyetest',
                        'first_name' => $first_name,
                        'last_name' => $last_name,
                        'phone' => $phone,
                        'email' => $email,
                        'postal_code' => $postal_code,
                        'city' => $city,
                        'street' => $street,
                        'house_number' => $house_number,
                        'additional_address' => $additional_address
                    ];
                    
                    $_SESSION['info_message'] = 'این خدمت رایگان فقط برای اعضای VIP فعال است. لطفاً ابتدا اشتراک خود را فعال کنید.';
                    header("Location: admin/subscription.php");
                    exit();
                } else {
                    // ثبت درخواست در دیتابیس
                    try {
                        $stmt = $pdo->prepare("
                            INSERT INTO eye_test_appointment_requests 
                            (user_id, first_name, last_name, phone, email, postal_code, city, street, house_number, additional_address, status, created_at, updated_at) 
                            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'pending', NOW(), NOW())
                        ");
                        $stmt->execute([
                            $user_id,
                            $first_name,
                            $last_name,
                            $phone,
                            $email,
                            $postal_code,
                            $city,
                            $street,
                            $house_number,
                            $additional_address
                        ]);

                        $success_req_id = $pdo->lastInsertId();
                        $success = true;

                        // ارسال پیام به تلگرام مدیر
                        $admin_msg = "👁️ <b>درخواست جدید نوبت تست چشم (رایگان VIP)</b>\n\n";
                        $admin_msg .= "👤 نام کاربر: {$first_name} {$last_name}\n";
                        $admin_msg .= "📞 تلفن: {$phone}\n";
                        $admin_msg .= "📧 ایمیل: {$email}\n";
                        $admin_msg .= "🔗 لینک پنل ادمین: https://farsifahr.com/panel/eye-test-appointment-requests/{$success_req_id}/edit\n";
                        send_telegram_admin_message($admin_msg);

                    } catch (Exception $e) {
                        $errors[] = 'خطا در ثبت درخواست: ' . $e->getMessage();
                    }
                }
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($setting['title']) ?></title>
    <!-- CSS Assets -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="assets/css/vendor/bootstrap.min.rtl.css" rel="stylesheet">
    <link href="assets/css/vendor/fontawesome.css" rel="stylesheet">
    <link href="assets/css/font-ir.css" rel="stylesheet">
    <link href="assets/css/style.rtl.css" rel="stylesheet">
    <link href="assets/css/landing-custom.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Rubik:wght@300;400;500;700&display=swap" rel="stylesheet">
    
    <style>
        body {
            background-color: #0b0f19;
            color: #f3f4f6;
            font-family: 'IRANSans', 'Rubik', sans-serif;
        }
        .premium-card {
            background: rgba(17, 24, 39, 0.7);
            backdrop-filter: blur(16px);
            border: 1px solid rgba(255, 255, 255, 0.08);
            border-radius: 20px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.5);
        }
        .form-control {
            background-color: rgba(31, 41, 55, 0.6);
            border: 1px solid rgba(255, 255, 255, 0.1);
            color: #f3f4f6;
            border-radius: 10px;
            padding: 12px;
            transition: all 0.3s;
        }
        .form-control:focus {
            background-color: rgba(31, 41, 55, 0.9);
            border-color: #6366f1;
            box-shadow: 0 0 10px rgba(99, 102, 241, 0.4);
            color: #fff;
        }
        .form-control::placeholder {
            color: #9ca3af;
        }
        .form-label {
            font-weight: 500;
            color: #d1d5db;
        }
        .btn-premium {
            background: linear-gradient(135deg, #6366f1, #4f46e5);
            border: none;
            padding: 12px 24px;
            border-radius: 10px;
            font-weight: 600;
            transition: all 0.3s;
        }
        .btn-premium:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(99, 102, 241, 0.4);
        }
    </style>
</head>
<body>

<div class="container py-5 my-5">
    <div class="row justify-content-center">
        <div class="col-lg-8 col-md-10">
            <div class="text-center mb-5">
                <a href="index.php"><img src="assets/images/logo/logoAsset%201.svg" alt="farsifahr" class="mb-4" style="height: 60px;"></a>
                <h1 class="fw-bold text-gradient mb-3"><?= htmlspecialchars($setting['title']) ?></h1>
                <p class="text-muted fs-5"><?= htmlspecialchars($setting['description']) ?></p>
                <div class="mt-3">
                    <span class="badge bg-success p-2 fs-6">هزینه: رایگان (مخصوص کاربران دارای اشتراک)</span>
                </div>
            </div>

            <?php if (!empty($errors)): ?>
                <div class="alert alert-danger border-0 shadow-sm mb-4" style="background-color: rgba(220, 38, 38, 0.2); color: #fca5a5; border-radius: 12px;">
                    <ul class="mb-0">
                        <?php foreach ($errors as $error): ?>
                            <li><?= htmlspecialchars($error) ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>

            <?php if ($success): ?>
                <div class="premium-card p-5 text-center text-white">
                    <div class="mb-4">
                        <i class="fa-solid fa-circle-check text-success" style="font-size: 80px;"></i>
                    </div>
                    <h3 class="fw-bold mb-4">درخواست نوبت شما با موفقیت ثبت شد</h3>
                    <p class="fs-5 mb-4 text-muted">
                        پشتیبانی سایت به زودی برای هماهنگی و ارسال تاییدیه نوبت تست چشم با شما تماس خواهد گرفت.
                    </p>
                    <div class="mt-4">
                        <a href="admin/index.php" class="btn btn-premium px-5 py-3 text-white">ورود به داشبورد کاربری</a>
                    </div>
                </div>
            <?php else: ?>
                <div class="premium-card p-4 p-md-5">
                    <?php if ($user_id && !$has_active_sub): ?>
                        <div class="alert alert-warning text-center border-0 p-4 mb-4" style="background-color: rgba(245, 158, 11, 0.15); color: #fcd34d; border-radius: 15px;">
                            <i class="fa-solid fa-triangle-exclamation fs-3 mb-2"></i>
                            <h5 class="fw-bold">شما اشتراک VIP فعال ندارید</h5>
                            <p class="mb-3 small">این خدمت به صورت رایگان فقط برای کاربران دارای اشتراک VIP معتبر در دسترس است.</p>
                            <a href="admin/subscription.php" class="btn btn-warning px-4 py-2 fw-semibold" style="color: #0b0f19;">خرید و فعال‌سازی اشتراک VIP</a>
                        </div>
                    <?php endif; ?>

                    <form method="POST">
                        <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">

                        <h4 class="fw-bold mb-4 text-primary" style="color: #818cf8 !important;"><i class="fa-solid fa-user me-2"></i> اطلاعات فردی</h4>
                        <div class="row g-3 mb-4">
                            <div class="col-md-6">
                                <label class="form-label">نام <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" name="first_name" required value="<?= htmlspecialchars($user_info['name'] ?? '') ?>" placeholder="مثال: علی">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">نام خانوادگی <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" name="last_name" required placeholder="مثال: رضایی">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">شماره تماس <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" name="phone" required placeholder="مثال: 09123456789">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">ایمیل <span class="text-danger">*</span></label>
                                <input type="email" class="form-control" name="email" required value="<?= htmlspecialchars($user_info['email'] ?? '') ?>" placeholder="example@email.com">
                            </div>
                        </div>

                        <hr class="my-4" style="border-color: rgba(255,255,255,0.1);">

                        <h4 class="fw-bold mb-4 text-primary" style="color: #818cf8 !important;"><i class="fa-solid fa-map-location-dot me-2"></i> آدرس محل سکونت</h4>
                        <div class="row g-3 mb-4">
                            <div class="col-md-4">
                                <label class="form-label">کد پستی <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" name="postal_code" required placeholder="مثال: 80331">
                            </div>
                            <div class="col-md-8">
                                <label class="form-label">شهر <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" name="city" required placeholder="مثال: مونیخ (München)">
                            </div>
                            <div class="col-md-8">
                                <label class="form-label">خیابان <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" name="street" required placeholder="مثال: Marienplatz">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">پلاک <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" name="house_number" required placeholder="مثال: 12">
                            </div>
                            <div class="col-12">
                                <label class="form-label">جزئیات بیشتر آدرس</label>
                                <input type="text" class="form-control" name="additional_address" placeholder="مثال: c/o Müller, App. 4">
                            </div>
                        </div>

                        <button type="submit" class="btn btn-primary btn-premium w-100 mt-4 text-white">
                            <i class="fa-solid fa-paper-plane me-2"></i> ثبت نهایی درخواست و دریافت نوبت
                        </button>
                    </form>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- JS Assets -->
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</body>
</html>
