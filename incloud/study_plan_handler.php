<?php
// incloud/study_plan_handler.php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/functions.php';

header('Content-Type: application/json');

$action = $_POST['action'] ?? '';

switch ($action) {
    case 'calculate':
        echo json_encode(calculate_plan_logic($_POST));
        break;
    case 'save':
        handle_save_plan($pdo);
        break;
    case 'get_user_plan':
        handle_get_user_plan($pdo);
        break;
    case 'delete':
        handle_delete_plan($pdo);
        break;
    default:
        echo json_encode(['success' => false, 'message' => __('invalid_operation', 'عملیات نامعتبر')]);
}

function calculate_plan_logic($data) {
    $level = $data['german_level'] ?? 'b1';
    $percent = intval($data['previous_study_percent'] ?? 0);
    $daily_hours = intval($data['daily_hours'] ?? 1);
    $study_days_count = intval($data['study_days_count'] ?? 1);

    if ($study_days_count < 1) $study_days_count = 1;

    $multipliers = [
        'none' => 0.5,
        'a1' => 0.6,
        'a2' => 0.8,
        'b1' => 1.0,
        'b2' => 1.2,
        'c1' => 1.5,
        'c2' => 2.0
    ];

    $multiplier = $multipliers[$level] ?? 1.0;
    
    // پارامترهای جدید برای نگاشت دقیق بین ۱ ماه تا ۱ سال
    // حداقل توان مطالعه: سطح ضعیف، ۱ ساعت در روز، ۱ روز در هفته = 0.5 * 1 * 1 = 0.5
    // حداکثر توان مطالعه: سطح عالی، ۸ ساعت در روز، ۷ روز در هفته = 2.0 * 8 * 7 = 112
    $study_power = $multiplier * $daily_hours * $study_days_count;
    
    $min_power = 0.5;
    $max_power = 112.0;
    
    // محدود کردن توان برای اطمینان از قرارگیری در بازه
    $clamped_power = max($min_power, min($max_power, $study_power));
    
    // محاسبه روزهای تقویمی برای شروع از صفر (نگاشت غیرخطی برای تعادل بهتر)
    // فرمول: days = 460 / (power ^ 0.58)
    // این فرمول باعث می‌شود سطح B1 با ۲ ساعت مطالعه در ۷ روز هفته حدود ۱۰۰ روز (۳.۳ ماه) شود.
    $mapped_days_at_zero = 460 / pow($clamped_power, 0.58);
    
    // محدود کردن به بازه ۳۰ تا ۳۶۵ روز برای شروع از صفر
    $mapped_days_at_zero = max(30, min(365, $mapped_days_at_zero));
    
    // اعمال درصد پیشرفت کاربر
    $remaining_factor = 1 - ($percent / 100);
    $final_calendar_days = max(1, round($mapped_days_at_zero * $remaining_factor));
    
    // مشتق کردن سایر مقادیر
    $calendar_weeks = ceil($final_calendar_days / 7);
    $days_of_study_needed = ceil($final_calendar_days * ($study_days_count / 7));
    $total_hours_needed = $days_of_study_needed * $daily_hours;

    return [
        'success' => true,
        'hours_total' => round($total_hours_needed, 1),
        'days_study' => $days_of_study_needed,
        'weeks' => $calendar_weeks,
        'calendar_days' => $final_calendar_days
    ];
}

