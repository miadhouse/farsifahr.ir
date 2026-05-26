<?php
// get_preview_question.php
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

if (!isset($_POST['question_id']) || empty($_POST['question_id'])) {
    echo json_encode([
        'success' => false,
        'message' => 'شناسه سوال ارسال نشده است'
    ]);
    exit;
}

$questionId = intval($_POST['question_id']);

try {
    // Mock user config for preview
    $examDateType = 'before';
    $userLanguage = 'de';

    // ساخت شرط فیلتر available
    $availableCondition = "";
    if ($examDateType === 'before') {
        $availableCondition = " AND (available = 0 OR available = 1)";
    } elseif ($examDateType === 'after') {
        $availableCondition = " AND (available = 0 OR available = 2)";
    }

    $stmt = $pdo->prepare("SELECT * FROM questions WHERE id = ? $availableCondition");
    $stmt->execute([$questionId]);
    $question = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$question) {
        echo json_encode([
            'success' => false,
            'message' => 'سوال مورد نظر یافت نشد یا در دسترس نیست.'
        ]);
        exit;
    }

    $isVip = true;

    // دریافت زبان سوال
    $language = 'de';
    
    // دریافت ترجمه و توضیحات
    $questionTranslation = $question['farsi_text'] ?? '';
    $questionExplanation = $question['info'] ?? '';

    // دریافت پاسخ‌ها
    $stmt = $pdo->prepare("
        SELECT a.*
        FROM answers a
        INNER JOIN questions q ON a.question_number = q.number
        WHERE q.id = ?
        ORDER BY a.id ASC
    ");
    $stmt->execute([$questionId]);
    $answers = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $formattedAnswers = [];
    foreach ($answers as $answer) {
        $answerData = $answer;
        
        // اطمینان از وجود فیلد text و asw_pretext برای نمایش (طبق نیاز frontend)
        $answerData['text'] = $answer['text'] ?? ($answer['asw_de'] ?? '');
        $answerData['en_text'] = $answer['en_text'] ?? '';
        
        // دریافت ترجمه و توضیح پاسخ از ستون‌های خود جدول
        $answerData['translation'] = $answer['farsi_text'] ?? '';
        $answerData['explanation'] = $answer['info'] ?? ''; 
        
        $formattedAnswers[] = $answerData;
    }

    $response = [
        'success' => true,
        'question' => [
            'id' => $question['id'],
            'number' => $question['number'],
            'text' => $question['text'] ?? ($question['qst_de'] ?? ''),
            'en_text' => $question['en_text'] ?? '',
            'asw_pretext' => $question['asw_pretext'] ?? '',
            'asw_en' => $question['asw_en'] ?? '',
            'farsi_text' => $question['farsi_text'] ?? '',
            'info' => $question['info'] ?? '',
            'translation' => $questionTranslation,
            'explanation' => $questionExplanation,
            'picture' => $question['picture'] ?? ($question['image'] ?? ''),
            'video' => $question['video'] ?? '',
            'type' => $question['type'] ?? '',
            'points' => $question['points'] ?? ($question['pts'] ?? 0),
            'is_bookmarked' => false
        ],
        'answers' => $formattedAnswers
    ];

    echo json_encode($response);

} catch (Exception $e) {
    error_log("Error in get_preview_question: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'خطای سیستمی رخ داده است.'
    ]);
}
