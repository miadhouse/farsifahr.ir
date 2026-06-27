<?php
// incloud/pending-request-handler.php
require_once __DIR__ . '/functions.php';
require_once __DIR__ . '/subscription-functions.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (isset($_SESSION['pending_service_request']) && is_logged_in()) {
    $req = $_SESSION['pending_service_request'];
    $user_id = $_SESSION['user_id'];
    
    $service_type = $req['service_type'] ?? '';
    
    if ($service_type === 'translation') {
        // ثبت درخواست ترجمه گواهینامه
        try {
            $stmt = $pdo->prepare("
                INSERT INTO license_translation_requests 
                (user_id, first_name, last_name, phone, email, postal_code, city, street, house_number, additional_address, front_image_path, back_image_path, status, price, payment_contact_method, created_at, updated_at) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'pending_payment', ?, ?, NOW(), NOW())
            ");
            $stmt->execute([
                $user_id,
                $req['first_name'],
                $req['last_name'],
                $req['phone'],
                $req['email'],
                $req['postal_code'],
                $req['city'],
                $req['street'],
                $req['house_number'],
                $req['additional_address'],
                $req['front_image_path'],
                $req['back_image_path'],
                $req['price'],
                $req['payment_contact_method']
            ]);
            
            $req_id = $pdo->lastInsertId();
            
            // ارسال اعلان تلگرام
            $admin_msg = "📥 <b>درخواست جدید ترجمه گواهینامه (کاربر مهمان سابق)</b>\n\n";
            $admin_msg .= "👤 نام کاربر: {$req['first_name']} {$req['last_name']}\n";
            $admin_msg .= "📞 تلفن: {$req['phone']}\n";
            $admin_msg .= "📧 ایمیل: {$req['email']}\n";
            $admin_msg .= "💰 مبلغ: {$req['price']} یورو\n";
            $admin_msg .= "🔗 لینک پنل ادمین: https://farsifahr.com/panel/license-translation-requests/{$req_id}/edit\n";
            send_telegram_admin_message($admin_msg);
            
            unset($_SESSION['pending_service_request']);
            $_SESSION['success_message'] = 'درخواست ترجمه گواهینامه شما با موفقیت ثبت شد. لطفاً هزینه آن را نهایی کنید.';
            header("Location: /admin/my-requests.php");
            exit();
        } catch (Exception $e) {
            error_log("Error saving pending translation request: " . $e->getMessage());
        }
    } elseif ($service_type === 'eyetest' || $service_type === 'firstaid') {
        // بررسی اشتراک فعال
        $active_sub = get_user_active_subscription($user_id, $pdo);
        $has_active_sub = ($active_sub && $active_sub['plan_slug'] !== 'free');
        
        if ($has_active_sub) {
            try {
                if ($service_type === 'eyetest') {
                    $stmt = $pdo->prepare("
                        INSERT INTO eye_test_appointment_requests 
                        (user_id, first_name, last_name, phone, email, postal_code, city, street, house_number, additional_address, status, created_at, updated_at) 
                        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'pending', NOW(), NOW())
                    ");
                    $stmt->execute([
                        $user_id,
                        $req['first_name'],
                        $req['last_name'],
                        $req['phone'],
                        $req['email'],
                        $req['postal_code'],
                        $req['city'],
                        $req['street'],
                        $req['house_number'],
                        $req['additional_address']
                    ]);
                    
                    $req_id = $pdo->lastInsertId();
                    
                    // اعلان تلگرام
                    $admin_msg = "👁️ <b>درخواست جدید تست چشم (کاربر مهمان سابق)</b>\n\n";
                    $admin_msg .= "👤 نام کاربر: {$req['first_name']} {$req['last_name']}\n";
                    $admin_msg .= "📞 تلفن: {$req['phone']}\n";
                    $admin_msg .= "🔗 لینک پنل ادمین: https://farsifahr.com/panel/eye-test-appointment-requests/{$req_id}/edit\n";
                    send_telegram_admin_message($admin_msg);
                } else {
                    $stmt = $pdo->prepare("
                        INSERT INTO first_aid_course_appointment_requests 
                        (user_id, first_name, last_name, phone, email, postal_code, city, street, house_number, additional_address, status, created_at, updated_at) 
                        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'pending', NOW(), NOW())
                    ");
                    $stmt->execute([
                        $user_id,
                        $req['first_name'],
                        $req['last_name'],
                        $req['phone'],
                        $req['email'],
                        $req['postal_code'],
                        $req['city'],
                        $req['street'],
                        $req['house_number'],
                        $req['additional_address']
                    ]);
                    
                    $req_id = $pdo->lastInsertId();
                    
                    // اعلان تلگرام
                    $admin_msg = "🚑 <b>درخواست جدید کورس کمک‌های اولیه (کاربر مهمان سابق)</b>\n\n";
                    $admin_msg .= "👤 نام کاربر: {$req['first_name']} {$req['last_name']}\n";
                    $admin_msg .= "📞 تلفن: {$req['phone']}\n";
                    $admin_msg .= "🔗 لینک پنل ادمین: https://farsifahr.com/panel/first-aid-course-appointment-requests/{$req_id}/edit\n";
                    send_telegram_admin_message($admin_msg);
                }
                
                unset($_SESSION['pending_service_request']);
                $_SESSION['success_message'] = 'درخواست نوبت شما با موفقیت ثبت شد.';
                header("Location: /admin/my-requests.php");
                exit();
            } catch (Exception $e) {
                error_log("Error saving pending VIP request: " . $e->getMessage());
            }
        } else {
            // کاربر اشتراک فعال ندارد، او را به صفحه خرید اشتراک هدایت می‌کنیم
            $_SESSION['info_message'] = 'شما یک درخواست در انتظار برای خدمت رایگان دارید. لطفاً ابتدا یک اشتراک فعال تهیه کنید تا درخواست شما نهایی شود.';
            header("Location: /admin/subscription.php");
            exit();
        }
    }
}
