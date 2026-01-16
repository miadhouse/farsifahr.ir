<?php
// google-callback.php
require_once '../incloud/functions.php';
require_once '../vendor/autoload.php'; // Google API Client

// تنظیم Google Client
$client = new Google_Client();
$client->setClientId(GOOGLE_CLIENT_ID);
$client->setClientSecret(GOOGLE_CLIENT_SECRET);
$client->setRedirectUri(GOOGLE_REDIRECT_URI);
$client->addScope("email");
$client->addScope("profile");

// دریافت کد از گوگل
if (isset($_GET['code'])) {
    try {
        // تبدیل کد به توکن
        $token = $client->fetchAccessTokenWithAuthCode($_GET['code']);
        $client->setAccessToken($token['access_token']);
        
        // دریافت اطلاعات کاربر از گوگل
        $google_oauth = new Google_Service_Oauth2($client);
        $google_account_info = $google_oauth->userinfo->get();
        
        $google_id = $google_account_info->id;
        $email = $google_account_info->email;
        $name = $google_account_info->name;
        
        // بررسی وجود کاربر
        $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ? OR google_id = ?");
        $stmt->execute([$email, $google_id]);
        $user = $stmt->fetch();
        
        if ($user) {
            // کاربر موجود - بروزرسانی google_id اگر لازم باشد
            if (empty($user['google_id'])) {
                $stmt = $pdo->prepare("UPDATE users SET google_id = ? WHERE id = ?");
                $stmt->execute([$google_id, $user['id']]);
            }
        } else {
            // ثبت کاربر جدید
            $stmt = $pdo->prepare("
                INSERT INTO users (email, name, google_id, email_verified) 
                VALUES (?, ?, ?, 1)
            ");
            $stmt->execute([$email, $name, $google_id]);
            $user = [
                'id' => $pdo->lastInsertId(),
                'email' => $email,
                'name' => $name,
                'role' => 'user'
            ];
        }
        
        // ورود کاربر
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['email'] = $user['email'];
        $_SESSION['name'] = $user['name'] ?? $name;
        $_SESSION['role'] = $user['role'];
        $_SESSION['logged_in'] = true;
        
        // ذخیره سشن
        save_session($user['id'], $pdo);
        
        // ثبت لاگ
        log_user_action($user['id'], $email, 'google_login', 'success', $pdo);
        
        // هدایت به داشبورد
        header("Location: dashboard.php");
        exit();
        
    } catch (Exception $e) {
        // خطا در ورود
        $_SESSION['error'] = 'خطا در ورود با گوگل. لطفا دوباره تلاش کنید.';
        header("Location: index.php");
        exit();
    }
} else {
    // هدایت به صفحه اصلی در صورت عدم وجود کد
    header("Location: index.php");
    exit();
}