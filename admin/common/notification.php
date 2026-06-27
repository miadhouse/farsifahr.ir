<?php
$unread_count = 0;
if (isset($_SESSION['user_id'])) {
    $unread_count = get_unread_announcements_count($_SESSION['user_id']);
}
?>
<li class="nav-item navbar-dropdown dropdown me-3 me-xl-2">
    <a class="nav-link hide-arrow" href="announcements.php" title="اعلان‌ها">
        <i class="bx bx-bell bx-sm"></i>
        <?php if ($unread_count > 0): ?>
            <span class="badge bg-danger rounded-pill badge-notifications" style="position: absolute; top: 5px; right: 2px; padding: 0.25em 0.6em; font-size: 0.7rem;"><?= $unread_count ?></span>
        <?php endif; ?>
    </a>
</li>