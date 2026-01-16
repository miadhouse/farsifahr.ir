<?php
require_once __DIR__ . '/../../incloud/questions.php';

if (!isset($_POST['selected_questions'])) {
    die('پارامترهای لازم ارسال نشده‌اند');
}

// تبدیل به آرایه در صورت نیاز
$selectedQuestions = is_array($_POST['selected_questions']) 
    ? $_POST['selected_questions'] 
    : explode(',', $_POST['selected_questions']);

$questions = loadQuestions($pdo, $selectedQuestions);

if (empty($questions)) {
    echo '<div class="alert alert-warning">هیچ سوالی یافت نشد</div>';
} else { echo '<h5>'.count($selectedQuestions).'</h5>';
    foreach ($questions as $question): ?>
        <div class="form-check form-check-primary mt-3">
            <input class="form-check-input" type="checkbox" 
                   value="<?= htmlspecialchars($question['id']) ?>" 
                   id="customCheckPrimary<?= $question['id'] ?>" checked>
            <label class="form-check-label" for="customCheckPrimary<?= $question['id'] ?>">
                <?= htmlspecialchars($question['text']) ?>
            </label>
        </div>
    <?php endforeach;
}
?>