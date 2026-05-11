<?php
// mail-functions.php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../config/config.php';

/**
 * ارسال ایمیل با PHPMailer
 * 
 * @param string $to آدرس ایمیل گیرنده
 * @param string $subject موضوع ایمیل
 * @param string $body محتوای HTML ایمیل
 * @param string $altBody محتوای متنی (اختیاری)
 * @return array نتیجه ارسال
 */
function send_email_phpmailer($to, $subject, $body, $altBody = '') {
    $mail = new PHPMailer(true);
    
    try {
        // تنظیمات سرور
        $mail->isSMTP();
        $mail->Host       = SMTP_HOST;
        $mail->SMTPAuth   = true;
        $mail->Username   = SMTP_USER;
        $mail->Password   = SMTP_PASS;
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port       = SMTP_PORT;
        
        // تنظیمات encoding برای فارسی
        $mail->CharSet = 'UTF-8';
        $mail->Encoding = 'base64';
        
        // فرستنده و گیرنده
        $mail->setFrom(SMTP_FROM, SITE_NAME);
        $mail->addAddress($to);
        $mail->addReplyTo(SMTP_FROM, SITE_NAME);
        
        // محتوا
        $mail->isHTML(true);
        $mail->Subject = $subject;
        $mail->Body    = $body;
        $mail->AltBody = $altBody ?: strip_tags($body);
        
        $mail->send();
        
        return [
            'success' => true,
            'message' => 'ایمیل با موفقیت ارسال شد'
        ];
        
    } catch (Exception $e) {
        return [
            'success' => false,
            'message' => "خطا در ارسال ایمیل: {$mail->ErrorInfo}"
        ];
    }
}

/**
 * ارسال ایمیل تایید حساب کاربری
 */
function send_verification_email($email, $name, $token) {
    $verify_link = SITE_URL . "auth/verify.php?token=" . $token;
    
    $template = get_email_template('verification', [
        'name' => $name,
        'verify_link' => $verify_link,
        'site_name' => SITE_NAME
    ]);
    
    return send_email_phpmailer(
        $email,
        'فعال‌سازی حساب کاربری - ' . SITE_NAME,
        $template['html'],
        $template['text']
    );
}

/**
 * ارسال ایمیل بازیابی رمز عبور
 */
function send_password_reset_email($email, $name, $token) {
    $reset_link = SITE_URL . "auth/reset-password.php?token=" . $token;
    $expiry_hours = PASSWORD_RESET_EXPIRY / 3600;
    
    $template = get_email_template('password_reset', [
        'name' => $name,
        'reset_link' => $reset_link,
        'expiry_hours' => $expiry_hours,
        'site_name' => SITE_NAME
    ]);
    
    return send_email_phpmailer(
        $email,
        'درخواست تغییر رمز عبور - ' . SITE_NAME,
        $template['html'],
        $template['text']
    );
}

/**
 * ارسال ایمیل اطلاع‌رسانی تغییر رمز
 */
function send_password_changed_email($email, $name) {
    $template = get_email_template('password_changed', [
        'name' => $name,
        'site_name' => SITE_NAME,
        'support_email' => SMTP_FROM
    ]);
    
    return send_email_phpmailer(
        $email,
        'امنیت حساب: رمز عبور تغییر کرد - ' . SITE_NAME,
        $template['html'],
        $template['text']
    );
}

/**
 * ارسال ایمیل خوش‌آمدگویی
 */
function send_welcome_email($email, $name) {
    $template = get_email_template('welcome', [
        'name' => $name,
        'site_name' => SITE_NAME,
        'login_url' => SITE_URL
    ]);
    
    return send_email_phpmailer(
        $email,
        'خوش آمدید به ' . SITE_NAME,
        $template['html'],
        $template['text']
    );
}

/**
 * ارسال ایمیل برنامه مطالعه
 */
function send_study_plan_email($email, $name, $plan_details) {
    $template = get_email_template('study_plan', [
        'name' => $name,
        'estimated_days' => $plan_details['calendar_days'],
        'study_hours' => $plan_details['daily_hours'],
        'study_days' => $plan_details['study_days'],
        'german_level' => $plan_details['german_level'],
        'site_name' => SITE_NAME,
        'dashboard_url' => SITE_URL . "admin/"
    ]);
    
    return send_email_phpmailer(
        $email,
        'برنامه مطالعه شما آماده است - ' . SITE_NAME,
        $template['html'],
        $template['text']
    );
}

