        <?php
        $user_initial = mb_substr($_SESSION['name'] ?? 'ک', 0, 1, 'utf-8');
        $user_display_name = $_SESSION['name'] ?? 'کاربر';
        $user_display_role = ($_SESSION['role'] ?? 'user') == 'admin' ? __('admin') : __('user');
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
            <li>
              <a class="dropdown-item" href="<?= SITE_URL ?>logout.php">
                <i class="bx bx-power-off me-2"></i>
                <span class="align-middle"><?= __('logout') ?></span>
              </a>
            </li>
          </ul>
        </li>