<?php
//get_question.php
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

    $stmt = $pdo->prepare("
        SELECT 
           *
        FROM questions 
        WHERE id = :question_id" . $availableCondition . " LIMIT 10
    ");

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

    echo json_encode([
        'success' => true,
        'question' => $question,
        'answers' => getAnswers($pdo, $questionId),
        'message' => 'سوال با موفقیت بارگذاری شد'
    ]);

} catch (PDOException $e) {
    error_log("Database error in get_question.php: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
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
            SELECT 
               *
            FROM answers 
            WHERE question_id = :question_id
        ");

        $stmt->bindValue(':question_id', (int) $questionId, PDO::PARAM_INT);
        $stmt->execute();

        $answers = $stmt->fetchAll();

        if (!$answers) {
            echo json_encode([
                'success' => false,
                'message' => 'پاسخی جهت نمایش وجود ندارد'
            ]);
            exit;
        }
        return $answers;

    } catch (PDOException $e) {
        error_log("Database error in get_question.php: " . $e->getMessage());
        echo json_encode([
            'success' => false,
            'message' => $e->getMessage()
        ]);
    } catch (Exception $e) {
        error_log("General error in get_question.php: " . $e->getMessage());
        echo json_encode([
            'success' => false,
            'message' => 'خطای سیستمی رخ داده است'
        ]);
    }
}