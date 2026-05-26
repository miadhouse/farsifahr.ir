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
                $user['google_id'] = $google_id;
            }
        } else {
            // تولید رمز عبور تصادفی قوی برای امنیت بیشتر (اگر بعدا بخواهند با ایمیل وارد شوند)
            $random_password = bin2hex(random_bytes(16));
            $hashed_password = hash_password($random_password);
            $referral_code = generate_referral_code($pdo);
            
            // ثبت کاربر جدید
            $stmt = $pdo->prepare("
                INSERT INTO users (email, name, google_id, password, role, email_verified, referral_code) 
                VALUES (?, ?, ?, ?, 'user', 1, ?)
            ");
            $stmt->execute([$email, $name, $google_id, $hashed_password, $referral_code]);
            
            $user_id = $pdo->lastInsertId();
            
            // دریافت اطلاعات کامل کاربر تازه ثبت‌نام شده
            $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
            $stmt->execute([$user_id]);
            $user = $stmt->fetch();

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
        $_SESSION['name'] = $user['name'];
        $_SESSION['role'] = $user['role'];
        $_SESSION['logged_in'] = true;
        
        // ذخیره سشن در دیتابیس
        save_session($user['id'], $pdo);
        
        // ثبت لاگ
        log_user_action($user['id'], $email, 'google_login', 'success', $pdo);
        
        // اطمینان از ذخیره سشن قبل از هدایت
        session_write_close();
        
        // هدایت به پنل مدیریت با پارامتر جلوگیری از کش
        header("Location: " . SITE_URL . "admin/?login=success&t=" . time());
        exit();
        
    } catch (Exception $e) {
        // خطا در ورود
        error_log("Google Login Error: " . $e->getMessage());
        $_SESSION['error'] = 'خطا در ورود با گوگل. لطفا دوباره تلاش کنید.';
        session_write_close();
        header("Location: " . SITE_URL);
        exit();
    }
} else {
    // هدایت به صفحه اصلی در صورت عدم وجود کد
    session_write_close();
    header("Location: " . SITE_URL);
    exit();
}