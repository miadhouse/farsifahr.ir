<?php
// اتصال به دیتابیس (باید با تنظیمات دیتابیس خود تطبیق دهید)
require_once __DIR__ . '/../config/config.php';
// بررسی ورود کاربر (باید با سیستم احراز هویت شما تطبیق دهید)
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'کاربر وارد نشده است']);
    exit;
}



if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'متد غیرمجاز']);
    exit;
}
// بررسی CSRF token
if (!isset($_POST['csrf_token']) || empty($_POST['csrf_token'])) {
    echo json_encode([
        'success' => false,
        'message' => 'توکن امنیتی ارسال نشده است'
    ]);
    exit;
}
// دریافت پارامترها
$user_id = $_SESSION['user_id'];
$question_id = $_POST['question_id'] ?? null;

if (!$question_id) {
    http_response_code(400);
    echo json_encode(['error' => 'شناسه سوال ارسال نشده است']);
    exit;
}

try {
    // بررسی وجود بوک مارک
    $stmt = $pdo->prepare("SELECT id FROM question_bookmarks WHERE user_id = ? AND question_id = ?");
    $stmt->execute([$user_id, $question_id]);
    $bookmark = $stmt->fetch();

    if ($bookmark) {
        // حذف بوک مارک
        $stmt = $pdo->prepare("DELETE FROM question_bookmarks WHERE user_id = ? AND question_id = ?");
        $stmt->execute([$user_id, $question_id]);

        echo json_encode([
            'success' => true,
            'bookmarked' => false,
            'message' => 'علامت گذاری حذف شد'
        ]);
    } else {
        // افزودن بوک مارک
        $stmt = $pdo->prepare("INSERT INTO question_bookmarks (user_id, question_id) VALUES (?, ?)");
        $stmt->execute([$user_id, $question_id]);

        echo json_encode([
            'success' => true,
            'bookmarked' => true,
            'message' => 'سوال علامت گذاری شد'
        ]);
    }

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'خطا در پایگاه داده: ' . $e->getMessage()]);
}
?>