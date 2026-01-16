<?php
// verify.php
require_once __DIR__ . '/../incloud/functions.php';

$token = $_GET['token'] ?? '';
$message = '';
$messageType = 'danger'; // danger, success, warning
$verified = false;

if (empty($token)) {
    $message = 'توکن تایید نامعتبر است.';
} else {
    // بررسی توکن
    $stmt = $pdo->prepare("
        SELECT id, email, name, email_verified 
        FROM users 
        WHERE verification_token = ?
    ");
    $stmt->execute([$token]);
    $user = $stmt->fetch();
    
    if (!$user) {
        $message = 'توکن تایید نامعتبر یا منقضی شده است.';
    } elseif ($user['email_verified'] == 1) {
        $message = 'حساب کاربری شما قبلاً تایید شده است.';
        $messageType = 'info';
        $verified = true;
    } else {
        // تایید حساب کاربری
        try {
            $stmt = $pdo->prepare("
                UPDATE users 
                SET email_verified = 1, verification_token = NULL 
                WHERE id = ?
            ");
            $stmt->execute([$user['id']]);
            
            // ارسال ایمیل خوش‌آمدگویی
            require_once __DIR__ . '/../incloud/mail-functions.php';
            send_welcome_email($user['email'], $user['name']);
            
            // ثبت لاگ
            log_user_action($user['id'], $user['email'], 'email_verification', 'success', $pdo);
            
            $message = 'حساب کاربری شما با موفقیت تایید شد! اکنون می‌توانید وارد شوید.';
            $messageType = 'success';
            $verified = true;
            
        } catch (PDOException $e) {
            $message = 'خطا در تایید حساب کاربری. لطفا دوباره تلاش کنید.';
            log_user_action($user['id'], $user['email'], 'email_verification', 'failed', $pdo);
        }
    }
}
?>
<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>تایید حساب کاربری - <?php echo SITE_NAME; ?></title>
    
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    
    <!-- SweetAlert2 -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
    
    <!-- Custom CSS -->
    <link rel="stylesheet" href="assets/css/style.css">
    
    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .verification-card {
            background: white;
            border-radius: 20px;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
            padding: 40px;
            max-width: 500px;
            width: 100%;
            text-align: center;
            animation: slideIn 0.5s ease;
        }
        
        @keyframes slideIn {
            from {
                opacity: 0;
                transform: translateY(30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        .icon-wrapper {
            width: 100px;
            height: 100px;
            margin: 0 auto 30px;
            background: #f8f9fa;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 50px;
        }
        
        .icon-success {
            background: #d4edda;
            color: #28a745;
        }
        
        .icon-danger {
            background: #f8d7da;
            color: #dc3545;
        }
        
        .icon-info {
            background: #d1ecf1;
            color: #17a2b8;
        }
        
        .countdown {
            font-size: 18px;
            margin-top: 20px;
            color: #6c757d;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="verification-card">
            <!-- Icon -->
            <div class="icon-wrapper icon-<?php echo $messageType; ?>">
                <?php if ($messageType === 'success'): ?>
                    <i class="bi bi-check-circle-fill"></i>
                <?php elseif ($messageType === 'info'): ?>
                    <i class="bi bi-info-circle-fill"></i>
                <?php else: ?>
                    <i class="bi bi-x-circle-fill"></i>
                <?php endif; ?>
            </div>
            
            <!-- Title -->
            <h2 class="mb-4">
                <?php if ($messageType === 'success'): ?>
                    تایید موفق!
                <?php elseif ($messageType === 'info'): ?>
                    قبلاً تایید شده
                <?php else: ?>
                    خطا در تایید
                <?php endif; ?>
            </h2>
            
            <!-- Message -->
            <div class="alert alert-<?php echo $messageType; ?>" role="alert">
                <?php echo $message; ?>
            </div>
            
            <!-- Actions -->
            <?php if ($verified): ?>
                <div class="d-grid gap-2">
                    <button type="button" class="btn btn-primary btn-lg" onclick="redirectToLogin()">
                        <i class="bi bi-box-arrow-in-left"></i> ورود به حساب کاربری
                    </button>
                </div>
                
                <div class="countdown" id="countdown">
                    هدایت خودکار در <span id="timer">5</span> ثانیه...
                </div>
            <?php else: ?>
                <div class="d-grid gap-2">
                    <a href="../index.php" class="btn btn-secondary btn-lg">
                        <i class="bi bi-house"></i> بازگشت به صفحه اصلی
                    </a>
                    <button type="button" class="btn btn-warning btn-lg" onclick="requestNewToken()">
                        <i class="bi bi-arrow-clockwise"></i> درخواست توکن جدید
                    </button>
                </div>
            <?php endif; ?>
            
            <!-- Footer -->
            <div class="mt-4 text-muted">
                <small>
                    <i class="bi bi-shield-check"></i>
                    <?php echo SITE_NAME; ?> - سیستم احراز هویت امن
                </small>
            </div>
        </div>
    </div>

    <!-- Scripts -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    
    <script>
        <?php if ($verified): ?>
        // شمارش معکوس و هدایت خودکار
        let seconds = 5;
        const countdown = setInterval(() => {
            seconds--;
            document.getElementById('timer').textContent = seconds;
            
            if (seconds <= 0) {
                clearInterval(countdown);
                redirectToLogin();
            }
        }, 1000);
        <?php endif; ?>
        
        function redirectToLogin() {
            window.location.href = '../index.php';
        }
        
        function requestNewToken() {
            Swal.fire({
                title: 'درخواست توکن جدید',
                input: 'email',
                inputLabel: 'ایمیل حساب کاربری',
                inputPlaceholder: 'email@example.com',
                showCancelButton: true,
                confirmButtonText: 'ارسال',
                cancelButtonText: 'انصراف',
                inputValidator: (value) => {
                    if (!value || !value.includes('@')) {
                        return 'لطفا ایمیل معتبر وارد کنید';
                    }
                }
            }).then((result) => {
                if (result.isConfirmed) {
                    // ارسال درخواست برای توکن جدید
                    fetch('resend-verification.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/x-www-form-urlencoded',
                        },
                        body: 'email=' + encodeURIComponent(result.value)
                    })
                    .then(response => response.json())
                    .then(data => {
                        Swal.fire({
                            icon: data.success ? 'success' : 'error',
                            title: data.success ? 'موفق' : 'خطا',
                            text: data.message
                        });
                    })
                    .catch(error => {
                        Swal.fire({
                            icon: 'error',
                            title: 'خطا',
                            text: 'خطا در ارسال درخواست'
                        });
                    });
                }
            });
        }
        
        // انیمیشن موفقیت
        <?php if ($messageType === 'success'): ?>
        window.addEventListener('load', () => {
            confetti({
                particleCount: 100,
                spread: 70,
                origin: { y: 0.6 }
            });
        });
        <?php endif; ?>
    </script>
    
    <!-- Confetti Effect (Optional) -->
    <?php if ($messageType === 'success'): ?>
    <script src="https://cdn.jsdelivr.net/npm/canvas-confetti@1.6.0/dist/confetti.browser.min.js"></script>
    <?php endif; ?>
</body>
</html>