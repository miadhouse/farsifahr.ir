<?php
// api.php
require_once __DIR__ . '/../config/config.php';

// تنظیم هدرها
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST');
header('Access-Control-Allow-Headers: Content-Type');

// شروع session
session_start();

// دریافت نوع درخواست
$action = $_GET['action'] ?? $_POST['action'] ?? '';

try {
    switch($action) {
        case 'get_classes':
            getClasses();
            break;
            
        case 'get_versions':
            getVersions();
            break;
            
        case 'save_selection':
            saveSelection();
            break;
            
        case 'get_selection':
            getSelection();
            break;
            
        default:
            throw new Exception('عملیات نامعتبر');
    }
} catch(Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}

// دریافت کلاس‌ها
function getClasses() {
    global $pdo;
    
    $stmt = $pdo->query("SELECT * FROM tbl_classes ORDER BY id");
    $classes = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode([
        'success' => true,
        'classes' => $classes
    ]);
}

// دریافت نسخه‌ها
function getVersions() {
    global $pdo;
    
    $stmt = $pdo->query("SELECT * FROM data_versions ORDER BY version");
    $versions = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode([
        'success' => true,
        'versions' => $versions
    ]);
}

// ذخیره انتخاب
function saveSelection() {
    $class_id = $_POST['class_id'] ?? null;
    $data_version = $_POST['data_version'] ?? null;
    
    if (!$class_id || !$data_version) {
        throw new Exception('پارامترهای الزامی ارسال نشده');
    }
    
    $_SESSION['selected_class'] = $class_id;
    $_SESSION['selected_data_version'] = $data_version;
    $_SESSION['selection_time'] = time();
    
    echo json_encode([
        'success' => true,
        'message' => 'انتخاب ذخیره شد'
    ]);
}

// دریافت انتخاب فعلی
function getSelection() {
    echo json_encode([
        'success' => true,
        'selection' => [
            'class_id' => $_SESSION['selected_class'] ?? null,
            'data_version' => $_SESSION['selected_data_version'] ?? null,
            'selection_time' => $_SESSION['selection_time'] ?? null
        ]
    ]);
}
?>