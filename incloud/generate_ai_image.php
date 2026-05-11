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

$prompt = $_POST['prompt'] ?? '';
if (empty(trim($prompt))) {
    echo json_encode(['success' => false, 'message' => 'متن (Prompt) خالی است']);
    exit;
}

// Function to translate Persian to English using Google Translate free API
function translateToEnglish($text) {
    if (!preg_match('/[ا-ی]/u', $text)) {
        return $text; // Already English (mostly)
    }
    
    $url = "https://translate.googleapis.com/translate_a/single?client=gtx&sl=fa&tl=en&dt=t&q=" . urlencode($text);
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);
    $response = curl_exec($ch);
    curl_close($ch);
    
    if ($response) {
        $result = json_decode($response, true);
        $translation = "";
        if (isset($result[0])) {
            foreach ($result[0] as $segment) {
                $translation .= $segment[0] ?? "";
            }
            return trim($translation);
        }
    }
    return $text; // Fallback to original
}

$englishPrompt = translateToEnglish($prompt);

// Gemini API Key provided
$apiKey = "AIzaSyADjcpet-WVDpeMlZtIoXo2BZsjDPRfuh8";
$model = "imagen-4.0-generate-001";
$url = "https://generativelanguage.googleapis.com/v1beta/models/{$model}:predict?key={$apiKey}";

// تاکید بر عدم وجود متن در تصویر
$noTextPrompt = ", NO text, NO typography, NO letters, NO words, NO watermark, NO poster, NO signs, clear scene";

$data = [
    "instances" => [
        ["prompt" => $englishPrompt . $noTextPrompt]
    ],
    "parameters" => [
        "sampleCount" => 1
    ]
];

$ch = curl_init($url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Content-Type: application/json'
]);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
curl_setopt($ch, CURLOPT_TIMEOUT, 40);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

$responseData = json_decode($response, true);
$imageData = null;
$usedGemini = false;

if ($httpCode === 200 && isset($responseData['predictions'][0]['bytesBase64Encoded'])) {
    // Decode base64 image from Gemini
    $imageData = base64_decode($responseData['predictions'][0]['bytesBase64Encoded']);
    $usedGemini = true;
} else {
    // Fallback to pollinations if Gemini fails (e.g. billing not updated yet)
    $errorMsg = $responseData['error']['message'] ?? 'خطای نامشخص از سمت جمنای';
    
    // اجازه می‌دهیم خود کاربر سبک را تعیین کند، فقط کلمات کیفیت بالا را اضافه می‌کنیم
    $enhancedPrompt = $englishPrompt . ", high quality, detailed" . $noTextPrompt;
    $encodedPrompt = urlencode($enhancedPrompt);
    $aiImageUrl = "https://image.pollinations.ai/prompt/{$encodedPrompt}?width=300&height=300&nologo=true";
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $aiImageUrl);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);
    $imageData = curl_exec($ch);
    $fallbackHttpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    if (!$imageData || $fallbackHttpCode !== 200) {
         echo json_encode(['success' => false, 'message' => "هر دو سرویس جمنای و جایگزین با خطا مواجه شدند. (خطای جمنای: $errorMsg)"]);
         exit;
    }
}

// Function to resize image to max 300px
function resizeImageBuffer($data, $maxSize = 300) {
    if (!function_exists('imagecreatefromstring')) return $data;
    
    $img = @imagecreatefromstring($data);
    if (!$img) return $data;
    
    $width = imagesx($img);
    $height = imagesy($img);
    
    if ($width <= $maxSize && $height <= $maxSize) {
        imagedestroy($img);
        return $data;
    }
    
    $ratio = min($maxSize / $width, $maxSize / $height);
    $newWidth = round($width * $ratio);
    $newHeight = round($height * $ratio);
    
    $newImg = imagecreatetruecolor($newWidth, $newHeight);
    
    // Preserve transparency
    imagealphablending($newImg, false);
    imagesavealpha($newImg, true);
    
    imagecopyresampled($newImg, $img, 0, 0, 0, 0, $newWidth, $newHeight, $width, $height);
    
    ob_start();
    imagejpeg($newImg, null, 90); // Save as JPEG with 90% quality
    $resizedData = ob_get_clean();
    
    imagedestroy($img);
    imagedestroy($newImg);
    
    return $resizedData;
}

$imageData = resizeImageBuffer($imageData, 300);

// Save the image locally
$uploadDir = __DIR__ . '/../miad/storage/app/public/answers/info/';
if (!is_dir($uploadDir)) {
    mkdir($uploadDir, 0755, true);
}

$prefix = $usedGemini ? 'ai_gemini_' : 'ai_pollinations_';
$filename = uniqid($prefix, true) . '.jpg';
$destination = $uploadDir . $filename;

if (file_put_contents($destination, $imageData)) {
    chmod($destination, 0644);
    $url = '/storage/answers/info/' . $filename;
    $msg = $usedGemini ? 'تصویر با موفقیت توسط Gemini 4.0 تولید و اضافه شد.' : 'جمنای هنوز فعال نشده (استفاده از مدل جایگزین)';
    echo json_encode(['success' => true, 'url' => $url, 'message' => $msg]);
} else {
    echo json_encode(['success' => false, 'message' => 'خطا در ذخیره تصویر روی سرور']);
}
