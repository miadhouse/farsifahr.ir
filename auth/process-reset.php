<?php
// process-reset.php
require_once __DIR__ . '/../incloud/functions.php';
require_once __DIR__ . '/../incloud/mail-functions.php';

header('Content-Type: application/json; charset=utf-8');

// بررسی درخواست POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'متد غیرمجاز']);
    exit;
}

// بررسی CSRF token
if (!isset($_POST['csrf_token']) || !verify_csrf_token($_POST['csrf_token'])) {
    echo json_encode(['success' => false, 'message' => 'توکن امنیتی نامعتبر است']);
    exit;
}

$token = $_POST['token'] ?? '';
$password = $_POST['password'] ?? '';
$password_confirm = $_POST['password_confirm'] ?? '';

// اعتبارسنجی
if (empty($token) || empty($password) || empty($password_confirm)) {
    echo json_encode(['success' => false, 'message' => 'لطفا تمام فیلدها را پر کنید']);
    exit;
}

// بررسی تطابق رمز عبور
if ($password !== $password_confirm) {
    echo json_encode(['success' => false, 'message' => 'رمز عبور و تکرار آن مطابقت ندارند']);
    exit;
}

// اعتبارسنجی رمز عبور
if (!validate_password($password)) {
    echo json_encode([
        'success' => false, 
        'message' => 'رمز عبور باید حداقل 8 کاراکتر و شامل حروف بزرگ، کوچک و عدد باشد'
    ]);
    exit;
}

// بررسی توکن
$stmt = $pdo->prepare("
    SELECT id, email,name FROM users 
    WHERE reset_token = ? AND reset_expires > NOW()
");
$stmt->execute([$token]);
$user = $stmt->fetch();

if (!$user) {
    echo json_encode(['success' => false, 'message' => 'توکن منقضی شده یا نامعتبر است']);
    exit;
}

// تغییر رمز عبور
try {
    $hashed_password = hash_password($password);
    
    $stmt = $pdo->prepare("
        UPDATE users 
        SET password = ?, reset_token = NULL, reset_expires = NULL 
        WHERE id = ?
    ");
    $stmt->execute([$hashed_password, $user['id']]);
    
    // ثبت لاگ
    log_user_action($user['id'], $user['email'], 'password_reset', 'success', $pdo);
    
    // ارسال ایمیل اطلاع‌رسانی
    $email_body = "
        <h2>رمز عبور شما تغییر کرد</h2>
        <p>رمز عبور حساب کاربری شما با موفقیت تغییر یافت.</p>
        <p>اگر شما این تغییر را انجام نداده‌اید، فورا با پشتیبانی تماس بگیرید.</p>
    ";
    send_password_changed_email($user['email'], $user['name'], );
    
    echo json_encode([
        'success' => true, 
        'message' => 'رمز عبور شما با موفقیت تغییر یافت'
    ]);
    
} catch (PDOException $e) {
    log_user_action($user['id'], $user['email'], 'password_reset', 'failed', $pdo);
    echo json_encode(['success' => false, 'message' => 'خطا در تغییر رمز عبور']);
}