  <?php
require_once __DIR__ . '/../incloud/questions.php';
require_once __DIR__ . '/../incloud/functions.php';
// در ابتدای فایل PHP بعد از require_once
// فقط token موجود در session را دریافت می‌کنیم (که موقع لاگین ساخته شده)
$csrf_token = $_SESSION['csrf_token'] ?? '';
$user_id = $_SESSION['user_id'] ?? null;

// اگر token وجود نداشت (کاربر لاگین نیست)، خطا نمایش دهید
if (empty($csrf_token) || empty($user_id)) {
    header("Location: ../auth/auth.php");
    exit;
}
if (!isset($_POST['selected_questions']) && !isset($_GET['questions'])) {
    die('پارامترهای لازم ارسال نشده‌اند');
}

// Get mode parameter (default to browse if not specified)
$mode = $_POST['mode'] ?? $_GET['mode'] ?? 'browse';

// Get selected questions from POST or GET
$rawQuestions = $_POST['selected_questions'] ?? $_GET['questions'] ?? '';

// تبدیل به آرایه در صورت نیاز
$selectedQuestions = is_array($rawQuestions)
    ? $rawQuestions
    : explode(',', $rawQuestions);

if (empty($selectedQuestions)) {
    echo '<div class="alert alert-warning">هیچ سوالی یافت نشد</div>';
    exit;
}

// Initialize or get user answers from session
if (!isset($_SESSION['user_answers'])) {
    $_SESSION['user_answers'] = [];
}

if (!isset($_SESSION['solved_questions'])) {
    $_SESSION['solved_questions'] = [];
}

// تعداد کل سوال‌ها
$totalQuestions = count($selectedQuestions);

// مدیریت صفحه‌بندی با session
$questionsPerPage = 10;
$currentQuestionIndex = 0;
$currentPage = 1;

if (isset($_GET['questions'])) {
    $firstQuestion = explode(',', $_GET['questions'])[0];
    $_SESSION['current_question_id'] = $firstQuestion;
}

// بررسی وجود question_id در session
if (isset($_SESSION['current_question_id']) && in_array($_SESSION['current_question_id'], $selectedQuestions)) {
    // پیدا کردن index سوال فعلی
    $currentQuestionIndex = array_search($_SESSION['current_question_id'], $selectedQuestions);
    $currentPage = floor($currentQuestionIndex / $questionsPerPage) + 1;
} else {
    // اگر session وجود ندارد، از سوال اول شروع کن
    $currentQuestionIndex = 0;
    $currentPage = 1;
    $_SESSION['current_question_id'] = $selectedQuestions[0];
}

// محاسبه محدوده صفحه فعلی
$startIndex = ($currentPage - 1) * $questionsPerPage;
$endIndex = min($startIndex + $questionsPerPage - 1, $totalQuestions - 1);

// محاسبه تعداد کل صفحات
$totalPages = ceil($totalQuestions / $questionsPerPage);

// سوال فعلی
$currentQuestion = $selectedQuestions[$currentQuestionIndex];

// دسترسی کاربر
$user_id = $_SESSION['user_id'] ?? null;
$isVip = is_user_vip($user_id, $pdo);
$questionLimit = get_user_question_limit($user_id, $pdo);

$maxAccessibleId = 999999999; // پیش‌فرض نامحدود
if (!$isVip && $questionLimit !== null) {
    $examDateType = getUserExamDateType($pdo, $user_id);
    $availableCondition = "";
    if ($examDateType === 'before') {
        $availableCondition = " AND (available = 0 OR available = 1)";
    } elseif ($examDateType === 'after') {
        $availableCondition = " AND (available = 0 OR available = 2)";
    }
    
    $stmt = $pdo->prepare("SELECT id FROM questions WHERE 1=1 $availableCondition ORDER BY id ASC LIMIT 1 OFFSET " . ($questionLimit - 1));
    $stmt->execute();
    $limitId = $stmt->fetchColumn();
    if ($limitId !== false) {
        $maxAccessibleId = intval($limitId);
    }
}

