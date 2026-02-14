<?php
/**
 * اسکریپت وب برای import و همگام‌سازی جدول answers
 * این نسخه از PDO موجود در config.php استفاده می‌کند
 */

require '../config/config.php';

// تنظیمات آپلود
define('MAX_FILE_SIZE', 50 * 1024 * 1024); // 50MB
define('UPLOAD_DIR', __DIR__ . '/uploads/');
define('DEBUG_MODE', true); // برای دیباگ - بعداً false کنید

// ایجاد پوشه uploads اگر وجود ندارد
if (!is_dir(UPLOAD_DIR)) {
    mkdir(UPLOAD_DIR, 0755, true);
}

class AnswersImporter {
    private $pdo;
    private $stats = [
        'total_in_file' => 0,
        'existing' => 0,
        'new_inserted' => 0,
        'errors' => 0,
        'error_messages' => []
    ];
    private $output = [];

    public function __construct($pdo) {
        $this->pdo = $pdo;
        $this->addOutput('success', '✓ اتصال به دیتابیس برقرار است');
    }

    private function addOutput($type, $message) {
        $this->output[] = ['type' => $type, 'message' => $message];
    }

    public function getOutput() {
        return $this->output;
    }

    public function parseSqlFile($filepath) {
        if (!file_exists($filepath)) {
            $this->addOutput('error', '✗ فایل SQL یافت نشد');
            return [];
        }

        $this->addOutput('info', '→ در حال خواندن فایل SQL...');
        $content = file_get_contents($filepath);
        
        // دیباگ: نمایش تعداد کاراکترها
        if (defined('DEBUG_MODE') && DEBUG_MODE) {
            $this->addOutput('info', '→ حجم فایل: ' . strlen($content) . ' کاراکتر');
            $preview = substr($content, 0, 500);
            $this->addOutput('info', '→ پیش‌نمایش: ' . htmlspecialchars($preview) . '...');
        }
        
        // حذف کامنت‌ها
        $content = preg_replace('/--.*$/m', '', $content);
        $content = preg_replace('/\/\*.*?\*\//s', '', $content);
        
        // پیدا کردن تمام دستورات INSERT
        // پشتیبانی از فرمت‌های مختلف: INSERT INTO `answers`, INSERT INTO answers
        $pattern = '/INSERT\s+INTO\s+`?answers`?\s*\([^)]*\)\s*VALUES\s*(.*?);/is';
        preg_match_all($pattern, $content, $matches);

        if (defined('DEBUG_MODE') && DEBUG_MODE) {
            $this->addOutput('info', '→ تعداد INSERT یافت شده (الگو 1): ' . count($matches[0]));
        }

        if (empty($matches[1])) {
            // اگر فرمت اول کار نکرد، فرمت دیگری را امتحان کن
            $pattern = '/INSERT\s+INTO\s+`?answers`?.*?VALUES\s*(.*?);/is';
            preg_match_all($pattern, $content, $matches);
            
            if (defined('DEBUG_MODE') && DEBUG_MODE) {
                $this->addOutput('info', '→ تعداد INSERT یافت شده (الگو 2): ' . count($matches[0]));
            }
        }

        if (empty($matches[1])) {
            $this->addOutput('error', '✗ هیچ دستور INSERT معتبر در فایل یافت نشد');
            $this->addOutput('info', '→ لطفاً مطمئن شوید فایل SQL شامل دستورات INSERT INTO answers است');
            return [];
        }

        $records = [];
        foreach ($matches[1] as $values_block) {
            $records = array_merge($records, $this->parseValuesBlock($values_block));
        }

        $this->stats['total_in_file'] = count($records);
        $this->addOutput('success', '✓ تعداد ' . count($records) . ' رکورد از فایل SQL خوانده شد');
        
        return $records;
    }

