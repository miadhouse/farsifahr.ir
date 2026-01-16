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
 * دریافت ویژگی‌های یک پلن
 */
function get_plan_features($plan_id, $pdo) {
    $stmt = $pdo->prepare("
        SELECT sf.*, pf.feature_value, pf.is_unlimited
        FROM subscription_features sf
        JOIN plan_features pf ON sf.id = pf.feature_id
        WHERE pf.plan_id = ? AND sf.is_active = 1
        ORDER BY sf.id ASC
    ");
    $stmt->execute([$plan_id]);
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
 * بررسی دسترسی کاربر به یک ویژگی
 */
function user_has_feature($user_id, $feature_slug, $pdo) {
    $subscription = get_user_active_subscription($user_id, $pdo);
    if (!$subscription) {
        return false;
    }
    
    $stmt = $pdo->prepare("
        SELECT pf.feature_value, pf.is_unlimited
        FROM plan_features pf
        JOIN subscription_features sf ON pf.feature_id = sf.id
        WHERE pf.plan_id = ? AND sf.slug = ? AND sf.is_active = 1
    ");
    $stmt->execute([$subscription['plan_id'], $feature_slug]);
    $feature = $stmt->fetch();
    
    return $feature !== false;
}

/**
 * دریافت مقدار یک ویژگی برای کاربر
 */
function get_user_feature_value($user_id, $feature_slug, $pdo) {
    $subscription = get_user_active_subscription($user_id, $pdo);
    if (!$subscription) {
        return null;
    }
    
    $stmt = $pdo->prepare("
        SELECT pf.feature_value, pf.is_unlimited
        FROM plan_features pf
        JOIN subscription_features sf ON pf.feature_id = sf.id
        WHERE pf.plan_id = ? AND sf.slug = ? AND sf.is_active = 1
    ");
    $stmt->execute([$subscription['plan_id'], $feature_slug]);
    $feature = $stmt->fetch();
    
    if (!$feature) {
        return null;
    }
    
    return $feature['is_unlimited'] ? 'unlimited' : $feature['feature_value'];
}

/**
 * بررسی اینکه آیا کاربر از یک ویژگی استفاده کرده یا نه
 */
function check_feature_usage($user_id, $feature_slug, $current_usage, $pdo) {
    $limit = get_user_feature_value($user_id, $feature_slug, $pdo);
    
    if ($limit === null) {
        return false; // کاربر به این ویژگی دسترسی ندارد
    }
    
    if ($limit === 'unlimited') {
        return true; // نامحدود
    }
    
    return intval($current_usage) < intval($limit);
}

/**
 * ایجاد اشتراک جدید برای کاربر
 */
function create_user_subscription($user_id, $plan_id, $pdo, $is_yearly = false, $amount_paid = 0, $payment_method = null, $transaction_id = null) {
    try {
        $pdo->beginTransaction();
        
        // محاسبه تاریخ انقضا
        $expires_at = null;
        if ($plan_id != 1) { // اگر پلن رایگان نباشد
            $period = $is_yearly ? '1 YEAR' : '1 MONTH';
            $expires_at = date('Y-m-d H:i:s', strtotime("+$period"));
        }
        
        // لغو اشتراک‌های قبلی
        $stmt = $pdo->prepare("UPDATE user_subscriptions SET status = 'cancelled' WHERE user_id = ? AND status = 'active'");
        $stmt->execute([$user_id]);
        
        // ایجاد اشتراک جدید
        $stmt = $pdo->prepare("
            INSERT INTO user_subscriptions 
            (user_id, plan_id, expires_at, is_yearly, amount_paid, payment_method, transaction_id) 
            VALUES (?, ?, ?, ?, ?, ?, ?)
        ");
        $stmt->execute([$user_id, $plan_id, $expires_at, $is_yearly, $amount_paid, $payment_method, $transaction_id]);
        
        // بروزرسانی پلن فعلی کاربر
        $stmt = $pdo->prepare("UPDATE users SET current_plan_id = ? WHERE id = ?");
        $stmt->execute([$plan_id, $user_id]);
        
        $pdo->commit();
        return true;
        
    } catch (Exception $e) {
        $pdo->rollback();
        return false;
    }
}

/**
 * لغو اشتراک کاربر
 */
function cancel_user_subscription($user_id, $pdo) {
    try {
        $pdo->beginTransaction();
        
        // لغو اشتراک فعال
        $stmt = $pdo->prepare("UPDATE user_subscriptions SET status = 'cancelled' WHERE user_id = ? AND status = 'active'");
        $stmt->execute([$user_id]);
        
        // تغییر به پلن رایگان
        $stmt = $pdo->prepare("UPDATE users SET current_plan_id = 1 WHERE id = ?");
        $stmt->execute([$user_id]);
        
        // ایجاد اشتراک رایگان
        $stmt = $pdo->prepare("
            INSERT INTO user_subscriptions (user_id, plan_id, status, amount_paid) 
            VALUES (?, 1, 'active', 0.00)
        ");
        $stmt->execute([$user_id]);
        
        $pdo->commit();
        return true;
        
    } catch (Exception $e) {
        $pdo->rollback();
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
            SELECT user_id FROM user_subscriptions 
            WHERE status = 'active' AND expires_at IS NOT NULL AND expires_at <= NOW()
        ");
        $stmt->execute();
        $expired_users = $stmt->fetchAll(PDO::FETCH_COLUMN);
        
        if (!empty($expired_users)) {
            // تغییر وضعیت به منقضی شده
            $stmt = $pdo->prepare("
                UPDATE user_subscriptions 
                SET status = 'expired' 
                WHERE status = 'active' AND expires_at IS NOT NULL AND expires_at <= NOW()
            ");
            $stmt->execute();
            
            // تغییر کاربران به پلن رایگان
            $placeholders = str_repeat('?,', count($expired_users) - 1) . '?';
            $stmt = $pdo->prepare("UPDATE users SET current_plan_id = 1 WHERE id IN ($placeholders)");
            $stmt->execute($expired_users);
            
            // ایجاد اشتراک رایگان برای کاربران
            foreach ($expired_users as $user_id) {
                $stmt = $pdo->prepare("
                    INSERT INTO user_subscriptions (user_id, plan_id, status, amount_paid) 
                    VALUES (?, 1, 'active', 0.00)
                ");
                $stmt->execute([$user_id]);
            }
        }
        
        $pdo->commit();
        return count($expired_users);
        
    } catch (Exception $e) {
        $pdo->rollback();
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
 * محاسبه قیمت با تخفیف سالانه
 */
/**
 * محاسبه قیمت با تخفیف سالانه
 */
function calculate_plan_price($plan_id, $is_yearly, $pdo) {
    $plan = get_subscription_plan($plan_id, $pdo);
    if (!$plan) {
        return null;
    }
    
    if ($is_yearly) {
        $original_price = $plan['monthly_price'] * 12;
        $discounted_price = $plan['yearly_price'];
        
        // Prevent division by zero
        if ($original_price <= 0) {
            return [
                'original_price' => $original_price,
                'discounted_price' => $discounted_price,
                'discount_amount' => 0,
                'discount_percentage' => 0
            ];
        }
        
        $discount_amount = $original_price - $discounted_price;
        $discount_percentage = round(($discount_amount / $original_price) * 100);
        
        return [
            'original_price' => $original_price,
            'discounted_price' => $discounted_price,
            'discount_amount' => $discount_amount,
            'discount_percentage' => $discount_percentage
        ];
    } else {
        return [
            'price' => $plan['monthly_price']
        ];
    }
}

/**
 * بررسی امکان ارتقا پلن
 */
function can_upgrade_plan($user_id, $target_plan_id, $pdo) {
    $current_subscription = get_user_active_subscription($user_id, $pdo);
    if (!$current_subscription) {
        return false;
    }
    
    $current_plan = get_subscription_plan($current_subscription['plan_id'], $pdo);
    $target_plan = get_subscription_plan($target_plan_id, $pdo);
    
    if (!$current_plan || !$target_plan) {
        return false;
    }
    
    return $target_plan['sort_order'] > $current_plan['sort_order'];
}

/**
 * فرمت کردن قیمت
 */
function format_price($price) {
    return number_format($price, 0, '.', ',') . ' تومان';
}

/**
 * تبدیل مقدار ویژگی به متن قابل نمایش
 */
function format_feature_value($value, $is_unlimited = false) {
    if ($is_unlimited) {
        return 'نامحدود';
    }
    
    if (is_numeric($value)) {
        return number_format($value, 0, '.', ',');
    }
    
    return $value;
}
function get_all_features($pdo) {
    $stmt = $pdo->prepare("SELECT * FROM subscription_features WHERE is_active = 1 ORDER BY id ASC");
    $stmt->execute();
    return $stmt->fetchAll();
}

function getLastInvoiceNumber($pdo){
    $stmt = $pdo->prepare("SELECT id FROM subscription_features ORDER BY id ASC LIMIT 1");
    return $stmt++;
};