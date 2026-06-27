<?php
require_once('../incloud/functions.php');

// بررسی ورود کاربر
if (!is_logged_in()) {
    header('Location: /register.php');
    exit;
}

$user_id = $_SESSION['user_id'];
$announcements = get_user_announcements($user_id);
?>

<div class="col-12 mb-4">
    <div class="card">
        <div class="card-header bg-label-primary d-flex justify-content-between align-items-center py-3" style="direction: rtl;">
            <h5 class="mb-0 d-flex align-items-center gap-2">
                <i class="bx bx-bell fs-4"></i>
                <span class="fw-bold">لیست اعلان‌های سیستم</span>
            </h5>
            <span class="badge bg-primary rounded-pill"><?= count($announcements) ?> اعلان فعال</span>
        </div>
        <div class="card-body mt-3" style="direction: rtl;">
            <?php if (count($announcements) > 0): ?>
                <div class="announcement-list">
                    <?php foreach ($announcements as $ann): ?>
                        <div class="announcement-card-item p-3 mb-3 border rounded <?php echo $ann['is_read'] ? 'bg-light border-light-subtle' : 'border-primary-subtle bg-primary-opacity-5'; ?>" style="position: relative; transition: all 0.2s;">
                            <div class="d-flex justify-content-between align-items-start mb-2">
                                <h6 class="mb-0 fw-bold d-flex align-items-center gap-2">
                                    <?php if (!$ann['is_read']): ?>
                                        <span class="badge bg-danger p-1 rounded-circle" style="width: 8px; height: 8px;" title="خوانده نشده"></span>
                                        <span class="badge bg-label-danger btn-xs" style="font-size: 0.75rem;">جدید</span>
                                    <?php endif; ?>
                                    <?= htmlspecialchars($ann['title']) ?>
                                </h6>
                                <small class="text-muted">
                                    <i class="bx bx-calendar me-1"></i>
                                    <?= date('Y/m/d H:i', strtotime($ann['created_at'])) ?>
                                </small>
                            </div>
                            <div class="announcement-card-body text-secondary mt-2" style="line-height: 1.7; font-size: 0.92rem;">
                                <?= $ann['content'] ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <div class="text-center py-5">
                    <i class="bx bx-bell-off text-muted mb-2" style="font-size: 3rem;"></i>
                    <p class="text-muted mb-0">در حال حاضر هیچ اعلانی برای نمایش وجود ندارد.</p>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<style>
.bg-primary-opacity-5 {
    background-color: rgba(105, 108, 255, 0.05) !important;
}
.border-primary-subtle {
    border-color: rgba(105, 108, 255, 0.25) !important;
}
.announcement-card-item:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.05);
}
.announcement-card-body img, .announcement-card-body video, .announcement-card-body iframe {
    max-width: 100%;
    height: auto;
    border-radius: 6px;
    margin: 10px 0;
}
</style>
