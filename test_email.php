<?php
// test_email.php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require_once __DIR__ . '/vendor/autoload.php';
require_once __DIR__ . '/config/config.php';

$mail = new PHPMailer(true);

try {
    // تنظیمات برای دیباگ
    $mail->SMTPDebug = 2; // نمایش جزئیات کامل SMTP
    $mail->Debugoutput = 'html';

    $mail->isSMTP();
    $mail->Host       = SMTP_HOST;
    $mail->SMTPAuth   = true;
    $mail->Username   = SMTP_USER;
    $mail->Password   = SMTP_PASS;
    $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
    $mail->Port       = SMTP_PORT;

    $mail->setFrom(SMTP_FROM, 'تست سیستم');
    $mail->addAddress('mehran.khademi@gmail.com'); // ایمیل خودتان را اینجا قرار دهید

    $mail->isHTML(true);
    $mail->Subject = 'تست ارسال ایمیل';
    $mail->Body    = 'این یک ایمیل تست برای بررسی مشکل ارسال است.';

    $mail->send();
    echo 'ایمیل با موفقیت ارسال شد.';
} catch (Exception $e) {
    echo "پیام خطا: {$mail->ErrorInfo}";
}
?>
