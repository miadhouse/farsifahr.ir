<?php
require_once __DIR__ . '/../../incloud/functions.php';
require_once __DIR__ . '/common/response.php';
require_once __DIR__ . '/common/auth_middleware.php';

use Api\V1\Common\Response;

// Handle CORS
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit;
}

header("X-API-Handler: V1-Index");
$request_uri = $_SERVER['REQUEST_URI'];
$path = parse_url($request_uri, PHP_URL_PATH);

// Find the part after /api/v1
$base_segment = '/api/v1';
$pos = strpos($path, $base_segment);
if ($pos !== false) {
    $path = substr($path, $pos + strlen($base_segment));
}
$path = trim($path, '/');

$parts = explode('/', $path);
$resource = $parts[0] ?? '';
$action = $parts[1] ?? '';

switch ($resource) {
    case 'auth':
        require_once __DIR__ . '/auth.php';
        break;
    case 'dashboard':
        require_once __DIR__ . '/dashboard.php';
        break;
    case 'practice':
        require_once __DIR__ . '/practice.php';
        break;
    case 'exam':
        require_once __DIR__ . '/exam.php';
        break;
    case 'vocabulary':
        require_once __DIR__ . '/vocabulary.php';
        break;
    default:
        Response::error('Resource not found', 404);
}
