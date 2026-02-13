<?php
// get_question.php
require_once __DIR__ . '/questions.php';
require_once __DIR__ . '/../config/config.php';

header('Content-Type: application/json; charset=utf-8');

// Check if it's a POST request
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode([
        'success' => false,
        'message' => 'فقط درخواست POST مجاز است'
    ]);
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

if (!isset($_POST['question_id']) || empty($_POST['question_id'])) {
    echo json_encode([
        'success' => false,
        'message' => 'شناسه سوال ارسال نشده است'
    ]);
    exit;
}

$questionId = intval($_POST['question_id']);
$user_id = $_SESSION['user_id'] ?? null;

try {
    // دریافت exam_date_type کاربر
    $examDateType = getUserExamDateType($pdo, $user_id);
    
    // ساخت شرط فیلتر available
    $availableCondition = "";
    if ($examDateType === 'before') {
        $availableCondition = " AND (available = 0 OR available = 1)";
    } elseif ($examDateType === 'after') {
        $availableCondition = " AND (available = 0 OR available = 2)";
    }
    
    // دریافت سوال
    $stmt = $pdo->prepare("
        SELECT *
        FROM questions 
        WHERE id = :question_id" . $availableCondition
    );
    $stmt->bindValue(':question_id', (int) $questionId, PDO::PARAM_INT);
    $stmt->execute();
    $question = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$question) {
        echo json_encode([
            'success' => false,
            'message' => 'سوالی جهت نمایش وجود ندارد'
        ]);
        exit;
    }
    
    // بررسی دسترسی کاربر به این سوال بر اساس شماره سوال
    // محاسبه شماره سوال در لیست فیلتر شده
    $stmt = $pdo->prepare("
        SELECT COUNT(*) as question_number
        FROM questions 
        WHERE id <= :question_id" . $availableCondition
    );
    $stmt->bindValue(':question_id', (int) $questionId, PDO::PARAM_INT);
    $stmt->execute();
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    $question_number = $result['question_number'];
    
    // بررسی دسترسی
    if (!can_access_question($user_id, $question_number, $pdo)) {
        $limit = get_user_question_limit($user_id, $pdo);
        echo json_encode([
            'success' => false,
            'message' => "دسترسی به این سوال محدود شده است. در پلن رایگان فقط به {$limit} سوال اول دسترسی دارید. لطفاً برای دسترسی به تمام سوالات، اشتراک VIP تهیه کنید.",
            'requires_upgrade' => true,
            'current_limit' => $limit
        ]);
        exit;
    }
    
    echo json_encode([
        'success' => true,
        'question' => $question,
        'answers' => getAnswers($pdo, $questionId),
        'message' => 'سوال با موفقیت بارگذاری شد',
        'user_info' => [
            'is_vip' => is_user_vip($user_id, $pdo),
            'question_limit' => get_user_question_limit($user_id, $pdo),
            'accessible_questions' => getUserAccessibleQuestions($pdo, $user_id)
        ]
    ]);
    
} catch (PDOException $e) {
    error_log("Database error in get_question.php: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'خطای پایگاه داده رخ داده است'
    ]);
} catch (Exception $e) {
    error_log("General error in get_question.php: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'خطای سیستمی رخ داده است'
    ]);
}

function getAnswers(PDO $pdo, $questionId)
{
    try {
        $stmt = $pdo->prepare("
            SELECT a.*
            FROM answers a
            INNER JOIN questions q ON a.question_number = q.number
            WHERE q.id = :question_id
        ");
        $stmt->bindValue(':question_id', (int) $questionId, PDO::PARAM_INT);
        $stmt->execute();
        $answers = $stmt->fetchAll();
        
        if (!$answers) {
            return [];
        }
        
        return $answers;
        
    } catch (PDOException $e) {
        error_log("Database error getting answers: " . $e->getMessage());
        return [];
    }
}