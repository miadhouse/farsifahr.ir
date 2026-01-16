// assets/js/main.js

// تابع رفرش کپچا
function refreshCaptcha() {
    document.getElementById('captchaImage').src = 'incloud/captcha.php?' + Date.now();
}

function refreshCaptcha2() {
    document.getElementById('captchaImage2').src = 'incloud/captcha.php?' + Date.now();
}

// نمایش مدال بازیابی رمز
function showResetModal() {
    const loginModal = bootstrap.Modal.getInstance(document.getElementById('loginModal'));
    loginModal.hide();
    
    const resetModal = new bootstrap.Modal(document.getElementById('resetModal'));
    resetModal.show();
}

// ورود با گوگل
function googleLogin() {
    window.location.href = 'auth/google-login.php';
}

// فرم ورود
document.getElementById('loginForm').addEventListener('submit', async function(e) {
    e.preventDefault();
    
    const formData = new FormData(this);
    const submitBtn = this.querySelector('button[type="submit"]');
    
    // غیرفعال کردن دکمه
    submitBtn.disabled = true;
    submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>در حال ورود...';
    
    try {
        const response = await fetch('auth/auth.php', {
            method: 'POST',
            body: formData
        });
        
        const result = await response.json();
        
        if (result.success) {
            Swal.fire({
                icon: 'success',
                title: 'موفق',
                text: result.message,
                timer: 1500,
                showConfirmButton: false
            }).then(() => {
                window.location.href = result.redirect;
            });
        } else {
            Swal.fire({
                icon: 'error',
                title: 'خطا',
                text: result.message
            });
            refreshCaptcha();
        }
    } catch (error) {
        Swal.fire({
            icon: 'error',
            title: 'خطا',
            text: 'خطا در برقراری ارتباط با سرور'
        });
    } finally {
        submitBtn.disabled = false;
        submitBtn.innerHTML = '<i class="bi bi-box-arrow-in-left"></i> ورود';
    }
});

// فرم ثبت نام
document.getElementById('registerForm').addEventListener('submit', async function(e) {
    e.preventDefault();
    
    const formData = new FormData(this);
    const submitBtn = this.querySelector('button[type="submit"]');
    
    // اعتبارسنجی رمز عبور
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
    submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>در حال ثبت نام...';
    
    try {
        const response = await fetch('auth/auth.php', {
            method: 'POST',
            body: formData
        });
        
        const result = await response.json();
        
        if (result.success) {
            Swal.fire({
                icon: 'success',
                title: 'موفق',
                text: result.message,
                confirmButtonText: 'باشه'
            }).then(() => {
                const registerModal = bootstrap.Modal.getInstance(document.getElementById('registerModal'));
                registerModal.hide();
                this.reset();
            });
        } else {
            Swal.fire({
                icon: 'error',
                title: 'خطا',
                text: result.message
            });
            refreshCaptcha2();
        }
    } catch (error) {
        Swal.fire({
            icon: 'error',
            title: 'خطا',
            text: 'خطا در برقراری ارتباط با سرور'
        });
    } finally {
        submitBtn.disabled = false;
        submitBtn.innerHTML = '<i class="bi bi-person-plus"></i> ثبت نام';
    }
});

// فرم بازیابی رمز
document.getElementById('resetForm').addEventListener('submit', async function(e) {
    e.preventDefault();
    
    const formData = new FormData(this);
    const submitBtn = this.querySelector('button[type="submit"]');
    
    // غیرفعال کردن دکمه
    submitBtn.disabled = true;
    submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>در حال ارسال...';
    
    try {
        const response = await fetch('auth/auth.php', {
            method: 'POST',
            body: formData
        });
        
        const result = await response.json();
        
        if (result.success) {
            Swal.fire({
                icon: 'success',
                title: 'موفق',
                text: result.message,
                confirmButtonText: 'باشه'
            }).then(() => {
                const resetModal = bootstrap.Modal.getInstance(document.getElementById('resetModal'));
                resetModal.hide();
                this.reset();
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
        submitBtn.innerHTML = '<i class="bi bi-envelope"></i> ارسال لینک بازیابی';
    }
});

// اعتبارسنجی real-time رمز عبور
document.getElementById('password').addEventListener('input', function() {
    const password = this.value;
    const feedback = document.createElement('div');
    feedback.className = 'form-text';
    
    const hasUpperCase = /[A-Z]/.test(password);
    const hasLowerCase = /[a-z]/.test(password);
    const hasNumbers = /\d/.test(password);
    const hasMinLength = password.length >= 8;
    
    let strength = 0;
    let message = '';
    
    if (hasMinLength) strength++;
    if (hasUpperCase) strength++;
    if (hasLowerCase) strength++;
    if (hasNumbers) strength++;
    
    if (strength === 0) {
        message = '';
    } else if (strength < 3) {
        feedback.className += ' text-danger';
        message = 'رمز عبور ضعیف';
    } else if (strength === 3) {
        feedback.className += ' text-warning';
        message = 'رمز عبور متوسط';
    } else {
        feedback.className += ' text-success';
        message = 'رمز عبور قوی';
    }
    
    // حذف فیدبک قبلی
    const existingFeedback = this.parentElement.querySelector('.form-text');
    if (existingFeedback && existingFeedback.textContent.includes('رمز عبور')) {
        existingFeedback.remove();
    }
    
    if (message) {
        feedback.textContent = message;
        this.parentElement.appendChild(feedback);
    }
});