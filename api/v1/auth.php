<?php
use Api\V1\Common\Response;
use Api\V1\Common\AuthMiddleware;

$action = $action ?: '';

switch ($action) {
    case 'login':
        handle_api_login($pdo);
        break;
    case 'register':
        handle_api_register($pdo);
        break;
    case 'forgot-password':
        handle_api_forgot_password($pdo);
        break;
    case 'me':
        $user = AuthMiddleware::authenticate();
        unset($user['password']);
        Response::success($user);
        break;
    default:
        Response::error('Action not found', 404);
}

function handle_api_login($pdo) {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        Response::error('Method not allowed', 405);
    }

    $input = json_decode(file_get_contents('php://input'), true);
    $email = trim($input['email'] ?? '');
    $password = $input['password'] ?? '';

    if (empty($email) || empty($password)) {
        Response::error('Email and password are required');
    }

    $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
    $stmt->execute([$email]);
    $user = $stmt->fetch();

    if (!$user || !password_verify($password, $user['password'])) {
        Response::error('Invalid email or password');
    }

    if ($user['email_verified'] == 0) {
        Response::error('Email not verified', 403, ['status' => 'unverified']);
    }

    // Generate api_token if not exists or always refresh? Let's refresh for security.
    $api_token = generate_token(64);
    $stmt = $pdo->prepare("UPDATE users SET api_token = ? WHERE id = ?");
    $stmt->execute([$api_token, $user['id']]);

    $user['api_token'] = $api_token;
    unset($user['password']);
    unset($user['verification_token']);
    unset($user['reset_token']);
    unset($user['reset_expires']);

    Response::success($user, 'Login successful');
}

function handle_api_register($pdo) {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        Response::error('Method not allowed', 405);
    }

    $input = json_decode(file_get_contents('php://input'), true);
    $name = trim($input['name'] ?? '');
    $email = trim($input['email'] ?? '');
    $password = $input['password'] ?? '';
    $password_confirm = $input['password_confirm'] ?? '';

    if (empty($name) || empty($email) || empty($password)) {
        Response::error('All fields are required');
    }

    if (!validate_email($email)) {
        Response::error('Invalid email format');
    }

    if (!validate_password($password)) {
        Response::error('Password must be at least 8 characters and include uppercase, lowercase and numbers');
    }

    if ($password !== $password_confirm) {
        Response::error('Passwords do not match');
    }

    $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
    $stmt->execute([$email]);
    if ($stmt->rowCount() > 0) {
        Response::error('Email already registered');
    }

    try {
        $hashed_password = hash_password($password);
        $api_token = generate_token(64);
        
        // Auto-verify email for now as requested for the app prototype or keep same as web
        // The web code had: VALUES (?, ?, ?, ?, ?, 1) which means it was auto-verified (1)
        
        $stmt = $pdo->prepare("
            INSERT INTO users (name, email, password, email_verified, api_token) 
            VALUES (?, ?, ?, 1, ?)
        ");
        $stmt->execute([$name, $email, $hashed_password, $api_token]);

        $user_id = $pdo->lastInsertId();
        
        $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
        $stmt->execute([$user_id]);
        $user = $stmt->fetch();
        
        unset($user['password']);
        Response::success($user, 'Registration successful');

    } catch (PDOException $e) {
        Response::error('Registration failed: ' . $e->getMessage());
    }
}

function handle_api_forgot_password($pdo) {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        Response::error('Method not allowed', 405);
    }

    $input = json_decode(file_get_contents('php://input'), true);
    $email = trim($input['email'] ?? '');

    if (empty($email) || !validate_email($email)) {
        Response::error('Valid email is required');
    }

    $stmt = $pdo->prepare("SELECT id, name FROM users WHERE email = ?");
    $stmt->execute([$email]);
    $user = $stmt->fetch();

    if (!$user) {
        // Uniform message for security
        Response::success(null, 'If the email exists, a reset link has been sent');
        return;
    }

    $reset_token = generate_token();
    $reset_expires = date('Y-m-d H:i:s', time() + PASSWORD_RESET_EXPIRY);

    $stmt = $pdo->prepare("UPDATE users SET reset_token = ?, reset_expires = ? WHERE id = ?");
    $stmt->execute([$reset_token, $reset_expires, $user['id']]);

    require_once __DIR__ . '/../../incloud/mail-functions.php';
    send_password_reset_email($email, $user['name'], $reset_token);

    Response::success(null, 'Reset link sent to your email');
}