$isAdmin = is_super_admin();
?>
<!DOCTYPE html>
<html data-bs-theme="light" lang="en" style="height: 100%;">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, shrink-to-fit=no">
    <title>questions-page</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://use.fontawesome.com/releases/v5.12.0/css/all.css">
    
    <!-- Summernote CSS & JS -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <link href="https://cdn.jsdelivr.net/npm/summernote@0.8.18/dist/summernote-lite.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/summernote@0.8.18/dist/summernote-lite.min.js"></script>
    
    <!-- Driver.js for Tour -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/driver.js@1.0.1/dist/driver.css"/>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
        /* تور استایل */
        .driver-popover {
            direction: rtl !important;
            text-align: right !important;
            font-family: 'Tahoma', sans-serif !important;
        }
        .driver-popover-title {
            font-weight: bold !important;
            font-size: 1.1rem !important;
        }
        .driver-popover-description {
            font-size: 0.95rem !important;
            line-height: 1.6 !important;
        }
        .driver-popover-footer {
            display: flex !important;
            align-items: center !important;
            justify-content: space-between !important;
            flex-wrap: wrap !important;
            gap: 10px !important;
        }
        .driver-popover-progress-text {
            order: 2 !important;
            margin-right: auto !important;
            margin-left: auto !important;
        }
        .driver-popover-navigation-btns {
            order: 3 !important;
        }
        #dontShowTourContainer {
            order: 1 !important;
        }
        .driver-popover-next-btn, .driver-popover-prev-btn {
            background-color: #5a8dee !important;
            text-shadow: none !important;
            color: white !important;
            border: none !important;
        }
    </style>
    <style>
        /* استایل تصاویر داخل توضیحات و ترجمه‌ها */
        .note-modal { z-index: 100001 !important; }
        .note-modal-backdrop { z-index: 100000 !important; }
        
        .explanation-box img, .translation-box img, .answer-explanation img, .answer-translation img {
            max-width: 180px !important;
            height: auto !important;
            border-radius: 8px;
            margin: 10px 0;
            display: block;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        }

        /* استایل پنل پایین (Vocabulary Bottom Sheet) */
        .vocab-bottom-sheet {
            position: fixed !important;
            bottom: -100% !important;
            left: 0 !important;
            right: 0 !important;
            background: white !important;
            border-top-left-radius: 25px !important;
            border-top-right-radius: 25px !important;
            box-shadow: 0 -5px 25px rgba(0, 0, 0, 0.2) !important;
            z-index: 9999 !important;
            transition: bottom 0.4s cubic-bezier(0.36, 1.1, 0.42, 1) !important;
            padding: 20px !important;
            max-height: 80vh !important;
            overflow-y: auto !important;
            display: block !important;
        }

        .vocab-bottom-sheet.active {
            bottom: 0 !important;
        }

        .sheet-handle {
            width: 40px !important;
            height: 4px !important;
            background: #e0e0e0 !important;
            border-radius: 2px !important;
            margin: -10px auto 15px !important;
        }

        .vocab-sheet-content {
            direction: rtl !important;
            text-align: center !important;
        }

        .vocab-word-display {
            font-size: 1.3rem !important;
            font-weight: bold !important;
            color: #333 !important;
            margin-bottom: 5px !important;
            display: block !important;
        }

        .vocab-translation-display {
            font-size: 1.1rem !important;
            color: #28a745 !important;
            background: #f8f9fa !important;
            padding: 15px !important;
            border-radius: 12px !important;
            margin: 15px 0 !important;
            border: 1px dashed #28a745 !important;
            min-height: 50px !important;
            display: none !important;
        }

        .vocab-translation-display.active { display: block !important; }

        .vocab-actions {
            display: flex !important;
            gap: 12px !important;
            justify-content: center !important;
            margin-top: 20px !important;
        }

        .sheet-overlay {
            position: fixed !important;
            top: 0 !important;
            left: 0 !important;
            right: 0 !important;
            bottom: 0 !important;
            background: rgba(0,0,0,0.4) !important;
            z-index: 9998 !important;
            display: none !important;
            backdrop-filter: blur(2px) !important;
        }

        .custom-checkbox .checkmark:after {
            border: solid #fff;
            left: 7px;
            top: -2px;
            width: 9px;
            height: 18px;
            font-weight: bolder;
            border-width: 0 5px 5px 0;
            transform: rotate(45deg);
            box-shadow: 2px 2px 0 0 #000
        }

        .custom-checkbox input:checked~.checkmark:after {
            display: block
        }

        .checkmark:after {
            content: "";
            position: absolute;
            display: none
        }

        .custom-checkbox input:checked~.checkmark {
            background-color: #4caf50
        }

        .checkmark {
            position: absolute;
            top: 0;
            left: 0;
            border: 2px solid #000;
            background-color: #fff;
            border-radius: 4px;
            width: 23px;
            height: 23px
        }

        .custom-checkbox input {
            position: absolute;
            opacity: 0;
            cursor: pointer
        }

        .custom-checkbox {
            position: relative;
            padding-left: 30px;
            cursor: pointer;
            display: inline-block
        }

        .form-label {
            margin-bottom: 1.2rem;
        }

        .video-placeholder {
            position: relative;
            cursor: pointer;
            border-radius: 10px;
            overflow: hidden;
        }

        .video-placeholder img {
            width: 100%;
            height: auto;
        }

        .play-button {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            background: rgba(0, 0, 0, 0.7);
            border: none;
            border-radius: 50%;
            width: 80px;
            height: 80px;
            color: white;
            font-size: 30px;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
        }

        .video-counter {
            background: rgba(0, 0, 0, 0.8);
            color: white;
            padding: 3px 7px;
            border-radius: 13px;
            font-size: 12px;
        }

        .modal-dialog-xl {
            max-width: 90%;
        }

        .disabled-button {
            opacity: 0.6;
            cursor: not-allowed !important;
        }

        .video-question-mode .answers-section {
            display: none;
        }

        .video-question-mode .question-content {
            display: none;
        }

        .answers-mode .video-controls {
            display: none;
        }

        .keypad-btn {
            height: 50px;
            font-size: 18px;
            font-weight: bold;
        }

        .numeric-keypad {
            user-select: none;
        }

        /* استایل برای دکمه بوک مارک */
        .bookmark-btn {
            transition: all 0.3s ease;
        }

        .bookmark-btn:hover {
            transform: scale(1.1);
        }

        .bookmark-loading {
            opacity: 0.6;
            cursor: wait !important;
        }

        /* استایل برای پیام‌های toast */
        .bookmark-toast {
            position: fixed;
            top: 20px;
            right: 20px;
            z-index: 9999;
            min-width: 300px;
            animation: slideInRight 0.3s ease;
        }

        @keyframes slideInRight {
            from {
                transform: translateX(100%);
                opacity: 0;
            }

            to {
                transform: translateX(0);
                opacity: 1;
            }
        }

        /* Practice Mode Styles */
        .answer-item {
            transition: all 0.3s ease;
            border-radius: 8px;
            padding: 10px;
            margin-bottom: 10px;
        }

        .answer-correct-selected {
            background-color: #d4edda !important;
            border: 2px solid #28a745 !important;
        }

        .answer-incorrect-selected {
            background-color: #f8d7da !important;
            border: 2px solid #dc3545 !important;
        }

        .answer-correct-unselected {
            background-color: #f8d7da !important;
            border: 2px solid #dc3545 !important;
        }

        .answer-incorrect-unselected {
            background-color: #d4edda !important;
            border: 2px solid #28a745 !important;
        }

        .question-btn-correct {
            background-color: #28a745 !important;
            border-color: #28a745 !important;
        }

        .question-btn-incorrect {
            background-color: #dc3545 !important;
            border-color: #dc3545 !important;
        }

        .practice-mode .custom-checkbox input:disabled {
            pointer-events: none;
        }

        .practice-mode .answer-item.disabled {
            pointer-events: none;
            opacity: 0.8;
        }

        /* استایل‌های جدید برای دایره‌های رنگی وضعیت */
        .question-status-indicator {
            position: absolute;
            top: 2px;
            right: 2px;
            width: 10px;
            height: 10px;
            border-radius: 50%;
            border: .5px solid white;
            z-index: 10;
        }

        .status-gray {
            background-color: #6c757d;
        }

        .status-blue {
            background-color: #0d6efd;
        }

        .status-green {
            background-color: #198754;
        }

        .status-yellow {
            background-color: #ffc107;
        }

        .status-red {
            background-color: #dc3545;
        }

        .question-btn-container {
            position: relative;
            display: inline-block;
        }

        /* استایل برای انتخاب متن */
        .vocabulary-selection {
            position: relative;
            user-select: text;
        }

        /* استایل برای متن انتخاب شده */
        .selected-word {
            background-color: #fff3cd;
            border-radius: 3px;
            padding: 1px 2px;
        }

        
        /* استایل پنل پایین (Vocabulary Bottom Sheet) */
        .vocab-bottom-sheet {
            position: fixed;
            bottom: -100%;
            left: 0;
            right: 0;
            background: white;
            border-top-left-radius: 25px;
            border-top-right-radius: 25px;
            box-shadow: 0 -5px 25px rgba(0, 0, 0, 0.2);
            z-index: 3000;
            transition: bottom 0.4s cubic-bezier(0.36, 1.1, 0.42, 1);
            padding: 20px;
            max-height: 80vh;
            overflow-y: auto;
        }

        .vocab-bottom-sheet.active {
            bottom: 0;
        }

        .sheet-handle {
            width: 40px;
            height: 4px;
            background: #e0e0e0;
            border-radius: 2px;
            margin: -10px auto 15px;
        }

        .vocab-sheet-content {
            direction: rtl;
            text-align: center;
        }

        .vocab-word-display {
            font-size: 1.3rem;
            font-weight: bold;
            color: #333;
            margin-bottom: 5px;
            display: block;
        }

        .vocab-translation-display {
            font-size: 1.1rem;
            color: #28a745;
            background: #f8f9fa;
            padding: 15px;
            border-radius: 12px;
            margin: 15px 0;
            border: 1px dashed #28a745;
            min-height: 50px;
            display: none;
        }

        .vocab-translation-display.active { display: block !important; }

        .vocab-actions {
            display: flex;
            gap: 12px;
            justify-content: center;
            margin-top: 20px;
        }

        .sheet-overlay {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0,0,0,0.4);
            z-index: 2999;
            display: none;
            backdrop-filter: blur(2px);
        }

        .vocab-icons, .vocab-icon, .translation-popup, .popup-overlay {
            display: none !important;
        }


        /* Toast notifications */
        .vocab-toast {
            position: fixed;
            top: 20px;
            right: 20px;
            z-index: 1200;
            min-width: 300px;
            animation: slideInRight 0.3s ease;
        }

        .btn-circle {
            border-radius: 50%;
            margin-right: 10px;
        }

        .translation-editable {
            background-color: #fff3cd;
            border: 2px dashed #ffc107;
            padding: 8px;
            border-radius: 5px;
            cursor: text;
        }

        .translation-editable:focus {
            outline: none;
            border-color: #ff9800;
            background-color: #fffaeb;
        }

        .edit-hint {
            font-size: 11px;
            color: #666;
            margin-top: 5px;
        }

        .save-word-btn:disabled {
            opacity: 0.6;
            cursor: not-allowed;
        }

        /* ============================================
           استایل‌های جدید برای ترجمه و توضیح
           ============================================ */
        
        /* Translation/Explanation Content Box */
        .translation-box, .explanation-box {
            margin-top: 15px;
            padding: 15px;
            border-radius: 10px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            animation: slideDown 0.3s ease;
            position: relative;
        }

        .explanation-box {
            background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
        }

        .translation-box h6, .explanation-box h6 {
            margin: 0 0 10px 0;
            font-weight: bold;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .translation-box p, .explanation-box p {
            margin: 0;
            line-height: 1.6;
            direction: rtl;
            text-align: right;
        }

        @keyframes slideDown {
            from {
                opacity: 0;
                transform: translateY(-10px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        /* Answer Translation/Explanation Styles */
        .answer-translation, .answer-explanation {
            margin-top: 8px;
            padding: 10px;
            border-radius: 8px;
            background-color: rgba(102, 126, 234, 0.1);
            border-right: 3px solid #667eea;
            direction: rtl;
            text-align: right;
            font-size: 0.9rem;
            animation: fadeIn 0.3s ease;
        }

        .answer-explanation {
            background-color: rgba(240, 147, 251, 0.1);
            border-right-color: #f093fb;
        }

        .answer-translation strong, .answer-explanation strong {
            color: #667eea;
        }

        .answer-explanation strong {
            color: #f5576c;
        }

        /* Active State for Buttons */
        #translateBtn.active {
            background-color: #667eea !important;
            color: white !important;
            box-shadow: 0 0 10px rgba(102, 126, 234, 0.5);
        }

        #explainBtn.active {
            background-color: #f5576c !important;
            color: white !important;
            box-shadow: 0 0 10px rgba(245, 87, 108, 0.5);
        }

        /* Button Hover Effects */
        #translateBtn:hover, #explainBtn:hover {
            transform: scale(1.05);
            transition: all 0.2s ease;
        }

        /* Empty State */
        .no-translation, .no-explanation {
            padding: 10px;
            border-radius: 8px;
            background-color: #f8f9fa;
            color: #6c757d;
            text-align: center;
            font-style: italic;
            direction: rtl;
        }

        /* Pretext Translation */
        .pretext-translation, .pretext-explanation {
            margin-top: 10px;
            padding: 12px;
            border-radius: 8px;
            background: linear-gradient(135deg, rgba(102, 126, 234, 0.15) 0%, rgba(118, 75, 162, 0.15) 100%);
            border-right: 4px solid #667eea;
            direction: rtl;
            text-align: right;
        }

        .pretext-explanation {
            background: linear-gradient(135deg, rgba(240, 147, 251, 0.15) 0%, rgba(245, 87, 108, 0.15) 100%);
            border-right-color: #f5576c;
        }
        /* در بخش style اضافه کنید: */
.answer-item {
    align-items: flex-start !important; /* تغییر از center به flex-start */
}
/* در بخش style اضافه کنید: */

/* استایل ویژه برای وقتی که هر دو فعال هستند */
.translation-box.with-explanation {
    margin-bottom: 10px;
}

.explanation-box.with-translation {
    margin-top: 10px;
}

/* استایل برای پاسخ‌ها وقتی هر دو فعال هستند */
.answer-translation.with-explanation {
    margin-bottom: 5px;
    border-bottom-left-radius: 0;
    border-bottom-right-radius: 0;
}

.answer-explanation.with-translation {
    margin-top: 0;
    border-top-left-radius: 0;
    border-top-right-radius: 0;
    border-top: 1px dashed rgba(102, 126, 234, 0.3);
}
.answer-item .flex-grow-1 {
    width: 100%;
}

.answer-translation, .answer-explanation {
    margin-top: 8px;
    padding: 10px;
    border-radius: 8px;
    background-color: rgba(102, 126, 234, 0.1);
    border-right: 3px solid #667eea;
    direction: rtl;
    text-align: right;
    font-size: 0.9rem;
    animation: fadeIn 0.3s ease;
    display: block; /* اطمینان از نمایش به صورت بلوک */
    width: 100%;
}

.answer-explanation {
    background-color: rgba(240, 147, 251, 0.1);
    border-right-color: #f093fb;
}
.vocabulary-selection {
    position: relative;
    user-select: text; /* دسکتاپ: سلکت عادی */
}

/* فقط موبایل */
@media (hover: none) and (pointer: coarse) {
    .vocabulary-selection {
        -webkit-user-select: none;
        user-select: none;
    }
}
    </style>
<style>
/* Adjust button sizes and header padding */
.header-bar { padding-top: 0.25rem !important; padding-bottom: 0.25rem !important; }
/* Center middle button group */
.header-bar .header-middle-buttons { margin-left: auto; margin-right: auto; }
.header-bar .btn { padding: 0.25rem 0.5rem !important; font-size: 0.75rem; }
.header-bar .code-exit-wrapper .btn-circle { margin-right: 3px !important; }
.header-bar .header-middle-buttons { margin-left: auto !important; margin-right: auto !important; display: flex; align-items: center; gap: 0.2rem; }
.header-bar .gap-2 { gap: 0.1rem !important; }
.header-bar #code { margin-left: 0.05rem !important; font-size: .7rem !important; }
</style>
</head>

<body style="min-height: 100vh; background-color: #d3f5da;" class="<?= $mode === 'practice' ? 'practice-mode' : '' ?>">
    <!-- Vocabulary Bottom Sheet -->
    <div id="sheet-overlay" class="sheet-overlay" onclick="closeVocabSheet()"></div>
    <div id="vocab-sheet" class="vocab-bottom-sheet">
        <div class="sheet-handle"></div>
        <div class="vocab-sheet-content">
            <span id="sheet-original-word" class="vocab-word-display"></span>
            
            <div id="sheet-translation-box" class="vocab-translation-display">
                <div contenteditable="true" id="sheet-translated-word" class="translation-editable"></div>
                <small class="edit-hint text-muted d-block mt-2">
                    <i class="fas fa-edit"></i> قابل ویرایش
                </small>
            </div>

            <div class="vocab-actions">
                <button id="sheet-translate-btn" class="btn btn-success btn-lg px-4" onclick="translateWord()">
                    <i class="fas fa-language"></i> ترجمه
                </button>
                <button id="sheet-save-btn" class="btn btn-warning btn-lg px-4" onclick="saveWord()" style="display: none;">
                    <i class="fas fa-save"></i> ذخیره در کلکشن
                </button>
                
                <button class="btn btn-outline-secondary btn-lg px-3" onclick="closeVocabSheet()">
                    <i class="fas fa-times"></i>
                </button>
            </div>
        </div>
    </div>
    <div class="container" style="min-height: 100vh;">
        <div class="text-white bg-success d-flex justify-content-between align-items-center p-1 px-2 header-bar" style="border-bottom-right-radius: 5px;border-bottom-left-radius: 5px;position: sticky;top: 0;z-index: 1000;">
            <div class="code-exit-wrapper d-flex align-items-center"><a class="btn btn-warning btn-sm btn-danger btn-circle" href="../admin/practice.php"> <i class="fas fa-times"></i></a><span id="code"></span></div>
            <div class="header-middle-buttons d-flex align-items-center gap-2 mx-auto">
                <button class="btn btn-sm btn-light" id="translateBtn" onclick="toggleTranslation()" title="ترجمه سوال و پاسخ‌ها">ترجمه</button>
                <button class="btn btn-sm btn-info" id="explainBtn" onclick="toggleExplanation()" title="توضیح سوال و پاسخ‌ها">توضیح</button>
                <button class="btn btn-sm btn-secondary" id="runmaBtn" onclick="startGuidedTour()" title="رانما"><i class="fas fa-question-circle"></i></button>
           <span id="report-btn " class=" mx-1 btn-sm p-0"
                            onclick="openReportModal()" title="گزارش مشکل در سوال">
                            <i class="fas fa-exclamation-circle"></i>
</span>
            </div>
                <?php if ($isAdmin): ?>
                <button class="btn btn-sm btn-primary" id="geminiFetchBtn" onclick="geminiFetchInfo()" title="درک مطلب و ترجمه با هوش مصنوعی (Gemini)">
                    <i class="fas fa-brain"></i>
                </button>
                <button class="btn btn-sm btn-warning" id="botFetchBtn" onclick="botFetchInfo()" title="واکشی ربات (ترجمه و توضیح خودکار)">
                    <i class="fas fa-robot"></i>
                </button>
                <button class="btn btn-sm btn-dark" id="openSourceBtn" onclick="openSourceLink()" title="مشاهده منبع اصلی (سایت مرجع)">
                    <i class="fas fa-external-link-alt"></i>
                </button>
                <button class="btn btn-sm btn-secondary" id="manageTagsBtn" onclick="openTagsModal()" title="مدیریت دسته‌های خاص (تگ‌ها)">
                    <i class="fas fa-tags"></i>
                </button>
                <?php endif; ?>
                    
                <span>نمره: <span id="punkt"></span></span>

        </div>
        <div class="mt-4 p-4" style="padding-bottom: 350px !important;">
            <h6 id="text" class="fw-bold mb-4 question-text"></h6>
            
            <!-- Question Translation/Explanation Container -->
            <div id="question-translation-container"></div>
            <div id="question-explanation-container"></div>
            
            <div class="row">
                <div class="col-12 col-md-6 " id="media">
                </div>
                <div class="col-12 col-md-6">
                    <div class="d-flex flex-column gap-3">
                        <div class="d-flex align-items-center">
                        </div>
                        <div id="asw_pretext" class="fw-bold text-start" style="width: 100%; direction: rtl;"><!-- اگر موجود بود --> </div>
                        
                        <!-- Pretext Translation/Explanation Container -->
                        <div id="pretext-translation-container"></div>
                        <div id="pretext-explanation-container"></div>

                        <!-- Video Controls Section -->
                        <div id="video-controls" class="video-controls" style="display: none;">
                            <div class="text-center">
                                <button id="video-start-btn" class="btn btn-primary btn-lg mb-3" onclick="playVideo()">
                                    Video starten
                                </button>
                                <div class="mb-3">
                                    <span class="video-counter">
                                        Sie können das Video insgesamt <span id="remaining-views">5</span> Mal ansehen.
                                    </span>
                                </div>
                                <button id="zur-aufgabe-btn" class="btn btn-success" style="display: none;"
                                    onclick="showAnswers()">
                                    Zur Aufgabenstellung
                                </button>
                            </div>
                        </div>

                        <!-- Answers Section -->
                        <div id="answers" class="answers-section">

                        </div>

                    </div>
                </div>
            </div>
        </div>
        <div class="fixed-bottom container-fluid"
            style="margin-bottom: 50px;padding: 5px;background-color: #aad7aa;border-radius: 30px 30px 0 0;;width: 92%;">
            <div class="d-flex align-items-center"></div>
            <div class="row px-4 py-2">
                <div class="col-4 fw-bold text-start p-0">
                    <span class="badge bg-warning text-dark" style="direction: rtl;"> <?= $totalQuestions ?> سوال
                    </span>
                      <button id="help-request" class="btn btn-primary btn-sm" style="font-size: .6rem;" onclick="openHelpModal()">کمک</button>
                            </button>
                </div>
                <div class="col-8 text-end">
                    <div class="text-end">

                        <?php if ($mode === 'practice'): ?>
                            <button id="solve-btn" class="btn btn-warning btn-sm p-1" onclick="solveQuestion()"
                                style="display: none;"> مشاهده پاسخ
                                <i class="fas fa-lightbulb"></i>
                            </button>
                            <button id="next-btn" class="btn btn-success mx-1 btn-sm p-1" onclick="nextQuestion()"
                                style="display: none;">
                                بعدی <i class="fas fa-arrow-right"></i>
                            </button>
                        <?php else: ?>
                            <button class="btn btn-success mx-1 btn-sm p-1" onclick="nextQuestion()">بعدی <i
                                    class="fas fa-arrow-right"></i></button>
                        <?php endif; ?>

                        <button id="bookmark-btn" class="btn btn-primary mx-1 btn-sm p-1 bookmark-btn"
                            onclick="toggleBookmark()" title="علامت گذاری سوال">
                            <i id="bookmark-icon" class="far fa-star text-warning"></i>
                        </button>
                
                    </div>
                </div>
            </div>
        </div>
        <div class="fixed-bottom container-fluid p-0">
            <div class="d-flex justify-content-between align-items-center p-2 px-md-4 px-2"
                style="background: var(--bs-success);">
                <div class="d-flex gap-1">
                    <button class="btn btn-light text-success btn-sm px-2" onclick="goToFirstQuestion()" title="سوال اول">
                        <i class="fas fa-fast-backward"></i>
                    </button>
                    <button class="btn btn-light text-success btn-sm px-2" onclick="previousQuestion()" title="قبلی">
                        <i class="fas fa-step-backward"></i>
                    </button>
                </div>
                
                <div class="d-flex gap-1 overflow-auto mx-1 justify-content-center" id="question-buttons" style="scrollbar-width: none; -ms-overflow-style: none;">
                    <!-- Question buttons will be rendered here -->
                </div>

                <div class="d-flex gap-1">
                    <button class="btn btn-light text-success btn-sm px-2" onclick="nextQuestion()" title="بعدی">
                        <i class="fas fa-step-forward"></i>
                    </button>
                    <button class="btn btn-light text-success btn-sm px-2" onclick="goToLastQuestion()" title="سوال آخر">
                        <i class="fas fa-fast-forward"></i>
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Report Modal -->
    <div class="modal fade" id="reportModal" tabindex="-1" aria-hidden="true" style="z-index: 99999;">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">گزارش مشکل سوال</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body" style="direction: rtl; text-align: right;">
                    <?php
                        $rewardDays = 10;
                        $envPath = __DIR__ . '/../miad/.env';
                        if (file_exists($envPath)) {
                            $envContent = file_get_contents($envPath);
                            if (preg_match('/^REPORT_REWARD_DAYS=(\d+)/m', $envContent, $matches)) {
                                $rewardDays = (int)$matches[1];
                            }
                        }
                    ?>
                    <p class="text-info mb-3">
                        <i class="fas fa-gift"></i> در صورتی که گزارش شما توسط مدیر تایید شود، <strong><?= $rewardDays ?> روز اشتراک VIP هدیه</strong> به شما تعلق خواهد گرفت. وضعیت گزارش را می‌توانید در <strong>داشبورد کاربری</strong> خود پیگیری کنید.
                    </p>
                    <div class="mb-3">
                        <label for="report-message" class="form-label">توضیح مشکل:</label>
                        <textarea id="report-message" class="form-control" rows="4" placeholder="مثلاً: ترجمه اشتباه است، تصویر لود نمی‌شود و..."></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">لغو</button>
                    <button type="button" class="btn btn-danger" onclick="submitReport()">ارسال گزارش</button>
                </div>
            </div>
        </div>
    </div>
    <!-- Help Request Modal -->
    <div class="modal fade" id="helpModal" tabindex="-1" aria-hidden="true" style="z-index: 99999;">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">درخواست راهنمایی</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body" style="direction: rtl; text-align: right;">
                    <p>برای توضیح بیشتر دربارهٔ سؤال شما، مستقیماً به پشتیبان متصل خواهید شد. پشتیبان در اسرع وقت سؤال شما را توضیح خواهد داد.</p>
                </div>
                <div class="modal-footer justify-content-center">
                    <button type="button" class="btn btn-primary" onclick="sendHelpRequest()">موافقم</button>
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">انصراف</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Tags Modal -->
    <div class="modal fade" id="tagsModal" tabindex="-1" aria-hidden="true" style="z-index: 99999;">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">مدیریت دسته‌های خاص</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body" style="direction: rtl; text-align: right;">
                    <div id="tags-loading" class="text-center my-3"><i class="fas fa-spinner fa-spin fa-2x text-primary"></i></div>
                    <div id="tags-container" style="display: none;">
                        <h6 class="mb-3">دسته‌های اختصاص داده شده:</h6>
                        <div id="attached-tags" class="d-flex flex-wrap gap-2 mb-4"></div>
                        <hr>
                        <h6 class="mb-3">انتخاب از دسته‌های موجود:</h6>
                        <div id="available-tags" class="d-flex flex-wrap gap-2 mb-4"></div>
                        <hr>
                        <h6 class="mb-3">ایجاد دسته جدید:</h6>
                        <div class="input-group mb-3">
                            <input type="text" id="new-tag-name" class="form-control" placeholder="نام دسته جدید...">
                            <input type="color" id="new-tag-color" class="form-control form-control-color" value="#0d6efd" title="رنگ دسته">
                            <button class="btn btn-outline-primary" type="button" onclick="createNewTag()">ایجاد و افزودن</button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Editor Modal -->
    <div class="modal fade" id="editorModal" tabindex="-1" aria-hidden="true" style="z-index: 99999;">
        <div class="modal-dialog modal-dialog-xl modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">ویرایشگر پیشرفته</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body" style="direction: rtl; text-align: right;">
                    <div id="summernote"></div>
                </div>
                <div class="modal-footer justify-content-between">
                    <div>
                        <button type="button" class="btn btn-info" id="ai-image-btn" onclick="generateAiImage()">
                            <i class="fas fa-magic"></i> تولید تصویر هوش مصنوعی
                        </button>
                    </div>
                    <div>
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">لغو</button>
                        <button type="button" class="btn btn-primary" id="save-editor-btn">ذخیره</button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Video Modal -->
    <div class="modal fade" id="videoModal" tabindex="-1" aria-labelledby="videoModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-xl modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="videoModalLabel">Video</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body p-0">
                    <video id="modal-video" width="100%" controls>
                        <source src="" type="video/mp4">
                        Your browser does not support the video tag.
                    </video>
                </div>
            </div>
        </div>
    </div>
    
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

    <script>
        const csrfToken = '<?= $csrf_token ?>';
        const isAdmin = <?= json_encode($isAdmin) ?>;

        const selectedQuestions = <?= json_encode($selectedQuestions) ?>;
        const mode = '<?= $mode ?>';
        const isVip = <?= json_encode($isVip) ?>;
        const questionLimit = <?= json_encode($questionLimit) ?>;
        const maxAccessibleId = <?= $maxAccessibleId ?>;
        
        let currentQuestionIndex = <?= $currentQuestionIndex ?>;
        let questionsPerPage;
        let currentQuestionData = null;
        let isVideoQuestion = false;
        let videoViewCount = 0;
        let maxVideoViews = 5;
        let hasWatchedVideo = false;
        let showingAnswers = false;
        let questionSolved = false;
        let userAnswers = {};
        let hasUserAnswer = false;
        let solvedQuestions = {}; // Track solved questions in memory
        let questionStatuses = {}; // برای ذخیره وضعیت رنگی سوالات
        let selectedText = '';
        let selectedRange = null;
        let currentTranslation = '';
        let currentWord = '';
        let currentWordContext = ""; // کانتکست کلمه برای ترجمه دقیق‌تر

        let vocabularyState = {
            translated: false,
            canSave: false
        };
        // Variables to track current context
        let currentQuestionId = null;
        let currentCategoryId = null;

        // ============================================
        // متغیرهای جدید برای ترجمه و توضیح
        // ============================================
        let translationActive = false;
        let explanationActive = false;


        function createFormDataWithCSRF(data = {}) {
            const formData = new URLSearchParams();
            formData.append('csrf_token', csrfToken);

            for (const [key, value] of Object.entries(data)) {
                formData.append(key, value);
            }

            return formData;
        }

        function updateQuestionsPerPage() {
            const width = window.innerWidth;
            if (width >= 992) {       // دسکتاپ
                questionsPerPage = 20;
            } else if (width >= 768) { // تبلت
                questionsPerPage = 8;
            } else {                  // موبایل
                questionsPerPage = 3;
            }
        }

        document.addEventListener("DOMContentLoaded", function () {
            loadQuestionStatuses(() => {
                loadCurrentQuestion();
                renderPageButtons();
                updateNavigationButtons();
            });
            initVocabularySystem();
            
            // Handle video modal close
            const videoModalEl = document.getElementById('videoModal');
            if (videoModalEl) {
                videoModalEl.addEventListener('hidden.bs.modal', function () {
                    const modalVideo = document.getElementById('modal-video');
                    if (modalVideo) {
                        modalVideo.pause();
                        modalVideo.src = '';
                    }
                    
                    // Force remove backdrop if it gets stuck
                    const backdrops = document.querySelectorAll('.modal-backdrop');
                    backdrops.forEach(b => b.remove());
                    document.body.classList.remove('modal-open');
                    document.body.style.overflow = '';
                    document.body.style.paddingRight = '';
                });
            }
        });

        window.addEventListener('resize', () => {
            renderPageButtons();
        });
        
        function initializeVocabularySystem() {
            // Initialize vocabulary system
            initVocabularySystem();

            // Get current question and category IDs
            if (typeof selectedQuestions !== 'undefined' && typeof currentQuestionIndex !== 'undefined') {
                currentQuestionId = selectedQuestions[currentQuestionIndex];
            }
        }
        
        
        // Vocabulary System Functions (Optimized)
        function initVocabularySystem() {
            // استفاده از Event Delegation برای پایداری بیشتر
            document.body.addEventListener('mouseup', function(event) {
                // اگر روی پنل یا کادرهای ویرایش ادمین کلیک شده، کاری نکن
                if (event.target.closest('#vocab-sheet') || 
                    event.target.closest('#sheet-overlay') || 
                    event.target.closest('[contenteditable="true"]')) {
                    return;
                }

                // فقط در بخش‌های مجاز (سوال و جواب) عمل کن
                // دکمه‌های کیبورد عددی نباید باعث باز شدن پنل ترجمه شوند
                if (event.target.closest('.keypad-btn') || event.target.closest('.btn-outline-danger')) {
                    return;
                }

                if (event.target.closest('.vocabulary-selection') || 
                    event.target.closest('#text') || 
                    event.target.closest('#answers')) {
                    
                    handleTextSelection(event);
                }
            });

            // بستن با کلیک خارج
            document.addEventListener('mousedown', function (e) {
                if (e.target.closest('#vocab-sheet') || e.target.closest('#sheet-overlay')) {
                    return;
                }
                if (!window.getSelection().toString().trim()) {
                    // فقط اگر در حال انتخاب متن نیست، ببند
                    // closeVocabSheet(); // فعلا غیرفعال برای جلوگیری از بستن ناگهانی
                }
            }, true);
        }

        function addTextSelectionListeners(element) {
            const isMobile = window.matchMedia("(hover: none) and (pointer: coarse)").matches;

            if (isMobile) {
                let longPressTimer = null;
                let touchStartX = 0, touchStartY = 0;

                element.addEventListener('touchstart', function(e) {
                    const touch = e.touches[0];
                    touchStartX = touch.clientX;
                    touchStartY = touch.clientY;

                    longPressTimer = setTimeout(() => {
                        const data = getWordAndContextAtPoint(touch.clientX, touch.clientY);
                        if (data && isValidWord(data.word)) {
                            showVocabSheet(data.word, data.context);
                            if (navigator.vibrate) navigator.vibrate(50);
                        }
                    }, 500);
                }, { passive: true });

                element.addEventListener('touchmove', function(e) {
                    const touch = e.touches[0];
                    if (Math.abs(touch.clientX - touchStartX) > 10 || Math.abs(touch.clientY - touchStartY) > 10) {
                        clearTimeout(longPressTimer);
                    }
                }, { passive: true });

                element.addEventListener('touchend', () => clearTimeout(longPressTimer), { passive: true });
            } else {
                element.addEventListener('mouseup', handleTextSelection);
            }
        }

        function handleTextSelection(event) {
            setTimeout(() => {
                const selection = window.getSelection();
                const text = selection.toString().trim();
                
                if (text && isValidWord(text)) {
                    let context = "";
                    if (selection.rangeCount > 0) {
                        context = selection.getRangeAt(0).startContainer.textContent;
                    }
                    showVocabSheet(text, context);
                } else {
                    // اگر متنی انتخاب نشده، کلمه‌ای که روی آن کلیک شده را پیدا کن
                    const data = getWordAndContextAtPoint(event.clientX, event.clientY);
                    if (data && isValidWord(data.word)) {
                        showVocabSheet(data.word, data.context);
                    }
                }
            }, 200);
        }

        function getWordAndContextAtPoint(x, y) {
            let range = document.caretRangeFromPoint ? document.caretRangeFromPoint(x, y) : null;
            if (!range || range.startContainer.nodeType !== Node.TEXT_NODE) return null;
            const text = range.startContainer.textContent;
            let start = range.startOffset, end = range.startOffset;
            while (start > 0 && /[a-zA-ZäöüßÄÖÜ]/.test(text[start - 1])) start--;
            while (end < text.length && /[a-zA-ZäöüßÄÖÜ]/.test(text[end])) end++;
            const word = text.substring(start, end);
            return word.length >= 2 ? { word: word, context: text } : null;
        }

        function isValidWord(text) {
            const trimmed = text.trim();
            return trimmed.split(/\s+/).length === 1 && trimmed.length >= 2 && /[a-zA-ZäöüßÄÖÜ]/.test(trimmed);
        }

        function showVocabSheet(word, context = "") {
            selectedText = word;
            currentWord = word;
            currentWordContext = context;
            const originalWordEl = document.getElementById('sheet-original-word');
            const translationBoxEl = document.getElementById('sheet-translation-box');
            const saveBtnEl = document.getElementById('sheet-save-btn');
            const translateBtnEl = document.getElementById('sheet-translate-btn');
            const sheetEl = document.getElementById('vocab-sheet');
            const overlayEl = document.getElementById('sheet-overlay');

            if (originalWordEl) originalWordEl.textContent = word;
            if (translationBoxEl) translationBoxEl.classList.remove('active');
            if (saveBtnEl) saveBtnEl.style.display = 'none';
            if (translateBtnEl) translateBtnEl.style.display = 'inline-block';
            if (sheetEl) sheetEl.classList.add('active');
            if (overlayEl) overlayEl.style.display = 'block';
        }

        function closeVocabSheet() {
            const sheetEl = document.getElementById('vocab-sheet');
            const overlayEl = document.getElementById('sheet-overlay');
            
            if (sheetEl) sheetEl.classList.remove('active');
            if (overlayEl) overlayEl.style.display = 'none';
            clearSelection();
        }

        function clearSelection() {
            if (window.getSelection) window.getSelection().removeAllRanges();
            selectedText = '';
        }

        function translateWord() {
            const wordEl = document.getElementById('sheet-original-word');
            const word = wordEl ? wordEl.textContent.trim() : null;
            
            if (!word) {
                showVocabToast('کلمه‌ای انتخاب نشده است', 'error');
                return;
            }

            const btn = document.getElementById('sheet-translate-btn');
            const original = btn.innerHTML;
            btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';
            btn.disabled = true;

            const formData = createFormDataWithCSRF({ 
                word: word,
                context: currentWordContext
            });
            
            // ابتدا از جمینای برای ترجمه با کانتکست استفاده می‌کنیم
            fetch('../incloud/gemini_translate.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: formData
            })
            .then(r => r.json())
            .then(data => {
                if (data.success && data.translation) {
                    displaySheetTranslation(word, data.translation, data.in_user_collection);
                } else {
                    // اگر جمینای خطا داد، از سیستم قدیمی استفاده کن
                    fallbackTranslation(word);
                }
            })
            .catch(() => fallbackTranslation(word))
            .finally(() => {
                btn.innerHTML = original;
                btn.disabled = false;
            });
        }

        function fallbackTranslation(word) {
            const formData = createFormDataWithCSRF({ word: word });
            fetch('../incloud/get_translation.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: formData
            })
            .then(r => r.json())
            .then(data => {
                if (data.success && data.translation) {
                    displaySheetTranslation(word, data.translation, data.in_user_collection);
                } else {
                    googleTranslate(word);
                }
            })
            .catch(() => googleTranslate(word));
        }

        function googleTranslate(text) {
            if (!text) return;
            const formData = createFormDataWithCSRF({ 
                text: text, 
                from: 'de', 
                to: 'fa' 
            });
            fetch('../incloud/google_translate.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: formData
            })
            .then(r => r.json())
            .then(data => {
                if (data.success) displaySheetTranslation(text, data.translation, false);
                else showVocabToast('خطا در ترجمه گوگل', 'error');
            })
            .catch(err => {
                console.error('Google Translate Fetch Error:', err);
                showVocabToast('خطا در ارتباط با مترجم گوگل', 'error');
            });
        }

        function displaySheetTranslation(original, translation, inCollection) {
            currentTranslation = translation;
            const box = document.getElementById('sheet-translation-box');
            const input = document.getElementById('sheet-translated-word');
            const saveBtn = document.getElementById('sheet-save-btn');
            const translateBtn = document.getElementById('sheet-translate-btn');

            if (input) input.textContent = translation;
            if (box) box.classList.add('active');
            if (translateBtn) translateBtn.style.display = 'none';
            
            if (saveBtn) {
                saveBtn.style.display = 'inline-block';
                if (inCollection) {
                    saveBtn.innerHTML = '<i class="fas fa-check"></i> در کلکشن موجود است';
                    saveBtn.disabled = true;
                    saveBtn.className = 'btn btn-outline-success btn-lg px-4';
                } else {
                    saveBtn.innerHTML = '<i class="fas fa-plus"></i> ذخیره کلمه';
                    saveBtn.disabled = false;
                    saveBtn.className = 'btn btn-warning btn-lg px-4';
                }
            }
        }

        function saveWord() {
            const edited = document.getElementById('sheet-translated-word').textContent.trim();
            if (!edited) return;
            const btn = document.getElementById('sheet-save-btn');
            const original = btn.innerHTML;
            btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';
            btn.disabled = true;

            const formData = createFormDataWithCSRF({
                word: currentWord,
                translation: edited,
                question_id: currentQuestionId
            });

            fetch('../incloud/save_vocabulary.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: formData
            })
            .then(r => r.json())
            .then(data => {
                if (data.success) {
                    btn.innerHTML = '<i class="fas fa-check"></i> ذخیره شد';
                    btn.className = 'btn btn-success btn-lg px-4';
                    setTimeout(closeVocabSheet, 1200);
                } else {
                    btn.innerHTML = original;
                    btn.disabled = false;
                    showVocabToast(data.error, 'error');
                }
            });
        }

        function updateVocabularyContext(questionId, categoryId = null) {
            currentQuestionId = questionId;
            currentCategoryId = categoryId;
        }

        function showVocabToast(message, type = 'info') {
            const toast = document.createElement('div');
            toast.className = `alert alert-${type === 'error' ? 'danger' : 'success'} vocab-toast alert-dismissible`;
            toast.innerHTML = `<span>${message}</span><button type="button" class="btn-close" onclick="this.parentElement.remove()"></button>`;
            document.body.appendChild(toast);
            setTimeout(() => toast.remove(), 3000);
        }        
        
        // بارگذاری وضعیت رنگی تمام سوالات
        function loadQuestionStatuses(callback) {
            const formData = createFormDataWithCSRF({
                question_ids: JSON.stringify(selectedQuestions)
            });

            fetch("../incloud/get_question_statuses.php", {
                method: "POST",
                headers: {
                    "Content-Type": "application/x-www-form-urlencoded"
                },
                body: formData
            })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        questionStatuses = data.data;
                    } else {
                        console.error('خطا در دریافت وضعیت سوالات:', data.error);
                        selectedQuestions.forEach(id => {
                            questionStatuses[id] = { color: 'gray', correct: 0, incorrect: 0 };
                        });
                    }
                    if (callback) callback();
                })
                .catch(error => {
                    console.error('خطا در ارتباط با سرور:', error);
                    selectedQuestions.forEach(id => {
                        questionStatuses[id] = { color: 'gray', correct: 0, incorrect: 0 };
                    });
                    if (callback) callback();
                });
        }

        // ثبت پاسخ کاربر و به‌روزرسانی وضعیت
        function updateAnswerStatus(questionId, isCorrect) {
            const formData = createFormDataWithCSRF({
                question_id: questionId,
                is_correct: isCorrect ? 1 : 0
            });

            fetch("../incloud/update_answer_status.php", {
                method: "POST",
                headers: {
                    "Content-Type": "application/x-www-form-urlencoded"
                },
                body: formData
            })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        questionStatuses[questionId] = {
                            color: data.color,
                            correct: data.correct,
                            incorrect: data.incorrect
                        };
                        renderPageButtons();
                    } else {
                        console.error('خطا در ثبت وضعیت پاسخ:', data.error);
                    }
                })
                .catch(error => {
                    console.error('خطا در ارتباط با سرور:', error);
                });
        }

        // ============================================
        // توابع جدید برای ترجمه و توضیح
        // ============================================
        
      function toggleTranslation() {
    const btn = document.getElementById('translateBtn');
    const explainBtn = document.getElementById('explainBtn');
    
    if (translationActive) {
        // Hide translation
        translationActive = false;
        btn.classList.remove('active');
        hideTranslationContent();
    } else {
        // Show translation only
        translationActive = true;
        btn.classList.add('active');
        
        // Hide explanation if active
        if (explanationActive) {
            explanationActive = false;
            explainBtn.classList.remove('active');
            hideExplanationContent();
        }
        
        showTranslationContent();
        setTimeout(() => {
            window.scrollTo({ top: document.documentElement.scrollHeight, behavior: 'smooth' });
        }, 100);
    }
}
function toggleExplanation() {
    const btn = document.getElementById('explainBtn');
    const translateBtn = document.getElementById('translateBtn');

    // در حالت تمرین، اگر سوال حل نشده، پیام بده
    if (mode === 'practice' && !questionSolved) {
        showVocabToast('ابتدا به سوال پاسخ دهید', 'error');
        return;
    }

    if (explanationActive) {
        explanationActive = false;
        btn.classList.remove('active');
        hideExplanationContent();

        if (!translationActive) {
            hideTranslationContent();
        }
    } else {
        explanationActive = true;
        btn.classList.add('active');

        showTranslationContent();
        showExplanationContent();
        setTimeout(() => {
            window.scrollTo({ top: document.documentElement.scrollHeight, behavior: 'smooth' });
        }, 100);
    }
}

