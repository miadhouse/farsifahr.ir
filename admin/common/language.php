<?php
// admin/common/language.php
$curr = get_current_lang();
$flag = $curr == 'fa' ? 'ir' : ($curr == 'en' ? 'us' : 'de');
$lang_name = $curr == 'fa' ? 'فارسی' : ($curr == 'en' ? 'English' : 'Deutsch');
?>
<li class="nav-item dropdown-language dropdown me-2 me-xl-0 d-none d-xl-block">
    <a class="nav-link dropdown-toggle hide-arrow" href="javascript:void(0);" data-bs-toggle="dropdown">
        <i class="fi fi-<?= $flag ?> fis rounded-circle fs-3 me-1"></i>
    </a>
    <ul class="dropdown-menu dropdown-menu-end">
        <li>
            <a class="dropdown-item <?= $curr == 'fa' ? 'active' : '' ?>" href="?lang=fa">
                <i class="fi fi-ir fis rounded-circle fs-4 me-1"></i>
                <span class="align-middle">فارسی</span>
            </a>
        </li>
        <li>
            <a class="dropdown-item <?= $curr == 'de' ? 'active' : '' ?>" href="?lang=de">
                <i class="fi fi-de fis rounded-circle fs-4 me-1"></i>
                <span class="align-middle">Deutsch</span>
            </a>
        </li>
        <li>
            <a class="dropdown-item <?= $curr == 'en' ? 'active' : '' ?>" href="?lang=en">
                <i class="fi fi-us fis rounded-circle fs-4 me-1"></i>
                <span class="align-middle">English</span>
            </a>
        </li>
    </ul>
</li>
