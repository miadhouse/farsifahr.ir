<?php include_once('../config/config.php'); ?>
<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ایمپورت فایل SQL</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
            padding: 20px;
        }
        .container {
            background: white;
            border-radius: 15px;
            box-shadow: 0 10px 40px rgba(0,0,0,0.2);
            padding: 40px;
            max-width: 600px;
            width: 100%;
        }
        h1 {
            color: #333;
            margin-bottom: 30px;
            text-align: center;
            font-size: 28px;
        }
        .upload-area {
            border: 3px dashed #667eea;
            border-radius: 10px;
            padding: 40px;
            text-align: center;
            background: #f8f9ff;
            margin-bottom: 20px;
            transition: all 0.3s;
        }
        .upload-area:hover {
            border-color: #764ba2;
            background: #f0f2ff;
        }
        .upload-area.dragover {
            border-color: #764ba2;
            background: #e8ebff;
            transform: scale(1.02);
        }
        .file-input-wrapper {
            position: relative;
            display: inline-block;
        }
        input[type="file"] {
            position: absolute;
            opacity: 0;
            width: 100%;
            height: 100%;
            cursor: pointer;
        }
        .file-label {
            display: inline-block;
            padding: 12px 30px;
            background: #667eea;
            color: white;
            border-radius: 8px;
            cursor: pointer;
            transition: all 0.3s;
            font-weight: bold;
        }
        .file-label:hover {
            background: #764ba2;
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(102, 126, 234, 0.4);
        }
        .file-name {
            margin-top: 15px;
            color: #666;
            font-size: 14px;
        }
        .upload-icon {
            font-size: 48px;
            color: #667eea;
            margin-bottom: 15px;
        }
        .btn {
            width: 100%;
            padding: 15px;
            background: #667eea;
            color: white;
            border: none;
            border-radius: 8px;
            font-size: 16px;
            font-weight: bold;
            cursor: pointer;
            transition: all 0.3s;
        }
        .btn:hover {
            background: #764ba2;
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(102, 126, 234, 0.4);
        }
        .btn:disabled {
            background: #ccc;
            cursor: not-allowed;
            transform: none;
        }
        .result {
            margin-top: 30px;
            padding: 20px;
            border-radius: 8px;
            font-size: 14px;
            line-height: 1.8;
            max-height: 500px;
            overflow-y: auto;
        }
        .success {
            background: #d4edda;
            border: 1px solid #c3e6cb;
            color: #155724;
        }
        .error {
            background: #f8d7da;
            border: 1px solid #f5c6cb;
            color: #721c24;
        }
        .info {
            background: #d1ecf1;
            border: 1px solid #bee5eb;
            color: #0c5460;
        }
        .log-item {
            padding: 8px 0;
            border-bottom: 1px solid rgba(0,0,0,0.1);
        }
        .log-item:last-child {
            border-bottom: none;
        }
        .summary {
            margin-top: 15px;
            padding-top: 15px;
            border-top: 2px solid rgba(0,0,0,0.2);
            font-weight: bold;
        }
        .question-group {
            background: rgba(102, 126, 234, 0.1);
            padding: 10px;
            margin: 10px 0;
            border-radius: 5px;
            border-left: 4px solid #667eea;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>📤 ایمپورت فایل SQL به جدول Answers</h1>
        
        <form method="POST" enctype="multipart/form-data" id="uploadForm">
            <div class="upload-area" id="uploadArea">
                <div class="upload-icon">📁</div>
                <div class="file-input-wrapper">
                    <label class="file-label">
                        انتخاب فایل SQL
                        <input type="file" name="sql_file" id="sqlFile" accept=".sql" required>
                    </label>
                </div>
                <div class="file-name" id="fileName">هیچ فایلی انتخاب نشده</div>
            </div>
            
            <button type="submit" class="btn" id="submitBtn">
                ⬆️ آپلود و ایمپورت
            </button>
        </form>

        <?php
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['sql_file'])) {
            
            // آرایه شماره سوالات موجود
            $questions = array(
  array('number' => '2.2.03-026-M'),
  array('number' => '2.2.03-110'),
  array('number' => '1.1.07-176-M'),
  array('number' => '1.2.09-022-M'),
  array('number' => '1.2.10-006-M'),
  array('number' => '1.2.19-117'),
  array('number' => '1.2.19-118-M'),
  array('number' => '1.2.20-110-M'),
  array('number' => '1.2.20-111-M'),
  array('number' => '1.2.36-016-M'),
  array('number' => '1.2.37-104'),
  array('number' => '1.3.01-051-M'),
  array('number' => '1.4.41-028-M'),
  array('number' => '1.4.41-029'),
  array('number' => '1.4.41-030'),
  array('number' => '1.4.41-173'),
  array('number' => '1.5.01-017'),
  array('number' => '2.1.07-124-M'),
  array('number' => '2.2.07-014-M'),
  array('number' => '2.2.18-024-M'),
  array('number' => '2.2.23-126'),
  array('number' => '2.2.23-127'),
  array('number' => '2.2.23-128'),
  array('number' => '2.4.41-005-M'),
  array('number' => '2.4.42-110')
);

            
            // تبدیل آرایه به لیست ساده
            $questionNumbers = array_column($questions, 'number');
            
            $results = [];
            $totalInserted = 0;
            $totalSkipped = 0;
            $questionsProcessed = [];
            $hasError = false;
            
            try {
                // بررسی آپلود فایل
                if ($_FILES['sql_file']['error'] !== UPLOAD_ERR_OK) {
                    throw new Exception('خطا در آپلود فایل');
                }
                
                // بررسی نوع فایل
                $fileName = $_FILES['sql_file']['name'];
                $fileExt = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
                
                if ($fileExt !== 'sql') {
                    throw new Exception('فقط فایل‌های SQL مجاز هستند');
                }
                
                // خواندن محتوای فایل
                $content = file_get_contents($_FILES['sql_file']['tmp_name']);
                
                if (empty($content)) {
                    throw new Exception('فایل خالی است');
                }
                
                $results[] = "✓ فایل {$fileName} خوانده شد";
                $results[] = "✓ تعداد سوالات در آرایه: " . count($questionNumbers);
                $results[] = "<hr>";
                
                // استخراج دستورات INSERT با ساختار جدید
                preg_match_all("/INSERT INTO `answers` \(`question_number`, `text`, `en_text`, `farsi_text`, `info`, `is_image`, `original_content`, `asw_type`, `asw_corr`, `asw_hint`\) VALUES\s*([\s\S]*?);/i", $content, $matches);
                
                if (empty($matches[1])) {
                    throw new Exception('هیچ دستور INSERT در فایل یافت نشد');
                }
                
                // جمع‌آوری تمام رکوردها و گروه‌بندی بر اساس question_number
                $allRecords = [];
                
                foreach ($matches[1] as $valuesBlock) {
                    // جداسازی هر ردیف
                    preg_match_all("/\(([^)]+(?:\([^)]*\)[^)]*)*)\)/", $valuesBlock, $rows);
                    
                    foreach ($rows[1] as $row) {
                        // پردازش مقادیر
                        $values = parseValues($row);
                        
                        if (count($values) < 10) {
                            continue;
                        }
                        
                        $questionNumber = $values[0];
                        
                        // ذخیره رکورد در آرایه گروه‌بندی شده
                        if (!isset($allRecords[$questionNumber])) {
                            $allRecords[$questionNumber] = [];
                        }
                        
                        $allRecords[$questionNumber][] = [
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
                    }
                }
                
                // پردازش هر گروه از سوالات
                foreach ($allRecords as $questionNumber => $records) {
                    
                    // بررسی اینکه آیا این question_number در آرایه وجود دارد
                    if (!in_array($questionNumber, $questionNumbers)) {
                        $results[] = "<div class='question-group'>⊘ <strong>{$questionNumber}</strong> رد شد (در آرایه سوالات موجود نیست - " . count($records) . " پاسخ)</div>";
                        $totalSkipped += count($records);
                        continue;
                    }
                    
                    // بررسی وجود question_number در جدول دیتابیس
                    $checkStmt = $pdo->prepare("SELECT COUNT(*) FROM answers WHERE question_number = ?");
                    $checkStmt->execute([$questionNumber]);
                    $exists = $checkStmt->fetchColumn();
                    
                    if ($exists > 0) {
                        $results[] = "<div class='question-group'>⊘ <strong>{$questionNumber}</strong> رد شد (در دیتابیس موجود است - {$exists} پاسخ)</div>";
                        $totalSkipped += count($records);
                        continue;
                    }
                    
                    // درج تمام رکوردهای این سوال
                    $insertedCount = 0;
                    $results[] = "<div class='question-group'>";
                    $results[] = "📝 <strong>{$questionNumber}</strong> - در حال درج " . count($records) . " پاسخ:";
                    
                    foreach ($records as $record) {
                        try {
                            $insertStmt = $pdo->prepare("
                                INSERT INTO answers 
                                (question_number, text, en_text, farsi_text, info, is_image, original_content, asw_type, asw_corr, asw_hint) 
                                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
                            ");
                            
                            $insertStmt->execute([
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
                            
                            $insertedCount++;
                            $totalInserted++;
                            
                            // نمایش متن کوتاه شده پاسخ
                            $shortText = mb_substr($record['text'], 0, 50);
                            if (mb_strlen($record['text']) > 50) {
                                $shortText .= '...';
                            }
                            $results[] = "&nbsp;&nbsp;&nbsp;✓ پاسخ #{$insertedCount}: {$shortText}";
                            
                        } catch (PDOException $e) {
                            $results[] = "&nbsp;&nbsp;&nbsp;❌ خطا در درج پاسخ: " . $e->getMessage();
                        }
                    }
                    
                    $results[] = "✅ جمع: {$insertedCount} پاسخ اضافه شد";
                    $results[] = "</div>";
                    
                    if (!isset($questionsProcessed[$questionNumber])) {
                        $questionsProcessed[$questionNumber] = $insertedCount;
                    }
                }
                
                $cssClass = 'success';
                
            } catch (PDOException $e) {
                $results[] = "❌ خطا در دیتابیس: " . $e->getMessage();
                $hasError = true;
                $cssClass = 'error';
            } catch (Exception $e) {
                $results[] = "❌ خطا: " . $e->getMessage();
                $hasError = true;
                $cssClass = 'error';
            }
            
            // نمایش نتایج
            if (!empty($results)) {
                echo '<div class="result ' . $cssClass . '">';
                foreach ($results as $result) {
                    if ($result === '<hr>') {
                        echo '<hr style="margin: 15px 0; border: none; border-top: 1px solid rgba(0,0,0,0.2);">';
                    } else {
                        echo '<div class="log-item">' . $result . '</div>';
                    }
                }
                
                if (!$hasError) {
                    echo '<div class="summary">';
                    echo '📊 خلاصه نتایج:<br>';
                    echo '✅ تعداد سوالات جدید: ' . count($questionsProcessed) . '<br>';
                    echo '✅ تعداد پاسخ‌های اضافه شده: ' . $totalInserted . '<br>';
                    echo '⊘ تعداد پاسخ‌های رد شده: ' . $totalSkipped . '<br>';
                    echo '📈 جمع کل پاسخ‌ها: ' . ($totalInserted + $totalSkipped);
                    echo '</div>';
                }
                
                echo '</div>';
            }
        }
        
        function parseValues($row) {
            $values = [];
            $current = '';
            $inQuotes = false;
            $quoteChar = '';
            $escaped = false;
            
            for ($i = 0; $i < strlen($row); $i++) {
                $char = $row[$i];
                
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
                
                if (($char === "'" || $char === '"') && !$inQuotes) {
                    $inQuotes = true;
                    $quoteChar = $char;
                    continue;
                }
                
                if ($char === $quoteChar && $inQuotes) {
                    $inQuotes = false;
                    $values[] = $current;
                    $current = '';
                    continue;
                }
                
                if ($char === ',' && !$inQuotes) {
                    if ($current === 'NULL' || $current === '') {
                        $values[] = null;
                    } elseif (is_numeric($current)) {
                        $values[] = $current;
                    }
                    $current = '';
                    continue;
                }
                
                if ($inQuotes) {
                    $current .= $char;
                } elseif (trim($char) !== '') {
                    $current .= $char;
                }
            }
            
            // آخرین مقدار
            if ($current !== '') {
                if ($current === 'NULL') {
                    $values[] = null;
                } else {
                    $values[] = $current;
                }
            }
            
            return $values;
        }
        ?>
    </div>

    <script>
        const fileInput = document.getElementById('sqlFile');
        const fileName = document.getElementById('fileName');
        const uploadArea = document.getElementById('uploadArea');
        const submitBtn = document.getElementById('submitBtn');

        // نمایش نام فایل انتخاب شده
        fileInput.addEventListener('change', function(e) {
            if (this.files.length > 0) {
                fileName.textContent = '📄 ' + this.files[0].name;
                fileName.style.color = '#667eea';
                fileName.style.fontWeight = 'bold';
            } else {
                fileName.textContent = 'هیچ فایلی انتخاب نشده';
                fileName.style.color = '#666';
                fileName.style.fontWeight = 'normal';
            }
        });

        // Drag & Drop
        uploadArea.addEventListener('dragover', function(e) {
            e.preventDefault();
            this.classList.add('dragover');
        });

        uploadArea.addEventListener('dragleave', function(e) {
            e.preventDefault();
            this.classList.remove('dragover');
        });

        uploadArea.addEventListener('drop', function(e) {
            e.preventDefault();
            this.classList.remove('dragover');
            
            const files = e.dataTransfer.files;
            if (files.length > 0) {
                fileInput.files = files;
                fileName.textContent = '📄 ' + files[0].name;
                fileName.style.color = '#667eea';
                fileName.style.fontWeight = 'bold';
            }
        });

        // جلوگیری از ارسال مجدد فرم
        document.getElementById('uploadForm').addEventListener('submit', function() {
            submitBtn.disabled = true;
            submitBtn.textContent = '⏳ در حال پردازش...';
        });
    </script>
</body>
</html>