    private function parseValuesBlock($values_block) {
        $records = [];
        
        // حذف فضاهای خالی اضافی
        $values_block = trim($values_block);
        
        if (defined('DEBUG_MODE') && DEBUG_MODE) {
            $this->addOutput('info', '→ طول بلوک VALUES: ' . strlen($values_block) . ' کاراکتر');
            $preview = substr($values_block, 0, 300);
            $this->addOutput('info', '→ شروع بلوک: ' . htmlspecialchars($preview));
        }
        
        // پارس دستی با شمارش پرانتزها
        $records_raw = [];
        $current = '';
        $depth = 0;
        $in_string = false;
        $string_char = '';
        $escaped = false;
        
        for ($i = 0; $i < strlen($values_block); $i++) {
            $char = $values_block[$i];
            
            if ($escaped) {
                $current .= $char;
                $escaped = false;
                continue;
            }
            
            if ($char === '\\') {
                $escaped = true;
                $current .= $char;
                continue;
            }
            
            if (($char === '"' || $char === "'") && !$in_string) {
                $in_string = true;
                $string_char = $char;
                $current .= $char;
                continue;
            }
            
            if ($char === $string_char && $in_string) {
                $in_string = false;
                $current .= $char;
                continue;
            }
            
            if ($in_string) {
                $current .= $char;
                continue;
            }
            
            if ($char === '(') {
                if ($depth === 0) {
                    $current = '';
                } else {
                    $current .= $char;
                }
                $depth++;
            } elseif ($char === ')') {
                $depth--;
                if ($depth === 0) {
                    $records_raw[] = $current;
                    $current = '';
                } else {
                    $current .= $char;
                }
            } else {
                if ($depth > 0) {
                    $current .= $char;
                }
            }
        }
        
        if (defined('DEBUG_MODE') && DEBUG_MODE) {
            $this->addOutput('info', '→ تعداد رکوردهای خام یافت شده: ' . count($records_raw));
        }
        
        foreach ($records_raw as $record_str) {
            $record = $this->parseRecord($record_str);
            if ($record) {
                $records[] = $record;
            }
        }
        
        return $records;
    }

    private function parseRecord($record_str) {
        $values = [];
        $current = '';
        $in_quotes = false;
        $quote_char = '';
        $escaped = false;

        for ($i = 0; $i < strlen($record_str); $i++) {
            $char = $record_str[$i];

            if ($escaped) {
                $current .= $char;
                $escaped = false;
                continue;
            }

            if ($char === '\\') {
                $escaped = true;
                $current .= $char;
                continue;
            }

            if (($char === "'" || $char === '"') && !$in_quotes) {
                $in_quotes = true;
                $quote_char = $char;
                continue;
            }

            if ($char === $quote_char && $in_quotes) {
                $in_quotes = false;
                $quote_char = '';
                continue;
            }

            if ($char === ',' && !$in_quotes) {
                $values[] = $this->cleanValue($current);
                $current = '';
                continue;
            }

            $current .= $char;
        }

        if ($current !== '') {
            $values[] = $this->cleanValue($current);
        }

        if (defined('DEBUG_MODE') && DEBUG_MODE && count($values) !== 11) {
            $this->addOutput('info', '→ تعداد فیلدها: ' . count($values) . ' (انتظار: 11)');
            if (count($values) > 0 && count($values) < 15) {
                $this->addOutput('info', '→ فیلدها: ' . implode(' | ', array_map(function($v) {
                    return substr($v ?? 'NULL', 0, 30);
                }, $values)));
            }
        }

        if (count($values) === 11) {
            return [
                'question_number' => $values[0],
                'text' => $values[1],
                'en_text' => $values[2],
                'farsi_text' => $values[3],
                'info' => $values[4],
                'is_image' => $values[5],
                'original_content' => $values[6],
                'asw_type' => $values[7],
                'asw_corr' => $values[8],
                'asw_hint' => $values[9]
            ];
        } elseif (count($values) === 10) {
            // گاهی فیلد آخر (asw_hint) وجود ندارد
            return [
                'question_number' => $values[0],
                'text' => $values[1],
                'en_text' => $values[2],
                'farsi_text' => $values[3],
                'info' => $values[4],
                'is_image' => $values[5],
                'original_content' => $values[6],
                'asw_type' => $values[7],
                'asw_corr' => $values[8],
                'asw_hint' => $values[9] ?? ''
            ];
        }

        return null;
    }

    private function cleanValue($value) {
        $value = trim($value);
        
        if (strtoupper($value) === 'NULL') {
            return null;
        }
        
        $value = stripslashes($value);
        
        return $value;
    }

