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
      <a href="/#study-plan" class="menu-link">
        <i class="menu-icon tf-icons bx bx-calendar-event"></i>
        <div><?= __('my_study_plan') ?></div>
      </a>
    </li>
    <li class="menu-item">
      <a href="subscription.php" class="menu-link">
        <i class="menu-icon tf-icons bx bx-wallet"></i>
        <div data-i18n="subscription"><?= __('subscription') ?></div>
      </a>
    </li>
    <li class="menu-item">
      <a href="free-subscription.php" class="menu-link">
        <i class="menu-icon tf-icons bx bx-gift"></i>
        <div><?= __('free_subscription') ?></div>
      </a>
    </li>
    <li class="menu-item">
      <a href="vocabulary.php" class="menu-link">
        <i class="menu-icon tf-icons bx bx-book-open"></i>
        <div><?= __('vocabulary') ?></div>
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