function openSourceLink() {
    if (!isAdmin || !currentQuestionData) return;
    
    const question = currentQuestionData.question;
    const number = question.number.toLowerCase().replace(/\./g, '-');
    let text = question.text.toLowerCase();
    
    // جایگزینی حروف آلمانی
    const replacements = { 'ä': 'ae', 'ö': 'oe', 'ü': 'ue', 'ß': 'ss' };
    for (let char in replacements) {
        text = text.split(char).join(replacements[char]);
    }
    
    // تبدیل به Slug
    const slug = text.replace(/[^a-z0-9]+/g, '-').replace(/^-+|-+$/g, '');
    
    const url = `https://www.fuehrerschein-bestehen.de/Erklaerungen/${slug}-${number}`;
    window.open(url, '_blank');
}

function geminiFetchInfo() {
    if (!isAdmin) return;
    
    Swal.fire({
        title: 'توجه',
        text: 'آیا مایل به درک مطلب کلی سوال و ترجمه با هوش مصنوعی (Gemini) هستید؟ (ممکن است تا یک دقیقه طول بکشد)',
        icon: 'question',
        showCancelButton: true,
        confirmButtonText: 'بله',
        cancelButtonText: 'خیر'
    }).then((result) => {
        if (!result.isConfirmed) return;
        
        const btn = document.getElementById('geminiFetchBtn');
    const originalIcon = btn.innerHTML;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';
    btn.disabled = true;
    
    const formData = createFormDataWithCSRF({
        id: currentQuestionData.question.id
    });
    
    fetch('../incloud/gemini_fetch.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showVocabToast(data.message, 'success');
            // Reload the question to show new translations
            setTimeout(() => {
                loadCurrentQuestion();
            }, 1500);
        } else {
            showVocabToast('خطا: ' + data.message, 'error');
        }
    })
    .catch(err => {
        console.error('Error fetching Gemini info:', err);
        showVocabToast('خطا در ارتباط با سرور', 'error');
    })
    .finally(() => {
        btn.innerHTML = originalIcon;
        btn.disabled = false;
    });
    });
}

