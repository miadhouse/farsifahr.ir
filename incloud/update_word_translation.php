<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/functions.php';

header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'متد غیرمجاز']);
    exit;
}

// بررسی CSRF token
$token = $_POST['csrf_token'] ?? '';
if (!verify_csrf_token($token)) {
    echo json_encode(['success' => false, 'message' => 'توکن امنیتی نامعتبر است']);
    exit;
}

$word = trim($_POST['word'] ?? '');
$word = trim($word, " \t\n\r\0\x0B.,?!;:\"'()[]{}«»");
$translation = trim($_POST['translation'] ?? '');
$user_id = $_SESSION['user_id'] ?? null;

if (empty($word) || empty($translation)) {
    echo json_encode(['success' => false, 'message' => 'کلمه و ترجمه الزامی است']);
    exit;
}

try {
    // 1. به‌روزرسانی یا درج در vocabulary_words
    $stmt = $pdo->prepare("SELECT id, translation FROM vocabulary_words WHERE word = ? LIMIT 1");
    $stmt->execute([$word]);
    $existing = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($existing) {
        $word_id = $existing['id'];
        $stmt = $pdo->prepare("UPDATE vocabulary_words SET translation = ?, updated_at = CURRENT_TIMESTAMP WHERE id = ?");
        $stmt->execute([$translation, $word_id]);
    } else {
        $stmt = $pdo->prepare("INSERT INTO vocabulary_words (word, translation) VALUES (?, ?)");
        $stmt->execute([$word, $translation]);
        $word_id = $pdo->lastInsertId();
    }

    // 2. در صورت وجود در کلکشن کاربر، تاریخ آن به‌روزرسانی شود
    if ($user_id && $word_id) {
        $stmtUser = $pdo->prepare("SELECT id FROM user_vocabulary WHERE user_id = ? AND word_id = ?");
        $stmtUser->execute([$user_id, $word_id]);
        $userVocab = $stmtUser->fetch(PDO::FETCH_ASSOC);
        if ($userVocab) {
            $stmtUpdateUser = $pdo->prepare("UPDATE user_vocabulary SET updated_at = CURRENT_TIMESTAMP WHERE id = ?");
            $stmtUpdateUser->execute([$userVocab['id']]);
        }
    }

    echo json_encode([
        'success' => true,
        'message' => 'ترجمه با موفقیت به‌روزرسانی و اعمال شد',
        'word' => $word,
        'translation' => $translation,
        'word_id' => $word_id
    ]);

} catch (PDOException $e) {
    error_log("Database error in update_word_translation.php: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'خطای دیتابیس در ذخیره ترجمه']);
}
