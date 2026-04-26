<?php
header('Content-Type: application/json');
require_once('../config/config.php');
require_once('../incloud/functions.php');

// بررسی ورود کاربر
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'لطفا ابتدا وارد حساب کاربری خود شوید.']);
    exit;
}

$user_id = $_SESSION['user_id'];

// بررسی درخواست POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'درخواست نامعتبر است.']);
    exit;
}

// دریافت داده‌ها
$name = trim($_POST['name'] ?? '');
$email = trim($_POST['email'] ?? '');
$phone = trim($_POST['phone'] ?? '');
$new_password = $_POST['newPassword'] ?? '';
$confirm_password = $_POST['confirmPassword'] ?? '';

// اعتبارسنجی
if (empty($name) || empty($email)) {
    echo json_encode(['success' => false, 'message' => 'نام و ایمیل نمی‌توانند خالی باشند.']);
    exit;
}

if (!validate_email($email)) {
    echo json_encode(['success' => false, 'message' => 'فرمت ایمیل معتبر نیست.']);
    exit;
}

// بررسی تکراری نبودن ایمیل برای سایر کاربران
$stmt = $pdo->prepare("SELECT id FROM users WHERE email = ? AND id != ?");
$stmt->execute([$email, $user_id]);
if ($stmt->rowCount() > 0) {
    echo json_encode(['success' => false, 'message' => 'این ایمیل قبلاً توسط کاربر دیگری ثبت شده است.']);
    exit;
}

// اگر رمز عبور وارد شده باشد
$password_update_sql = "";
$params = [$name, $email, $user_id];

// برای هندل کردن فیلد phone (اگر ستون وجود نداشت خطا ندهد)
// در کدنویسی واقعی بهتر است ابتدا وجود ستون چک شود یا ساختار دیتابیس ثابت باشد.
// اینجا فرض می‌کنیم اگر کوئری خطا داد، به خاطر نبودن ستون phone است و آن را حذف می‌کنیم.

try {
    $pdo->beginTransaction();

    // دریافت اطلاعات فعلی برای مقایسه ایمیل
    $stmt = $pdo->prepare("SELECT email FROM users WHERE id = ?");
    $stmt->execute([$user_id]);
    $current_user_data = $stmt->fetch();
    $old_email = $current_user_data['email'];

    $email_changed = ($email !== $old_email);
    $message = "اطلاعات با موفقیت بروزرسانی شد.";

    // آپدیت اطلاعات پایه (بجز ایمیل در صورتی که تغییر کرده باشد)
    $check_phone = $pdo->query("SHOW COLUMNS FROM users LIKE 'phone'");
    $has_phone = $check_phone->rowCount() > 0;

    if ($has_phone) {
        $sql = "UPDATE users SET name = ?, phone = ? WHERE id = ?";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$name, $phone, $user_id]);
    } else {
        $sql = "UPDATE users SET name = ? WHERE id = ?";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$name, $user_id]);
    }

    // اگر ایمیل تغییر کرده باشد
    if ($email_changed) {
        $verification_token = bin2hex(random_bytes(32));
        // ایمیل اصلی را تغییر نمی‌دهیم، فقط در pending_email ذخیره می‌کنیم
        $stmt = $pdo->prepare("UPDATE users SET pending_email = ?, verification_token = ? WHERE id = ?");
        $stmt->execute([$email, $verification_token, $user_id]);
        
        // ارسال ایمیل تایید به ایمیل جدید
        require_once('../incloud/mail-functions.php');
        send_verification_email($email, $name, $verification_token);
        
        $message = "اطلاعات بروز شد. برای تغییر نهایی ایمیل، لطفا لینک تایید ارسال شده به ایمیل جدید را کلیک کنید. تا آن زمان از ایمیل فعلی استفاده خواهد شد.";
    }

    // آپدیت رمز عبور
    if (!empty($new_password)) {
        if (strlen($new_password) < 8) {
            throw new Exception("رمز عبور باید حداقل 8 کاراکتر باشد.");
        }
        if ($new_password !== $confirm_password) {
            throw new Exception("رمز عبور و تکرار آن مطابقت ندارند.");
        }
        
        $hashed_password = hash_password($new_password);
        $stmt = $pdo->prepare("UPDATE users SET password = ? WHERE id = ?");
        $stmt->execute([$hashed_password, $user_id]);
    }

    $pdo->commit();

    // بروزرسانی سشن (نام تغییر می‌کند اما ایمیل تا زمان تایید ثابت می‌ماند)
    $_SESSION['name'] = $name;
    
    // در این حالت نیازی به لاگ‌اوت نیست
    echo json_encode(['success' => true, 'message' => $message, 'email_changed' => false]);
    
} catch (Exception $e) {
    $pdo->rollBack();
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>