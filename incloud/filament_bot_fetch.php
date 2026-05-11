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
$isAdmin = isset($_SESSION['role']) && $_SESSION['role'] === 'admin';
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
    
    // Get question details
    $stmt = $pdo->prepare("SELECT id, number, text, asw_pretext FROM questions WHERE id = ?");
    $stmt->execute([$id]);
    $question = $stmt->fetch();

    if (!$question) {
        echo json_encode(['success' => false, 'message' => 'سوال یافت نشد.']);
        exit;
    }

    $data = $scraper->scrape($question['number'], (string) $question['text']);
    
    $savedInfo = false;
    $savedAnswers = 0;
    
    // Translate Question Text
    $faQuestionText = '';
    if (!empty($question['text'])) {
        $faQuestionText = $scraper->translate($question['text']);
    }

    // Translate Question Asw Pretext
    $faAswPretext = '';
    if (!empty($question['asw_pretext'])) {
        $faAswPretext = $scraper->translate($question['asw_pretext']);
    }

    // Translate Question Info (if any scraped)
    $finalInfo = null;
    if (!empty($data['question_info'])) {
        $faQuestionInfo = $scraper->translate($data['question_info']);
        $finalInfo = $faQuestionInfo ? "<div dir='rtl'>{$faQuestionInfo}</div>" : $data['question_info'];
    }

    if ($finalInfo !== null) {
        $updateQ = $pdo->prepare("UPDATE questions SET info = ?, farsi_text = ?, asw_farsi = ? WHERE id = ?");
        $updateQ->execute([$finalInfo, $faQuestionText, $faAswPretext, $id]);
    } else {
        $updateQ = $pdo->prepare("UPDATE questions SET farsi_text = ?, asw_farsi = ? WHERE id = ?");
        $updateQ->execute([$faQuestionText, $faAswPretext, $id]);
    }
    $savedInfo = true;

    $stmtAns = $pdo->prepare("SELECT id, text FROM answers WHERE question_number = ?");
    $stmtAns->execute([$question['number']]);
    $dbAnswers = $stmtAns->fetchAll();

    foreach ($dbAnswers as $dbAnswer) {
        // Translate Answer Text
        $faAnsText = '';
        if (!empty($dbAnswer['text'])) {
            $faAnsText = $scraper->translate($dbAnswer['text']);
        }

        $finalAnsInfo = null;
        foreach ($data['answers'] as $scraped) {
            if (!empty($scraped['info'])) {
                if ($dbAnswer['text'] && $scraper->textsMatch((string) $dbAnswer['text'], $scraped['text'])) {
                    $faAnsInfo = $scraper->translate($scraped['info']);
                    $finalAnsInfo = $faAnsInfo ? "<div dir='rtl'>{$faAnsInfo}</div>" : $scraped['info'];
                    break;
                }
            }
        }

        if ($finalAnsInfo !== null) {
            $updateA = $pdo->prepare("UPDATE answers SET info = ?, farsi_text = ? WHERE id = ?");
            $updateA->execute([$finalAnsInfo, $faAnsText, $dbAnswer['id']]);
        } else {
            $updateA = $pdo->prepare("UPDATE answers SET farsi_text = ? WHERE id = ?");
            $updateA->execute([$faAnsText, $dbAnswer['id']]);
        }
        $savedAnswers++;
    }

    echo json_encode([
        'success' => true, 
        'message' => 'اطلاعات با موفقیت واکشی و ترجمه شد. (سوال: ' . ($savedInfo ? 'بله' : 'خیر') . ' | پاسخ‌ها: ' . $savedAnswers . ' مورد). لطفاً صفحه را رفرش کنید یا سوال را دوباره باز کنید.'
    ]);
} catch (\Throwable $e) {
    echo json_encode(['success' => false, 'message' => 'خطا در واکشی: ' . $e->getMessage()]);
}
