
            <!-- Mobile Bottom Navigation Bar -->
            <div class="mobile-bottom-nav">
              <div class="mobile-nav-menu">
                <a href="index.php" class="nav-item <?= (basename($_SERVER['PHP_SELF']) == 'index.php') ? 'active' : '' ?>">
                  <i class="bx bx-home nav-icon"></i>
                  <span class="nav-label">خانه</span>
                </a>
                <a href="practice.php" class="nav-item <?= (in_array(basename($_SERVER['PHP_SELF']), ['practice.php', 'practice2.php'])) ? 'active' : '' ?>">
                  <i class="bx bx-task nav-icon"></i>
                  <span class="nav-label">تمرین</span>
                </a>
                <a href="special-categories.php" class="nav-item <?= (basename($_SERVER['PHP_SELF']) == 'special-categories.php') ? 'active' : '' ?>">
                  <i class="bx bx-category nav-icon"></i>
                  <span class="nav-label">دسته بندی</span>
                </a>
                <a href="exams.php" class="nav-item <?= (basename($_SERVER['PHP_SELF']) == 'exams.php') ? 'active' : '' ?>">
                  <i class="bx bx-collection nav-icon"></i>
                  <span class="nav-label">امتحان</span>
                </a>
                <a href="vocabulary.php" class="nav-item <?= (basename($_SERVER['PHP_SELF']) == 'vocabulary.php') ? 'active' : '' ?>">
                  <i class="bx bx-book-open nav-icon"></i>
                  <span class="nav-label">کلمات</span>
                </a>
                <a href="profile-edit.php" class="nav-item <?= (basename($_SERVER['PHP_SELF']) == 'profile-edit.php') ? 'active' : '' ?>">
                  <i class="bx bx-user nav-icon"></i>
                  <span class="nav-label">پروفایل</span>
                </a>
              </div>
              <div class="mobile-nav-divider"></div>
              <div class="mobile-nav-footer">
                طراحی شده توسط <a href="#" target="_blank" class="fw-semibold text-primary">farsi-fahr</a>
              </div>
            </div>

            <style>
            @media (max-width: 991.98px) {

              body {
                padding-bottom: 120px !important;
              }
              
              .mobile-bottom-nav {
                position: fixed;
                bottom: 15px;
                left: 50%;
                transform: translateX(-50%);
                width: 92%;
                max-width: 480px;
                height: 92px;
                background: rgba(35, 45, 63, 0.88);
                backdrop-filter: blur(15px);
                -webkit-backdrop-filter: blur(15px);
                border: 1px solid rgba(255, 255, 255, 0.1);
                border-radius: 20px;
                display: flex;
                flex-direction: column;
                justify-content: center;
                align-items: center;
                z-index: 1099;
                box-shadow: 0 8px 32px rgba(0, 0, 0, 0.3);
                padding: 6px 8px;
                direction: rtl;
              }

              .mobile-nav-menu {
                display: flex;
                width: 100%;
                justify-content: space-evenly;
                align-items: center;
                height: 52px;
              }

              .mobile-nav-divider {
                width: 92%;
                height: 1px;
                background: rgba(255, 255, 255, 0.1);
                margin: 4px 0;
              }

              .light-style .mobile-nav-divider,
              html[data-theme="theme-default"]:not(.dark-style) .mobile-nav-divider {
                background: rgba(0, 0, 0, 0.08);
              }

              .mobile-nav-footer {
                font-size: 10px;
                color: #a3afc5;
                text-align: center;
                line-height: 12px;
              }

              .light-style .mobile-nav-footer,
              html[data-theme="theme-default"]:not(.dark-style) .mobile-nav-footer {
                color: #516377;
              }

              .mobile-nav-footer a {
                color: inherit;
                text-decoration: none;
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
                font-size: 10px;
                font-weight: 500;
                transition: all 0.2s ease-in-out;
                flex: 1;
                padding: 5px 0;
                border-radius: 12px;
                text-align: center;
                white-space: nowrap;
              }

              .light-style .mobile-bottom-nav .nav-item,
              html[data-theme="theme-default"]:not(.dark-style) .mobile-bottom-nav .nav-item {
                color: #516377;
              }

              .mobile-bottom-nav .nav-item .nav-icon {
                font-size: 22px;
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