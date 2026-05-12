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
    $questionTranslation = '';
    $questionExplanation = '';
    
    // در حالت پیش‌نمایش، دسترسی کامل می‌دهیم
    $stmt = $pdo->prepare("SELECT content FROM qa_translations WHERE question_id = ? AND lang = ? AND type = 'translation'");
    $stmt->execute([$questionId, $language]);
    $questionTranslation = $stmt->fetchColumn() ?: '';

    $stmt = $pdo->prepare("SELECT content FROM qa_translations WHERE question_id = ? AND lang = ? AND type = 'explanation'");
    $stmt->execute([$questionId, $language]);
    $questionExplanation = $stmt->fetchColumn() ?: '';

    // دریافت پاسخ‌ها
    $stmt = $pdo->prepare("SELECT * FROM answers WHERE question_id = ? ORDER BY id ASC");
    $stmt->execute([$questionId]);
    $answers = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $formattedAnswers = [];
    foreach ($answers as $answer) {
        $answerData = [
            'id' => $answer['id'],
            'asw_corr' => $answer['asw_corr'],
            'content' => $answer['asw_de'] ?: ($answer['asw_en'] ?: $answer['asw_es']),
            'type' => $answer['type'],
        ];

        // دریافت ترجمه و توضیح پاسخ در صورت VIP بودن
        $answerData['translation'] = '';
        $answerData['explanation'] = '';
        
        $stmt = $pdo->prepare("SELECT content FROM qa_translations WHERE answer_id = ? AND lang = ? AND type = 'translation'");
        $stmt->execute([$answer['id'], $language]);
        $answerData['translation'] = $stmt->fetchColumn() ?: '';

        $stmt = $pdo->prepare("SELECT content FROM qa_translations WHERE answer_id = ? AND lang = ? AND type = 'explanation'");
        $stmt->execute([$answer['id'], $language]);
        $answerData['explanation'] = $stmt->fetchColumn() ?: '';
        
        $formattedAnswers[] = $answerData;
    }

    $response = [
        'success' => true,
        'question' => [
            'id' => $question['id'],
            'content' => $question['qst_de'] ?: ($question['qst_en'] ?: $question['qst_es']),
            'translation' => $questionTranslation,
            'explanation' => $questionExplanation,
            'image' => $question['image'],
            'video' => $question['video'],
            'type' => $question['type'],
            'points' => $question['pts'],
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
