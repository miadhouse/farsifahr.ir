<?php
require_once __DIR__ . '/incloud/functions.php';

// Redirect to dashboard if already logged in
if (is_logged_in()) {
    header('Location: admin/');
    exit();
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
    <title><?= __('register_title', 'ثبت نام') ?> | farsifahr</title>
    
    <!-- Favicon -->
    <link href="assets/images/favicon.svg" rel="shortcut icon" type="image/x-icon">

    <!-- CSS Assets -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <?php if (get_lang_dir() === 'rtl'): ?>
    <link href="assets/css/vendor/bootstrap.min.rtl.css" rel="stylesheet">
    <?php endif; ?>
    <link href="assets/css/vendor/fontawesome.css" rel="stylesheet">
    <link href="assets/css/font-ir.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Rubik:wght@300;400;500;700&display=swap" rel="stylesheet">
    <link href="assets/css/auth-dark.css" rel="stylesheet">
</head>
<body>

<div class="auth-bg">
    <div class="blob"></div>
    <div class="blob"></div>
    <div class="blob"></div>
</div>

<div class="auth-card">
    <div class="auth-header">
        <a href="index.php">
            <img src="assets/images/logo/logoAsset%201.svg" alt="farsifahr">
        </a>
        <h4 class="mb-0"><?= __('register_title', 'ثبت نام حساب کاربری') ?></h4>
    </div>
    <div class="auth-body">
        <form id="registerForm">
            <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
            <input type="hidden" name="action" value="register">

            <div class="row">
                <div class="col-md-12 mb-3">
                    <label class="form-label"><?= __('full_name_label', 'نام و نام خانوادگی') ?></label>
                    <input type="text" class="form-control" name="name" placeholder="John Doe" required>
                </div>
                <div class="col-md-12 mb-3">
                    <label class="form-label"><?= __('email_label', 'ایمیل') ?></label>
                    <input type="email" class="form-control" name="email" placeholder="example@email.com" required>
                </div>
            </div>

            <div class="mb-3">
                <label class="form-label"><?= __('password_label', 'رمز عبور') ?></label>
                <div class="input-group">
                    <input type="password" class="form-control" name="password" id="registerPassword" placeholder="••••••••" required>
                    <span class="input-group-text" onclick="togglePasswordVisibility('registerPassword', this)">
                        <i class="fa-solid fa-eye"></i>
                    </span>
                </div>
                <div class="password-strength">
                    <div id="strengthBar" class="password-strength-bar"></div>
                </div>
                <small class="text-muted d-block mt-1" id="strengthText"></small>
            </div>

            <div class="mb-3">
                <label class="form-label"><?= __('confirm_password_label', 'تکرار رمز عبور') ?></label>
                <div class="input-group">
                    <input type="password" class="form-control" name="password_confirm" id="registerPasswordConfirm" placeholder="••••••••" required>
                    <span class="input-group-text" onclick="togglePasswordVisibility('registerPasswordConfirm', this)">
                        <i class="fa-solid fa-eye"></i>
                    </span>
                </div>
            </div>

            <div class="d-flex justify-content-center">
                <div class="g-recaptcha" data-sitekey="<?= RECAPTCHA_SITE_KEY ?>"></div>
            </div>

            <button type="submit" class="btn btn-primary w-100 mb-3">
                <i class="fa-solid fa-user-plus me-2"></i> <?= __('register_button', 'ثبت نام') ?>
            </button>

            <div class="divider"><?= __('or', 'یا') ?></div>

            <button type="button" class="btn btn-google w-100" onclick="googleLogin()">
                <img src="https://www.gstatic.com/firebasejs/ui/2.0.0/images/auth/google.svg" width="20">
                <?= __('google_register_button', 'ثبت نام با گوگل') ?>
            </button>
        </form>

        <div class="auth-footer">
            <?= __('already_have_account', 'قبلا ثبت نام کرده‌اید؟') ?> <a href="login.php"><?= __('login_now', 'وارد شوید') ?></a>
        </div>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script src="https://www.google.com/recaptcha/api.js?hl=<?= get_current_lang() ?>" async defer></script>

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

    function googleLogin() {
        window.location.href = 'auth/google-login.php';
    }

    // Password strength meter
    document.getElementById('registerPassword').addEventListener('input', function() {
        const password = this.value;
        const bar = document.getElementById('strengthBar');
        const text = document.getElementById('strengthText');
        
        let strength = 0;
        if (password.length >= 8) strength++;
        if (/[A-Z]/.test(password)) strength++;
        if (/[a-z]/.test(password)) strength++;
        if (/[0-9]/.test(password)) strength++;
        if (/[^A-Za-z0-9]/.test(password)) strength++;

        const colors = ['#e2e8f0', '#ef4444', '#f59e0b', '#10b981', '#059669'];
        const messages = ['', '<?= __("very_weak", "خیلی ضعیف") ?>', '<?= __("weak", "ضعیف") ?>', '<?= __("medium", "متوسط") ?>', '<?= __("strong", "قوی") ?>'];
        
        const index = Math.min(strength, 4);
        bar.style.width = (index * 25) + '%';
        bar.style.backgroundColor = colors[index];
        text.textContent = messages[index];
        text.style.color = colors[index];
    });

    document.getElementById('registerForm').addEventListener('submit', async function(e) {
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
        submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span><?= __("registering", "در حال ثبت نام...") ?>';
        
        try {
            const response = await fetch('auth/auth.php', {
                method: 'POST',
                body: formData
            });
            
            const result = await response.json();
            
            if (result.success) {
                Swal.fire({
                    icon: 'success',
                    title: '<?= __("success", "موفق") ?>',
                    text: result.message,
                    confirmButtonText: '<?= __("ok", "باشه") ?>'
                }).then(() => {
                    window.location.href = 'login.php';
                });
            } else {
                Swal.fire({
                    icon: 'error',
                    title: '<?= __("error", "خطا") ?>',
                    text: result.message
                });
                if (typeof grecaptcha !== 'undefined') {
                    grecaptcha.reset();
                }
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
</script>

</body>
</html>
