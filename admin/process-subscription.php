<?php
require_once('../incloud/functions.php');
require_once('../incloud/subscription-functions.php');

// بررسی ورود کاربر
if (!is_logged_in()) {
    header('Location: /register.php');
    exit;
}

// بررسی درخواست POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: subscription.php');
    exit;
}

$user_id = $_SESSION['user_id'];
$errors = [];
$success = false;

// دریافت و اعتبارسنجی داده‌ها
$plan_id = isset($_POST['plan_id']) && is_numeric($_POST['plan_id']) ? intval($_POST['plan_id']) : 0;
$duration = isset($_POST['duration']) ? trim($_POST['duration']) : '';
$amount = isset($_POST['amount']) && is_numeric($_POST['amount']) ? floatval($_POST['amount']) : 0;

// اعتبارسنجی plan_id
if ($plan_id <= 0) {
    $errors[] = 'پلن انتخاب شده نامعتبر است.';
}

// دریافت اطلاعات پلن
$plan = get_subscription_plan($plan_id, $pdo);
if (!$plan) {
    $errors[] = 'پلن مورد نظر یافت نشد.';
}

// اعتبارسنجی duration
if ($plan && $plan['slug'] !== 'free') {
    if (empty($duration) || !validate_duration($duration)) {
        $errors[] = 'دوره زمانی انتخاب شده نامعتبر است.';
    } else {
        // بررسی قیمت
        $expected_price = get_plan_price_by_duration($plan, $duration);
        if ($expected_price <= 0) {
            $errors[] = 'این دوره زمانی برای پلن انتخابی فعال نیست.';
        } elseif ($amount != $expected_price) {
            $errors[] = 'مبلغ ارسالی با قیمت پلن مطابقت ندارد.';
        }
    }
}

// بررسی اشتراک معلق قبلی
$pending_subscription = get_user_pending_subscription($user_id, $pdo);
if ($pending_subscription) {
    $errors[] = 'شما یک درخواست اشتراک معلق دارید. لطفا ابتدا آن را پیگیری یا لغو کنید.';
}

// اگر خطایی نبود، درخواست را ثبت کن
if (empty($errors)) {
    try {
        $pdo->beginTransaction();
        
        // لغو تمام اشتراک‌های قبلی فعال و معلق
        $stmt = $pdo->prepare("
            UPDATE user_subscriptions 
            SET status = 'cancelled', updated_at = NOW() 
            WHERE user_id = ? AND status IN ('active', 'pending')
        ");
        $stmt->execute([$user_id]);
        
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
        
        $subscription_id = $pdo->lastInsertId();
        
        $pdo->commit();
        $success = true;
        
        // هدایت به صفحه پرداخت یا تایید
        $_SESSION['success_message'] = 'درخواست اشتراک شما با موفقیت ثبت شد. لطفا منتظر تایید پشتیبانی باشید.';
        header('Location: subscription.php?success=1');
        exit;
        
    } catch (Exception $e) {
        $pdo->rollBack();
        error_log("Error creating subscription: " . $e->getMessage());
        $errors[] = 'خطا در ثبت درخواست. لطفا دوباره تلاش کنید.';
    }
}

// اگر خطا داشتیم، به صفحه قبل برگردیم
if (!empty($errors)) {
    $_SESSION['error_messages'] = $errors;
    header('Location: invoice-request.php?plan-id=' . $plan_id . '&duration=' . $duration);
    exit;
}