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
    <title><?= __('login_title', 'ورود به حساب کاربری') ?> | farsifahr</title>
    
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
        <h4 class="mb-0"><?= __('login_title', 'ورود به حساب کاربری') ?></h4>
    </div>
    <div class="auth-body">
        <form id="loginForm">
            <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
            <input type="hidden" name="action" value="login">

            <div class="mb-3">
                <label class="form-label"><?= __('email_label', 'ایمیل') ?></label>
                <input type="email" class="form-control" name="email" placeholder="example@email.com" required>
            </div>

            <div class="mb-3">
                <div class="d-flex justify-content-between align-items-center mb-1">
                    <label class="form-label mb-0"><?= __('password_label', 'رمز عبور') ?></label>
                    <a href="forgot-password.php" class="small text-decoration-none"><?= __('forgot_password', 'فراموشی رمز عبور؟') ?></a>
                </div>
                <div class="input-group">
                    <input type="password" class="form-control" name="password" id="loginPassword" placeholder="••••••••" required>
                    <span class="input-group-text" onclick="togglePasswordVisibility('loginPassword', this)">
                        <i class="fa-solid fa-eye"></i>
                    </span>
                </div>
            </div>

            <div class="mb-3 form-check">
                <input type="checkbox" class="form-check-input" name="remember" id="remember">
                <label class="form-check-label" for="remember"><?= __('remember_me', 'مرا به خاطر بسپار') ?></label>
            </div>

            <div class="d-flex justify-content-center">
                <div class="g-recaptcha" data-sitekey="<?= RECAPTCHA_SITE_KEY ?>"></div>
            </div>

            <button type="submit" class="btn btn-primary w-100 mb-3">
                <i class="fa-solid fa-right-to-bracket me-2"></i> <?= __('login_button', 'ورود') ?>
            </button>

            <div class="divider"><?= __('or', 'یا') ?></div>

            <button type="button" class="btn btn-google w-100" onclick="googleLogin()">
                <img src="https://www.gstatic.com/firebasejs/ui/2.0.0/images/auth/google.svg" width="20">
                <?= __('google_login_button', 'ورود با گوگل') ?>
            </button>
        </form>

        <div class="auth-footer">
            <?= __('no_account', 'حساب کاربری ندارید؟') ?> <a href="register.php"><?= __('register_now', 'همین حالا ثبت نام کنید') ?></a>
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

    document.getElementById('loginForm').addEventListener('submit', async function(e) {
        e.preventDefault();
        
        const formData = new FormData(this);
        const submitBtn = this.querySelector('button[type="submit"]');
        
        submitBtn.disabled = true;
        const originalText = submitBtn.innerHTML;
        submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span><?= __("logging_in", "در حال ورود...") ?>';
        
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
                    timer: 1500,
                    showConfirmButton: false
                }).then(() => {
                    window.location.href = result.redirect;
                });
            } else if (result.status === 'unverified') {
                let countdown = 60;
                let timerInterval;
                
                Swal.fire({
                    icon: 'warning',
                    title: '<?= __("email_verification_required", "تایید ایمیل الزامی است") ?>',
                    html: `
                        <p>${result.message}</p>
                        <div id="resend-wrapper">
                            <button id="resend-btn" class="btn btn-primary btn-sm">
                                <i class="fa-solid fa-envelope me-1"></i> <?= __("resend_verification_email", "ارسال مجدد ایمیل تایید") ?>
                            </button>
                        </div>
                        <div id="timer-text" class="mt-2 text-muted small" style="display:none">
                            <?= __("can_resend_in", "امکان ارسال مجدد تا") ?> <span id="seconds">60</span> <?= __("seconds_later", "ثانیه دیگر") ?>
                        </div>
                    `,
                    showConfirmButton: true,
                    confirmButtonText: '<?= __("got_it", "متوجه شدم") ?>',
                    didOpen: () => {
                        const resendBtn = document.getElementById('resend-btn');
                        const timerText = document.getElementById('timer-text');
                        const secondsSpan = document.getElementById('seconds');
                        
                        resendBtn.addEventListener('click', async () => {
                            resendBtn.disabled = true;
                            resendBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span> <?= __("sending", "در حال ارسال...") ?>';
                            
                            try {
                                const res = await fetch('auth/resend-verification.php', {
                                    method: 'POST',
                                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                                    body: 'email=' + encodeURIComponent(result.email)
                                });
                                const resData = await res.json();
                                
                                if (resData.success) {
                                    Swal.showValidationMessage('');
                                    resendBtn.style.display = 'none';
                                    timerText.style.display = 'block';
                                    
                                    countdown = 60;
                                    timerInterval = setInterval(() => {
                                        countdown--;
                                        secondsSpan.textContent = countdown;
                                        if (countdown <= 0) {
                                            clearInterval(timerInterval);
                                            resendBtn.style.display = 'inline-block';
                                            resendBtn.disabled = false;
                                            resendBtn.innerHTML = '<i class="fa-solid fa-envelope me-1"></i> <?= __("resend_verification_email", "ارسال مجدد ایمیل تایید") ?>';
                                            timerText.style.display = 'none';
                                        }
                                    }, 1000);
                                    
                                    Swal.fire({
                                        icon: 'success',
                                        title: '<?= __("sent", "ارسال شد") ?>',
                                        text: resData.message,
                                        timer: 3000
                                    });
                                } else {
                                    Swal.showValidationMessage(resData.message);
                                    resendBtn.disabled = false;
                                    resendBtn.innerHTML = '<i class="fa-solid fa-envelope me-1"></i> <?= __("resend_verification_email", "ارسال مجدد ایمیل تایید") ?>';
                                }
                            } catch (err) {
                                Swal.showValidationMessage('<?= __("connection_error", "خطا در برقراری ارتباط") ?>');
                                resendBtn.disabled = false;
                                resendBtn.innerHTML = '<i class="fa-solid fa-envelope me-1"></i> <?= __("resend_verification_email", "ارسال مجدد ایمیل تایید") ?>';
                            }
                        });
                    },
                    willClose: () => {
                        clearInterval(timerInterval);
                    }
                });
                if (typeof grecaptcha !== 'undefined') {
                    grecaptcha.reset();
                }
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
