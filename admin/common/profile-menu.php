        <?php
        $user_initial = mb_substr($_SESSION['name'] ?? 'ک', 0, 1, 'utf-8');
        $user_display_name = $_SESSION['name'] ?? 'کاربر';
        $user_display_role = is_super_admin() ? __('admin') : __('user');
        ?>
        <li class="nav-item navbar-dropdown dropdown-user dropdown">
          <a class="nav-link dropdown-toggle hide-arrow" href="javascript:void(0);" data-bs-toggle="dropdown">
            <div class="avatar avatar-online">
              <span class="avatar-initial rounded-circle bg-label-primary"><?= $user_initial ?></span>
            </div>
          </a>
          <ul class="dropdown-menu dropdown-menu-end">
            <li>
              <a class="dropdown-item" href="javascript:void(0);">
                <div class="d-flex align-items-center">
                  <div class="flex-shrink-0 me-3">
                    <div class="avatar avatar-online">
                      <span class="avatar-initial rounded-circle bg-label-primary"><?= $user_initial ?></span>
                    </div>
                  </div>
                  <div class="flex-grow-1">
                    <span class="fw-semibold d-block"><?= htmlspecialchars($user_display_name) ?></span>
                    <small><?= $user_display_role ?></small>
                  </div>
                </div>
              </a>
            </li>
            <li>
              <div class="dropdown-divider"></div>
            </li>
            <li>
              <a class="dropdown-item" href="profile-edit.php">
                <i class="bx bx-user me-2"></i>
                <span class="align-middle"><?= __('edit_profile') ?></span>
              </a>
            </li>
            <li>
              <a class="dropdown-item" href="subscription.php">
                <i class="bx bx-wallet me-2"></i>
                <span class="align-middle"><?= __('subscriptions') ?></span>
              </a>
            </li>
            <li>
              <a class="dropdown-item" href="javascript:void(0);">
                <i class="bx bx-cog me-2"></i>
                <span class="align-middle"><?= __('settings') ?></span>
              </a>
            </li>
            <li>
              <div class="dropdown-divider"></div>
            </li>
            <!-- Mobile Language Switcher -->
            <li class="d-xl-none">
              <h6 class="dropdown-header"><?= __('language', 'تغییر زبان') ?></h6>
            </li>
            <li class="d-xl-none">
              <a class="dropdown-item" href="?lang=fa">
                <i class="fi fi-ir fis rounded-circle fs-4 me-1"></i>
                <span class="align-middle">فارسی</span>
              </a>
            </li>
            <li class="d-xl-none">
              <a class="dropdown-item" href="?lang=de">
                <i class="fi fi-de fis rounded-circle fs-4 me-1"></i>
                <span class="align-middle">Deutsch</span>
              </a>
            </li>
            <li class="d-xl-none">
              <a class="dropdown-item" href="?lang=en">
                <i class="fi fi-us fis rounded-circle fs-4 me-1"></i>
                <span class="align-middle">English</span>
              </a>
            </li>
            <li class="d-xl-none">
              <div class="dropdown-divider"></div>
            </li>
            <li>
              <a class="dropdown-item" href="<?= SITE_URL ?>logout.php">
                <i class="bx bx-power-off me-2"></i>
                <span class="align-middle"><?= __('logout') ?></span>
              </a>
            </li>
          </ul>
        </li>