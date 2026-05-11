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
            // تولید رمز عبور تصادفی قوی برای امنیت بیشتر (اگر بعدا بخواهند با ایمیل وارد شوند)
            $random_password = bin2hex(random_bytes(16));
            $hashed_password = hash_password($random_password);
            
            // ثبت کاربر جدید
            $stmt = $pdo->prepare("
                INSERT INTO users (email, name, google_id, password, role, email_verified) 
                VALUES (?, ?, ?, ?, 'user', 1)
            ");
            $stmt->execute([$email, $name, $google_id, $hashed_password]);
            
            $user = [
                'id' => $pdo->lastInsertId(),
                'email' => $email,
                'name' => $name,
                'role' => 'user'
            ];

            // ارسال پیام به مدیر در تلگرام
            $telegram_message = "🆕 <b>ثبت نام جدید در سایت (گوگل)</b>\n\n";
            $telegram_message .= "👤 نام: {$name}\n";
            $telegram_message .= "📧 ایمیل: {$email}\n";
            $telegram_message .= "🕒 زمان: " . date('Y-m-d H:i:s');
            send_telegram_admin_message($telegram_message);
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
        
        // هدایت به پنل مدیریت
        header("Location: " . SITE_URL . "admin/");
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