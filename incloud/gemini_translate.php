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
$word = trim($word, " \t\n\r\0\x0B.,?!;:\"'()[]{}«»");
$context = trim($_POST['context'] ?? '');
$user_id = $_SESSION['user_id'] ?? null;

if (!function_exists('saveToVocabularyWords')) {
    function saveToVocabularyWords($word, $translation) {
        global $pdo;
        try {
            $stmt = $pdo->prepare("SELECT id FROM vocabulary_words WHERE word = ?");
            $stmt->execute([$word]);
            $existing = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($existing) {
                $stmt = $pdo->prepare("UPDATE vocabulary_words SET translation = ?, updated_at = CURRENT_TIMESTAMP WHERE id = ?");
                $stmt->execute([$translation, $existing['id']]);
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
}

// ۱. ابتدا بررسی وجود کلمه در دیتابیس (اولویت اول با دیتابیس است)
try {
    $stmt = $pdo->prepare("SELECT id, translation FROM vocabulary_words WHERE word = ? AND translation IS NOT NULL AND TRIM(translation) != '' LIMIT 1");
    $stmt->execute([$word]);
    $cached = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($cached && !empty(trim($cached['translation']))) {
        $in_user_collection = false;
        if ($user_id) {
            $stmtUser = $pdo->prepare("SELECT id FROM user_vocabulary WHERE user_id = ? AND word_id = ?");
            $stmtUser->execute([$user_id, $cached['id']]);
            $in_user_collection = $stmtUser->fetch(PDO::FETCH_ASSOC) !== false;
        }

        echo json_encode([
            'success' => true,
            'translation' => trim($cached['translation']),
            'word_id' => $cached['id'],
            'in_user_collection' => $in_user_collection,
            'from_database' => true
        ]);
        exit;
    }
} catch (PDOException $e) {
    error_log("Database lookup error in gemini_translate: " . $e->getMessage());
}

// ۲. در صورت عدم وجود در دیتابیس، ترجمه با هوش مصنوعی (Gemini)
try {
    $apiKey = defined('GEMINI_API_KEY') && !empty(GEMINI_API_KEY) ? GEMINI_API_KEY : getenv('GEMINI_API_KEY');
    $model = "gemini-flash-latest";
    $url = "https://generativelanguage.googleapis.com/v1beta/models/{$model}:generateContent";

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
            "maxOutputTokens" => 300,
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
    echo json_encode(['success' => false, 'message' => 'خطا در ارتباط با هوش مصنوعی']);
}
