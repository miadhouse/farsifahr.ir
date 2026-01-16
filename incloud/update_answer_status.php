<?php
//update_answer_status.php
require_once __DIR__ . '/../config/config.php';

header('Content-Type: application/json');

// بررسی CSRF token
if (!isset($_POST['csrf_token']) || empty($_POST['csrf_token'])) {
    echo json_encode([
        'success' => false,
        'message' => 'توکن امنیتی ارسال نشده است'
    ]);
    exit;
}

if (!isset($_POST['question_id']) || !isset($_POST['is_correct']) || !isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'error' => 'پارامترهای لازم ارسال نشده‌اند']);
    exit;
}

$questionId = (int) $_POST['question_id'];
$isCorrect = (bool) $_POST['is_correct'];
$userId = $_SESSION['user_id'];

try {
    // بررسی وجود رکورد قبلی
    $stmt = $pdo->prepare("SELECT correct, incorrect FROM user_question_stats WHERE user_id = ? AND question_id = ?");
    $stmt->execute([$userId, $questionId]);
    $existing = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($existing) {
        // آپدیت رکورد موجود
        $currentCorrect = (int) $existing['correct'];
        $currentIncorrect = (int) $existing['incorrect'];

        if ($isCorrect) {
            // پاسخ صحیح
            $newCorrect = min(2, $currentCorrect + 1);

            // اگر دو بار متوالی صحیح شد، incorrect را صفر می‌کنیم
            if ($newCorrect >= 2) {
                $newIncorrect = 0;
            } else {
                // اگر یک بار صحیح شد، incorrect را کم می‌کنیم (حداقل 0)
                $newIncorrect = max(0, $currentIncorrect - 1);
            }
        } else {
            // پاسخ نادرست
            $newIncorrect = min(2, $currentIncorrect + 1);

            // اگر دو بار متوالی غلط شد، correct را صفر می‌کنیم
            if ($newIncorrect >= 2) {
                $newCorrect = 0;
            } else {
                // اگر یک بار غلط شد، correct را دست نخورده نگه می‌داریم
                // تا ترکیب صحیح و غلط (زرد) نشان داده شود
                $newCorrect = $currentCorrect;
            }
        }

        // updated_at به صورت خودکار توسط دیتابیس به‌روز می‌شود
        $stmt = $pdo->prepare("UPDATE user_question_stats SET correct = ?, incorrect = ? WHERE user_id = ? AND question_id = ?");
        $stmt->execute([$newCorrect, $newIncorrect, $userId, $questionId]);

    } else {
        // ایجاد رکورد جدید
        if ($isCorrect) {
            $newCorrect = 1;
            $newIncorrect = 0;
        } else {
            $newCorrect = 0;
            $newIncorrect = 1;
        }

        $stmt = $pdo->prepare("INSERT INTO user_question_stats (user_id, question_id, correct, incorrect, updated_at) VALUES (?, ?, ?, ?, NOW())");
        $stmt->execute([$userId, $questionId, $newCorrect, $newIncorrect]);
    }

    // بازگردانی وضعیت جدید
    $stmt = $pdo->prepare("SELECT correct, incorrect FROM user_question_stats WHERE user_id = ? AND question_id = ?");
    $stmt->execute([$userId, $questionId]);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);

    // تعیین رنگ بر اساس وضعیت
    $color = getQuestionColor($result['correct'], $result['incorrect']);

    echo json_encode([
        'success' => true,
        'correct' => (int) $result['correct'],
        'incorrect' => (int) $result['incorrect'],
        'color' => $color
    ]);

} catch (PDOException $e) {
    echo json_encode(['success' => false, 'error' => 'خطا در دیتابیس: ' . $e->getMessage()]);
}

function getQuestionColor($correct, $incorrect)
{
    $correct = (int) $correct;
    $incorrect = (int) $incorrect;

    // اگر هیچ پاسخی داده نشده
    if ($correct == 0 && $incorrect == 0) {
        return 'gray'; // خاکستری
    }

    // اگر دو بار متوالی صحیح پاسخ داده شده
    if ($correct >= 2 && $incorrect == 0) {
        return 'green'; // سبز
    }

    // اگر یک بار صحیح پاسخ داده شده (بدون غلط)
    if ($correct == 1 && $incorrect == 0) {
        return 'blue'; // آبی
    }

    // اگر دو بار متوالی غلط پاسخ داده شده
    if ($incorrect >= 2 && $correct == 0) {
        return 'red'; // قرمز
    }

    // اگر یک بار غلط پاسخ داده شده (بدون صحیح)
    if ($incorrect == 1 && $correct == 0) {
        return 'red'; // قرمز
    }

    // در سایر حالات (ترکیب صحیح و غلط)
    return 'yellow'; // زرد
}
?>