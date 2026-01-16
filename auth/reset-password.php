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
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>تغییر رمز عبور - <?php echo SITE_NAME; ?></title>
    
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    
    <!-- SweetAlert2 -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
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
                                    <input type="password" class="form-control" name="password" id="newPassword" required>
                                    <small class="text-muted">حداقل 8 کاراکتر، شامل حروف بزرگ، کوچک و عدد</small>
                                </div>
                                
                                <div class="mb-3">
                                    <label class="form-label">تکرار رمز عبور جدید</label>
                                    <input type="password" class="form-control" name="password_confirm" required>
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