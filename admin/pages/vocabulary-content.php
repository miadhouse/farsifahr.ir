<?php

$csrf_token = $_SESSION['csrf_token'] ?? '';

if (empty($csrf_token)) {
    die('لطفاً مجدداً وارد شوید');
}
$user_id = $_SESSION['user_id'];
?>

<meta name="csrf-token" content="<?= $csrf_token ?>">
<style>
    .category-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
        gap: 20px;
        margin-top: 20px;
    }

    .category-card {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        border-radius: 12px;
        padding: 20px;
        cursor: pointer;
        transition: all 0.3s ease;
        color: white;
        position: relative;
        overflow: hidden;
    }

    .category-card:hover {
        transform: translateY(-5px);
        box-shadow: 0 10px 25px rgba(102, 126, 234, 0.4);
    }

    .category-card.all-words-card {
        background: linear-gradient(135deg, #059669 0%, #10b981 100%);
    }

    .category-card.all-words-card:hover {
        box-shadow: 0 10px 25px rgba(16, 185, 129, 0.4);
    }

    .category-card::before {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background: linear-gradient(135deg, rgba(255, 255, 255, 0.1) 0%, rgba(255, 255, 255, 0) 100%);
        opacity: 0;
        transition: opacity 0.3s ease;
    }

    .category-card:hover::before {
        opacity: 1;
    }

    .category-name {
        font-weight: bold;
        font-size: 1.1rem;
        margin-bottom: 10px;
    }

    .category-info {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-top: 15px;
    }

    .word-count {
        background: rgba(255, 255, 255, 0.2);
        padding: 5px 15px;
        border-radius: 20px;
        font-size: 0.9rem;
    }

    /* Flashcard Styles */
    .flashcard-section {
        display: none;
    }

    .flashcard-container {
        perspective: 1000px;
        max-width: 600px;
        margin: 0 auto;
    }

    .flashcard {
        position: relative;
        width: 100%;
        height: 400px;
        cursor: pointer;
        transform-style: preserve-3d;
        transition: transform 0.6s;
    }

    .flashcard.flipped {
        transform: rotateY(180deg);
    }

    .flashcard-face {
        position: absolute;
        width: 100%;
        height: 100%;
        backface-visibility: hidden;
        display: flex;
        align-items: center;
        justify-content: center;
        flex-direction: column;
        border-radius: 20px;
        box-shadow: 0 10px 40px rgba(0, 0, 0, 0.3);
        padding: 40px;
    }

    .flashcard-front {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
    }

    .flashcard-back {
        background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
        color: white;
        transform: rotateY(180deg);
    }

    .flashcard-word {
        font-size: 3rem;
        font-weight: bold;
        margin-bottom: 20px;
        text-align: center;
    }

    .flashcard-translation {
        font-size: 2rem;
        text-align: center;
    }

    .flashcard-hint {
        font-size: 1rem;
        opacity: 0.8;
        margin-top: 20px;
    }

    .card-top-bar {
        position: absolute;
        top: 18px;
        left: 20px;
        right: 20px;
        display: flex;
        justify-content: space-between;
        align-items: center;
        z-index: 10;
    }

    .btn-audio {
        background: rgba(255, 255, 255, 0.22);
        border: 1px solid rgba(255, 255, 255, 0.45);
        color: white;
        width: 44px;
        height: 44px;
        border-radius: 50%;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        font-size: 1.25rem;
        cursor: pointer;
        transition: all 0.25s ease;
        backdrop-filter: blur(6px);
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
    }

    .btn-audio:hover {
        background: rgba(255, 255, 255, 0.4);
        transform: scale(1.1);
        color: white;
    }

    .btn-audio.speaking {
        background: #10b981;
        border-color: #34d399;
        animation: pulse-audio 0.9s infinite alternate ease-in-out;
    }

    @keyframes pulse-audio {
        from { transform: scale(1); box-shadow: 0 0 0 0 rgba(16, 185, 129, 0.7); }
        to { transform: scale(1.12); box-shadow: 0 0 0 12px rgba(16, 185, 129, 0); }
    }

    .badge-lang {
        background: rgba(255, 255, 255, 0.22);
        border: 1px solid rgba(255, 255, 255, 0.35);
        color: white;
        padding: 6px 14px;
        border-radius: 20px;
        font-size: 0.88rem;
        font-weight: bold;
        cursor: pointer;
        transition: all 0.2s ease;
        display: inline-flex;
        align-items: center;
        gap: 6px;
        backdrop-filter: blur(6px);
        user-select: none;
    }

    .badge-lang:hover {
        background: rgba(255, 255, 255, 0.4);
        transform: scale(1.05);
        color: white;
    }

    .auto-speak-container {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        color: #384554;
        font-size: 0.92rem;
        font-weight: 500;
        user-select: none;
        cursor: pointer;
        background: #ffffff;
        border: 1px solid #d9dee3;
        padding: 6px 14px;
        border-radius: 20px;
        box-shadow: 0 2px 6px rgba(67, 89, 113, 0.1);
        transition: all 0.2s ease;
    }

    .auto-speak-container i {
        color: #696cff;
        font-size: 1rem;
    }

    .auto-speak-container:hover {
        background: #f8f9fa;
        color: #1e293b;
        border-color: #c7ced6;
    }

    .auto-speak-container input[type="checkbox"] {
        cursor: pointer;
        accent-color: #696cff;
        width: 16px;
        height: 16px;
        margin: 0;
    }

    /* هماهنگی با تم تیره در پنل مدیریت */
    .dark-style .auto-speak-container,
    [data-bs-theme="dark"] .auto-speak-container {
        color: #f1f5f9;
        background: rgba(255, 255, 255, 0.12);
        border-color: rgba(255, 255, 255, 0.22);
        box-shadow: none;
    }

    .dark-style .auto-speak-container i,
    [data-bs-theme="dark"] .auto-speak-container i {
        color: #34d399;
    }

    .dark-style .auto-speak-container:hover,
    [data-bs-theme="dark"] .auto-speak-container:hover {
        background: rgba(255, 255, 255, 0.2);
        color: #ffffff;
    }

    .dark-style .auto-speak-container input[type="checkbox"],
    [data-bs-theme="dark"] .auto-speak-container input[type="checkbox"] {
        accent-color: #10b981;
    }

    .flashcard-controls {
        display: flex;
        justify-content: center;
        gap: 20px;
        margin-top: 30px;
        flex-wrap: wrap;
    }

    .control-btn {
        padding: 12px 25px;
        border: none;
        border-radius: 50px;
        font-weight: bold;
        cursor: pointer;
        transition: all 0.3s ease;
        font-size: 1rem;
        white-space: nowrap;
    }

    .btn-prev {
        background: #6c757d;
        color: white;
        order: 2;
    }

    .btn-prev:hover {
        background: #5a6268;
        transform: scale(1.05);
    }

    .btn-next {
        background: #28a745;
        color: white;
        order: 1;
    }

    .btn-next:hover {
        background: #218838;
        transform: scale(1.05);
    }

    .btn-shuffle {
        background: #ffc107;
        color: #333;
        order: 3;
    }

    .btn-shuffle:hover {
        background: #e0a800;
        transform: scale(1.05);
    }

    .btn-back {
        background: #dc3545;
        color: white;
        order: 4;
    }

    .btn-back:hover {
        background: #c82333;
        transform: scale(1.05);
    }

    .progress-info {
        text-align: center;
        color: #384554;
        font-size: 1.15rem;
        margin-bottom: 20px;
        font-weight: bold;
    }

    .dark-style .progress-info,
    [data-bs-theme="dark"] .progress-info {
        color: #ffffff;
    }

    .loading {
        text-align: center;
        color: white;
        font-size: 1.5rem;
        padding: 50px;
    }

    .error-message {
        background: #f8d7da;
        color: #721c24;
        padding: 15px;
        border-radius: 10px;
        text-align: center;
        margin: 20px 0;
    }

    @media (max-width: 768px) {
        .flashcard {
            height: 300px;
        }

        .flashcard-word {
            font-size: 1.8rem;
        }

        .flashcard-translation {
            font-size: 1.4rem;
        }

        .category-grid {
            grid-template-columns: 1fr;
        }

        .flashcard-controls {
            gap: 10px;
            padding: 0 15px;
        }

        .control-btn {
            padding: 10px 12px;
            font-size: 0.85rem;
            flex: 1 1 calc(50% - 10px);
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 5px;
        }
    }
</style>
</head>

<body>
    <div class="container">
        <h1 class="page-title">
            <i class="fas fa-book-open"></i>
            تمرین واژگان
        </h1>

        <!-- Category Selection Section -->
        <div id="categorySection" class="category-section">
            <h3 class="text-center mb-4">
                <i class="fas fa-folder-open"></i>
                دسته‌بندی‌های شما
            </h3>
            <div id="categoryGrid" class="category-grid">
                <div class="loading">
                    <i class="fas fa-spinner fa-spin"></i>
                    در حال بارگذاری...
                </div>
            </div>
        </div>

        <!-- Flashcard Section -->
        <div id="flashcardSection" class="flashcard-section">
            <div class="d-flex justify-content-between align-items-center mb-3" style="max-width: 600px; margin: 0 auto; padding: 0 5px;">
                <div class="progress-info mb-0" id="progressInfo">
                    کارت 1 از 10
                </div>
                <label class="auto-speak-container mb-0" title="پخش خودکار تلفظ کلمه هنگام رفتن به کارت جدید">
                    <input type="checkbox" id="autoSpeakCheck" onchange="saveAutoSpeakPref()">
                    <i class="fas fa-volume-high"></i>
                    <span>پخش خودکار</span>
                </label>
            </div>

            <div class="flashcard-container">
                <div class="flashcard" id="flashcard">
                    <div class="flashcard-face flashcard-front">
                        <div class="card-top-bar">
                            <button type="button" class="btn-audio" onclick="speakCurrentWord(event)" title="پخش تلفظ صوتی (کلید S)">
                                <i class="fas fa-volume-up"></i>
                            </button>
                            <button type="button" class="badge-lang" onclick="toggleCurrentLang(event)" title="تغییر زبان تلفظ (کلیک کنید)">
                                <span id="langFlag">🇩🇪</span> <span id="langText">آلمانی</span>
                            </button>
                        </div>
                        <div class="flashcard-word" id="wordText">کلمه</div>
                        <div class="flashcard-hint">
                            <i class="fas fa-hand-pointer"></i>
                            برای دیدن ترجمه کلیک کنید
                        </div>
                    </div>
                    <div class="flashcard-face flashcard-back">
                        <div class="card-top-bar">
                            <button type="button" class="btn-audio" onclick="speakCurrentWord(event)" title="پخش تلفظ صوتی (کلید S)">
                                <i class="fas fa-volume-up"></i>
                            </button>
                            <span class="badge-lang" style="cursor: default; pointer-events: none;">
                                <i class="fas fa-language"></i> ترجمه فارسی
                            </span>
                        </div>
                        <div class="flashcard-translation" id="translationText">ترجمه</div>
                        <div class="flashcard-hint">
                            <i class="fas fa-hand-pointer"></i>
                            برای برگشتن کلیک کنید
                        </div>
                    </div>
                </div>
            </div>

            <div class="flashcard-controls">
                <button class="control-btn btn-back" onclick="backToCategories()">
                    <i class="fas fa-arrow-right"></i>
                    بازگشت
                </button>
                <button class="control-btn btn-prev" onclick="previousCard()">
                    <i class="fas fa-chevron-right"></i>
                    قبلی
                </button>
                <button class="control-btn btn-shuffle" onclick="shuffleCards()">
                    <i class="fas fa-random"></i>
                    مخلوط کن
                </button>
                <button class="control-btn btn-next" onclick="nextCard()">
                    بعدی
                    <i class="fas fa-chevron-left"></i>
                </button>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        let categories = [];
        let totalWordsCount = 0;
        let currentWords = [];
        let currentIndex = 0;
        let currentWordLang = 'de';
        const flashcard = document.getElementById('flashcard');
        const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content || '';

        // Preload voices
        if ('speechSynthesis' in window) {
            window.speechSynthesis.onvoiceschanged = () => {
                window.speechSynthesis.getVoices();
            };
        }

        // Load categories on page load
        document.addEventListener('DOMContentLoaded', () => {
            loadCategories();
            const autoSpeakEl = document.getElementById('autoSpeakCheck');
            if (autoSpeakEl) {
                autoSpeakEl.checked = (localStorage.getItem('farsifahr_vocab_autospeak') === '1');
            }
        });

        async function loadCategories() {
            try {
                const formData = new FormData();
                formData.append('csrf_token', csrfToken);

                const response = await fetch('../incloud/get_vocabulary_categories.php', {
                    method: 'POST',
                    body: formData
                });

                const data = await response.json();

                if (data.success) {
                    categories = data.categories || [];
                    totalWordsCount = data.total_words !== undefined ? data.total_words : 0;
                    displayCategories();
                } else {
                    showError(data.error || 'خطا در بارگذاری دسته‌بندی‌ها');
                }
            } catch (error) {
                console.error('Error:', error);
                showError('خطا در ارتباط با سرور');
            }
        }

        function displayCategories() {
            const grid = document.getElementById('categoryGrid');

            if (categories.length === 0 && totalWordsCount === 0) {
                grid.innerHTML = `
                    <div class="error-message">هنوز کلمه‌ای ذخیره نکرده‌اید</div>
                    <p class="text-muted text-center mt-3 mx-auto" style="max-width: 500px; line-height: 1.8; font-size: 0.92rem; direction: rtl;">
                        شما می‌توانید هنگام مطالعه سوالات روی هر کلمه‌ای یک بار کلیک کنید سپس بعد از ترجمه در صورت تمایل آن کلمه را ذخیره کنید
                    </p>
                `;
                return;
            }

            let html = '';

            if (totalWordsCount > 0) {
                html += `
                    <div class="category-card all-words-card" onclick="selectCategory('all')">
                        <div class="category-name">
                            <i class="fas fa-layer-group"></i>
                            همه کلمات (تمام دسته‌ها)
                        </div>
                        <div class="category-info">
                            <div class="word-count">
                                <i class="fas fa-book"></i>
                                ${totalWordsCount} کلمه
                            </div>
                            <div>
                                <i class="fas fa-arrow-left"></i>
                            </div>
                        </div>
                    </div>
                `;
            }

            html += categories.map(cat => `
                <div class="category-card" onclick="selectCategory(${cat.id})">
                    <div class="category-name">
                        <i class="fas fa-tag"></i>
                        ${cat.index_code} - ${cat.title}
                    </div>
                    <div class="category-info">
                        <div class="word-count">
                            <i class="fas fa-book"></i>
                            ${cat.word_count} کلمه
                        </div>
                        <div>
                            <i class="fas fa-arrow-left"></i>
                        </div>
                    </div>
                </div>
            `).join('');

            grid.innerHTML = html;
        }

        async function selectCategory(categoryId) {
            try {
                const formData = new FormData();
                formData.append('category_id', categoryId);
                formData.append('csrf_token', csrfToken);

                const response = await fetch('../incloud/get_vocabulary_by_category.php', {
                    method: 'POST',
                    body: formData
                });

                const data = await response.json();

                if (data.success) {
                    currentWords = data.words;
                    currentIndex = 0;
                    showFlashcards();
                } else {
                    showError(data.error || 'خطا در بارگذاری کلمات');
                }
            } catch (error) {
                console.error('Error:', error);
                showError('خطا در ارتباط با سرور');
            }
        }

        function showFlashcards() {
            document.getElementById('categorySection').style.display = 'none';
            document.getElementById('flashcardSection').style.display = 'block';
            displayCard();
        }

        function displayCard() {
            if (currentWords.length === 0) return;

            const word = currentWords[currentIndex];
            document.getElementById('wordText').textContent = word.word;
            document.getElementById('translationText').textContent = word.translation;
            document.getElementById('progressInfo').textContent =
                `کارت ${currentIndex + 1} از ${currentWords.length}`;

            // Reset flip
            flashcard.classList.remove('flipped');

            // Update current word language
            currentWordLang = (word.language && word.language.toLowerCase() === 'en') ? 'en' : 'de';
            updateLangUI(currentWordLang);

            // Auto-speak if enabled
            const autoSpeakEl = document.getElementById('autoSpeakCheck');
            if (autoSpeakEl && autoSpeakEl.checked) {
                setTimeout(() => {
                    speakWord(word.word, currentWordLang);
                }, 250);
            }
        }

        function updateLangUI(lang) {
            const flagEl = document.getElementById('langFlag');
            const textEl = document.getElementById('langText');
            if (flagEl && textEl) {
                if (lang === 'en') {
                    flagEl.textContent = '🇬🇧';
                    textEl.textContent = 'انگلیسی';
                } else {
                    flagEl.textContent = '🇩🇪';
                    textEl.textContent = 'آلمانی';
                }
            }
        }

        function toggleCurrentLang(event) {
            if (event) {
                event.stopPropagation();
                event.preventDefault();
            }
            if (currentWords.length === 0 || !currentWords[currentIndex]) return;

            currentWordLang = (currentWordLang === 'en') ? 'de' : 'en';
            updateLangUI(currentWordLang);
            speakWord(currentWords[currentIndex].word, currentWordLang);
        }

        function speakCurrentWord(event) {
            if (event) {
                event.stopPropagation();
                event.preventDefault();
            }
            if (currentWords.length === 0 || !currentWords[currentIndex]) return;

            const word = currentWords[currentIndex].word;
            speakWord(word, currentWordLang);
        }

        function speakWord(text, lang = 'de') {
            if (!('speechSynthesis' in window)) {
                console.warn('SpeechSynthesis is not supported in this browser.');
                return;
            }

            window.speechSynthesis.cancel();

            const utterance = new SpeechSynthesisUtterance(text);
            utterance.lang = (lang === 'en') ? 'en-US' : 'de-DE';
            utterance.rate = 0.88;

            const voices = window.speechSynthesis.getVoices();
            if (voices && voices.length > 0) {
                const prefix = (lang === 'en') ? 'en' : 'de';
                const voice = voices.find(v => v.lang && v.lang.toLowerCase().startsWith(prefix));
                if (voice) {
                    utterance.voice = voice;
                }
            }

            const speakBtns = document.querySelectorAll('.btn-audio');
            speakBtns.forEach(btn => btn.classList.add('speaking'));

            utterance.onend = function () {
                speakBtns.forEach(btn => btn.classList.remove('speaking'));
            };
            utterance.onerror = function () {
                speakBtns.forEach(btn => btn.classList.remove('speaking'));
            };

            window.speechSynthesis.speak(utterance);
        }

        function saveAutoSpeakPref() {
            const autoSpeakEl = document.getElementById('autoSpeakCheck');
            if (autoSpeakEl) {
                localStorage.setItem('farsifahr_vocab_autospeak', autoSpeakEl.checked ? '1' : '0');
            }
        }

        function nextCard() {
            if (currentIndex < currentWords.length - 1) {
                currentIndex++;
                displayCard();
            }
        }

        function previousCard() {
            if (currentIndex > 0) {
                currentIndex--;
                displayCard();
            }
        }

        function shuffleCards() {
            currentWords = currentWords.sort(() => Math.random() - 0.5);
            currentIndex = 0;
            displayCard();
        }

        function backToCategories() {
            window.speechSynthesis && window.speechSynthesis.cancel();
            document.getElementById('flashcardSection').style.display = 'none';
            document.getElementById('categorySection').style.display = 'block';
            flashcard.classList.remove('flipped');
        }

        // Flip card on click (ignoring clicks on buttons)
        flashcard.addEventListener('click', function (e) {
            if (e.target.closest('.card-top-bar')) {
                return;
            }
            this.classList.toggle('flipped');
        });

        // Keyboard navigation
        document.addEventListener('keydown', function (e) {
            if (document.getElementById('flashcardSection').style.display === 'block') {
                if (e.key === 'ArrowRight') {
                    previousCard();
                } else if (e.key === 'ArrowLeft') {
                    nextCard();
                } else if (e.key === ' ') {
                    e.preventDefault();
                    flashcard.classList.toggle('flipped');
                } else if (e.key === 's' || e.key === 'S' || e.key === 'v' || e.key === 'V') {
                    speakCurrentWord();
                }
            }
        });

        function showError(message) {
            const grid = document.getElementById('categoryGrid');
            grid.innerHTML = `<div class="error-message">${message}</div>`;
        }
    </script>