function botFetchInfo() {
    if (!isAdmin) return;
    
    Swal.fire({
        title: 'توجه',
        text: 'آیا مایل به واکشی اطلاعات و ترجمه خودکار از سایت مرجع هستید؟ (ممکن است چند ثانیه طول بکشد)',
        icon: 'question',
        showCancelButton: true,
        confirmButtonText: 'بله',
        cancelButtonText: 'خیر'
    }).then((result) => {
        if (!result.isConfirmed) return;
        
        const btn = document.getElementById('botFetchBtn');
    const originalIcon = btn.innerHTML;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';
    btn.disabled = true;
    
    const formData = createFormDataWithCSRF({
        id: currentQuestionData.question.id
    });
    
    fetch('../incloud/filament_bot_fetch.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showVocabToast(data.message, 'success');
            // Reload the question to show new translations
            setTimeout(() => {
                loadCurrentQuestion();
            }, 1500);
        } else {
            showVocabToast('خطا: ' + data.message, 'error');
        }
    })
    .catch(err => {
        console.error('Error fetching bot info:', err);
        showVocabToast('خطا در ارتباط با سرور', 'error');
    })
    .finally(() => {
        btn.innerHTML = originalIcon;
        btn.disabled = false;
    });
    });
}

function saveAdminEdit(element, type, field, id) {
    if (!isAdmin) return;
    
    const newContent = element.innerHTML.trim();
    
    let originalContent = '';
    if (type === 'question') {
        originalContent = currentQuestionData.question[field] || '';
    } else if (type === 'answer') {
        const answer = currentQuestionData.answers.find(a => a.id == id);
        if (answer) originalContent = answer[field] || '';
    }

    if (newContent === originalContent) {
        // No changes made
        return;
    }

    Swal.fire({
        title: 'توجه',
        text: 'آیا ذخیره مورد تایید است؟',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: 'بله',
        cancelButtonText: 'خیر'
    }).then((result) => {
        if (!result.isConfirmed) {
            // Revert to original if cancelled
            element.innerHTML = originalContent;
            return;
        }

        const formData = createFormDataWithCSRF({
        type: type,
        field: field,
        id: id,
        content: newContent
    });
    
    // Disable editing temporarily while saving
    element.contentEditable = "false";
    element.style.opacity = "0.5";

    fetch('../incloud/update_qa_content.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showVocabToast('تغییرات با موفقیت ذخیره شد', 'success');
            // Update local object
            if (type === 'question') {
                currentQuestionData.question[field] = newContent;
            } else if (type === 'answer') {
                const answer = currentQuestionData.answers.find(a => a.id == id);
                if (answer) answer[field] = newContent;
            }
        } else {
            showVocabToast('خطا: ' + data.message, 'error');
            element.innerHTML = originalContent;
        }
    })
    .catch(err => {
        console.error('Error saving edit:', err);
        showVocabToast('خطا در ارتباط با سرور', 'error');
        element.innerHTML = originalContent;
    })
    .finally(() => {
        element.contentEditable = "true";
        element.style.opacity = "1";
    });
    });
}

function processHtmlContent(html) {
    if (!html) return '';
    
    // ۱. تبدیل پیوست‌هایی که به صورت متن نمایش داده شده‌اند به تصویر
    const parser = new DOMParser();
    const doc = parser.parseFromString(html, 'text/html');
    
    doc.querySelectorAll('a').forEach(link => {
        const href = link.getAttribute('href');
        if (href && href.match(/\.(svg|png|jpg|jpeg|webp|gif)(\?.*)?$/i)) {
            const img = document.createElement('img');
            img.src = href;
            img.style.maxWidth = '100%';
            img.style.display = 'block';
            
            // جایگزینی لینک با تصویر واقعی
            if (link.parentNode && (link.parentNode.tagName === 'FIGURE' || link.innerText.includes(href.split('/').pop()))) {
                 link.parentNode.replaceChild(img, link);
            } else {
                 link.replaceWith(img);
            }
        }
    });

    return doc.body.innerHTML;
}

function makeEditableHTML(text, type, field, id) {
    const processedText = processHtmlContent(text);
    if (isAdmin) {
        let editorBtn = '';
        let topPadding = '5px';
        if (field === 'info') {
            editorBtn = `
            <button onclick="openEditorModal('${type}', '${field}', ${id})" class="btn btn-sm btn-primary" style="position: absolute; top: -15px; left: 10px; z-index: 10;" title="درج تصویر / ادیتور">
               <i class="fas fa-image"></i>
            </button>`;
            topPadding = '25px';
        }
        
        return `
        <div style="position: relative; border: 1px dashed #ffc107; padding: ${topPadding} 5px 5px; border-radius: 4px; min-height: 40px; margin-top: 15px;">
            ${editorBtn}
            <div contenteditable="true" onblur="saveAdminEdit(this, '${type}', '${field}', ${id})">${processedText || ''}</div>
        </div>`;
    }
    return processedText || '';
}

function showTranslationContent() {
    if (!currentQuestionData) return;
    
    const question = currentQuestionData.question;
    const answers = currentQuestionData.answers;
    
    // Show question translation
    const questionTransContainer = document.getElementById('question-translation-container');
    if ((question.farsi_text && question.farsi_text.trim()) || isAdmin) {
        const additionalClass = explanationActive ? 'with-explanation' : '';
        const content = makeEditableHTML(question.farsi_text, 'question', 'farsi_text', question.id);
        questionTransContainer.innerHTML = `
            <div class="translation-box ${additionalClass}">
                ${isAdmin ? `<h6><i class="fas fa-edit"></i> ویرایش ترجمه سوال</h6>` : ''}
                <p>${content}</p>
            </div>
        `;
    } else {
        questionTransContainer.innerHTML = '';
    }
    
    // Show pretext translation if exists
    const pretextTransContainer = document.getElementById('pretext-translation-container');
    if ((question.asw_farsi && question.asw_farsi.trim()) || isAdmin) {
        const content = makeEditableHTML(question.asw_farsi, 'question', 'asw_farsi', question.id);
        pretextTransContainer.innerHTML = `
            <div class="pretext-translation"><p class="mb-0 mt-2">${content}</p></div>
            </div>
        `;
    } else {
        pretextTransContainer.innerHTML = '';
    }
    
    // Show answer translations - زیر متن پاسخ
    if (answers && answers.length > 0) {
        answers.forEach((answer, index) => {
            const answerItem = document.querySelector(`[data-answer-index="${index}"]`);
            if (answerItem && ((answer.farsi_text && answer.farsi_text.trim()) || isAdmin)) {
                const answerContainer = answerItem.querySelector('.flex-grow-1');
                if (answerContainer) {
                    // Remove existing translation if any
                    const existingTrans = answerContainer.querySelector('.answer-translation');
                    if (existingTrans) existingTrans.remove();
                    
                    const additionalClass = explanationActive ? 'with-explanation' : '';
                    const translationDiv = document.createElement('div');
                    translationDiv.className = `answer-translation mt-2 ${additionalClass}`;
                    
                    const content = makeEditableHTML(answer.farsi_text, 'answer', 'farsi_text', answer.id);
                    translationDiv.innerHTML = `<strong>ترجمه:</strong> ${content}`;
                    
                    // اگر توضیح فعال است، ترجمه را قبل از توضیح قرار بده
                    const existingExpl = answerContainer.querySelector('.answer-explanation');
                    if (existingExpl) {
                        answerContainer.insertBefore(translationDiv, existingExpl);
                    } else {
                        answerContainer.appendChild(translationDiv);
                    }
                }
            }
        });
    }
}

