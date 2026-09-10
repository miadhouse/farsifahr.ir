<?php
// update_question_video.php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/functions.php';

header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'فقط درخواست POST مجاز است']);
    exit;
}

// بررسی CSRF token
$token = $_POST['csrf_token'] ?? '';
if (!verify_csrf_token($token)) {
    echo json_encode(['success' => false, 'message' => 'توکن امنیتی نامعتبر است']);
    exit;
}

// بررسی دسترسی ادمین اصلی
$isAdmin = is_super_admin();
if (!$isAdmin) {
    echo json_encode(['success' => false, 'message' => 'دسترسی غیرمجاز']);
    exit;
}

$questionId = isset($_POST['question_id']) ? (int)$_POST['question_id'] : null;
$action = $_POST['action'] ?? ''; // 'upload' or 'delete'

if (!$questionId) {
    echo json_encode(['success' => false, 'message' => 'شناسه سوال معتبر نیست']);
    exit;
}

if ($action === 'delete') {
    try {
        // حذف فایل فیزیکی ویدیو در صورت وجود
        $stmt = $pdo->prepare("SELECT video_url FROM question_videos WHERE question_id = ?");
        $stmt->execute([$questionId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($row && !empty($row['video_url'])) {
            $filePath = __DIR__ . '/../storage/' . $row['video_url'];
            if (file_exists($filePath)) {
                @unlink($filePath);
            }
        }
        
        $stmt = $pdo->prepare("DELETE FROM question_videos WHERE question_id = ?");
        $stmt->execute([$questionId]);
        
        echo json_encode(['success' => true, 'message' => 'ویدیو آموزشی با موفقیت حذف شد']);
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'message' => 'خطا در حذف ویدیو: ' . $e->getMessage()]);
    }
    exit;
}

if ($action === 'upload') {
    if (!isset($_FILES['video_file']) || $_FILES['video_file']['error'] !== UPLOAD_ERR_OK) {
        echo json_encode(['success' => false, 'message' => 'فایل ویدیو به درستی آپلود نشده است یا حجم آن خیلی زیاد است']);
        exit;
    }
    
    $file = $_FILES['video_file'];
    $allowedTypes = ['video/mp4', 'video/quicktime', 'video/x-msvideo', 'video/x-matroska'];
    $fileType = mime_content_type($file['tmp_name']);
    
    if (!in_array($fileType, $allowedTypes)) {
        echo json_encode(['success' => false, 'message' => 'فرمت فایل معتبر نیست. فقط ویدیوهای mp4, mov, avi, mkv مجاز هستند.']);
        exit;
    }
    
    $maxSize = 100 * 1024 * 1024; // 100MB
    if ($file['size'] > $maxSize) {
        echo json_encode(['success' => false, 'message' => 'اندازه فایل ویدیو نباید بیشتر از 100 مگابایت باشد.']);
        exit;
    }
    
    $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
    $newFileName = 'video_' . $questionId . '_' . time() . '.' . $extension;
    $uploadDir = __DIR__ . '/../storage/question_videos/';
    
    if (!is_dir($uploadDir)) {
        @mkdir($uploadDir, 0755, true);
    }
    
    $destination = $uploadDir . $newFileName;
    
    if (move_uploaded_file($file['tmp_name'], $destination)) {
        $dbPath = 'question_videos/' . $newFileName;
        
        try {
            // حذف ویدیو قبلی در صورت وجود فیزیکی
            $stmt = $pdo->prepare("SELECT video_url FROM question_videos WHERE question_id = ?");
            $stmt->execute([$questionId]);
            $oldRow = $stmt->fetch(PDO::FETCH_ASSOC);
            if ($oldRow && !empty($oldRow['video_url'])) {
                $oldFilePath = __DIR__ . '/../storage/' . $oldRow['video_url'];
                if (file_exists($oldFilePath)) {
                    @unlink($oldFilePath);
                }
            }
            
            // ذخیره یا بروزرسانی در دیتابیس
            $stmt = $pdo->prepare("INSERT INTO question_videos (question_id, video_url, title) VALUES (?, ?, ?) 
                ON DUPLICATE KEY UPDATE video_url = ?, updated_at = NOW()");
            $title = 'ویدیو آموزشی سوال ' . $questionId;
            $stmt->execute([$questionId, $dbPath, $title, $dbPath]);
            
            echo json_encode([
                'success' => true, 
                'message' => 'ویدیو آموزشی با موفقیت آپلود و ذخیره شد',
                'video_url' => $dbPath
            ]);
        } catch (PDOException $e) {
            echo json_encode(['success' => false, 'message' => 'خطا در ذخیره دیتابیس: ' . $e->getMessage()]);
        }
    } else {
        echo json_encode(['success' => false, 'message' => 'خطا در ذخیره‌سازی فایل روی سرور']);
    }
    exit;
}

echo json_encode(['success' => false, 'message' => 'عملیات نامعتبر']);
