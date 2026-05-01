<?php
header('Content-Type: application/json');
require_once('../config/config.php');
require_once('../incloud/subscription-functions.php');

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

// دریافت و اعتبارسنجی داده‌ها
$plan_id = isset($_POST['plan_id']) && is_numeric($_POST['plan_id']) ? intval($_POST['plan_id']) : 0;
$duration = isset($_POST['duration']) ? trim($_POST['duration']) : '';

// اعتبارسنجی plan_id
if ($plan_id <= 0) {
    echo json_encode(['success' => false, 'message' => 'پلن انتخاب شده نامعتبر است.']);
    exit;
}

// دریافت اطلاعات پلن
$plan = get_subscription_plan($plan_id, $pdo);
if (!$plan) {
    echo json_encode(['success' => false, 'message' => 'پلن مورد نظر یافت نشد.']);
    exit;
}

// اعتبارسنجی duration
if ($plan['slug'] !== 'free') {
    if (empty($duration) || !validate_duration($duration)) {
        echo json_encode(['success' => false, 'message' => 'دوره زمانی انتخاب شده نامعتبر است.']);
        exit;
    }
    
    // بررسی قیمت
    $amount = get_plan_price_by_duration($plan, $duration);
    if ($amount <= 0) {
        echo json_encode(['success' => false, 'message' => 'این دوره زمانی برای پلن انتخابی فعال نیست.']);
        exit;
    }
} else {
    $amount = 0;
}

// بررسی اشتراک معلق قبلی
$pending_subscription = get_user_pending_subscription($user_id, $pdo);
if ($pending_subscription) {
    echo json_encode(['success' => false, 'message' => 'شما یک درخواست اشتراک معلق دارید. لطفا ابتدا آن را پیگیری یا لغو کنید.']);
    exit;
}

// بررسی اشتراک فعال قبلی (VIP)
if (is_user_vip($user_id, $pdo)) {
    echo json_encode(['success' => false, 'message' => 'شما در حال حاضر یک اشتراک VIP فعال دارید و نمی‌توانید اشتراک دیگری خریداری کنید.']);
    exit;
}

// ثبت درخواست
try {
    $pdo->beginTransaction();
    
    // لغو تمام اشتراک‌های قبلی فعال و معلق (اختیاری - بر اساس منطق پروژه شما)
    // در اینجا ما فقط معلق‌های قبلی را چک کردیم. اگر کاربر بخواهد ارتقا دهد، سیستم قبلاً معلق‌ها را چک کرده است.
    
    $duration_days = get_duration_days($duration);
    $expires_at = null;
    
    if ($duration_days > 0) {
        $expires_at = date('Y-m-d H:i:s', strtotime("+{$duration_days} days"));
    }
    
    // ایجاد رکورد اشتراک با وضعیت pending
    $stmt = $pdo->prepare("
        INSERT INTO user_subscriptions 
        (user_id, plan_id, expires_at, duration_days, amount_paid, status, created_at) 
        VALUES (?, ?, ?, ?, ?, 'pending', NOW())
    ");
    
    $stmt->execute([
        $user_id,
        $plan_id,
        $expires_at,
        $duration_days,
        $amount
    ]);
    
    $pdo->commit();
    echo json_encode(['success' => true, 'message' => 'درخواست اشتراک شما با موفقیت ثبت شد. لطفا منتظر تایید پشتیبانی باشید.']);
    exit;
    
} catch (Exception $e) {
    $pdo->rollBack();
    error_log("Error creating subscription via AJAX: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'خطا در ثبت درخواست. لطفا دوباره تلاش کنید.']);
    exit;
}