function showExplanationContent() {
    if (!currentQuestionData) return;
    
    const question = currentQuestionData.question;
    const answers = currentQuestionData.answers;
    
    // Show question explanation
    const questionExplainContainer = document.getElementById('question-explanation-container');
    if ((question.info && question.info.trim()) || isAdmin) {
        const additionalClass = translationActive ? 'with-translation' : ''; 
        const content = makeEditableHTML(question.info, 'question', 'info', question.id);
        questionExplainContainer.innerHTML = `
            <div class="explanation-box ${additionalClass}">
                <h6><i class="fas fa-info-circle"></i> توضیح سوال</h6>
                <p>${content}</p>
            </div>
        `;
    } else {
        questionExplainContainer.innerHTML = '';
    }
    
    // Show answer explanations - زیر متن پاسخ
    if (answers && answers.length > 0) {
        answers.forEach((answer, index) => {
            const answerItem = document.querySelector(`[data-answer-index="${index}"]`);
            if (answerItem && ((answer.info && answer.info.trim()) || isAdmin)) {
                const answerContainer = answerItem.querySelector('.flex-grow-1');
                if (answerContainer) {
                    // Remove existing explanation if any
                    const existingExpl = answerContainer.querySelector('.answer-explanation');
                    if (existingExpl) existingExpl.remove();
                    
                    const explanationDiv = document.createElement('div');
                    explanationDiv.className = translationActive ? 'answer-explanation mt-2 with-translation' : 'answer-explanation mt-2';
                    
                    const content = makeEditableHTML(answer.info, 'answer', 'info', answer.id);
                    explanationDiv.innerHTML = `<strong>توضیح:</strong> ${content}`;
                    answerContainer.appendChild(explanationDiv);
                }
            }
        });
    }
    

}

function hideTranslationContent() {
    // فقط اگر explanationActive فعال نباشد، ترجمه را مخفی کن
    if (!explanationActive) {
        document.getElementById('question-translation-container').innerHTML = '';
        document.getElementById('pretext-translation-container').innerHTML = '';
        
        // Remove all answer translations
        document.querySelectorAll('.answer-translation').forEach(el => el.remove());
    }
}

        function hideExplanationContent() {
            document.getElementById('question-explanation-container').innerHTML = '';
            document.getElementById('pretext-explanation-container').innerHTML = '';
            
            // Remove all answer explanations
            document.querySelectorAll('.answer-explanation').forEach(el => el.remove());
        }

        // ============================================
        // ادامه کد اصلی...
        // ============================================
        
        function loadCurrentQuestion() {
            resetQuestionState();

            const questionId = selectedQuestions[currentQuestionIndex];

            if (!questionId) {
                console.error('No question ID found at current index:', currentQuestionIndex);
                return;
            }

            const formData = createFormDataWithCSRF({
                question_id: questionId
            });

            fetch("../incloud/get_question.php", {
                method: "POST",
                headers: {
                    "Content-Type": "application/x-www-form-urlencoded"
                },
                body: formData
            }).then(response => {
                if (!response.ok) {
                    throw new Error(`HTTP error! status: ${response.status}`);
                }
                return response.json();
            })
                .then(data => {
                    if (data && data.success === false) {
                        if (data.requires_reload) {
                            window.location.reload();
                            return;
                        }
                        console.error('Backend error:', data.message);
                        showErrorMessage(data.message || 'خطا در دریافت اطلاعات سوال', data.requires_upgrade);
                        if (!data.requires_upgrade) {
                            markQuestionAsProblematic(questionId);
                        }
                        return;
                    }

                    if (!data || typeof data !== 'object') {
                        console.error('Invalid data received:', data);
                        showErrorMessage('خطا در دریافت اطلاعات سوال');
                        return;
                    }

                    if (!data.question) {
                        console.error('Question data is missing in response:', data);
                        showErrorMessage('اطلاعات سوال یافت نشد');
                        return;
                    }

                    currentQuestionData = data;
                    updateQuestionDisplay(data);
                    updateSession(questionId);
                    checkBookmarkStatus(questionId);

                    if (mode === 'practice') {
                        loadUserAnswersFromMemory(questionId);
                        updatePracticeButtons();
                    }
                    
            
                })
                .catch(error => {
                    console.error("خطا در بارگذاری سوال:", error);
                    showErrorMessage('خطا در بارگذاری سوال: ' + error.message);
                });

            updateVocabularyContext(questionId, currentCategoryId);
            document.dispatchEvent(new CustomEvent('questionChanged', {
                detail: { questionId: questionId, categoryId: currentCategoryId }
            }));
            updateNavigationButtons();
        }

        function showErrorMessage(message, requiresUpgrade = false) {
            const mediaElement = document.getElementById("media");
            const textElement = document.getElementById("text");
            const answersElement = document.getElementById("answers");
            const codeElement = document.getElementById("code");
            const punktElement = document.getElementById("punkt");

            if (mediaElement) {
                mediaElement.innerHTML = '<div class="alert alert-danger"><i class="fas fa-exclamation-triangle"></i> ' + message + '</div>';
            }

            if (requiresUpgrade) {
                if (textElement) {
                    textElement.innerText = 'محدودیت دسترسی (پلن رایگان)';
                }
                if (answersElement) {
                    answersElement.innerHTML = `
                        <div class="alert alert-info text-center p-4">
                            <i class="fas fa-lock fa-3x mb-3 text-warning"></i>
                            <p class="mb-4">${message}</p>
                            <a href="../admin/subscription.php" class="btn btn-warning btn-lg">
                                <i class="fas fa-crown me-2"></i> تهیه اشتراک VIP و دسترسی نامحدود
                            </a>
                        </div>
                    `;
                }
            } else {
                if (textElement) {
                    textElement.innerText = 'سوال دارای مشکل است - پاسخ‌ها یافت نشد';
                }
                if (answersElement) {
                    answersElement.innerHTML = '<div class="alert alert-warning">این سوال فاقد پاسخ است. لطفاً به سوال بعدی بروید.</div>';
                }
            }

            if (codeElement) {
                const questionId = selectedQuestions[currentQuestionIndex];
                codeElement.innerText = questionId || 'N/A';
            }

            if (punktElement) {
                punktElement.innerText = '0';
            }
        }
        
        function markQuestionAsProblematic(questionId) {
            console.warn(`Question ${questionId} has no answers - marked as problematic`);
            console.log(`🚨 PROBLEMATIC QUESTION ID: ${questionId}`);

            Swal.fire({
                title: 'سوال مشکل دار',
                text: `سوال با کد ${questionId} فاقد پاسخ است. آیا می‌خواهید این کد را کپی کنید؟`,
                icon: 'warning',
                showCancelButton: true,
                confirmButtonText: 'بله، کپی کن',
                cancelButtonText: 'خیر'
            }).then((result) => {
                if (result.isConfirmed) {
                    navigator.clipboard.writeText(questionId).then(() => {
                        Swal.fire('کپی شد', `کد سوال ${questionId} کپی شد`, 'success');
                    }).catch(() => {
                        Swal.fire({
                            title: 'کپی دستی',
                            text: 'مرورگر شما از کپی خودکار پشتیبانی نمی‌کند. لطفا کد زیر را کپی کنید:',
                            input: 'text',
                            inputValue: questionId
                        });
                    });
                }
            });

            let problematicQuestions = JSON.parse(localStorage.getItem('problematicQuestions') || '[]');
            if (!problematicQuestions.includes(questionId)) {
                problematicQuestions.push(questionId);
                localStorage.setItem('problematicQuestions', JSON.stringify(problematicQuestions));
            }

            let detailedProblematicQuestions = JSON.parse(localStorage.getItem('detailedProblematicQuestions') || '[]');
            const existingEntry = detailedProblematicQuestions.find(item => item.questionId === questionId);

            if (!existingEntry) {
                detailedProblematicQuestions.push({
                    questionId: questionId,
                    timestamp: new Date().toISOString(),
                    error: 'پاسخی جهت نمایش وجود ندارد',
                    questionIndex: currentQuestionIndex + 1,
                    totalQuestions: selectedQuestions.length
                });
                localStorage.setItem('detailedProblematicQuestions', JSON.stringify(detailedProblematicQuestions));
            }
        }

        function toggleBookmark() {
            const bookmarkBtn = document.getElementById('bookmark-btn');
            const bookmarkIcon = document.getElementById('bookmark-icon');
            const questionId = selectedQuestions[currentQuestionIndex];

            bookmarkBtn.classList.add('bookmark-loading');
            bookmarkBtn.disabled = true;

            const formData = createFormDataWithCSRF({
                question_id: questionId
            });

            fetch("../incloud/toggle_bookmark.php", {
                method: "POST",
                headers: {
                    "Content-Type": "application/x-www-form-urlencoded"
                },
                body: formData
            })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        updateBookmarkIcon(data.bookmarked);
                        showBookmarkToast(data.message, 'success');
                    } else {
                        showBookmarkToast(data.error || 'خطا در انجام عملیات', 'error');
                    }
                })
                .catch(error => {
                    console.error('Bookmark error:', error);
                    showBookmarkToast('خطا در ارتباط با سرور', 'error');
                })
                .finally(() => {
                    bookmarkBtn.classList.remove('bookmark-loading');
                    bookmarkBtn.disabled = false;
                });
        }

        function checkBookmarkStatus(questionId) {
            fetch("../incloud/check_bookmark.php?question_id=" + questionId + "&csrf_token=" + encodeURIComponent(csrfToken))
                .then(response => response.json())
                .then(data => {
                    updateBookmarkIcon(data.bookmarked);
                })
                .catch(error => {
                    console.error('Check bookmark error:', error);
                    updateBookmarkIcon(false);
                });
        }

        function updateBookmarkIcon(isBookmarked) {
            const bookmarkIcon = document.getElementById('bookmark-icon');

            if (isBookmarked) {
                bookmarkIcon.className = 'fas fa-star text-warning';
            } else {
                bookmarkIcon.className = 'far fa-star text-warning';
            }
        }

        function showBookmarkToast(message, type) {
            const toast = document.createElement('div');
            toast.className = `alert alert-${type === 'error' ? 'danger' : 'success'} bookmark-toast alert-dismissible`;
            toast.innerHTML = `
                <div class="d-flex justify-content-between align-items-center">
                    <span><i class="fas fa-${type === 'error' ? 'exclamation-triangle' : 'check-circle'} me-2"></i>${message}</span>
                    <button type="button" class="btn-close" onclick="this.parentElement.parentElement.remove()"></button>
                </div>
            `;

            document.body.appendChild(toast);

            setTimeout(() => {
                if (toast.parentElement) {
                    toast.remove();
                }
            }, 3000);
        }

        function resetQuestionState() {
           resetVideoState();
    questionSolved = false;
    hasUserAnswer = false;
    userAnswers = {};
    closeVocabSheet();
    
    // ریست کردن ترجمه و توضیح
    translationActive = false;
    explanationActive = false;
    
    // ریست کردن ظاهر دکمه‌ها
    document.getElementById('translateBtn').classList.remove('active');
    document.getElementById('explainBtn').classList.remove('active');
    
    // پاک کردن محتوا
    hideTranslationContent();
    hideExplanationContent();
        }

        function resetVideoState() {
            videoViewCount = 0;
            hasWatchedVideo = false;
            showingAnswers = false;
            isVideoQuestion = false;
        }

        function loadUserAnswersFromMemory(questionId) {
            const solvedData = localStorage.getItem('solvedQuestion_' + questionId);

            if (solvedData) {
                try {
                    const parsed = JSON.parse(solvedData);
                    questionSolved = parsed.solved || false;

                    if (questionSolved) {
                        setTimeout(() => {
                            questionSolved = false;
                        }, 100);
                    }
                } catch (e) {
                    console.error('Error parsing solved data:', e);
                    questionSolved = false;
                }
            } else {
                questionSolved = false;
            }

            userAnswers = {};
            hasUserAnswer = false;

            updatePracticeButtons();
        }

        function applyUserAnswers() {
            if (Object.keys(userAnswers).length === 0) return;

            const checkboxes = document.querySelectorAll('.checkbox');
            checkboxes.forEach((checkbox, index) => {
                const answerId = checkbox.getAttribute('data-answer-id');
                if (userAnswers[answerId]) {
                    checkbox.checked = true;
                }
            });

            const numericInput = document.getElementById('numeric-answer');
            if (numericInput && userAnswers.numeric_value) {
                numericInput.value = userAnswers.numeric_value;
            }

            if (questionSolved) {
                showAnswerResults();
            }
        }

        function updatePracticeButtons() {
            const solveBtn = document.getElementById('solve-btn');
            const nextBtn = document.getElementById('next-btn');

            if (mode === 'browse' || mode === 'review') {
                solveBtn.style.display = 'none';
                nextBtn.style.display = 'inline-block';
                return;
            }

            if (mode !== 'practice') return;

            if (questionSolved) {
                solveBtn.style.display = 'none';
                nextBtn.style.display = 'inline-block';
            } else if (hasUserAnswer) {
                solveBtn.style.display = 'inline-block';
                nextBtn.style.display = 'none';
            } else {
                solveBtn.style.display = 'none';
                nextBtn.style.display = 'none';
            }
        }

