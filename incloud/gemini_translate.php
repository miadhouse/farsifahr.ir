<?php
// gemini_translate.php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/functions.php';

header('Content-Type: application/json; charset=utf-8');

// بررسی CSRF token
$token = $_POST['csrf_token'] ?? '';
if (!verify_csrf_token($token)) {
    echo json_encode(['success' => false, 'message' => 'توکن امنیتی نامعتبر است']);
    exit;
}

if (!isset($_POST['word']) || empty(trim($_POST['word']))) {
    echo json_encode(['success' => false, 'message' => 'کلمه ارسال نشده است']);
    exit;
}

$word = trim($_POST['word']);
$context = trim($_POST['context'] ?? '');
$user_id = $_SESSION['user_id'] ?? null;

// اگر کلمه در دیتابیس موجود است و کانتکست نداریم، می‌توانیم همان را برگردانیم
// اما طبق درخواست کاربر، ترجمه‌ها خوب نیستند، پس ترجیحاً از جمینای استفاده می‌کنیم

try {
    $apiKey = "AIzaSyADjcpet-WVDpeMlZtIoXo2BZsjDPRfuh8";
    $model = "gemini-2.5-flash"; // استفاده از همان مدل موجود در پروژه
    $url = "https://generativelanguage.googleapis.com/v1beta/models/{$model}:generateContent?key={$apiKey}";

    $promptText = "You are an expert German to Persian translator for driving license exams.\n";
    if (!empty($context)) {
        $promptText .= "Translate the German word '{$word}' into Persian, specifically considering its meaning in the following context: \"{$context}\".\n";
    } else {
        $promptText .= "Translate the German word '{$word}' into Persian.\n";
    }
    $promptText .= "Provide ONLY the Persian translation as a single word or short phrase. Do not include any explanation or extra text.";

    $payload = [
        "contents" => [
            ["parts" => [["text" => $promptText]]]
        ],
        "generationConfig" => [
            "temperature" => 0.1,
            "topP" => 1,
            "topK" => 1,
            "maxOutputTokens" => 20,
        ]
    ];

    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    $responseData = json_decode($response, true);
    
    if ($httpCode !== 200 || !isset($responseData['candidates'][0]['content']['parts'][0]['text'])) {
        throw new Exception($responseData['error']['message'] ?? 'Gemini API Error');
    }

    $translation = trim($responseData['candidates'][0]['content']['parts'][0]['text']);
    
    // ذخیره در دیتابیس برای مراجعات بعدی (اختیاری، اما اینجا برای هماهنگی با سیستم موجود انجام می‌دهیم)
    $word_id = saveToVocabularyWords($word, $translation);

    // بررسی وجود در کلکشن کاربر
    $in_user_collection = false;
    if ($user_id && $word_id) {
        $stmt = $pdo->prepare("SELECT id FROM user_vocabulary WHERE user_id = ? AND word_id = ?");
        $stmt->execute([$user_id, $word_id]);
        $in_user_collection = $stmt->fetch(PDO::FETCH_ASSOC) !== false;
    }

    echo json_encode([
        'success' => true,
        'translation' => $translation,
        'word_id' => $word_id,
        'in_user_collection' => $in_user_collection
    ]);

} catch (Exception $e) {
    error_log("Gemini translation error: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'خطا در ترجمه هوشمند']);
}

// تابع برای ذخیره در vocabulary_words
function saveToVocabularyWords($word, $translation) {
    global $pdo;
    try {
        $stmt = $pdo->prepare("SELECT id FROM vocabulary_words WHERE word = ?");
        $stmt->execute([$word]);
        $existing = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($existing) {
            // اگر قبلاً بوده، فقط آیدی را برمی‌گردانیم (یا می‌توانیم آپدیت کنیم)
            // در اینجا آپدیت نمی‌کنیم چون شاید ترجمه جمینای فقط برای این کانتکست خاص باشد
            // اما سیستم فعلی طوری است که کلمه را در vocabulary_words ذخیره می‌کند
            return $existing['id'];
        } else {
            $stmt = $pdo->prepare("INSERT INTO vocabulary_words (word, translation) VALUES (?, ?)");
            $stmt->execute([$word, $translation]);
            return $pdo->lastInsertId();
        }
    } catch (PDOException $e) {
        return null;
    }
}
