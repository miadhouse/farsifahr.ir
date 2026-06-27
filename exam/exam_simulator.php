<?php
require_once __DIR__ . '/../incloud/questions.php';
require_once __DIR__ . '/../incloud/subscription-functions.php';
require_once __DIR__ . '/../config/config.php';

if (!isset($_SESSION['user_id']) || !isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header('Location: ../index.php');
    exit();
}

// بررسی CSRF token
$csrf_token = $_SESSION['csrf_token'] ?? '';
if (empty($csrf_token)) {
    // اگر csrf token وجود نداشت، session را پاک کن و به صفحه login برگردان
    session_destroy();
    header('Location: ../index.php');
    exit();
}

$user_id = $_SESSION['user_id'];
$isSubscribed = is_user_vip($user_id, $pdo);

if (!$isSubscribed) {
    die('شما دسترسی به این بخش را ندارید. لطفاً اشتراک VIP خریداری کنید.');
}
// تابع بازگشتی برای دریافت تمام سوالات یک دسته و تمام زیردسته‌هایش
function getAllCategoryQuestionsRecursive($pdo, $parentId, $user_id = null)
{
    $allQuestions = [];

    // دریافت exam_date_type کاربر
    $examDateType = getUserExamDateType($pdo, $user_id);

    // ساخت شرط فیلتر available
    $availableCondition = "";
    if ($examDateType === 'before') {
        $availableCondition = " AND (available = 0 OR available = 1)";
    } elseif ($examDateType === 'after') {
        $availableCondition = " AND (available = 0 OR available = 2)";
    }

    // دریافت تمام زیردسته‌های مستقیم
    $stmt = $pdo->prepare("SELECT id FROM categories WHERE parent_id = :parentId");
    $stmt->bindValue(':parentId', $parentId, PDO::PARAM_INT);
    $stmt->execute();
    $categoryIds = $stmt->fetchAll(PDO::FETCH_COLUMN);

    if (count($categoryIds) > 0) {
        foreach ($categoryIds as $catId) {
            // سوالات این دسته با فیلتر available
            $pattern = "%," . $catId . ",%";
            $stmt2 = $pdo->prepare("
                SELECT * FROM questions 
                WHERE category_id LIKE :pattern" . $availableCondition
            );
            $stmt2->bindValue(':pattern', $pattern, PDO::PARAM_STR);
            $stmt2->execute();

            $results = $stmt2->fetchAll(PDO::FETCH_ASSOC);
            if ($results) {
                $allQuestions = array_merge($allQuestions, $results);
            }

            // سوالات زیردسته‌های این دسته (بازگشتی)
            $subQuestions = getAllCategoryQuestionsRecursive($pdo, $catId, $user_id);
            if ($subQuestions) {
                $allQuestions = array_merge($allQuestions, $subQuestions);
            }
        }
    }

    return $allQuestions;
}

// انتخاب 20 سوال از Grundstoff (id = 0)
$grundstoffQuestions = getAllCategoryQuestionsRecursive($pdo, 0, $user_id);
shuffle($grundstoffQuestions);
$grundstoffSelected = array_slice($grundstoffQuestions, 0, min(20, count($grundstoffQuestions)));

// انتخاب 10 سوال از Zusatzstoff (id = 1)
$zusatzstoffQuestions = getAllCategoryQuestionsRecursive($pdo, 1, $user_id);
shuffle($zusatzstoffQuestions);
$zusatzstoffSelected = array_slice($zusatzstoffQuestions, 0, min(10, count($zusatzstoffQuestions)));

// ذخیره ID های سوالات
$grundstoffIds = array_column($grundstoffSelected, 'id');
$zusatzstoffIds = array_column($zusatzstoffSelected, 'id');

// بررسی تعداد سوالات
if (count($grundstoffIds) < 20) {
    die("خطا: تعداد سوالات Grundstoff کافی نیست. فقط " . count($grundstoffIds) . " سوال یافت شد. (مجموع کل: " . count($grundstoffQuestions) . ")");
}

if (count($zusatzstoffIds) < 10) {
    die("خطا: تعداد سوالات Zusatzstoff کافی نیست. فقط " . count($zusatzstoffIds) . " سوال یافت شد. (مجموع کل: " . count($zusatzstoffQuestions) . ")");
}

