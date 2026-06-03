<?php
// reset-password.php
require_once __DIR__ . '/../incloud/functions.php';

$token = $_GET['token'] ?? '';
$error = '';
$valid_token = false;

if (empty($token)) {
    $error = 'توکن نامعتبر است';
} else {
    // بررسی توکن
    $stmt = $pdo->prepare("
        SELECT id, email, name FROM users 
        WHERE reset_token = ? AND reset_expires > NOW()
    ");
    $stmt->execute([$token]);
    $user = $stmt->fetch();
    
    if ($user) {
        $valid_token = true;
    } else {
        $error = 'توکن منقضی شده یا نامعتبر است';
    }
}
?>
<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>تغییر رمز عبور - <?php echo SITE_NAME; ?></title>
    
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    
    <!-- SweetAlert2 -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
    <style>
        .cursor-pointer { cursor: pointer; }
        [dir="rtl"] .input-group .form-control:first-child {
            border-top-right-radius: 0.375rem !important;
            border-bottom-right-radius: 0.375rem !important;
            border-top-left-radius: 0 !important;
            border-bottom-left-radius: 0 !important;
            border-left: none;
        }
        [dir="rtl"] .input-group-text {
            border-top-left-radius: 0.375rem !important;
            border-bottom-left-radius: 0.375rem !important;
            border-top-right-radius: 0 !important;
            border-bottom-right-radius: 0 !important;
            border-left: 1px solid #ced4da;
            background-color: transparent;
        }
    </style>
</head>
<body class="bg-light">
    <div class="container">
        <div class="row justify-content-center mt-5">
            <div class="col-md-6 col-lg-5">
                <div class="card shadow">
                    <div class="card-body p-4">
                        <h3 class="card-title text-center mb-4">
                            <i class="bi bi-key"></i> تغییر رمز عبور
                        </h3>
                        
                        <?php if (!$valid_token): ?>
                            <div class="alert alert-danger">
                                <i class="bi bi-exclamation-triangle"></i> <?php echo $error; ?>
                            </div>
                            <div class="text-center">
                                <a href="index.php" class="btn btn-primary">
                                    بازگشت به صفحه اصلی
                                </a>
                            </div>
                        <?php else: ?>
                            <form id="newPasswordForm">
                                <input type="hidden" name="token" value="<?php echo htmlspecialchars($token); ?>">
                                <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                                
                                <div class="mb-3">
                                    <label class="form-label">رمز عبور جدید</label>
                                    <div class="input-group">
                                        <input type="password" class="form-control" name="password" id="newPassword" required>
                                        <span class="input-group-text cursor-pointer" onclick="togglePasswordVisibility('newPassword', this)">
                                            <i class="fa-regular fa-eye"></i>
                                        </span>
                                    </div>
                                    <small class="text-muted">حداقل 8 کاراکتر، شامل حروف بزرگ، کوچک و عدد</small>
                                </div>
                                
                                <div class="mb-3">
                                    <label class="form-label">تکرار رمز عبور جدید</label>
                                    <div class="input-group">
                                        <input type="password" class="form-control" name="password_confirm" id="confirmPassword" required>
                                        <span class="input-group-text cursor-pointer" onclick="togglePasswordVisibility('confirmPassword', this)">
                                            <i class="fa-regular fa-eye"></i>
                                        </span>
                                    </div>
                                </div>
                                
                                <div class="d-grid">
                                    <button type="submit" class="btn btn-primary">
                                        <i class="bi bi-check-circle"></i> تغییر رمز عبور
                                    </button>
                                </div>
                            </form>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Scripts -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    
    <?php if ($valid_token): ?>
    <script>
        function togglePasswordVisibility(inputId, button) {
            const input = document.getElementById(inputId);
            const icon = button.querySelector('i');
            
            if (input.type === 'password') {
                input.type = 'text';
                icon.classList.remove('fa-eye');
                icon.classList.add('fa-eye-slash');
            } else {
                input.type = 'password';
                icon.classList.remove('fa-eye-slash');
                icon.classList.add('fa-eye');
            }
        }

        document.getElementById('newPasswordForm').addEventListener('submit', async function(e) {
            e.preventDefault();
            
            const formData = new FormData(this);
            const submitBtn = this.querySelector('button[type="submit"]');
            
            // اعتبارسنجی
            const password = formData.get('password');
            const passwordConfirm = formData.get('password_confirm');
            
            if (password !== passwordConfirm) {
                Swal.fire({
                    icon: 'error',
                    title: 'خطا',
                    text: 'رمز عبور و تکرار آن مطابقت ندارند'
                });
                return;
            }
            
            // غیرفعال کردن دکمه
            submitBtn.disabled = true;
            submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>در حال تغییر...';
            
            try {
                const response = await fetch('process-reset.php', {
                    method: 'POST',
                    body: formData
                });
                
                const result = await response.json();
                
                if (result.success) {
                    Swal.fire({
                        icon: 'success',
                        title: 'موفق',
                        text: result.message,
                        confirmButtonText: 'ورود به حساب'
                    }).then(() => {
                        window.location.href = '../index.php';
                    });
                } else {
                    Swal.fire({
                        icon: 'error',
                        title: 'خطا',
                        text: result.message
                    });
                }
            } catch (error) {
                Swal.fire({
                    icon: 'error',
                    title: 'خطا',
                    text: 'خطا در برقراری ارتباط با سرور'
                });
            } finally {
                submitBtn.disabled = false;
                submitBtn.innerHTML = '<i class="bi bi-check-circle"></i> تغییر رمز عبور';
            }
        });
    </script>
    <?php endif; ?>
</body>
</html>