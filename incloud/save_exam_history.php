<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/functions.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'error' => 'Not logged in']);
    exit();
}

$csrf_token = $_POST['csrf_token'] ?? '';
if (!verify_csrf_token($csrf_token)) {
    echo json_encode(['success' => false, 'error' => 'Invalid CSRF token']);
    exit();
}

$user_id = $_SESSION['user_id'];
$score = (int)($_POST['score'] ?? 0);
$total_questions = (int)($_POST['total_questions'] ?? 30);
$correct_count = (int)($_POST['correct_count'] ?? 0);
$error_points = (int)($_POST['error_points'] ?? 0);
$five_point_errors = (int)($_POST['five_point_errors'] ?? 0);
$passed = (int)($_POST['passed'] ?? 0);
$wrong_questions = $_POST['wrong_questions'] ?? '[]';
$all_questions = $_POST['all_questions'] ?? '[]';

try {
    $stmt = $pdo->prepare("
        INSERT INTO exam_history (
            user_id, score, total_questions, correct_count, error_points, 
            five_point_errors, passed, wrong_questions, all_questions
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
    ");
    
    $stmt->execute([
        $user_id, $score, $total_questions, $correct_count, $error_points,
        $five_point_errors, $passed, $wrong_questions, $all_questions
    ]);

    echo json_encode(['success' => true, 'id' => $pdo->lastInsertId()]);
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'error' => 'Database error: ' . $e->getMessage()]);
}