// ترکیب همه سوالات برای استفاده
$allQuestions = array_merge($grundstoffIds, $zusatzstoffIds);
?>
<!DOCTYPE html>
<html data-bs-theme="light" lang="en" style="height: 100%;">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, shrink-to-fit=no">
    <title>Prüfungssimulation</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://use.fontawesome.com/releases/v5.12.0/css/all.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
    <style>
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

        .timer-box {
            font-size: 17px;
            font-weight: bold;
            color: #000;
            padding: 8px 8px;
            background: white;
            border-radius: 10px;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
        }

        @media (max-width: 576px) {
            .timer-box {
                font-size: 18px;
                padding: 8px 15px;
            }
        }

        .timer-warning {
            color: #dc3545;
            animation: pulse 1s infinite;
        }

        @keyframes pulse {

            0%,
            100% {
                opacity: 1;
            }

            50% {
                opacity: 0.5;
            }
        }

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

        .question-btn {
            min-width: 45px;
            margin: 2px;
            padding: 8px 12px;
            font-size: 14px;
        }

        @media (max-width: 768px) {
            .question-btn {
                min-width: 40px;
                margin: 2px;
                padding: 6px 10px;
                font-size: 13px;
            }
        }

        .question-btn-answered {
            background-color: #0d6efd !important;
            border-color: #0d6efd !important;
        }

        .question-btn-current {
            background-color: #212529 !important;
            border-color: #212529 !important;
        }

        .section-switch {
            background: white;
            border-radius: 10px;
            padding: 5px;
            display: inline-flex;
            gap: 5px;
        }

        @media (max-width: 576px) {
            .section-switch {
                width: 100%;
                justify-content: center;
            }
        }

        .section-btn {
            padding: 8px 20px;
            border: none;
            background: transparent;
            cursor: pointer;
            border-radius: 8px;
            font-weight: bold;
            transition: all 0.3s;
            font-size: 14px;
        }

        @media (max-width: 576px) {
            .section-btn {
                padding: 6px 15px;
                font-size: 12px;
                flex: 1;
            }
        }

        .section-btn.active {
            background: #198754;
            color: white;
        }

        .video-placeholder {
            position: relative;
            cursor: pointer;
            border-radius: 10px;
            overflow: hidden;
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
            cursor: pointer;
        }

        .keypad-btn {
            height: 50px;
            font-size: 18px;
            font-weight: bold;
        }

        .answer-item {
            transition: all 0.3s ease;
            border-radius: 8px;
            padding: 10px;
            margin-bottom: 10px;
        }

        .results-modal {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.85);
            display: none;
            align-items: center;
            justify-content: center;
            z-index: 9999;
            backdrop-filter: blur(5px);
        }

        .results-content {
            background: #f8f9fa;
            border-radius: 25px;
            padding: 30px;
            max-width: 700px;
            width: 95%;
            text-align: center;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.3);
            direction: rtl;
        }

        .result-card {
            background: white;
            border-radius: 15px;
            padding: 20px;
            margin-bottom: 20px;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.05);
            transition: transform 0.3s;
        }

        .result-card:hover {
            transform: translateY(-5px);
        }

        .result-stat-box {
            padding: 15px;
            border-radius: 12px;
            background: #f1f3f5;
            height: 100%;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
        }

        .stat-icon {
            font-size: 24px;
            margin-bottom: 10px;
        }

        .result-passed {
            color: #198754;
            font-weight: bold;
        }

        .result-failed {
            color: #dc3545;
            font-weight: bold;
        }

        .result-title-text {
            font-size: 32px;
            margin-top: 10px;
        }

        .stat-label {
            font-size: 14px;
            color: #6c757d;
            margin-bottom: 5px;
        }

        .stat-value {
            font-size: 20px;
            font-weight: bold;
            color: #212529;
        }
    </style>
</head>