/**
 * دریافت قالب ایمیل
 */
function get_email_template($type, $data = []) {
    $templates = [
        'study_plan' => [
            'html' => '
<!DOCTYPE html>
<html dir="rtl" lang="fa">
<head>
    <meta charset="UTF-8">
    <style>
        body { font-family: Tahoma, Arial, sans-serif; line-height: 1.6; color: #333; }
        .container { max-width: 600px; margin: 0 auto; padding: 20px; }
        .header { background: #5a8dee; color: white; padding: 25px; text-align: center; border-radius: 10px 10px 0 0; }
        .content { background: #f8f9fa; padding: 30px; border-radius: 0 0 10px 10px; border: 1px solid #e2e8f0; }
        .plan-box { background: white; border: 2px dashed #5a8dee; padding: 20px; border-radius: 10px; margin: 20px 0; }
        .button { display: inline-block; padding: 12px 30px; background: #5a8dee; color: white; text-decoration: none; border-radius: 5px; margin: 20px 0; }
        .footer { text-align: center; margin-top: 30px; color: #666; font-size: 12px; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>برنامه مطالعه اختصاصی شما 📚</h1>
        </div>
        <div class="content">
            <h2>سلام {name} عزیز،</h2>
            <p>برنامه مطالعه شما با توجه به اطلاعاتی که وارد کردید آماده شده است:</p>
            
            <div class="plan-box">
                <p>📍 سطح زبان فعلی: <b>{german_level}</b></p>
                <p>⏱ زمان مطالعه روزانه: <b>{study_hours} ساعت</b></p>
                <p>📅 روزهای مطالعه در هفته: <b>{study_days}</b></p>
                <p>⏳ تخمین زمان قبولی: <b>{estimated_days} روز</b></p>
            </div>
            
            <p>شما می‌توانید با ورود به پنل کاربری، برنامه خود را ویرایش کنید و وضعیت پیشرفت خود را مشاهده نمایید.</p>
            
            <div style="text-align: center;">
                <a href="{dashboard_url}" class="button">ورود به پنل و مشاهده جزئیات</a>
            </div>
        </div>
        <div class="footer">
            <p>© {site_name} - همسفر شما در جاده‌های آلمان</p>
        </div>
    </div>
</body>
</html>',
            'text' => 'سلام {name} عزیز،
برنامه مطالعه شما آماده شد:
سطح زبان: {german_level}
زمان مطالعه روزانه: {study_hours} ساعت
روزهای مطالعه در هفته: {study_days}
تخمین زمان قبولی: {estimated_days} روز

برای مشاهده جزئیات بیشتر به پنل کاربری مراجعه کنید:
{dashboard_url}

{site_name}'
        ],
        'verification' => [
            'html' => '
<div dir="rtl" style="font-family: Tahoma, sans-serif; line-height: 1.8; color: #000;">
    <p>سلام {name} عزیز،</p>
    <p>خوشحالیم که به جمع ما پیوستید.</p>
    <p>برای فعال‌سازی و شروع استفاده از خدمات سایت، لطفا روی لینک زیر کلیک کنید:</p>
    <p><a href="{verify_link}" style="color: #0000ff; text-decoration: underline;">{verify_link}</a></p>
    <br>
    <p>با احترام،</p>
    <p>{site_name}</p>
    <hr style="border: none; border-top: 1px solid #eee; margin: 20px 0;">
    <small style="color: #999;">زمان ارسال: ' . date('H:i:s') . '</small>
</div>',
            'text' => 'سلام {name} عزیز،

برای تایید حساب کاربری خود به آدرس زیر مراجعه کنید:
{verify_link}

{site_name}'
        ],
        
        'password_reset' => [
            'html' => '
<!DOCTYPE html>
<html dir="rtl" lang="fa">
<head>
    <meta charset="UTF-8">
    <style>
        body { font-family: Tahoma, Arial, sans-serif; line-height: 1.6; color: #333; }
        .container { max-width: 600px; margin: 0 auto; padding: 20px; }
        .header { background: #ffc107; color: #333; padding: 20px; text-align: center; border-radius: 10px 10px 0 0; }
        .content { background: #f8f9fa; padding: 30px; border-radius: 0 0 10px 10px; }
        .button { display: inline-block; padding: 12px 30px; background: #ffc107; color: #333; text-decoration: none; border-radius: 5px; margin: 20px 0; }
        .warning { background: #fff3cd; border: 1px solid #ffeaa7; padding: 10px; border-radius: 5px; margin: 20px 0; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>بازیابی رمز عبور</h1>
        </div>
        <div class="content">
            <h2>سلام {name} عزیز،</h2>
            <p>درخواست بازیابی رمز عبور برای حساب کاربری شما دریافت شد.</p>
            <div style="text-align: center;">
                <a href="{reset_link}" class="button">تغییر رمز عبور</a>
            </div>
            <div class="warning">
                <strong>توجه:</strong> این لینک تنها {expiry_hours} ساعت اعتبار دارد.
            </div>
            <p>اگر شما این درخواست را ارسال نکرده‌اید، رمز عبور شما در امان است و می‌توانید این ایمیل را نادیده بگیرید.</p>
        </div>
    </div>
</body>
</html>',
            'text' => 'سلام {name} عزیز،

درخواست بازیابی رمز عبور دریافت شد.

برای تغییر رمز عبور به آدرس زیر مراجعه کنید:
{reset_link}

توجه: این لینک تنها {expiry_hours} ساعت اعتبار دارد.

{site_name}'
        ],
        
        'password_changed' => [
            'html' => '
<!DOCTYPE html>
<html dir="rtl" lang="fa">
<head>
    <meta charset="UTF-8">
    <style>
        body { font-family: Tahoma, Arial, sans-serif; line-height: 1.6; color: #333; }
        .container { max-width: 600px; margin: 0 auto; padding: 20px; }
        .header { background: #28a745; color: white; padding: 20px; text-align: center; border-radius: 10px 10px 0 0; }
        .content { background: #f8f9fa; padding: 30px; border-radius: 0 0 10px 10px; }
        .alert { background: #d4edda; border: 1px solid #c3e6cb; padding: 15px; border-radius: 5px; margin: 20px 0; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>رمز عبور تغییر کرد</h1>
        </div>
        <div class="content">
            <h2>سلام {name} عزیز،</h2>
            <div class="alert">
                رمز عبور حساب کاربری شما با موفقیت تغییر یافت.
            </div>
            <p>اگر شما این تغییر را انجام نداده‌اید، فوراً با پشتیبانی تماس بگیرید:</p>
            <p>ایمیل پشتیبانی: {support_email}</p>
        </div>
    </div>
</body>
</html>',
            'text' => 'سلام {name} عزیز،

رمز عبور شما با موفقیت تغییر یافت.

اگر شما این تغییر را انجام نداده‌اید، فوراً با پشتیبانی تماس بگیرید.

{site_name}'
        ],
        
        'welcome' => [
            'html' => '
<!DOCTYPE html>
<html dir="rtl" lang="fa">
<head>
    <meta charset="UTF-8">
    <style>
        body { font-family: Tahoma, Arial, sans-serif; line-height: 1.6; color: #333; }
        .container { max-width: 600px; margin: 0 auto; padding: 20px; }
        .header { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 30px; text-align: center; border-radius: 10px 10px 0 0; }
        .content { background: #f8f9fa; padding: 30px; border-radius: 0 0 10px 10px; }
        .button { display: inline-block; padding: 12px 30px; background: #667eea; color: white; text-decoration: none; border-radius: 5px; margin: 20px 0; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>به {site_name} خوش آمدید! 🎉</h1>
        </div>
        <div class="content">
            <h2>سلام {name} عزیز،</h2>
            <p>حساب کاربری شما با موفقیت ایجاد شد. ما خوشحالیم که به جمع ما پیوسته‌اید!</p>
            <div style="text-align: center;">
                <a href="{login_url}" class="button">ورود به حساب کاربری</a>
            </div>
            <p>در صورت داشتن هرگونه سوال، با ما در تماس باشید.</p>
        </div>
    </div>
</body>
</html>',
            'text' => 'به {site_name} خوش آمدید!

سلام {name} عزیز،

حساب کاربری شما با موفقیت ایجاد شد.

برای ورود به حساب کاربری:
{login_url}

{site_name}'
        ]
    ];
    
    // جایگزینی متغیرها
    $template = $templates[$type] ?? ['html' => '', 'text' => ''];
    
    foreach ($data as $key => $value) {
        $template['html'] = str_replace('{' . $key . '}', $value, $template['html']);
        $template['text'] = str_replace('{' . $key . '}', $value, $template['text']);
    }
    
    return $template;
}