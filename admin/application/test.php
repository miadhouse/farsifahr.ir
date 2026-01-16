<!DOCTYPE html>
<html lang="fa" dir="rtl">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>آزمون گواهینامه رانندگی</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.2/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-icons/1.11.0/font/bootstrap-icons.min.css"
        rel="stylesheet">
    <style>
        body {
            font-family: 'Tahoma', sans-serif;
            background: linear-gradient(135deg, #e8f5e8 0%, #c8e6c9 100%);
            min-height: 100vh;
            direction: rtl;
        }

        .test-container {
            background: white;
            border-radius: 15px;
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.1);
            overflow: hidden;
            max-width: 1000px;
            margin: 20px auto;
        }

        .test-header {
            background: linear-gradient(135deg, #2e7d32 0%, #4caf50 100%);
            color: white;
            padding: 15px 25px;
        }

        .question-number {
            background: rgba(255, 255, 255, 0.2);
            padding: 5px 15px;
            border-radius: 20px;
            font-weight: bold;
            display: inline-block;
            margin-left: 10px;
        }

        .points-badge {
            background: rgba(255, 255, 255, 0.2);
            padding: 5px 15px;
            border-radius: 20px;
            font-weight: bold;
        }

        .question-content {
            padding: 30px;
        }

        .question-title {
            font-size: 1.3em;
            font-weight: 600;
            color: #2e7d32;
            margin-bottom: 25px;
        }

        .media-container {
            background: #f8f9fa;
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 25px;
            min-height: 200px;
            display: flex;
            align-items: center;
            justify-content: center;
            border: 2px dashed #dee2e6;
        }

        .media-container img {
            max-width: 100%;
            max-height: 300px;
            border-radius: 8px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
        }

        .media-placeholder {
            text-align: center;
            color: #6c757d;
        }

        .video-container {
            position: relative;
            cursor: pointer;
            border-radius: 10px;
            overflow: hidden;
        }

        .video-preview {
            position: relative;
            width: 100%;
            height: auto;
            display: block;
        }

        .video-play-button {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            width: 80px;
            height: 80px;
            background: rgba(0, 0, 0, 0.7);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 2rem;
            transition: all 0.3s ease;
            border: 3px solid white;
        }

        .video-play-button:hover {
            background: rgba(0, 0, 0, 0.9);
            transform: translate(-50%, -50%) scale(1.1);
        }

        .video-play-button i {
            margin-left: 5px;
        }

        .video-pre-start {
            text-align: center;
            padding: 40px 20px;
            background: linear-gradient(135deg, #f3f4f6 0%, #e5e7eb 100%);
            border-radius: 10px;
            margin-bottom: 25px;
        }

        .video-pre-start h3 {
            color: #2e7d32;
            font-size: 1.2em;
            margin-bottom: 20px;
            font-weight: 600;
        }

        .video-start-btn {
            background: linear-gradient(135deg, #2e7d32 0%, #4caf50 100%);
            color: white;
            border: none;
            padding: 15px 40px;
            border-radius: 10px;
            font-size: 1.1em;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 20px;
            gap: 10px;
        }

        .video-start-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(76, 175, 80, 0.3);
        }

        .video-remaining-count {
            color: #666;
            font-size: 0.95em;
            margin-top: 15px;
        }

        .video-modal {
            display: none;
            position: fixed;
            z-index: 9999;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.9);
        }

        .video-modal.show {
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .video-modal-content {
            position: relative;
            max-width: 90%;
            max-height: 90%;
            background: #000;
            border-radius: 10px;
            overflow: hidden;
        }

        .video-modal video {
            width: 100%;
            height: auto;
            max-height: 80vh;
        }

        .video-modal-close {
            position: absolute;
            top: 15px;
            right: 15px;
            color: white;
            font-size: 2rem;
            cursor: pointer;
            z-index: 10000;
            width: 40px;
            height: 40px;
            background: rgba(0, 0, 0, 0.7);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: all 0.3s ease;
        }

        .video-modal-close:hover {
            background: rgba(255, 255, 255, 0.2);
        }

        .content-with-image {
            display: flex;
            gap: 30px;
            align-items: flex-start;
        }

        .image-section {
            flex: 0 0 50%;
        }

        .answers-section {
            flex: 0 0 50%;
        }

        .answer-option {
            background: white;
            border: 2px solid #e9ecef;
            border-radius: 10px;
            padding: 15px 20px;
            margin-bottom: 15px;
            cursor: pointer;
            transition: all 0.3s ease;
            position: relative;
        }

        .answer-option:hover {
            border-color: #4caf50;
            box-shadow: 0 4px 12px rgba(76, 175, 80, 0.15);
        }

        .answer-option.selected {
            border-color: #2196f3;
            background: linear-gradient(135deg, #e3f2fd 0%, #bbdefb 100%);
        }

        .answer-option.correct {
            border-color: #4caf50;
            background: linear-gradient(135deg, #e8f5e8 0%, #c8e6c9 100%);
        }

        .answer-option.incorrect {
            border-color: #f44336;
            background: linear-gradient(135deg, #ffebee 0%, #ffcdd2 100%);
        }

        .answer-checkbox {
            width: 20px;
            height: 20px;
            border: 2px solid #ccc;
            border-radius: 3px;
            margin-left: 15px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            transition: all 0.3s ease;
        }

        .answer-option.selected .answer-checkbox {
            background: #2196f3;
            border-color: #2196f3;
            color: white;
        }

        .answer-option.correct .answer-checkbox {
            background: #4caf50;
            border-color: #4caf50;
            color: white;
        }

        .answer-option.incorrect .answer-checkbox {
            background: #f44336;
            border-color: #f44336;
            color: white;
        }

        .answer-image-container {
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 10px;
            background: #f8f9fa;
            border-radius: 8px;
            min-height: 60px;
        }

        .answer-image-container img,
        .answer-image {
            max-width: 100%;
            max-height: 80px;
            border-radius: 6px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
        }

        .answer-text {
            padding: 5px 0;
        }

        .numeric-answer-container {
            background: white;
            border: 2px solid #e9ecef;
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 20px;
        }

        .numeric-input-group {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            margin-bottom: 20px;
            flex-wrap: wrap;
        }

        .answer-label {
            font-weight: 600;
            color: #2e7d32;
            margin-left: 10px;
        }

        .numeric-input {
            width: 120px;
            height: 50px;
            font-size: 1.2em;
            text-align: center;
            border: 2px solid #ddd;
            border-radius: 8px;
            background: #f8f9fa;
        }

        .numeric-input:focus {
            border-color: #4caf50;
            outline: none;
            box-shadow: 0 0 0 0.2rem rgba(76, 175, 80, 0.25);
        }

        .unit-label {
            font-weight: 600;
            color: #666;
            margin-right: 10px;
        }

        .numeric-keypad {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 10px;
            max-width: 300px;
            margin: 0 auto;
        }

        .keypad-btn {
            width: 60px;
            height: 60px;
            border: 2px solid #ddd;
            border-radius: 8px;
            background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
            font-size: 1.1em;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.2s ease;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .keypad-btn:hover {
            border-color: #4caf50;
            background: linear-gradient(135deg, #e8f5e8 0%, #c8e6c9 100%);
        }

        .keypad-btn:active {
            transform: scale(0.95);
        }

        .keypad-btn.special {
            background: linear-gradient(135deg, #fff3e0 0%, #ffe0b2 100%);
            color: #f57c00;
        }

        .keypad-btn.delete {
            background: linear-gradient(135deg, #ffebee 0%, #ffcdd2 100%);
            color: #d32f2f;
        }

        .answer-hint {
            text-align: center;
            color: #666;
            font-size: 0.9em;
            margin-top: 15px;
            font-style: italic;
        }

        .numeric-answer-container.correct {
            border-color: #4caf50;
            background: linear-gradient(135deg, #e8f5e8 0%, #c8e6c9 100%);
        }

        .numeric-answer-container.incorrect {
            border-color: #f44336;
            background: linear-gradient(135deg, #ffebee 0%, #ffcdd2 100%);
        }

        .navigation-container {
            background: #2e7d32;
            color: white;
            padding: 20px 30px;
        }

        .question-counter {
            font-size: 1.1em;
            font-weight: 600;
        }

        .navigation-buttons {
            display: flex;
            gap: 3px;
            align-items: center;
        }

        .nav-btn {
            width: 30px;
            height: 30px;
            border: none;
            border-radius: 8px;
            background: rgb(25 69 41 / 65%);
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .nav-btn:hover {
            background: rgba(255, 255, 255, 0.3);
        }

        .nav-btn:disabled {
            opacity: 0.5;
            cursor: not-allowed;
        }

        /* صفحه‌بندی سوالات */
        .question-pagination-container {
            position: relative;
        }

        .question-numbers {
            display: flex;
            gap: 5px;
            flex-wrap: wrap;
            justify-content: center;
            min-height: 80px;
            align-items: center;
        }

        .question-num-btn {
            width: 35px;
            height: 35px;
            border: 1px solid rgba(255, 255, 255, 0.3);
            border-radius: 6px;
            background: rgba(255, 255, 255, 0.1);
            color: white;
            font-size: 0.9em;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .question-num-btn.active {
            background: white;
            color: #2e7d32;
            font-weight: bold;
        }

        .question-num-btn.answered {
            background: rgba(76, 175, 80, 0.8);
            border-color: rgba(76, 175, 80, 0.8);
        }

        .question-num-btn:hover {
            background: rgba(255, 255, 255, 0.2);
        }

        /* دکمه‌های صفحه‌بندی */
        .pagination-controls {
            position: absolute;
            top: 50%;
            transform: translateY(-50%);
            background: rgba(0, 0, 0, 0.3);
            border: none;
            color: white;
            width: 30px;
            height: 30px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .pagination-controls:hover {
            background: rgba(0, 0, 0, 0.5);
        }

        .pagination-controls:disabled {
            opacity: 0.3;
            cursor: not-allowed;
        }

        .pagination-prev {
            right: -40px;
        }

        .pagination-next {
            left: -40px;
        }

        .pagination-indicator {
            text-align: center;
            margin-top: 10px;
            font-size: 0.8em;
            color: rgba(255, 255, 255, 0.7);
        }

        .action-buttons {
            gap: 10px;
        }

        .btn-abort {
            background: #f44336;
            border: none;
            color: white;
            padding: 10px 20px;
            border-radius: 8px;
            font-weight: 600;
        }

        .btn-confirm {
            background: #4caf50;
            border: none;
            color: white;
            padding: 10px 20px;
            border-radius: 8px;
            font-weight: 600;
        }

        .btn-confirm:disabled {
            background: #cccccc;
            cursor: not-allowed;
        }

        .question-info {
            background: linear-gradient(135deg, #fff3e0 0%, #ffe0b2 100%);
            border-radius: 10px;
            padding: 20px;
            margin-top: 20px;
            border-right: 4px solid #ff9800;
            display: none;
        }

        .question-info.show {
            display: block;
            animation: slideDown 0.3s ease;
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

        .progress-container {
            background: rgba(255, 255, 255, 0.1);
            border-radius: 10px;
            padding: 10px;
            margin-bottom: 15px;
        }

        .progress {
            height: 8px;
            border-radius: 4px;
            background: rgba(255, 255, 255, 0.3);
        }

        .progress-bar {
            background: white;
            transition: width 0.3s ease;
        }

        /* Responsive Pagination */
        @media (max-width: 576px) {

            /* موبایل: 1 دکمه در هر صفحه */
            .question-numbers {
                justify-content: center;
                min-height: 60px;
            }
        }

        @media (min-width: 577px) and (max-width: 992px) {

            /* تبلت: 3 دکمه در هر صفحه */
            .question-numbers {
                justify-content: center;
                min-height: 60px;
            }
        }

        @media (min-width: 993px) {

            /* دسکتاپ: 5 دکمه در هر صفحه */
            .question-numbers {
                justify-content: center;
                min-height: 60px;
            }
        }

        @media (max-width: 768px) {
            .question-content {
                padding: 20px;
            }

            .navigation-container {
                padding: 15px 20px;
            }

            .numeric-keypad {
                max-width: 250px;
            }

            .keypad-btn {
                width: 50px;
                height: 50px;
                font-size: 1em;
            }

            .content-with-image {
                flex-direction: column;
                gap: 20px;
            }

            .image-section,
            .answers-section {
                flex: none;
            }

            .pagination-prev {
                right: -35px;
            }

            .pagination-next {
                left: -35px;
            }
        }
    </style>
</head>

<body>
    <div class="container-fluid">
        <div class="test-container">
            <!-- Header -->
            <div class="test-header d-flex justify-content-between align-items-center">
                <div class="d-flex align-items-center">
                    <span class="question-number" id="questionNumber">1.1.01-001</span>
                    <i class="bi bi-info-circle ms-2"></i>
                    <span id="modeIndicator" class="badge bg-light text-dark ms-2">تمرین</span>
                </div>
                <div class="points-badge">
                    امتیاز: <span id="questionPoints">4</span>
                    <i class="bi bi-x-lg ms-2" style="cursor: pointer;" title="بستن" onclick="closeTest()"></i>
                </div>
            </div>

            <!-- Progress Bar -->
            <div class="progress-container">
                <div class="progress">
                    <div class="progress-bar" role="progressbar" id="progressBar" style="width: 0%"></div>
                </div>
            </div>

            <!-- Question Content -->
            <div class="question-content">
                <h2 class="question-title" id="questionTitle">
                    Was versteht man unter defensivem Fahren?
                </h2>

                <!-- Video Pre-Start Interface -->
                <div id="videoPreStart" class="video-pre-start" style="display: none;">
                    <h3>Bitte starten Sie den Film, um sich mit der Situation vertraut zu machen.</h3>
                    <button class="video-start-btn" onclick="startVideoQuestion()">
                        <i class="bi bi-play-circle"></i>
                        Video starten
                    </button>
                    <div class="video-remaining-count">
                        Sie können das Video insgesamt <b><span id="videoRemainingCount">5</span></b> Mal ansehen.
                    </div>
                </div>

                <!-- Content with Image Layout -->
                <div id="contentContainer">
                    <!-- Multiple Choice Answers -->
                    <div class="answers-section" id="answersSection">
                        <div class="answer-option" data-answer-index="0" onclick="selectAnswer(0, 0)">
                            <div class="d-flex align-items-center">
                                <div class="answer-checkbox">
                                    <i class="bi bi-check" style="display: none;"></i>
                                </div>
                                <div class="flex-grow-1">
                                    <div class="answer-text">Mit Fehlern anderer rechnen</div>
                                </div>
                            </div>
                        </div>
                        <div class="answer-option" data-answer-index="1" onclick="selectAnswer(0, 1)">
                            <div class="d-flex align-items-center">
                                <div class="answer-checkbox">
                                    <i class="bi bi-check" style="display: none;"></i>
                                </div>
                                <div class="flex-grow-1">
                                    <div class="answer-text">Vorsorgich an jeder Kreuzung anhalten</div>
                                </div>
                            </div>
                        </div>
                        <div class="answer-option" data-answer-index="2" onclick="selectAnswer(0, 2)">
                            <div class="d-flex align-items-center">
                                <div class="answer-checkbox">
                                    <i class="bi bi-check" style="display: none;"></i>
                                </div>
                                <div class="flex-grow-1">
                                    <div class="answer-text">Nicht auf dem eigenen Recht bestehen</div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Numeric Input Answer -->
                    <div class="numeric-answer-container" id="numericAnswerContainer" style="display: none;">
                        <div class="numeric-input-group">
                            <span class="answer-label" id="answerLabel">پاسخ:</span>
                            <input type="text" class="numeric-input" id="numericInput" readonly placeholder="0">
                            <span class="unit-label" id="unitLabel">km/h</span>
                        </div>

                        <div class="numeric-keypad" id="numericKeypad">
                            <button class="keypad-btn" onclick="inputNumber('1')">1</button>
                            <button class="keypad-btn" onclick="inputNumber('2')">2</button>
                            <button class="keypad-btn" onclick="inputNumber('3')">3</button>
                            <button class="keypad-btn" onclick="inputNumber('4')">4</button>
                            <button class="keypad-btn" onclick="inputNumber('5')">5</button>
                            <button class="keypad-btn" onclick="inputNumber('6')">6</button>
                            <button class="keypad-btn" onclick="inputNumber('7')">7</button>
                            <button class="keypad-btn" onclick="inputNumber('8')">8</button>
                            <button class="keypad-btn" onclick="inputNumber('9')">9</button>
                            <button class="keypad-btn special" onclick="inputNumber(',')">،</button>
                            <button class="keypad-btn" onclick="inputNumber('0')">0</button>
                            <button class="keypad-btn delete" onclick="deleteNumber()">
                                <i class="bi bi-backspace"></i>
                            </button>
                            <button class="keypad-btn special" onclick="clearInput()" style="grid-column: span 3;">
                                پاک کردن
                            </button>
                        </div>

                        <div class="answer-hint" id="answerHint">
                            <!-- راهنمایی پاسخ اینجا نمایش داده می‌شود -->
                        </div>
                    </div>
                </div>

                <!-- Question Info -->
                <div class="question-info" id="questionInfo">
                    <h5><i class="bi bi-info-circle me-2"></i>توضیحات</h5>
                    <div id="questionInfoContent"></div>
                </div>
            </div>

            <!-- Navigation -->
            <div class="navigation-container">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <div class="question-counter">
                        <span id="currentQuestionIndex">1</span> از <span id="totalQuestions">170</span> سوال
                    </div>
                    <div class="action-buttons d-flex">
                        <button class="btn-abort" onclick="closeTest()">
                            خروج
                        </button>
                        <button class="btn-confirm ms-2" id="confirmBtn" onclick="confirmAnswer()">
                            تایید پاسخ
                        </button>
                    </div>
                </div>

                <div class="d-flex justify-content-between align-items-center">
                    <!-- Navigation Controls -->
                    <div class="navigation-buttons">
                        <button class="nav-btn" onclick="firstQuestion()" title="اولین سوال" id="firstBtn">
                            <i class="bi bi-chevron-bar-right"></i>
                        </button>
                        <button class="nav-btn" onclick="prevPage()" title="صفحه قبلی" id="prevBtn">
                            <i class="bi bi-chevron-double-right"></i>
                        </button>
                        <button class="nav-btn" onclick="prevQuestion()" title="سوال قبلی" id="prevBtn">
                            <i class="bi bi-chevron-right"></i>
                        </button>
                    </div>

                    <!-- Question Numbers with Pagination -->
                    <div class="question-pagination-container">

                        <div class="question-numbers" id="questionNumbers">
                            <!-- شماره سوالات اینجا بارگذاری می‌شوند -->
                        </div>
                        <div class="pagination-indicator" id="paginationIndicator">
                            صفحه 1 از 34
                        </div>
                    </div>

                    <!-- Forward Navigation -->
                    <div class="navigation-buttons">
                        <button class="nav-btn" onclick="nextQuestion()" title="سوال بعدی" id="nextBtn">
                            <i class="bi bi-chevron-left"></i>
                        </button>
                        <button class="nav-btn" onclick="nextPage()" title="سوال بعدی" id="nextBtn">
                            <i class="bi bi-chevron-double-left"></i>
                        </button>
                        <button class="nav-btn" onclick="lastQuestion()" title="آخرین سوال" id="lastBtn">
                            <i class="bi bi-chevron-bar-left"></i>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Video Modal -->
    <div class="video-modal" id="videoModal">
        <div class="video-modal-content">
            <span class="video-modal-close" id="videoModalClose">
                <i class="bi bi-x"></i>
            </span>
            <video id="modalVideo" controls>
                <source src="" type="video/mp4">
                مرورگر شما از پخش ویدیو پشتیبانی نمی‌کند.
            </video>
        </div>
    </div>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.2/js/bootstrap.bundle.min.js"></script>

    <script>
        // Global variables
        let questions = [];
        let currentQuestionIndex = 0;
        let mode = 'practice';
        let userAnswers = {};
        let questionStates = {};
        let questionAnswerOrder = {};

        // متغیرهای صفحه‌بندی
        let currentPage = 0;
        let questionsPerPage = 5; // پیش‌فرض برای دسکتاپ
        let totalPages = 0;

        // Image and Video base URLs
        const IMAGE_BASE_URL = 'https://t24.theorie24.de/2025-01-v400/data/img/images/';
        const VIDEO_BASE_URL = 'https://www.theorie24.de/live_images/_current_ws_2024-10-01_2025-04-01/videos/';
        const ANSWER_IMAGE_BASE_URL = 'https://t24.theorie24.de/2025-01-v400/data/img/etc/de/';

        // Video state tracking
        let videoStates = {};

        // تابع تعیین تعداد سوالات در هر صفحه بر اساس اندازه صفحه
        function getQuestionsPerPage() {
            const width = window.innerWidth;
            if (width <= 576) {
                return 1; // موبایل: 1 دکمه
            } else if (width <= 992) {
                return 3; // تبلت: 3 دکمه
            } else {
                return 10; // دسکتاپ: 5 دکمه
            }
        }

        // تابع محاسبه صفحه‌بندی
        function calculatePagination() {
            questionsPerPage = getQuestionsPerPage();
            totalPages = Math.ceil(questions.length / questionsPerPage);

            // اطمینان از اینکه صفحه فعلی معتبر است
            if (currentPage >= totalPages) {
                currentPage = totalPages - 1;
            }
            if (currentPage < 0) {
                currentPage = 0;
            }
        }

        // تابع نمایش دکمه‌های سوالات برای صفحه فعلی
        function displayCurrentPage() {
            const questionNumbers = document.getElementById('questionNumbers');
            questionNumbers.innerHTML = '';

            const startIndex = currentPage * questionsPerPage;
            const endIndex = Math.min(startIndex + questionsPerPage, questions.length);

            for (let i = startIndex; i < endIndex; i++) {
                const btn = document.createElement('button');
                btn.className = 'question-num-btn';
                btn.textContent = i + 1;
                btn.onclick = () => goToQuestion(i);
                btn.id = `questionBtn${i}`;

                // اعمال کلاس‌های مناسب
                if (i === currentQuestionIndex) {
                    btn.classList.add('active');
                } else if (questionStates[i] && questionStates[i].answered) {
                    btn.classList.add('answered');
                }

                questionNumbers.appendChild(btn);
            }

            // بروزرسانی نشانگر صفحه
            updatePaginationIndicator();

            // بروزرسانی دکمه‌های صفحه‌بندی
            updatePaginationButtons();
        }

        // تابع بروزرسانی نشانگر صفحه
        function updatePaginationIndicator() {
            const indicator = document.getElementById('paginationIndicator');
            indicator.textContent = `صفحه ${currentPage + 1} از ${totalPages}`;
        }

        // تابع بروزرسانی دکمه‌های صفحه‌بندی
        function updatePaginationButtons() {
            const prevBtn = document.getElementById('paginationPrev');
            const nextBtn = document.getElementById('paginationNext');

            prevBtn.disabled = currentPage === 0;
            nextBtn.disabled = currentPage === totalPages - 1;
        }

        // تابع رفتن به صفحه قبل
        function prevPage() {
            if (currentPage > 0) {
                currentPage--;
                displayCurrentPage();
            }
        }

        // تابع رفتن به صفحه بعد
        function nextPage() {
            if (currentPage < totalPages - 1) {
                currentPage++;
                displayCurrentPage();
            }
        }

        // تابع پیدا کردن صفحه سوال
        function findQuestionPage(questionIndex) {
            return Math.floor(questionIndex / questionsPerPage);
        }

        // Initialize test with sample data
        function initializeTest() {
            <?php
            // دریافت داده‌های ارسال شده از فرم
            if (isset($_POST['session_data'])) {
                $sessionData = json_decode($_POST['session_data'], true);
                echo "questions = " . json_encode($sessionData['questions'], JSON_UNESCAPED_UNICODE) . ";\n";
                echo "mode = '" . $sessionData['mode'] . "';\n";
            } elseif (isset($_POST['questions']) && isset($_POST['mode'])) {
                // روش جایگزین
                echo "questions = " . $_POST['questions'] . ";\n";
                echo "mode = '" . $_POST['mode'] . "';\n";
            } else {
                // داده‌های نمونه برای تست
            
            }
            ?>

            // Initialize user answers, question states, and video states
            questions.forEach((question, index) => {
                userAnswers[index] = question.asw_type_1 === "2" ? "" : [];
                questionStates[index] = {
                    answered: false,
                    confirmed: false
                };

                videoStates[index] = {
                    hasPlayed: false,
                    showingEndImage: false,
                    remainingViews: 5,
                    videoStarted: false
                };

                // For multiple choice questions, create shuffled order
                if (question.asw_type_1 === "1" && question.answers) {
                    questionAnswerOrder[index] = question.answers.map((answer, answerIndex) => ({
                        ...answer,
                        originalIndex: answerIndex
                    }));

                    // Shuffle only once per question to maintain consistency
                    for (let i = questionAnswerOrder[index].length - 1; i > 0; i--) {
                        const j = Math.floor(Math.random() * (i + 1));
                        [questionAnswerOrder[index][i], questionAnswerOrder[index][j]] =
                            [questionAnswerOrder[index][j], questionAnswerOrder[index][i]];
                    }
                }
            });

            // Set mode indicator
            document.getElementById('modeIndicator').textContent =
                mode === 'practice' ? 'تمرین' : 'مرور';

            if (mode === 'review') {
                document.getElementById('modeIndicator').classList.add('bg-info');
                document.getElementById('confirmBtn').style.display = 'none';
            }

            // محاسبه صفحه‌بندی و نمایش صفحه اول
            calculatePagination();
            displayCurrentPage();

            // Load first question
            loadQuestion(0);

            // Update navigation
            updateNavigation();

            document.getElementById('totalQuestions').textContent = questions.length;

            console.log('Test initialized with', questions.length, 'questions in', mode, 'mode');
        }

        // Helper function to check if answer text is an image filename
        function isAnswerImage(text) {
            if (!text) return false;

            // Check if it's an HTML img tag with %IMG_ETC% placeholder
            if (text.includes('<img') && text.includes('%IMG_ETC%')) {
                return true;
            }

            // Check if it's a direct image filename
            const imageExtensions = ['.png', '.jpg', '.jpeg', '.gif', '.svg'];
            const extension = text.toLowerCase().substring(text.lastIndexOf('.'));
            return imageExtensions.includes(extension);
        }

        // Helper function to process answer image content
        function processAnswerImageContent(text) {
            if (text.includes('<img') && text.includes('%IMG_ETC%')) {
                // Replace the placeholder with actual URL
                return text.replace(/%IMG_ETC%/g, ANSWER_IMAGE_BASE_URL);
            } else if (isAnswerImage(text)) {
                // Create img tag for direct filename
                const imageUrl = ANSWER_IMAGE_BASE_URL + text;
                return `<img src="${imageUrl}" alt="تصویر پاسخ" class="answer-image" onerror="this.parentElement.innerHTML='<div class=&quot;text-muted&quot;><i class=&quot;bi bi-image&quot;></i> خطا در بارگذاری تصویر: ${text}</div>'">`;
            }
            return text;
        }

        // Load specific question
        function loadQuestion(index) {
            if (index < 0 || index >= questions.length) return;

            currentQuestionIndex = index;
            const question = questions[index];

            // Update question info
            document.getElementById('questionNumber').textContent = question.number;
            document.getElementById('questionPoints').textContent = question.points;
            document.getElementById('currentQuestionIndex').textContent = index + 1;

            // Update progress bar
            const progress = ((index + 1) / questions.length) * 100;
            document.getElementById('progressBar').style.width = progress + '%';

            // بررسی اینکه آیا نیاز به تغییر صفحه هست
            const questionPage = findQuestionPage(index);
            if (questionPage !== currentPage) {
                currentPage = questionPage;
                displayCurrentPage();
            } else {
                // فقط بروزرسانی دکمه‌های فعلی
                updateQuestionButtons();
            }

            // Check if this is a video question that hasn't been started yet
            const hasVideo = question.picture && isVideoFile(question.picture);
            const videoNotStarted = hasVideo && !videoStates[index].videoStarted && mode !== 'review';

            if (videoNotStarted) {
                showVideoPreStart(question, index);
            } else {
                showQuestionContent(question, index);
            }

            // Update navigation buttons
            updateNavigation();

            console.log('Loaded question', index + 1, ':', question.text, 'Type:', question.asw_type_1, 'Has video:', hasVideo, 'Video started:', videoStates[index].videoStarted);
        }

        // Show video pre-start interface
        function showVideoPreStart(question, questionIndex) {
            // Hide question title and content
            document.getElementById('questionTitle').style.display = 'none';
            document.getElementById('contentContainer').style.display = 'none';
            document.getElementById('questionInfo').classList.remove('show');

            // Show video pre-start interface
            const videoPreStart = document.getElementById('videoPreStart');
            videoPreStart.style.display = 'block';

            // Update remaining count
            document.getElementById('videoRemainingCount').textContent = videoStates[questionIndex].remainingViews;

            // Hide confirm button
            document.getElementById('confirmBtn').style.display = 'none';
        }

        // Show actual question content
        function showQuestionContent(question, questionIndex) {
            // Hide video pre-start interface
            document.getElementById('videoPreStart').style.display = 'none';

            // Show confirm button if in practice mode
            if (mode === 'practice') {
                document.getElementById('confirmBtn').style.display = 'inline-block';
            }

            // For video questions, modify layout to show question text above/below video
            const hasVideo = question.picture && isVideoFile(question.picture);
            if (hasVideo) {
                // Show question title above content
                document.getElementById('questionTitle').textContent = question.text;
                document.getElementById('questionTitle').style.display = 'block';
                document.getElementById('contentContainer').style.display = 'block';

                // Setup video-specific layout (single column)
                setupVideoQuestionLayout(question, questionIndex);
            } else {
                // Regular layout for non-video questions
                document.getElementById('questionTitle').textContent = question.text;
                document.getElementById('questionTitle').style.display = 'block';
                document.getElementById('contentContainer').style.display = 'block';

                // Setup content layout based on whether question has image
                setupContentLayout(question, questionIndex);
            }

            // Load appropriate answer interface based on question type
            if (question.asw_type_1 === "2") {
                loadNumericAnswer(question, questionIndex);
            } else {
                loadMultipleChoiceAnswers(question, questionIndex);
            }

            // Hide/show question info
            const questionInfo = document.getElementById('questionInfo');
            const questionInfoContent = document.getElementById('questionInfoContent');

            if (mode === 'review' && question.info) {
                questionInfoContent.innerHTML = question.info;
                questionInfo.classList.add('show');
            } else {
                questionInfo.classList.remove('show');
            }
        }

        // Start video question - triggered by "Video starten" button
        function startVideoQuestion() {
            const questionIndex = currentQuestionIndex;
            const question = questions[questionIndex];

            if (!question.picture || !isVideoFile(question.picture)) {
                console.error('No video found for question', questionIndex);
                return;
            }

            // Mark video as started
            videoStates[questionIndex].videoStarted = true;

            // Open video modal immediately
            const questionCode = getQuestionCode(question.picture);
            const videoUrl = VIDEO_BASE_URL + question.picture;
            openVideoModal(videoUrl, questionIndex);

            // Show question content after modal is opened
            showQuestionContent(question, questionIndex);
        }

        // Helper function to check if file is a video
        function isVideoFile(filename) {
            if (!filename) return false;
            const videoExtensions = ['.mp4', '.m4v', '.avi', '.mov', '.wmv', '.flv', '.webm', '.ogv'];
            const extension = filename.toLowerCase().substring(filename.lastIndexOf('.'));
            return videoExtensions.includes(extension);
        }

        // Helper function to get question code from picture filename
        function getQuestionCode(filename) {
            // Remove extension first
            const nameWithoutExt = filename.substring(0, filename.lastIndexOf('.'));
            // For videos, we need to keep the full name including -M part
            // The -M is part of the video filename, not a suffix to remove
            return nameWithoutExt;
        }

        // Setup video question layout (single column with text above video)
        function setupVideoQuestionLayout(question, questionIndex) {
            const contentContainer = document.getElementById('contentContainer');
            const answersSection = document.getElementById('answersSection');

            // Remove any existing image or video sections
            const existingImageSection = document.getElementById('imageSection');
            if (existingImageSection) {
                existingImageSection.remove();
            }

            const existingVideoSection = document.getElementById('videoSection');
            if (existingVideoSection) {
                existingVideoSection.remove();
            }

            // Remove content-with-image class for single column layout
            contentContainer.className = '';

            // Create new video section at the top
            const videoSection = document.createElement('div');
            videoSection.id = 'videoSection';
            videoSection.className = 'video-section mb-4';

            // Insert video section before answers
            contentContainer.insertBefore(videoSection, answersSection);

            // Setup video content
            setupVideoContent(videoSection, question, questionIndex);
        }

        // Setup content layout based on whether question has image/video
        function setupContentLayout(question, questionIndex) {
            const contentContainer = document.getElementById('contentContainer');
            const answersSection = document.getElementById('answersSection');
            const numericContainer = document.getElementById('numericAnswerContainer');

            // Clean up any existing sections first
            const existingImageSection = document.getElementById('imageSection');
            if (existingImageSection) {
                existingImageSection.remove();
            }

            const existingVideoSection = document.getElementById('videoSection');
            if (existingVideoSection) {
                existingVideoSection.remove();
            }

            // If question has picture/video, setup side-by-side layout
            if (question.picture && question.picture.trim() !== '') {
                // Create image section if it doesn't exist
                let imageSection = document.createElement('div');
                imageSection.id = 'imageSection';
                imageSection.className = 'image-section';
                contentContainer.insertBefore(imageSection, answersSection);

                // Add content-with-image class for desktop layout
                contentContainer.className = 'content-with-image';

                // Check if it's a video or image
                if (isVideoFile(question.picture)) {
                    setupVideoContent(imageSection, question, questionIndex);
                } else {
                    setupImageContent(imageSection, question);
                }

                console.log('Media loaded:', question.picture, 'Is video:', isVideoFile(question.picture));
            } else {
                // Remove content-with-image class
                contentContainer.className = '';
            }
        }

        // Setup regular image content
        function setupImageContent(imageSection, question) {
            const imageUrl = IMAGE_BASE_URL + question.picture;
            imageSection.innerHTML = `
                <div class="media-container">
                    <img src="${imageUrl}" alt="تصویر سوال" onerror="this.parentElement.innerHTML='<div class=&quot;media-placeholder&quot;><i class=&quot;bi bi-image&quot;></i><div>خطا در بارگذاری تصویر</div></div>'">
                </div>
            `;
        }

        // Setup video content with preview image and play button
        function setupVideoContent(videoSection, question, questionIndex) {
            const questionCode = getQuestionCode(question.picture);
            const videoState = videoStates[questionIndex];

            let previewImageUrl;
            if (videoState.hasPlayed && videoState.showingEndImage) {
                // Show end image after video has been played and modal closed
                previewImageUrl = IMAGE_BASE_URL + questionCode + '_ende.jpg';
            } else {
                // Show start image initially
                previewImageUrl = IMAGE_BASE_URL + questionCode + '_anfang.jpg';
            }

            const videoUrl = VIDEO_BASE_URL + question.picture;
            const remainingViews = videoState.remainingViews;

            // Only show clickable video if there are remaining views
            if (remainingViews > 0) {
                videoSection.innerHTML = `
                    <div class="media-container">
                        <div class="video-container" onclick="openVideoModal('${videoUrl}', ${questionIndex})">
                            <img src="${previewImageUrl}" alt="پیش‌نمایش ویدیو" class="video-preview" 
                                 onerror="this.parentElement.parentElement.innerHTML='<div class=&quot;media-placeholder&quot;><i class=&quot;bi bi-play-circle&quot;></i><div>خطا در بارگذاری ویدیو</div></div>'">
                            <div class="video-play-button">
                                <i class="bi bi-play-fill"></i>
                            </div>
                        </div>
                        <div class="text-center mt-2">
                            <small class="text-muted">باقیمانده: ${remainingViews} بار</small>
                        </div>
                    </div>
                `;
            } else {
                // No more views available - show end image only
                videoSection.innerHTML = `
                    <div class="media-container">
                        <img src="${previewImageUrl}" alt="تصویر پایان ویدیو" 
                             onerror="this.parentElement.innerHTML='<div class=&quot;media-placeholder&quot;><i class=&quot;bi bi-film&quot;></i><div>ویدیو تمام شد</div></div>'">
                        <div class="text-center mt-2">
                            <small class="text-muted">تعداد مشاهده تمام شد</small>
                        </div>
                    </div>
                `;
            }
        }

        // Open video modal
        function openVideoModal(videoUrl, questionIndex) {
            const videoState = videoStates[questionIndex];

            // Check if views are exhausted
            if (videoState.remainingViews <= 0) {
                alert('تعداد مشاهده ویدیو تمام شده است');
                return;
            }

            const modal = document.getElementById('videoModal');
            const video = document.getElementById('modalVideo');

            // Set video source
            video.src = videoUrl;

            // Show modal
            modal.classList.add('show');

            // Play video
            video.play().catch(e => {
                console.error('Error playing video:', e);
                alert('خطا در پخش ویدیو');
            });

            // Decrease remaining views
            videoState.remainingViews--;
            videoState.hasPlayed = true;

            console.log('Video opened. Remaining views:', videoState.remainingViews);
        }

        // Close video modal
        function closeVideoModal() {
            const modal = document.getElementById('videoModal');
            const video = document.getElementById('modalVideo');

            // Pause and reset video
            video.pause();
            video.currentTime = 0;
            video.src = '';

            // Hide modal
            modal.classList.remove('show');

            // Find current question and update if it has video
            const currentQuestion = questions[currentQuestionIndex];
            if (currentQuestion.picture && isVideoFile(currentQuestion.picture)) {
                videoStates[currentQuestionIndex].showingEndImage = true;
                // Reload the video section to show updated view count and end image
                const videoSection = document.getElementById('videoSection');
                if (videoSection) {
                    setupVideoContent(videoSection, currentQuestion, currentQuestionIndex);
                }
            }
        }

        // Load numeric input answer
        function loadNumericAnswer(question, questionIndex) {
            // Hide multiple choice section
            document.getElementById('answersSection').style.display = 'none';

            // Show numeric input section
            const numericContainer = document.getElementById('numericAnswerContainer');
            numericContainer.style.display = 'block';

            // Set up the answer components - use asw_pretext for label
            document.getElementById('answerLabel').textContent = question.asw_pretext || 'پاسخ:';
            document.getElementById('answerHint').textContent = question.asw_hint_1 || '';

            // Extract unit from hint if present
            const hint = question.asw_hint_1 || '';
            document.getElementById('unitLabel').textContent = hint;

            const numericInput = document.getElementById('numericInput');
            const keypadBtns = document.querySelectorAll('.keypad-btn');

            // Reset container styling
            numericContainer.classList.remove('correct', 'incorrect');

            // Set input value and state based on mode and confirmation status
            if (mode === 'review') {
                // In review mode, show the correct answer
                const correctAnswer = question.answers[0].text.replace(/[^\d,]/g, '');
                numericInput.value = correctAnswer;
                numericInput.disabled = true;
                numericContainer.classList.add('correct');

                // Disable keypad
                keypadBtns.forEach(btn => btn.disabled = true);
            } else if (questionStates[questionIndex].confirmed) {
                // In practice mode but already confirmed
                const userAnswer = userAnswers[questionIndex];
                const correctAnswer = question.answers[0].text.replace(/[^\d,]/g, '');

                numericInput.value = userAnswer;
                numericInput.disabled = true;

                // Check if answer is correct
                if (userAnswer === correctAnswer) {
                    numericContainer.classList.add('correct');
                } else {
                    numericContainer.classList.add('incorrect');
                }

                // Disable keypad
                keypadBtns.forEach(btn => btn.disabled = true);
            } else {
                // In practice mode, not confirmed yet
                numericInput.value = userAnswers[questionIndex] || '';
                numericInput.disabled = false;

                // Enable keypad
                keypadBtns.forEach(btn => btn.disabled = false);
            }

            // Update confirm button
            updateConfirmButton();
        }

        // Load multiple choice answers
        function loadMultipleChoiceAnswers(question, questionIndex) {
            // Hide numeric input section
            document.getElementById('numericAnswerContainer').style.display = 'none';

            // Show multiple choice section
            const answersSection = document.getElementById('answersSection');
            answersSection.style.display = 'block';
            answersSection.innerHTML = '';

            // Check if this is a multiple choice question and has answers
            if (question.asw_type_1 !== "1" || !question.answers || !questionAnswerOrder[questionIndex]) {
                console.error('Invalid multiple choice question data for question', questionIndex);
                return;
            }

            // Use pre-shuffled order for this question to maintain consistency
            const shuffledAnswers = questionAnswerOrder[questionIndex];

            shuffledAnswers.forEach((answer, displayIndex) => {
                const answerDiv = document.createElement('div');
                answerDiv.className = 'answer-option';
                answerDiv.setAttribute('data-answer-index', answer.originalIndex);
                answerDiv.onclick = () => selectAnswer(questionIndex, answer.originalIndex);

                // در حالت review، پاسخ‌های صحیح را نمایش بده
                if (mode === 'review') {
                    if (answer.isCorrect) {
                        answerDiv.classList.add('correct');
                    }
                }

                // بررسی پاسخ قبلی کاربر
                const userAnswer = userAnswers[questionIndex];
                if (userAnswer && userAnswer.includes(answer.originalIndex)) {
                    answerDiv.classList.add('selected');

                    // اگر سوال تایید شده، وضعیت نهایی را نمایش بده
                    if (questionStates[questionIndex].confirmed) {
                        if (answer.isCorrect) {
                            answerDiv.classList.remove('selected');
                            answerDiv.classList.add('correct');
                        } else {
                            answerDiv.classList.remove('selected');
                            answerDiv.classList.add('incorrect');
                        }
                    }
                }

                // Check if answer is an image or text
                let answerContent;
                if (isAnswerImage(answer.text)) {
                    // Process image content (either HTML with placeholder or direct filename)
                    answerContent = `
                        <div class="answer-image-container">
                            ${processAnswerImageContent(answer.text)}
                        </div>
                    `;
                } else {
                    // Display text for text-based answers
                    answerContent = `<div class="answer-text">${answer.text}</div>`;
                }

                answerDiv.innerHTML = `
                    <div class="d-flex align-items-center">
                        <div class="answer-checkbox">
                            <i class="bi bi-check" style="display: none;"></i>
                        </div>
                        <div class="flex-grow-1">
                            ${answerContent}
                        </div>
                    </div>
                `;

                // نمایش تیک در حالت‌های مختلف
                const checkIcon = answerDiv.querySelector('.bi-check');
                if (mode === 'review' && answer.isCorrect) {
                    checkIcon.style.display = 'inline';
                } else if (userAnswer && userAnswer.includes(answer.originalIndex)) {
                    checkIcon.style.display = 'inline';
                }

                answersSection.appendChild(answerDiv);
            });

            // Update confirm button
            updateConfirmButton();
        }

        // Numeric input functions
        function inputNumber(digit) {
            if (mode === 'review' || questionStates[currentQuestionIndex].confirmed) return;

            const input = document.getElementById('numericInput');
            if (digit === ',' && input.value.includes(',')) return; // Only one comma allowed

            input.value += digit;

            // Update user answer
            userAnswers[currentQuestionIndex] = input.value;
            questionStates[currentQuestionIndex].answered = input.value.length > 0;

            updateConfirmButton();
            updateQuestionButtons();
        }

        function deleteNumber() {
            if (mode === 'review' || questionStates[currentQuestionIndex].confirmed) return;

            const input = document.getElementById('numericInput');
            input.value = input.value.slice(0, -1);

            // Update user answer
            userAnswers[currentQuestionIndex] = input.value;
            questionStates[currentQuestionIndex].answered = input.value.length > 0;

            updateConfirmButton();
            updateQuestionButtons();
        }

        function clearInput() {
            if (mode === 'review' || questionStates[currentQuestionIndex].confirmed) return;

            const input = document.getElementById('numericInput');
            input.value = '';

            // Update user answer
            userAnswers[currentQuestionIndex] = '';
            questionStates[currentQuestionIndex].answered = false;

            updateConfirmButton();
            updateQuestionButtons();
        }

        // Handle answer selection for multiple choice
        function selectAnswer(questionIndex, answerIndex) {
            // در حالت review، انتخاب پاسخ غیرفعال است
            if (mode === 'review') return;

            // اگر سوال تایید شده، انتخاب غیرفعال است
            if (questionStates[questionIndex].confirmed) return;

            if (!userAnswers[questionIndex]) {
                userAnswers[questionIndex] = [];
            }

            const answerElement = document.querySelector(`[data-answer-index="${answerIndex}"]`);
            const checkIcon = answerElement.querySelector('.bi-check');

            // Toggle selection
            if (userAnswers[questionIndex].includes(answerIndex)) {
                // Remove selection
                const index = userAnswers[questionIndex].indexOf(answerIndex);
                userAnswers[questionIndex].splice(index, 1);
                answerElement.classList.remove('selected');
                checkIcon.style.display = 'none';
            } else {
                // Add selection
                userAnswers[questionIndex].push(answerIndex);
                answerElement.classList.add('selected');
                checkIcon.style.display = 'inline';
            }

            // Mark as answered
            questionStates[questionIndex].answered = userAnswers[questionIndex].length > 0;

            // Update confirm button
            updateConfirmButton();

            // Update question button
            updateQuestionButtons();

            console.log('Question', questionIndex + 1, 'answers:', userAnswers[questionIndex]);
        }

        // Confirm answer
        function confirmAnswer() {
            const questionIndex = currentQuestionIndex;
            const question = questions[questionIndex];

            if (question.asw_type_1 === "2") {
                confirmNumericAnswer(question, questionIndex);
            } else {
                confirmMultipleChoiceAnswer(question, questionIndex);
            }
        }

        // Confirm numeric answer
        function confirmNumericAnswer(question, questionIndex) {
            const userAnswer = userAnswers[questionIndex] || '';

            if (userAnswer.trim() === '') {
                alert('لطفاً یک عدد وارد کنید');
                return;
            }

            // Mark as confirmed
            questionStates[questionIndex].confirmed = true;

            // Get correct answer (remove non-digit/comma characters)
            const correctAnswer = question.answers[0].text.replace(/[^\d,]/g, '');

            // Check if answer is correct
            const isCorrect = userAnswer === correctAnswer;

            const numericContainer = document.getElementById('numericAnswerContainer');
            const numericInput = document.getElementById('numericInput');
            const keypadBtns = document.querySelectorAll('.keypad-btn');

            // Apply styling based on correctness
            if (isCorrect) {
                numericContainer.classList.add('correct');
                console.log('پاسخ عددی صحیح است');
            } else {
                numericContainer.classList.add('incorrect');
                console.log('پاسخ عددی غلط است. صحیح:', correctAnswer, 'کاربر:', userAnswer);
            }

            // Disable input and keypad
            numericInput.disabled = true;
            keypadBtns.forEach(btn => btn.disabled = true);

            // Show explanation if available
            if (question.info) {
                const questionInfo = document.getElementById('questionInfo');
                const questionInfoContent = document.getElementById('questionInfoContent');
                questionInfoContent.innerHTML = question.info;
                questionInfo.classList.add('show');
            }

            // Update confirm button
            updateConfirmButton();

            // Update question button
            updateQuestionButtons();
        }

        // Confirm multiple choice answer
        function confirmMultipleChoiceAnswer(question, questionIndex) {
            const userAnswer = userAnswers[questionIndex] || [];
            questionStates[questionIndex].confirmed = true;
            questionStates[questionIndex].answered = true;

            // Show correct/incorrect states
            const answerElements = document.querySelectorAll('.answer-option');
            answerElements.forEach((element, index) => {
                const answerIndex = parseInt(element.getAttribute('data-answer-index'));
                const answer = question.answers[answerIndex];
                const wasSelected = userAnswer.includes(answerIndex);
                const isCorrect = answer.isCorrect;

                // Remove previous states
                element.classList.remove('selected', 'correct', 'incorrect');

                // Apply new state based on 4 scenarios
                if (isCorrect && wasSelected) {
                    // پاسخ صحیح که کاربر انتخاب کرده - سبز
                    element.classList.add('correct');
                } else if (isCorrect && !wasSelected) {
                    // پاسخ صحیح که کاربر انتخاب نکرده - قرمز (باید تیک می‌زد)
                    element.classList.add('incorrect');
                } else if (!isCorrect && wasSelected) {
                    // پاسخ غلط که کاربر انتخاب کرده - قرمز (نباید تیک می‌زد)
                    element.classList.add('incorrect');
                } else {
                    // پاسخ غلط که کاربر انتخاب نکرده - حالت عادی (درست عمل کرده)
                    element.classList.remove('selected', 'correct', 'incorrect');
                }

                // Update checkmark visibility and state
                const checkIcon = element.querySelector('.bi-check');
                if (isCorrect) {
                    // همه پاسخ‌های صحیح باید تیک داشته باشند
                    checkIcon.style.display = 'inline';
                } else if (!isCorrect && wasSelected) {
                    // پاسخ غلط که کاربر انتخاب کرده - تیک برداشته شود
                    checkIcon.style.display = 'none';
                } else {
                    // پاسخ غلط که انتخاب نشده - تیک نداشته باشد
                    checkIcon.style.display = 'none';
                }
            });

            // Show explanation
            if (question.info) {
                const questionInfo = document.getElementById('questionInfo');
                const questionInfoContent = document.getElementById('questionInfoContent');
                questionInfoContent.innerHTML = question.info;
                questionInfo.classList.add('show');
            }

            // Update confirm button
            updateConfirmButton();

            // Update question button
            updateQuestionButtons();

            // بررسی صحت پاسخ (بدون انتقال خودکار)
            const correctAnswers = question.answers.map((ans, idx) => ans.isCorrect ? idx : -1).filter(idx => idx !== -1);
            const userCorrect = userAnswer.every(ans => question.answers[ans].isCorrect) &&
                correctAnswers.every(ans => userAnswer.includes(ans));

            // فقط لاگ کردن نتیجه، بدون انتقال خودکار
            if (userCorrect) {
                console.log('پاسخ چندگزینه‌ای صحیح است');
            } else {
                console.log('پاسخ چندگزینه‌ای غلط است');
            }

            console.log('Answer confirmed for question', questionIndex + 1);
        }

        // Update confirm button state
        function updateConfirmButton() {
            const confirmBtn = document.getElementById('confirmBtn');
            const questionIndex = currentQuestionIndex;
            const question = questions[questionIndex];

            if (mode === 'review') {
                confirmBtn.style.display = 'none';
                return;
            }

            // Hide confirm button if video hasn't been started for video questions
            const hasVideo = question.picture && isVideoFile(question.picture);
            if (hasVideo && !videoStates[questionIndex].videoStarted) {
                confirmBtn.style.display = 'none';
                return;
            }

            if (questionStates[questionIndex].confirmed) {
                confirmBtn.textContent = 'تایید شده';
                confirmBtn.disabled = true;
            } else {
                let hasAnswer = false;
                if (question.asw_type_1 === "2") {
                    // Numeric question
                    hasAnswer = userAnswers[questionIndex] && userAnswers[questionIndex].trim() !== '';
                } else {
                    // Multiple choice question
                    hasAnswer = true; // همیشه فعال برای سوالات چندگزینه‌ای
                }

                if (hasAnswer) {
                    confirmBtn.textContent = 'تایید پاسخ';
                    confirmBtn.disabled = false;
                } else {
                    confirmBtn.textContent = 'تایید پاسخ';
                    confirmBtn.disabled = true;
                }
            }
        }

        // Update navigation buttons
        function updateNavigation() {
            document.getElementById('firstBtn').disabled = currentQuestionIndex === 0;
            document.getElementById('prevBtn').disabled = currentQuestionIndex === 0;
            document.getElementById('nextBtn').disabled = currentQuestionIndex === questions.length - 1;
            document.getElementById('lastBtn').disabled = currentQuestionIndex === questions.length - 1;
        }

        // Update question number buttons (only for currently visible page)
        function updateQuestionButtons() {
            const startIndex = currentPage * questionsPerPage;
            const endIndex = Math.min(startIndex + questionsPerPage, questions.length);

            for (let i = startIndex; i < endIndex; i++) {
                const btn = document.getElementById(`questionBtn${i}`);
                if (btn) {
                    btn.classList.remove('active', 'answered');

                    if (i === currentQuestionIndex) {
                        btn.classList.add('active');
                    } else if (questionStates[i] && questionStates[i].answered) {
                        btn.classList.add('answered');
                    }
                }
            }
        }

        // Navigation functions
        function firstQuestion() {
            loadQuestion(0);
        }

        function prevQuestion() {
            if (currentQuestionIndex > 0) {
                loadQuestion(currentQuestionIndex - 1);
            }
        }

        function nextQuestion() {
            if (currentQuestionIndex < questions.length - 1) {
                loadQuestion(currentQuestionIndex + 1);
            }
        }

        function lastQuestion() {
            loadQuestion(questions.length - 1);
        }

        function goToQuestion(index) {
            loadQuestion(index);
        }

        function closeTest() {
            if (confirm('آیا مطمئن هستید که می‌خواهید از آزمون خارج شوید؟')) {
                window.history.back();
            }
        }

        // Event listener for window resize to recalculate pagination
        window.addEventListener('resize', function () {
            const oldQuestionsPerPage = questionsPerPage;
            calculatePagination();

            // If questions per page changed, update display
            if (oldQuestionsPerPage !== questionsPerPage) {
                // Find which page the current question should be on
                currentPage = findQuestionPage(currentQuestionIndex);
                displayCurrentPage();
            }
        });

        // Initialize test when page loads
        document.addEventListener('DOMContentLoaded', function () {
            initializeTest();

            // Setup video modal event listeners
            const videoModal = document.getElementById('videoModal');
            const videoModalClose = document.getElementById('videoModalClose');
            const modalVideo = document.getElementById('modalVideo');

            // Close modal when clicking close button
            videoModalClose.addEventListener('click', closeVideoModal);

            // Close modal when clicking outside video
            videoModal.addEventListener('click', function (e) {
                if (e.target === videoModal) {
                    closeVideoModal();
                }
            });

            // Close modal when pressing Escape key
            document.addEventListener('keydown', function (e) {
                if (e.key === 'Escape' && videoModal.classList.contains('show')) {
                    closeVideoModal();
                }
            });

            // Optional: Close modal when video ends
            modalVideo.addEventListener('ended', function () {
                setTimeout(closeVideoModal, 1000); // Wait 1 second after video ends
            });
        });
    </script>
</body>

</html>