<body style="height: 100%;background-color: #d3f5da;">
    <?php render_announcements('exam'); ?>
    <div class="container" style="height: 100%;">
        <!-- Header -->
        <div class="text-white bg-success d-flex justify-content-between align-items-center p-2 px-3 px-md-4"
            style="border-bottom-right-radius: 30px;border-bottom-left-radius: 30px;position: sticky;top: 0;z-index: 100;">
            <div class="d-flex align-items-center gap-2 gap-md-3">
                <a class="btn btn-warning btn-sm btn-danger btn-circle" href="../admin/practice.php">
                    <i class="fas fa-times"></i>
                </a>
                <span class="badge bg-warning text-dark d-none d-md-inline">شبیه ساز امتحان</span>
                <span class="badge bg-warning text-dark d-md-none" style="font-size: 10px;">امتحان</span>
                <span id="code" class="d-none d-md-inline"></span>
            </div>
            <div class="timer-box" id="timer">30:00</div>
            <span class="d-none fw-bold d-md-inline punkt"><span id="punkt"></span></span>
            <span class="d-md-none fw-bold punkt" style="font-size: 12px;">P: <span id="punkt"></span></span>
        </div>

        <!-- Main Content -->
        <div class="mt-4 p-4" style="height: 100%;">
            <h1 id="text" class="fw-bold h6 mb-4"></h1>
            <div class="row">
                <div class="col-12 col-md-6" id="media"></div>
                <div class="col-12 col-md-6">
                    <div class="d-flex flex-column gap-3">
                        <span id="asw_pretext" class="fw-bold"></span>

                        <!-- Video Controls -->
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
                        <div id="answers" class="answers-section"></div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Bottom Navigation -->
        <div class="fixed-bottom container-fluid"
            style="margin-bottom: 50px;padding: 5px;background-color: #aad7aa;border-radius: 30px 30px 0 0;width: 92%;">
            <div class="row px-4 py-2">
                <div class="col-12 col-md-6 text-start mb-2 mb-md-0">
                    <div class="section-switch">
                        <button class="section-btn active" id="grundstoff-btn" onclick="switchSection('grundstoff')">
                            Grundstoff (20)
                        </button>
                        <button class="section-btn" id="zusatzstoff-btn" onclick="switchSection('zusatzstoff')">
                            B (10)
                        </button>
                    </div>
                </div>
                <div class="col-12 col-md-6 text-end">
                    <button class="btn btn-primary mx-1 btn-sm p-1 bookmark-btn" id="bookmark-btn"
                        onclick="toggleBookmark()" title="علامت گذاری سوال">
                        <i id="bookmark-icon" class="far fa-star text-warning"></i>
                    </button>
                    <button class="btn btn-success mx-1 btn-sm p-1" onclick="nextQuestion()">
                        Weiter <i class="fas fa-arrow-right"></i>
                    </button>
                    <button class="btn btn-warning btn-sm p-1" onclick="showResultsConfirm()">
                        Auswertung
                    </button>
                </div>
            </div>
        </div>

        <!-- Question Buttons -->
        <div class="fixed-bottom container-fluid p-0">
            <div class="d-flex justify-content-between align-items-center p-2 px-4"
                style="background: var(--bs-success);">
                <button class="btn btn-light text-success" onclick="previousQuestion()">
                    <i class="fas fa-step-backward"></i>
                </button>
                <div class="d-md-block d-flex gap-1 flex-wrap justify-content-center" id="question-buttons"></div>
                <button class="btn btn-light text-success" onclick="nextQuestion()">
                    <i class="fas fa-step-forward"></i>
                </button>
            </div>
        </div>
    </div>

    <!-- Video Modal -->
    <div class="modal fade" id="videoModal" tabindex="-1">
        <div class="modal-dialog modal-dialog-xl modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Video</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body p-0">
                    <video id="modal-video" width="100%" controls>
                        <source src="" type="video/mp4">
                    </video>
                </div>
            </div>
        </div>
    </div>

    <!-- Results Modal -->
    <div class="results-modal" id="results-modal">
        <div class="results-content">
            <div id="result-icon"></div>
            <h2 id="result-title" class="mb-4"></h2>
            
            <div class="result-card">
                <div id="result-details"></div>
            </div>

            <div class="d-flex gap-2 gap-md-3 justify-content-center flex-wrap mt-4">
                <button class="btn btn-primary px-4 py-2 rounded-pill" onclick="reviewAnswers()">
                    <i class="fas fa-search me-1"></i> بررسی پاسخ‌ها
                </button>
                <button class="btn btn-success px-4 py-2 rounded-pill" onclick="location.reload()">
                    <i class="fas fa-redo me-1"></i> امتحان مجدد
                </button>
                <button class="btn btn-secondary px-4 py-2 rounded-pill" onclick="location.href='../admin/'">
                    <i class="fas fa-home me-1"></i> بازگشت
                </button>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script>
        const csrfToken = '<?= $csrf_token ?>';
        const grundstoffQuestions = <?= json_encode($grundstoffIds) ?>;
        const zusatzstoffQuestions = <?= json_encode($zusatzstoffIds) ?>;

        let currentSection = 'grundstoff';
        let currentQuestionIndex = 0;
        let currentQuestionData = null;
        let userAnswers = {}; // {questionId: {answerId: true/false} or {numeric_value: "123"}}
        let examStartTime = Date.now();
        let examDuration = 30 * 60 * 1000; // 30 minutes in milliseconds
        let timerInterval = null;
        let isVideoQuestion = false;
        let videoViewCount = 0;
        let maxVideoViews = 5;
        let hasWatchedVideo = false;
        let showingAnswers = false;
        let videoUrl = '';
        let examFinished = false;

        function getCurrentQuestions() {
            return currentSection === 'grundstoff' ? grundstoffQuestions : zusatzstoffQuestions;
        }

        function switchSection(section) {
            if (examFinished) return;

            currentSection = section;
            currentQuestionIndex = 0;

            // Update buttons
            document.getElementById('grundstoff-btn').classList.toggle('active', section === 'grundstoff');
            document.getElementById('zusatzstoff-btn').classList.toggle('active', section === 'zusatzstoff');

            loadCurrentQuestion();
            renderQuestionButtons();
            updateNavigationState();
        }

        function startTimer() {
            timerInterval = setInterval(() => {
                const elapsed = Date.now() - examStartTime;
                const remaining = Math.max(0, examDuration - elapsed);

                if (remaining === 0) {
                    clearInterval(timerInterval);
                    finishExam();
                    return;
                }

                const minutes = Math.floor(remaining / 60000);
                const seconds = Math.floor((remaining % 60000) / 1000);
                const display = `${minutes}:${seconds.toString().padStart(2, '0')}`;

                const timerEl = document.getElementById('timer');
                timerEl.textContent = display;

                if (remaining < 5 * 60 * 1000) {
                    timerEl.classList.add('timer-warning');
                }
            }, 1000);
        }

        function createFormDataWithCSRF(data = {}) {
            const formData = new URLSearchParams();
            formData.append('csrf_token', csrfToken);
            for (const [key, value] of Object.entries(data)) {
                formData.append(key, value);
            }
            return formData;
        }

        function loadCurrentQuestion() {
            const questions = getCurrentQuestions();
            const questionId = questions[currentQuestionIndex];

            resetQuestionState();
            updateNavigationState();

            const formData = createFormDataWithCSRF({ question_id: questionId });

            fetch("../incloud/get_question.php", {
                method: "POST",
                headers: { "Content-Type": "application/x-www-form-urlencoded" },
                body: formData
            })
                .then(response => response.json())
                .then(data => {
                    if (data && data.success && data.question) {
                        currentQuestionData = data;
                        updateQuestionDisplay(data);
                        loadUserAnswer(questionId);
                        checkBookmarkStatus(questionId);
                    }
                })
                .catch(error => console.error("Error loading question:", error));
        }

        // تابع بوک‌مارک
        function toggleBookmark() {
            const bookmarkBtn = document.getElementById('bookmark-btn');
            const bookmarkIcon = document.getElementById('bookmark-icon');
            const questions = getCurrentQuestions();
            const questionId = questions[currentQuestionIndex];

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
                // ستاره پر (علامت گذاری شده)
                bookmarkIcon.className = 'fas fa-star text-warning';
            } else {
                // ستاره خالی (علامت گذاری نشده)
                bookmarkIcon.className = 'far fa-star text-warning';
            }
        }

        function showBookmarkToast(message, type) {
            // Create toast notification
            const toast = document.createElement('div');
            toast.className = `alert alert-${type === 'error' ? 'danger' : 'success'} bookmark-toast alert-dismissible`;
            toast.style.position = 'fixed';
            toast.style.top = '20px';
            toast.style.right = '20px';
            toast.style.zIndex = '9999';
            toast.style.minWidth = '300px';
            toast.style.animation = 'slideInRight 0.3s ease';
            toast.innerHTML = `
                <div class="d-flex justify-content-between align-items-center">
                    <span><i class="fas fa-${type === 'error' ? 'exclamation-triangle' : 'check-circle'} me-2"></i>${message}</span>
                    <button type="button" class="btn-close" onclick="this.parentElement.parentElement.remove()"></button>
                </div>
            `;

            document.body.appendChild(toast);

            // Auto remove after 3 seconds
            setTimeout(() => {
                if (toast.parentElement) {
                    toast.remove();
                }
            }, 3000);
        }

        // تابع جدید برای به‌روزرسانی وضعیت دکمه‌های ناوبری
        function updateNavigationState() {
            const currentQuestions = getCurrentQuestions();
            const isFirstQuestion = currentQuestionIndex === 0 && currentSection === 'grundstoff';
            const isLastQuestion = currentQuestionIndex === currentQuestions.length - 1 && currentSection === 'zusatzstoff';

            // دکمه‌های back/previous
            const backButtons = document.querySelectorAll('button[onclick="previousQuestion()"]');
            backButtons.forEach(btn => {
                if (isFirstQuestion) {
                    btn.disabled = true;
                    btn.style.opacity = '0.5';
                    btn.style.cursor = 'not-allowed';
                } else {
                    btn.disabled = false;
                    btn.style.opacity = '1';
                    btn.style.cursor = 'pointer';
                }
            });

            // دکمه‌های next
            const nextButtons = document.querySelectorAll('button[onclick="nextQuestion()"]');
            nextButtons.forEach(btn => {
                if (isLastQuestion) {
                    btn.disabled = true;
                    btn.style.opacity = '0.5';
                    btn.style.cursor = 'not-allowed';
                } else {
                    btn.disabled = false;
                    btn.style.opacity = '1';
                    btn.style.cursor = 'pointer';
                }
            });
        }

        function resetQuestionState() {
            videoViewCount = 0;
            hasWatchedVideo = false;
            showingAnswers = false;
            isVideoQuestion = false;
        }

        function updateQuestionDisplay(data) {
            const question = data.question;
            const fileName = question.picture || '';
            const extension = fileName ? fileName.split('.').pop().toLowerCase() : '';
            const fileNameWithoutExt = fileName ? fileName.replace(/\.[^/.]+$/, "") : '';

            isVideoQuestion = ['mp4', 'm4v', 'webm'].includes(extension);

            if (['jpg', 'jpeg', 'png', 'gif', 'webp'].includes(extension)) {
                const imageUrl = 'https://t24.theorie24.de/2025-01-v400/data/img/images/' + fileName;
                document.getElementById("media").innerHTML = '<img src="' + imageUrl + '" class="w-100">';
                showRegularQuestion(data);
            } else if (isVideoQuestion) {
                videoUrl = 'https://www.theorie24.de/live_images/_current_ws_2024-10-01_2025-04-01/videos/' + fileName;
                showVideoQuestion(data, fileNameWithoutExt);
            } else {
                document.getElementById("media").innerHTML = '';
                showRegularQuestion(data);
            }

            document.getElementById("code").innerText = question.number || '';
            document.querySelectorAll(".punkt").forEach(el => {
                el.innerText = question.points || '0';
            });
        }

        function showVideoQuestion(data, fileNameWithoutExt) {
            document.getElementById("text").innerText = "Bitte starten Sie den Film, um sich mit der Situation vertraut zu machen.";
            document.getElementById("video-controls").style.display = "block";
            document.getElementById("answers").style.display = "none";
            updateVideoPlaceholder(fileNameWithoutExt);
            updateVideoControls();
        }

        function showRegularQuestion(data) {
            document.getElementById("text").innerText = data.question.text;
            document.getElementById("asw_pretext").innerHTML = data.question.asw_pretext || '';
            document.getElementById("video-controls").style.display = "none";
            document.getElementById("answers").style.display = "block";
            answerBuilder(data.answers);
        }

        function updateVideoPlaceholder(fileNameWithoutExt) {
            const imageName = hasWatchedVideo ? fileNameWithoutExt + '_ende.jpg' : fileNameWithoutExt + '_anfang.jpg';
            const imageUrl = 'https://t24.theorie24.de/2025-01-v400/data/img/images/' + imageName;
            let playButtonHtml = '';

            if (videoViewCount < maxVideoViews && !showingAnswers) {
                playButtonHtml = '<button class="play-button" onclick="playVideo()"><i class="fas fa-play"></i></button>';
            }

            document.getElementById("media").innerHTML =
                '<div class="video-placeholder">' +
                '<img src="' + imageUrl + '" class="w-100">' +
                playButtonHtml +
                '</div>';
        }

        function updateVideoControls() {
            document.getElementById("remaining-views").innerText = maxVideoViews - videoViewCount;
            const startBtn = document.getElementById("video-start-btn");
            const zurAufgabeBtn = document.getElementById("zur-aufgabe-btn");

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

            if (showingAnswers) {
                startBtn.style.display = "none";
                zurAufgabeBtn.style.display = "none";
            }
        } function playVideo() {
            if (videoViewCount >= maxVideoViews || showingAnswers) return;

            videoViewCount++;
            hasWatchedVideo = true;

            if (currentQuestionData) {
                const fileName = currentQuestionData.question.picture || '';
                const fileNameWithoutExt = fileName.replace(/\.[^/.]+$/, "");
                updateVideoPlaceholder(fileNameWithoutExt);
            }

            const modal = new bootstrap.Modal(document.getElementById('videoModal'));
            const modalVideo = document.getElementById('modal-video');
            modalVideo.src = videoUrl;
            modal.show();
            updateVideoControls();

            modalVideo.onended = function () { modal.hide(); };
            document.getElementById('videoModal').addEventListener('hidden.bs.modal', function () {
                modalVideo.pause();
                modalVideo.src = '';
            });
        }

        function showAnswers() {
            if (!currentQuestionData) return;
            showingAnswers = true;
            document.getElementById("video-controls").style.display = "none";
            document.getElementById("answers").style.display = "block";
            document.getElementById("text").innerText = currentQuestionData.question.text;
            answerBuilder(currentQuestionData.answers);

            if (currentQuestionData) {
                const fileName = currentQuestionData.question.picture || '';
                const fileNameWithoutExt = fileName.replace(/\.[^/.]+$/, "");
                const imageUrl = 'https://t24.theorie24.de/2025-01-v400/data/img/images/' + fileNameWithoutExt + '_anfang.jpg';
                document.getElementById("media").innerHTML = '<div class="video-placeholder"><img src="' + imageUrl + '" class="w-100"></div>';
            }
        }

        function answerBuilder(answers) {
            if (!answers || answers.length === 0) return;

            let answersText = "";
            const answerType = answers[0].asw_type || 1;
            const questions = getCurrentQuestions();
            const questionId = questions[currentQuestionIndex];

            if (answerType == 2) {
                const answer = answers[0];
                const hint = answer.asw_hint || '';
                const savedAnswer = userAnswers[questionId]?.numeric_value || '';

                answersText = `
                <div class="text-center">
                    <div class="mb-4">
                        <div class="d-flex justify-content-center align-items-center gap-2 mb-3">
                            <input type="text" id="numeric-answer" value="${savedAnswer}"
                                   class="form-control text-center" style="width: 150px; font-size: 18px; font-weight: bold;">
                            <span class="fw-bold fs-5">${hint}</span>
                        </div>
                    </div>
                    <div class="numeric-keypad mx-auto" style="max-width: 300px;">
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
                    <input type="hidden" id="correct-answer" value="${answer.text || ''}">
                </div>
            `;
            } else {
                answers.forEach((answer, index) => {
                    const isChecked = userAnswers[questionId]?.[answer.id] ? 'checked' : '';
                    const isImage = answer.is_image == 1;
                    let answerContent = '';

                    if (isImage) {
                        let imageName = '';
                        let isEtcImage = false;

                        if (answer.original_content) {
                            const etcMatch = answer.original_content.match(/%IMG_ETC%\/([^"']+)/);
                            if (etcMatch) {
                                imageName = etcMatch[1];
                                isEtcImage = true;
                            } else {
                                const answerMatch = answer.original_content.match(/%IMG_ANSWER%\/([^"']+)/);
                                if (answerMatch) imageName = answerMatch[1];
                            }
                        }

                        if (!imageName) imageName = `answer_${answer.id}.png`;

                        const imageUrl = isEtcImage || imageName.toLowerCase().startsWith('div')
                            ? `https://t24.theorie24.de/2025-01-v400/data/img/etc/de/${imageName}`
                            : `https://t24.theorie24.de/2025-01-v400/data/img/answers/${imageName}`;
                        const maxWidth = isEtcImage || imageName.toLowerCase().startsWith('div') ? 300 : 90;

                        answerContent = `<img src="${imageUrl}" alt="Answer ${index + 1}" class="img-fluid" style="max-width:${maxWidth}px;">`;
                    } else {
                        answerContent = `<span class="fw-bold">${answer.text}</span>`;
                    }

                    answersText += `
                    <div class="d-flex mb-3 align-items-center answer-item">
                        <label class="form-label me-2 custom-checkbox">
                            <input type="checkbox" class="checkbox" data-answer-id="${answer.id}" ${isChecked} onchange="handleAnswerChange(this)">
                            <span class="checkmark"></span>
                        </label>
                        ${answerContent}
                    </div>
                `;
                });
            }

            document.getElementById("answers").innerHTML = answersText;
        }

        function handleAnswerChange(checkbox) {
            const questions = getCurrentQuestions();
            const questionId = questions[currentQuestionIndex];
            const answerId = checkbox.getAttribute('data-answer-id');

            if (!userAnswers[questionId]) {
                userAnswers[questionId] = {};
            }

            if (checkbox.checked) {
                userAnswers[questionId][answerId] = true;
            } else {
                delete userAnswers[questionId][answerId];
            }

            renderQuestionButtons();
        }

        function addNumber(num) {
            const input = document.getElementById('numeric-answer');
            if (!input) return;
            input.value += num;
            saveNumericAnswer();
        }

        function addComma() {
            const input = document.getElementById('numeric-answer');
            if (!input || input.value.includes(',')) return;
            if (input.value) input.value += ',';
            saveNumericAnswer();
        }

        function clearLastChar() {
            const input = document.getElementById('numeric-answer');
            if (!input) return;
            input.value = input.value.slice(0, -1);
            saveNumericAnswer();
        }

        function clearAnswer() {
            const input = document.getElementById('numeric-answer');
            if (!input) return;
            input.value = '';
            saveNumericAnswer();
        }

        function saveNumericAnswer() {
            const input = document.getElementById('numeric-answer');
            const questions = getCurrentQuestions();
            const questionId = questions[currentQuestionIndex];

            if (!userAnswers[questionId]) {
                userAnswers[questionId] = {};
            }

            userAnswers[questionId].numeric_value = input.value;
            renderQuestionButtons();
        }

        function loadUserAnswer(questionId) {
            if (!userAnswers[questionId]) return;

            const answerData = userAnswers[questionId];

            if (answerData.numeric_value !== undefined) {
                const input = document.getElementById('numeric-answer');
                if (input) input.value = answerData.numeric_value;
            } else {
                Object.keys(answerData).forEach(answerId => {
                    const checkbox = document.querySelector(`input[data-answer-id="${answerId}"]`);
                    if (checkbox) checkbox.checked = true;
                });
            }
        }

        function nextQuestion() {
            if (examFinished) return;

            const currentQuestions = getCurrentQuestions();

            if (currentQuestionIndex < currentQuestions.length - 1) {
                // حرکت به سوال بعدی در همین بخش
                currentQuestionIndex++;
                loadCurrentQuestion();
                renderQuestionButtons();
            } else {
                // آخرین سوال این بخش است
                if (currentSection === 'grundstoff') {
                    // از grundstoff به zusatzstoff برو
                    switchSection('zusatzstoff');
                } else {
                    // آخرین سوال امتحان است
                    // می‌توانید پیام یا عملیات خاصی اینجا انجام دهید
                    console.log('آخرین سوال امتحان');
                }
            }
        }

        function previousQuestion() {
            if (examFinished) return;

            if (currentQuestionIndex > 0) {
                // حرکت به سوال قبلی در همین بخش
                currentQuestionIndex--;
                loadCurrentQuestion();
                renderQuestionButtons();
            } else {
                // اولین سوال این بخش است
                if (currentSection === 'zusatzstoff') {
                    // از zusatzstoff به آخرین سوال grundstoff برو
                    currentSection = 'grundstoff';
                    currentQuestionIndex = grundstoffQuestions.length - 1;

                    // Update buttons
                    document.getElementById('grundstoff-btn').classList.add('active');
                    document.getElementById('zusatzstoff-btn').classList.remove('active');

                    loadCurrentQuestion();
                    renderQuestionButtons();
                } else {
                    // اولین سوال امتحان است - نمی‌توان برگشت
                    console.log('اولین سوال امتحان');
                }
            }
        }

        function goToQuestion(index) {
            currentQuestionIndex = index;
            loadCurrentQuestion();
            renderQuestionButtons();
        }

        function renderQuestionButtons() {
            const container = document.getElementById('question-buttons');
            container.innerHTML = '';
            const questions = getCurrentQuestions();

            // تعیین تعداد سوالات نمایشی بر اساس اندازه صفحه
            function getQuestionsPerPage() {
                if (window.innerWidth < 768) {
                    return 3; // موبایل
                } else if (window.innerWidth < 992) {
                    return 10; // تبلت
                } else {
                    return 20; // دسکتاپ
                }
            }

            const questionsPerPage = getQuestionsPerPage();
            const currentPage = Math.floor(currentQuestionIndex / questionsPerPage) + 1;
            const totalPages = Math.ceil(questions.length / questionsPerPage);

            const startIndex = (currentPage - 1) * questionsPerPage;
            const endIndex = Math.min(startIndex + questionsPerPage - 1, questions.length - 1);

            for (let i = startIndex; i <= endIndex; i++) {
                const questionId = questions[i];
                const btn = document.createElement('button');
                btn.className = 'btn question-btn';
                btn.textContent = i + 1;
                btn.onclick = () => goToQuestion(i);

                if (i === currentQuestionIndex) {
                    btn.classList.add('btn-dark', 'question-btn-current');
                } else if (userAnswers[questionId] && Object.keys(userAnswers[questionId]).length > 0) {
                    btn.classList.add('btn-primary', 'question-btn-answered');
                } else {
                    btn.classList.add('btn-success');
                }

                container.appendChild(btn);
            }
        }

        // Re-render buttons on window resize
        let resizeTimer;
        window.addEventListener('resize', () => {
            clearTimeout(resizeTimer);
            resizeTimer = setTimeout(() => {
                renderQuestionButtons();
            }, 250);
        });

        function showResultsConfirm() {
            const allQuestions = [...grundstoffQuestions, ...zusatzstoffQuestions];
            const answeredCount = allQuestions.filter(id => userAnswers[id] && Object.keys(userAnswers[id]).length > 0).length;

            if (answeredCount < allQuestions.length) {
                Swal.fire({
                    title: 'سوالات ناتمام',
                    text: `شما به ${answeredCount} سوال از ${allQuestions.length} سوال پاسخ داده‌اید. برای مشاهده نتیجه باید به تمام سوالات پاسخ دهید.`,
                    icon: 'warning',
                    confirmButtonText: 'متوجه شدم',
                    confirmButtonColor: '#ffc107',
                });
                return;
            }

            Swal.fire({
                title: 'پایان آزمون',
                text: 'آیا مایلید آزمون را به پایان رسانده و نتیجه را مشاهده کنید؟',
                icon: 'question',
                showCancelButton: true,
                confirmButtonText: 'بله، مشاهده نتیجه',
                cancelButtonText: 'خیر، ادامه آزمون',
                confirmButtonColor: '#198754',
                cancelButtonColor: '#6c757d',
            }).then((result) => {
                if (result.isConfirmed) {
                    finishExam();
                }
            });
        }

        function finishExam() {
            examFinished = true;
            clearInterval(timerInterval);
            calculateResults();
        }

        function calculateResults() {
            let correctCount = 0;
            let totalPoints = 0;
            let earnedPoints = 0;
            let errorPoints = 0;
            let fivePointErrors = 0;
            let wrongQuestionsIds = [];

            const allQuestions = [...grundstoffQuestions, ...zusatzstoffQuestions];
            let processedCount = 0;

            allQuestions.forEach(questionId => {
                const formData = createFormDataWithCSRF({ question_id: questionId });

                fetch("../incloud/get_question.php", {
                    method: "POST",
                    headers: { "Content-Type": "application/x-www-form-urlencoded" },
                    body: formData
                })
                    .then(response => response.json())
                    .then(data => {
                        processedCount++;

                        if (data && data.success) {
                            const points = parseInt(data.question.points) || 0;
                            totalPoints += points;

                            const isCorrect = checkAnswer(questionId, data);
                            if (isCorrect) {
                                correctCount++;
                                earnedPoints += points;
                            } else {
                                errorPoints += points;
                                wrongQuestionsIds.push(questionId);
                                if (points === 5) {
                                    fivePointErrors++;
                                }
                            }

                            saveQuestionStatus(questionId, isCorrect);

                            if (processedCount === allQuestions.length) {
                                let passed = (errorPoints <= 10 && fivePointErrors < 2) ? 1 : 0;
                                saveExamResults(earnedPoints, correctCount, errorPoints, fivePointErrors, passed, wrongQuestionsIds, allQuestions);
                                displayResults(correctCount, earnedPoints, totalPoints, errorPoints, fivePointErrors);
                            }
                        }
                    });
            });
        }

        function saveExamResults(score, correctCount, errorPoints, fivePointErrors, passed, wrongQuestionsIds, allQuestionsIds) {
            const formData = createFormDataWithCSRF({
                score: score,
                total_questions: allQuestionsIds.length,
                correct_count: correctCount,
                error_points: errorPoints,
                five_point_errors: fivePointErrors,
                passed: passed,
                wrong_questions: JSON.stringify(wrongQuestionsIds),
                all_questions: JSON.stringify(allQuestionsIds)
            });

            fetch("../incloud/save_exam_history.php", {
                method: "POST",
                headers: { "Content-Type": "application/x-www-form-urlencoded" },
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    console.log("Exam history saved successfully.");
                } else {
                    console.error("Error saving exam history:", data.error);
                }
            })
            .catch(error => console.error("Error saving exam history:", error));
        }

        // تابع جدید برای ذخیره وضعیت سوال در دیتابیس
        function saveQuestionStatus(questionId, isCorrect) {
            const formData = createFormDataWithCSRF({
                question_id: questionId,
                is_correct: isCorrect ? 1 : 0
            });

            fetch("../incloud/update_answer_status.php", {
                method: "POST",
                headers: { "Content-Type": "application/x-www-form-urlencoded" },
                body: formData
            })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        console.log(`Question ${questionId} status saved: ${data.color}`);
                    } else {
                        console.error(`Error saving question ${questionId}:`, data.error);
                    }
                })
                .catch(error => {
                    console.error(`Error saving question ${questionId}:`, error);
                });
        }

        function checkAnswer(questionId, data) {
            const userAnswer = userAnswers[questionId];
            if (!userAnswer) return false;

            const answerType = data.answers[0]?.asw_type || 1;

            if (answerType == 2) {
                const correctAnswer = data.answers[0]?.text || '';
                return userAnswer.numeric_value?.trim() === correctAnswer.trim();
            } else {
                let isCorrect = true;
                data.answers.forEach(answer => {
                    const isAnswerCorrect = answer.asw_corr == 1;
                    const isSelected = userAnswer[answer.id] === true;

                    if ((isAnswerCorrect && !isSelected) || (!isAnswerCorrect && isSelected)) {
                        isCorrect = false;
                    }
                });
                return isCorrect;
            }
        }

        function displayResults(correctCount, earnedPoints, totalPoints, errorPoints, fivePointErrors) {
            // بررسی قوانین قبولی/ردی
            let passed = true;
            let failReason = '';

            if (errorPoints > 10) {
                passed = false;
                failReason = 'بیش از ۱۰ امتیاز منفی (نمره منفی شما: ' + errorPoints + ')';
            } else if (fivePointErrors >= 2) {
                passed = false;
                failReason = 'دو پاسخ اشتباه در سوالات ۵ امتیازی';
            }

            const modal = document.getElementById('results-modal');

            if (passed) {
                document.getElementById('result-icon').innerHTML = '<i class="fas fa-check-circle text-success" style="font-size: 80px;"></i>';
                document.getElementById('result-title').innerHTML = '<div class="result-passed result-title-text">تبریک! قبول شدید</div>';
            } else {
                document.getElementById('result-icon').innerHTML = '<i class="fas fa-times-circle text-danger" style="font-size: 80px;"></i>';
                document.getElementById('result-title').innerHTML = '<div class="result-failed result-title-text">متأسفانه قبول نشدید</div>';
            }

            let resultHTML = `
                <div class="row g-3">
                    <div class="col-6 col-md-3">
                        <div class="result-stat-box">
                            <div class="stat-icon text-primary"><i class="fas fa-question-circle"></i></div>
                            <div class="stat-label">کل سوالات</div>
                            <div class="stat-value">۳۰</div>
                        </div>
                    </div>
                    <div class="col-6 col-md-3">
                        <div class="result-stat-box">
                            <div class="stat-icon text-success"><i class="fas fa-check"></i></div>
                            <div class="stat-label">پاسخ صحیح</div>
                            <div class="stat-value">${correctCount}</div>
                        </div>
                    </div>
                    <div class="col-6 col-md-3">
                        <div class="result-stat-box">
                            <div class="stat-icon text-danger"><i class="fas fa-exclamation-triangle"></i></div>
                            <div class="stat-label">نمره منفی</div>
                            <div class="stat-value">${errorPoints}</div>
                        </div>
                    </div>
                    <div class="col-6 col-md-3">
                        <div class="result-stat-box">
                            <div class="stat-icon text-warning"><i class="fas fa-star"></i></div>
                            <div class="stat-label">امتیاز کسب شده</div>
                            <div class="stat-value">${earnedPoints}</div>
                        </div>
                    </div>
                </div>
            `;

            if (!passed && failReason) {
                resultHTML += `
                    <div class="alert alert-danger mt-4 border-0 rounded-4">
                        <i class="fas fa-info-circle me-1"></i> <strong>علت مردودی:</strong> ${failReason}
                    </div>
                `;
            } else if (passed && errorPoints > 0) {
                 resultHTML += `
                    <div class="alert alert-warning mt-4 border-0 rounded-4 text-dark">
                        <i class="fas fa-exclamation-circle me-1"></i> شما با <strong>${errorPoints}</strong> امتیاز منفی قبول شدید.
                    </div>
                `;
            }

            document.getElementById('result-details').innerHTML = resultHTML;

            modal.style.display = 'flex';
        }

        function reviewAnswers() {
            document.getElementById('results-modal').style.display = 'none';
            currentSection = 'grundstoff';
            currentQuestionIndex = 0;
            loadCurrentQuestion();
            renderQuestionButtons();
        }

        // Initialize
        document.addEventListener('DOMContentLoaded', function () {
            startTimer();
            loadCurrentQuestion();
            renderQuestionButtons();
            updateNavigationState();
        });
    </script>