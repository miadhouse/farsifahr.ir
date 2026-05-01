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

session_start();
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

    // Scrape original explanations (German) from the site
    $data = [];
    try {
        $data = $scraper->scrape($question['number'], (string) $question['text']);
    } catch (\Exception $e) {
        // Ignore if scraping fails
    }
    
    $stmtAns = $pdo->prepare("SELECT id, text FROM answers WHERE question_number = ?");
    $stmtAns->execute([$question['number']]);
    $dbAnswers = $stmtAns->fetchAll();

    // Prepare JSON structure for Gemini
    $promptData = [
        'question' => [
            'text' => $question['text'],
            'asw_pretext' => $question['asw_pretext'] ?? '',
            'info' => $data['question_info'] ?? ''
        ],
        'answers' => []
    ];

    foreach ($dbAnswers as $dbAnswer) {
        $scrapedInfo = '';
        if (!empty($data['answers'])) {
            foreach ($data['answers'] as $scraped) {
                if (!empty($scraped['info']) && $dbAnswer['text'] && $scraper->textsMatch((string) $dbAnswer['text'], $scraped['text'])) {
                    $scrapedInfo = $scraped['info'];
                    break;
                }
            }
        }
        $promptData['answers'][] = [
            'id' => $dbAnswer['id'],
            'text' => $dbAnswer['text'],
            'info' => $scrapedInfo
        ];
    }

    $promptText = "You are an expert translator for German driving license exams. Your task is to translate driving questions and their explanations from German into natural, high-quality Persian (Farsi). Keep in mind the context of German traffic rules.\n"
                . "First, analyze the ENTIRE scenario to understand the context. Then, translate all the string values in the provided JSON to Persian.\n\n"
                . "CRITICAL RULES:\n"
                . "1. Output ONLY a valid JSON object matching the exact structure below, but with the string values replaced by their Persian translation.\n"
                . "2. Do NOT change any keys or IDs.\n"
                . "3. If a value is an empty string, leave it empty.\n"
                . "4. Output proper Persian text.\n"
                . "5. IMPORTANT: Translate the 'info' fields ONLY if they contain text in the provided JSON. Do NOT add or make up explanations if the original 'info' field is empty. If an 'info' field contains German text, you MUST translate it completely.\n\n"
                . "Here is the JSON to translate:\n" . json_encode($promptData, JSON_UNESCAPED_UNICODE);

    $apiKey = "AIzaSyADjcpet-WVDpeMlZtIoXo2BZsjDPRfuh8";
    $model = "gemini-2.5-flash";
    $url = "https://generativelanguage.googleapis.com/v1beta/models/{$model}:generateContent?key={$apiKey}";

    $payload = [
        "contents" => [
            ["parts" => [["text" => $promptText]]]
        ],
        "generationConfig" => [
            "responseMimeType" => "application/json",
            "temperature" => 0.1
        ]
    ];

    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json'
    ]);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_TIMEOUT, 60);

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    $responseData = json_decode($response, true);
    
    if ($httpCode !== 200 || !isset($responseData['candidates'][0]['content']['parts'][0]['text'])) {
        $errMsg = $responseData['error']['message'] ?? 'Unknown Gemini Error';
        echo json_encode(['success' => false, 'message' => "خطا در ارتباط با سرویس Gemini: " . $errMsg]);
        exit;
    }

    $translatedJsonText = trim($responseData['candidates'][0]['content']['parts'][0]['text']);
    
    // Remove markdown if Gemini included it despite our prompt
    $translatedJsonText = preg_replace('/^```json\s*/i', '', $translatedJsonText);
    $translatedJsonText = preg_replace('/\s*```$/i', '', $translatedJsonText);
    
    $translatedData = json_decode($translatedJsonText, true);

    if (!$translatedData || !isset($translatedData['question'])) {
        echo json_encode(['success' => false, 'message' => 'خروجی Gemini نامعتبر بود.']);
        exit;
    }

    $pdo->beginTransaction();

    // Update Question
    $q = $translatedData['question'];
    $finalInfo = !empty($q['info']) ? "<div dir='rtl'>{$q['info']}</div>" : null;
    
    if ($finalInfo !== null) {
        $updateQ = $pdo->prepare("UPDATE questions SET info = ?, farsi_text = ?, asw_farsi = ? WHERE id = ?");
        $updateQ->execute([$finalInfo, $q['text'] ?? '', $q['asw_pretext'] ?? '', $id]);
    } else {
        $updateQ = $pdo->prepare("UPDATE questions SET farsi_text = ?, asw_farsi = ? WHERE id = ?");
        $updateQ->execute([$q['text'] ?? '', $q['asw_pretext'] ?? '', $id]);
    }

    // Update Answers
    if (isset($translatedData['answers']) && is_array($translatedData['answers'])) {
        foreach ($translatedData['answers'] as $ans) {
            $ansId = $ans['id'];
            $finalAnsInfo = !empty($ans['info']) ? "<div dir='rtl'>{$ans['info']}</div>" : null;
            
            if ($finalAnsInfo !== null) {
                $updateA = $pdo->prepare("UPDATE answers SET info = ?, farsi_text = ? WHERE id = ?");
                $updateA->execute([$finalAnsInfo, $ans['text'] ?? '', $ansId]);
            } else {
                $updateA = $pdo->prepare("UPDATE answers SET farsi_text = ? WHERE id = ?");
                $updateA->execute([$ans['text'] ?? '', $ansId]);
            }
        }
    }

    $pdo->commit();

    echo json_encode([
        'success' => true, 
        'message' => 'اطلاعات واکشی شد و ترجمه با درک مطلب (Gemini 2.5) با موفقیت انجام شد.'
    ]);
} catch (\Throwable $e) {
    if (isset($pdo) && $pdo->inTransaction()) {
        $pdo->rollBack();
    }
    echo json_encode(['success' => false, 'message' => 'خطای سیستمی: ' . $e->getMessage()]);
}
