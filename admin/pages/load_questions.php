<?php
require_once __DIR__ . '/../../incloud/questions.php';

// Set content type for AJAX response
header('Content-Type: text/html; charset=utf-8');
$user_id = $_SESSION['user_id'];

try {
    // Initialize questions array
    $questions = [];

    // Get questions based on the request type
    if (isset($_GET['subcategory_id']) && $_GET['subcategory_id'] != null) {
        $questions = getQuestions($pdo, $cat2id = $_GET['subcategory_id'], $user_id);
        $categoryType = 'subcategory';
        $categoryId = $_GET['subcategory_id'];
    } elseif (isset($_GET['category_id']) && $_GET['category_id'] != null) {
        $questions = getRootCategoryQuestions($pdo, $cat2id = $_GET['category_id'], $user_id);
        $categoryType = 'category';
        $categoryId = $_GET['category_id'];
    } else {
        throw new Exception('نوع دسته‌بندی مشخص نشده است.');
    }

    // Check if questions were found
    if (empty($questions)) {
        echo '<div class="alert alert-warning text-center">';
        echo '<i class="fas fa-exclamation-triangle"></i>';
        echo '<div class="mt-2">هیچ سوالی برای این دسته‌بندی یافت نشد.</div>';
        echo '</div>';
        exit;
    }

    // Display questions count header
    echo '<div class="questions-header mb-3">';
    // echo '<div class="alert alert-info d-flex justify-content-between align-items-center">';
    // echo '<div>';
    // echo '<i class="fas fa-list-ul me-2"></i>';
    // echo '<strong>' . count($questions) . '</strong> سوال یافت شد';
    // echo '</div>';
    // echo '<div class="text-muted small">همه سوالات به صورت پیش‌فرض انتخاب شده‌اند</div>';
    // echo '</div>';
    echo '</div>';

    // Display questions with checkboxes
    echo '<div class="questions-list">';

    foreach ($questions as $index => $question) {
        $questionNumber = $index + 1;
        $questionId = htmlspecialchars($question['id']);
        $questionText = htmlspecialchars($question['text']);

        echo '<div class="form-check modal-bg form-check-primary mt-3 question-item d-flex align-items-center justify-content-between">';
        echo '<div>';
        echo '<input style="margin-right: -1.4em;" class="form-check-input " type="checkbox" value="' . $questionId . '" id="' . $questionId . '" checked>';
        echo '<label class="form-check-label" for="' . $questionId . '">';
        echo '<div class="question-content">';
        echo '<div class="question-text fs-6 small-tex text-bg-darkt">' . $questionText . '</div>';
        echo '</div>';
        echo '</label>';
        echo '</div>';
        echo '<button type="button" class="btn btn-sm btn-outline-info bot-fetch-btn" data-q-id="' . $questionId . '" title="دریافت اطلاعات از سایت آلمانی">';
        echo '<i class="fas fa-robot"></i>';
        echo '</button>';
        echo '</div>';
    }

    echo '</div>';

} catch (Exception $e) {
    // Display error message
    echo '<div class="alert alert-danger text-center">';
    echo '<i class="fas fa-exclamation-circle me-2"></i>';
    echo '<strong>خطا:</strong> ' . htmlspecialchars($e->getMessage());
    echo '</div>';
}
?>

<style>
    .questions-header {
        position: sticky;
        top: 0;
        z-index: 10;
    }

    .question-item {
        border: 1px solid #e9ecef;
        border-radius: 8px;
        padding: 15px;
        margin-bottom: 10px;
        transition: all 0.2s ease;
    }

    .question-item:hover {
        background-color: #f8f9fa;
        border-color: #0d6efd;
        box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
    }

    .question-item:has(input:checked) {
        background-color: #86b7fe;
        border-color: #0d6efd;
    }

    .question-content {
        display: flex;
        align-items: flex-start;
        gap: 10px;
        width: 100%;
    }

    .question-number {
        flex-shrink: 0;
        margin-top: 2px;
    }

    .question-text {
        flex: 1;
        line-height: 1.6;
        font-size: 0.95rem;
        color: #495057;
    }

    .form-check-input {
        margin-top: 4px;
        margin-left: 10px;
    }

    .form-check-input:checked {
        background-color: #0d6efd;
        border-color: #0d6efd;
    }

    .form-check-input:focus {
        border-color: #86b7fe;
        box-shadow: 0 0 0 0.25rem rgba(13, 110, 253, 0.25);
    }

    .form-check-label {
        cursor: pointer;
        width: 100%;
        margin-bottom: 0;
    }

    .questions-list {
        max-height: none;
    }

    /* RTL Support */
    [dir="rtl"] .question-content {
        text-align: right;
    }

    [dir="rtl"] .form-check-input {
        margin-right: 10px;
        margin-left: 0;
    }

    /* Responsive Design */
    @media (max-width: 768px) {
        .question-item {
            padding: 12px;
        }

        .question-text {
            font-size: 0.9rem;
        }

        .question-number .badge {
            font-size: 0.75rem;
        }
    }

    /* Animation for newly loaded content */
    @keyframes fadeInUp {
        from {
            opacity: 0;
            transform: translateY(20px);
        }

        to {
            opacity: 1;
            transform: translateY(0);
        }
    }

    .question-item {
        animation: fadeInUp 0.3s ease forwards;
    }

    .question-item:nth-child(even) {
        animation-delay: 0.1s;
    }

    .question-item:nth-child(odd) {
        animation-delay: 0.05s;
    }
</style>

<script>
    // Add some interactive behavior for the loaded questions
    $(document).ready(function () {
        $('.bot-fetch-btn').on('click', function(e) {
            e.preventDefault();
            const btn = $(this);
            const questionId = btn.data('q-id');
            const originalIcon = btn.html();

            Swal.fire({
                title: 'در حال دریافت اطلاعات...',
                text: 'لطفا منتظر بمانید، ربات در حال جستجو در سایت Führerschein-bestehen است.',
                allowOutsideClick: false,
                didOpen: () => {
                    Swal.showLoading();
                }
            });

            btn.html('<i class="fas fa-spinner fa-spin"></i>').prop('disabled', true);

            $.ajax({
                url: '../incloud/fetch_question_info.php',
                type: 'POST',
                data: {
                    action: 'bot_fetch',
                    question_id: questionId
                },
                success: function(response) {
                    btn.html(originalIcon).prop('disabled', false);
                    if (response.success) {
                        Swal.fire({
                            icon: 'success',
                            title: 'موفقیت‌آمیز',
                            text: response.message,
                            confirmButtonText: 'عالیه'
                        });
                        btn.removeClass('btn-outline-info').addClass('btn-success');
                    } else {
                        Swal.fire({
                            icon: 'error',
                            title: 'خطا',
                            text: response.message,
                            confirmButtonText: 'باشه'
                        });
                    }
                },
                error: function() {
                    btn.html(originalIcon).prop('disabled', false);
                    Swal.fire({
                        icon: 'error',
                        title: 'خطا',
                        text: 'ایرادی در برقراری ارتباط با سرور پیش آمد.',
                        confirmButtonText: 'باشه'
                    });
                }
            });
        });
    });
</script>