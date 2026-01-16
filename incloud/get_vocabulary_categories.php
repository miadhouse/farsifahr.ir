<?php
// incloud/get_vocabulary_categories.php
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

$user_id = $_SESSION['user_id'];

try {
    // ابتدا تمام کلمات کاربر را با question_id آن‌ها دریافت می‌کنیم
    $stmt = $pdo->prepare("
        SELECT DISTINCT
            uv.word_id,
            uv.question_id,
            q.category_id
        FROM user_vocabulary uv
        INNER JOIN questions q ON uv.question_id = q.id
        WHERE uv.user_id = ?
        AND uv.question_id IS NOT NULL
    ");

    $stmt->execute([$user_id]);
    $questionData = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if (empty($questionData)) {
        echo json_encode([
            'success' => true,
            'categories' => []
        ]);
        exit;
    }

    // استخراج تمام category_id ها و شمارش کلمات یونیک
    $categoryWords = []; // [category_id => [word_id1, word_id2, ...]]

    foreach ($questionData as $row) {
        // category_id به صورت ",1,2,3," ذخیره شده
        $categoryIds = trim($row['category_id'], ',');
        $categoryArray = explode(',', $categoryIds);

        foreach ($categoryArray as $catId) {
            if (!empty($catId)) {
                if (!isset($categoryWords[$catId])) {
                    $categoryWords[$catId] = [];
                }
                // اضافه کردن word_id به لیست (اگر قبلاً وجود نداشت)
                if (!in_array($row['word_id'], $categoryWords[$catId])) {
                    $categoryWords[$catId][] = $row['word_id'];
                }
            }
        }
    }

    if (empty($categoryWords)) {
        echo json_encode([
            'success' => true,
            'categories' => []
        ]);
        exit;
    }

    // دریافت اطلاعات دسته‌بندی‌ها
    $categoryIds = array_keys($categoryWords);
    $placeholders = str_repeat('?,', count($categoryIds) - 1) . '?';

    $stmt = $pdo->prepare("
        SELECT 
            id,
            title,
            index_code,
            category_type,
            parent_id
        FROM categories
        WHERE id IN ($placeholders)
        ORDER BY 
            CAST(SUBSTRING_INDEX(index_code, '.', 1) AS UNSIGNED),
            CAST(SUBSTRING_INDEX(SUBSTRING_INDEX(index_code, '.', 2), '.', -1) AS UNSIGNED),
            CAST(SUBSTRING_INDEX(index_code, '.', -1) AS UNSIGNED)
    ");

    $stmt->execute($categoryIds);
    $categories = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // اضافه کردن تعداد کلمات یونیک به هر دسته‌بندی
    foreach ($categories as &$category) {
        $category['word_count'] = count($categoryWords[$category['id']] ?? []);
    }

    echo json_encode([
        'success' => true,
        'categories' => $categories
    ]);

} catch (PDOException $e) {
    error_log("Database error in get_vocabulary_categories.php: " . $e->getMessage());
    error_log("SQL State: " . $e->getCode());
    echo json_encode([
        'success' => false,
        'error' => 'خطا در دریافت دسته‌بندی‌ها',
        'details' => $e->getMessage() // برای دیباگ
    ]);
} catch (Exception $e) {
    error_log("General error in get_vocabulary_categories.php: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'error' => 'خطا در دریافت دسته‌بندی‌ها',
        'details' => $e->getMessage() // برای دیباگ
    ]);
}
?>