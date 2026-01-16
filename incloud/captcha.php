<?php
// captcha.php
session_start();

// تنظیمات کپچا
$width = 120;
$height = 40;
$font_size = 20;
$code_length = 6;

// تولید کد
$code = substr(str_shuffle('23456789ABCDEFGHJKLMNPQRSTUVWXYZ'), 0, $code_length);
$_SESSION['captcha'] = $code;

// ایجاد تصویر
$image = imagecreatetruecolor($width, $height);

// رنگ‌ها
$bg_color = imagecolorallocate($image, 255, 255, 255);
$text_color = imagecolorallocate($image, 0, 0, 0);
$noise_color = imagecolorallocate($image, 100, 100, 100);

// پر کردن پس‌زمینه
imagefilledrectangle($image, 0, 0, $width, $height, $bg_color);

// اضافه کردن نویز (نقاط)
for ($i = 0; $i < 100; $i++) {
    imagesetpixel($image, rand(0, $width), rand(0, $height), $noise_color);
}

// اضافه کردن نویز (خطوط)
for ($i = 0; $i < 5; $i++) {
    imageline($image, rand(0, $width), rand(0, $height), 
              rand(0, $width), rand(0, $height), $noise_color);
}

// نوشتن متن
$font = __DIR__ . '../assets/fonts/arial.ttf'; // مسیر فونت را تنظیم کنید

// اگر فونت وجود ندارد، از فونت داخلی استفاده کنید
if (!file_exists($font)) {
    // استفاده از فونت داخلی
    $x = 10;
    $y = 25;
    for ($i = 0; $i < strlen($code); $i++) {
        imagestring($image, 5, $x, $y + rand(-5, 5), $code[$i], $text_color);
        $x += 18;
    }
} else {
    // استفاده از فونت TTF
    $x = 10;
    for ($i = 0; $i < strlen($code); $i++) {
        $angle = rand(-15, 15);
        imagettftext($image, $font_size, $angle, $x, 30, $text_color, $font, $code[$i]);
        $x += 18;
    }
}

// تنظیم هدر
header('Content-Type: image/png');
header('Cache-Control: no-cache, no-store, must-revalidate');
header('Pragma: no-cache');
header('Expires: 0');

// خروجی تصویر
imagepng($image);
imagedestroy($image);