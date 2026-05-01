<?php
require_once __DIR__ . '/../config/config.php';

header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'فقط درخواست POST مجاز است']);
    exit;
}

// بررسی CSRF token
if (!isset($_POST['csrf_token']) || empty($_POST['csrf_token'])) {
    echo json_encode(['success' => false, 'message' => 'توکن امنیتی ارسال نشده است']);
    exit;
}

$user_id = $_SESSION['user_id'] ?? null;
if (!$user_id) {
    echo json_encode(['success' => false, 'message' => 'لطفاً ابتدا وارد شوید']);
    exit;
}

$question_id = isset($_POST['question_id']) ? (int)$_POST['question_id'] : 0;
$message = trim($_POST['message'] ?? '');

if (!$question_id) {
    echo json_encode(['success' => false, 'message' => 'شناسه سوال نامعتبر است']);
    exit;
}

try {
    $stmt = $pdo->prepare("INSERT INTO question_reports (user_id, question_id, message, status, created_at, updated_at) VALUES (?, ?, ?, 'pending', NOW(), NOW())");
    $stmt->execute([$user_id, $question_id, $message]);
    
    echo json_encode(['success' => true, 'message' => 'گزارش شما با موفقیت ثبت شد. می‌توانید وضعیت بررسی آن را در داشبورد خود پیگیری کنید. پس از تایید مدیر، هدیه به شما تعلق خواهد گرفت.']);
} catch (\PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'خطا در ثبت گزارش: ' . $e->getMessage()]);
}
