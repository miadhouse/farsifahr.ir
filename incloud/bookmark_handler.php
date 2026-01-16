<?php
require_once __DIR__ . '/../config/config.php';


// بررسی ورود کاربر (فرض بر این است که user_id در session ذخیره شده)
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'کاربر وارد نشده است']);
    exit;
}

// بررسی پارامترهای ورودی
if (!isset($_POST['question_id']) || !isset($_POST['action'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'پارامترهای لازم ارسال نشده']);
    exit;
}

$user_id = $_SESSION['user_id'];
$question_id = $_POST['question_id'];
$action = $_POST['action']; // 'add' یا 'remove'

try {
    if ($action === 'add') {
        // اضافه کردن نشانه‌گذاری
        $stmt = $pdo->prepare("INSERT IGNORE INTO bookmarked_questions (user_id, question_id) VALUES (?, ?)");
        $stmt->execute([$user_id, $question_id]);
        
        if ($stmt->rowCount() > 0) {
            echo json_encode(['success' => true, 'action' => 'added', 'message' => 'سوال نشانه‌گذاری شد']);
        } else {
            echo json_encode(['success' => true, 'action' => 'exists', 'message' => 'سوال از قبل نشانه‌گذاری شده']);
        }
        
    } elseif ($action === 'remove') {
        // حذف نشانه‌گذاری
        $stmt = $pdo->prepare("DELETE FROM bookmarked_questions WHERE user_id = ? AND question_id = ?");
        $stmt->execute([$user_id, $question_id]);
        
        if ($stmt->rowCount() > 0) {
            echo json_encode(['success' => true, 'action' => 'removed', 'message' => 'نشانه‌گذاری حذف شد']);
        } else {
            echo json_encode(['success' => false, 'message' => 'نشانه‌گذاری یافت نشد']);
        }
        
    } elseif ($action === 'check') {
        // بررسی وضعیت نشانه‌گذاری
        $stmt = $pdo->prepare("SELECT id FROM bookmarked_questions WHERE user_id = ? AND question_id = ?");
        $stmt->execute([$user_id, $question_id]);
        
        $isBookmarked = $stmt->fetch() !== false;
        
        echo json_encode([
            'success' => true, 
            'is_bookmarked' => $isBookmarked,
            'message' => $isBookmarked ? 'نشانه‌گذاری شده' : 'نشانه‌گذاری نشده'
        ]);
        
    } else {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'عملیات نامعتبر']);
    }
    
} catch(PDOException $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'خطا در انجام عملیات: ' . $e->getMessage()]);
}
