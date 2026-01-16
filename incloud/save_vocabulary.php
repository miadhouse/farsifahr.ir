<?php
//save_vocabulary.php
require_once __DIR__ . '/../config/config.php';

header('Content-Type: application/json; charset=utf-8');

// فعال‌سازی نمایش خطا برای دیباگ (بعداً غیرفعال کنید)
ini_set('display_errors', 1);
error_reporting(E_ALL);

// بررسی CSRF token
if (!isset($_POST['csrf_token']) || empty($_POST['csrf_token'])) {
    echo json_encode([
        'success' => false,
        'error' => 'توکن امنیتی ارسال نشده است'
    ]);
    exit;
}

// بررسی ورود کاربر
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'error' => 'کاربر وارد نشده است']);
    exit;
}

$user_id = $_SESSION['user_id'];

// بررسی پارامترهای ورودی
if (
    !isset($_POST['word']) || !isset($_POST['translation']) ||
    empty(trim($_POST['word'])) || empty(trim($_POST['translation']))
) {
    echo json_encode(['success' => false, 'error' => 'کلمه و ترجمه الزامی است']);
    exit;
}

$word = trim($_POST['word']);
$translation = trim($_POST['translation']);
$question_id = isset($_POST['question_id']) && !empty($_POST['question_id']) ? (int) $_POST['question_id'] : null;
$category_id = isset($_POST['category_id']) && !empty($_POST['category_id']) ? (int) $_POST['category_id'] : null;

// بررسی اینکه فقط یک کلمه باشد
$words = preg_split('/\s+/', $word);
if (count($words) > 1) {
    echo json_encode(['success' => false, 'error' => 'فقط یک کلمه قابل ذخیره است']);
    exit;
}

// بررسی طول کلمه
if (mb_strlen($word, 'UTF-8') < 2 || mb_strlen($word, 'UTF-8') > 100) {
    echo json_encode(['success' => false, 'error' => 'طول کلمه باید بین 2 تا 100 کاراکتر باشد']);
    exit;
}

// بررسی طول ترجمه
if (mb_strlen($translation, 'UTF-8') > 200) {
    echo json_encode(['success' => false, 'error' => 'طول ترجمه نباید بیشتر از 200 کاراکتر باشد']);
    exit;
}

// بررسی اینکه کلمه شامل حروف باشد
if (!preg_match('/[a-zA-ZäöüßÄÖÜ]/', $word)) {
    echo json_encode(['success' => false, 'error' => 'کلمه باید شامل حروف باشد']);
    exit;
}

