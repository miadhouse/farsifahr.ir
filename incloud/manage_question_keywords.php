<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/functions.php';

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

$isAdmin = is_super_admin();
if (!$isAdmin) {
    echo json_encode(['success' => false, 'message' => 'دسترسی غیرمجاز']);
    exit;
}

$action = $_POST['action'] ?? '';
$question_id = isset($_POST['question_id']) ? (int)$_POST['question_id'] : 0;
$keyword = trim($_POST['keyword'] ?? '');

if (!$question_id) {
    echo json_encode(['success' => false, 'message' => 'شناسه سوال نامعتبر است']);
    exit;
}

if (empty($keyword)) {
    echo json_encode(['success' => false, 'message' => 'کلمه کلیدی نمی‌تواند خالی باشد']);
    exit;
}

try {
    if ($action === 'create') {
        $translation = trim($_POST['translation'] ?? '');
        if (empty($translation)) {
            echo json_encode(['success' => false, 'message' => 'ترجمه نمی‌تواند خالی باشد']);
            exit;
        }

        // Check if keyword already exists for this question
        $stmt = $pdo->prepare("SELECT id FROM question_keywords WHERE question_id = ? AND keyword = ?");
        $stmt->execute([$question_id, $keyword]);
        $existsId = $stmt->fetchColumn();

        if ($existsId) {
            // Update translation
            $update = $pdo->prepare("UPDATE question_keywords SET translation = ? WHERE id = ?");
            $update->execute([$translation, $existsId]);
        } else {
            // Insert new keyword
            $insert = $pdo->prepare("INSERT INTO question_keywords (question_id, keyword, translation) VALUES (?, ?, ?)");
            $insert->execute([$question_id, $keyword, $translation]);
        }

        echo json_encode(['success' => true, 'message' => 'کلمه کلیدی با موفقیت ثبت شد']);
    } 
    elseif ($action === 'delete') {
        $delete = $pdo->prepare("DELETE FROM question_keywords WHERE question_id = ? AND keyword = ?");
        $delete->execute([$question_id, $keyword]);

        echo json_encode(['success' => true, 'message' => 'کلمه کلیدی با موفقیت حذف شد']);
    } else {
        echo json_encode(['success' => false, 'message' => 'عملیات نامعتبر است']);
    }
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'خطای پایگاه داده: ' . $e->getMessage()]);
}
