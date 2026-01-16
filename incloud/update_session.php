<?php
require_once(__DIR__ . '/../config/config.php');
// بررسی CSRF token
if (!isset($_POST['csrf_token']) || empty($_POST['csrf_token'])) {
    echo json_encode([
        'success' => false,
        'message' => 'توکن امنیتی ارسال نشده است'
    ]);
    exit;
}
if (isset($_POST['current_question_id'])) {
    $_SESSION['current_question_id'] = $_POST['current_question_id'];
    echo json_encode(['status' => 'success', 'message' => 'Session updated successfully']);
} else {
    echo json_encode(['status' => 'error', 'message' => 'Question ID not provided']);
}
?>