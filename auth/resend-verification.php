<?php
// resend-verification.php
require_once __DIR__ . '/../incloud/functions.php';
require_once __DIR__ . '/../incloud/mail-functions.php';

header('Content-Type: application/json; charset=utf-8');

// بررسی درخواست POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'متد غیرمجاز']);
    exit;
}

$email = trim($_POST['email'] ?? '');

// اعتبارسنجی ایمیل
if (empty($email) || !validate_email($email)) {
    echo json_encode(['success' => false, 'message' => 'لطفا ایمیل معتبر وارد کنید']);
    exit;
}

// جستجوی کاربر
$stmt = $pdo->prepare("
    SELECT id, name, email_verified, verification_token 
    FROM users 
    WHERE email = ?
");
$stmt->execute([$email]);
$user = $stmt->fetch();

if (!$user) {
    // برای جلوگیری از افشای اطلاعات
    echo json_encode([
        'success' => true, 
        'message' => 'در صورت وجود حساب کاربری، ایمیل تایید ارسال شد'
    ]);
    exit;
}

// بررسی وضعیت تایید
if ($user['email_verified'] == 1) {
    echo json_encode([
        'success' => false, 
        'message' => 'حساب کاربری شما قبلاً تایید شده است'
    ]);
    exit;
}

// بررسی محدودیت زمانی (حداکثر یک ایمیل در 5 دقیقه)
$stmt = $pdo->prepare("
    SELECT COUNT(*) as count 
    FROM user_logs 
    WHERE user_id = ? 
    AND action = 'resend_verification' 
    AND created_at > DATE_SUB(NOW(), INTERVAL 5 MINUTE)
");
$stmt->execute([$user['id']]);
$recent = $stmt->fetch();

if ($recent['count'] > 0) {
    echo json_encode([
        'success' => false, 
        'message' => 'لطفا 5 دقیقه صبر کنید و سپس دوباره تلاش کنید'
    ]);
    exit;
}

// تولید توکن جدید
$new_token = generate_token();

// بروزرسانی توکن
$stmt = $pdo->prepare("
    UPDATE users 
    SET verification_token = ? 
    WHERE id = ?
");
$stmt->execute([$new_token, $user['id']]);

// ارسال ایمیل
$result = send_verification_email($email, $user['name'], $new_token);

// ثبت لاگ
log_user_action($user['id'], $email, 'resend_verification', 'success', $pdo);

if ($result['success']) {
    echo json_encode([
        'success' => true, 
        'message' => 'ایمیل تایید جدید ارسال شد. لطفا صندوق ورودی خود را بررسی کنید'
    ]);
} else {
    echo json_encode([
        'success' => false, 
        'message' => 'خطا در ارسال ایمیل. لطفا بعداً تلاش کنید'
    ]);
}