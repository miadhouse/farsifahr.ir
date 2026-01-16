<?php
// incloud/get_vocabulary_by_category.php
require_once __DIR__ . '/../config/config.php';

header('Content-Type: application/json; charset=utf-8');

// بررسی CSRF token
if (!isset($_POST['csrf_token']) || empty($_POST['csrf_token'])) {
    echo json_encode([
        'success' => false,
        'error' => 'توکن امنیتی ارسال نشده است'
    ]);
    exit;
}

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'error' => 'کاربر وارد نشده است']);
    exit;
}

if (!isset($_POST['category_id']) || empty($_POST['category_id'])) {
    echo json_encode(['success' => false, 'error' => 'شناسه دسته‌بندی ارسال نشده است']);
    exit;
}

$user_id = $_SESSION['user_id'];
$category_id = (int) $_POST['category_id'];

try {
    // دریافت کلمات مربوط به دسته‌بندی خاص
    // category_id در جدول questions به صورت ",1,2,3," ذخیره شده
    $pattern = "%," . $category_id . ",%";

    $stmt = $pdo->prepare("
        SELECT DISTINCT
            vw.id,
            vw.word,
            vw.translation,
            uv.question_id,
            q.text,
            q.category_id as question_categories
        FROM user_vocabulary uv
        INNER JOIN vocabulary_words vw ON uv.word_id = vw.id
        INNER JOIN questions q ON uv.question_id = q.id
        WHERE uv.user_id = ?
        AND q.category_id LIKE ?
        AND uv.question_id IS NOT NULL
        ORDER BY uv.created_at DESC
    ");

    $stmt->execute([$user_id, $pattern]);
    $words = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if (empty($words)) {
        echo json_encode([
            'success' => false,
            'error' => 'هیچ کلمه‌ای برای این دسته‌بندی یافت نشد'
        ]);
        exit;
    }

    echo json_encode([
        'success' => true,
        'words' => $words
    ]);

} catch (PDOException $e) {
    error_log("Database error in get_vocabulary_by_category.php: " . $e->getMessage());
    error_log("SQL State: " . $e->getCode());
    echo json_encode([
        'success' => false,
        'error' => 'خطا در دریافت کلمات',
        'details' => $e->getMessage() // برای دیباگ
    ]);
} catch (Exception $e) {
    error_log("General error in get_vocabulary_by_category.php: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'error' => 'خطا در دریافت کلمات',
        'details' => $e->getMessage() // برای دیباگ
    ]);
}
?>