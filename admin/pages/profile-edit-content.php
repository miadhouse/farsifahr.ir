<?php
// دریافت اطلاعات فعلی کاربر
$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$_SESSION['user_id']]);
$user = $stmt->fetch();

if (!$user) {
    echo "کاربر یافت نشد.";
    exit;
}

// اگر فیلد phone وجود نداشته باشد (چون نتوانستم دیتابیس را تغییر دهم)، 
// از یک مقدار خالی استفاده می‌کنیم یا اگر وجود داشت نمایش می‌دهیم.
$phone = $user['phone'] ?? '';
?>

<div class="container-xxl flex-grow-1 container-p-y">
  <h4 class="py-3 breadcrumb-wrapper mb-4">
    <span class="text-muted fw-light">تنظیمات حساب /</span> ویرایش اطلاعات
  </h4>

  <div class="row">
    <div class="col-md-12">
      <div class="card mb-4">
        <h5 class="card-header">اطلاعات پروفایل</h5>
        <!-- Account -->
        <div class="card-body">
          <div class="d-flex align-items-start align-items-sm-center gap-4">
            <div class="avatar avatar-xl">
              <span class="avatar-initial rounded rounded-3 bg-label-primary fs-2"><?= mb_substr($user['name'], 0, 1, 'utf-8') ?></span>
            </div>
            <div class="button-wrapper">
                <h4 class="mb-1"><?= htmlspecialchars($user['name']) ?></h4>
                <p class="text-muted mb-0"><?= htmlspecialchars($user['email']) ?></p>
            </div>
          </div>
        </div>
        <hr class="my-0">
        <div class="card-body">
          <form id="formAccountSettings" method="POST" onsubmit="return false">
            <div class="row">
              <div class="mb-3 col-md-6">
                <label for="name" class="form-label">نام و نام خانوادگی</label>
                <input class="form-control" type="text" id="name" name="name" value="<?= htmlspecialchars($user['name']) ?>" autofocus />
              </div>
              <div class="mb-3 col-md-6">
                <label for="email" class="form-label">ایمیل</label>
                <input class="form-control" type="text" id="email" name="email" value="<?= htmlspecialchars($user['email']) ?>" placeholder="john.doe@example.com" />
              </div>
              <div class="mb-3 col-md-6">
                <label class="form-label" for="phone">شماره همراه</label>
                <div class="input-group input-group-merge">
                  <span class="input-group-text">IR (+98)</span>
                  <input type="text" id="phone" name="phone" class="form-control" placeholder="912 345 6789" value="<?= htmlspecialchars($phone) ?>" />
                </div>
              </div>
            </div>
            
            <div class="row mt-2">
                <h5 class="mb-3 mt-4">تغییر رمز عبور (در صورت نیاز)</h5>
                <div class="mb-3 col-md-6 form-password-toggle">
                  <label class="form-label" for="newPassword">رمز عبور جدید</label>
                  <div class="input-group input-group-merge">
                    <input class="form-control" type="password" id="newPassword" name="newPassword" placeholder="&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;" />
                    <span class="input-group-text cursor-pointer"><i class="bx bx-hide"></i></span>
                  </div>
                </div>
                <div class="mb-3 col-md-6 form-password-toggle">
                  <label class="form-label" for="confirmPassword">تکرار رمز عبور جدید</label>
                  <div class="input-group input-group-merge">
                    <input class="form-control" type="password" name="confirmPassword" id="confirmPassword" placeholder="&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;" />
                    <span class="input-group-text cursor-pointer"><i class="bx bx-hide"></i></span>
                  </div>
                </div>
            </div>

            <div class="mt-4">
              <button type="submit" id="btnSaveProfile" class="btn btn-primary me-2">ذخیره تغییرات</button>
              <button type="reset" class="btn btn-label-secondary">انصراف</button>
            </div>
          </form>
        </div>
        <!-- /Account -->
      </div>
    </div>
  </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const btnSave = document.getElementById('btnSaveProfile');
    const form = document.getElementById('formAccountSettings');

    btnSave.addEventListener('click', function() {
        const formData = new FormData(form);
        
        // غیرفعال کردن دکمه و نمایش لودینگ
        btnSave.disabled = true;
        btnSave.innerHTML = '<span class="spinner-border spinner-border-sm me-1" role="status" aria-hidden="true"></span> در حال ذخیره...';

        $.ajax({
            url: 'ajax-update-profile.php',
            type: 'POST',
            data: $(form).serialize(),
            success: function(response) {
                if (response.success) {
                    Swal.fire({
                        icon: 'success',
                        title: 'موفقیت‌آمیز',
                        text: response.message,
                        confirmButtonText: 'باشه',
                        customClass: { confirmButton: 'btn btn-success' }
                    }).then(() => {
                        if (response.email_changed) {
                            window.location.href = '../logout.php';
                        } else {
                            location.reload();
                        }
                    });
                } else {
                    Swal.fire({
                        icon: 'error',
                        title: 'خطا',
                        text: response.message,
                        confirmButtonText: 'باشه',
                        customClass: { confirmButton: 'btn btn-danger' }
                    });
                }
            },
            error: function() {
                Swal.fire({
                    icon: 'error',
                    title: 'خطا',
                    text: 'ایرادی در برقراری ارتباط با سرور پیش آمد.',
                    confirmButtonText: 'باشه',
                    customClass: { confirmButton: 'btn btn-danger' }
                });
            },
            complete: function() {
                btnSave.disabled = false;
                btnSave.innerHTML = 'ذخیره تغییرات';
            }
        });
    });
});
</script>