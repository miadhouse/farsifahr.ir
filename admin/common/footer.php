      <footer class="content-footer footer bg-footer-theme">
              <div class="container-fluid d-flex flex-wrap justify-content-between py-3 flex-md-row flex-column">
                <div class="mb-2 mb-md-0">
                  <?= __('designed_by', 'طراحی شده توسط وبسایت') ?>
                  <a href="#" target="_blank" class="footer-link fw-semibold">farsi-fahr</a>
                </div>
                <div>
                  <a href="#" class="footer-link me-4" target="_blank"><?= __('license', 'لایسنس') ?></a>
                  <a href="#" target="_blank" class="footer-link d-none d-sm-inline-block"><?= __('support', 'پشتیبانی') ?></a>
                </div>
              </div>
            </footer>

            <!-- Mobile Bottom Navigation Bar -->
            <div class="mobile-bottom-nav">
              <a href="index.php" class="nav-item <?= (basename($_SERVER['PHP_SELF']) == 'index.php') ? 'active' : '' ?>">
                <i class="bx bx-home nav-icon"></i>
                <span class="nav-label">خانه</span>
              </a>
              <a href="practice.php" class="nav-item <?= (in_array(basename($_SERVER['PHP_SELF']), ['practice.php', 'special-categories.php', 'practice2.php'])) ? 'active' : '' ?>">
                <i class="bx bx-task nav-icon"></i>
                <span class="nav-label">تمرین</span>
              </a>
              <a href="exams.php" class="nav-item <?= (basename($_SERVER['PHP_SELF']) == 'exams.php') ? 'active' : '' ?>">
                <i class="bx bx-collection nav-icon"></i>
                <span class="nav-label">امتحان</span>
              </a>
              <a href="profile-edit.php" class="nav-item <?= (basename($_SERVER['PHP_SELF']) == 'profile-edit.php') ? 'active' : '' ?>">
                <i class="bx bx-user nav-icon"></i>
                <span class="nav-label">پروفایل</span>
              </a>
            </div>

            <style>
            @media (max-width: 991.98px) {
              body {
                padding-bottom: 90px !important;
              }
              
              .mobile-bottom-nav {
                position: fixed;
                bottom: 15px;
                left: 50%;
                transform: translateX(-50%);
                width: 90%;
                max-width: 400px;
                height: 65px;
                background: rgba(35, 45, 63, 0.88);
                backdrop-filter: blur(15px);
                -webkit-backdrop-filter: blur(15px);
                border: 1px solid rgba(255, 255, 255, 0.1);
                border-radius: 20px;
                display: flex;
                justify-content: space-evenly;
                align-items: center;
                z-index: 1099;
                box-shadow: 0 8px 32px rgba(0, 0, 0, 0.3);
                padding: 0 8px;
                direction: rtl;
              }

              /* Support for light style theme classes */
              .light-style .mobile-bottom-nav,
              html[data-theme="theme-default"]:not(.dark-style) .mobile-bottom-nav {
                background: rgba(255, 255, 255, 0.9);
                border: 1px solid rgba(0, 0, 0, 0.08);
                box-shadow: 0 8px 32px rgba(0, 0, 0, 0.1);
              }

              .mobile-bottom-nav .nav-item {
                display: flex;
                flex-direction: column;
                align-items: center;
                justify-content: center;
                color: #a3afc5;
                text-decoration: none;
                font-size: 11px;
                font-weight: 500;
                transition: all 0.2s ease-in-out;
                width: 22%;
                padding: 5px 0;
                border-radius: 12px;
              }

              .light-style .mobile-bottom-nav .nav-item,
              html[data-theme="theme-default"]:not(.dark-style) .mobile-bottom-nav .nav-item {
                color: #516377;
              }

              .mobile-bottom-nav .nav-item .nav-icon {
                font-size: 24px;
                margin-bottom: 2px;
                transition: transform 0.2s ease-in-out;
              }

              .mobile-bottom-nav .nav-item:hover {
                color: #5a8dee;
              }

              .mobile-bottom-nav .nav-item.active {
                color: #5a8dee;
                font-weight: 600;
              }

              .mobile-bottom-nav .nav-item.active .nav-icon {
                transform: translateY(-2px) scale(1.05);
                text-shadow: 0 4px 10px rgba(90, 141, 238, 0.3);
              }
            }

            @media (min-width: 992px) {
              .mobile-bottom-nav {
                display: none !important;
              }
            }
            </style>