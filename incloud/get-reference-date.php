<?php
// ajax/get-reference-date.php
require_once(__DIR__ . '/../config/config.php');
require_once(__DIR__ . '/../includes/functions.php');
require_once(__DIR__ . '/../includes/user-config-handler.php');

header('Content-Type: application/json; charset=utf-8');

// بررسی ورود کاربر
if (!is_logged_in()) {
    echo json_encode([
        'success' => false,
        'message' => 'لطفاً ابتدا وارد شوید'
    ]);
    exit;
}

try {
    $configHandler = new UserConfigHandler($pdo);
    $referenceDate = $configHandler->getReferenceDate();

    // تبدیل به فرمت فارسی
    require_once(__DIR__ . '/../includes/jdf.php'); // کتابخانه تقویم شمسی

    echo json_encode([
        'success' => true,
        'data' => [
            'date' => $referenceDate,
            'formatted' => date('d.m.Y', strtotime($referenceDate))
        ]
    ]);
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'خطا در دریافت تاریخ مرجع'
    ]);
}