function solveQuestion() {
    if (mode !== 'practice' || questionSolved) return;

    questionSolved = true;

    const isCorrect = checkUserAnswer();
    const questionId = selectedQuestions[currentQuestionIndex];
    updateAnswerStatus(questionId, isCorrect);

    showAnswerResults();

    solvedQuestions[questionId] = isCorrect;

    localStorage.setItem('solvedQuestion_' + questionId, JSON.stringify({
        solved: true,
        correct: isCorrect,
        timestamp: Date.now()
    }));

    updatePracticeButtons();
    renderPageButtons();

    // نمایش اتوماتیک توضیح بعد از پاسخ
    explanationActive = true;
    document.getElementById('explainBtn').classList.add('active');
    showTranslationContent();
    showExplanationContent();
}

        function checkUserAnswer() {
            if (!currentQuestionData || !currentQuestionData.answers) return false;

            const answerType = currentQuestionData.answers[0]['asw_type'] || 1;

            if (answerType == 2) {
                return checkNumericAnswer();
            } else {
                return checkMultipleChoiceAnswer();
            }
        }

        function checkMultipleChoiceAnswer() {
            let isCorrect = true;

            currentQuestionData.answers.forEach((answer) => {
                const checkbox = document.querySelector(`input[data-answer-id="${answer.id}"]`);
                if (!checkbox) return;

                const isAnswerCorrect = answer.asw_corr == 1;
                const isSelected = checkbox.checked;

                if ((isAnswerCorrect && !isSelected) || (!isAnswerCorrect && isSelected)) {
                    isCorrect = false;
                }
            });

            return isCorrect;
        }

        function checkNumericAnswer() {
            const input = document.getElementById('numeric-answer');
            const correctAnswer = document.getElementById('correct-answer');

            if (input && correctAnswer) {
                return input.value.trim() === correctAnswer.value.trim();
            }
            return false;
        }

        function showAnswerResults() {
            if (!currentQuestionData || !currentQuestionData.answers) return;

            const answerType = currentQuestionData.answers[0]['asw_type'] || 1;

            if (answerType == 2) {
                showNumericAnswerResult();
            } else {
                showCheckboxAnswerResults();
            }
        }

        function showNumericAnswerResult() {
            const input = document.getElementById('numeric-answer');
            const correctAnswer = document.getElementById('correct-answer');

            if (input && correctAnswer) {
                const isCorrect = input.value.trim() === correctAnswer.value.trim();

                if (!isCorrect) {
                    input.value = correctAnswer.value;
                }

                input.style.backgroundColor = isCorrect ? '#d4edda' : '#f8d7da';
                input.style.borderColor = isCorrect ? '#28a745' : '#dc3545';
                input.readOnly = true;

                const keypad = document.querySelector('.numeric-keypad');
                if (keypad) {
                    keypad.style.display = 'none';
                }
            }
        }

        function showCheckboxAnswerResults() {
            const answerItems = document.querySelectorAll('.answer-item');

            currentQuestionData.answers.forEach((answer, index) => {
                const checkbox = document.querySelector(`input[data-answer-id="${answer.id}"]`);
                const answerItem = answerItems[index];

                if (!checkbox || !answerItem) return;

                const isCorrect = answer.asw_corr == 1;
                const isSelected = checkbox.checked;

                answerItem.classList.remove('answer-correct-selected', 'answer-incorrect-selected',
                    'answer-correct-unselected', 'answer-incorrect-unselected');

                if (isCorrect && isSelected) {
                    answerItem.classList.add('answer-correct-selected');
                } else if (!isCorrect && isSelected) {
                    answerItem.classList.add('answer-incorrect-selected');
                    checkbox.checked = false;
                } else if (isCorrect && !isSelected) {
                    answerItem.classList.add('answer-correct-unselected');
                    checkbox.checked = true;
                } else if (!isCorrect && !isSelected) {
                    answerItem.classList.add('answer-incorrect-unselected');
                }

                checkbox.disabled = true;
                answerItem.classList.add('disabled');
            });
        }

        let imageUrl = '';
        let videoUrl = '';
        
        function updateQuestionDisplay(data) {
            if (!data) {
                console.error('No data provided to updateQuestionDisplay');
                return;
            }

            if (!data.question) {
                console.error('Question data is missing:', data);
                return;
            }

            const question = data.question;
            const fileName = question.picture || '';
            const extension = fileName ? fileName.split('.').pop().toLowerCase() : '';
            const fileNameWithoutExt = fileName ? fileName.replace(/\.[^/.]+$/, "") : '';

            isVideoQuestion = ['mp4', 'm4v', 'webm'].includes(extension);

            if (['jpg', 'jpeg', 'png', 'gif', 'webp'].includes(extension)) {
                imageUrl = 'https://t24.theorie24.de/2025-01-v400/data/img/images/' + fileName;
                document.getElementById("media").innerHTML = '<img id="image" src="' + imageUrl + '" alt="" class="w-100">';
                showRegularQuestion(data);
            } else if (isVideoQuestion) {
                videoUrl = 'https://www.theorie24.de/live_images/_current_ws_2024-10-01_2025-04-01/videos/' + fileName;
                if (mode === 'browse' || mode === 'review' || questionSolved) {
                    showingAnswers = true;
                    hasWatchedVideo = true; // برای نمایش تصویر پایان ویدیو
                    showRegularQuestion(data);
                    updateVideoPlaceholder(fileNameWithoutExt);
                } else {
                    showVideoQuestion(data, fileNameWithoutExt);
                }
            } else {
                document.getElementById("media").innerHTML = '';
                showRegularQuestion(data);
            }

            document.getElementById("code").innerText = question.number || 'N/A';
            document.getElementById("punkt").innerText = question.points || '0';
        }
        
        function showVideoQuestion(data, fileNameWithoutExt) {
            document.getElementById("text").innerText = "Bitte starten Sie den Film, um sich mit der Situation vertraut zu machen.";
            document.getElementById("asw_pretext").innerHTML = '';
            document.getElementById("asw_pretext").style.display = "none";
            document.getElementById("pretext-translation-container").style.display = "none";
            document.getElementById("pretext-explanation-container").style.display = "none";

            document.getElementById("video-controls").style.display = "block";
            document.getElementById("answers").style.display = "none";

            updateVideoPlaceholder(fileNameWithoutExt);
            updateVideoControls();

            if (isAdmin) {
                translationActive = true;
                explanationActive = true;
                document.getElementById('translateBtn').style.display = 'none';
                document.getElementById('explainBtn').style.display = 'none';
                showTranslationContent();
                showExplanationContent();
            } else {
                if (translationActive) showTranslationContent();
                if (explanationActive) showExplanationContent();
            }
        }

        function showRegularQuestion(data) {
            document.getElementById("text").innerText = data['question']['text'];
            document.getElementById("asw_pretext").innerHTML = data['question']['asw_pretext'] || '';
            document.getElementById("asw_pretext").style.display = "block";
            document.getElementById("pretext-translation-container").style.display = "block";
            document.getElementById("pretext-explanation-container").style.display = "block";
            document.getElementById("video-controls").style.display = "none";
            document.getElementById("answers").style.display = "block";
            answerBuilder(data['answers']);
            
            if (isAdmin) {
                translationActive = true;
                explanationActive = true;
                document.getElementById('translateBtn').style.display = 'none';
                document.getElementById('explainBtn').style.display = 'none';
                showTranslationContent();
                showExplanationContent();
            } else {
                if (translationActive) showTranslationContent();
                if (explanationActive) showExplanationContent();
            }
        }

        function updateVideoPlaceholder(fileNameWithoutExt) {
            let imageName;
            if (hasWatchedVideo) {
                imageName = fileNameWithoutExt + '_ende.jpg';
            } else {
                imageName = fileNameWithoutExt + '_anfang.jpg';
            }

            const imageUrl = 'https://t24.theorie24.de/2025-01-v400/data/img/images/' + imageName;

            let playButtonHtml = '';
            if (mode !== 'practice' || (videoViewCount < maxVideoViews && !showingAnswers)) {
                playButtonHtml = '<button class="play-button" onclick="playVideo()"><i class="fas fa-play"></i></button>';
            }

            document.getElementById("media").innerHTML =
                '<div class="video-placeholder" onclick="' + ((mode !== 'practice' || (videoViewCount < maxVideoViews && !showingAnswers)) ? 'playVideo()' : '') + '">' +
                '<img src="' + imageUrl + '" alt="Video Preview" class="w-100">' +
                playButtonHtml +
                '</div>';
        }

        function updateVideoControls() {
            const remainingViews = maxVideoViews - videoViewCount;
            document.getElementById("remaining-views").innerText = remainingViews;

            const startBtn = document.getElementById("video-start-btn");
            const zurAufgabeBtn = document.getElementById("zur-aufgabe-btn");

            if (mode !== 'practice') {
                startBtn.style.display = "inline-block";
                zurAufgabeBtn.style.display = "inline-block";
            } else {
                if (videoViewCount >= maxVideoViews) {
                    startBtn.style.display = "none";
                    zurAufgabeBtn.style.display = "block";
                } else if (hasWatchedVideo) {
                    startBtn.style.display = "inline-block";
                    zurAufgabeBtn.style.display = "inline-block";
                } else {
                    startBtn.style.display = "inline-block";
                    zurAufgabeBtn.style.display = "none";
                }
            }

            if (showingAnswers) {
                if (mode === 'practice') {
                    startBtn.style.display = "none";
                }
                zurAufgabeBtn.style.display = "none";
            }
        }

        function playVideo() {
            if (mode === 'practice' && (videoViewCount >= maxVideoViews || showingAnswers)) {
                return;
            }

            videoViewCount++;
            hasWatchedVideo = true;

            if (currentQuestionData) {
                const fileName = currentQuestionData['question']['picture'] || '';
                const fileNameWithoutExt = fileName.replace(/\.[^/.]+$/, "");
                updateVideoPlaceholder(fileNameWithoutExt);
            }

            const modalEl = document.getElementById('videoModal');
            const modal = bootstrap.Modal.getOrCreateInstance(modalEl);
            const modalVideo = document.getElementById('modal-video');
            modalVideo.src = videoUrl;

            modal.show();

            updateVideoControls();

            modalVideo.onended = function () {
                modal.hide();
            };
        }

        function showAnswers() {
            if (!currentQuestionData) return;

            showingAnswers = true;

            document.getElementById("video-controls").style.display = "none";

            document.getElementById("answers").style.display = "block";

            document.getElementById("text").innerText = currentQuestionData['question']['text'];
            document.getElementById("asw_pretext").innerHTML = currentQuestionData['question']['asw_pretext'] || '';
            document.getElementById("asw_pretext").style.display = "block";
            document.getElementById("pretext-translation-container").style.display = "block";
            document.getElementById("pretext-explanation-container").style.display = "block";

            answerBuilder(currentQuestionData['answers']);

            if (isAdmin) {
                translationActive = true;
                explanationActive = true;
                document.getElementById('translateBtn').style.display = 'none';
                document.getElementById('explainBtn').style.display = 'none';
                showTranslationContent();
                showExplanationContent();
            } else {
                if (translationActive) showTranslationContent();
                if (explanationActive) showExplanationContent();
            }

            if (currentQuestionData) {
                const fileName = currentQuestionData['question']['picture'] || '';
                const fileNameWithoutExt = fileName.replace(/\.[^/.]+$/, "");
                
                if (isVideoQuestion) {
                    updateVideoPlaceholder(fileNameWithoutExt);
                } else {
                    const imageUrl = 'https://t24.theorie24.de/2025-01-v400/data/img/images/' + fileNameWithoutExt + '_anfang.jpg';
                    document.getElementById("media").innerHTML =
                        '<div class="video-placeholder">' +
                        '<img src="' + imageUrl + '" alt="Video Preview" class="w-100">' +
                        '</div>';
                }
            }
        }

        function rot13Decode(str) {
            return str.replace(/[a-zA-Z]/g, function (a) {
                return String.fromCharCode((a <= "Z" ? 90 : 122) >= (a = a.charCodeAt(0) + 13) ? a : a - 26);
            });
        }
