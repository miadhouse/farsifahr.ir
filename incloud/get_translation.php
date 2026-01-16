<?php
//get_translation.php
require_once __DIR__ . '/../config/config.php';

header('Content-Type: application/json; charset=utf-8');
// بررسی CSRF token
if (!isset($_POST['csrf_token']) || empty($_POST['csrf_token'])) {
    echo json_encode([
        'success' => false,
        'message' => 'توکن امنیتی ارسال نشده است'
    ]);
    exit;
}
if (!isset($_POST['word']) || empty(trim($_POST['word']))) {
    echo json_encode(['success' => false, 'error' => 'کلمه ارسال نشده است']);
    exit;
}

$word = trim($_POST['word']);
$user_id = $_SESSION['user_id'] ?? null;

// بررسی اینکه فقط یک کلمه باشد
$words = explode(' ', $word);
if (count($words) > 1) {
    echo json_encode(['success' => false, 'error' => 'فقط یک کلمه قابل ترجمه است']);
    exit;
}

// جستجوی کلمه در دیتابیس
try {
    $stmt = $pdo->prepare("SELECT id, translation FROM vocabulary_words WHERE word = ?");
    $stmt->execute([$word]);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($result) {
        $word_id = $result['id'];
        $translation = $result['translation'];

        // بررسی اینکه آیا این کلمه در کلکشن کاربر موجود است یا نه
        $in_user_collection = false;
        if ($user_id) {
            $stmt = $pdo->prepare("SELECT id FROM user_vocabulary WHERE user_id = ? AND word_id = ?");
            $stmt->execute([$user_id, $word_id]);
            $in_user_collection = $stmt->fetch(PDO::FETCH_ASSOC) !== false;
        }

        echo json_encode([
            'success' => true,
            'translation' => $translation,
            'word_id' => $word_id,
            'from_database' => true,
            'in_user_collection' => $in_user_collection
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'error' => 'ترجمه در دیتابیس موجود نیست'
        ]);
    }

} catch (PDOException $e) {
    error_log("Database error in get_translation.php: " . $e->getMessage());
    echo json_encode(['success' => false, 'error' => 'خطا در جستجوی دیتابیس']);
}
?>