try {
    // بررسی اتصال به دیتابیس
    if (!isset($pdo) || !$pdo) {
        throw new Exception('اتصال به دیتابیس برقرار نیست');
    }

    $pdo->beginTransaction();

    // مرحله 1: بررسی یا درج در vocabulary_words
    $stmt = $pdo->prepare("SELECT id, translation FROM vocabulary_words WHERE word = ?");
    if (!$stmt) {
        throw new Exception('خطا در آماده‌سازی کوئری SELECT: ' . implode(', ', $pdo->errorInfo()));
    }

    $stmt->execute([$word]);
    $word_row = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$word_row) {
        // کلمه جدید است
        $stmt = $pdo->prepare("INSERT INTO vocabulary_words (word, translation) VALUES (?, ?)");
        if (!$stmt) {
            throw new Exception('خطا در آماده‌سازی کوئری INSERT vocabulary_words: ' . implode(', ', $pdo->errorInfo()));
        }

        $result = $stmt->execute([$word, $translation]);
        if (!$result) {
            throw new Exception('خطا در INSERT vocabulary_words: ' . implode(', ', $stmt->errorInfo()));
        }

        $word_id = $pdo->lastInsertId();

        if (!$word_id) {
            throw new Exception('خطا در دریافت lastInsertId برای vocabulary_words');
        }
    } else {
        $word_id = $word_row['id'];

        // اگر ترجمه تغییر کرده، آن را به‌روزرسانی کن
        if ($word_row['translation'] !== $translation) {
            $stmt = $pdo->prepare("UPDATE vocabulary_words SET translation = ?, updated_at = CURRENT_TIMESTAMP WHERE id = ?");
            if (!$stmt) {
                throw new Exception('خطا در آماده‌سازی کوئری UPDATE vocabulary_words: ' . implode(', ', $pdo->errorInfo()));
            }

            $result = $stmt->execute([$translation, $word_id]);
            if (!$result) {
                throw new Exception('خطا در UPDATE vocabulary_words: ' . implode(', ', $stmt->errorInfo()));
            }
        }
    }

    // مرحله 2: بررسی کلکشن کاربر
    $stmt = $pdo->prepare("
        SELECT id FROM user_vocabulary 
        WHERE user_id = ? AND word_id = ?
    ");
    if (!$stmt) {
        throw new Exception('خطا در آماده‌سازی کوئری SELECT user_vocabulary: ' . implode(', ', $pdo->errorInfo()));
    }

    $stmt->execute([$user_id, $word_id]);
    $user_vocab_row = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($user_vocab_row) {
        // کلمه در کلکشن موجود است - به‌روزرسانی
        $stmt = $pdo->prepare("
            UPDATE user_vocabulary 
            SET question_id = ?, category_id = ?, updated_at = CURRENT_TIMESTAMP 
            WHERE id = ?
        ");
        if (!$stmt) {
            throw new Exception('خطا در آماده‌سازی کوئری UPDATE user_vocabulary: ' . implode(', ', $pdo->errorInfo()));
        }

        $result = $stmt->execute([$question_id, $category_id, $user_vocab_row['id']]);
        if (!$result) {
            throw new Exception('خطا در UPDATE user_vocabulary: ' . implode(', ', $stmt->errorInfo()));
        }

        $pdo->commit();

        echo json_encode([
            'success' => true,
            'message' => 'ترجمه به‌روزرسانی شد',
            'word_id' => $word_id,
            'updated' => true
        ]);
    } else {
        // مرحله 3: افزودن به کلکشن کاربر
        $stmt = $pdo->prepare("
            INSERT INTO user_vocabulary (word_id, user_id, question_id, category_id) 
            VALUES (?, ?, ?, ?)
        ");
        if (!$stmt) {
            throw new Exception('خطا در آماده‌سازی کوئری INSERT user_vocabulary: ' . implode(', ', $pdo->errorInfo()));
        }

        $result = $stmt->execute([$word_id, $user_id, $question_id, $category_id]);
        if (!$result) {
            throw new Exception('خطا در INSERT user_vocabulary: ' . implode(', ', $stmt->errorInfo()));
        }

        $user_vocab_id = $pdo->lastInsertId();

        $pdo->commit();

        echo json_encode([
            'success' => true,
            'message' => 'کلمه به کلکشن واژگان شما افزوده شد',
            'word_id' => $word_id,
            'user_vocab_id' => $user_vocab_id,
            'updated' => false
        ]);
    }

} catch (PDOException $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }

    $error_message = "خطای دیتابیس: " . $e->getMessage();
    error_log("Database error in save_vocabulary.php: " . $error_message);
    error_log("SQL State: " . $e->getCode());

    echo json_encode([
        'success' => false,
        'error' => 'خطا در ذخیره کلمه',
        'details' => $error_message, // برای دیباگ - بعداً حذف کنید
        'sql_state' => $e->getCode()
    ]);
} catch (Exception $e) {
    if (isset($pdo) && $pdo->inTransaction()) {
        $pdo->rollBack();
    }

    $error_message = $e->getMessage();
    error_log("General error in save_vocabulary.php: " . $error_message);

    echo json_encode([
        'success' => false,
        'error' => 'خطا در ذخیره کلمه',
        'details' => $error_message // برای دیباگ - بعداً حذف کنید
    ]);
}
?>