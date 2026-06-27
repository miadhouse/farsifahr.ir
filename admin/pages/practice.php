<?php
$user_id = $_SESSION['user_id'];

/**
 * رندر بازگشتی دسته‌بندی‌ها
 */
function renderCategoriesRecursive($pdo, $categories, $all_subcategories, $is_root = true) {
    foreach ($categories as $category): 
        $has_subs = !empty($all_subcategories[$category['id']]);
        $item_class = $is_root ? 'category-item' : 'subcategory-item';
        if ($has_subs) $item_class .= ' expandable';
        
        $data_id_attr = $is_root ? 'data-category-id' : 'data-subcategory-id';
        $data_title_attr = $is_root ? 'data-category-title' : 'data-subcategory-title';
        $text_class = $is_root ? 'category-text' : 'subcategory-text';
        $subtitle_class = $is_root ? 'category-subtitle' : 'subcategory-subtitle';
        $badge_class = $is_root ? 'question-badge' : 'subcategory-badge';
        $code_class = $is_root ? 'code-badge' : 'subcategory-code';
        ?>
        <div class="category-container">
            <div class="<?php echo $item_class; ?>"
                <?php echo $data_id_attr; ?>="<?php echo $category['id']; ?>"
                <?php echo $data_title_attr; ?>="<?php echo htmlspecialchars(($category['title_en'] ?: $category['title']) . ' (' . $category['title'] . ')'); ?>"
                data-question-count="<?php echo $category['question_count']; ?>">
                <div class="<?php echo $text_class; ?>">
                    <span><?php echo htmlspecialchars($category['title_en'] ?: $category['title']); ?></span>
                    <span class="<?php echo $subtitle_class; ?>"><?php echo htmlspecialchars($category['title']); ?></span>
                </div>
                <div class="category-badges">
                    <span class="<?php echo $badge_class; ?>"><?php echo $category['question_count']; ?></span>
                    <span class="<?php echo $code_class; ?>"><?php echo $category['index_code']; ?></span>
                    <?php if ($has_subs): ?>
                        <i class="fas fa-chevron-left arrow"></i>
                    <?php endif; ?>
                </div>
            </div>

            <?php if ($has_subs): ?>
                <div class="subcategories" id="sub-<?php echo $category['id']; ?>">
                    <?php renderCategoriesRecursive($pdo, $all_subcategories[$category['id']], $all_subcategories, false); ?>
                </div>
            <?php endif; ?>
        </div>
    <?php endforeach;
}

// Get main categories
$grundstoff_main = getCategories($pdo, 1, 0, $user_id);
$zusatzstoff_main = getCategories($pdo, 2, 1, $user_id);

// Get all subcategories recursively
$all_subcategories = [];
function fetchSubcategoriesRecursive($pdo, $parent_id, $user_id, &$all_subcategories) {
    $subcats = getSubcategories($pdo, $parent_id, user_id: $user_id);
    if (!empty($subcats)) {
        $all_subcategories[$parent_id] = $subcats;
        foreach ($subcats as $sub) {
            fetchSubcategoriesRecursive($pdo, $sub['id'], $user_id, $all_subcategories);
        }
    }
}

foreach (array_merge($grundstoff_main, $zusatzstoff_main) as $category) {
    fetchSubcategoriesRecursive($pdo, $category['id'], $user_id, $all_subcategories);
}

// Calculate totals
$grundstoff_total = 0;
$zusatzstoff_total = 0;

foreach ($grundstoff_main as $cat) {
    $grundstoff_total += $cat['question_count'];
}

