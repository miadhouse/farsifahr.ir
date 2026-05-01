<?php
require_once __DIR__ . '/../config/config.php';

header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'فقط درخواست POST مجاز است']);
    exit;
}

// بررسی CSRF token
if (!isset($_POST['csrf_token']) || empty($_POST['csrf_token'])) {
    echo json_encode(['success' => false, 'message' => 'توکن امنیتی ارسال نشده است']);
    exit;
}

$isAdmin = isset($_SESSION['role']) && $_SESSION['role'] === 'admin';
if (!$isAdmin) {
    echo json_encode(['success' => false, 'message' => 'دسترسی غیرمجاز']);
    exit;
}

$action = $_POST['action'] ?? '';
$question_id = isset($_POST['question_id']) ? (int)$_POST['question_id'] : 0;

if (!$question_id) {
    echo json_encode(['success' => false, 'message' => 'شناسه سوال نامعتبر است']);
    exit;
}

try {
    if ($action === 'fetch') {
        // Fetch all available tags
        $stmt = $pdo->query("SELECT id, name, color FROM question_tags ORDER BY name ASC");
        $allTags = $stmt->fetchAll();

        // Fetch attached tags for this question
        $stmt2 = $pdo->prepare("
            SELECT t.id, t.name, t.color 
            FROM question_tags t 
            JOIN question_question_tag qt ON t.id = qt.question_tag_id 
            WHERE qt.question_id = ?
        ");
        $stmt2->execute([$question_id]);
        $questionTags = $stmt2->fetchAll();

        echo json_encode(['success' => true, 'all_tags' => $allTags, 'question_tags' => $questionTags]);
    } 
    elseif ($action === 'toggle') {
        $tag_id = isset($_POST['tag_id']) ? (int)$_POST['tag_id'] : 0;
        if (!$tag_id) {
             echo json_encode(['success' => false, 'message' => 'شناسه تگ نامعتبر است']);
             exit;
        }
        
        // Check if attached
        $stmt = $pdo->prepare("SELECT id FROM question_question_tag WHERE question_id = ? AND question_tag_id = ?");
        $stmt->execute([$question_id, $tag_id]);
        $exists = $stmt->fetchColumn();
        
        if ($exists) {
            // Detach
            $del = $pdo->prepare("DELETE FROM question_question_tag WHERE question_id = ? AND question_tag_id = ?");
            $del->execute([$question_id, $tag_id]);
            $is_attached = false;
        } else {
            // Attach
            $ins = $pdo->prepare("INSERT INTO question_question_tag (question_id, question_tag_id) VALUES (?, ?)");
            $ins->execute([$question_id, $tag_id]);
            $is_attached = true;
        }
        
        echo json_encode(['success' => true, 'is_attached' => $is_attached]);
    }
    elseif ($action === 'create_and_attach') {
        $tag_name = trim($_POST['tag_name'] ?? '');
        $color = trim($_POST['color'] ?? 'primary');
        
        if (empty($tag_name)) {
            echo json_encode(['success' => false, 'message' => 'نام تگ نمی‌تواند خالی باشد']);
            exit;
        }
        
        // Check if tag already exists
        $stmt = $pdo->prepare("SELECT id FROM question_tags WHERE name = ?");
        $stmt->execute([$tag_name]);
        $tag_id = $stmt->fetchColumn();
        
        if (!$tag_id) {
            // Create new tag
            $insTag = $pdo->prepare("INSERT INTO question_tags (name, color, created_at, updated_at) VALUES (?, ?, NOW(), NOW())");
            $insTag->execute([$tag_name, $color]);
            $tag_id = $pdo->lastInsertId();
        }
        
        // Attach if not already attached
        $stmt2 = $pdo->prepare("SELECT id FROM question_question_tag WHERE question_id = ? AND question_tag_id = ?");
        $stmt2->execute([$question_id, $tag_id]);
        if (!$stmt2->fetchColumn()) {
            $insQt = $pdo->prepare("INSERT INTO question_question_tag (question_id, question_tag_id) VALUES (?, ?)");
            $insQt->execute([$question_id, $tag_id]);
        }
        
        echo json_encode(['success' => true, 'tag_id' => $tag_id, 'name' => $tag_name, 'color' => $color]);
    } else {
         echo json_encode(['success' => false, 'message' => 'عملیات نامعتبر است']);
    }
} catch (\PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'خطای دیتابیس: ' . $e->getMessage()]);
}