    public function getExistingQuestionNumbers() {
        $stmt = $this->pdo->query("SELECT DISTINCT question_number FROM answers WHERE question_number IS NOT NULL");
        $results = $stmt->fetchAll(PDO::FETCH_COLUMN);
        
        $this->addOutput('info', '→ تعداد ' . count($results) . ' شماره سوال منحصر به فرد در دیتابیس موجود است');
        
        return array_flip($results);
    }

    public function getExistingRecordsForQuestion($question_number) {
        $stmt = $this->pdo->prepare("
            SELECT question_number, text, en_text, farsi_text, info, is_image, 
                   original_content, asw_type, asw_corr, asw_hint
            FROM answers 
            WHERE question_number = ?
        ");
        $stmt->execute([$question_number]);
        return $stmt->fetchAll();
    }

    private function recordsAreEqual($record1, $record2) {
        $fields = ['text', 'en_text', 'farsi_text', 'info', 'is_image', 
                   'original_content', 'asw_type', 'asw_corr', 'asw_hint'];
        
        foreach ($fields as $field) {
            $val1 = $record1[$field] ?? null;
            $val2 = $record2[$field] ?? null;
            
            $val1 = ($val1 === '' || $val1 === '0') ? null : $val1;
            $val2 = ($val2 === '' || $val2 === '0') ? null : $val2;
            
            if ($val1 != $val2) {
                return false;
            }
        }
        
        return true;
    }

    private function insertRecord($record) {
        try {
            $stmt = $this->pdo->prepare("
                INSERT INTO answers 
                (question_number, text, en_text, farsi_text, info, is_image, 
                 original_content, asw_type, asw_corr, asw_hint)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");
            
            $stmt->execute([
                $record['question_number'],
                $record['text'],
                $record['en_text'],
                $record['farsi_text'],
                $record['info'],
                $record['is_image'],
                $record['original_content'],
                $record['asw_type'],
                $record['asw_corr'],
                $record['asw_hint']
            ]);
            
            return true;
        } catch (PDOException $e) {
            $this->stats['errors']++;
            $this->stats['error_messages'][] = $e->getMessage();
            return false;
        }
    }

    public function syncRecords($sql_records) {
        $this->addOutput('info', '→ شروع همگام‌سازی رکوردها...');
        
        $sql_grouped = [];
        foreach ($sql_records as $record) {
            $qn = $record['question_number'];
            if (!isset($sql_grouped[$qn])) {
                $sql_grouped[$qn] = [];
            }
            $sql_grouped[$qn][] = $record;
        }

        $existing_questions = $this->getExistingQuestionNumbers();

        foreach ($sql_grouped as $question_number => $sql_records_for_question) {
            if (!isset($existing_questions[$question_number])) {
                $this->addOutput('info', '→ سوال جدید: ' . $question_number . ' (' . count($sql_records_for_question) . ' رکورد)');
                
                foreach ($sql_records_for_question as $record) {
                    if ($this->insertRecord($record)) {
                        $this->stats['new_inserted']++;
                    }
                }
            } else {
                $existing_records = $this->getExistingRecordsForQuestion($question_number);
                
                foreach ($sql_records_for_question as $sql_record) {
                    $found = false;
                    
                    foreach ($existing_records as $existing_record) {
                        if ($this->recordsAreEqual($sql_record, $existing_record)) {
                            $found = true;
                            $this->stats['existing']++;
                            break;
                        }
                    }
                    
                    if (!$found) {
                        if ($this->insertRecord($sql_record)) {
                            $this->stats['new_inserted']++;
                        }
                    }
                }
            }
        }
    }

    public function getStats() {
        return $this->stats;
    }
}

// پردازش فرم
$result = null;
$processing = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['sql_file'])) {
    $processing = true;
    $result = ['success' => false, 'output' => [], 'stats' => []];
    
    try {
        // بررسی خطاهای آپلود
        if ($_FILES['sql_file']['error'] !== UPLOAD_ERR_OK) {
            throw new Exception('خطا در آپلود فایل');
        }

        // بررسی نوع فایل
        $file_info = pathinfo($_FILES['sql_file']['name']);
        if (!isset($file_info['extension']) || strtolower($file_info['extension']) !== 'sql') {
            throw new Exception('فقط فایل‌های SQL قابل قبول هستند');
        }

        // بررسی حجم فایل
        if ($_FILES['sql_file']['size'] > MAX_FILE_SIZE) {
            throw new Exception('حجم فایل بیش از حد مجاز است');
        }

        // انتقال فایل
        $upload_path = UPLOAD_DIR . uniqid() . '_' . basename($_FILES['sql_file']['name']);
        if (!move_uploaded_file($_FILES['sql_file']['tmp_name'], $upload_path)) {
            throw new Exception('خطا در ذخیره فایل');
        }

        // پردازش فایل با استفاده از $pdo موجود
        $importer = new AnswersImporter($pdo);
        $sql_records = $importer->parseSqlFile($upload_path);
        
        if (!empty($sql_records)) {
            $importer->syncRecords($sql_records);
        }
        
        $result['output'] = $importer->getOutput();
        $result['stats'] = $importer->getStats();
        $result['success'] = true;

        // حذف فایل آپلود شده
        @unlink($upload_path);

    } catch (Exception $e) {
        $result['output'][] = ['type' => 'error', 'message' => '✗ خطا: ' . $e->getMessage()];
    }
}
?>
<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Import SQL - جدول Answers</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Tahoma', 'Arial', sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 20px;
            direction: rtl;
        }

        .container {
            max-width: 900px;
            margin: 0 auto;
            background: white;
            border-radius: 15px;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
            overflow: hidden;
        }

        .header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 30px;
            text-align: center;
        }

        .header h1 {
            font-size: 28px;
            margin-bottom: 10px;
        }

        .header p {
            font-size: 14px;
            opacity: 0.9;
        }

        .content {
            padding: 40px;
        }

        .upload-section {
            background: #f8f9fa;
            border: 3px dashed #dee2e6;
            border-radius: 10px;
            padding: 40px;
            text-align: center;
            margin-bottom: 30px;
            transition: all 0.3s ease;
        }

        .upload-section:hover {
            border-color: #667eea;
            background: #f0f2ff;
        }

        .upload-icon {
            font-size: 48px;
            color: #667eea;
            margin-bottom: 20px;
        }

        .file-input-wrapper {
            position: relative;
            display: inline-block;
            cursor: pointer;
            margin-bottom: 15px;
        }

        .file-input-wrapper input[type="file"] {
            position: absolute;
            opacity: 0;
            width: 100%;
            height: 100%;
            cursor: pointer;
        }

        .file-input-label {
            display: inline-block;
            padding: 12px 30px;
            background: #667eea;
            color: white;
            border-radius: 25px;
            cursor: pointer;
            transition: all 0.3s ease;
            font-weight: bold;
        }

        .file-input-label:hover {
            background: #5568d3;
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(102, 126, 234, 0.3);
        }

        .file-name {
            display: block;
            margin-top: 10px;
            color: #495057;
            font-size: 14px;
        }

        .submit-btn {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 15px 40px;
            border: none;
            border-radius: 25px;
            font-size: 16px;
            font-weight: bold;
            cursor: pointer;
            transition: all 0.3s ease;
            box-shadow: 0 5px 15px rgba(102, 126, 234, 0.3);
        }

        .submit-btn:hover:not(:disabled) {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(102, 126, 234, 0.4);
        }

        .submit-btn:disabled {
            opacity: 0.5;
            cursor: not-allowed;
        }

        .output-section {
            background: #f8f9fa;
            border-radius: 10px;
            padding: 20px;
            margin-top: 30px;
            max-height: 500px;
            overflow-y: auto;
        }

        .output-line {
            padding: 10px;
            margin: 5px 0;
            border-radius: 5px;
            font-family: 'Courier New', monospace;
            font-size: 13px;
        }

        .output-line.success {
            background: #d4edda;
            color: #155724;
            border-right: 4px solid #28a745;
        }

        .output-line.error {
            background: #f8d7da;
            color: #721c24;
            border-right: 4px solid #dc3545;
        }

        .output-line.info {
            background: #d1ecf1;
            color: #0c5460;
            border-right: 4px solid #17a2b8;
        }

        .stats-box {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-top: 20px;
        }

        .stat-card {
            background: white;
            padding: 20px;
            border-radius: 10px;
            text-align: center;
            box-shadow: 0 3px 10px rgba(0, 0, 0, 0.1);
        }

        .stat-number {
            font-size: 36px;
            font-weight: bold;
            color: #667eea;
            margin-bottom: 5px;
        }

        .stat-label {
            font-size: 14px;
            color: #6c757d;
        }

        .spinner {
            border: 4px solid #f3f3f3;
            border-top: 4px solid #667eea;
            border-radius: 50%;
            width: 40px;
            height: 40px;
            animation: spin 1s linear infinite;
            margin: 20px auto;
        }

        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }

        .info-box {
            background: #e7f3ff;
            border-right: 4px solid #2196F3;
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 20px;
        }

        .info-box h3 {
            color: #1976D2;
            margin-bottom: 10px;
            font-size: 16px;
        }

        .info-box ul {
            margin-right: 20px;
            color: #424242;
            font-size: 14px;
        }

        .info-box li {
            margin: 5px 0;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>📊 Import فایل SQL</h1>
            <p>وارد کردن رکوردهای جدید به جدول Answers</p>
        </div>

        <div class="content">
            <div class="info-box">
                <h3>📌 راهنما:</h3>
                <ul>
                    <li>فایل SQL خود را انتخاب کنید</li>
                    <li>اسکریپت به صورت خودکار رکوردهای جدید را شناسایی می‌کند</li>
                    <li>فقط رکوردهایی که در دیتابیس موجود نیستند اضافه می‌شوند</li>
                    <li>حداکثر حجم فایل: 50MB</li>
                </ul>
            </div>

            <form method="POST" enctype="multipart/form-data" id="uploadForm">
                <div class="upload-section">
                    <div class="upload-icon">📁</div>
                    <h3>فایل SQL را اینجا آپلود کنید</h3>
                    <div class="file-input-wrapper">
                        <input type="file" name="sql_file" id="sql_file" accept=".sql" required onchange="updateFileName()">
                        <label for="sql_file" class="file-input-label">
                            انتخاب فایل
                        </label>
                    </div>
                    <span class="file-name" id="fileName">هیچ فایلی انتخاب نشده</span>
                </div>

                <div style="text-align: center;">
                    <button type="submit" class="submit-btn" id="submitBtn">
                        🚀 شروع Import
                    </button>
                </div>
            </form>

            <?php if ($processing && $result): ?>
                <div class="output-section">
                    <h3 style="margin-bottom: 15px; color: #495057;">📋 گزارش عملیات:</h3>
                    
                    <?php foreach ($result['output'] as $line): ?>
                        <div class="output-line <?php echo htmlspecialchars($line['type']); ?>">
                            <?php echo htmlspecialchars($line['message']); ?>
                        </div>
                    <?php endforeach; ?>

                    <?php if ($result['success'] && !empty($result['stats'])): ?>
                        <div class="stats-box">
                            <div class="stat-card">
                                <div class="stat-number"><?php echo $result['stats']['total_in_file']; ?></div>
                                <div class="stat-label">کل رکوردهای فایل</div>
                            </div>
                            <div class="stat-card">
                                <div class="stat-number"><?php echo $result['stats']['new_inserted']; ?></div>
                                <div class="stat-label">رکوردهای جدید</div>
                            </div>
                            <div class="stat-card">
                                <div class="stat-number"><?php echo $result['stats']['existing']; ?></div>
                                <div class="stat-label">رکوردهای موجود</div>
                            </div>
                            <div class="stat-card">
                                <div class="stat-number"><?php echo $result['stats']['errors']; ?></div>
                                <div class="stat-label">خطاها</div>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <script>
        function updateFileName() {
            const input = document.getElementById('sql_file');
            const fileNameSpan = document.getElementById('fileName');
            
            if (input.files.length > 0) {
                fileNameSpan.textContent = input.files[0].name;
                fileNameSpan.style.color = '#667eea';
                fileNameSpan.style.fontWeight = 'bold';
            } else {
                fileNameSpan.textContent = 'هیچ فایلی انتخاب نشده';
                fileNameSpan.style.color = '#495057';
                fileNameSpan.style.fontWeight = 'normal';
            }
        }

        document.getElementById('uploadForm').addEventListener('submit', function() {
            const btn = document.getElementById('submitBtn');
            btn.disabled = true;
            btn.innerHTML = '⏳ در حال پردازش...';
        });
    </script>
</body>
</html>