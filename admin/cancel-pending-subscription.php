<?php
session_start();
require_once('../config/config.php');
require_once('../incloud/subscription-functions.php');

// بررسی ورود کاربر
if (!isset($_SESSION['user_id'])) {
    header('Location: ../login.php');
    exit;
}

// بررسی درخواست POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: subscription.php');
    exit;
}

$user_id = $_SESSION['user_id'];

try {
    $pdo->beginTransaction();
    
    // دریافت اشتراک معلق
    $pending_subscription = get_user_pending_subscription($user_id, $pdo);
    
    if (!$pending_subscription) {
        $_SESSION['error_message'] = 'هیچ اشتراک معلقی یافت نشد.';
        header('Location: subscription.php');
        exit;
    }
    
    // تغییر وضعیت به لغو شده
    $stmt = $pdo->prepare("
        UPDATE user_subscriptions 
        SET status = 'cancelled', updated_at = NOW() 
        WHERE user_id = ? AND status = 'pending'
    ");
    
    $stmt->execute([$user_id]);
    
    $pdo->commit();
    
    $_SESSION['success_message'] = 'درخواست اشتراک معلق شما با موفقیت لغو شد.';
    header('Location: subscription.php');
    exit;
    
} catch (Exception $e) {
    $pdo->rollBack();
    error_log("Error cancelling pending subscription: " . $e->getMessage());
    $_SESSION['error_message'] = 'خطا در لغو درخواست. لطفا دوباره تلاش کنید.';
    header('Location: subscription.php');
    exit;
}