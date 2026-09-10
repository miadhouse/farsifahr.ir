<?php
//google_translate.php
require_once __DIR__ . '/../config/config.php';

header('Content-Type: application/json; charset=utf-8');
// بررسی CSRF token
if (!isset($_POST['csrf_token']) || empty($_POST['csrf_token'])) {
    echo json_encode([
        'success' => false,
        'message' => 'توکن امنیتی ارسال نشده است'
    ]);
    exit;
}
if (!isset($_POST['text']) || empty(trim($_POST['text']))) {
    echo json_encode(['success' => false, 'error' => 'متن ارسال نشده است']);
    exit;
}

$text = trim($_POST['text']);
$text = trim($text, " \t\n\r\0\x0B.,?!;:\"'()[]{}«»");
$from = $_POST['from'] ?? 'de';
$to = $_POST['to'] ?? 'fa';

// بررسی طول متن
if (strlen($text) < 2 || strlen($text) > 150) {
    echo json_encode(['success' => false, 'error' => 'طول کلمه یا عبارت باید بین 2 تا 150 کاراکتر باشد']);
    exit;
}

// بررسی تعداد کلمات (اجازه تا ۶ کلمه برای عبارات و اصطلاحات)
$words = preg_split('/\s+/', $text);
if (count($words) > 6) {
    echo json_encode(['success' => false, 'error' => 'حداکثر تا ۶ کلمه قابل ترجمه است']);
    exit;
}

// بررسی اینکه متن شامل حروف باشد
if (!preg_match('/[a-zA-ZäöüßÄÖÜ]/u', $text)) {
    echo json_encode(['success' => false, 'error' => 'متن باید شامل حروف لاتین یا آلمانی باشد']);
    exit;
}
// تابع جدید برای ذخیره اتوماتیک در vocabulary_words
if (!function_exists('saveToVocabularyWords')) {
    function saveToVocabularyWords($word, $translation)
    {
        global $pdo;

        try {
            // بررسی اینکه کلمه از قبل وجود دارد یا نه
            $stmt = $pdo->prepare("SELECT id FROM vocabulary_words WHERE word = ?");
            $stmt->execute([$word]);
            $existing = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($existing) {
                // اگر کلمه وجود دارد، ترجمه‌اش را به‌روزرسانی کن
                $stmt = $pdo->prepare("UPDATE vocabulary_words SET translation = ?, updated_at = CURRENT_TIMESTAMP WHERE id = ?");
                $stmt->execute([$translation, $existing['id']]);
                return $existing['id'];
            } else {
                // اگر کلمه وجود ندارد، آن را اضافه کن
                $stmt = $pdo->prepare("INSERT INTO vocabulary_words (word, translation) VALUES (?, ?)");
                $stmt->execute([$word, $translation]);
                return $pdo->lastInsertId();
            }
        } catch (PDOException $e) {
            error_log("Error saving to vocabulary_words: " . $e->getMessage());
            return null;
        }
    }
}

try {
    $translation = false;

    // روش 1: استفاده از Google Translate API رسمی در صورت وجود کلید
    if (defined('GOOGLE_TRANSLATE_API_KEY') && !empty(GOOGLE_TRANSLATE_API_KEY)) {
        $translation = googleTranslateAPI($text, $from, $to);
    } 

    // روش 2: استفاده از وب سرویس رایگان گوگل از طریق cURL
    if (!$translation) {
        $translation = googleTranslateCurl($text, $from, $to);
    }

    // روش 3: در صورت خطای گوگل (مانند خطای 429 Too Many Requests)، استفاده از جمینای
    if (!$translation) {
        $translation = translateWithGemini($text, $from, $to);
    }

    // روش 4: فال‌بک نهایی با سرویس رایگان MyMemory
    if (!$translation) {
        $translation = translateWithMyMemory($text, $from, $to);
    }

    if ($translation) {
        // ذخیره اتوماتیک در vocabulary_words
        $word_id = saveToVocabularyWords($text, $translation);

        echo json_encode([
            'success' => true,
            'translation' => $translation,
            'original' => $text,
            'word_id' => $word_id,
            'auto_saved' => true
        ]);
    } else {
        echo json_encode(['success' => false, 'error' => 'ترجمه انجام نشد']);
    }

} catch (Exception $e) {
    error_log("Translation error: " . $e->getMessage());
    echo json_encode(['success' => false, 'error' => 'خطا در سرویس ترجمه: ' . $e->getMessage()]);
}

