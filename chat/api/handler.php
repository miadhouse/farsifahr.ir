<?php
/**
 * FarsiFahr Live Chat - API Handler
 * Place at: /chat/api/handler.php
 */

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');

require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../incloud/functions.php';

$action = $_POST['action'] ?? $_GET['action'] ?? '';

// ==================== HELPERS ====================

function get_chat_ip() {
    if (!empty($_SERVER['HTTP_CLIENT_IP'])) return $_SERVER['HTTP_CLIENT_IP'];
    if (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) return $_SERVER['HTTP_X_FORWARDED_FOR'];
    return $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
}

function is_chat_admin() {
    return isset($_SESSION['email']) && $_SESSION['email'] === 'miadaleali@gmail.com';
}

function get_or_create_session($pdo, $token = null) {
    if ($token) {
        $stmt = $pdo->prepare("SELECT * FROM chat_sessions WHERE session_token = ?");
        $stmt->execute([$token]);
        $session = $stmt->fetch();
        if ($session) {
            // Update last seen
            $pdo->prepare("UPDATE chat_sessions SET last_seen = NOW(), is_online = 1 WHERE id = ?")
                ->execute([$session['id']]);
            return $session;
        }
    }
    return null;
}

function sanitize_message($msg) {
    return htmlspecialchars(trim($msg), ENT_QUOTES, 'UTF-8');
}

// ==================== ACTIONS ====================

