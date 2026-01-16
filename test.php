<?php
require_once __DIR__ . '/incloud/questions.php';
require_once __DIR__ . '/config/config.php';
$questionId = "153";
try {
    $stmt = $pdo->prepare("
        SELECT 
           *
        FROM questions 
        WHERE id = :question_id
    ");
    
    $stmt->bindValue(':question_id', $questionId, PDO::PARAM_INT);
    $stmt->execute();
    
    $question = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$question) {
        echo json_encode([
            'success' => false,
            'message' => 'سوال یافت نشد یا دسترسی به آن مجاز نیست'
        ]);
        exit;
    }

    if (!empty($question['image'])) {
        $question['image'] = 'assets/images/' . $question['image'];
    }
    
    if (!empty($question['video'])) {

        $question['video'] = 'assets/videos/' . $question['video'];
    }


    
    echo json_encode([
        'success' => true,
        'question' => $question,
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
?>