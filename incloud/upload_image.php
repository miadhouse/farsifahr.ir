<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/functions.php';

header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'فقط درخواست POST مجاز است']);
    exit;
}

// بررسی CSRF token
$token = $_POST['csrf_token'] ?? '';
if (!verify_csrf_token($token)) {
    echo json_encode(['success' => false, 'message' => 'توکن امنیتی نامعتبر است']);
    exit;
}

// Check if user is admin
$isAdmin = is_super_admin();
if (!$isAdmin) {
    echo json_encode(['success' => false, 'message' => 'دسترسی غیرمجاز']);
    exit;
}

if (!isset($_FILES['image']) || $_FILES['image']['error'] !== UPLOAD_ERR_OK) {
    echo json_encode(['success' => false, 'message' => 'خطا در آپلود تصویر']);
    exit;
}

$file = $_FILES['image'];
$ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
$allowedExts = ['jpg', 'jpeg', 'png', 'gif', 'webp', 'svg'];

if (!in_array($ext, $allowedExts)) {
    echo json_encode(['success' => false, 'message' => 'فرمت فایل غیرمجاز است']);
    exit;
}

$uploadDir = __DIR__ . '/../miad/storage/app/public/answers/info/';
if (!is_dir($uploadDir)) {
    mkdir($uploadDir, 0755, true);
}

$filename = uniqid('img_', true) . '.' . $ext;
$destination = $uploadDir . $filename;

if (move_uploaded_file($file['tmp_name'], $destination)) {
    chmod($destination, 0644);
    // URL to access the image
    $url = '/storage/answers/info/' . $filename;
    echo json_encode(['success' => true, 'url' => $url]);
} else {
    echo json_encode(['success' => false, 'message' => 'خطا در ذخیره فایل']);
}