switch ($action) {

    // --- User: Start or resume session ---
    case 'init':
        $token = $_POST['token'] ?? null;
        $session = $token ? get_or_create_session($pdo, $token) : null;

        if (!$session) {
            // Create new session
            $new_token = bin2hex(random_bytes(24));
            $user_id = $_SESSION['user_id'] ?? null;
            $guest_name = null;
            $guest_email = null;

            if ($user_id) {
                $stmt = $pdo->prepare("SELECT name, email FROM users WHERE id = ?");
                $stmt->execute([$user_id]);
                $user = $stmt->fetch();
                $guest_name = $user['name'] ?? null;
                $guest_email = $user['email'] ?? null;
            }

            $stmt = $pdo->prepare("
                INSERT INTO chat_sessions (session_token, user_id, guest_name, guest_email, ip_address, user_agent, page_url, status)
                VALUES (?, ?, ?, ?, ?, ?, ?, 'waiting')
            ");
            $stmt->execute([
                $new_token,
                $user_id,
                $guest_name,
                $guest_email,
                get_chat_ip(),
                substr($_SERVER['HTTP_USER_AGENT'] ?? '', 0, 255),
                substr($_POST['page_url'] ?? '', 0, 500)
            ]);

            $session_id = $pdo->lastInsertId();

            // Insert welcome bot message
            $welcome = 'سلام! 👋 به پشتیبانی فارسی‌فهر خوش آمدید. پیام خود را بنویسید تا در اولین فرصت پاسخ دهیم.';
            if (!$user_id) {
                $welcome = 'سلام! 👋 برای شروع چت، لطفاً ایمیل و نامتان را وارد کنید.';
            }

            $pdo->prepare("INSERT INTO chat_messages (session_id, sender_type, message) VALUES (?, 'system', ?)")
                ->execute([$session_id, $welcome]);

            $stmt = $pdo->prepare("SELECT * FROM chat_sessions WHERE id = ?");
            $stmt->execute([$session_id]);
            $session = $stmt->fetch();
        }

        // Get messages
        $msgs = $pdo->prepare("SELECT * FROM chat_messages WHERE session_id = ? ORDER BY created_at ASC");
        $msgs->execute([$session['id']]);
        $messages = $msgs->fetchAll();

        // Mark user messages as read if admin
        if (is_chat_admin()) {
            $pdo->prepare("UPDATE chat_messages SET is_read = 1 WHERE session_id = ? AND sender_type = 'user'")
                ->execute([$session['id']]);
            $pdo->prepare("UPDATE chat_sessions SET unread_admin = 0 WHERE id = ?")
                ->execute([$session['id']]);
        }

        echo json_encode([
            'success' => true,
            'token' => $session['session_token'],
            'session' => [
                'id' => $session['id'],
                'status' => $session['status'],
                'guest_name' => $session['guest_name'],
                'guest_email' => $session['guest_email'],
                'needs_info' => !$_SESSION['user_id'] && !$session['guest_email'],
            ],
            'messages' => array_map(fn($m) => [
                'id' => $m['id'],
                'type' => $m['sender_type'],
                'message' => $m['message'],
                'time' => date('H:i', strtotime($m['created_at'])),
            ], $messages)
        ]);
        break;

    // --- User: Submit guest info ---
    case 'set_guest_info':
        $token = $_POST['token'] ?? '';
        $session = get_or_create_session($pdo, $token);
        if (!$session) { echo json_encode(['success' => false, 'message' => 'Session not found']); break; }

        $name = sanitize_message($_POST['name'] ?? '');
        $email = filter_var(trim($_POST['email'] ?? ''), FILTER_VALIDATE_EMAIL);

        if (!$name || !$email) {
            echo json_encode(['success' => false, 'message' => 'نام و ایمیل معتبر وارد کنید']);
            break;
        }

        $pdo->prepare("UPDATE chat_sessions SET guest_name = ?, guest_email = ? WHERE id = ?")
            ->execute([$name, $email, $session['id']]);

        // Replace system message
        $pdo->prepare("DELETE FROM chat_messages WHERE session_id = ? AND sender_type = 'system'")
            ->execute([$session['id']]);
        $pdo->prepare("INSERT INTO chat_messages (session_id, sender_type, message) VALUES (?, 'system', ?)")
            ->execute([$session['id'], "سلام {$name} عزیز! 👋 به پشتیبانی فارسی‌فهر خوش آمدید. پیامتان را بنویسید."]);

        echo json_encode(['success' => true]);
        break;

    // --- User/Admin: Send message ---
    case 'send':
        $token = $_POST['token'] ?? '';
        $message = trim($_POST['message'] ?? '');
        $session_id = intval($_POST['session_id'] ?? 0);

        if (empty($message)) { echo json_encode(['success' => false]); break; }
        if (mb_strlen($message) > 2000) { echo json_encode(['success' => false, 'message' => 'پیام خیلی طولانی است']); break; }

        $message = htmlspecialchars($message, ENT_QUOTES, 'UTF-8');

        if (is_chat_admin()) {
            // Admin sending to a specific session
            if (!$session_id) { echo json_encode(['success' => false]); break; }
            $stmt = $pdo->prepare("SELECT * FROM chat_sessions WHERE id = ?");
            $stmt->execute([$session_id]);
            $session = $stmt->fetch();
            if (!$session) { echo json_encode(['success' => false]); break; }

            $pdo->prepare("INSERT INTO chat_messages (session_id, sender_type, message) VALUES (?, 'admin', ?)")
                ->execute([$session_id, $message]);
            $pdo->prepare("UPDATE chat_sessions SET status = 'active', admin_joined = 1, unread_user = unread_user + 1, updated_at = NOW() WHERE id = ?")
                ->execute([$session_id]);

        } else {
            // User sending
            $session = get_or_create_session($pdo, $token);
            if (!$session) { echo json_encode(['success' => false]); break; }

            $user_id = $_SESSION['user_id'] ?? null;
            $pdo->prepare("INSERT INTO chat_messages (session_id, sender_type, sender_id, message) VALUES (?, 'user', ?, ?)")
                ->execute([$session['id'], $user_id, $message]);
            $pdo->prepare("UPDATE chat_sessions SET unread_admin = unread_admin + 1, updated_at = NOW() WHERE id = ?")
                ->execute([$session['id']]);
        }

        echo json_encode(['success' => true]);
        break;

    // --- Poll for new messages ---
    case 'poll':
        $token = $_POST['token'] ?? '';
        $last_id = intval($_POST['last_id'] ?? 0);
        $session_id = intval($_POST['session_id'] ?? 0);

        if (is_chat_admin() && $session_id) {
            $stmt = $pdo->prepare("SELECT * FROM chat_messages WHERE session_id = ? AND id > ? ORDER BY created_at ASC");
            $stmt->execute([$session_id, $last_id]);

            // Mark admin unread as read
            $pdo->prepare("UPDATE chat_messages SET is_read = 1 WHERE session_id = ? AND sender_type = 'user' AND id > ?")
                ->execute([$session_id, $last_id]);
            $pdo->prepare("UPDATE chat_sessions SET unread_admin = 0 WHERE id = ?")
                ->execute([$session_id]);
        } else {
            $session = get_or_create_session($pdo, $token);
            if (!$session) { echo json_encode(['success' => false, 'messages' => []]); break; }

            $stmt = $pdo->prepare("SELECT * FROM chat_messages WHERE session_id = ? AND id > ? AND sender_type IN ('admin','system') ORDER BY created_at ASC");
            $stmt->execute([$session['id'], $last_id]);

            // Mark user messages as read
            $pdo->prepare("UPDATE chat_messages SET is_read = 1 WHERE session_id = ? AND sender_type IN ('admin','system')")
                ->execute([$session['id']]);
            $pdo->prepare("UPDATE chat_sessions SET unread_user = 0 WHERE id = ?")
                ->execute([$session['id']]);
        }

        $messages = $stmt->fetchAll();
        echo json_encode([
            'success' => true,
            'messages' => array_map(fn($m) => [
                'id' => $m['id'],
                'type' => $m['sender_type'],
                'message' => $m['message'],
                'time' => date('H:i', strtotime($m['created_at'])),
            ], $messages)
        ]);
        break;

    // --- Admin: Get sessions list ---
    case 'get_sessions':
        if (!is_chat_admin()) { echo json_encode(['success' => false]); break; }

        $filter = $_GET['filter'] ?? 'all';
        $whereClause = '';
        if ($filter === 'waiting') $whereClause = "WHERE cs.status = 'waiting'";
        elseif ($filter === 'active') $whereClause = "WHERE cs.status = 'active'";
        elseif ($filter === 'online') $whereClause = "WHERE cs.last_seen >= DATE_SUB(NOW(), INTERVAL 3 MINUTE)";

        $stmt = $pdo->query("
            SELECT cs.*,
                   u.name as user_name_db,
                   (SELECT message FROM chat_messages WHERE session_id = cs.id ORDER BY created_at DESC LIMIT 1) as last_message,
                   (SELECT created_at FROM chat_messages WHERE session_id = cs.id ORDER BY created_at DESC LIMIT 1) as last_message_time
            FROM chat_sessions cs
            LEFT JOIN users u ON cs.user_id = u.id
            {$whereClause}
            ORDER BY cs.updated_at DESC
            LIMIT 100
        ");
        $sessions = $stmt->fetchAll();

        echo json_encode([
            'success' => true,
            'sessions' => array_map(fn($s) => [
                'id' => $s['id'],
                'name' => $s['user_name_db'] ?? $s['guest_name'] ?? 'مهمان',
                'email' => $s['guest_email'] ?? '',
                'status' => $s['status'],
                'is_online' => strtotime($s['last_seen']) > (time() - 180),
                'unread' => $s['unread_admin'],
                'last_message' => $s['last_message'] ? mb_substr(html_entity_decode($s['last_message']), 0, 60) : '',
                'last_time' => $s['last_message_time'] ? date('H:i', strtotime($s['last_message_time'])) : '',
                'is_member' => !empty($s['user_id']),
                'created_at' => date('Y/m/d H:i', strtotime($s['created_at'])),
            ], $sessions),
            'stats' => [
                'total_online' => $pdo->query("SELECT COUNT(*) FROM chat_sessions WHERE last_seen >= DATE_SUB(NOW(), INTERVAL 3 MINUTE)")->fetchColumn(),
                'waiting' => $pdo->query("SELECT COUNT(*) FROM chat_sessions WHERE status = 'waiting' AND unread_admin > 0")->fetchColumn(),
                'active' => $pdo->query("SELECT COUNT(*) FROM chat_sessions WHERE status = 'active'")->fetchColumn(),
            ]
        ]);
        break;

    // --- Admin: Get messages for a session ---
    case 'get_session_messages':
        if (!is_chat_admin()) { echo json_encode(['success' => false]); break; }
        $session_id = intval($_GET['session_id'] ?? 0);

        $stmt = $pdo->prepare("SELECT cs.*, u.name as user_name_db FROM chat_sessions cs LEFT JOIN users u ON cs.user_id = u.id WHERE cs.id = ?");
        $stmt->execute([$session_id]);
        $session = $stmt->fetch();
        if (!$session) { echo json_encode(['success' => false]); break; }

        $msgs = $pdo->prepare("SELECT * FROM chat_messages WHERE session_id = ? ORDER BY created_at ASC");
        $msgs->execute([$session_id]);

        $pdo->prepare("UPDATE chat_messages SET is_read = 1 WHERE session_id = ? AND sender_type = 'user'")->execute([$session_id]);
        $pdo->prepare("UPDATE chat_sessions SET unread_admin = 0 WHERE id = ?")->execute([$session_id]);

        echo json_encode([
            'success' => true,
            'session' => [
                'id' => $session['id'],
                'name' => $session['user_name_db'] ?? $session['guest_name'] ?? 'مهمان',
                'email' => $session['guest_email'] ?? '',
                'status' => $session['status'],
                'is_member' => !empty($session['user_id']),
                'is_online' => strtotime($session['last_seen']) > (time() - 180),
                'created_at' => date('Y/m/d H:i', strtotime($session['created_at'])),
            ],
            'messages' => array_map(fn($m) => [
                'id' => $m['id'],
                'type' => $m['sender_type'],
                'message' => $m['message'],
                'time' => date('H:i', strtotime($m['created_at'])),
                'date' => date('Y/m/d', strtotime($m['created_at'])),
            ], $msgs->fetchAll())
        ]);
        break;

    // --- Admin: Close session ---
    case 'close_session':
        if (!is_chat_admin()) { echo json_encode(['success' => false]); break; }
        $session_id = intval($_POST['session_id'] ?? 0);

        $pdo->prepare("UPDATE chat_sessions SET status = 'closed' WHERE id = ?")->execute([$session_id]);
        $pdo->prepare("INSERT INTO chat_messages (session_id, sender_type, message) VALUES (?, 'system', 'چت توسط پشتیبانی بسته شد. ممنون از تماس شما. 🙏')")->execute([$session_id]);

        echo json_encode(['success' => true]);
        break;

    // --- Admin: Get quick replies ---
    case 'get_quick_replies':
        if (!is_chat_admin()) { echo json_encode(['success' => false]); break; }
        $stmt = $pdo->query("SELECT * FROM chat_quick_replies WHERE is_active = 1 ORDER BY sort_order ASC");
        echo json_encode(['success' => true, 'replies' => $stmt->fetchAll()]);
        break;

    // --- Heartbeat: user still online ---
    case 'heartbeat':
        $token = $_POST['token'] ?? '';
        if ($token) {
            $pdo->prepare("UPDATE chat_sessions SET last_seen = NOW(), is_online = 1 WHERE session_token = ?")
                ->execute([$token]);

            // Check for unread messages
            $stmt = $pdo->prepare("SELECT unread_user, status FROM chat_sessions WHERE session_token = ?");
            $stmt->execute([$token]);
            $session = $stmt->fetch();
            echo json_encode(['success' => true, 'unread' => $session['unread_user'] ?? 0, 'status' => $session['status'] ?? 'waiting']);
        } else {
            echo json_encode(['success' => false]);
        }
        break;

    // --- Admin: Get total unread count ---
    case 'admin_unread':
        if (!is_chat_admin()) { echo json_encode(['count' => 0]); break; }
        $count = $pdo->query("SELECT SUM(unread_admin) FROM chat_sessions WHERE status != 'closed'")->fetchColumn();
        echo json_encode(['count' => intval($count)]);
        break;

    default:
        echo json_encode(['success' => false, 'message' => 'Invalid action']);
}
