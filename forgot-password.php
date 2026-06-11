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
    <title><?= __('forgot_password_title', 'فراموشی رمز عبور') ?> | farsifahr</title>
    
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
        <h4 class="mb-0"><?= __('forgot_password_title', 'بازیابی رمز عبور') ?></h4>
    </div>
    <div class="auth-body">
        <p class="text-muted text-center mb-4">
            <?= __('forgot_password_desc', 'ایمیل خود را وارد کنید تا لینک بازیابی رمز عبور برای شما ارسال شود.') ?>
        </p>
        
        <form id="resetForm">
            <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
            <input type="hidden" name="action" value="reset">

            <div class="mb-4">
                <label class="form-label"><?= __('email_label', 'ایمیل حساب کاربری') ?></label>
                <input type="email" class="form-control" name="email" placeholder="example@email.com" required>
            </div>

            <button type="submit" class="btn btn-primary w-100">
                <i class="fa-solid fa-paper-plane me-2"></i> <?= __('send_reset_link', 'ارسال لینک بازیابی') ?>
            </button>
        </form>

        <a href="login.php" class="back-to-login">
            <i class="fa-solid fa-arrow-<?= get_lang_dir() === 'rtl' ? 'left' : 'right' ?>"></i>
            <?= __('back_to_login', 'بازگشت به صفحه ورود') ?>
        </a>

        <div class="auth-footer">
            <?= __('no_account', 'حساب کاربری ندارید؟') ?> <a href="register.php"><?= __('register_now', 'ثبت نام کنید') ?></a>
        </div>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<script>
    document.getElementById('resetForm').addEventListener('submit', async function(e) {
        e.preventDefault();
        
        const formData = new FormData(this);
        const submitBtn = this.querySelector('button[type="submit"]');
        
        submitBtn.disabled = true;
        const originalText = submitBtn.innerHTML;
        submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span><?= __("sending", "در حال ارسال...") ?>';
        
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
                    this.reset();
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
</script>

</body>
</html>