function answerBuilder(answers = null) {
    if (answers && answers.length > 0) {
        let answersText = "";
        const answerType = answers[0]['asw_type'] || 1;

        if (answerType == 2) {
            const answer = answers[0];
            const hint = answer['asw_hint'] || '';
            const savedAnswer = userAnswers.numeric_value || '';
            const isReadOnly = (mode === 'browse' || questionSolved) ? 'readonly' : '';
            const displayKeypad = (mode === 'browse' || questionSolved) ? 'display: none;' : '';

            answersText = `
                <div class="text-center">
                    <div class="mb-4">
                        <div class="d-flex justify-content-center align-items-center gap-2 mb-3">
                            <input type="text" id="numeric-answer" value="${savedAnswer}" ${isReadOnly}
                                   class="form-control text-center" style="width: 150px; font-size: 18px; font-weight: bold;">
                            <span class="fw-bold fs-5">${hint}</span>
                        </div>
                    </div>
                    <div class="numeric-keypad mx-auto" style="max-width: 300px; ${displayKeypad}">
                        <div class="row g-2 mb-2">
                            ${[0, 1, 2].map(n => `<div class="col-4"><button class="btn btn-outline-secondary w-100 keypad-btn" onclick="addNumber('${n}')">${n}</button></div>`).join('')}
                        </div>
                        <div class="row g-2 mb-2">
                            ${[3, 4, 5].map(n => `<div class="col-4"><button class="btn btn-outline-secondary w-100 keypad-btn" onclick="addNumber('${n}')">${n}</button></div>`).join('')}
                        </div>
                        <div class="row g-2 mb-2">
                            ${[6, 7, 8].map(n => `<div class="col-4"><button class="btn btn-outline-secondary w-100 keypad-btn" onclick="addNumber('${n}')">${n}</button></div>`).join('')}
                        </div>
                        <div class="row g-2 mb-3">
                            <div class="col-4"><button class="btn btn-outline-secondary w-100 keypad-btn" onclick="addNumber('9')">9</button></div>
                            <div class="col-4"><button class="btn btn-outline-secondary w-100 keypad-btn" onclick="addComma()">,</button></div>
                            <div class="col-4"><button class="btn btn-outline-secondary w-100 keypad-btn" onclick="clearLastChar()">⌫</button></div>
                        </div>
                        <div class="row g-2">
                            <div class="col-12"><button class="btn btn-outline-danger w-100" onclick="clearAnswer()">Löschen</button></div>
                        </div>
                    </div>
                    <input type="hidden" id="correct-answer" value="${answer['text'] || ''}">
                </div>
            `;
            if (mode === 'browse' || mode === 'review' || questionSolved) {
                setTimeout(showNumericAnswerResult, 50);
            }
        } else {
            answers.forEach((answer, index) => {
                let status = "";
                let disabled = "";

                if (mode === 'browse' || mode === 'review') {
                    if (answer['asw_corr'] == 1) {
                        status = 'checked';
                    }
                    disabled = 'disabled';
                } else if (mode === 'practice' && questionSolved) {
                    disabled = 'disabled';
                }

                const isImage = answer['is_image'] == 1;
                let answerContent = '';

                if (isImage) {
                    // کد تصویر بدون تغییر...
                    let imageName = '';
                    let isEtcImage = false;

                    if (answer['original_content']) {
                        const etcMatch = answer['original_content'].match(/%IMG_ETC%\/([^"']+)/);
                        if (etcMatch) {
                            imageName = etcMatch[1];
                            isEtcImage = true;
                        } else {
                            const answerMatch = answer['original_content'].match(/%IMG_ANSWER%\/([^"']+)/);
                            if (answerMatch) {
                                imageName = answerMatch[1];
                                isEtcImage = false;
                            }
                        }
                    }

                    if (!imageName && answer['text']) {
                        const numberMatch = answer['text'].match(/\d+/);
                        if (numberMatch) {
                            imageName = `answer_${answer['id']}.png`;
                            isEtcImage = false;
                        }
                    }

                    if (!imageName) {
                        imageName = `answer_${answer['id']}.png`;
                        isEtcImage = false;
                    }

                    let imageUrl = '';
                    let maxWidth = '';
                    if (isEtcImage || imageName.toLowerCase().startsWith('div')) {
                        imageUrl = `https://t24.theorie24.de/2025-01-v400/data/img/etc/de/${imageName}`;
                        maxWidth = 300;
                    } else {
                        imageUrl = `https://t24.theorie24.de/2025-01-v400/data/img/answers/${imageName}`;
                        maxWidth = 90;
                    }

                    answerContent = `<img src="${imageUrl}" alt="Answer ${index + 1}" class="img-fluid" style="max-width:${maxWidth}px; height: auto;">`;
                } else {
                    const decodedText = answer['text'];
                    answerContent = `
                        <div class="vocabulary-selection">
                            <span class="fw-bold" name="text" status="richtig">${decodedText}</span>
                            <span class="d-none" name="help_text"></span>
                        </div>
                    `;
                }

                answersText += `
                    <div class="d-flex mb-3 align-items-start answer-item" data-answer-index="${index}">
                        <label class="form-label me-2 custom-checkbox">
                            <input type="checkbox" class="checkbox" data-answer-id="${answer['id']}" 
                                   ${status} ${disabled} 
                                   ${mode === 'practice' && !questionSolved ? 'onchange="handleAnswerChange(this)"' : ''}>
                            <span class="checkmark"></span>
                        </label>
                        <div class="flex-grow-1">
                            ${answerContent}
                        </div>
                    </div>
                `;
            });
        }

        document.getElementById("answers").innerHTML = answersText;
        

    } else {
        console.log('خطا در استخراج پاسخ');
        return "";
    }
}

        function handleAnswerChange(checkbox) {
            if (mode !== 'practice' || questionSolved) return;

            const questionId = selectedQuestions[currentQuestionIndex];
            const answerId = checkbox.getAttribute('data-answer-id');

            if (checkbox.checked) {
                userAnswers[answerId] = true;
            } else {
                delete userAnswers[answerId];
            }

            hasUserAnswer = Object.keys(userAnswers).length > 0;

            updatePracticeButtons();

            setTimeout(() => {
                reinitializeVocabularySelection();
            }, 100);
        }

        function addNumber(num) {
            const input = document.getElementById('numeric-answer');
            if (!input) return;

            input.value += num;

            if (mode === 'practice' && !questionSolved) {
                hasUserAnswer = input.value.trim().length > 0;

                const questionId = selectedQuestions[currentQuestionIndex];
                userAnswers.numeric_value = input.value;

                localStorage.setItem('userAnswers_' + questionId, JSON.stringify({
                    answers: userAnswers,
                    timestamp: Date.now()
                }));

                updatePracticeButtons();
            }
        }

        function addComma() {
            const input = document.getElementById('numeric-answer');
            if (!input) return;

            if (input.value && !input.value.includes(',')) {
                input.value += ',';

                if (mode === 'practice' && !questionSolved) {
                    const questionId = selectedQuestions[currentQuestionIndex];
                    userAnswers.numeric_value = input.value;

                    localStorage.setItem('userAnswers_' + questionId, JSON.stringify({
                        answers: userAnswers,
                        timestamp: Date.now()
                    }));
                }
            }
        }

        function clearLastChar() {
            const input = document.getElementById('numeric-answer');
            if (!input) return;

            input.value = input.value.slice(0, -1);

            if (mode === 'practice' && !questionSolved) {
                hasUserAnswer = input.value.trim().length > 0;

                const questionId = selectedQuestions[currentQuestionIndex];
                userAnswers.numeric_value = input.value;

                localStorage.setItem('userAnswers_' + questionId, JSON.stringify({
                    answers: userAnswers,
                    timestamp: Date.now()
                }));

                updatePracticeButtons();
            }
        }

        function clearAnswer() {
            const input = document.getElementById('numeric-answer');
            if (!input) return;

            input.value = '';

            if (mode === 'practice' && !questionSolved) {
                hasUserAnswer = false;

                const questionId = selectedQuestions[currentQuestionIndex];
                delete userAnswers.numeric_value;

                localStorage.setItem('userAnswers_' + questionId, JSON.stringify({
                    answers: userAnswers,
                    timestamp: Date.now()
                }));

                updatePracticeButtons();
            }
        }

        function updateSession(questionId) {
            const formData = createFormDataWithCSRF({
                current_question_id: questionId
            });

            fetch("../incloud/update_session.php", {
                method: "POST",
                headers: {
                    "Content-Type": "application/x-www-form-urlencoded"
                },
                body: formData
            });
        }


        function nextQuestion() {
            if (mode === 'practice' && !questionSolved) {
                if (!hasUserAnswer) {
                    Swal.fire({
                        icon: 'warning',
                        title: 'توجه',
                        text: 'لطفاً ابتدا حداقل یک پاسخ را انتخاب کنید.',
                        confirmButtonText: 'باشه',
                        confirmButtonColor: '#5a8dee'
                    });
                    return;
                }
                solveQuestion();
                return;
            }
            if (currentQuestionIndex < selectedQuestions.length - 1) {
                currentQuestionIndex++;
                loadCurrentQuestion();
                renderPageButtons();
                updateNavigationButtons();

            }
            window.scrollTo({
        top: 0,
        left: 0,
        behavior: 'smooth'
        });
        }

        function previousQuestion() {
            if (currentQuestionIndex > 0) {
                currentQuestionIndex--;
                loadCurrentQuestion();
                renderPageButtons();

                updateNavigationButtons();
                window.scrollTo({
  top: 0,
  left: 0,
  behavior: 'smooth'
});

            }
        }

        function updateNavigationButtons() {
            const nextButtons = document.querySelectorAll('button[onclick="nextQuestion()"]');
            const prevButtons = document.querySelectorAll('button[onclick="previousQuestion()"]');

            const forwardBtn = document.querySelector('.fa-step-forward')?.closest('button');
            const backwardBtn = document.querySelector('.fa-step-backward')?.closest('button');

            if (currentQuestionIndex >= selectedQuestions.length - 1) {
                nextButtons.forEach(btn => {
                    btn.disabled = true;
                    btn.classList.add('disabled-button');
                });
                if (forwardBtn) {
                    forwardBtn.disabled = true;
                    forwardBtn.classList.add('disabled-button');
                }
            } else {
                nextButtons.forEach(btn => {
                    btn.disabled = false;
                    btn.classList.remove('disabled-button');
                });
                if (forwardBtn) {
                    forwardBtn.disabled = false;
                    forwardBtn.classList.remove('disabled-button');
                }
            }

            if (currentQuestionIndex <= 0) {
                prevButtons.forEach(btn => {
                    btn.disabled = true;
                    btn.classList.add('disabled-button');
                });
                if (backwardBtn) {
                    backwardBtn.disabled = true;
                    backwardBtn.classList.add('disabled-button');
                }
            } else {
                prevButtons.forEach(btn => {
                    btn.disabled = false;
                    btn.classList.remove('disabled-button');
                });
                if (backwardBtn) {
                    backwardBtn.disabled = false;
                    backwardBtn.classList.remove('disabled-button');
                }
            }
            
            const firstBtn = document.querySelector('button[onclick="goToFirstQuestion()"]');
            const lastBtn = document.querySelector('button[onclick="goToLastQuestion()"]');
            if (firstBtn) {
                firstBtn.disabled = currentQuestionIndex <= 0;
                if(firstBtn.disabled) firstBtn.classList.add('disabled-button'); else firstBtn.classList.remove('disabled-button');
            }
            if (lastBtn) {
                lastBtn.disabled = currentQuestionIndex >= selectedQuestions.length - 1;
                if(lastBtn.disabled) lastBtn.classList.add('disabled-button'); else lastBtn.classList.remove('disabled-button');
            }
        }


        function goToQuestion(index) {
            currentQuestionIndex = index;
            loadCurrentQuestion();
            renderPageButtons();
            updateNavigationButtons();
            window.scrollTo({
  top: 0,
  left: 0,
  behavior: 'smooth'
});

        }
        
        function goToFirstQuestion() {
            if (currentQuestionIndex > 0) {
                Swal.fire({
                    title: 'توجه',
                    text: 'آیا مایل هستید به اولین سوال بروید؟',
                    icon: 'question',
                    showCancelButton: true,
                    confirmButtonText: 'بله',
                    cancelButtonText: 'خیر'
                }).then((result) => {
                    if (result.isConfirmed) {
                        goToQuestion(0);
                    }
                });
            }
        }
        
        function goToLastQuestion() {
            if (currentQuestionIndex < selectedQuestions.length - 1) {
                Swal.fire({
                    title: 'توجه',
                    text: 'آیا مایل هستید به آخرین سوال بروید؟',
                    icon: 'question',
                    showCancelButton: true,
                    confirmButtonText: 'بله',
                    cancelButtonText: 'خیر'
                }).then((result) => {
                    if (result.isConfirmed) {
                        goToQuestion(selectedQuestions.length - 1);
                    }
                });
            }
        }

        function renderPageButtons() {
            const container = document.getElementById('question-buttons');
            container.innerHTML = '';

            function getQuestionsPerPage() {
                if (window.innerWidth < 768) {
                    return 3;
                } else if (window.innerWidth < 992) {
                    return 10;
                } else {
                    return 15;
                }
            }

            const questionsPerPage = getQuestionsPerPage();
            const currentPage = Math.floor(currentQuestionIndex / questionsPerPage) + 1;
            const totalPages = Math.ceil(selectedQuestions.length / questionsPerPage);

            const startIndex = (currentPage - 1) * questionsPerPage;
            const endIndex = Math.min(startIndex + questionsPerPage - 1, selectedQuestions.length - 1);

            for (let i = startIndex; i <= endIndex; i++) {
                const questionNumber = i + 1;
                const questionId = parseInt(selectedQuestions[i]);
                let buttonClass = 'btn-success';
                let isDisabled = false;

                if (!isVip && questionId > maxAccessibleId) {
                    buttonClass = 'btn-secondary disabled-button';
                    isDisabled = true;
                }

                if (i === currentQuestionIndex) {
                    buttonClass = 'btn-dark';
                }

                const btnContainer = document.createElement('div');
                btnContainer.className = 'question-btn-container';

                const btn = document.createElement('button');
                btn.id = `btn${questionNumber}`;
                btn.className = `btn ${buttonClass}`;
                btn.textContent = questionNumber;
                
                if (isDisabled) {
                    btn.onclick = () => showErrorMessage(`دسترسی به این سوال محدود شده است. در پلن رایگان فقط به ${questionLimit} سوال اول دسترسی دارید. لطفاً برای دسترسی به تمام سوالات، اشتراک VIP تهیه کنید.`, true);
                } else {
                    btn.onclick = () => goToQuestion(i);
                }

                const statusIndicator = document.createElement('div');
                statusIndicator.className = 'question-status-indicator';

                const questionStatus = questionStatuses[questionId];
                if (questionStatus) {
                    statusIndicator.classList.add(`status-${questionStatus.color}`);
                } else {
                    statusIndicator.classList.add('status-gray');
                }

                btnContainer.appendChild(btn);
                btnContainer.appendChild(statusIndicator);
                container.appendChild(btnContainer);
            }
        }
        
        function setupVocabIconsEvents() {
            const vocabIcons = document.getElementById('vocab-icons');
            if (vocabIcons) {
                vocabIcons.addEventListener('click', function (e) {
                    e.stopPropagation();
                });
            }
        }

    
        // Report Issue JS
        function openReportModal() {
    if (!currentQuestionData) return;
    const modal = new bootstrap.Modal(document.getElementById('reportModal'));
    modal.show();
}
// stray help modal line removed
// stray closing brace removed
            function sendHelpRequest() {
                const questionText = document.getElementById('text').innerText.trim();
                const answers = [];
                const answerElems = document.querySelectorAll('#answers .answer-item .flex-grow-1');
                answerElems.forEach((el, idx) => {
                    const txt = el.innerText.trim();
                    if (txt) answers.push(`${idx + 1}. ${txt}`);
                });
                let imageUrl = '';
                const imgEl = document.getElementById('image');
                if (imgEl && imgEl.src) {
                    imageUrl = imgEl.src;
                }
                let message = `سؤال: ${questionText}\n`;
                if (answers.length) {
                    message += 'پاسخ‌ها:\n' + answers.join('\n') + '\n';
                }
                if (imageUrl) {
                    message += `تصویر: ${imageUrl}\n`;
                }
                const telegramUrl = 'https://t.me/YourAdminUsername?text=' + encodeURIComponent(message);
                window.open(telegramUrl, '_blank');
                const modalInst = bootstrap.Modal.getInstance(document.getElementById('helpModal'));
                if (modalInst) modalInst.hide();
            }
            document.getElementById('report-message').value = '';

// Global Help Request functions
function openHelpModal() {
    const helpModal = new bootstrap.Modal(document.getElementById('helpModal'));
    helpModal.show();
}

function sendHelpRequest() {
    const questionText = document.getElementById('text').innerText.trim();
    const answers = [];
    const answerElems = document.querySelectorAll('#answers .answer-item .flex-grow-1');
    answerElems.forEach((el, idx) => {
        const txt = el.innerText.trim();
        if (txt) answers.push(`${idx + 1}. ${txt}`);
    });
    let imageUrl = '';
    const imgEl = document.getElementById('image');
    if (imgEl && imgEl.src) {
        imageUrl = imgEl.src;
    }
    let message = `سؤال: ${questionText}\n`;
    if (answers.length) {
        message += 'پاسخ‌ها:\n' + answers.join('\n') + '\n';
    }
    if (imageUrl) {
        message += `تصویر: ${imageUrl}\n`;
    }
    const telegramUrl = 'https://t.me/farsifahr?text=' + encodeURIComponent(message);
    window.open(telegramUrl, '_blank');
    const modalInst = bootstrap.Modal.getInstance(document.getElementById('helpModal'));
    if (modalInst) modalInst.hide();
}
// stray extra closing brace removed

        function submitReport() {
            const message = document.getElementById('report-message').value.trim();
            if (!message) return showVocabToast('لطفاً توضیح مختصری درباره مشکل بنویسید', 'error');

            const btn = document.querySelector('#reportModal .btn-danger');
            const originalText = btn.innerText;
            btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> در حال ارسال...';
            btn.disabled = true;

            const formData = createFormDataWithCSRF({
                question_id: currentQuestionData.question.id,
                message: message
            });

            fetch('../incloud/submit_report.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: formData
            })
            .then(r => r.json())
            .then(data => {
                if (data.success) {
                    showVocabToast(data.message, 'success');
                    const modal = bootstrap.Modal.getInstance(document.getElementById('reportModal'));
                    modal.hide();
                } else {
                    showVocabToast('خطا: ' + data.message, 'error');
                }
            })
            .catch(err => {
                showVocabToast('خطا در ارتباط با سرور', 'error');
            })
            .finally(() => {
                btn.innerText = originalText;
                btn.disabled = false;
            });
        }

        // Tags management JS
        let allTags = [];
        let questionTags = [];
        
        function openTagsModal() {
            if (!isAdmin || !currentQuestionData) return;
            const modal = new bootstrap.Modal(document.getElementById('tagsModal'));
            modal.show();
            
            document.getElementById('tags-loading').style.display = 'block';
            document.getElementById('tags-container').style.display = 'none';
            document.getElementById('new-tag-name').value = '';
            
            const formData = createFormDataWithCSRF({
                action: 'fetch',
                question_id: currentQuestionData.question.id
            });
            
            fetch('../incloud/manage_question_tags.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: formData
            })
            .then(r => r.json())
            .then(data => {
                if (data.success) {
                    allTags = data.all_tags;
                    questionTags = data.question_tags;
                    renderTagsUI();
                    
                    document.getElementById('tags-loading').style.display = 'none';
                    document.getElementById('tags-container').style.display = 'block';
                } else {
                    showVocabToast('خطا در دریافت تگ‌ها: ' + data.message, 'error');
                }
            })
            .catch(err => {
                showVocabToast('خطا در ارتباط با سرور', 'error');
            });
        }
        
        function renderTagsUI() {
            const attachedDiv = document.getElementById('attached-tags');
            const availableDiv = document.getElementById('available-tags');
            
            attachedDiv.innerHTML = '';
            availableDiv.innerHTML = '';
            
            if (questionTags.length === 0) {
                attachedDiv.innerHTML = '<span class="text-muted">تگی اختصاص داده نشده است</span>';
            }
            
            const attachedIds = questionTags.map(t => t.id);
            
            // Render attached
            questionTags.forEach(tag => {
                const badge = document.createElement('span');
                badge.className = 'badge p-2 d-flex align-items-center gap-2';
                badge.style.backgroundColor = tag.color || '#0d6efd';
                badge.innerHTML = `
                    ${tag.name}
                    <i class="fas fa-times cursor-pointer" style="cursor: pointer;" onclick="toggleTag(${tag.id}, this)"></i>
                `;
                attachedDiv.appendChild(badge);
            });
            
            // Render available (unattached)
            let hasAvailable = false;
            allTags.forEach(tag => {
                if (!attachedIds.includes(tag.id)) {
                    hasAvailable = true;
                    const badge = document.createElement('span');
                    badge.className = 'badge p-2 cursor-pointer';
                    badge.style.backgroundColor = tag.color || '#6c757d';
                    badge.style.cursor = 'pointer';
                    badge.style.opacity = '0.7';
                    badge.innerHTML = `+ ${tag.name}`;
                    badge.onclick = () => toggleTag(tag.id, badge);
                    availableDiv.appendChild(badge);
                }
            });
            
            if (!hasAvailable) {
                availableDiv.innerHTML = '<span class="text-muted">همه تگ‌های موجود اختصاص داده شده‌اند</span>';
            }
        }
        
        function toggleTag(tagId, element) {
            const formData = createFormDataWithCSRF({
                action: 'toggle',
                question_id: currentQuestionData.question.id,
                tag_id: tagId
            });
            
            if (element) element.style.opacity = '0.5';
            
            fetch('../incloud/manage_question_tags.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: formData
            })
            .then(r => r.json())
            .then(data => {
                if (data.success) {
                    // Refetch internally to keep it clean
                    openTagsModal();
                } else {
                    showVocabToast('خطا: ' + data.message, 'error');
                }
            })
            .catch(err => {
                showVocabToast('خطا در ارتباط با سرور', 'error');
            });
        }
        
        function createNewTag() {
            const name = document.getElementById('new-tag-name').value.trim();
            const color = document.getElementById('new-tag-color').value;
            
            if (!name) return showVocabToast('لطفاً نام دسته را وارد کنید', 'error');
            
            const formData = createFormDataWithCSRF({
                action: 'create_and_attach',
                question_id: currentQuestionData.question.id,
                tag_name: name,
                color: color
            });
            
            fetch('../incloud/manage_question_tags.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: formData
            })
            .then(r => r.json())
            .then(data => {
                if (data.success) {
                    document.getElementById('new-tag-name').value = '';
                    openTagsModal();
                } else {
                    showVocabToast('خطا: ' + data.message, 'error');
                }
            })
            .catch(err => {
                showVocabToast('خطا در ارتباط با سرور', 'error');
            });
        }

        let currentEditorContext = null;

        function openEditorModal(type, field, id) {
            currentEditorContext = { type, field, id };
            let content = '';
            
            if (type === 'question') {
                content = currentQuestionData.question[field] || '';
            } else if (type === 'answer') {
                const answer = currentQuestionData.answers.find(a => a.id == id);
                if (answer) content = answer[field] || '';
            }

            $('#summernote').summernote({
                height: 250,
                dialogsInBody: true,
                toolbar: [
                    ['style', ['style']],
                    ['font', ['bold', 'underline', 'clear']],
                    ['color', ['color']],
                    ['para', ['ul', 'ol', 'paragraph']],
                    ['insert', ['link', 'picture', 'video']],
                    ['view', ['fullscreen', 'codeview', 'help']]
                ],
                callbacks: {
                    onImageUpload: function(files) {
                        for(let i=0; i < files.length; i++) {
                            uploadEditorImage(files[i]);
                        }
                    }
                }
            });

            $('#summernote').summernote('code', processHtmlContent(content));
            
            const editorModal = new bootstrap.Modal(document.getElementById('editorModal'));
            editorModal.show();
        }

        function uploadEditorImage(file) {
            const data = new FormData();
            data.append("image", file);
            data.append("csrf_token", csrfToken);

            fetch('../incloud/upload_image.php', {
                method: 'POST',
                body: data
            })
            .then(r => r.json())
            .then(data => {
                if (data.success) {
                    $('#summernote').summernote('insertImage', data.url);
                } else {
                    showVocabToast('خطا در آپلود: ' + data.message, 'error');
                }
            })
            .catch(err => {
                showVocabToast('خطا در ارتباط با سرور آپلود', 'error');
            });
        }

        function generateAiImage() {
            const promptStr = prompt("لطفاً موضوع تصویری که می‌خواهید ساخته شود را وارد کنید (می‌توانید فارسی بنویسید):");
            if (!promptStr || promptStr.trim() === "") return;

            const btn = document.getElementById('ai-image-btn');
            const originalHtml = btn.innerHTML;
            btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> در حال تولید (ممکن است زمان‌بر باشد)...';
            btn.disabled = true;

            const formData = createFormDataWithCSRF({ prompt: promptStr.trim() });

            fetch('../incloud/generate_ai_image.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: formData
            })
            .then(r => r.json())
            .then(data => {
                if (data.success) {
                    $('#summernote').summernote('insertImage', data.url);
                    showVocabToast(data.message || 'تصویر با موفقیت تولید و اضافه شد', 'success');
                } else {
                    showVocabToast('خطا در تولید تصویر: ' + data.message, 'error');
                }
            })
            .catch(err => {
                console.error(err);
                showVocabToast('خطا در ارتباط با سرور تولید تصویر', 'error');
            })
            .finally(() => {
                btn.innerHTML = originalHtml;
                btn.disabled = false;
            });
        }

        document.addEventListener("DOMContentLoaded", function () {
            const saveEditorBtn = document.getElementById('save-editor-btn');
            if (saveEditorBtn) {
                saveEditorBtn.addEventListener('click', function() {
                    if (!currentEditorContext) return;
                    
                    const newContent = $('#summernote').summernote('code');
                    const { type, field, id } = currentEditorContext;
                    
                    const formData = createFormDataWithCSRF({
                        type: type,
                        field: field,
                        id: id,
                        content: newContent
                    });
                    
                    saveEditorBtn.disabled = true;
                    saveEditorBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';

                    fetch('../incloud/update_qa_content.php', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                        body: formData
                    })
                    .then(r => r.json())
                    .then(data => {
                        if (data.success) {
                            showVocabToast('تغییرات با موفقیت ذخیره شد', 'success');
                            if (type === 'question') {
                                currentQuestionData.question[field] = newContent;
                            } else if (type === 'answer') {
                                const answer = currentQuestionData.answers.find(a => a.id == id);
                                if (answer) answer[field] = newContent;
                            }
                            
                            const editorModal = bootstrap.Modal.getInstance(document.getElementById('editorModal'));
                            editorModal.hide();
                            
                            // Render again
                            if (type === 'question' && field === 'info') {
                                showExplanationContent();
                            } else if (type === 'answer' && field === 'info') {
                                showExplanationContent();
                            } else {
                                showTranslationContent();
                            }
                            
                        } else {
                            showVocabToast('خطا: ' + data.message, 'error');
                        }
                    })
                    .finally(() => {
                        saveEditorBtn.disabled = false;
                        saveEditorBtn.innerHTML = 'ذخیره';
                    });
                });
            }
        });
