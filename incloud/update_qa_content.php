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
$isAdmin = isset($_SESSION['role']) && $_SESSION['role'] === 'admin';
if (!$isAdmin) {
    echo json_encode(['success' => false, 'message' => 'دسترسی غیرمجاز']);
    exit;
}

$id = isset($_POST['id']) ? (int)$_POST['id'] : null;
$type = $_POST['type'] ?? null;
$field = $_POST['field'] ?? null;
$content = $_POST['content'] ?? '';

if (!$id || !$type || !$field) {
    echo json_encode(['success' => false, 'message' => 'پارامترهای لازم ارسال نشده‌اند']);
    exit;
}

// Validate type and field to prevent SQL injection
$allowedTypes = ['question' => 'questions', 'answer' => 'answers'];
$allowedFields = ['farsi_text', 'info', 'asw_farsi', 'text', 'asw_pretext'];

if (!array_key_exists($type, $allowedTypes) || !in_array($field, $allowedFields)) {
    echo json_encode(['success' => false, 'message' => 'پارامترهای نامعتبر']);
    exit;
}

$tableName = $allowedTypes[$type];

try {
    $stmt = $pdo->prepare("UPDATE {$tableName} SET {$field} = :content WHERE id = :id");
    $stmt->execute([
        ':content' => $content,
        ':id' => $id
    ]);
    
    echo json_encode(['success' => true, 'message' => 'با موفقیت بروزرسانی شد']);
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'خطا در پایگاه داده: ' . $e->getMessage()]);
}
