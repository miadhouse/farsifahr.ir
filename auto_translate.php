<?php
// auto_translate.php
// اسکریپت ترجمه خودکار سوالات با استفاده از هوش مصنوعی Gemini

// تنظیم محدودیت زمانی نامحدود برای جلوگیری از توقف اسکریپت
set_time_limit(0);
ini_set('memory_limit', '512M');

require_once __DIR__ . '/config/config.php';

// تنظیم لاگر
$logFile = __DIR__ . '/translation_log.txt';
function writeLog($message) {
    global $logFile;
    $time = date('Y-m-d H:i:s');
    $logMsg = "[$time] $message\n";
    echo $logMsg;
    file_put_contents($logFile, $logMsg, FILE_APPEND);
}

writeLog("=== شروع فرآیند ترجمه خودکار سوالات ===");

// بارگذاری سرویس‌های لاراول
try {
    require __DIR__ . '/miad/vendor/autoload.php';
    $app = require_once __DIR__ . '/miad/bootstrap/app.php';
    $kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
    $kernel->bootstrap();
    writeLog("سرویس‌های هسته لاراول (Scraper) با موفقیت بارگذاری شدند.");
} catch (\Throwable $e) {
    writeLog("خطا در راه‌اندازی لاراول: " . $e->getMessage());
    exit;
}

try {
    $scraper = app(\App\Services\QuestionScraperService::class);
    
    // دریافت سوالاتی که هنوز ترجمه نشده‌اند
    $stmt = $pdo->query("SELECT id, number, text, asw_pretext FROM questions WHERE farsi_text IS NULL OR farsi_text = '' ORDER BY id ASC");
    $untranslatedQuestions = $stmt->fetchAll();
    
    $total = count($untranslatedQuestions);
    writeLog("تعداد کل سوالات برای ترجمه: $total");
    
    if ($total === 0) {
        writeLog("تمام سوالات قبلاً ترجمه شده‌اند. پایان کار.");
        exit;
    }
    
    $count = 0;
    foreach ($untranslatedQuestions as $question) {
        $count++;
        $id = $question['id'];
        $number = $question['number'];
        writeLog("--- در حال پردازش سوال $count از $total | شناسه: $id | شماره: $number ---");
        
        // 1. واکشی اطلاعات اصلی آلمانی (توضیحات)
        $data = [];
        try {
            $data = $scraper->scrape($question['number'], (string) $question['text']);
        } catch (\Exception $e) {
            writeLog("هشدار: خطا در واکشی (Scrape) سایت مرجع برای سوال $id: " . $e->getMessage());
        }
        
        // 2. دریافت پاسخ‌ها از دیتابیس
        $stmtAns = $pdo->prepare("SELECT id, text FROM answers WHERE question_number = ?");
        $stmtAns->execute([$question['number']]);
        $dbAnswers = $stmtAns->fetchAll();
        
        // 3. ساختاربندی داده‌ها برای هوش مصنوعی
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
        
        // تنظیم پرامپت دقیق و سخت‌گیرانه برای اطمینان از ترجمه توضیحات
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
        
        // 4. ارسال درخواست به Gemini
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
            $errMsg = $responseData['error']['message'] ?? 'Unknown Error';
            writeLog("خطا: ارتباط با Gemini برای سوال $id ناموفق بود. پیام: $errMsg");
            writeLog("توقف ۵ ثانیه ای برای جلوگیری از بن شدن...");
            sleep(5);
            continue; // برو سوال بعدی
        }
        
        $translatedJsonText = trim($responseData['candidates'][0]['content']['parts'][0]['text']);
        
        // حذف مارک‌داون
        $translatedJsonText = preg_replace('/^```json\s*/i', '', $translatedJsonText);
        $translatedJsonText = preg_replace('/\s*```$/i', '', $translatedJsonText);
        
        $translatedData = json_decode($translatedJsonText, true);
        
        if (!$translatedData || !isset($translatedData['question'])) {
            writeLog("خطا: خروجی Gemini برای سوال $id نامعتبر بود. پرش به سوال بعدی.");
            sleep(5);
            continue;
        }
        
        // 5. ذخیره در دیتابیس
        $pdo->beginTransaction();
        
        try {
            $q = $translatedData['question'];
            $finalInfo = !empty($q['info']) ? "<div dir='rtl'>{$q['info']}</div>" : null;
            
            if ($finalInfo !== null) {
                $updateQ = $pdo->prepare("UPDATE questions SET info = ?, farsi_text = ?, asw_farsi = ? WHERE id = ?");
                $updateQ->execute([$finalInfo, $q['text'] ?? '', $q['asw_pretext'] ?? '', $id]);
            } else {
                $updateQ = $pdo->prepare("UPDATE questions SET farsi_text = ?, asw_farsi = ? WHERE id = ?");
                $updateQ->execute([$q['text'] ?? '', $q['asw_pretext'] ?? '', $id]);
            }
            
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
            writeLog("موفقیت: سوال $id با موفقیت ترجمه و ذخیره شد.");
            
        } catch (\Throwable $e) {
            $pdo->rollBack();
            writeLog("خطای دیتابیس در ذخیره سوال $id: " . $e->getMessage());
        }
        
        // ایجاد تاخیر ۴ ثانیه‌ای برای رعایت محدودیت‌های (Rate Limit) API جمنای
        sleep(4);
    }
    
    writeLog("=== پایان فرآیند ترجمه. تمام سوالات بررسی شدند. ===");

} catch (\Throwable $e) {
    writeLog("خطای سیستمی غیرمنتظره: " . $e->getMessage());
}
