<?php
// get_dashboard_stats.php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../incloud/user-config-handler.php';

header('Content-Type: application/json');

// بررسی CSRF token
if (!isset($_POST['csrf_token']) || empty($_POST['csrf_token'])) {
    echo json_encode([
        'success' => false,
        'message' => 'توکن امنیتی ارسال نشده است'
    ]);
    exit;
}

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'error' => 'کاربر وارد نشده است']);
    exit;
}

$userId = $_SESSION['user_id'];

try {
    // دریافت تنظیمات کاربر
    $configHandler = new UserConfigHandler($pdo);
    $userConfig = $configHandler->getUserConfig($userId);

    // بررسی اینکه آیا کاربر تنظیمات را انجام داده است
    if (!$userConfig) {
        echo json_encode([
            'success' => false,
            'error' => 'لطفاً ابتدا تنظیمات خود را تکمیل کنید',
            'needs_config' => true
        ]);
        exit;
    }

    // تعیین available بر اساس exam_date_type
    // before = available IN (0, 1)
    // after = available IN (0, 2)
    $availableFilter = ($userConfig['exam_date_type'] === 'before') ? 1 : 2;

    // محاسبه کل سوالات موجود بر اساس فیلترها
    // سوالات با available = 0 برای هر دو حالت نمایش داده می‌شوند
    $stmt = $pdo->prepare("
        SELECT COUNT(*) as total_questions 
        FROM questions 
        WHERE available = 0 OR available = ?
    ");
    $stmt->execute([$availableFilter]);
    $totalQuestions = $stmt->fetch(PDO::FETCH_ASSOC)['total_questions'];

    // محاسبه آمار کلی با فیلتر بر اساس تنظیمات کاربر
    $stmt = $pdo->prepare("
        SELECT 
            COUNT(*) as total_answered,
            SUM(CASE WHEN uqs.correct >= 2 AND uqs.incorrect = 0 THEN 1 ELSE 0 END) as green_count,
            SUM(CASE WHEN uqs.correct = 1 AND uqs.incorrect = 0 THEN 1 ELSE 0 END) as blue_count,
            SUM(CASE WHEN (uqs.correct > 0 AND uqs.incorrect > 0) OR (uqs.correct = 1 AND uqs.incorrect >= 1) THEN 1 ELSE 0 END) as yellow_count,
            SUM(CASE WHEN (uqs.incorrect >= 2 AND uqs.correct = 0) OR (uqs.incorrect = 1 AND uqs.correct = 0) THEN 1 ELSE 0 END) as red_count
        FROM user_question_stats uqs
        INNER JOIN questions q ON uqs.question_id = q.id
        WHERE uqs.user_id = ? 
        AND (q.available = 0 OR q.available = ?)
    ");
    $stmt->execute([$userId, $availableFilter]);
    $stats = $stmt->fetch(PDO::FETCH_ASSOC);

    // محاسبه سوالات پاسخ داده نشده
    $notAnswered = $totalQuestions - (int) $stats['total_answered'];

    // محاسبه درصد آمادگی
    // سبز = 100% آماده، آبی = 50% آماده، زرد = 25% آماده، قرمز و خاکستری = 0% آماده
    $readyScore =
        ((int) $stats['green_count'] * 100) +
        ((int) $stats['blue_count'] * 50) +
        ((int) $stats['yellow_count'] * 25);

    $readinessPercentage = $totalQuestions > 0 ? round(($readyScore / ($totalQuestions * 100)) * 100) : 0;

    // آمار هفتگی - سوالات پاسخ داده شده در 7 روز گذشته
    $stmt = $pdo->prepare("
        SELECT COUNT(DISTINCT uqs.question_id) as week_count
        FROM user_question_stats uqs
        INNER JOIN questions q ON uqs.question_id = q.id
        WHERE uqs.user_id = ? 
        AND (q.available = 0 OR q.available = ?)
        AND uqs.updated_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)
    ");
    $stmt->execute([$userId, $availableFilter]);
    $weekStats = $stmt->fetch(PDO::FETCH_ASSOC);

    // آمار هفته قبل (7 تا 14 روز قبل)
    $stmt = $pdo->prepare("
        SELECT COUNT(DISTINCT uqs.question_id) as last_week_count
        FROM user_question_stats uqs
        INNER JOIN questions q ON uqs.question_id = q.id
        WHERE uqs.user_id = ? 
        AND (q.available = 0 OR q.available = ?)
        AND uqs.updated_at >= DATE_SUB(NOW(), INTERVAL 14 DAY)
        AND uqs.updated_at < DATE_SUB(NOW(), INTERVAL 7 DAY)
    ");
    $stmt->execute([$userId, $availableFilter]);
    $lastWeekStats = $stmt->fetch(PDO::FETCH_ASSOC);

    // محاسبه درصد بهبود
    $thisWeek = (int) $weekStats['week_count'];
    $lastWeek = (int) $lastWeekStats['last_week_count'];
    $improvement = $lastWeek > 0 ? round((($thisWeek - $lastWeek) / $lastWeek) * 100) : ($thisWeek > 0 ? 100 : 0);

    // آمار ماهانه - برای نمودار (30 روز گذشته)
    $stmt = $pdo->prepare("
        SELECT 
            DATE_FORMAT(uqs.updated_at, '%Y-%m-%d') as date,
            COUNT(DISTINCT uqs.question_id) as count
        FROM user_question_stats uqs
        INNER JOIN questions q ON uqs.question_id = q.id
        WHERE uqs.user_id = ? 
        AND (q.available = 0 OR q.available = ?)
        AND uqs.updated_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
        GROUP BY DATE_FORMAT(uqs.updated_at, '%Y-%m-%d')
        ORDER BY date ASC
    ");
    $stmt->execute([$userId, $availableFilter]);
    $monthlyData = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // آماده‌سازی داده‌های نمودار ماهانه
    // ایجاد آرایه کامل 30 روزه با مقادیر صفر
    $chartLabels = [];
    $chartData = [];

    for ($i = 29; $i >= 0; $i--) {
        $date = date('Y-m-d', strtotime("-$i days"));
        $chartLabels[] = date('d M', strtotime($date));
        $chartData[] = 0; // مقدار پیش‌فرض صفر
    }

    // پر کردن داده‌های واقعی
    foreach ($monthlyData as $row) {
        $dateKey = date('d M', strtotime($row['date']));
        $index = array_search($dateKey, $chartLabels);
        if ($index !== false) {
            $chartData[$index] = (int) $row['count'];
        }
    }

    // خروجی نهایی
    echo json_encode([
        'success' => true,
        'data' => [
            'total_questions' => (int) $totalQuestions,
            'total_answered' => (int) $stats['total_answered'],
            'not_answered' => $notAnswered,
            'green_count' => (int) $stats['green_count'],
            'blue_count' => (int) $stats['blue_count'],
            'yellow_count' => (int) $stats['yellow_count'],
            'red_count' => (int) $stats['red_count'],
            'readiness_percentage' => $readinessPercentage,
            'this_week' => $thisWeek,
            'last_week' => $lastWeek,
            'improvement' => $improvement,
            'monthly_chart' => [
                'labels' => $chartLabels,
                'data' => $chartData
            ],
            'config' => [
                'exam_date_type' => $userConfig['exam_date_type'],
            ]
        ]
    ]);

} catch (PDOException $e) {
    echo json_encode(['success' => false, 'error' => 'خطا در دیتابیس: ' . $e->getMessage()]);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => 'خطا: ' . $e->getMessage()]);
}
?>