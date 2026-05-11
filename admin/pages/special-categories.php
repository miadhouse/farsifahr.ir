<?php
$user_id = $_SESSION['user_id'];

// Calculate special categories totals
$stmt = $pdo->prepare("SELECT COUNT(*) FROM question_bookmarks WHERE user_id = ?");
$stmt->execute([$user_id]);
$bookmarks_count = $stmt->fetchColumn();

$stmt = $pdo->query("SELECT COUNT(*) FROM questions WHERE points = 5");
$points5_count = $stmt->fetchColumn();

$stmt = $pdo->query("SELECT COUNT(*) FROM questions WHERE picture LIKE '%.m4v'");
$video_count = $stmt->fetchColumn();

$stmt = $pdo->query("SELECT COUNT(*) FROM questions WHERE picture IS NOT NULL AND picture != '' AND picture NOT LIKE '%.m4v'");
$picture_count = $stmt->fetchColumn();

$stmt = $pdo->query("SELECT id, name FROM question_tags ORDER BY name ASC");
$tags = $stmt->fetchAll(PDO::FETCH_ASSOC);
$tag_counts = [];
$total_special = $bookmarks_count + $points5_count + $video_count + $picture_count;
foreach ($tags as $tag) {
    $stmtCount = $pdo->prepare("SELECT COUNT(*) FROM question_question_tag WHERE question_tag_id = ?");
    $stmtCount->execute([$tag['id']]);
    $tag_count = $stmtCount->fetchColumn();
    $tag_counts[$tag['id']] = $tag_count;
    $total_special += $tag_count;
}
?>
<div class="container-xxl flex-grow-1 container-p-y">

    <div class="main-container">
        <div class="header">
            <h1>
                <i class="fas fa-star car-icon"></i>
                دسته‌بندی‌های خاص
            </h1>
            <p>سوالات نشان‌شده، سخت و سفارشی</p>
        </div>

        <div class="row g-4">
            <div class="col-lg-12">
                <div class="category-card special-categories">
                    <div class="card-title">
                        <div>
                            <div class="total-questions"><?php echo $total_special; ?> سوال - دسته‌های خاص</div>
                            <div style="font-size: 1rem; color: #6b7280; font-weight: normal;">
                                ⭐ سوالات ویژه، نشان‌شده‌ها و هشتگ‌ها
                            </div>
                        </div>
                        <i class="fas fa-star icon text-warning"></i>
                    </div>

                    <div class="row">
                        <div class="col-md-6">
                            <div class="category-container">
                                <div class="category-item special-category-item"
                                    data-special-type="bookmarks"
                                    data-category-title="سوالات علامت‌گذاری شده"
                                    data-question-count="<?php echo $bookmarks_count; ?>">
                                    <div class="category-text">
                                        <i class="fas fa-bookmark text-primary me-2"></i>
                                        سوالات علامت‌گذاری شده
                                    </div>
                                    <div class="category-badges">
                                        <span class="question-badge"><?php echo $bookmarks_count; ?></span>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="category-container">
                                <div class="category-item special-category-item"
                                    data-special-type="5points"
                                    data-category-title="سوالات ۵ امتیازی"
                                    data-question-count="<?php echo $points5_count; ?>">
                                    <div class="category-text">
                                        <i class="fas fa-star text-warning me-2"></i>
                                        سوالات ۵ امتیازی
                                    </div>
                                    <div class="category-badges">
                                        <span class="question-badge"><?php echo $points5_count; ?></span>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="category-container">
                                <div class="category-item special-category-item"
                                    data-special-type="video"
                                    data-category-title="سوالات ویدیویی"
                                    data-question-count="<?php echo $video_count; ?>">
                                    <div class="category-text">
                                        <i class="fas fa-video text-danger me-2"></i>
                                        سوالات ویدیویی
                                    </div>
                                    <div class="category-badges">
                                        <span class="question-badge"><?php echo $video_count; ?></span>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="category-container">
                                <div class="category-item special-category-item"
                                    data-special-type="picture"
                                    data-category-title="سوالات تصویری"
                                    data-question-count="<?php echo $picture_count; ?>">
                                    <div class="category-text">
                                        <i class="fas fa-image text-success me-2"></i>
                                        سوالات تصویری
                                    </div>
                                    <div class="category-badges">
                                        <span class="question-badge"><?php echo $picture_count; ?></span>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="col-md-6">
                            <?php foreach ($tags as $tag): ?>
                                <?php if ($tag_counts[$tag['id']] > 0): ?>
                                <div class="category-container">
                                    <div class="category-item special-category-item"
                                        data-special-type="tag"
                                        data-tag-id="<?php echo $tag['id']; ?>"
                                        data-category-title="هشتگ: <?php echo htmlspecialchars($tag['name']); ?>"
                                        data-question-count="<?php echo $tag_counts[$tag['id']]; ?>">
                                        <div class="category-text">
                                            <i class="fas fa-hashtag text-info me-2"></i>
                                            <?php echo htmlspecialchars($tag['name']); ?>
                                        </div>
                                        <div class="category-badges">
                                            <span class="question-badge"><?php echo $tag_counts[$tag['id']]; ?></span>
                                        </div>
                                    </div>
                                </div>
                                <?php endif; ?>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="w-100 ce" style="position: fixed !important; bottom: 0 !important; right: 0; z-index: 9999;">
                <div class="category-footer" id="categoryFooter">
                    <div class="selected-category-info">
                        <h4 id="selectedCategoryTitle">دسته‌بندی انتخابی</h4>
                        <div class="selected-category-details">
                            <span id="selectedQuestionCount">0</span> سوال
                        </div>
                    </div>
                    <div class="category-actions">
                        <button class="btn-select-questions" id="selectQuestionsBtn" onclick="openQuestionsModal()">
                            <i class="fas fa-tasks"></i>
                            انتخاب سوالات
                        </button>
                        <button class="btn-clear" onclick="clearSelection()">
                            <i class="fas fa-times"></i>
                            حذف انتخاب
                        </button>
                    </div>
                </div>

                <div class="no-selection-message" id="noSelectionMessage">
                    <div class="message-content">
                        <i class="fas fa-info-circle"></i>
                        لطفا برای شروع یک دسته‌بندی را انتخاب کنید!
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Questions Selection Modal -->
    <div class="modal fade" id="questionsModal" tabindex="-1" aria-labelledby="questionsModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <div class="d-flex justify-content-between w-100">
                        <h5 class="modal-title" id="questionsModalLabel">انتخاب سوالات</h5>
                        <div class="modal-actions">
                            <button class="btn btn-info btn-sm me-2" id="browseQuestionsBtn"
                                onclick="browseQuestions()">
                                <i class="fas fa-list mx-1"></i>
                                مرور سوالات
                            </button>
                            <button class="btn btn-success btn-sm" id="practiceBtn" onclick="startPractice()">
                                <i class="fas fa-play mx-1"></i>
                                تمرین
                            </button>
                        </div>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body p-2">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <div class="fs-6">
                            <span class="badge bg-primary p-1" id="selectedQuestionsCount">0</span>
                            سوال انتخاب شده از
                            <span class="badge bg-secondary p-1" id="totalQuestionsCount">0</span>
                            سوال
                        </div>
                        <div>
                            <button class="btn p-1 btn-sm btn-outline-primary btn-sm" onclick="selectAllQuestions()">
                                <i class="fas fa-check-double mx-1"></i>
                            </button>
                            <button class="btn p-1 btn-sm btn-outline-secondary btn-sm ms-1"
                                onclick="deselectAllQuestions()">
                                <i class="fas fa-times mx-1"></i>
                            </button>
                        </div>
                    </div>
                    <div id="questionsContainer">
                        <div class="text-center p-4">
                            <div class="spinner-border text-primary" role="status">
                                <span class="visually-hidden">در حال بارگذاری...</span>
                            </div>
                            <div class="mt-2">در حال بارگذاری سوالات...</div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <div class="d-flex justify-content-between w-100">
                        <div class="selected-info">
                            <span class="text-muted">
                                <span id="footerSelectedCount">0</span> سوال انتخاب شده
                            </span>
                        </div>
                        <div>
                            <button class="btn btn-info me-2" onclick="browseQuestions()">
                                <i class="fas fa-list mx-1"></i>
                                مرور سوالات
                            </button>
                            <button class="btn btn-success" onclick="startPractice()">
                                <i class="fas fa-play mx-1"></i>
                                تمرین
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <style>
        .category-container { margin-bottom: 0.5rem; }
        .category-item { display: flex; justify-content: space-between; align-items: center; padding: 0.75rem 1rem; background-color: #f9fafb; border: 1px solid #e5e7eb; border-radius: 0.375rem; cursor: pointer; transition: all 0.2s ease; position: relative; user-select: none; }
        .category-item:hover { background-color: #f3f4f6; border-color: #d1d5db; }
        .category-item.selected { background-color: #dbeafe; border-color: #3b82f6; color: #1d4ed8; }
        .category-text { flex-grow: 1; margin-right: 0.5rem; font-weight: 500; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; direction: rtl; text-align: right; }
        .category-badges { display: flex; align-items: center; gap: 0.5rem; }
        .question-badge { font-size: 0.75rem; padding: 0.25rem 0.5rem; border-radius: 0.25rem; font-weight: bold; background-color: #dbeafe; color: #1d4ed8; }
        .category-card { background: white; border-radius: 0.5rem; box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1); padding: 1rem; transition: transform 0.2s ease; }
        .category-card:hover { transform: translateY(-2px); box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1); }
        .card-title { display: flex; justify-content: space-between; align-items: center; padding: 0.75rem 1rem; background: #f8fafc; border-radius: 0.375rem; margin-bottom: 1rem; }
        .card-title i.icon { font-size: 1.5rem; color: #6b7280; }
        .total-questions { font-weight: bold; font-size: 1.1rem; color: #111827; }
        #categoryFooter { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 1rem; border-radius: 0.5rem 0.5rem 0 0; box-shadow: 0 -2px 10px rgba(0, 0, 0, 0.1); display: flex; justify-content: space-between; align-items: center; z-index: 1000; }
        .selected-category-info h4 { margin: 0; font-size: 1.2rem; font-weight: 600; }
        .selected-category-details { font-size: 0.9rem; opacity: 0.9; }
        .category-actions button { margin-left: 0.5rem; padding: 0.5rem 1rem; border-radius: 0.375rem; border: none; cursor: pointer; transition: all 0.2s ease; }
        .btn-select-questions { background: #10b981; color: white; }
        .btn-select-questions:hover { background: #059669; }
        .btn-clear { background: #ef4444; color: white; }
        .btn-clear:hover { background: #dc2626; }
        .no-selection-message { background: #f3f4f6; color: #6b7280; padding: 1rem; border-radius: 0.5rem 0.5rem 0 0; text-align: center; display: flex; justify-content: center; align-items: center; z-index: 1000; }
        .message-content { display: flex; align-items: center; gap: 0.5rem; font-size: 0.9rem; }
        .message-content i { color: #3b82f6; }
        .slide-out-down { animation: slideOutDown 0.3s forwards; }
        .slide-in-up { animation: slideInUp 0.3s forwards; }
        @keyframes slideOutDown { from { transform: translateY(0); opacity: 1; } to { transform: translateY(100%); opacity: 0; } }
        @keyframes slideInUp { from { transform: translateY(100%); opacity: 0; } to { transform: translateY(0); opacity: 1; } }
        #categoryFooter, .no-selection-message { position: fixed !important; bottom: 0 !important; right: 0; left: 0; z-index: 9999; display: none; }
        .dark-style .category-card { background-color: #283144; border: 1px solid #36445d; box-shadow: 0 1px 3px rgba(0, 0, 0, 0.4); }
        .dark-style .card-title { background-color: rgba(90, 141, 238, 0.08); border-bottom: 1px solid #36445d; }
        .dark-style .total-questions { color: #d8deea; }
        .dark-style .category-item { background-color: #1c222f; border-color: #36445d; color: #cbd5e0; }
        .dark-style .category-item:hover { background-color: #283144; border-color: #4a5568; }
        .dark-style .category-item.selected { background-color: rgba(90, 141, 238, 0.16); border-color: #5a8dee; color: #5a8dee; }
        .dark-style .no-selection-message { background-color: #283144; color: #a1b0cb; border-top: 1px solid #36445d; }
        .dark-style h3 { color: #d8deea !important; }
    </style>

    <script>
        function setCookie(name, value, days = 30) {
            const expires = new Date();
            expires.setTime(expires.getTime() + (days * 24 * 60 * 60 * 1000));
            document.cookie = name + '=' + value + ';expires=' + expires.toUTCString() + ';path=/';
        }

        function getCookie(name) {
            const nameEQ = name + "=";
            const ca = document.cookie.split(';');
            for (let i = 0; i < ca.length; i++) {
                let c = ca[i];
                while (c.charAt(0) === ' ') c = c.substring(1, c.length);
                if (c.indexOf(nameEQ) === 0) return c.substring(nameEQ.length, c.length);
            }
            return null;
        }

        function deleteCookie(name) {
            document.cookie = name + '=; Path=/; Expires=Thu, 01 Jan 1970 00:00:01 GMT;';
        }

        let selectedCategoryId = null;
        let selectedCategoryTitle = '';
        let selectedQuestionCount = 0;
        let selectedType = '';

        document.addEventListener('DOMContentLoaded', function () {
            loadSavedCategory();
            const cards = document.querySelectorAll('.category-card');
            cards.forEach((card, index) => {
                card.style.opacity = '0';
                card.style.transform = 'translateY(30px)';
                setTimeout(() => {
                    card.style.transition = 'all 0.6s ease';
                    card.style.opacity = '1';
                    card.style.transform = 'translateY(0)';
                }, index * 200);
            });
        });

        function loadSavedCategory() {
            const savedCategory = getCookie('selectedSpecialCategory');
            if (savedCategory) {
                try {
                    const categoryData = JSON.parse(decodeURIComponent(savedCategory));
                    selectedCategoryId = categoryData.id;
                    selectedCategoryTitle = categoryData.title;
                    selectedQuestionCount = categoryData.questionCount;
                    selectedType = categoryData.type;
                    
                    document.querySelectorAll('.special-category-item').forEach(item => item.classList.remove('selected'));
                    const selectedItem = Array.from(document.querySelectorAll('.special-category-item')).find(item => {
                        let itemId = item.getAttribute('data-special-type');
                        if(item.getAttribute('data-tag-id')) itemId += '_' + item.getAttribute('data-tag-id');
                        return itemId === selectedCategoryId;
                    });
                    if(selectedItem) selectedItem.classList.add('selected');

                    updateFooterDisplay();
                } catch (e) {
                    console.error(e);
                    deleteCookie('selectedSpecialCategory');
                }
            }
        }

        function saveCategoryToCookie() {
            const categoryData = {
                id: selectedCategoryId,
                title: selectedCategoryTitle,
                questionCount: selectedQuestionCount,
                type: selectedType
            };
            setCookie('selectedSpecialCategory', encodeURIComponent(JSON.stringify(categoryData)));
        }

        function updateFooterDisplay() {
            const footer = document.getElementById('categoryFooter');
            const noSelectionMessage = document.getElementById('noSelectionMessage');

            if (selectedCategoryId) {
                if (footer.style.display !== 'none' && !footer.classList.contains('slide-out-down')) {
                    footer.classList.add('slide-out-down');
                    setTimeout(() => {
                        document.getElementById('selectedCategoryTitle').textContent = selectedCategoryTitle;
                        document.getElementById('selectedQuestionCount').textContent = selectedQuestionCount;
                        footer.style.display = 'flex';
                        footer.classList.remove('slide-out-down');
                        footer.classList.add('slide-in-up');
                        setTimeout(() => { footer.classList.remove('slide-in-up'); }, 300);
                    }, 300);
                } else {
                    document.getElementById('selectedCategoryTitle').textContent = selectedCategoryTitle;
                    document.getElementById('selectedQuestionCount').textContent = selectedQuestionCount;
                    noSelectionMessage.style.display = 'none';
                    footer.style.display = 'flex';
                    footer.classList.add('slide-in-up');
                    setTimeout(() => { footer.classList.remove('slide-in-up'); }, 300);
                }
            } else {
                if (footer.style.display !== 'none') {
                    footer.classList.add('slide-out-down');
                    setTimeout(() => {
                        footer.style.display = 'none';
                        footer.classList.remove('slide-out-down');
                        noSelectionMessage.style.display = 'flex';
                    }, 300);
                } else {
                    noSelectionMessage.style.display = 'flex';
                }
            }
        }

        function selectCategory(id, title, questionCount, type) {
            selectedCategoryId = id;
            selectedCategoryTitle = title;
            selectedQuestionCount = questionCount;
            selectedType = type;
            
            document.querySelectorAll('.special-category-item').forEach(item => item.classList.remove('selected'));
            const selectedItem = Array.from(document.querySelectorAll('.special-category-item')).find(item => {
                let itemId = item.getAttribute('data-special-type');
                if(item.getAttribute('data-tag-id')) itemId += '_' + item.getAttribute('data-tag-id');
                return itemId === id;
            });
            if(selectedItem) selectedItem.classList.add('selected');

            updateFooterDisplay();
            saveCategoryToCookie();
        }

        function clearSelection() {
            selectedCategoryId = null;
            selectedCategoryTitle = '';
            selectedQuestionCount = 0;
            selectedType = '';
            
            document.querySelectorAll('.special-category-item').forEach(item => item.classList.remove('selected'));

            updateFooterDisplay();
            deleteCookie('selectedSpecialCategory');
        }

        function openQuestionsModal() {
            if (!selectedCategoryId) return;
            const modalElement = document.getElementById('questionsModal');
            const modal = new bootstrap.Modal(modalElement);
            document.getElementById('questionsModalLabel').textContent = `انتخاب سوالات - ${selectedCategoryTitle}`;
            modal.show();
            modalElement.addEventListener('shown.bs.modal', () => { document.getElementById('categoryFooter').hidden = true; });
            modalElement.addEventListener('hidden.bs.modal', () => { document.getElementById('categoryFooter').hidden = false; });
            loadQuestions();
        }

        function loadQuestions() {
            const container = document.getElementById('questionsContainer');
            container.innerHTML = `<div class="text-center p-4"><div class="spinner-border text-primary" role="status"><span class="visually-hidden">در حال بارگذاری...</span></div><div class="mt-2">در حال بارگذاری سوالات...</div></div>`;

            let url = '';
            if (selectedType === 'special') {
                const parts = String(selectedCategoryId).split('_');
                url = `pages/load_questions.php?special_type=${parts[0]}`;
                if (parts.length > 1) url += `&tag_id=${parts[1]}`;
            }

            fetch(url)
                .then(response => response.text())
                .then(data => {
                    container.innerHTML = data;
                    updateQuestionCounts();
                    const checkboxes = document.querySelectorAll('#questionsContainer input[type="checkbox"]');
                    checkboxes.forEach(cb => cb.addEventListener('change', updateQuestionCounts));
                })
                .catch(error => {
                    container.innerHTML = `<div class="alert alert-danger text-center"><i class="fas fa-exclamation-triangle"></i> خطا در بارگذاری سوالات. لطفا دوباره تلاش کنید.</div>`;
                });
        }

        function updateQuestionCounts() {
            const checkboxes = document.querySelectorAll('#questionsContainer input[type="checkbox"]');
            const selectedCheckboxes = document.querySelectorAll('#questionsContainer input[type="checkbox"]:checked');
            document.getElementById('totalQuestionsCount').textContent = checkboxes.length;
            document.getElementById('selectedQuestionsCount').textContent = selectedCheckboxes.length;
            document.getElementById('footerSelectedCount').textContent = selectedCheckboxes.length;
        }

        function selectAllQuestions() {
            document.querySelectorAll('#questionsContainer input[type="checkbox"]').forEach(cb => cb.checked = true);
            updateQuestionCounts();
        }

        function deselectAllQuestions() {
            document.querySelectorAll('#questionsContainer input[type="checkbox"]').forEach(cb => cb.checked = false);
            updateQuestionCounts();
        }

        function getSelectedQuestionIds() {
            return Array.from(document.querySelectorAll('#questionsContainer input[type="checkbox"]:checked')).map(cb => cb.id);
        }

        function navigateToTest(mode = 'browse') {
            const selectedQuestions = getSelectedQuestionIds();
            if (selectedQuestions.length === 0) { 
                Swal.fire({
                    icon: 'warning',
                    title: 'توجه',
                    text: 'لطفا حداقل یک سوال را انتخاب کنید.',
                    confirmButtonText: 'متوجه شدم'
                });
                return; 
            }

            const form = document.createElement('form');
            form.method = 'POST';
            form.action = '../app/index.php';

            selectedQuestions.forEach(questionId => {
                const input = document.createElement('input');
                input.type = 'hidden';
                input.name = 'selected_questions[]';
                input.value = questionId;
                form.appendChild(input);
            });

            const modeInput = document.createElement('input');
            modeInput.type = 'hidden';
            modeInput.name = 'mode';
            modeInput.value = mode;
            form.appendChild(modeInput);

            const categoryInput = document.createElement('input');
            categoryInput.type = 'hidden';
            categoryInput.name = 'special_type';
            categoryInput.value = selectedCategoryId;
            form.appendChild(categoryInput);

            document.body.appendChild(form);
            form.submit();
        }

        function browseQuestions() { navigateToTest('browse'); }
        function startPractice() { navigateToTest('practice'); }

        document.querySelectorAll('.special-category-item').forEach(item => {
            item.addEventListener('click', function () {
                const specialType = this.getAttribute('data-special-type');
                const tagId = this.getAttribute('data-tag-id');
                const categoryTitle = this.getAttribute('data-category-title');
                const questionCount = parseInt(this.getAttribute('data-question-count'));
                let id = specialType;
                if (tagId) id += '_' + tagId;
                selectCategory(id, categoryTitle, questionCount, 'special');
            });
        });
    </script>
</div>