</script>

    <script src="https://cdn.jsdelivr.net/npm/driver.js@1.0.1/dist/driver.js.iife.js"></script>
    <script>
    function startGuidedTour() {
        // بررسی اینکه آیا کاربر قبلاً تور را دیده یا خیر
        if (localStorage.getItem('farsifahr_tour_shown') === 'true') {
            return;
        }

        const driver = window.driver.js.driver;
        const driverObj = driver({
            showProgress: true,
            nextBtnText: 'بعدی',
            prevBtnText: 'قبلی',
            doneBtnText: 'پایان',
            allowClose: true,
            overlayColor: 'rgba(0,0,0,0.8)',
            onDestroyStarted: () => {
                if (window.tourCheckboxChecked) {
                    localStorage.setItem('farsifahr_tour_shown', 'true');
                }
                driverObj.destroy();
            },
            onPopoverRender: (popover) => {
                const footer = popover.footer || document.querySelector('.driver-popover-footer');
                if (footer && !document.getElementById('dontShowTourContainer')) {
                    const container = document.createElement("div");
                    container.id = "dontShowTourContainer";
                    container.style.display = "flex";
                    container.style.alignItems = "center";
                    container.style.gap = "5px";
                    container.style.marginRight = "auto";

                    const checkbox = document.createElement("input");
                    checkbox.type = "checkbox";
                    checkbox.id = "dontShowTourCheckbox";
                    checkbox.style.cursor = "pointer";
                    if (window.tourCheckboxChecked) checkbox.checked = true;
                    checkbox.addEventListener("change", (e) => {
                        window.tourCheckboxChecked = e.target.checked;
                        if (e.target.checked) {
                            localStorage.setItem('farsifahr_tour_shown', 'true');
                        } else {
                            localStorage.removeItem('farsifahr_tour_shown');
                        }
                    });

                    const label = document.createElement("label");
                    label.htmlFor = "dontShowTourCheckbox";
                    label.innerText = "دیگر نشان نده";
                    label.style.fontSize = "12px";
                    label.style.color = "#666";
                    label.style.cursor = "pointer";
                    label.style.margin = "0";

                    const closeBtn = document.createElement("button");
                    closeBtn.innerText = "بستن";
                    closeBtn.style.fontSize = "12px";
                    closeBtn.style.color = "#f56565";
                    closeBtn.style.border = "1px solid #f56565";
                    closeBtn.style.borderRadius = "4px";
                    closeBtn.style.background = "transparent";
                    closeBtn.style.padding = "2px 8px";
                    closeBtn.style.cursor = "pointer";
                    
                    closeBtn.addEventListener("click", () => {
                        if (checkbox.checked) {
                            localStorage.setItem('farsifahr_tour_shown', 'true');
                        }
                        driverObj.destroy();
                    });

                    container.appendChild(closeBtn);
                    container.appendChild(checkbox);
                    container.appendChild(label);

                    footer.appendChild(container);
                }
            },
            steps: [
                { 
                    element: '#translateBtn', 
                    popover: { 
                        title: 'دکمه ترجمه', 
                        description: 'با کلیک روی این دکمه، صورت سوال و تمام گزینه‌ها بلافاصله به فارسی ترجمه می‌شوند.',
                        side: "bottom", align: 'start' 
                    } 
                },
                { 
                    element: '#explainBtn', 
                    popover: { 
                        title: 'دکمه توضیح و درک مطلب', 
                        description: 'این دکمه علاوه بر ترجمه، توضیح کامل و منطق سوال را برای یادگیری بهتر به شما نمایش می‌دهد.',
                        side: "bottom", align: 'start' 
                    } 
                },
                { 
                    element: '#report-btn', 
                    popover: { 
                        title: 'گزارش سوال', 
                        description: 'در صورت مشاهده هرگونه اشتباه در سوال یا ترجمه، از اینجا گزارش دهید. در صورت تایید، اشتراک VIP هدیه می‌گیرید!',
                        side: "left", align: 'start' 
                    } 
                },
                { 
                    element: '#next-btn', 
                    popover: { 
                        title: 'ناوبری سوالات', 
                        description: 'برای رفتن به سوال بعدی از این دکمه استفاده کنید. همچنین می‌توانید از کلیدهای جهت‌نما استفاده کنید.',
                        side: "top", align: 'start' 
                    } 
                },
                { 
                    element: '#bookmark-btn', 
                    popover: { 
                        title: 'علامت‌گذاری سوال', 
                        description: 'سوالات مهم یا سخت را ستاره‌دار کنید تا بعداً در بخش سوالات نشان‌شده راحت‌تر آن‌ها را پیدا و مرور کنید.',
                        side: "top", align: 'start' 
                    } 
                },
                { 
                    element: '#question-buttons', 
                    popover: { 
                        title: 'وضعیت یادگیری سوالات', 
                        description: 'دایره‌های رنگی وضعیت شما را نشان می‌دهند:<br>🔘 <b>خاکستری:</b> پاسخ داده نشده<br>🔴 <b>قرمز:</b> بلد نیستید (۲ پاسخ غلط)<br>🔵 <b>آبی:</b> ۵۰-۵۰ هستید<br>🟢 <b>سبز:</b> این سوال را کاملاً بلدید',
                        side: "top", align: 'center' 
                    } 
                },
                { 
                    element: '#text', 
                    popover: { 
                        title: 'ترجمه هوشمند کلمات', 
                        description: 'روی هر کلمه آلمانی که کلیک کنید، ترجمه آن باز می‌شود. می‌توانید آن را ذخیره کنید تا در بخش "واژه آموزی" داشبورد به صورت فلش‌کارت تمرین کنید.',
                        side: "bottom", align: 'center' 
                    } 
                }
            ]
        });

        driverObj.drive();
    }

    // شروع تور با کمی تاخیر برای لود کامل سوال
    // setTimeout(startGuidedTour, 1500); // Disabled auto start, user triggers via رانما button
    </script>
</body>

</html>