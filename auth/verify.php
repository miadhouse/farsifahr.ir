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
        SELECT id, email, name, email_verified, pending_email 
        FROM users 
        WHERE verification_token = ?
    ");
    $stmt->execute([$token]);
    $user = $stmt->fetch();
    
    if (!$user) {
        $message = 'توکن تایید نامعتبر یا منقضی شده است.';
    } elseif ($user['email_verified'] == 1 && empty($user['pending_email'])) {
        $message = 'حساب کاربری شما قبلاً تایید شده است.';
        $messageType = 'info';
        $verified = true;
    } else {
        // تایید حساب کاربری یا تغییر ایمیل
        try {
            $new_email = $user['pending_email'] ?: $user['email'];
            
            $stmt = $pdo->prepare("
                UPDATE users 
                SET email = ?, email_verified = 1, verification_token = NULL, pending_email = NULL 
                WHERE id = ?
            ");
            $stmt->execute([$new_email, $user['id']]);
            
            // ارسال ایمیل خوش‌آمدگویی یا تایید تغییر
            require_once __DIR__ . '/../incloud/mail-functions.php';
            if (!empty($user['pending_email'])) {
                $message = 'ایمیل شما با موفقیت تغییر یافت و تایید شد!';
            } else {
                send_welcome_email($new_email, $user['name']);
                $message = 'حساب کاربری شما با موفقیت تایید شد! اکنون می‌توانید وارد شوید.';
            }
            
            // ثبت لاگ
            log_user_action($user['id'], $new_email, 'email_verification', 'success', $pdo);
            
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
    <link rel="stylesheet" href="../assets/css/auth-dark.css">
    
    <style>
        .verification-card {
            background: var(--card-bg);
            backdrop-filter: blur(12px);
            -webkit-backdrop-filter: blur(12px);
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 24px;
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.5);
            padding: 40px;
            max-width: 500px;
            width: 100%;
            text-align: center;
            animation: slideIn 0.5s ease;
            color: var(--text-main);
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
            background: rgba(255, 255, 255, 0.05);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 50px;
        }
        
        .icon-success {
            background: rgba(40, 167, 69, 0.2);
            color: #28a745;
        }
        
        .icon-danger {
            background: rgba(220, 53, 69, 0.2);
            color: #dc3545;
        }
        
        .icon-info {
            background: rgba(23, 162, 184, 0.2);
            color: #17a2b8;
        }
        
        .countdown {
            font-size: 18px;
            margin-top: 20px;
            color: var(--text-muted);
        }

        .alert {
            border: none;
            border-radius: 12px;
        }

        .alert-success { background: rgba(40, 167, 69, 0.2); color: #28a745; }
        .alert-danger { background: rgba(220, 53, 69, 0.2); color: #dc3545; }
        .alert-info { background: rgba(23, 162, 184, 0.2); color: #17a2b8; }
    </style>
</head>
<body>
    <div class="auth-bg">
        <div class="blob"></div>
        <div class="blob"></div>
        <div class="blob"></div>
    </div>
    <div class="container d-flex align-items-center justify-content-center min-vh-100">
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