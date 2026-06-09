<?php
/**
 * farsifahr Live Chat - Telegram Webhook Handler
 * Place at: /chat/api/telegram_webhook.php
 */

require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../incloud/functions.php';

$input = file_get_contents('php://input');
$update = json_decode($input, true);

if (!$update || !isset($update['message'])) {
    exit;
}

$message = $update['message'];
$chat_id = $message['chat']['id'];
$text = $message['text'] ?? '';

// Verify it's from the admin chat
if (defined('TELEGRAM_ADMIN_CHAT_ID') && $chat_id != TELEGRAM_ADMIN_CHAT_ID) {
    exit;
}

// Logic to identify which session to reply to:
// We look for Session ID in the replied message text or the current text
$session_id = null;

// 1. Check if it's a reply to a message containing "Session: #SXXX"
if (isset($message['reply_to_message'])) {
    $reply_text = $message['reply_to_message']['text'] ?? '';
    if (preg_match('/Session: #S(\d+)/', $reply_text, $matches)) {
        $session_id = intval($matches[1]);
    }
}

// 2. Alternatively, check if text starts with /r XXX message
if (!$session_id && preg_match('/^\/r\s+(\d+)\s+(.+)/s', $text, $matches)) {
    $session_id = intval($matches[1]);
    $text = $matches[2];
}

if ($session_id && !empty($text)) {
    // Sanitize and save to DB
    $clean_text = htmlspecialchars($text, ENT_QUOTES, 'UTF-8');
    
    $stmt = $pdo->prepare("INSERT INTO chat_messages (session_id, sender_type, message) VALUES (?, 'admin', ?)");
    if ($stmt->execute([$session_id, $clean_text])) {
        // Update session status
        $pdo->prepare("UPDATE chat_sessions SET status = 'active', admin_joined = 1, unread_user = unread_user + 1, updated_at = NOW() WHERE id = ?")
            ->execute([$session_id]);
            
        // Confirm back to Telegram
        send_telegram_admin_message("✅ پاسخ شما برای جلسه #S{$session_id} ارسال شد.");
    } else {
        send_telegram_admin_message("❌ خطا در ارسال پاسخ.");
    }
}

exit;
