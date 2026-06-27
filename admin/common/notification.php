<?php
$unread_count = 0;
if (isset($_SESSION['user_id'])) {
    $unread_count = get_unread_announcements_count($_SESSION['user_id']);
}
?>
<li class="nav-item navbar-dropdown dropdown me-3 me-xl-2" style="list-style: none;">
    <a class="nav-link hide-arrow" href="announcements.php" title="اعلان‌ها" style="position: relative; display: inline-block;">
        <i class="bx bx-bell bx-sm"></i>
        <?php if ($unread_count > 0): ?>
            <span class="badge bg-danger rounded-pill badge-notifications" style="position: absolute; top: -2px; right: -4px; padding: 0.25em 0.5em; font-size: 0.65rem; min-width: 16px; height: 16px; display: inline-flex; align-items: center; justify-content: center; z-index: 10;"><?= $unread_count ?></span>
        <?php endif; ?>
    </a>
</li>