foreach ($zusatzstoff_main as $cat) {
    $zusatzstoff_total += $cat['question_count'];
}

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
                <i class="fas fa-car car-icon"></i>
                دسته‌بندی امتحان گواهینامه آلمان
            </h1>
            <p>ساختار کامل موضوعات و دسته‌بندی سوالات امتحان تئوری</p>
        </div>

        <div class="row g-4">
            <!-- Grundstoff (Basic Material) -->
            <div class="col-lg-6">
                <div class="category-card grundstoff">
                    <div class="card-title">
                        <div>
                            <div class="total-questions"><?php echo $grundstoff_total; ?> سوال - (Grundstoff) مواد پایه
                            </div>
                            <div style="font-size: 1rem; color: #6b7280; font-weight: normal;">
                                📚 موضوعات اصلی و پایه‌ای
                            </div>
                        </div>
                        <i class="fas fa-book icon"></i>
                    </div>

                    <?php renderCategoriesRecursive($pdo, $grundstoff_main, $all_subcategories); ?>
                </div>
            </div>

            <!-- Zusatzstoff (Additional Material) -->
            <div class="col-lg-6">
                <div class="category-card zusatzstoff">
                    <div class="card-title">
                        <div>
                            <div class="total-questions"><?php echo $zusatzstoff_total; ?> سوال - (Zusatzstoff) مواد
                                تکمیلی</div>
                            <div style="font-size: 1rem; color: #6b7280; font-weight: normal;">
                                📋 موضوعات تخصصی و تکمیلی
                            </div>
                        </div>
                        <i class="fas fa-clipboard-list icon"></i>
                    </div>

                    <?php renderCategoriesRecursive($pdo, $zusatzstoff_main, $all_subcategories); ?>
                </div>
            </div>
        </div>

        <!-- Statistics Section -->
        <div class="row mt-4">
            <div class="col-12">
                <div class="category-card" style="text-align: center;">
                    <h3 style="color: #374151; margin-bottom: 20px;">
                        <i class="fas fa-chart-bar"></i>
                        آمار کلی سوالات
                    </h3>
                    <div class="row">
                        <div class="col-md-4">
                            <div style="font-size: 2rem; color: #2563eb; font-weight: bold;">
                                <?php echo $grundstoff_total; ?>
                            </div>
                            <div style="color: #6b7280;">سوالات مواد پایه</div>
                        </div>
                        <div class="col-md-4">
                            <div style="font-size: 2rem; color: #7c3aed; font-weight: bold;">
                                <?php echo $zusatzstoff_total; ?>
                            </div>
                            <div style="color: #6b7280;">سوالات مواد تکمیلی</div>
                        </div>
                        <div class="col-md-4">
                            <div style="font-size: 2rem; color: #059669; font-weight: bold;">
                                <?php echo $grundstoff_total + $zusatzstoff_total; ?>
                            </div>
                            <div style="color: #6b7280;">مجموع کل سوالات</div>
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

                    <!-- آکاردئون فیلتر سوالات -->
                    <div class="accordion mb-3" id="filterAccordion" style="direction: rtl;">
                        <div class="accordion-item border rounded shadow-sm">
                            <h2 class="accordion-header" id="headingFilters">
                                <button class="accordion-button collapsed filter-accordion-btn" type="button" data-bs-toggle="collapse" data-bs-target="#collapseFilters" aria-expanded="false" aria-controls="collapseFilters">
                                    <span id="accordionTitleText">فیلتر سوالات</span>
                                </button>
                            </h2>
                            <div id="collapseFilters" class="accordion-collapse collapse" aria-labelledby="headingFilters">
                                <div class="accordion-body bg-light p-3">
                                    
                                    <!-- فیلترهای سوالات به صورت چک‌باکس‌های دکمه‌ای هم‌اندازه -->
                                    <div class="row g-2 filter-container">
                                        <div class="col-md-3 col-6">
                                            <input type="checkbox" class="btn-check" id="filter-all" autocomplete="off" onchange="toggleFilterAll()">
                                            <label class="btn btn-sm btn-outline-dark w-100 py-2 d-flex align-items-center justify-content-center filter-btn-label" for="filter-all">
                                                <i class="fas fa-check-square me-2 checked-icon" style="display: none;"></i>
                                                <i class="far fa-square me-2 unchecked-icon"></i>
                                                همه سوالات
                                            </label>
                                        </div>
                                        <div class="col-md-3 col-6">
                                            <input type="checkbox" class="btn-check" id="filter-image" autocomplete="off" onchange="applyFilters()">
                                            <label class="btn btn-sm btn-outline-primary w-100 py-2 d-flex align-items-center justify-content-center filter-btn-label" for="filter-image">
                                                <i class="fas fa-check-square me-2 checked-icon" style="display: none;"></i>
                                                <i class="far fa-square me-2 unchecked-icon"></i>
                                                تصویری
                                            </label>
                                        </div>
                                        <div class="col-md-3 col-6">
                                            <input type="checkbox" class="btn-check" id="filter-video" autocomplete="off" onchange="applyFilters()">
                                            <label class="btn btn-sm btn-outline-info w-100 py-2 d-flex align-items-center justify-content-center filter-btn-label" for="filter-video">
                                                <i class="fas fa-check-square me-2 checked-icon" style="display: none;"></i>
                                                <i class="far fa-square me-2 unchecked-icon"></i>
                                                ویدیویی
                                            </label>
                                        </div>
                                        <div class="col-md-3 col-6">
                                            <input type="checkbox" class="btn-check" id="filter-incorrect" autocomplete="off" onchange="applyFilters()">
                                            <label class="btn btn-sm btn-outline-danger w-100 py-2 d-flex align-items-center justify-content-center filter-btn-label" for="filter-incorrect">
                                                <i class="fas fa-check-square me-2 checked-icon" style="display: none;"></i>
                                                <i class="far fa-square me-2 unchecked-icon"></i>
                                                اشتباه (پرچم قرمز)
                                            </label>
                                        </div>
                                        <div class="col-md-3 col-6">
                                            <input type="checkbox" class="btn-check" id="filter-unanswered" autocomplete="off" onchange="applyFilters()">
                                            <label class="btn btn-sm btn-outline-secondary w-100 py-2 d-flex align-items-center justify-content-center filter-btn-label" for="filter-unanswered">
                                                <i class="fas fa-check-square me-2 checked-icon" style="display: none;"></i>
                                                <i class="far fa-square me-2 unchecked-icon"></i>
                                                بدون پاسخ (خاکستری)
                                            </label>
                                        </div>
                                        <div class="col-md-3 col-6">
                                            <input type="checkbox" class="btn-check" id="filter-half-prepared" autocomplete="off" onchange="applyFilters()">
                                            <label class="btn btn-sm btn-outline-warning w-100 py-2 d-flex align-items-center justify-content-center filter-btn-label" for="filter-half-prepared">
                                                <i class="fas fa-check-square me-2 checked-icon" style="display: none;"></i>
                                                <i class="far fa-square me-2 unchecked-icon"></i>
                                                ۵۰ درصد آماده
                                            </label>
                                        </div>
                                        <div class="col-md-3 col-6">
                                            <input type="checkbox" class="btn-check" id="filter-prepared" autocomplete="off" onchange="applyFilters()">
                                            <label class="btn btn-sm btn-outline-success w-100 py-2 d-flex align-items-center justify-content-center filter-btn-label" for="filter-prepared">
                                                <i class="fas fa-check-square me-2 checked-icon" style="display: none;"></i>
                                                <i class="far fa-square me-2 unchecked-icon"></i>
                                                کاملاً آماده
                                            </label>
                                        </div>
                                        <div class="col-md-3 col-6">
                                            <input type="checkbox" class="btn-check" id="filter-points5" autocomplete="off" onchange="applyFilters()">
                                            <label class="btn btn-sm btn-outline-secondary w-100 py-2 d-flex align-items-center justify-content-center filter-btn-label" for="filter-points5">
                                                <i class="fas fa-check-square me-2 checked-icon" style="display: none;"></i>
                                                <i class="far fa-square me-2 unchecked-icon"></i>
                                                ۵ امتیازی
                                            </label>
                                        </div>
                                    </div>
                                    
                                </div>
                            </div>
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
        /* استایل کلی آکاردئون */
        .category-container {
            margin-bottom: 0.5rem;
        }

        .category-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 0.75rem 1rem;
            background-color: #f9fafb;
            border: 1px solid #e5e7eb;
            border-radius: 0.375rem;
            cursor: pointer;
            transition: all 0.2s ease;
            position: relative;
            user-select: none;
        }

        .category-item:hover {
            background-color: #f3f4f6;
            border-color: #d1d5db;
        }

        .category-item.expanded {
            background-color: #dbeafe;
            border-color: #3b82f6;
            color: #1d4ed8;
        }

        .category-item.expandable .arrow {
            transition: transform 0.3s ease;
        }

        .category-item.expandable.expanded .arrow {
            transform: rotate(90deg);
        }

        .category-text, .subcategory-text {
            flex-grow: 1;
            margin-right: 0.5rem;
            font-weight: 500;
            overflow: hidden;
            display: flex;
            flex-direction: column;
            line-height: 1.2;
        }

        .category-subtitle, .subcategory-subtitle {
            font-size: 0.75rem;
            color: #6b7280;
            font-weight: normal;
            margin-top: 2px;
        }

        .category-badges {
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .question-badge,
        .code-badge,
        .subcategory-badge,
        .subcategory-code {
            font-size: 0.75rem;
            padding: 0.25rem 0.5rem;
            border-radius: 0.25rem;
            font-weight: bold;
        }

        .question-badge {
            background-color: #dbeafe;
            color: #1d4ed8;
        }

        .code-badge {
            background-color: #f3f4f6;
            color: #4b5563;
        }

        .subcategory-badge {
            background-color: #fef3c7;
            color: #92400e;
        }

        .subcategory-code {
            background-color: #f5f5f5;
            color: #6b7280;
        }

        /* استایل زیرمجموعه‌ها (Subcategories) */
        .subcategories {
            margin-top: 0.5rem;
            padding-left: 1.5rem;
            max-height: 0;
            overflow: hidden;
            transition: max-height 0.3s ease, padding 0.3s ease;
            border-left: 3px solid #e5e7eb;
        }

        .subcategories.expanded {
            max-height: 2000px;
            /* مقدار بالایی برای انعطاف‌پذیری */
            padding-top: 0.5rem;
            padding-bottom: 0.5rem;
            border-left-color: #3b82f6;
        }

        .subcategory-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 0.5rem 1rem;
            background-color: #f9fafb;
            border: 1px solid #e5e7eb;
            border-radius: 0.375rem;
            cursor: pointer;
            transition: all 0.2s ease;
            margin-bottom: 0.25rem;
            user-select: none;
        }

        .subcategory-item:hover {
            background-color: #f3f4f6;
            border-color: #d1d5db;
        }

        .subcategory-item.selected {
            background-color: #dbeafe;
            border-color: #3b82f6;
            color: #1d4ed8;
        }

        .subcategory-badges {
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        /* استایل برای حالت انتخاب شده */
        .category-item.selected {
            background-color: #dbeafe;
            border-color: #3b82f6;
            color: #1d4ed8;
        }

        /* استایل برای افکت ریپل */
        .category-item,
        .subcategory-item {
            position: relative;
            overflow: hidden;
        }

        .category-item::after,
        .subcategory-item::after {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 0;
            height: 0;
            background: rgba(59, 130, 246, 0.2);
            border-radius: 50%;
            transform: scale(0);
            transition: transform 0.6s ease;
            pointer-events: none;
        }

        .category-item.ripple-effect::after,
        .subcategory-item.ripple-effect::after {
            width: 100%;
            height: 100%;
            transform: scale(1);
        }

        /* استایل برای تغییر جهت پیش‌فرض فونت فارسی */
        .category-text,
        .subcategory-text {
            direction: rtl;
            text-align: right;
        }

        /* استایل برای آیکون‌ها */
        .fas.arrow {
            font-size: 0.875rem;
            color: #6b7280;
            transition: transform 0.3s ease;
        }

        .fas.arrow:hover {
            color: #111827;
        }

        /* استایل برای کارت‌های دسته‌بندی */
        .category-card {
            background: white;
            border-radius: 0.5rem;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
            padding: 1rem;
            transition: transform 0.2s ease;
        }

        .category-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }

        .card-title {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 0.75rem 1rem;
            background: #f8fafc;
            border-radius: 0.375rem;
            margin-bottom: 1rem;
        }

        .card-title i.icon {
            font-size: 1.5rem;
            color: #6b7280;
        }

        .total-questions {
            font-weight: bold;
            font-size: 1.1rem;
            color: #111827;
        }

        /* استایل برای فوتر انتخاب */
        #categoryFooter {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 1rem;
            border-radius: 0.5rem 0.5rem 0 0;
            box-shadow: 0 -2px 10px rgba(0, 0, 0, 0.1);
            display: flex;
            justify-content: space-between;
            align-items: center;
            z-index: 1000;
        }

        .selected-category-info h4 {
            margin: 0;
            font-size: 1.2rem;
            font-weight: 600;
        }

        .selected-category-details {
            font-size: 0.9rem;
            opacity: 0.9;
        }

        .category-actions button {
            margin-left: 0.5rem;
            padding: 0.5rem 1rem;
            border-radius: 0.375rem;
            border: none;
            cursor: pointer;
            transition: all 0.2s ease;
        }

        .btn-select-questions {
            background: #10b981;
            color: white;
        }

        .btn-select-questions:hover {
            background: #059669;
        }

        .btn-clear {
            background: #ef4444;
            color: white;
        }

        .btn-clear:hover {
            background: #dc2626;
        }

        /* استایل پیام بدون انتخاب */
        .no-selection-message {
            background: #f3f4f6;
            color: #6b7280;
            padding: 1rem;
            border-radius: 0.5rem 0.5rem 0 0;
            text-align: center;
            display: flex;
            justify-content: center;
            align-items: center;
            z-index: 1000;
        }

        .message-content {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            font-size: 0.9rem;
        }

        .message-content i {
            color: #3b82f6;
        }

        /* انیمیشن خروج به پایین */
        .slide-out-down {
            animation: slideOutDown 0.3s forwards;
        }

        /* انیمیشن ورود از پایین */
        .slide-in-up {
            animation: slideInUp 0.3s forwards;
        }

        @keyframes slideOutDown {
            from {
                transform: translateY(0);
                opacity: 1;
            }

            to {
                transform: translateY(100%);
                opacity: 0;
            }
        }

        .no-selection-message.slide-in-up {
            animation: slideInUp 0.3s forwards;
        }

        .no-selection-message.slide-out-down {
            animation: slideOutDown 0.3s forwards;
        }

        #categoryFooter,
        .no-selection-message {
            position: fixed !important;
            bottom: 0 !important;
            right: 0;
            left: 0;
            z-index: 9999;
            display: none;
        }

        @keyframes slideInUp {
            from {
                transform: translateY(100%);
                opacity: 0;
            }

            to {
                transform: translateY(0);
                opacity: 1;
            }
        }

        /* Dark Mode Support */
        .dark-style .category-card {
            background-color: #283144;
            border: 1px solid #36445d;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.4);
        }

        .dark-style .card-title {
            background-color: rgba(90, 141, 238, 0.08);
            border-bottom: 1px solid #36445d;
        }

        .dark-style .total-questions {
            color: #d8deea;
        }

        .dark-style .category-item {
            background-color: #1c222f;
            border-color: #36445d;
            color: #cbd5e0;
        }

        .dark-style .category-item:hover {
            background-color: #283144;
            border-color: #4a5568;
        }

        .dark-style .subcategory-item {
            background-color: #1c222f;
            border-color: #36445d;
            color: #cbd5e0;
        }

        .dark-style .subcategory-item:hover {
            background-color: #283144;
            border-color: #4a5568;
        }

        .dark-style .category-item.expanded,
        .dark-style .category-item.selected,
        .dark-style .subcategory-item.selected {
            background-color: rgba(90, 141, 238, 0.16);
            border-color: #5a8dee;
            color: #5a8dee;
        }

        .dark-style .code-badge {
            background-color: #36445d;
            color: #a1b0cb;
        }

        .dark-style .subcategory-code {
            background-color: #36445d;
            color: #a1b0cb;
        }

        .dark-style .no-selection-message {
            background-color: #283144;
            color: #a1b0cb;
            border-top: 1px solid #36445d;
        }

        .dark-style .subcategories {
            border-left-color: #36445d;
        }

        .dark-style .subcategories.expanded {
            border-left-color: #5a8dee;
        }

        .dark-style h3 {
            color: #d8deea !important;
        }

        /* Custom styles for checkbox filter buttons */
        .btn-check:checked + .filter-btn-label .checked-icon {
            display: inline-block !important;
        }
        .btn-check:checked + .filter-btn-label .unchecked-icon {
            display: none !important;
        }
        .filter-btn-label {
            font-size: 0.85rem !important;
            font-weight: 500 !important;
            transition: all 0.2s ease !important;
            border-width: 1.5px !important;
        }
        .filter-container .col-md-4, .filter-container .col-6 {
            display: flex;
        }
        .filter-container label.btn {
            height: 100%;
            align-items: center;
            justify-content: center;
            text-align: center;
            border-radius: 0.375rem;
        }
        .filter-accordion-btn.has-active-filters {
            background-color: #e8f5e9 !important;
            color: #2e7d32 !important;
            font-weight: bold !important;
        }
        .dark-style .filter-accordion-btn.has-active-filters {
            background-color: rgba(46, 125, 50, 0.2) !important;
            color: #81c784 !important;
            font-weight: bold !important;
        }
    </style>

    <script>
        // Cookie helper functions
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

        // Global variables for selected category
        let selectedCategoryId = null;
        let selectedCategoryTitle = '';
        let selectedQuestionCount = 0;
        let selectedType = ''; // 'category' or 'subcategory'
        let loadedQuestions = [];

        // Initialize page
        document.addEventListener('DOMContentLoaded', function () {
            // Load saved category from cookie
            loadSavedCategory();

            // Initialize animations
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

        // Load saved category from cookie
        function loadSavedCategory() {
            const savedCategory = getCookie('selectedCategory');
            if (savedCategory) {
                try {
                    const categoryData = JSON.parse(decodeURIComponent(savedCategory));
                    selectedCategoryId = categoryData.id;
                    selectedCategoryTitle = categoryData.title;
                    selectedQuestionCount = categoryData.questionCount;
                    selectedType = categoryData.type;

                    updateFooterDisplay();
                } catch (e) {
                    console.error('خطا در بارگذاری دسته‌بندی ذخیره شده:', e);
                    deleteCookie('selectedCategory');
                }
            }
        }

        // Save category to cookie
        function saveCategoryToCookie() {
            const categoryData = {
                id: selectedCategoryId,
                title: selectedCategoryTitle,
                questionCount: selectedQuestionCount,
                type: selectedType
            };
            setCookie('selectedCategory', encodeURIComponent(JSON.stringify(categoryData)));
        }
        function updateFooterDisplay() {
            const footer = document.getElementById('categoryFooter');
            const noSelectionMessage = document.getElementById('noSelectionMessage');

            if (selectedCategoryId) {
                // اگر قبلاً footer نمایش داده شده بود، آن را خارج کن
                if (footer.style.display !== 'none' && !footer.classList.contains('slide-out-down')) {
                    footer.classList.add('slide-out-down');
                    setTimeout(() => {
                        // پس از خروج، محتوا را به‌روز کن و دوباره وارد کن
                        const titleElement = document.getElementById('selectedCategoryTitle');
                        const countElement = document.getElementById('selectedQuestionCount');
                        titleElement.textContent = selectedCategoryTitle;
                        countElement.textContent = selectedQuestionCount;

                        footer.style.display = 'flex';
                        footer.classList.remove('slide-out-down');
                        footer.classList.add('slide-in-up');

                        setTimeout(() => {
                            footer.classList.remove('slide-in-up');
                        }, 300);
                    }, 300);
                } else {
                    // اولین بار یا بعد از clear
                    const titleElement = document.getElementById('selectedCategoryTitle');
                    const countElement = document.getElementById('selectedQuestionCount');
                    titleElement.textContent = selectedCategoryTitle;
                    countElement.textContent = selectedQuestionCount;

                    noSelectionMessage.style.display = 'none';
                    footer.style.display = 'flex';
                    footer.classList.add('slide-in-up');
                    setTimeout(() => {
                        footer.classList.remove('slide-in-up');
                    }, 300);
                }
            } else {
                // حالت بدون انتخاب: footer را خارج کن و پیام را نشان بده
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

        // Select category function
        function selectCategory(id, title, questionCount, type) {
            selectedCategoryId = id;
            selectedCategoryTitle = title;
            selectedQuestionCount = questionCount;
            selectedType = type;

            updateFooterDisplay();
            saveCategoryToCookie();
        }

        // Clear selection
        function clearSelection() {
            selectedCategoryId = null;
            selectedCategoryTitle = '';
            selectedQuestionCount = 0;
            selectedType = '';

            updateFooterDisplay();
            deleteCookie('selectedCategory');

            closeAllAccordions();
        }

        // Open questions modal
        function openQuestionsModal() {
            if (!selectedCategoryId) return;

            const modalElement = document.getElementById('questionsModal');
            const modal = new bootstrap.Modal(modalElement);
            const modalTitle = document.getElementById('questionsModalLabel');

            modalTitle.textContent = `انتخاب سوالات - ${selectedCategoryTitle}`;

            // Show the modal
            modal.show();

            // Add event listeners to the modal element, not the Modal instance
            modalElement.addEventListener('shown.bs.modal', () => {
                document.getElementById('categoryFooter').hidden = true;;
            });

            modalElement.addEventListener('hidden.bs.modal', () => {
                document.getElementById('categoryFooter').hidden = false;;

            });

            loadQuestions();
        }
        // Load questions via AJAX
        // Load questions via AJAX
        function loadQuestions() {
            const container = document.getElementById('questionsContainer');

            // Show loading spinner
            container.innerHTML = `
                <div class="text-center p-4">
                    <div class="spinner-border text-primary" role="status">
                        <span class="visually-hidden">در حال بارگذاری...</span>
                    </div>
                    <div class="mt-2">در حال بارگذاری سوالات...</div>
                </div>
            `;

            // Reset all filter checkboxes and check 'filter-all'
            document.querySelectorAll('.btn-check').forEach(cb => {
                if (cb.id === 'filter-all') {
                    cb.checked = true;
                } else {
                    cb.checked = false;
                }
            });

            // Collapse accordion if open
            const collapseEl = document.getElementById('collapseFilters');
            if (collapseEl && collapseEl.classList.contains('show')) {
                const bsCollapse = bootstrap.Collapse.getInstance(collapseEl);
                if (bsCollapse) {
                    bsCollapse.hide();
                } else {
                    collapseEl.classList.remove('show');
                    const btn = document.querySelector('.filter-accordion-btn');
                    if (btn) {
                        btn.classList.add('collapsed');
                        btn.setAttribute('aria-expanded', 'false');
                    }
                }
            }

            // Reset accordion header
            const titleText = document.getElementById('accordionTitleText');
            const accordionBtn = document.querySelector('.filter-accordion-btn');
            if (titleText) titleText.textContent = 'فیلتر سوالات';
            if (accordionBtn) accordionBtn.classList.remove('has-active-filters');

            let url;
            if (selectedType === 'subcategory') {
                url = `pages/load_questions.php?subcategory_id=${selectedCategoryId}`;
            } else if (selectedType === 'special') {
                const parts = String(selectedCategoryId).split('_');
                const specialType = parts[0];
                url = `pages/load_questions.php?special_type=${specialType}`;
                if (parts.length > 1) {
                    url += `&tag_id=${parts[1]}`;
                }
            } else {
                url = `pages/load_questions.php?category_id=${selectedCategoryId}`;
            }

            fetch(url)
                .then(response => response.text())
                .then(data => {
                    container.innerHTML = data;
                    setupQuestionCheckboxes();
                    updateQuestionCounts();
                })
                .catch(error => {
                    console.error('خطا در بارگذاری سوالات:', error);
                    container.innerHTML = `
                        <div class="alert alert-danger text-center">
                            <i class="fas fa-exclamation-triangle"></i>
                            خطا در بارگذاری سوالات. لطفا دوباره تلاش کنید.
                        </div>
                    `;
                });
        }

        // Setup question checkboxes
        function setupQuestionCheckboxes() {
            const checkboxes = document.querySelectorAll('#questionsContainer input[type="checkbox"]');
            checkboxes.forEach(checkbox => {
                checkbox.addEventListener('change', updateQuestionCounts);
            });
        }

        // Update question counts
        function updateQuestionCounts() {
            const allCheckboxes = document.querySelectorAll('#questionsContainer input[type="checkbox"]');
            const allSelected = document.querySelectorAll('#questionsContainer input[type="checkbox"]:checked');

            const visibleCheckboxes = Array.from(allCheckboxes).filter(cb => {
                const item = cb.closest('.question-item');
                return item && item.style.display !== 'none';
            });
            const visibleSelected = visibleCheckboxes.filter(cb => cb.checked);

            document.getElementById('totalQuestionsCount').textContent = visibleCheckboxes.length;
            document.getElementById('selectedQuestionsCount').textContent = visibleSelected.length;
            document.getElementById('footerSelectedCount').textContent = allSelected.length;
        }

        // Select all questions
        function selectAllQuestions() {
            const checkboxes = document.querySelectorAll('#questionsContainer input[type="checkbox"]');
            checkboxes.forEach(checkbox => {
                const item = checkbox.closest('.question-item');
                if (item && item.style.display !== 'none') {
                    checkbox.checked = true;
                }
            });
            updateQuestionCounts();
        }

        // Deselect all questions
        function deselectAllQuestions() {
            const checkboxes = document.querySelectorAll('#questionsContainer input[type="checkbox"]');
            checkboxes.forEach(checkbox => {
                const item = checkbox.closest('.question-item');
                if (item && item.style.display !== 'none') {
                    checkbox.checked = false;
                }
            });
            updateQuestionCounts();
        }

        // Toggle master filter all
        function toggleFilterAll() {
            const filterAll = document.getElementById('filter-all');
            if (filterAll.checked) {
                // Uncheck all other filters
                document.querySelectorAll('.btn-check').forEach(cb => {
                    if (cb.id !== 'filter-all') {
                        cb.checked = false;
                    }
                });
            }
            applyFilters();
        }

        // Apply multiple question filters (combines filters with AND/OR logic)
        function applyFilters() {
            const filterAll = document.getElementById('filter-all').checked;
            const filterImage = document.getElementById('filter-image').checked;
            const filterVideo = document.getElementById('filter-video').checked;
            const filterIncorrect = document.getElementById('filter-incorrect').checked;
            const filterUnanswered = document.getElementById('filter-unanswered').checked;
            const filterHalfPrepared = document.getElementById('filter-half-prepared').checked;
            const filterPrepared = document.getElementById('filter-prepared').checked;
            const filterPoints5 = document.getElementById('filter-points5').checked;

            // If any specific filter is checked, uncheck "filter-all"
            const anySpecificActive = (filterImage || filterVideo || filterIncorrect || filterUnanswered || filterHalfPrepared || filterPrepared || filterPoints5);
            if (anySpecificActive && filterAll) {
                document.getElementById('filter-all').checked = false;
            }

            // If no specific filter is checked, check "filter-all"
            if (!anySpecificActive) {
                document.getElementById('filter-all').checked = true;
            }

            // Update accordion active filters state
            const titleText = document.getElementById('accordionTitleText');
            const accordionBtn = document.querySelector('.filter-accordion-btn');

            if (titleText && accordionBtn) {
                if (anySpecificActive) {
                    titleText.textContent = 'فیلتر سوالات (فیلتر فعال)';
                    accordionBtn.classList.add('has-active-filters');
                } else {
                    titleText.textContent = 'فیلتر سوالات';
                    accordionBtn.classList.remove('has-active-filters');
                }
            }

            const questionItems = document.querySelectorAll('.question-item');
            questionItems.forEach(item => {
                const points = parseInt(item.getAttribute('data-points') || '0', 10);
                const isImage = item.getAttribute('data-is-image') === '1';
                const isVideo = item.getAttribute('data-is-video') === '1';
                const status = item.getAttribute('data-status') || 'gray';

                // If "All Questions" is active, show everything
                if (document.getElementById('filter-all').checked) {
                    item.style.setProperty('display', 'flex', 'important');
                    return;
                }

                // 1. Type Filter (Image/Video)
                let matchesType = true;
                if (filterImage || filterVideo) {
                    matchesType = false;
                    if (filterImage && isImage) matchesType = true;
                    if (filterVideo && isVideo) matchesType = true;
                }

                // 2. Status Filter
                let matchesStatus = true;
                if (filterIncorrect || filterUnanswered || filterHalfPrepared || filterPrepared) {
                    matchesStatus = false;
                    if (filterIncorrect && status === 'red') matchesStatus = true;
                    if (filterUnanswered && status === 'gray') matchesStatus = true;
                    if (filterHalfPrepared && (status === 'yellow' || status === 'blue')) matchesStatus = true;
                    if (filterPrepared && status === 'green') matchesStatus = true;
                }

                // 3. Points Filter
                let matchesPoints = true;
                if (filterPoints5) {
                    matchesPoints = (points === 5);
                }

                // Combine conditions
                const show = matchesType && matchesStatus && matchesPoints;

                if (show) {
                    item.style.setProperty('display', 'flex', 'important');
                } else {
                    item.style.setProperty('display', 'none', 'important');
                }
            });

            updateQuestionCounts();
        }

        // Get selected question IDs (only counts visible questions)
        function getSelectedQuestionIds() {
            const selectedCheckboxes = document.querySelectorAll('#questionsContainer input[type="checkbox"]:checked');
            const visibleSelected = Array.from(selectedCheckboxes).filter(checkbox => {
                const item = checkbox.closest('.question-item');
                return item && item.style.display !== 'none';
            });
            return visibleSelected.map(checkbox => checkbox.id);
        }

        // Navigate to test page with selected questions
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

            // Create a form and submit it
            const form = document.createElement('form');
            form.method = 'POST';
            form.action = '../app/index.php';

            // Add selected questions as hidden inputs
            selectedQuestions.forEach(questionId => {
                const input = document.createElement('input');
                input.type = 'hidden';
                input.name = 'selected_questions[]';
                input.value = questionId;
                form.appendChild(input);
            });

            // Add mode parameter
            const modeInput = document.createElement('input');
            modeInput.type = 'hidden';
            modeInput.name = 'mode';
            modeInput.value = mode;
            form.appendChild(modeInput);

            // Add category info
            const categoryInput = document.createElement('input');
            categoryInput.type = 'hidden';
            if (selectedType === 'special') {
                categoryInput.name = 'special_type';
            } else {
                categoryInput.name = selectedType === 'subcategory' ? 'subcategory_id' : 'category_id';
            }
            categoryInput.value = selectedCategoryId;
            form.appendChild(categoryInput);

            document.body.appendChild(form);
            form.submit();
        }

        // Browse questions function
        function browseQuestions() {
            navigateToTest('browse');
        }

        // Start practice function
        function startPractice() {
            navigateToTest('practice');
        }

        // Function to close all accordions
        function closeAllAccordions() {
            document.querySelectorAll('.subcategories.expanded').forEach(sub => {
                sub.classList.remove('expanded');
            });
            document.querySelectorAll('.category-item.expanded').forEach(item => {
                item.classList.remove('expanded');
            });
        }

        // Add click functionality to expandable category items
        // Add click functionality to expandable category items
        document.querySelectorAll('.category-item.expandable').forEach(item => {
            item.addEventListener('click', function (e) {
                e.preventDefault();
                e.stopPropagation();

                const categoryId = this.getAttribute('data-category-id');
                const categoryTitle = this.getAttribute('data-category-title');
                const questionCount = parseInt(this.getAttribute('data-question-count'));
                const subcategoriesDiv = document.getElementById('sub-' + categoryId);

                // همیشه دسته‌بندی را انتخاب کن
                selectCategory(categoryId, categoryTitle, questionCount, 'category');

                // اگر زیرمجموعه وجود دارد، آکاردئون را toggle کن
                if (subcategoriesDiv) {
                    const isExpanded = subcategoriesDiv.classList.contains('expanded');

                    if (isExpanded) {
                        // بستن
                        subcategoriesDiv.classList.remove('expanded');
                        this.classList.remove('expanded');
                    } else {
                        // باز کردن (و بستن سایر آکاردئون‌ها)
                        closeAllAccordions();
                        subcategoriesDiv.classList.add('expanded');
                        this.classList.add('expanded');
                    }
                }
            });
        });
        // Add click functionality to subcategory items
        document.querySelectorAll('.subcategory-item').forEach(item => {
            item.addEventListener('click', function (e) {
                e.preventDefault();
                e.stopPropagation();

                const subcategoryId = this.getAttribute('data-subcategory-id');
                const subcategoryTitle = this.getAttribute('data-subcategory-title');
                const questionCount = parseInt(this.getAttribute('data-question-count'));

                selectCategory(subcategoryId, subcategoryTitle, questionCount, 'subcategory');
            });
        });

        // Add click functionality to non-expandable category items
        document.querySelectorAll('.category-item:not(.expandable)').forEach(item => {
            item.addEventListener('click', function () {
                const categoryId = this.getAttribute('data-category-id');
                const categoryTitle = this.getAttribute('data-category-title');
                const questionCount = parseInt(this.getAttribute('data-question-count'));

                selectCategory(categoryId, categoryTitle, questionCount, 'category');
            });
        });

        // Add click functionality to special category items
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

        // Add ripple effect to category items
        document.querySelectorAll('.category-item, .subcategory-item').forEach(item => {
            item.addEventListener('click', function (e) {
                const ripple = document.createElement('span');
                const rect = this.getBoundingClientRect();
                const size = Math.max(rect.width, rect.height);
                const x = e.clientX - rect.left - size / 2;
                const y = e.clientY - rect.top - size / 2;

                ripple.style.cssText = `
                    position: absolute;
                    border-radius: 50%;
                    background: rgba(59, 130, 246, 0.3);
                    transform: scale(0);
                    animation: ripple 0.6s linear;
                    width: ${size}px;
                    height: ${size}px;
                    left: ${x}px;
                    top: ${y}px;
                    pointer-events: none;
                `;

                this.style.position = 'relative';
                this.style.overflow = 'hidden';
                this.appendChild(ripple);

                setTimeout(() => {
                    ripple.remove();
                }, 600);
            });
        });

        // Add CSS for ripple animation
        const style = document.createElement('style');
        style.textContent = `
            @keyframes ripple {
                to {
                    transform: scale(4);
                    opacity: 0;
                }
            }
        `;
        document.head.appendChild(style);
    </script>

</div>
