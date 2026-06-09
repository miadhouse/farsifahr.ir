<?php
use Api\V1\Common\Response;
use Api\V1\Common\AuthMiddleware;

require_once __DIR__ . '/../../incloud/subscription-functions.php';

$user = AuthMiddleware::authenticate();
$userId = $user['id'];

$action = $action ?: '';

switch ($action) {
    case 'plans':
        handle_subscription_plans($pdo);
        break;
    case 'status':
        handle_subscription_status($pdo, $userId);
        break;
    default:
        Response::error('Action not found', 404);
}

function handle_subscription_plans($pdo) {
    try {
        $plans = get_all_subscription_plans($pdo);
        foreach ($plans as &$plan) {
            $plan['features'] = get_plan_features($plan['slug']);
            $plan['duration_options'] = get_vip_duration_options($plan);
        }
        Response::success($plans);
    } catch (Exception $e) {
        Response::error('Failed to fetch subscription plans: ' . $e->getMessage());
    }
}

function handle_subscription_status($pdo, $userId) {
    try {
        $subscription = get_user_active_subscription($userId, $pdo);
        $daysRemaining = get_days_until_expiry($userId, $pdo);
        
        Response::success([
            'is_vip' => is_user_vip($userId, $pdo),
            'subscription' => $subscription,
            'days_remaining' => $daysRemaining
        ]);
    } catch (Exception $e) {
        Response::error('Failed to fetch subscription status: ' . $e->getMessage());
    }
}
