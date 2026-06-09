<?php
use Api\V1\Common\Response;
use Api\V1\Common\AuthMiddleware;

require_once __DIR__ . '/../../incloud/user-config-handler.php';
require_once __DIR__ . '/../../incloud/subscription-functions.php';

$user = AuthMiddleware::authenticate();
$userId = $user['id'];

$action = $action ?: '';

switch ($action) {
    case 'categories':
        handle_practice_categories($pdo, $userId);
        break;
    case 'questions':
        handle_practice_questions($pdo, $userId);
        break;
    case 'question-details':
        handle_question_details($pdo, $userId);
        break;
    case 'submit-answer':
        handle_submit_answer($pdo, $userId);
        break;
    default:
        Response::error('Action not found', 404);
}

function handle_practice_categories($pdo, $userId) {
    try {
        $stmt = $pdo->prepare("SELECT * FROM categories ORDER BY parent_id, index_code");
        $stmt->execute();
        $categories = $stmt->fetchAll();
        Response::success($categories);
    } catch (Exception $e) {
        Response::error('Failed to fetch categories: ' . $e->getMessage());
    }
}

function handle_practice_questions($pdo, $userId) {
    $categoryId = $_GET['category_id'] ?? '';
    if (empty($categoryId)) {
        Response::error('Category ID is required');
    }

    try {
        $configHandler = new UserConfigHandler($pdo);
        $userConfig = $configHandler->getUserConfig($userId);
        $examDateType = $userConfig ? $userConfig['exam_date_type'] : 'before';

        $availableCondition = ($examDateType === 'before') ? " AND (available = 0 OR available = 1)" : " AND (available = 0 OR available = 2)";

        // Note: category_id in DB seems to be like ",49," (csv-like string)
        $stmt = $pdo->prepare("
            SELECT q.id, q.number, q.points, q.text, q.picture,
                   uqs.correct, uqs.incorrect
            FROM questions q
            LEFT JOIN user_question_stats uqs ON q.id = uqs.question_id AND uqs.user_id = ?
            WHERE q.category_id LIKE :cat" . $availableCondition . "
            ORDER BY q.id
        ");
        $stmt->execute(['user_id' => $userId, 'cat' => "%," . $categoryId . ",%"]);
        $questions = $stmt->fetchAll();

        Response::success($questions);
    } catch (Exception $e) {
        Response::error('Failed to fetch questions: ' . $e->getMessage());
    }
}

function handle_question_details($pdo, $userId) {
    $questionId = $_GET['question_id'] ?? '';
    if (empty($questionId)) {
        Response::error('Question ID is required');
    }

    try {
        $stmt = $pdo->prepare("SELECT * FROM questions WHERE id = ?");
        $stmt->execute([$questionId]);
        $question = $stmt->fetch();

        if (!$question) {
            Response::error('Question not found', 404);
        }

        // Get answers
        $stmt = $pdo->prepare("
            SELECT a.*
            FROM answers a
            INNER JOIN questions q ON a.question_number = q.number
            WHERE q.id = ?
        ");
        $stmt->execute([$questionId]);
        $answers = $stmt->fetchAll();

        $question['answers'] = $answers;

        Response::success($question);
    } catch (Exception $e) {
        Response::error('Failed to fetch question details: ' . $e->getMessage());
    }
}

function handle_submit_answer($pdo, $userId) {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        Response::error('Method not allowed', 405);
    }

    $input = json_decode(file_get_contents('php://input'), true);
    $questionId = (int)($input['question_id'] ?? 0);
    $isCorrect = (bool)($input['is_correct'] ?? false);

    if (!$questionId) {
        Response::error('Question ID is required');
    }

    try {
        // Logic from update_answer_status.php
        $stmt = $pdo->prepare("SELECT correct, incorrect FROM user_question_stats WHERE user_id = ? AND question_id = ?");
        $stmt->execute([$userId, $questionId]);
        $existing = $stmt->fetch();

        if ($existing) {
            $currentCorrect = (int) $existing['correct'];
            $currentIncorrect = (int) $existing['incorrect'];

            if ($isCorrect) {
                $newCorrect = min(2, $currentCorrect + 1);
                $newIncorrect = ($newCorrect >= 2) ? 0 : max(0, $currentIncorrect - 1);
            } else {
                $newIncorrect = min(2, $currentIncorrect + 1);
                $newCorrect = ($newIncorrect >= 2) ? 0 : $currentCorrect;
            }

            $stmt = $pdo->prepare("UPDATE user_question_stats SET correct = ?, incorrect = ?, updated_at = NOW() WHERE user_id = ? AND question_id = ?");
            $stmt->execute([$newCorrect, $newIncorrect, $userId, $questionId]);
        } else {
            $newCorrect = $isCorrect ? 1 : 0;
            $newIncorrect = $isCorrect ? 0 : 1;
            $stmt = $pdo->prepare("INSERT INTO user_question_stats (user_id, question_id, correct, incorrect, updated_at) VALUES (?, ?, ?, ?, NOW())");
            $stmt->execute([$userId, $questionId, $newCorrect, $newIncorrect]);
        }

        Response::success(['correct' => $newCorrect, 'incorrect' => $newIncorrect], 'Answer submitted');
    } catch (Exception $e) {
        Response::error('Failed to submit answer: ' . $e->getMessage());
    }
}
