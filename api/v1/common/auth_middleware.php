<?php
namespace Api\V1\Common;

require_once __DIR__ . '/../../../incloud/functions.php';
require_once __DIR__ . '/response.php';

class AuthMiddleware {
    public static function authenticate() {
        $headers = getallheaders();
        $authHeader = $headers['Authorization'] ?? $headers['authorization'] ?? '';

        if (preg_match('/Bearer\s(\S+)/', $authHeader, $matches)) {
            $token = $matches[1];
            global $pdo;
            
            $stmt = $pdo->prepare("SELECT * FROM users WHERE api_token = ?");
            $stmt->execute([$token]);
            $user = $stmt->fetch();

            if ($user) {
                return $user;
            }
        }

        Response::error('Unauthorized', 401);
    }
}
