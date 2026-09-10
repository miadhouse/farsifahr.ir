<?php
require_once __DIR__ . '/../config/config.php';

header('Content-Type: application/json; charset=utf-8');

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'کاربر وارد نشده است']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'متد غیرمجاز']);
    exit;
}

if (!isset($_POST['csrf_token']) || empty($_POST['csrf_token'])) {
    echo json_encode(['success' => false, 'message' => 'توکن امنیتی ارسال نشده است']);
    exit;
}

$user_id = (int)$_SESSION['user_id'];

try {
    if (isset($_POST['shuffle_answers'])) {
        $shuffle = ((int)$_POST['shuffle_answers'] === 1) ? 1 : 0;
    } else {
        $stmt = $pdo->prepare("SELECT shuffle_answers FROM users WHERE id = ?");
        $stmt->execute([$user_id]);
        $current = $stmt->fetchColumn();
        $shuffle = ($current == 1) ? 0 : 1;
    }

    $stmt = $pdo->prepare("UPDATE users SET shuffle_answers = ? WHERE id = ?");
    $stmt->execute([$shuffle, $user_id]);

    $_SESSION['shuffle_answers'] = $shuffle;

    echo json_encode([
        'success' => true,
        'shuffle_answers' => $shuffle,
        'message' => $shuffle ? 'چینش تصادفی گزینه‌ها فعال شد' : 'چینش تصادفی گزینه‌ها غیرفعال شد'
    ]);
} catch (PDOException $e) {
    error_log("Database error in toggle_shuffle_answers.php: " . $e->getMessage());
    echo json_encode(['success' => false, 'error' => 'خطای پایگاه داده رخ داده است']);
}
