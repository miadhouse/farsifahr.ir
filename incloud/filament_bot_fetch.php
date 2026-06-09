<?php
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

// Check if user is admin
$isAdmin = is_super_admin();
if (!$isAdmin) {
    echo json_encode(['success' => false, 'message' => 'دسترسی غیرمجاز']);
    exit;
}

$id = isset($_POST['id']) ? (int)$_POST['id'] : null;

if (!$id) {
    echo json_encode(['success' => false, 'message' => 'پارامترهای لازم ارسال نشده‌اند']);
    exit;
}

// Bootstrap Laravel to use its services
try {
    require __DIR__ . '/../miad/vendor/autoload.php';
    $app = require_once __DIR__ . '/../miad/bootstrap/app.php';
    $kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
    $kernel->bootstrap();
} catch (\Throwable $e) {
    echo json_encode(['success' => false, 'message' => 'خطا در راه‌اندازی سرویس‌های واکشی.']);
    exit;
}

try {
    $scraper = app(\App\Services\QuestionScraperService::class);
    
    // Get question details including existing translations
    $stmt = $pdo->prepare("SELECT id, number, text, asw_pretext, farsi_text, asw_farsi, info FROM questions WHERE id = ?");
    $stmt->execute([$id]);
    $question = $stmt->fetch();

    if (!$question) {
        echo json_encode(['success' => false, 'message' => 'سوال یافت نشد.']);
        exit;
    }

    // Scrape data from source
    $data = $scraper->scrape($question['number'], (string) $question['text']);
    
    $updatedInfo = false;
    $updatedQTrans = false;
    $updatedAswTrans = false;
    $savedAnswers = 0;
    $ansInfoCount = 0;
    
    // Translate Question Text (only if not already translated or if forced)
    $faQuestionText = $question['farsi_text'];
    if (!empty($question['text'])) {
        $trans = $scraper->translate($question['text']);
        if (!empty($trans)) {
            $faQuestionText = $trans;
            $updatedQTrans = true;
        }
    }

    // Translate Question Asw Pretext
    $faAswPretext = $question['asw_farsi'];
    if (!empty($question['asw_pretext'])) {
        $trans = $scraper->translate($question['asw_pretext']);
        if (!empty($trans)) {
            $faAswPretext = $trans;
            $updatedAswTrans = true;
        }
    }

    // Question Info
    $finalInfo = $question['info'];
    if (!empty($data['question_info'])) {
        $faQuestionInfo = $scraper->translate($data['question_info']);
        $finalInfo = $faQuestionInfo ? "<div dir='rtl'>{$faQuestionInfo}</div>" : $data['question_info'];
        $updatedInfo = true;
    }

    $updateQ = $pdo->prepare("UPDATE questions SET info = ?, farsi_text = ?, asw_farsi = ? WHERE id = ?");
    $updateQ->execute([$finalInfo, $faQuestionText, $faAswPretext, $id]);

    // Process Answers
    $stmtAns = $pdo->prepare("SELECT id, text, farsi_text, info FROM answers WHERE question_number = ?");
    $stmtAns->execute([$question['number']]);
    $dbAnswers = $stmtAns->fetchAll();

    foreach ($dbAnswers as $dbAnswer) {
        // Translate Answer Text
        $faAnsText = $dbAnswer['farsi_text'];
        if (!empty($dbAnswer['text'])) {
            $trans = $scraper->translate($dbAnswer['text']);
            if (!empty($trans)) {
                $faAnsText = $trans;
            }
        }

        $finalAnsInfo = $dbAnswer['info'];
        if (!empty($data['answers'])) {
            foreach ($data['answers'] as $scraped) {
                if (!empty($scraped['info'])) {
                    if ($dbAnswer['text'] && $scraper->textsMatch((string) $dbAnswer['text'], $scraped['text'])) {
                        $faAnsInfo = $scraper->translate($scraped['info']);
                        $finalAnsInfo = $faAnsInfo ? "<div dir='rtl'>{$faAnsInfo}</div>" : $scraped['info'];
                        $ansInfoCount++;
                        break;
                    }
                }
            }
        }

        $updateA = $pdo->prepare("UPDATE answers SET info = ?, farsi_text = ? WHERE id = ?");
        $updateA->execute([$finalAnsInfo, $faAnsText, $dbAnswer['id']]);
        $savedAnswers++;
    }

    $msg = "اطلاعات با موفقیت واکشی شد.\n";
    $msg .= "توضیح سوال: " . ($updatedInfo ? "بروز شد" : "تغییری نکرد") . "\n";
    $msg .= "ترجمه سوال: " . ($updatedQTrans ? "بروز شد" : "تغییری نکرد") . "\n";
    $msg .= "پاسخ‌ها: " . $savedAnswers . " مورد پردازش شد (" . $ansInfoCount . " مورد دارای توضیح جدید).";

    echo json_encode([
        'success' => true, 
        'message' => $msg
    ]);
} catch (\Throwable $e) {
    error_log("[BotFetch] Error for ID {$id}: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'خطا در واکشی: ' . $e->getMessage()]);
}