// تابع ترجمه با Google API (روش رسمی)
function googleTranslateAPI($text, $from, $to)
{
    if (!defined('GOOGLE_TRANSLATE_API_KEY') || empty(GOOGLE_TRANSLATE_API_KEY)) {
        return false;
    }

    $url = 'https://translation.googleapis.com/language/translate/v2';

    $data = [
        'key' => GOOGLE_TRANSLATE_API_KEY,
        'q' => $text,
        'source' => $from,
        'target' => $to,
        'format' => 'text'
    ];

    $options = [
        'http' => [
            'header' => "Content-Type: application/x-www-form-urlencoded\r\n",
            'method' => 'POST',
            'content' => http_build_query($data)
        ]
    ];

    $context = stream_context_create($options);
    $result = file_get_contents($url, false, $context);

    if ($result) {
        $json = json_decode($result, true);
        if (isset($json['data']['translations'][0]['translatedText'])) {
            return $json['data']['translations'][0]['translatedText'];
        }
    }

    return false;
}

// تابع ترجمه رایگان (محدودیت دارد - فقط برای تست)
function googleTranslateFree($text, $from, $to)
{
    // این روش برای استفاده تجاری مناسب نیست و محدودیت دارد
    $url = "https://translate.googleapis.com/translate_a/single?client=gtx&sl={$from}&tl={$to}&dt=t&q=" . urlencode($text);

    $options = [
        'http' => [
            'method' => 'GET',
            'header' => [
                'User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/91.0.4472.124 Safari/537.36'
            ],
            'timeout' => 10
        ]
    ];

    $context = stream_context_create($options);
    $result = @file_get_contents($url, false, $context);

    if ($result) {
        // پردازش نتیجه JSON
        $result = json_decode($result, true);
        if (isset($result[0][0][0])) {
            return $result[0][0][0];
        }
    }

    return false;
}

// روش جایگزین با cURL (پیشنهادی)
function googleTranslateCurl($text, $from, $to)
{
    $url = "https://translate.googleapis.com/translate_a/single?client=gtx&sl={$from}&tl={$to}&dt=t&q=" . urlencode($text);

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36');
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 2);
    curl_setopt($ch, CURLOPT_TIMEOUT, 3);

    $result = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($httpCode == 200 && $result) {
        $result = json_decode($result, true);
        if (isset($result[0][0][0])) {
            return $result[0][0][0];
        }
    }

    return false;
}

// روش جایگزین هوش مصنوعی (جمینای) در صورت قطعی یا مسدودی گوگل
function translateWithGemini($text, $from, $to)
{
    $apiKey = defined('GEMINI_API_KEY') && !empty(GEMINI_API_KEY) ? GEMINI_API_KEY : getenv('GEMINI_API_KEY');
    $model = "gemini-flash-latest";
    $url = "https://generativelanguage.googleapis.com/v1beta/models/{$model}:generateContent";

    $payload = [
        "contents" => [
            ["parts" => [["text" => "Translate the following German word/phrase into Persian (Farsi) for a driving license test. Provide ONLY the Persian translation as a single word or short phrase, without explanations:\n" . $text]]]
        ],
        "generationConfig" => [
            "temperature" => 0.1,
            "maxOutputTokens" => 200,
            "thinkingConfig" => [
                "thinkingBudget" => 0
            ]
        ]
    ];

    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json',
        'X-goog-api-key: ' . $apiKey
    ]);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_TIMEOUT, 6);

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($httpCode === 200 && $response) {
        $responseData = json_decode($response, true);
        if (isset($responseData['candidates'][0]['content']['parts'][0]['text'])) {
            return trim($responseData['candidates'][0]['content']['parts'][0]['text']);
        }
    }

    return false;
}

// روش جایگزین MyMemory
function translateWithMyMemory($text, $from, $to)
{
    $url = "https://api.mymemory.translated.net/get?q=" . urlencode($text) . "&langpair={$from}|{$to}";
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_TIMEOUT, 5);
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($httpCode === 200 && $response) {
        $data = json_decode($response, true);
        if (isset($data['responseData']['translatedText']) && !empty($data['responseData']['translatedText'])) {
            $translated = trim($data['responseData']['translatedText']);
            if (stripos($translated, 'MYMEMORY WARNING') === false) {
                return $translated;
            }
        }
    }
    return false;
}
?>