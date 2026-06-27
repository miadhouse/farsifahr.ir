<?php
// reset-password.php
require_once __DIR__ . '/../incloud/functions.php';

$token = $_GET['token'] ?? '';
$error = '';
$valid_token = false;

if (empty($token)) {
    $error = __('invalid_token', 'توکن نامعتبر است');
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
        $error = __('expired_token', 'توکن منقضی شده یا نامعتبر است');
    }
}

// CSRF Token for security
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
?>
<!DOCTYPE html>
<html lang="<?= get_current_lang() ?>" dir="<?= get_lang_dir() ?>">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= __('reset_password_title', 'تغییر رمز عبور') ?> | farsifahr</title>
    
    <!-- Favicon -->
    <link href="../assets/images/favicon.svg" rel="shortcut icon" type="image/x-icon">

    <!-- CSS Assets -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <?php if (get_lang_dir() === 'rtl'): ?>
    <link href="../assets/css/vendor/bootstrap.min.rtl.css" rel="stylesheet">
    <?php endif; ?>
    <link href="../assets/css/vendor/fontawesome.css" rel="stylesheet">
    <link href="../assets/css/font-ir.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Rubik:wght@300;400;500;700&display=swap" rel="stylesheet">
    <link href="../assets/css/auth-dark.css?v=1.1" rel="stylesheet">
</head>
<body>

<div class="auth-bg">
    <div class="blob"></div>
    <div class="blob"></div>
    <div class="blob"></div>
</div>

<div class="auth-card">
    <div class="auth-header">
        <a href="../index.php">
            <img src="../assets/images/logo/logoAsset%201.svg" alt="farsifahr">
        </a>
        <h4 class="mb-0"><?= __('reset_password_title', 'تعیین رمز عبور جدید') ?></h4>
    </div>
    <div class="auth-body">
        <?php if (!$valid_token): ?>
            <div class="alert-custom">
                <i class="fa-solid fa-circle-exclamation"></i>
                <span><?= $error ?></span>
            </div>
            <div class="text-center">
                <a href="../login.php" class="btn btn-primary w-100">
                    <?= __('back_to_login', 'بازگشت به صفحه ورود') ?>
                </a>
            </div>
        <?php else: ?>
            <p class="text-muted text-center mb-4">
                <?= __('enter_new_password', 'لطفا رمز عبور جدید خود را وارد کنید.') ?>
            </p>

            <form id="newPasswordForm">
                <input type="hidden" name="token" value="<?= htmlspecialchars($token) ?>">
                <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                
                <div class="mb-3">
                    <label class="form-label"><?= __('new_password_label', 'رمز عبور جدید') ?></label>
                    <div class="input-group">
                        <input type="password" class="form-control" name="password" id="newPassword" placeholder="••••••••" required>
                        <span class="input-group-text" onclick="togglePasswordVisibility('newPassword', this)">
                            <i class="fa-solid fa-eye"></i>
                        </span>
                    </div>
                    <small class="text-muted"><?= __('password_hint', 'حداقل 8 کاراکتر، شامل حروف بزرگ، کوچک و عدد') ?></small>
                </div>
                
                <div class="mb-4">
                    <label class="form-label"><?= __('confirm_password_label', 'تکرار رمز عبور جدید') ?></label>
                    <div class="input-group">
                        <input type="password" class="form-control" name="password_confirm" id="confirmPassword" placeholder="••••••••" required>
                        <span class="input-group-text" onclick="togglePasswordVisibility('confirmPassword', this)">
                            <i class="fa-solid fa-eye"></i>
                        </span>
                    </div>
                </div>
                
                <button type="submit" class="btn btn-primary w-100">
                    <i class="fa-solid fa-check-circle me-2"></i> <?= __('update_password', 'تغییر رمز عبور') ?>
                </button>
            </form>
        <?php endif; ?>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

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

    <?php if ($valid_token): ?>
    document.getElementById('newPasswordForm').addEventListener('submit', async function(e) {
        e.preventDefault();
        
        const formData = new FormData(this);
        const submitBtn = this.querySelector('button[type="submit"]');
        
        if (formData.get('password') !== formData.get('password_confirm')) {
            Swal.fire({
                icon: 'error',
                title: '<?= __("error", "خطا") ?>',
                text: '<?= __("password_mismatch", "رمز عبور و تکرار آن مطابقت ندارند") ?>'
            });
            return;
        }
        
        submitBtn.disabled = true;
        const originalText = submitBtn.innerHTML;
        submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span><?= __("updating", "در حال تغییر...") ?>';
        
        try {
            const response = await fetch('process-reset.php', {
                method: 'POST',
                body: formData
            });
            
            const result = await response.json();
            
            if (result.success) {
                Swal.fire({
                    icon: 'success',
                    title: '<?= __("success", "موفق") ?>',
                    text: result.message,
                    confirmButtonText: '<?= __("login_now", "ورود به حساب") ?>'
                }).then(() => {
                    window.location.href = '../login.php';
                });
            } else {
                Swal.fire({
                    icon: 'error',
                    title: '<?= __("error", "خطا") ?>',
                    text: result.message
                });
            }
        } catch (error) {
            Swal.fire({
                icon: 'error',
                title: '<?= __("error", "خطا") ?>',
                text: '<?= __("connection_error", "خطا در برقراری ارتباط") ?>'
            });
        } finally {
            submitBtn.disabled = false;
            submitBtn.innerHTML = originalText;
        }
    });
    <?php endif; ?>
</script>

</body>
</html>
