<?php
// service-translation.php
require_once __DIR__ . '/incloud/functions.php';
require_once __DIR__ . '/incloud/subscription-functions.php';

$service_key = 'translation';
$stmt = $pdo->prepare("SELECT * FROM service_settings WHERE service_key = ? AND is_active = 1 LIMIT 1");
$stmt->execute([$service_key]);
$setting = $stmt->fetch();

if (!$setting) {
    header("Location: index.php");
    exit();
}

$user_id = $_SESSION['user_id'] ?? null;
$user_info = null;
if ($user_id) {
    $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->execute([$user_id]);
    $user_info = $stmt->fetch();
}

$errors = [];
$success = false;
$success_req_id = null;

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
        $payment_contact_method = trim($_POST['payment_contact_method'] ?? 'whatsapp');

        // اعتبارسنجی اطلاعات فردی و آدرس
        if (empty($first_name) || empty($last_name) || empty($phone) || empty($email)) {
            $errors[] = 'پر کردن تمامی فیلدهای اطلاعات فردی الزامی است.';
        }
        if (!validate_email($email)) {
            $errors[] = 'ایمیل وارد شده نامعتبر است.';
        }
        if (empty($postal_code) || empty($city) || empty($street) || empty($house_number)) {
            $errors[] = 'پر کردن تمامی فیلدهای آدرس پستی الزامی است.';
        }

        // اعتبارسنجی آپلود فایل‌ها
        $front_image_path = '';
        $back_image_path = '';

        $allowed_exts = ['jpg', 'jpeg', 'png', 'pdf'];
        $max_size = 5 * 1024 * 1024; // 5MB

        // بررسی فایل روی گواهینامه
        if (!isset($_FILES['front_image']) || $_FILES['front_image']['error'] !== UPLOAD_ERR_OK) {
            $errors[] = 'آپلود تصویر روی گواهینامه الزامی است.';
        } else {
            $file = $_FILES['front_image'];
            $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
            if (!in_array($ext, $allowed_exts)) {
                $errors[] = 'فرمت تصویر روی گواهینامه غیرمجاز است. فرمت‌های مجاز: jpg, jpeg, png, pdf';
            }
            if ($file['size'] > $max_size) {
                $errors[] = 'حجم تصویر روی گواهینامه نمی‌تواند بیشتر از ۵ مگابایت باشد.';
            }
        }

        // بررسی فایل پشت گواهینامه
        if (!isset($_FILES['back_image']) || $_FILES['back_image']['error'] !== UPLOAD_ERR_OK) {
            $errors[] = 'آپلود تصویر پشت گواهینامه الزامی است.';
        } else {
            $file = $_FILES['back_image'];
            $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
            if (!in_array($ext, $allowed_exts)) {
                $errors[] = 'فرمت تصویر پشت گواهینامه غیرمجاز است. فرمت‌های مجاز: jpg, jpeg, png, pdf';
            }
            if ($file['size'] > $max_size) {
                $errors[] = 'حجم تصویر پشت گواهینامه نمی‌تواند بیشتر از ۵ مگابایت باشد.';
            }
        }

        if (empty($errors)) {
            // ذخیره فایل‌ها در دیسک امن
            $upload_dir = __DIR__ . '/miad/storage/app/licenses/';
            if (!is_dir($upload_dir)) {
                mkdir($upload_dir, 0755, true);
            }

            // روی گواهینامه
            $front_file = $_FILES['front_image'];
            $front_ext = strtolower(pathinfo($front_file['name'], PATHINFO_EXTENSION));
            $front_filename = 'front_' . uniqid() . '.' . $front_ext;
            $front_dest = $upload_dir . $front_filename;

            // پشت گواهینامه
            $back_file = $_FILES['back_image'];
            $back_ext = strtolower(pathinfo($back_file['name'], PATHINFO_EXTENSION));
            $back_filename = 'back_' . uniqid() . '.' . $back_ext;
            $back_dest = $upload_dir . $back_filename;

            if (move_uploaded_file($front_file['tmp_name'], $front_dest) && move_uploaded_file($back_file['tmp_name'], $back_dest)) {
                chmod($front_dest, 0644);
                chmod($back_dest, 0644);

                $front_db_path = 'licenses/' . $front_filename;
                $back_db_path = 'licenses/' . $back_filename;

                if (!$user_id) {
                    // جریان مهمان: ذخیره اطلاعات در سشن و هدایت به ثبت نام
                    $_SESSION['pending_service_request'] = [
                        'service_type' => 'translation',
                        'first_name' => $first_name,
                        'last_name' => $last_name,
                        'phone' => $phone,
                        'email' => $email,
                        'postal_code' => $postal_code,
                        'city' => $city,
                        'street' => $street,
                        'house_number' => $house_number,
                        'additional_address' => $additional_address,
                        'front_image_path' => $front_db_path,
                        'back_image_path' => $back_db_path,
                        'price' => $setting['price'],
                        'payment_contact_method' => $payment_contact_method
                    ];

                    $_SESSION['info_message'] = 'درخواست شما موقتاً ذخیره شد. برای نهایی‌سازی درخواست، لطفا ابتدا در سایت عضو شده یا وارد حساب کاربری خود شوید.';
                    header("Location: register.php");
                    exit();
                } else {
                    // کاربر لاگین است، ثبت مستقیم درخواست
                    try {
                        $stmt = $pdo->prepare("
                            INSERT INTO license_translation_requests 
                            (user_id, first_name, last_name, phone, email, postal_code, city, street, house_number, additional_address, front_image_path, back_image_path, status, price, payment_contact_method, created_at, updated_at) 
                            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'pending_payment', ?, ?, NOW(), NOW())
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
                            $additional_address,
                            $front_db_path,
                            $back_db_path,
                            $setting['price'],
                            $payment_contact_method
                        ]);

                        $success_req_id = $pdo->lastInsertId();
                        $success = true;

                        // ارسال پیام به تلگرام مدیر
                        $admin_msg = "📥 <b>درخواست جدید ترجمه گواهینامه</b>\n\n";
                        $admin_msg .= "👤 نام کاربر: {$first_name} {$last_name}\n";
                        $admin_msg .= "📞 تلفن: {$phone}\n";
                        $admin_msg .= "📧 ایمیل: {$email}\n";
                        $admin_msg .= "💰 مبلغ: {$setting['price']} یورو\n";
                        $admin_msg .= "🔗 لینک پنل ادمین: https://farsifahr.com/panel/license-translation-requests/{$success_req_id}/edit\n";
                        send_telegram_admin_message($admin_msg);
                        
                    } catch (Exception $e) {
                        $errors[] = 'خطا در ثبت درخواست در دیتابیس: ' . $e->getMessage();
                    }
                }
            } else {
                $errors[] = 'خطا در بارگذاری مدارک. لطفا مجددا تلاش کنید.';
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
        .file-upload-wrapper {
            position: relative;
            border: 2px dashed rgba(255, 255, 255, 0.15);
            border-radius: 12px;
            padding: 20px;
            text-align: center;
            background: rgba(31, 41, 55, 0.3);
            cursor: pointer;
            transition: border-color 0.3s;
        }
        .file-upload-wrapper:hover {
            border-color: #6366f1;
        }
        .file-upload-input {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            opacity: 0;
            cursor: pointer;
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
                    <h3 class="fw-bold mb-4">درخواست شما با موفقیت ثبت شد</h3>
                    <p class="fs-5 mb-4 text-muted">
                        هزینه ترجمه گواهینامه <strong class="text-white"><?= number_format($setting['price']) ?> یورو</strong> است. جهت تکمیل این مرحله و هماهنگی پرداخت، لطفاً از طریق دکمه‌های زیر با پشتیبانی در تماس باشید.
                    </p>
                    
                    <div class="alert alert-info border-0 py-3 mb-4" style="background-color: rgba(3, 195, 236, 0.1); color: #9be9fd; border-radius: 12px; font-size: 0.95rem;">
                        <i class="fa-solid fa-info-circle me-2"></i> شماره شناسه سفارش شما: <strong><?= $success_req_id ?></strong>
                    </div>

                    <div class="d-flex gap-3 flex-wrap justify-content-center">
                        <?php 
                        $whatsapp_base = (defined('WHATSAPP_URL') && WHATSAPP_URL !== '#') ? rtrim(str_replace('-', '', WHATSAPP_URL), '/') : 'https://wa.me/989177876760';
                        $telegram_support = (defined('TELEGRAM_SUPPORT_URL') && TELEGRAM_SUPPORT_URL !== '#') ? TELEGRAM_SUPPORT_URL : 'https://t.me/farsifahr';

                        $wa_msg = "سلام، من درخواست ترجمه گواهینامه با مشخصات {$first_name} {$last_name} را با شناسه سفارش {$success_req_id} ثبت کردم.\nلطفا فعال کنید.";
                        ?>
                        <a href="<?= $whatsapp_base ?>?text=<?= urlencode($wa_msg) ?>" target="_blank" class="btn btn-success btn-lg px-4 shadow py-3" style="border-radius: 10px;">
                            <i class="fa-brands fa-whatsapp me-2 fs-4"></i> اطلاع‌رسانی در واتس‌اپ
                        </a>
                        <a href="<?= $telegram_support ?>" target="_blank" class="btn btn-info btn-lg px-4 shadow py-3 text-white" style="border-radius: 10px; background-color: #03c3ec; border: none;">
                            <i class="fa-brands fa-telegram me-2 fs-4"></i> اطلاع‌رسانی در تلگرام
                        </a>
                    </div>
                    <div class="mt-5">
                        <a href="admin/index.php" class="btn btn-outline-light px-4" style="border-radius: 10px;">ورود به داشبورد کاربری</a>
                    </div>
                </div>
            <?php else: ?>
                <div class="premium-card p-4 p-md-5">
                    <form method="POST" enctype="multipart/form-data">
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
                                <label class="form-label">شماره تماس (ترجیحاً دارای واتس‌اپ) <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" name="phone" required placeholder="مثال: 09123456789">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">ایمیل <span class="text-danger">*</span></label>
                                <input type="email" class="form-control" name="email" required value="<?= htmlspecialchars($user_info['email'] ?? '') ?>" placeholder="example@email.com">
                            </div>
                        </div>

                        <hr class="my-4" style="border-color: rgba(255,255,255,0.1);">

                        <h4 class="fw-bold mb-4 text-primary" style="color: #818cf8 !important;"><i class="fa-solid fa-map-location-dot me-2"></i> آدرس پستی (در آلمان)</h4>
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
                                <label class="form-label">جزئیات بیشتر آدرس (نام روی آیفون/صندوق پستی، زنگ و ...)</label>
                                <input type="text" class="form-control" name="additional_address" placeholder="مثال: c/o Müller, App. 4">
                            </div>
                        </div>

                        <hr class="my-4" style="border-color: rgba(255,255,255,0.1);">

                        <h4 class="fw-bold mb-4 text-primary" style="color: #818cf8 !important;"><i class="fa-solid fa-file-arrow-up me-2"></i> بارگذاری مدارک</h4>
                        <div class="row g-3 mb-4">
                            <div class="col-md-6">
                                <label class="form-label mb-2">تصویر روی گواهینامه <span class="text-danger">*</span></label>
                                <div class="file-upload-wrapper" id="front-wrapper">
                                    <i class="fa-solid fa-id-card mb-2" style="font-size: 32px; color: #818cf8;"></i>
                                    <p class="mb-0 text-muted" id="front-text">انتخاب تصویر روی گواهینامه</p>
                                    <small class="text-muted d-block mt-1">فرمت‌های مجاز: JPG, PNG, PDF</small>
                                    <input type="file" class="file-upload-input" name="front_image" id="front_image" required accept="image/*,.pdf" onchange="updateFileName('front_image', 'front-text', 'front-wrapper')">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label mb-2">تصویر پشت گواهینامه <span class="text-danger">*</span></label>
                                <div class="file-upload-wrapper" id="back-wrapper">
                                    <i class="fa-solid fa-id-card mb-2" style="font-size: 32px; color: #818cf8;"></i>
                                    <p class="mb-0 text-muted" id="back-text">انتخاب تصویر پشت گواهینامه</p>
                                    <small class="text-muted d-block mt-1">فرمت‌های مجاز: JPG, PNG, PDF</small>
                                    <input type="file" class="file-upload-input" name="back_image" id="back_image" required accept="image/*,.pdf" onchange="updateFileName('back_image', 'back-text', 'back-wrapper')">
                                </div>
                            </div>
                        </div>

                        <hr class="my-4" style="border-color: rgba(255,255,255,0.1);">

                        <h4 class="fw-bold mb-4 text-primary" style="color: #818cf8 !important;"><i class="fa-solid fa-credit-card me-2"></i> اطلاعات پرداخت و تماس</h4>
                        <div class="row g-3 mb-4">
                            <div class="col-md-6">
                                <label class="form-label mb-2">روش ترجیحی تماس جهت نهایی‌سازی پرداخت</label>
                                <select class="form-control" name="payment_contact_method">
                                    <option value="whatsapp">واتس‌اپ</option>
                                    <option value="telegram">تلگرام</option>
                                </select>
                            </div>
                            <div class="col-md-6 d-flex align-items-center justify-content-md-end pt-md-4">
                                <div class="text-md-end w-100">
                                    <h5 class="mb-1 text-muted">مبلغ کل هزینه:</h5>
                                    <h3 class="fw-bold text-white"><?= number_format($setting['price']) ?> یورو</h3>
                                </div>
                            </div>
                        </div>

                        <button type="submit" class="btn btn-primary btn-premium w-100 mt-4 text-white">
                            <i class="fa-solid fa-paper-plane me-2"></i> ثبت نهایی درخواست و ادامه
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
<script>
    function updateFileName(inputId, textId, wrapperId) {
        const input = document.getElementById(inputId);
        const textElement = document.getElementById(textId);
        const wrapper = document.getElementById(wrapperId);
        
        if (input.files && input.files.length > 0) {
            textElement.textContent = input.files[0].name;
            wrapper.style.borderColor = '#10b981';
            textElement.style.color = '#10b981';
        } else {
            textElement.textContent = inputId === 'front_image' ? 'انتخاب تصویر روی گواهینامه' : 'انتخاب تصویر پشت گواهینامه';
            wrapper.style.borderColor = 'rgba(255, 255, 255, 0.15)';
            textElement.style.color = '#9ca3af';
        }
    }
</script>
</body>
</html>
