<?php
require_once __DIR__ . '/../config/config.php';

function fetchQuestionInfo($questionCode) {
    // تمیز کردن کد سوال برای جستجو
    $searchQuery = urlencode($questionCode . " führerschein-bestehen.de");
    $googleUrl = "https://www.google.com/search?q=" . $searchQuery;

    // تنظیمات برای جلوگیری از بلاک شدن (User Agent)
    $options = [
        'http' => [
            'method' => "GET",
            'header' => "User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/119.0.0.0 Safari/537.36\r\n"
        ]
    ];
    $context = stream_context_create($options);
    
    // ۱. پیدا کردن لینک در گوگل (در محیط واقعی بهتر است از API یا جستجوی مستقیم استفاده شود)
    $googleHtml = file_get_contents($googleUrl, false, $context);
    if (!$googleHtml) return ['success' => false, 'message' => 'Google search failed'];

    // پیدا کردن اولین لینک مربوط به سایت مورد نظر
    preg_match('/https:\/\/www\.fuehrerschein-bestehen\.de\/fragenkatalog\/[^\s&"]+/', $googleHtml, $matches);
    if (empty($matches)) return ['success' => false, 'message' => 'Target website link not found in Google'];
    
    $targetUrl = $matches[0];

    // ۲. دریافت صفحه اصلی سوال
    $html = file_get_contents($targetUrl, false, $context);
    if (!$html) return ['success' => false, 'message' => 'Failed to load target page'];

    // استفاده از DOMDocument برای پارس کردن
    $doc = new DOMDocument();
    @$doc->loadHTML('<?xml encoding="UTF-8">' . $html);
    $xpath = new DOMXPath($doc);

    $data = [
        'main_explanation' => '',
        'answers' => []
    ];

    // ۳. استخراج توضیح اصلی سوال
    // توضیح اصلی معمولا اولین fsb-erklaerung__text است که داخل یک ظرف خاص نیست
    $mainExplNodes = $xpath->query("//div[@id='fsb-fragentexte']/div[contains(@class, 'fsb-erklaerung')]//div[contains(@class, 'fsb-erklaerung__text')]");
    if ($mainExplNodes->length > 0) {
        $data['main_explanation'] = trim($mainExplNodes->item(0)->nodeValue);
    }

    // ۴. استخراج پاسخ‌ها و توضیحات آن‌ها
    $answerItems = $xpath->query("//div[contains(@class, 'fsb-answer-item')]");
    foreach ($answerItems as $item) {
        $textNode = $xpath->query(".//div[contains(@class, 'fsb-antwort__text')]", $item);
        $explNode = $xpath->query(".//div[contains(@class, 'fsb-erklaerung__text')]", $item);
        
        if ($textNode->length > 0) {
            $ansText = trim($textNode->item(0)->nodeValue);
            $ansExpl = ($explNode->length > 0) ? trim($explNode->item(0)->nodeValue) : '';
            $data['answers'][] = [
                'text' => $ansText,
                'explanation' => $ansExpl
            ];
        }
    }

    return ['success' => true, 'data' => $data, 'url' => $targetUrl];
}

// اگر درخواست Ajax باشد
if (isset($_POST['question_id']) && isset($_POST['action']) && $_POST['action'] == 'bot_fetch') {
    header('Content-Type: application/json');
    $qId = intval($_POST['question_id']);
    
    // دریافت کد سوال از دیتابیس
    $stmt = $pdo->prepare("SELECT id, number FROM questions WHERE id = ?");
    $stmt->execute([$qId]);
    $question = $stmt->fetch();

    if (!$question) {
        echo json_encode(['success' => false, 'message' => 'Question not found']);
        exit;
    }

    $result = fetchQuestionInfo($question['number']);
    
    if ($result['success']) {
        try {
            $pdo->beginTransaction();
            
            // آپدیت توضیح اصلی سوال
            $updateQ = $pdo->prepare("UPDATE questions SET info = ? WHERE id = ?");
            $updateQ->execute([$result['data']['main_explanation'], $qId]);

            // آپدیت توضیحات پاسخ‌ها
            // نکته: در اینجا فرض بر این است که پاسخ‌ها در جدول answers هستند
            $stmtAns = $pdo->prepare("SELECT id, text FROM answers WHERE question_number = (SELECT number FROM questions WHERE id = ?)");
            $stmtAns->execute([$qId]);
            $dbAnswers = $stmtAns->fetchAll();

            foreach ($result['data']['answers'] as $scrapedAns) {
                foreach ($dbAnswers as $dbAns) {
                    // مچ کردن متن پاسخ (ممکن است نیاز به تمیزکاری یا مقایسه درصدی باشد)
                    if (trim($dbAns['text']) == $scrapedAns['text'] || strpos($scrapedAns['text'], trim($dbAns['text'])) !== false) {
                        $updateA = $pdo->prepare("UPDATE answers SET info = ? WHERE id = ?");
                        $updateA->execute([$scrapedAns['explanation'], $dbAns['id']]);
                    }
                }
            }

            $pdo->commit();
            echo json_encode(['success' => true, 'message' => 'اطلاعات با موفقیت از سایت Führerschein-bestehen استخراج و ذخیره شد.']);
        } catch (Exception $e) {
            $pdo->rollBack();
            echo json_encode(['success' => false, 'message' => 'خطا در ذخیره‌سازی: ' . $e->getMessage()]);
        }
    } else {
        echo json_encode($result);
    }
    exit;
}
