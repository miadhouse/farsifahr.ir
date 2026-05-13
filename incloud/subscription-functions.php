<?php
// subscription-functions.php
require_once(__DIR__ . '/../config/config.php');

/**
 * دریافت اطلاعات پلن اشتراک
 */
function get_subscription_plan($plan_id, $pdo) {
    $stmt = $pdo->prepare("SELECT * FROM subscription_plans WHERE id = ? AND is_active = 1");
    $stmt->execute([$plan_id]);
    return $stmt->fetch();
}

/**
 * دریافت تمام پلن‌های اشتراک فعال
 */
function get_all_subscription_plans($pdo) {
    $stmt = $pdo->prepare("SELECT * FROM subscription_plans WHERE is_active = 1 ORDER BY sort_order ASC");
    $stmt->execute();
    return $stmt->fetchAll();
}

/**
 * دریافت اشتراک فعال کاربر
 */
function get_user_active_subscription($user_id, $pdo) {
    $stmt = $pdo->prepare("
        SELECT us.*, sp.id as plan_id, sp.name as plan_name, sp.description as plan_description, sp.slug as plan_slug
        FROM user_subscriptions us
        JOIN subscription_plans sp ON us.plan_id = sp.id
        WHERE us.user_id = ? AND us.status = 'active'
        AND (us.expires_at IS NULL OR us.expires_at > NOW())
        ORDER BY us.created_at DESC
        LIMIT 1
    ");
    $stmt->execute([$user_id]);
    return $stmt->fetch();
}

/**
 * دریافت اشتراک در انتظار کاربر
 */
function get_user_pending_subscription($user_id, $pdo) {
    $stmt = $pdo->prepare("
        SELECT us.*, sp.id as plan_id, sp.name as plan_name, sp.description as plan_description, sp.slug as plan_slug
        FROM user_subscriptions us
        JOIN subscription_plans sp ON us.plan_id = sp.id
        WHERE us.user_id = ? AND us.status = 'pending'
        ORDER BY us.created_at DESC
        LIMIT 1
    ");
    $stmt->execute([$user_id]);
    return $stmt->fetch();
}

/**
 * بررسی آیا کاربر VIP است
 */
function is_user_vip($user_id, $pdo) {
    $subscription = get_user_active_subscription($user_id, $pdo);
    if (!$subscription) {
        return false;
    }
    
    // بررسی slug پلن - اگر vip باشد، کاربر VIP است
    return $subscription['plan_slug'] === 'vip';
}

/**
 * دریافت محدودیت سوالات کاربر
 * Free: 200 سوال
 * VIP: نامحدود
 */
function get_user_question_limit($user_id, $pdo) {
    if (is_user_vip($user_id, $pdo)) {
        return null; // نامحدود
    }
    return 200; // پلن رایگان
}

/**
 * بررسی دسترسی کاربر به سوال
 */
function can_access_question($user_id, $question_number, $pdo) {
    $limit = get_user_question_limit($user_id, $pdo);
    
    // اگر نامحدود باشد (VIP)
    if ($limit === null) {
        return true;
    }
    
    // بررسی محدودیت برای پلن رایگان
    return $question_number <= $limit;
}

/**
 * ایجاد اشتراک جدید برای کاربر
 */
function create_user_subscription($user_id, $plan_id, $duration_days, $amount_paid, $pdo, $payment_method = null, $transaction_id = null, $status = 'active') {
    try {
        $pdo->beginTransaction();
        
        // محاسبه تاریخ انقضا
        $expires_at = null;
        if ($duration_days > 0) {
            $expires_at = date('Y-m-d H:i:s', strtotime("+{$duration_days} days"));
        }
        
        // لغو تمام اشتراک‌های قبلی فعال و معلق
        $stmt = $pdo->prepare("
            UPDATE user_subscriptions 
            SET status = 'cancelled', updated_at = NOW() 
            WHERE user_id = ? AND status IN ('active', 'pending')
        ");
        $stmt->execute([$user_id]);
        
        // ایجاد اشتراک جدید
        $stmt = $pdo->prepare("
            INSERT INTO user_subscriptions 
            (user_id, plan_id, expires_at, duration_days, amount_paid, payment_method, transaction_id, status, created_at) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW())
        ");
        $stmt->execute([$user_id, $plan_id, $expires_at, $duration_days, $amount_paid, $payment_method, $transaction_id, $status]);
        
        $subscription_id = $pdo->lastInsertId();
        
        // فقط اگر اشتراک active باشد، current_plan_id را آپدیت کن
        if ($status === 'active') {
            $stmt = $pdo->prepare("UPDATE users SET current_plan_id = ? WHERE id = ?");
            $stmt->execute([$plan_id, $user_id]);
        }
        
        $pdo->commit();
        return $subscription_id;
        
    } catch (Exception $e) {
        $pdo->rollback();
        error_log("Error creating subscription: " . $e->getMessage());
        return false;
    }
}

/**
 * لغو اشتراک کاربر و تغییر به پلن رایگان
 */
function cancel_user_subscription($user_id, $pdo) {
    try {
        $pdo->beginTransaction();
        
        // لغو تمام اشتراک‌های فعال و معلق
        $stmt = $pdo->prepare("
            UPDATE user_subscriptions 
            SET status = 'cancelled', updated_at = NOW() 
            WHERE user_id = ? AND status IN ('active', 'pending')
        ");
        $stmt->execute([$user_id]);
        
        // دریافت ID پلن رایگان
        $stmt = $pdo->prepare("SELECT id FROM subscription_plans WHERE slug = 'free' LIMIT 1");
        $stmt->execute();
        $free_plan = $stmt->fetch();
        
        if (!$free_plan) {
            throw new Exception("پلن رایگان یافت نشد");
        }
        
        // تغییر به پلن رایگان
        $stmt = $pdo->prepare("UPDATE users SET current_plan_id = ? WHERE id = ?");
        $stmt->execute([$free_plan['id'], $user_id]);
        
        // ایجاد اشتراک رایگان
        $stmt = $pdo->prepare("
            INSERT INTO user_subscriptions (user_id, plan_id, status, amount_paid, duration_days, created_at) 
            VALUES (?, ?, 'active', 0.00, 0, NOW())
        ");
        $stmt->execute([$user_id, $free_plan['id']]);
        
        $pdo->commit();
        return true;
        
    } catch (Exception $e) {
        $pdo->rollback();
        error_log("Error cancelling subscription: " . $e->getMessage());
        return false;
    }
}

/**
 * بررسی انقضای اشتراک‌ها
 */
function check_expired_subscriptions($pdo) {
    try {
        $pdo->beginTransaction();
        
        // پیدا کردن اشتراک‌های منقضی شده
        $stmt = $pdo->prepare("
            SELECT user_id, id FROM user_subscriptions 
            WHERE status = 'active' AND expires_at IS NOT NULL AND expires_at <= NOW()
        ");
        $stmt->execute();
        $expired_subscriptions = $stmt->fetchAll();
        
        $expired_count = count($expired_subscriptions);
        
        if ($expired_count > 0) {
            // تغییر وضعیت به منقضی شده
            $stmt = $pdo->prepare("
                UPDATE user_subscriptions 
                SET status = 'expired', updated_at = NOW() 
                WHERE status = 'active' AND expires_at IS NOT NULL AND expires_at <= NOW()
            ");
            $stmt->execute();
            
            // دریافت ID پلن رایگان
            $stmt = $pdo->prepare("SELECT id FROM subscription_plans WHERE slug = 'free' LIMIT 1");
            $stmt->execute();
            $free_plan = $stmt->fetch();
            
            if ($free_plan) {
                // برای هر کاربر، اشتراک رایگان ایجاد کن
                foreach ($expired_subscriptions as $sub) {
                    $user_id = $sub['user_id'];
                    
                    // لغو تمام اشتراک‌های active و pending قبلی
                    $stmt = $pdo->prepare("
                        UPDATE user_subscriptions 
                        SET status = 'cancelled', updated_at = NOW() 
                        WHERE user_id = ? AND status IN ('active', 'pending')
                    ");
                    $stmt->execute([$user_id]);
                    
                    // ایجاد اشتراک رایگان
                    $stmt = $pdo->prepare("
                        INSERT INTO user_subscriptions (user_id, plan_id, status, amount_paid, duration_days, created_at) 
                        VALUES (?, ?, 'active', 0.00, 0, NOW())
                    ");
                    $stmt->execute([$user_id, $free_plan['id']]);
                    
                    // بروزرسانی current_plan_id
                    $stmt = $pdo->prepare("UPDATE users SET current_plan_id = ? WHERE id = ?");
                    $stmt->execute([$free_plan['id'], $user_id]);
                }
            }
        }
        
        $pdo->commit();
        return $expired_count;
        
    } catch (Exception $e) {
        $pdo->rollback();
        error_log("Error checking expired subscriptions: " . $e->getMessage());
        return false;
    }
}

/**
 * دریافت آمار اشتراک‌ها
 */
function get_subscription_stats($pdo) {
    $stats = [];
    
    // تعداد کل کاربران هر پلن
    $stmt = $pdo->prepare("
        SELECT sp.name, COUNT(u.id) as user_count
        FROM subscription_plans sp
        LEFT JOIN users u ON sp.id = u.current_plan_id
        WHERE sp.is_active = 1
        GROUP BY sp.id, sp.name
        ORDER BY sp.sort_order
    ");
    $stmt->execute();
    $stats['plans'] = $stmt->fetchAll();
    
    // درآمد ماهانه
    $stmt = $pdo->prepare("
        SELECT SUM(amount_paid) as monthly_revenue
        FROM user_subscriptions
        WHERE status = 'active' AND created_at >= DATE_SUB(NOW(), INTERVAL 1 MONTH)
    ");
    $stmt->execute();
    $stats['monthly_revenue'] = $stmt->fetchColumn() ?: 0;
    
    // اشتراک‌های در حال انقضا (7 روز آینده)
    $stmt = $pdo->prepare("
        SELECT COUNT(*) as expiring_soon
        FROM user_subscriptions
        WHERE status = 'active' AND expires_at IS NOT NULL 
        AND expires_at BETWEEN NOW() AND DATE_ADD(NOW(), INTERVAL 7 DAY)
    ");
    $stmt->execute();
    $stats['expiring_soon'] = $stmt->fetchColumn() ?: 0;
    
    return $stats;
}

/**
 * دریافت تاریخچه اشتراک‌های کاربر
 */
function get_user_subscription_history($user_id, $pdo) {
    $stmt = $pdo->prepare("
        SELECT us.*, sp.name as plan_name, sp.slug as plan_slug
        FROM user_subscriptions us
        JOIN subscription_plans sp ON us.plan_id = sp.id
        WHERE us.user_id = ?
        ORDER BY us.created_at DESC
    ");
    $stmt->execute([$user_id]);
    return $stmt->fetchAll();
}

/**
 * دریافت گزینه‌های مدت زمان VIP
 */
function get_vip_duration_options($plan = null) {
    if ($plan && !empty($plan['durations'])) {
        $decoded = json_decode($plan['durations'], true);
        if (is_array($decoded)) {
            $options = [];
            foreach ($decoded as $index => $d) {
                $options[] = [
                    'days' => $d['days'],
                    'label' => $d['label'],
                    'price_key' => 'dyn_' . $index,
                    'price' => $d['price']
                ];
            }
            return $options;
        }
    }

    return [
        [
            'days' => 14,
            'label' => '2 هفته',
            'price_key' => 'price_2_weeks'
        ],
        [
            'days' => 30,
            'label' => '1 ماه',
            'price_key' => 'price_1_month'
        ],
        [
            'days' => 90,
            'label' => '3 ماه',
            'price_key' => 'price_3_months'
        ],
        [
            'days' => 180,
            'label' => '6 ماه',
            'price_key' => 'price_6_months'
        ],
        [
            'days' => 365,
            'label' => '1 سال',
            'price_key' => 'price_1_year'
        ]
    ];
}

/**
 * محاسبه قیمت بر اساس مدت زمان
 */
function get_vip_price($duration_days, $pdo) {
    $vip_plan = $pdo->prepare("SELECT * FROM subscription_plans WHERE slug = 'vip' LIMIT 1");
    $vip_plan->execute();
    $plan = $vip_plan->fetch();
    
    if (!$plan) {
        return null;
    }
    
    // ابتدا در فیلد جدید جستجو کن
    if (!empty($plan['durations'])) {
        $decoded = json_decode($plan['durations'], true);
        if (is_array($decoded)) {
            foreach ($decoded as $d) {
                if ($d['days'] == $duration_days) {
                    return $d['price'];
                }
            }
        }
    }

    $price_map = [
        14 => $plan['price_2_weeks'] ?? 0,
        30 => $plan['price_1_month'] ?? 0,
        90 => $plan['price_3_months'] ?? 0,
        180 => $plan['price_6_months'] ?? 0,
        365 => $plan['price_1_year'] ?? 0
    ];
    
    return $price_map[$duration_days] ?? null;
}

/**
 * فرمت کردن قیمت
 */
function format_price($price) {
    return number_format($price, 0, '.', ',') . ' یورو';
}

/**
 * دریافت روزهای باقی‌مانده تا انقضا
 */
function get_days_until_expiry($user_id, $pdo) {
    $subscription = get_user_active_subscription($user_id, $pdo);
    
    if (!$subscription || !$subscription['expires_at']) {
        return null; // نامحدود یا رایگان
    }
    
    $now = new DateTime();
    $expires = new DateTime($subscription['expires_at']);
    $diff = $now->diff($expires);
    
    return $diff->days;
}

/**
 * دریافت ویژگی‌های یک پلن
 */
function get_plan_features($plan_slug) {
    global $pdo;
    
    try {
        $stmt = $pdo->prepare("SELECT features FROM subscription_plans WHERE slug = ? AND is_active = 1");
        $stmt->execute([$plan_slug]);
        $features_json = $stmt->fetchColumn();
        
        if ($features_json) {
            $features = json_decode($features_json, true);
            if (is_array($features) && !empty($features)) {
                return $features;
            }
        }
    } catch (Exception $e) {
        error_log("Error fetching plan features: " . $e->getMessage());
    }

    // Fallback features if database is empty or error occurs
    $fallbacks = [
        'free' => [
            'دسترسی به 200 سوال اول',
            'بدون محدودیت زمانی',
            'پشتیبانی عمومی'
        ],
        'vip' => [
            'دسترسی نامحدود به تمام سوالات',
            'بدون محدودیت زمانی',
            'آپدیت‌های رایگان',
            'پشتیبانی اختصاصی',
            'دانلود نامحدود محتوا',
            'دسترسی به محتوای جدید'
        ]
    ];
    
    return $fallbacks[$plan_slug] ?? [];
}

/**
 * تبدیل duration به روز
 */
function get_duration_days($duration_key, $plan = null) {
    if (str_starts_with($duration_key, 'dyn_') && $plan && !empty($plan['durations'])) {
        $index = (int)str_replace('dyn_', '', $duration_key);
        $decoded = json_decode($plan['durations'], true);
        if (isset($decoded[$index])) {
            return $decoded[$index]['days'];
        }
    }

    $durations = [
        '2_weeks' => 14,
        '1_month' => 30,
        '3_months' => 90,
        '6_months' => 180,
        '1_year' => 365
    ];
    
    return $durations[$duration_key] ?? 0;
}

/**
 * تبدیل duration به برچسب فارسی
 */
function get_duration_label($duration_key, $plan = null) {
    if (str_starts_with($duration_key, 'dyn_') && $plan && !empty($plan['durations'])) {
        $index = (int)str_replace('dyn_', '', $duration_key);
        $decoded = json_decode($plan['durations'], true);
        if (isset($decoded[$index])) {
            return $decoded[$index]['label'];
        }
    }

    $labels = [
        '2_weeks' => '2 هفته',
        '1_month' => '1 ماه',
        '3_months' => '3 ماه',
        '6_months' => '6 ماه',
        '1_year' => '1 سال'
    ];
    
    return $labels[$duration_key] ?? '';
}

/**
 * دریافت قیمت بر اساس duration
 */
function get_plan_price_by_duration($plan, $duration_key) {
    if (str_starts_with($duration_key, 'dyn_') && !empty($plan['durations'])) {
        $index = (int)str_replace('dyn_', '', $duration_key);
        $decoded = json_decode($plan['durations'], true);
        if (isset($decoded[$index])) {
            return $decoded[$index]['price'];
        }
    }

    $price_keys = [
        '2_weeks' => 'price_2_weeks',
        '1_month' => 'price_1_month',
        '3_months' => 'price_3_months',
        '6_months' => 'price_6_months',
        '1_year' => 'price_1_year'
    ];
    
    $price_key = $price_keys[$duration_key] ?? null;
    
    if (!$price_key || !isset($plan[$price_key])) {
        return 0;
    }
    
    return $plan[$price_key];
}

/**
 * اعتبارسنجی duration
 */
function validate_duration($duration_key, $plan = null) {
    if (str_starts_with($duration_key, 'dyn_') && $plan && !empty($plan['durations'])) {
        $index = (int)str_replace('dyn_', '', $duration_key);
        $decoded = json_decode($plan['durations'], true);
        return isset($decoded[$index]);
    }

    $valid_durations = ['2_weeks', '1_month', '3_months', '6_months', '1_year'];
    return in_array($duration_key, $valid_durations);
}

/**
 * محاسبه تخفیف نسبت به خرید ماهانه
 */
function calculate_discount_percentage($plan, $duration_key) {
    if ($duration_key === '1_month' || $plan['price_1_month'] <= 0) {
        return 0;
    }
    
    $duration_days = get_duration_days($duration_key);
    $duration_price = get_plan_price_by_duration($plan, $duration_key);
    
    if ($duration_days <= 0 || $duration_price <= 0) {
        return 0;
    }
    
    $monthly_equivalent = ($duration_price / $duration_days) * 30;
    $discount = (1 - ($monthly_equivalent / $plan['price_1_month'])) * 100;
    
    return round($discount);
}

/**
 * دریافت مقدار یک تنظیم از دیتابیس
 */
function get_setting($key, $pdo, $default = null) {
    $stmt = $pdo->prepare("SELECT value FROM settings WHERE `key` = ?");
    $stmt->execute([$key]);
    $result = $stmt->fetch();
    return $result ? $result['value'] : $default;
}

/**
 * فعال‌سازی اشتراک pending و لغو اشتراک‌های قبلی
 */
function activate_pending_subscription($subscription_id, $pdo) {
    try {
        $pdo->beginTransaction();
        
        // دریافت اطلاعات اشتراک pending
        $stmt = $pdo->prepare("SELECT * FROM user_subscriptions WHERE id = ? AND status = 'pending'");
        $stmt->execute([$subscription_id]);
        $subscription = $stmt->fetch();
        
        if (!$subscription) {
            throw new Exception("اشتراک معلق یافت نشد");
        }
        
        $user_id = $subscription['user_id'];
        $plan_id = $subscription['plan_id'];
        $referred_by_id = $subscription['referred_by_id'];
        
        // لغو تمام اشتراک‌های فعال و معلق قبلی (به جز این اشتراک)
        $stmt = $pdo->prepare("
            UPDATE user_subscriptions 
            SET status = 'cancelled', updated_at = NOW() 
            WHERE user_id = ? AND id != ? AND status IN ('active', 'pending')
        ");
        $stmt->execute([$user_id, $subscription_id]);
        
        // فعال‌سازی این اشتراک
        $stmt = $pdo->prepare("
            UPDATE user_subscriptions 
            SET status = 'active', updated_at = NOW() 
            WHERE id = ?
        ");
        $stmt->execute([$subscription_id]);
        
        // بروزرسانی current_plan_id کاربر
        $stmt = $pdo->prepare("UPDATE users SET current_plan_id = ? WHERE id = ?");
        $stmt->execute([$plan_id, $user_id]);

        // اعمال هدیه معرف در صورت وجود
        if ($referred_by_id && $subscription['referral_bonus_applied'] == 0) {
            $bonus_new_user = (int)get_setting('referral_reward_new_user', $pdo, 7);
            $bonus_referrer = (int)get_setting('referral_reward_referrer', $pdo, 14);

            // ۱. اضافه کردن هدیه به خود کاربر (تمدید تاریخ انقضای اشتراک فعلی)
            if ($bonus_new_user > 0) {
                $stmtBonusUser = $pdo->prepare("
                    UPDATE user_subscriptions 
                    SET expires_at = DATE_ADD(expires_at, INTERVAL ? DAY), 
                        duration_days = duration_days + ?
                    WHERE id = ?
                ");
                $stmtBonusUser->execute([$bonus_new_user, $bonus_new_user, $subscription_id]);
            }

            // ۲. اضافه کردن هدیه به معرف (تمدید اشتراک فعال معرف یا ایجاد اشتراک جدید اگر ندارد)
            if ($bonus_referrer > 0) {
                $referrer_sub = get_user_active_subscription($referred_by_id, $pdo);
                if ($referrer_sub) {
                    // اگر اشتراک فعال دارد، آن را تمدید کن
                    $stmtBonusRef = $pdo->prepare("
                        UPDATE user_subscriptions 
                        SET expires_at = DATE_ADD(expires_at, INTERVAL ? DAY), 
                            duration_days = duration_days + ?
                        WHERE id = ?
                    ");
                    $stmtBonusRef->execute([$bonus_referrer, $bonus_referrer, $referrer_sub['id']]);
                } else {
                    // اگر اشتراک فعال ندارد، یک اشتراک VIP جدید به مدت هدیه برایش ایجاد کن
                    // ابتدا پیدا کردن ID پلن VIP
                    $stmtVip = $pdo->prepare("SELECT id FROM subscription_plans WHERE slug = 'vip' LIMIT 1");
                    $stmtVip->execute();
                    $vip_plan = $stmtVip->fetch();
                    if ($vip_plan) {
                        $expires_at_bonus = date('Y-m-d H:i:s', strtotime("+{$bonus_referrer} days"));
                        $stmtNewSub = $pdo->prepare("
                            INSERT INTO user_subscriptions 
                            (user_id, plan_id, expires_at, duration_days, amount_paid, status, created_at) 
                            VALUES (?, ?, ?, ?, 0, 'active', NOW())
                        ");
                        $stmtNewSub->execute([$referred_by_id, $vip_plan['id'], $expires_at_bonus, $bonus_referrer]);
                        
                        // بروزرسانی پلن فعلی معرف
                        $stmtUpdateRefPlan = $pdo->prepare("UPDATE users SET current_plan_id = ? WHERE id = ?");
                        $stmtUpdateRefPlan->execute([$vip_plan['id'], $referred_by_id]);
                    }
                }
            }

            // علامت‌گذاری که هدیه اعمال شده است
            $stmtMarkApplied = $pdo->prepare("UPDATE user_subscriptions SET referral_bonus_applied = 1 WHERE id = ?");
            $stmtMarkApplied->execute([$subscription_id]);

            // همچنین اگر کاربر قبلاً Referred By نداشته، ثبت کن
            $stmtUpdateUserRef = $pdo->prepare("UPDATE users SET referred_by_id = ? WHERE id = ? AND referred_by_id IS NULL");
            $stmtUpdateUserRef->execute([$referred_by_id, $user_id]);
        }
        
        $pdo->commit();
        return true;
        
    } catch (Exception $e) {
        $pdo->rollBack();
        error_log("Error activating subscription: " . $e->getMessage());
        return false;
    }
}

/**
 * بررسی و جلوگیری از اشتراک‌های تکراری active
 */
function ensure_single_active_subscription($user_id, $pdo) {
    try {
        $pdo->beginTransaction();
        
        // دریافت تمام اشتراک‌های active کاربر
        $stmt = $pdo->prepare("
            SELECT id, created_at 
            FROM user_subscriptions 
            WHERE user_id = ? AND status = 'active'
            ORDER BY created_at DESC
        ");
        $stmt->execute([$user_id]);
        $active_subscriptions = $stmt->fetchAll();
        
        // اگر بیش از یک اشتراک active وجود دارد
        if (count($active_subscriptions) > 1) {
            // اولین (جدیدترین) را نگه دار و بقیه را لغو کن
            $keep_id = $active_subscriptions[0]['id'];
            
            $stmt = $pdo->prepare("
                UPDATE user_subscriptions 
                SET status = 'cancelled', updated_at = NOW() 
                WHERE user_id = ? AND status = 'active' AND id != ?
            ");
            $stmt->execute([$user_id, $keep_id]);
            
            $pdo->commit();
            return count($active_subscriptions) - 1; // تعداد اشتراک‌های لغو شده
        }
        
        $pdo->commit();
        return 0;
        
    } catch (Exception $e) {
        $pdo->rollBack();
        error_log("Error ensuring single active subscription: " . $e->getMessage());
        return false;
    }
}