function handle_save_plan($pdo) {
    $is_logged_in = is_logged_in();
    $user_id = $_SESSION['user_id'] ?? null;
    
    // اگر لاگین نیست، ثبت‌نام انجام شود
    if (!$is_logged_in) {
        $name = trim($_POST['name'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';
        
        if (empty($email) || empty($password)) {
            echo json_encode(['success' => false, 'message' => __('enter_email_password', 'لطفا ایمیل و رمز عبور را وارد کنید')]);
            return;
        }

        // بررسی تکراری نبودن ایمیل
        $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
        $stmt->execute([$email]);
        if ($stmt->rowCount() > 0) {
            echo json_encode(['success' => false, 'message' => __('email_already_registered', 'این ایمیل قبلا ثبت شده است. لطفا لاگین کنید.')]);
            return;
        }

        // ثبت کاربر
        try {
            $hashed_password = hash_password($password);
            $stmt = $pdo->prepare("INSERT INTO users (name, email, password, role, email_verified) VALUES (?, ?, ?, 'user', 1)");
            $stmt->execute([$name ?: __('new_user', 'کاربر جدید'), $email, $hashed_password]);
            $user_id = $pdo->lastInsertId();
            
            // لاگین خودکار
            $_SESSION['user_id'] = $user_id;
            $_SESSION['email'] = $email;
            $_SESSION['name'] = $name ?: __('new_user', 'کاربر جدید');
            $_SESSION['role'] = 'user';
            $_SESSION['logged_in'] = true;
            save_session($user_id, $pdo);

            // اطلاع رسانی تلگرام ثبت نام
            $telegram_title = __('new_user_study_plan', 'کاربر جدید (از طریق فرم برنامه مطالعه)');
            $telegram_message = "🆕 <b>{$telegram_title}</b>\n\n👤 نام: {$name}\n📧 ایمیل: {$email}";
            send_telegram_admin_message($telegram_message);

        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => __('registration_error', 'خطا در ثبت نام: ') . $e->getMessage()]);
            return;
        }
    }

    // ذخیره برنامه
    $level = $_POST['german_level'];
    $percent = intval($_POST['previous_study_percent']);
    $daily_hours = intval($_POST['daily_hours']);
    $study_days = $_POST['study_days']; // Comma separated
    $study_days_count = count(explode(',', $study_days));
    
    $calc = calculate_plan_logic([
        'german_level' => $level,
        'previous_study_percent' => $percent,
        'daily_hours' => $daily_hours,
        'study_days_count' => $study_days_count
    ]);

    try {
        // حذف برنامه قبلی
        $stmt = $pdo->prepare("DELETE FROM study_plans WHERE user_id = ?");
        $stmt->execute([$user_id]);

        // درج برنامه جدید
        $stmt = $pdo->prepare("INSERT INTO study_plans (user_id, german_level, previous_study_percent, daily_hours, study_days, estimated_total_days) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->execute([$user_id, $level, $percent, $daily_hours, $study_days, $calc['calendar_days']]);

        // اطلاع رسانی تلگرام
        $user_email = $_SESSION['email'];
        $telegram_title = __('study_plan_registered_telegram', 'برنامه مطالعه جدید ثبت شد');
        $telegram_message = "📅 <b>{$telegram_title}</b>\n\n👤 کاربر: {$user_email}\n🇩🇪 سطح آلمان: {$level}\n⏱ زمان روزانه: {$daily_hours} ساعت\n🗓 تخمین کل: {$calc['calendar_days']} روز";
        send_telegram_admin_message($telegram_message);

        // ارسال ایمیل
        require_once __DIR__ . '/mail-functions.php';
        
        $day_map = [
            'Sat' => __('sat', 'شنبه'), 'Sun' => __('sun', 'یکشنبه'), 'Mon' => __('mon', 'دوشنبه'),
            'Tue' => __('tue', 'سه‌شنبه'), 'Wed' => __('wed', 'چهارشنبه'), 'Thu' => __('thu', 'پنجشنبه'), 'Fri' => __('fri', 'جمعه')
        ];
        $persian_days = array_map(fn($d) => $day_map[trim($d)] ?? $d, explode(',', $study_days));
        $separator = get_current_lang() === 'fa' ? '، ' : ', ';
        $persian_days_str = implode($separator, $persian_days);

        send_study_plan_email($user_email, $_SESSION['name'], [
            'calendar_days' => $calc['calendar_days'],
            'daily_hours' => $daily_hours,
            'study_days' => $persian_days_str,
            'german_level' => $level,
            'weeks' => $calc['weeks']
        ]);

        echo json_encode(['success' => true, 'message' => __('study_plan_saved_successfully', 'برنامه مطالعه شما با موفقیت ذخیره شد. یک نسخه نیز برای شما ایمیل شد.'), 'redirect' => 'admin']);
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => __('save_plan_error', 'خطا در ذخیره برنامه: ') . $e->getMessage()]);
    }
}

function handle_get_user_plan($pdo) {
    if (!is_logged_in()) {
        echo json_encode(['success' => false]);
        return;
    }
    $stmt = $pdo->prepare("SELECT * FROM study_plans WHERE user_id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $plan = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($plan) {
        echo json_encode(['success' => true, 'plan' => $plan]);
    } else {
        echo json_encode(['success' => false]);
    }
}

function handle_delete_plan($pdo) {
    if (!is_logged_in()) {
        echo json_encode(['success' => false, 'message' => __('please_login_first', 'لطفا ابتدا لاگین کنید')]);
        return;
    }
    try {
        $stmt = $pdo->prepare("DELETE FROM study_plans WHERE user_id = ?");
        $stmt->execute([$_SESSION['user_id']]);
        echo json_encode(['success' => true, 'message' => __('study_plan_deleted_successfully', 'برنامه مطالعه شما با موفقیت حذف شد.')]);
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => __('delete_plan_error', 'خطا در حذف برنامه: ') . $e->getMessage()]);
    }
}
