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
        color: white;
        font-size: 1.2rem;
        margin-bottom: 20px;
        font-weight: bold;
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
            <div class="progress-info" id="progressInfo">
                کارت 1 از 10
            </div>

            <div class="flashcard-container">
                <div class="flashcard" id="flashcard">
                    <div class="flashcard-face flashcard-front">
                        <div class="flashcard-word" id="wordText">کلمه</div>
                        <div class="flashcard-hint">
                            <i class="fas fa-hand-pointer"></i>
                            برای دیدن ترجمه کلیک کنید
                        </div>
                    </div>
                    <div class="flashcard-face flashcard-back">
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
        let currentWords = [];
        let currentIndex = 0;
        const flashcard = document.getElementById('flashcard');
        const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content || '';

        // Load categories on page load
        document.addEventListener('DOMContentLoaded', loadCategories);

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
                    categories = data.categories;
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

            if (categories.length === 0) {
                grid.innerHTML = `
                    <div class="error-message">هنوز کلمه‌ای ذخیره نکرده‌اید</div>
                    <p class="text-muted text-center mt-3 mx-auto" style="max-width: 500px; line-height: 1.8; font-size: 0.92rem; direction: rtl;">
                        شما می‌توانید هنگام مطالعه سوالات روی هر کلمه‌ای یک بار کلیک کنید سپس بعد از ترجمه در صورت تمایل آن کلمه را ذخیره کنید
                    </p>
                `;
                return;
            }

            grid.innerHTML = categories.map(cat => `
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
            document.getElementById('flashcardSection').style.display = 'none';
            document.getElementById('categorySection').style.display = 'block';
            flashcard.classList.remove('flipped');
        }

        // Flip card on click
        flashcard.addEventListener('click', function () {
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
                }
            }
        });

        function showError(message) {
            const grid = document.getElementById('categoryGrid');
            grid.innerHTML = `<div class="error-message">${message}</div>`;
        }
    </script>