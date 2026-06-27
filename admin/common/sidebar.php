<!-- Menu -->
<aside id="layout-menu" class="layout-menu menu-vertical menu bg-menu-theme">
  <div class="app-brand demo">
    <a href="index.php" class="app-brand-link mt-3">
      <span class="app-brand-logo demo" style="width: 50px !important; height: 80px!important;">
        <img src="../assets/images/logo/logoAsset 5.svg" alt="Logo">
      </span>
    </a>

    <a href="javascript:void(0);" class="layout-menu-toggle menu-link text-large ms-auto">
      <i class="bx menu-toggle-icon d-none d-xl-block fs-4 align-middle"></i>
      <i class="bx bx-x d-block d-xl-none bx-sm align-middle"></i>
    </a>
  </div>

  <div class="menu-divider mt-0"></div>

  <div class="menu-inner-shadow"></div>

  <ul class="menu-inner py-1">
    <li class="menu-item">
      <a href="index.php" class="menu-link">
        <i class="menu-icon tf-icons bx bx-home"></i>
        <div><?= __('dashboard') ?></div>
      </a>
    </li>
    <?php if (is_super_admin()): ?>
    <li class="menu-item">
      <a href="/panel" class="menu-link">
        <i class="menu-icon tf-icons bx bx-shield-quarter"></i>
        <div class="text-warning"><?= __('admin_panel', 'پنل مدیریت اصلی') ?></div>
      </a>
    </li>
    <?php endif; ?>
    <li class="menu-item">
      <a href="javascript:void(0);" class="menu-link menu-toggle">
        <i class="menu-icon tf-icons bx bx-task"></i>
        <div><?= __('practice') ?></div>
      </a>
      <ul class="menu-sub">
        <li class="menu-item">
          <a href="practice.php" class="menu-link">
            <div><?= __('main_categories') ?></div>
          </a>
        </li>
        <li class="menu-item">
          <a href="special-categories.php" class="menu-link">
            <div><?= __('special_categories') ?></div>
          </a>
        </li>
      </ul>
    </li>
    <li class="menu-item">
      <a href="subscription.php" class="menu-link">
        <i class="menu-icon tf-icons bx bx-wallet"></i>
        <div data-i18n="subscription"><?= __('subscription') ?></div>
      </a>
    </li>
    <li class="menu-item">
      <a href="free-subscription.php" class="menu-link">
        <i class="menu-icon tf-icons bx bx-medal"></i>
        <div><?= __('earn_points') ?></div>
      </a>
    </li>
    <li class="menu-item">
      <a href="exams.php" class="menu-link">
        <i class="menu-icon tf-icons bx bx-collection"></i>
        <div><?= __('exam_simulator') ?></div>
      </a>
    </li>
    <li class="menu-item">
      <a href="vocabulary.php" class="menu-link">
        <i class="menu-icon tf-icons bx bx-book-open"></i>
        <div><?= __('vocabulary') ?></div>
      </a>
    </li>
    <li class="menu-item">
      <a href="my-requests.php" class="menu-link">
        <i class="menu-icon tf-icons bx bx-list-ul"></i>
        <div><?= __('my_requests', 'درخواست‌های من') ?></div>
      </a>
    </li>
    <li class="menu-item">
      <a href="reports.php" class="menu-link">
        <i class="menu-icon tf-icons bx bx-bug"></i>
        <div><?= __('reports') ?></div>
      </a>
    </li>
  </ul>
</aside>
<!-- / Menu -->