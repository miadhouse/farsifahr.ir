<?php
require_once __DIR__ . '/functions.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit();
}

$post_id = isset($_POST['post_id']) ? (int)$_POST['post_id'] : 0;
$parent_id = isset($_POST['parent_id']) && !empty($_POST['parent_id']) ? (int)$_POST['parent_id'] : null;
$name = isset($_POST['name']) ? trim($_POST['name']) : '';
$email = isset($_POST['email']) ? trim($_POST['email']) : '';
$content = isset($_POST['content']) ? trim($_POST['content']) : '';

if (empty($name) || empty($email) || empty($content) || $post_id === 0) {
    echo json_encode(['success' => false, 'message' => 'لطفاً تمامی فیلدها را پر کنید.']);
    exit();
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    echo json_encode(['success' => false, 'message' => 'ایمیل وارد شده معتبر نیست.']);
    exit();
}

try {
    $stmt = $pdo->prepare("INSERT INTO comments (post_id, parent_id, author_name, author_email, content, status, created_at, updated_at) VALUES (?, ?, ?, ?, ?, 'pending', NOW(), NOW())");
    $stmt->execute([$post_id, $parent_id, $name, $email, $content]);

    // Get post title for notification
    $stmtPost = $pdo->prepare("SELECT title FROM posts WHERE id = ?");
    $stmtPost->execute([$post_id]);
    $post = $stmtPost->fetch();
    $post_title = $post ? $post['title'] : 'نامشخص';

    // Send Telegram Notification
    $tg_message = $parent_id ? "💬 <b>پاسخ جدید در وبلاگ</b>\n\n" : "💬 <b>نظر جدید در وبلاگ</b>\n\n";
    $tg_message .= "📌 <b>مطلب:</b> " . $post_title . "\n";
    $tg_message .= "👤 <b>فرستنده:</b> " . $name . " (" . $email . ")\n";
    $tg_message .= "📝 <b>متن:</b>\n" . $content . "\n\n";
    $tg_message .= "⏳ منتظر تایید در پنل مدیریت...";

    send_telegram_admin_message($tg_message);

    echo json_encode(['success' => true, 'message' => 'نظر شما با موفقیت ثبت شد و پس از تایید مدیریت نمایش داده خواهد شد.']);
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'خطا در ثبت نظر: ' . $e->getMessage()]);
}
