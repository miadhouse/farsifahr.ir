// assets/js/main.js

// تغییر وضعیت نمایش رمز عبور
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
        } else if (result.status === 'unverified') {
            let countdown = 60;
            let timerInterval;
            
            Swal.fire({
                icon: 'warning',
                title: 'تایید ایمیل الزامی است',
                html: `
                    <p>${result.message}</p>
                    <div id="resend-wrapper">
                        <button id="resend-btn" class="btn btn-primary btn-sm">
                            <i class="bi bi-envelope"></i> ارسال مجدد ایمیل تایید
                        </button>
                    </div>
                    <div id="timer-text" class="mt-2 text-muted small" style="display:none">
                        امکان ارسال مجدد تا <span id="seconds">60</span> ثانیه دیگر
                    </div>
                `,
                showConfirmButton: true,
                confirmButtonText: 'متوجه شدم',
                didOpen: () => {
                    const resendBtn = document.getElementById('resend-btn');
                    const timerText = document.getElementById('timer-text');
                    const secondsSpan = document.getElementById('seconds');
                    
                    resendBtn.addEventListener('click', async () => {
                        resendBtn.disabled = true;
                        resendBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span> در حال ارسال...';
                        
                        try {
                            const res = await fetch('auth/resend-verification.php', {
                                method: 'POST',
                                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                                body: 'email=' + encodeURIComponent(result.email)
                            });
                            const resData = await res.json();
                            
                            if (resData.success) {
                                Swal.showValidationMessage(''); // پاک کردن پیام خطا
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
                                        resendBtn.innerHTML = '<i class="bi bi-envelope"></i> ارسال مجدد ایمیل تایید';
                                        timerText.style.display = 'none';
                                    }
                                }, 1000);
                                
                                Swal.fire({
                                    icon: 'success',
                                    title: 'ارسال شد',
                                    text: resData.message,
                                    timer: 3000
                                });
                            } else {
                                Swal.showValidationMessage(resData.message);
                                resendBtn.disabled = false;
                                resendBtn.innerHTML = '<i class="bi bi-envelope"></i> ارسال مجدد ایمیل تایید';
                            }
                        } catch (err) {
                            Swal.showValidationMessage('خطا در برقراری ارتباط');
                            resendBtn.disabled = false;
                            resendBtn.innerHTML = '<i class="bi bi-envelope"></i> ارسال مجدد ایمیل تایید';
                        }
                    });
                },
                willClose: () => {
                    clearInterval(timerInterval);
                }
            });
            if (typeof grecaptcha !== 'undefined') {
                const recaptchas = document.querySelectorAll('.g-recaptcha');
                for (let i = 0; i < recaptchas.length; i++) {
                    try { grecaptcha.reset(i); } catch (e) {}
                }
            }
        } else {
            Swal.fire({
                icon: 'error',
                title: 'خطا',
                text: result.message
            });
            if (typeof grecaptcha !== 'undefined') {
                const recaptchas = document.querySelectorAll('.g-recaptcha');
                for (let i = 0; i < recaptchas.length; i++) {
                    try { grecaptcha.reset(i); } catch (e) {}
                }
            }
        }
    } catch (error) {
        Swal.fire({
            icon: 'error',
            title: 'خطا',
            text: 'خطا در برقراری ارتباط با سرور'
        });
        if (typeof grecaptcha !== 'undefined') {
            const recaptchas = document.querySelectorAll('.g-recaptcha');
            for (let i = 0; i < recaptchas.length; i++) {
                try { grecaptcha.reset(i); } catch (e) {}
            }
        }
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
        if (typeof grecaptcha !== 'undefined') {
            const recaptchas = document.querySelectorAll('.g-recaptcha');
            for (let i = 0; i < recaptchas.length; i++) {
                try { grecaptcha.reset(i); } catch (e) {}
            }
        }
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
                if (registerModal) registerModal.hide();
                this.reset();
                
                // Show login modal
                const loginModal = new bootstrap.Modal(document.getElementById('loginModal'));
                loginModal.show();
            });
        } else {
            Swal.fire({
                icon: 'error',
                title: 'خطا',
                text: result.message
            });
            if (typeof grecaptcha !== 'undefined') {
                const recaptchas = document.querySelectorAll('.g-recaptcha');
                for (let i = 0; i < recaptchas.length; i++) {
                    try { grecaptcha.reset(i); } catch (e) {}
                }
            }
        }
    } catch (error) {
        Swal.fire({
            icon: 'error',
            title: 'خطا',
            text: 'خطا در برقراری ارتباط با سرور'
        });
        if (typeof grecaptcha !== 'undefined') {
            const recaptchas = document.querySelectorAll('.g-recaptcha');
            for (let i = 0; i < recaptchas.length; i++) {
                try { grecaptcha.reset(i); } catch (e) {}
            }
        }
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