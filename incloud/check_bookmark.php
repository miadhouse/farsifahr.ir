<?php

// اتصال به دیتابیس
require_once __DIR__ . '/../config/config.php';
// بررسی CSRF token
if (!isset($_GET['csrf_token']) || empty($_GET['csrf_token'])) {
    echo json_encode([
        'success' => false,
        'message' => 'توکن امنیتی ارسال نشده است'
    ]);
    exit;
}
// بررسی ورود کاربر
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['bookmarked' => false]);
    exit;
}

$user_id = $_SESSION['user_id'];
$question_id = $_GET['question_id'] ?? null;

if (!$question_id) {
    echo json_encode(['bookmarked' => false]);
    exit;
}

try {
    $stmt = $pdo->prepare("SELECT id FROM question_bookmarks WHERE user_id = ? AND question_id = ?");
    $stmt->execute([$user_id, $question_id]);
    $bookmark = $stmt->fetch();

    echo json_encode(['bookmarked' => $bookmark ? true : false]);

} catch (PDOException $e) {
    echo json_encode(['bookmarked' => false, 'error' => $e->getMessage()]);
}
?>