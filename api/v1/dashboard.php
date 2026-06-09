<?php
use Api\V1\Common\Response;
use Api\V1\Common\AuthMiddleware;

require_once __DIR__ . '/../../incloud/user-config-handler.php';

$user = AuthMiddleware::authenticate();
$userId = $user['id'];

$action = $action ?: 'stats';

switch ($action) {
    case 'stats':
        handle_dashboard_stats($pdo, $userId);
        break;
    default:
        Response::error('Action not found', 404);
}

function handle_dashboard_stats($pdo, $userId) {
    try {
        $configHandler = new UserConfigHandler($pdo);
        $userConfig = $configHandler->getUserConfig($userId);

        if (!$userConfig) {
            Response::error('User configuration missing', 400, ['needs_config' => true]);
        }

        $availableFilter = ($userConfig['exam_date_type'] === 'before') ? 1 : 2;

        // Total questions
        $stmt = $pdo->prepare("
            SELECT COUNT(*) as total_questions 
            FROM questions 
            WHERE available = 0 OR available = ?
        ");
        $stmt->execute([$availableFilter]);
        $totalQuestions = (int)$stmt->fetch()['total_questions'];

        // Stats
        $stmt = $pdo->prepare("
            SELECT 
                COUNT(*) as total_answered,
                SUM(CASE WHEN uqs.correct >= 2 AND uqs.incorrect = 0 THEN 1 ELSE 0 END) as green_count,
                SUM(CASE WHEN uqs.correct = 1 AND uqs.incorrect = 0 THEN 1 ELSE 0 END) as blue_count,
                SUM(CASE WHEN (uqs.correct > 0 AND uqs.incorrect > 0) OR (uqs.correct = 1 AND uqs.incorrect >= 1) THEN 1 ELSE 0 END) as yellow_count,
                SUM(CASE WHEN (uqs.incorrect >= 2 AND uqs.correct = 0) OR (uqs.incorrect = 1 AND uqs.correct = 0) THEN 1 ELSE 0 END) as red_count
            FROM user_question_stats uqs
            INNER JOIN questions q ON uqs.question_id = q.id
            WHERE uqs.user_id = ? 
            AND (q.available = 0 OR q.available = ?)
        ");
        $stmt->execute([$userId, $availableFilter]);
        $stats = $stmt->fetch();

        $notAnswered = $totalQuestions - (int) $stats['total_answered'];

        $readyScore =
            ((int) $stats['green_count'] * 100) +
            ((int) $stats['blue_count'] * 50) +
            ((int) $stats['yellow_count'] * 25);

        $readinessPercentage = $totalQuestions > 0 ? round(($readyScore / ($totalQuestions * 100)) * 100) : 0;

        Response::success([
            'total_questions' => $totalQuestions,
            'total_answered' => (int) $stats['total_answered'],
            'not_answered' => $notAnswered,
            'green_count' => (int) $stats['green_count'],
            'blue_count' => (int) $stats['blue_count'],
            'yellow_count' => (int) $stats['yellow_count'],
            'red_count' => (int) $stats['red_count'],
            'readiness_percentage' => (int)$readinessPercentage,
            'config' => [
                'exam_date_type' => $userConfig['exam_date_type'],
            ]
        ]);

    } catch (Exception $e) {
        Response::error('Failed to fetch dashboard stats: ' . $e->getMessage(), 500);
    }
}
