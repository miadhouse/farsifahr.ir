<?php
// ajax/save-user-config.php
require_once(__DIR__ . '/../config/config.php');
require_once(__DIR__ . '/functions.php');
require_once(__DIR__ . '//user-config-handler.php');

header('Content-Type: application/json; charset=utf-8');

// بررسی ورود کاربر
if (!is_logged_in()) {
    echo json_encode([
        'success' => false,
        'message' => 'لطفاً ابتدا وارد شوید'
    ]);
    exit;
}

// بررسی متد درخواست
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode([
        'success' => false,
        'message' => 'متد درخواست نامعتبر است'
    ]);
    exit;
}

// بررسی CSRF token
if (!isset($_POST['csrf_token']) || !verify_csrf_token($_POST['csrf_token'])) {
    echo json_encode([
        'success' => false,
        'message' => 'توکن امنیتی نامعتبر است'
    ]);
    exit;
}

try {
    // دریافت داده‌ها
    $exam_date_type = isset($_POST['exam_date_type']) ? trim($_POST['exam_date_type']) : '';
    $language = isset($_POST['language']) ? trim($_POST['language']) : '';

    // اعتبارسنجی
    if (empty($exam_date_type) || empty($language)) {
        throw new Exception('لطفاً تمام فیلدها را پر کنید');
    }

    if (!in_array($exam_date_type, ['before', 'after'])) {
        throw new Exception('نوع تاریخ امتحان نامعتبر است');
    }

    if (!in_array($language, ['DE', 'EN'])) {
        throw new Exception('زبان انتخابی نامعتبر است');
    }

    // ذخیره تنظیمات
    $configHandler = new UserConfigHandler($pdo);
    $result = $configHandler->saveConfig($_SESSION['user_id'], $exam_date_type, $language);

    if ($result) {
        // ثبت لاگ
        log_user_action(
            $_SESSION['user_id'],
            $_SESSION['email'],
            'config_update',
            'success',
            $pdo
        );

        // پاک کردن flag مودال
        unset($_SESSION['show_config_modal']);

        echo json_encode([
            'success' => true,
            'message' => 'تنظیمات با موفقیت ذخیره شد',
            'data' => [
                'exam_date_type' => $exam_date_type,
                'language' => $language
            ]
        ]);
    } else {
        throw new Exception('خطا در ذخیره تنظیمات');
    }
} catch (Exception $e) {
    // ثبت لاگ خطا
    if (isset($_SESSION['user_id'])) {
        log_user_action(
            $_SESSION['user_id'],
            $_SESSION['email'],
            'config_update',
            'failed',
            $pdo
        );
    }

    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}