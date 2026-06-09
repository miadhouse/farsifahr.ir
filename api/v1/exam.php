<?php
use Api\V1\Common\Response;
use Api\V1\Common\AuthMiddleware;

require_once __DIR__ . '/../../incloud/user-config-handler.php';
require_once __DIR__ . '/../../incloud/subscription-functions.php';

$user = AuthMiddleware::authenticate();
$userId = $user['id'];

$action = $action ?: '';

switch ($action) {
    case 'simulator':
        handle_exam_simulator($pdo, $userId);
        break;
    case 'submit':
        handle_exam_submit($pdo, $userId);
        break;
    default:
        Response::error('Action not found', 404);
}

function handle_exam_simulator($pdo, $userId) {
    if (!is_user_vip($userId, $pdo)) {
        Response::error('VIP subscription required for exam simulator', 403);
    }

    try {
        $configHandler = new UserConfigHandler($pdo);
        $userConfig = $configHandler->getUserConfig($userId);
        $examDateType = $userConfig ? $userConfig['exam_date_type'] : 'before';

        // 20 questions from Grundstoff (id = 0)
        $grundstoffQuestions = getCategoryQuestionsForExam($pdo, 0, $examDateType);
        shuffle($grundstoffQuestions);
        $grundstoffSelected = array_slice($grundstoffQuestions, 0, 20);

        // 10 questions from Zusatzstoff (id = 1)
        $zusatzstoffQuestions = getCategoryQuestionsForExam($pdo, 1, $examDateType);
        shuffle($zusatzstoffQuestions);
        $zusatzstoffSelected = array_slice($zusatzstoffQuestions, 0, 10);

        if (count($grundstoffSelected) < 20 || count($zusatzstoffSelected) < 10) {
            Response::error('Not enough questions to generate an exam');
        }

        $allQuestions = array_merge($grundstoffSelected, $zusatzstoffSelected);
        
        // For each question, get answers
        foreach ($allQuestions as &$q) {
            $stmt = $pdo->prepare("
                SELECT a.*
                FROM answers a
                INNER JOIN questions qu ON a.question_number = qu.number
                WHERE qu.id = ?
            ");
            $stmt->execute([$q['id']]);
            $q['answers'] = $stmt->fetchAll();
        }

        Response::success($allQuestions);
    } catch (Exception $e) {
        Response::error('Failed to generate exam: ' . $e->getMessage());
    }
}

function getCategoryQuestionsForExam($pdo, $parentId, $examDateType) {
    $availableCondition = ($examDateType === 'before') ? " AND (available = 0 OR available = 1)" : " AND (available = 0 OR available = 2)";
    
    // Get all subcategories
    $stmt = $pdo->prepare("
        WITH RECURSIVE subcategories AS (
            SELECT id FROM categories WHERE parent_id = ?
            UNION ALL
            SELECT c.id FROM categories c INNER JOIN subcategories s ON c.parent_id = s.id
        )
        SELECT id FROM subcategories
    ");
    $stmt->execute([$parentId]);
    $categoryIds = $stmt->fetchAll(PDO::FETCH_COLUMN);
    $categoryIds[] = $parentId;

    if (empty($categoryIds)) return [];

    $questions = [];
    foreach ($categoryIds as $catId) {
        $pattern = "%," . $catId . ",%";
        $stmt = $pdo->prepare("SELECT id, number, text, picture, points FROM questions WHERE category_id LIKE ?" . $availableCondition);
        $stmt->execute([$pattern]);
        $questions = array_merge($questions, $stmt->fetchAll());
    }

    // Remove duplicates by ID
    $uniqueQuestions = [];
    foreach ($questions as $q) {
        $uniqueQuestions[$q['id']] = $q;
    }
    return array_values($uniqueQuestions);
}

function handle_exam_submit($pdo, $userId) {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        Response::error('Method not allowed', 405);
    }

    $input = json_decode(file_get_contents('php://input'), true);
    
    $score = (int)($input['score'] ?? 0);
    $total_questions = (int)($input['total_questions'] ?? 30);
    $correct_count = (int)($input['correct_count'] ?? 0);
    $error_points = (int)($input['error_points'] ?? 0);
    $five_point_errors = (int)($input['five_point_errors'] ?? 0);
    $passed = (int)($input['passed'] ?? 0);
    $wrong_questions = json_encode($input['wrong_questions'] ?? []);
    $all_questions = json_encode($input['all_questions'] ?? []);

    try {
        $stmt = $pdo->prepare("
            INSERT INTO exam_history (
                user_id, score, total_questions, correct_count, error_points, 
                five_point_errors, passed, wrong_questions, all_questions
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");
        
        $stmt->execute([
            $userId, $score, $total_questions, $correct_count, $error_points,
            $five_point_errors, $passed, $wrong_questions, $all_questions
        ]);

        Response::success(['id' => $pdo->lastInsertId()], 'Exam history saved');
    } catch (Exception $e) {
        Response::error('Failed to save exam history: ' . $e->getMessage());
    }
}
