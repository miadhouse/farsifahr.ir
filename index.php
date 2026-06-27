<?php
/**
 * PHP 8.4 Pure Functional Script
 */

// Include configuration
require_once __DIR__ . '/incloud/functions.php';
require_once __DIR__ . '/incloud/subscription-functions.php';

// Redirect to dashboard if already logged in (for PWA and direct access)
if (is_logged_in()) {
    header('Location: admin/');
    exit();
}

// دریافت تنظیمات خدمات جانبی
$stmt_services = $pdo->prepare("SELECT * FROM service_settings WHERE is_active = 1");
$stmt_services->execute();
$services_list = $stmt_services->fetchAll();
$services = [];
foreach ($services_list as $s) {
    $services[$s['service_key']] = $s;
}

$translation_title = $services['translation']['title'] ?? 'ترجمه رسمی گواهینامه';
$translation_desc = $services['translation']['description'] ?? 'ترجمه رسمی گواهینامه رانندگی ایرانی شما به آلمانی توسط مترجم رسمی قسم‌خورده.';
$translation_price = isset($services['translation']['price']) ? number_format($services['translation']['price']) : '50';

$eyetest_title = $services['eyetest']['title'] ?? 'نوبت تست چشم‌پزشکی';
$eyetest_desc = $services['eyetest']['description'] ?? 'یکی از پیش‌نیازهای دریافت گواهینامه رانندگی در آلمان، تست چشم‌پزشکی است.';

$firstaid_title = $services['firstaid']['title'] ?? 'کورس کمک‌های اولیه (Erste Hilfe)';
$firstaid_desc = $services['firstaid']['description'] ?? 'شرکت در دوره کمک‌های اولیه برای گرفتن گواهینامه آلمانی اجباری است.';

// جلوگیری از کش شدن صفحه (هم برای مهمان‌ها به دلیل CSRF و هم برای جلوگیری از نمایش محتوای قدیمی)
header('Vary: Cookie');
header('Cache-Control: no-cache, no-store, must-revalidate'); // HTTP 1.1
header('Pragma: no-cache'); // HTTP 1.0
header('Expires: 0'); // Proxies
header('X-LiteSpeed-Cache-Control: no-cache'); // LiteSpeed Server
?>
<html lang="<?= get_current_lang() ?>" dir="<?= get_lang_dir() ?>">

<head>
    <meta charset="utf-8">
    <meta content="IE=edge" http-equiv="X-UA-Compatible">
    <meta content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no" name="viewport">
    
    <!-- SEO Meta Tags -->
    <title><?= __('site_title', 'farsifahr | آموزش و آمادگی آزمون تئوری گواهینامه آلمانی') ?></title>
    <meta name="description" content="<?= __('site_description', 'جامع‌ترین مرجع آموزش و آمادگی آزمون تئوری گواهینامه آلمانی به زبان فارسی. ترجمه سوالات، آزمون آزمایشی و آموزش‌های رایگان گواهینامه آلمانی به فارسی.') ?>">
    <meta name="keywords" content="گواهینامه آلمانی, آموزش گواهینامه آلمانی, ترجمه سوالات گواهینامه آلمانی, گواهینامه آلمانی به فارسی, رایگان, آزمون تئوری گواهینامه آلمانی, farsi fahr, سوالات گواهینامه آلمان">
    <link rel="canonical" href="<?= SITE_URL ?>">
    
    <!-- Language Alternates (Hreflang) -->
    <link rel="alternate" hreflang="fa" href="<?= SITE_URL ?>?lang=fa" />
    <link rel="alternate" hreflang="de" href="<?= SITE_URL ?>?lang=de" />
    <link rel="alternate" hreflang="en" href="<?= SITE_URL ?>?lang=en" />
    <link rel="alternate" hreflang="x-default" href="<?= SITE_URL ?>" />

    <!-- Open Graph / Facebook -->
    <meta property="og:type" content="website">
    <meta property="og:url" content="<?= SITE_URL ?>">
    <meta property="og:title" content="<?= __('site_title', 'farsifahr | آموزش و آمادگی آزمون تئوری گواهینامه آلمانی') ?>">
    <meta property="og:description" content="<?= __('site_description', 'جامع‌ترین مرجع آموزش و آمادگی آزمون تئوری گواهینامه آلمانی به زبان فارسی.') ?>">
    <meta property="og:image" content="<?= SITE_URL ?>assets/images/logo/logoAsset%201.svg">

    <!-- Twitter -->
    <meta property="twitter:card" content="summary_large_image">
    <meta property="twitter:url" content="<?= SITE_URL ?>">
    <meta property="twitter:title" content="<?= __('site_title', 'farsifahr | آموزش و آمادگی آزمون تئوری گواهینامه آلمانی') ?>">
    <meta property="twitter:description" content="<?= __('site_description', 'جامع‌ترین مرجع آموزش و آمادگی آزمون تئوری گواهینامه آلمانی به زبان فارسی.') ?>">
    <meta property="twitter:image" content="<?= SITE_URL ?>assets/images/logo/logoAsset%201.svg">

    <link href="assets/images/favicon.svg" rel="shortcut icon" type="image/x-icon">
    <link href="assets/css/vendor/fontawesome.css" rel="preload" as="style" onload="this.onload=null;this.rel='stylesheet'"><noscript><link rel="stylesheet" href="assets/css/vendor/fontawesome.css"></noscript>
    <link href="assets/css/vendor/animate.min.css" rel="preload" as="style" onload="this.onload=null;this.rel='stylesheet'"><noscript><link rel="stylesheet" href="assets/css/vendor/animate.min.css"></noscript>
    <?php if (get_lang_dir() === 'rtl'): ?>
    <link href="assets/css/plugins/swiper.rtl.css" rel="stylesheet">
    <link href="assets/css/plugins/odometer.rtl.css" rel="stylesheet">
    <link href="assets/css/vendor/bootstrap.min.rtl.css" rel="stylesheet">
    <?php else: ?>
    <link href="assets/css/plugins/swiper.rtl.css" rel="stylesheet">
    <link href="assets/css/plugins/odometer.rtl.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <?php endif; ?>
    <link href="assets/css/style.rtl.css" rel="stylesheet">
    <link href="assets/css/landing-custom.css" rel="stylesheet">
    <link href="assets/css/pwa-section.css?v=1.2" rel="stylesheet">
    <link rel="preload" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css" as="style" onload="this.onload=null;this.rel='stylesheet'"><noscript><link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css"></noscript>
    <link rel="preload" href="https://cdn.jsdelivr.net/gh/lipis/flag-icons@7.2.3/css/flag-icons.min.css" as="style" onload="this.onload=null;this.rel='stylesheet'"><noscript><link rel="stylesheet" href="https://cdn.jsdelivr.net/gh/lipis/flag-icons@7.2.3/css/flag-icons.min.css"></noscript>
    <link href="assets/css/font-ir.css" rel="stylesheet">
    <link rel="stylesheet" href="/chat/widget.css?v=2.5">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&family=Rajdhani:wght@300;400;500;600;700&family=Rubik:ital,wght@0,300..900;1,300..900&display=swap" rel="stylesheet">

    <link rel="manifest" href="/manifest.json">
    <meta name="theme-color" content="#667eea">
    
    <!-- Apple PWA Meta Tags -->
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
    <meta name="apple-mobile-web-app-title" content="farsifahr">
    <link rel="apple-touch-icon" href="/assets/imgT24Logo.png">

    <script src="https://challenges.cloudflare.com/turnstile/v0/api.js" async defer></script>

    <?php if (isset($_SESSION['error'])): ?>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            Swal.fire({
                icon: 'error',
                title: 'خطا',
                text: '<?= htmlspecialchars($_SESSION['error']) ?>',
                confirmButtonText: 'متوجه شدم'
            });
        });
    </script>
    <?php unset($_SESSION['error']); endif; ?>


    <style>
        .cursor-pointer {
            cursor: pointer;
            z-index: 10;
            pointer-events: auto !important;
        }
        /* اصلاح ظاهر اینپوت‌های گروهی برای گرد شدن لبه‌ها */
        .input-group .form-control:first-child {
            border-top-left-radius: 0;
            border-bottom-left-radius: 0;
        }
        .input-group-text {
            background-color: transparent;
            border-right: none;
            border-top-right-radius: 0 !important;
            border-bottom-right-radius: 0 !important;
            color: #6c757d;
            z-index: 10;
        }
        [dir="rtl"] .input-group .form-control:first-child {
            border-top-right-radius: 0.375rem !important;
            border-bottom-right-radius: 0.375rem !important;
            border-top-left-radius: 0 !important;
            border-bottom-left-radius: 0 !important;
            border-left: none;
        }
        [dir="rtl"] .input-group-text {
            border-top-left-radius: 0.375rem !important;
            border-bottom-left-radius: 0.375rem !important;
            border-top-right-radius: 0 !important;
            border-bottom-right-radius: 0 !important;
            border-left: 1px solid #ced4da;
            border-right: none;
            margin-right: -1px;
        }
        .seo-content-section {
            background-color: #0f1113; /* Dark theme background */
            padding: 80px 0;
            border-top: 1px solid #1e2125;
        }
        .seo-text-content h2.title {
            font-size: 2rem;
            margin-bottom: 20px;
            color: #fff;
        }
        .seo-text-content p {
            font-size: 1.1rem;
            color: #adb5bd; /* Light text for dark background */
            margin-bottom: 0;
            text-align: justify;
            text-align-last: center;
        }
        .seo-text-content strong {
            color: #fff;
            font-weight: 600;
        }

        /* Responsive Blog Cards for 2-column mobile */
        @media (max-width: 767px) {
            .blog-card-style-two .blog-card-img img {
                height: 150px !important;
            }
            .blog-card-style-two .blog-content-wrap {
                padding: 15px !important;
            }
            .blog-card-style-two .blog-title {
                font-size: 14px !important;
                line-height: 1.4 !important;
                margin-bottom: 10px !important;
            }
            .blog-card-style-two .blog-tags ul li {
                font-size: 8px !important;
                margin-right: 0 !important;
                white-space: nowrap !important;
                display: flex !important;
                align-items: center !important;
                letter-spacing: -0.1px !important;
            }
            .blog-card-style-two .blog-tags ul {
                display: flex !important;
                flex-wrap: nowrap !important;
                gap: 5px !important;
                padding: 0 !important;
                margin-bottom: 5px !important;
                justify-content: space-between !important;
            }
            .blog-card-style-two .blog-tags ul li i {
                margin-left: 3px !important;
                font-size: 8px !important;
            }
            .blog-card-style-two .blog-tags ul li a {
                padding: 0 !important;
                display: flex !important;
                align-items: center !important;
                font-size: 8px !important;
            }
            .blog-card-style-two .blog-card-img span {
                font-size: 10px !important;
                padding: 2px 8px !important;
                min-width: auto !important;
                width: max-content !important;
                height: auto !important;
                min-height: auto !important;
                line-height: 1.5 !important;
                bottom: 10px !important;
                left: 10px !important;
                top: auto !important;
                right: auto !important;
                position: absolute !important;
                display: inline-block !important;
                border-radius: 5px !important;
                margin: 0 !important;
            }
            .blog-card-style-two .read-more-btn .tmp-btn {
                padding: 5px 10px !important;
                font-size: 11px !important;
                min-height: auto !important;
                height: 32px !important;
                display: flex !important;
                align-items: center !important;
                justify-content: center !important;
            }
            .blog-card-style-two .read-more-btn .tmp-btn .icon-reverse-wrapper {
                justify-content: center !important;
                width: 100% !important;
                display: flex !important;
                align-items: center !important;
            }
            .blog-card-style-two .read-more-btn .tmp-btn .btn-icon {
                display: none !important; /* Hide icons in 2-column mobile to save space and center text better */
            }
        }
    </style>
</head>

<body>
    <!-- PWA Install Banner -->
    <div id="pwa-install-banner">
        <div class="banner-content">
            <i class="fas fa-mobile-alt"></i>
            <span><?= __('pwa_install_text', 'نصب اپلیکیشن farsifahr برای دسترسی سریع‌تر') ?></span>
        </div>
        <div class="banner-actions">
            <button class="btn-install" id="btn-pwa-install"><?= __('install', 'نصب') ?></button>
            <button class="btn-close-banner" id="btn-pwa-close">&times;</button>
        </div>
    </div>
    <header class="tmp-header-area-start header-one header--sticky header--transparent sticky">
        <div class="container">
            <div class="row">
                <div class="col-lg-12">
                    <div class="header-content">
                        <!-- Mobile Hamburger Wrap -->
                        <div class="mobile-hamburger-wrap d-block d-xl-none">
                            <button class="tmp-menu-bars humberger_menu_active"><i
                                    class="fa-regular fa-bars-staggered"></i></button>
                        </div>

                        <div class="logo">
                            <a href="<?= SITE_URL ?>/index.php">
                                <img alt="farsifahr" class="logo-dark" src="assets/images/logo/logoAsset%201.svg">
                                <img alt="farsifahr" class="logo-white" src="assets/images/logo/logoAsset%201.svg">
                            </a>
                        </div>
                        <nav class="tmp-mainmenu-nav d-none d-xl-block">
                            <ul class="tmp-mainmenu">
                                <li class="nav-item">
                                    <a class="nav-link" href="#">
                                        <i class="bi bi-house me-1"></i><?= __('home_title') ?>
                                    </a>
                                </li>
                                <li class="nav-item">
                                    <a class="nav-link" href="#contact">
                                        <i class="bi bi-chat-dots me-1"></i><?= __('contact_us') ?>
                                    </a>
                                </li>
                            </ul>
                        </nav>
                        <div class="header-right-group d-flex align-items-center gap-0">
                            <div class="social-share-wrapper d-none d-xl-block">
                                <div class="social-link"><a target="_blank" href="<?= INSTAGRAM_URL ?>"><i class="fa-brands fa-instagram"></i></a> <a target="_blank" href="<?= TELEGRAM_CHANNEL_URL ?>"><i class="fa-brands fa-telegram"></i></a></div>
                            </div>
                            
                            <?php if (is_logged_in()): ?>
                                <!-- User Dropdown Menu -->
                                <div class="dropdown">
                                    <button class="btn btn-dark btn-lg text-white dropdown-toggle d-flex align-items-center gap-3" 
                                            type="button" 
                                            id="userDropdown" 
                                            data-bs-toggle="dropdown" 
                                            aria-expanded="false"
                                            style="padding: 10px 20px; font-size: 1.1rem;">
                                        <i class="fa-regular fa-user-circle" style="font-size: 1.5rem;"></i>
                                        <span class="d-none d-sm-inline fw-bold"><?= $_SESSION['name'] ?></span>
                                    </button>
                                    <ul style="background-color: #212529 !important;" class="dropdown-menu bg-black text-light w-100  dropdown-menu-end" aria-labelledby="userDropdown">
                                        <li>
                                            <a class="dropdown-item bg-black text-light" href="admin/index.php">
                                                <i class="fa-regular fa-speedometer me-2"></i>
                                                <?= __('dashboard', 'داشبورد') ?>
                                            </a>
                                        </li>
                                        <?php if (is_super_admin()): ?>
                                        <li>
                                            <a class="dropdown-item bg-black text-warning" href="panel">
                                                <i class="fa-regular fa-shield-halved me-2"></i>
                                                <?= __('admin_panel', 'پنل مدیریت') ?>
                                            </a>
                                        </li>
                                        <?php endif; ?>
                                        <li>
                                            <a class="dropdown-item bg-black text-light" href="admin/subscription.php">
                                                <i class="fa-regular fa-crown me-2"></i>
                                                <?= __('subscription', 'اشتراک') ?>
                                            </a>
                                        </li>
                                        <li><hr class="dropdown-divider"></li>
                                        <li>
                                            <a class="dropdown-item bg-black text-danger" href="logout.php">
                                                <i class="fa-regular fa-arrow-right-from-bracket me-2"></i>
                                                <?= __('logout', 'خروج') ?>
                                            </a>
                                        </li>
                                    </ul>
                                </div>
                                <!-- Language Switcher (Logged In) -->
                                <div class="dropdown nav-action-item">
                                    <?php 
                                    $curr = get_current_lang();
                                    $flag = $curr == 'fa' ? 'ir' : ($curr == 'en' ? 'us' : 'de');
                                    ?>
                                    <button class="btn btn-dark btn-sm dropdown-toggle" type="button" data-bs-toggle="dropdown">
                                        <span class="fi fi-<?= $flag ?>"></span>
                                    </button>
                                    <ul class="dropdown-menu dropdown-menu-end bg-dark">
                                        <li><a class="dropdown-item text-white" href="?lang=fa"><span class="fi fi-ir me-2"></span> فارسی</a></li>
                                        <li><a class="dropdown-item text-white" href="?lang=de"><span class="fi fi-de me-2"></span> Deutsch</a></li>
                                        <li><a class="dropdown-item text-white" href="?lang=en"><span class="fi fi-us me-2"></span> English</a></li>
                                    </ul>
                                </div>
                            <?php else: ?>
                                <!-- Guest Actions + Language Wrapper -->
                                <div class="d-flex align-items-center nav-actions-wrapper">
                                    <a href="login.php" class="btn btn-outline-light btn-sm nav-action-item">
                                        <i class="fa-regular fa-arrow-right-to-bracket"></i>
                                        <span class="d-none d-sm-inline"><?= __('login') ?></span>
                                    </a>
                                    <a href="register.php" class="btn btn-outline-primary btn-sm text-white nav-action-item">
                                        <i class="fa-regular fa-user-plus"></i>
                                        <span class="d-none d-sm-inline"><?= __('register') ?></span>
                                    </a>
                                    <!-- Language Switcher -->
                                    <div class="dropdown nav-action-item">
                                        <?php 
                                        $curr = get_current_lang();
                                        $flag = $curr == 'fa' ? 'ir' : ($curr == 'en' ? 'us' : 'de');
                                        ?>
                                        <button class="btn btn-dark btn-sm dropdown-toggle" type="button" data-bs-toggle="dropdown">
                                            <span class="fi fi-<?= $flag ?>"></span>
                                        </button>
                                        <ul class="dropdown-menu dropdown-menu-end bg-dark">
                                            <li><a class="dropdown-item text-white" href="?lang=fa"><span class="fi fi-ir me-2"></span> فارسی</a></li>
                                            <li><a class="dropdown-item text-white" href="?lang=de"><span class="fi fi-de me-2"></span> Deutsch</a></li>
                                            <li><a class="dropdown-item text-white" href="?lang=en"><span class="fi fi-us me-2"></span> English</a></li>
                                        </ul>
                                    </div>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>

                </div>
            </div>
        </div>
    </header>

    <!-- Subscription Status Banner (for logged in users) -->

    <!-- Main Content -->
    <div class="d-none d-xl-block">
        <div class="tmp-sidebar-area tmp_side_bar">
            <div class="inner">
                <div class="top-area"><a class="logo" href="index.html"> <img alt="Farsi - Fahr" class="logo-dark"
                            src="assets/images/logo/logoAsset%201.svg"> <img alt="Farsi - Fahr" class="logo-white"
                            src="assets/images/logo/logoAsset%201.svg"> </a>
                    <div class="close-icon-area">
                        <button class="tmp-round-action-btn close_side_menu_active"><i
                                class="fa-sharp fa-light fa-xmark"></i></button>
                    </div>
                </div>
                <div class="content-wrapper">
                    <div class="image-area-feature"><a href="index.html"> <img alt="personal-logo"
                                src="assets/images/logo/Designer.jpeg"> </a>
                    </div>
                    <h5 class="title mt--30">
                        <?= __('sidebar_title') ?>
                    </h5>
                    <p class="disc">

                    </p>
                    <div class="short-contact-area">
                        <div class="single-contact"><i class="fa-solid fa-phone"></i>
                            <div class="information tmp-link-animation"><span><?= __('whatsapp_contact') ?></span> <a class="number"
                                    href="#">004917661812772</a>
                            </div>
                        </div>
                        <div class="single-contact"><i class="fa-solid fa-envelope"></i>
                            <div class="information tmp-link-animation"><span><?= __('email_contact') ?></span> <a
                                    class="number" href="#">admin@farsiapp.de</a></div>
                        </div>
                    </div>
                    <div class="social-wrapper mt--20"><span class="subtitle"><?= __('follow_us') ?></span>
                        <div class="social-link"><a target="_blank" href="<?= INSTAGRAM_URL ?>"><i class="fa-brands fa-instagram"></i></a> <a target="_blank" href="<?= TELEGRAM_CHANNEL_URL ?>"><i class="fa-brands fa-telegram"></i></a></div>
                    </div>
                </div>
            </div>
        </div>
        <a class="overlay_close_side_menu close_side_menu_active" href="javascript:void(0);"></a>
    </div>
    <div class="d-block d-xl-none">
        <div class="tmp-popup-mobile-menu">
            <div class="inner">
                <div class="header-top">
                    <div class="logo"><a class="logo-area" href="index.html"> <img alt="Farsi - Fahr" class="logo-dark"
                                src="assets/images/logo/logoAsset%201.svg"> <img alt="Farsi - Fahr" class="logo-white"
                                src="assets/images/logo/logoAsset%201.svg"> </a></div>
                    <div class="close-menu">
                        <button class="close-button tmp-round-action-btn"><i class="fa-sharp fa-light fa-xmark"></i>
                        </button>
                    </div>
                </div>
                <ul class="tmp-mainmenu">
                    <li><a href="#"><?= __('home_title') ?></a></li>
                    <li><a href="#contact"><?= __('contact_us') ?></a></li>
                    
                    <!-- PWA Manual Install (Hidden if already standalone) -->
                    <li id="menu-install-li" style="display: none;">
                        <a href="javascript:void(0)" id="btn-manual-install">
                            <i class="fa-regular fa-mobile-screen-button me-2"></i>
                            <?= __('install_app', 'نصب اپلیکیشن') ?>
                        </a>
                    </li>

                    <li class="mt-4">
                        <?php if (is_logged_in()): ?>
                            <a href="admin" class="btn btn-primary w-100 text-white"><?= __('dashboard') ?></a>
                        <?php else: ?>
                            <div class="d-grid gap-2">
                                <a href="login.php" class="btn btn-outline-light"><?= __('login') ?></a>
                                <a href="register.php" class="btn btn-primary text-white" style="background-color: #5a8dee;"><?= __('register') ?></a>
                            </div>
                        <?php endif; ?>
                    </li>
                </ul>
            </div>
        </div>
    </div>
    <div class="rpp-banner-two-area">
        <div class="container">
            <div class="banner-two-main-wrapper">
                <div class="row align-items-center">
                    <div class="col-lg-6 order-lg-2">
                        <div class="banner-right-content">
                            <div class="main-img"><img alt="banner-img"
                                    class="tmp-scroll-trigger tmp-zoom-in animation-order-1"
                                    src="assets/images/banner/banner-user-image-two2.webp">
                                <h2 class="banner-big-text-1 up-down-2">FARSI-FAHR</h2>
                                <h2 class="banner-big-text-2 up-down">FARSI-FAHR</h2>
                                <div class="benner-two-bg-red-img"><img alt="red-img"
                                        src="assets/images/banner/banner-user-image-two-red-bg.png">
                                </div>
                                <div class="logo-under-img-wrap">
                                    <div class="logo-under-img"><img alt="logo-under-image" style="opacity: .3"
                                            src="assets/images/banner/logo-under-image.png"></div>

                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-lg-6 order-lg-1 mt--100">
                        <div class="inner"><span class="sub-title tmp-scroll-trigger tmp-fade-in animation-order-1"><?= __('banner_greeting') ?></span>
                            <h1 class="title tmp-scroll-trigger tmp-fade-in animation-order-2"><?= __('banner_title_part1') ?><br>
                                <span class="header-caption">
                                    <span class="cd-headline clip is-full-width">
                                        <span class="cd-words-wrapper" style="width: 107.73px; overflow: hidden;">
                                            <b class="theme-gradient is-visible"><?= __('feature_1') ?></b>
                                            <b class="theme-gradient is-hidden"><?= __('feature_2') ?></b>
                                            <b class="theme-gradient is-hidden"><?= __('feature_3') ?></b>
                                            <b class="theme-gradient is-hidden"><?= __('feature_4') ?></b>
                                            <b class="theme-gradient is-hidden"><?= __('feature_5') ?></b>
                                            <b class="theme-gradient is-hidden"><?= __('feature_6') ?></b>
                                        </span>
                                    </span>
                                </span> <?= __('banner_title_part2') ?>
                            </h1>
                            <p class="disc tmp-scroll-trigger tmp-title-split tmp-fade-in animation-order-3">
                                <span><?= __('banner_desc_part1') ?></span>
                                <?= __('banner_desc_part2') ?>
                                <span><?= __('banner_desc_part3') ?></span>
                                <?= __('banner_desc_part4') ?>
                            </p>
                            <div class="button-area-banner-two tmp-scroll-trigger tmp-fade-in animation-order-4"><a
                                    class="tmp-btn hover-icon-reverse radius-round" href="login.php"> <span
                                        class="icon-reverse-wrapper"> <span class="btn-text"><?= __('start_now') ?></span> <span class="btn-icon"><i
                                                class="fa-sharp fa-regular fa-arrow-left"></i></span> <span
                                            class="btn-icon"><i class="fa-sharp fa-regular fa-arrow-left"></i></span>
                                    </span> </a></div>
                            <div class="find-me-on tmp-scroll-trigger tmp-fade-in animation-order-5">
                                <h2 class="find-me-on-title"><?= __('follow_us_title') ?></h2>
                                <div class="social-link banner"><a target="_blank" href="<?= INSTAGRAM_URL ?>"><i class="fa-brands fa-instagram"></i></a> <a target="_blank" href="<?= TELEGRAM_CHANNEL_URL ?>"><i class="fa-brands fa-telegram"></i></a></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="banner-shape-two"><img alt="" src="assets/images/banner/banner-shape-two.png" loading="lazy"></div>
    </div>

    <!-- App Preview Section Start -->
    <section class="app-preview-section tmp-section-gapTop" id="app-preview">
        <div class="container">
            <h2 class="app-preview-title tmp-scroll-trigger tmp-fade-in animation-order-1"><?= __('test_app_title', 'در اینجا میتونی قابلیت های farsifahr رو تست کنی') ?></h2>
            <div class="mobile-mockup tmp-scroll-trigger tmp-fade-in animation-order-2">
                <div class="mobile-notch"></div>
                <div class="mobile-content" style="padding: 0;">
                    <iframe src="app/preview.php" width="100%" height="100%" frameborder="0" style="border-radius: 30px; background-color: #fff;" loading="lazy"></iframe>
                </div>
            </div>
        </div>
    </section>
    <div class="about-content-area">
        <div class="container tmp-section-gap">
            <div class="text-para-doc-wrap">
                <h2 class="text-para-documents tmp-scroll-trigger tmp-fade-in tmp-title-split-2 animation-order-1">
                    <?= __('about_content_part1') ?>
                    <span>
                        <?= __('about_content_span1') ?>
                    </span>
                    <?= __('about_content_part2') ?>

                    <span>
                        <?= __('about_content_span2') ?>
                    </span>
                    <?= __('about_content_part3') ?>
                </h2>
                <div class="left-bg-text-para"><img alt="" src="assets/images/banner/right-bg-text-para-doc.png" loading="lazy"></div>
                <div class="right-bg-text-para"><img alt="" src="assets/images/banner/left-bg-text-para-doc.png" loading="lazy"></div>
            </div>
        </div>
    </div>
    <!-- PWA Install Section -->
    <section class="pwa-install-section">
        <div class="container">
            <div class="section-head text-center mb--50">
                <h2 class="title">نصب به صورت نرم‌افزار</h2>
                <p class="description">
                    شما می‌توانید به صورت نرم‌افزار از سایت استفاده کنید، در تمام دستگاه‌ها قابل نصب است.
                    همچنین بدون نصب هم می‌توانید با ثبت‌نام و ورود در سایت از امکانات آن استفاده کنید.
                </p>
            </div>
            <div class="pwa-install-wrapper">
                <!-- Android Item -->
                <div class="pwa-item android">
                    <div class="icon-box">
                        <i class="fa-brands fa-android"></i>
                    </div>
                    <div class="content-box">
                        <h3>نصب نسخه اندروید</h3>
                        <div class="actions">
                            <button class="btn-pwa btn-install" id="btn-pwa-android-hero">نصب مستقیم</button>
                            <button class="btn-pwa btn-tutorial" onclick="showTutorial('android')">آموزش نصب</button>
                        </div>
                    </div>
                </div>
                <!-- iOS Item -->
                <div class="pwa-item ios">
                    <div class="icon-box">
                        <i class="fa-brands fa-apple"></i>
                    </div>
                    <div class="content-box">
                        <h3>نصب نسخه آیفون (iOS)</h3>
                        <div class="actions">
                            <button class="btn-pwa btn-install" onclick="showTutorial('ios')">نصب اپلیکیشن</button>
                            <button class="btn-pwa btn-tutorial" onclick="showTutorial('ios')">آموزش نصب</button>
                        </div>
                    </div>
                </div>
                <!-- Windows Item -->
                <div class="pwa-item windows">
                    <div class="icon-box">
                        <i class="fa-brands fa-windows"></i>
                    </div>
                    <div class="content-box">
                        <h3>نصب نسخه ویندوز (Desktop)</h3>
                        <div class="actions">
                            <button class="btn-pwa btn-install" onclick="showTutorial('windows')">نصب اپلیکیشن</button>
                            <button class="btn-pwa btn-tutorial" onclick="showTutorial('windows')">آموزش نصب</button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Start Services Area -->
    <section class="services-area tmp-section-gapTop" id="services" style="padding-top: 80px; padding-bottom: 80px; background-color: #0b0f19;">
        <div class="container">
            <div class="row justify-content-center text-center mb-5">
                <div class="col-lg-8">
                    <span class="text-primary fw-semibold text-uppercase tracking-wider" style="color: #6366f1 !important; font-size: 0.9rem; letter-spacing: 2px;">خدمات اختصاصی ما</span>
                    <h2 class="fw-bold text-white mt-2" style="font-size: 2.2rem;">خدمات جانبی گواهینامه آلمانی</h2>
                    <p class="text-muted mt-3">تمام پیش‌نیازهای دریافت گواهینامه آلمانی را به صورت یکجا، با پشتیبانی فارسی و بالاترین سرعت انجام دهید.</p>
                </div>
            </div>
            
            <div class="row g-4">
                <!-- Translation Card -->
                <div class="col-lg-4 col-md-6">
                    <div class="card h-100 p-4" style="background: rgba(17, 24, 39, 0.6); backdrop-filter: blur(12px); border: 1px solid rgba(255, 255, 255, 0.08); border-radius: 20px; transition: all 0.3s; box-shadow: 0 10px 25px rgba(0,0,0,0.3);">
                        <div class="card-body d-flex flex-column text-center">
                            <div class="icon-box mb-4 mx-auto d-flex align-items-center justify-content-center" style="width: 70px; height: 70px; border-radius: 15px; background: rgba(99, 102, 241, 0.15); color: #818cf8;">
                                <i class="fa-solid fa-file-signature" style="font-size: 30px;"></i>
                            </div>
                            <h4 class="fw-bold text-white mb-3"><?= htmlspecialchars($translation_title) ?></h4>
                            <p class="text-muted mb-4 small flex-grow-1" style="line-height: 1.6;"><?= htmlspecialchars($translation_desc) ?></p>
                            <div class="mb-4">
                                <span class="fs-4 fw-bold text-white"><?= htmlspecialchars($translation_price) ?> یورو</span>
                            </div>
                            <a href="service-translation.php" class="btn btn-primary w-100 py-3 fw-semibold" style="border-radius: 12px; background: linear-gradient(135deg, #6366f1, #4f46e5); border: none;">ثبت درخواست ترجمه</a>
                        </div>
                    </div>
                </div>

                <!-- Eye Test Card -->
                <div class="col-lg-4 col-md-6">
                    <div class="card h-100 p-4" style="background: rgba(17, 24, 39, 0.6); backdrop-filter: blur(12px); border: 1px solid rgba(255, 255, 255, 0.08); border-radius: 20px; transition: all 0.3s; box-shadow: 0 10px 25px rgba(0,0,0,0.3);">
                        <div class="card-body d-flex flex-column text-center">
                            <div class="icon-box mb-4 mx-auto d-flex align-items-center justify-content-center" style="width: 70px; height: 70px; border-radius: 15px; background: rgba(3, 195, 236, 0.15); color: #03c3ec;">
                                <i class="fa-solid fa-eye" style="font-size: 30px;"></i>
                            </div>
                            <h4 class="fw-bold text-white mb-3"><?= htmlspecialchars($eyetest_title) ?></h4>
                            <p class="text-muted mb-4 small flex-grow-1" style="line-height: 1.6;"><?= htmlspecialchars($eyetest_desc) ?></p>
                            <div class="mb-4">
                                <span class="badge bg-success p-2 fs-6" style="background-color: rgba(16, 185, 129, 0.2) !important; color: #34d399 !important;">رایگان (ویژه اعضا)</span>
                            </div>
                            <a href="service-eyetest.php" class="btn btn-info w-100 py-3 fw-semibold text-white" style="border-radius: 12px; background: linear-gradient(135deg, #03c3ec, #0284c7); border: none;">رزرو نوبت تست چشم</a>
                        </div>
                    </div>
                </div>

                <!-- First Aid Card -->
                <div class="col-lg-4 col-md-6">
                    <div class="card h-100 p-4" style="background: rgba(17, 24, 39, 0.6); backdrop-filter: blur(12px); border: 1px solid rgba(255, 255, 255, 0.08); border-radius: 20px; transition: all 0.3s; box-shadow: 0 10px 25px rgba(0,0,0,0.3);">
                        <div class="card-body d-flex flex-column text-center">
                            <div class="icon-box mb-4 mx-auto d-flex align-items-center justify-content-center" style="width: 70px; height: 70px; border-radius: 15px; background: rgba(16, 185, 129, 0.15); color: #34d399;">
                                <i class="fa-solid fa-kit-medical" style="font-size: 30px;"></i>
                            </div>
                            <h4 class="fw-bold text-white mb-3"><?= htmlspecialchars($firstaid_title) ?></h4>
                            <p class="text-muted mb-4 small flex-grow-1" style="line-height: 1.6;"><?= htmlspecialchars($firstaid_desc) ?></p>
                            <div class="mb-4">
                                <span class="badge bg-success p-2 fs-6" style="background-color: rgba(16, 185, 129, 0.2) !important; color: #34d399 !important;">رایگان (ویژه اعضا)</span>
                            </div>
                            <a href="service-firstaid.php" class="btn btn-success w-100 py-3 fw-semibold text-white" style="border-radius: 12px; background: linear-gradient(135deg, #10b981, #059669); border: none;">رزرو کورس Erste Hilfe</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
    <!-- End Services Area -->

    <section class="about-us-area">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-lg-6">
                    <div class="about-us-left-content-wrap bg-vactor-one">
                        <div class="years-of-experience-card tmp-scroll-trigger tmp-fade-in animation-order-1">
                            <h2 class="counter card-title ">
                                <span class="odometer ltr" data-count="4500">4500</span>+
                            </h2>
                            <p class="card-para"><?= __('correct_wrong_explanation') ?></p>
                        </div>
                        <div class="design-card tmp-scroll-trigger tmp-fade-in animation-order-2">
                            <div class="design-card-img">
                                <div class="icon"><i class="fa-sharp fa-thin fa-lock"></i></div>
                            </div>
                            <div class="card-info">
                                <h3 class="card-title"><?= __('basic_training_title') ?></h3>
                                <p class="card-para"><?= __('training_count') ?></p>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-lg-6">
                    <div class="about-us-right-content-wrap">
                        <div class="section-head text-align-left mb--50">
                            <div class="section-sub-title tmp-scroll-trigger tmp-fade-in animation-order-1"><span
                                    class="subtitle"><?= __('strategy_subtitle') ?></span></div>
                            <h2 class="title split-collab tmp-scroll-trigger tmp-fade-in animation-order-2"><?= __('strategy_title') ?></h2>
                            <p class="description tmp-scroll-trigger tmp-fade-in animation-order-3"></p>
                        </div>
                        <div class="about-us-section-card row g-5">
                            <div class="col-lg-6 col-md-6 col-sm-6 col-12">
                                <div class="about-us-card tmponhover tmp-scroll-trigger tmp-fade-in animation-order-4"
                                    style="--x: 146px; --y: 20px;">
                                    <div class="card-head">
                                        <div class="logo-img"><img alt="logo" src="assets/images/about/logo-1.svg" loading="lazy">
                                        </div>
                                        <h3 class="card-title"><?= __('fastest_time_title') ?></h3>
                                    </div>
                                    <p class="card-para"><?= __('fastest_time_desc') ?></p>
                                </div>
                            </div>
                            <div class="col-lg-6 col-md-6 col-sm-6 col-12">
                                <div class="about-us-card tmponhover tmp-scroll-trigger tmp-fade-in animation-order-5">
                                    <div class="card-head">
                                        <div class="logo-img"><img alt="logo" src="assets/images/about/logo-2.svg" loading="lazy">
                                        </div>
                                        <h3 class="card-title"><?= __('language_problem_title') ?></h3>
                                    </div>
                                    <p class="card-para"><?= __('language_problem_desc') ?></p>
                                </div>
                            </div>
                        </div>
                        <div
                            class="about-btn mt--40 mb--80 tmp-scroll-trigger tmp-fade-in animation-order-6 tmp-scroll-trigger--offscreen">
                            <a class="tmp-btn hover-icon-reverse radius-round" href="login.php"> <span
                                    class="icon-reverse-wrapper"> <span class="btn-text"><?= __('start_now') ?></span> <span class="btn-icon"><i
                                            class="fa-sharp fa-regular fa-arrow-left"></i></span> <span
                                        class="btn-icon"><i class="fa-sharp fa-regular fa-arrow-left"></i></span>
                                </span> </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <section class="blog-and-news-are tmp-section-gap">
        <div class="container">
            <div class="section-head mb--50">
                <div
                    class="section-sub-title center-title tmp-scroll-trigger tmp-fade-in animation-order-1">
                    <span class="subtitle"><?= __('last_blog', 'آخرین وبلاگ') ?></span>
                </div>
                <h2
                    class="title split-collab tmp-scroll-trigger tmp-fade-in animation-order-2">
                    <?= __('free_training_info', 'اطلاعات اولیه و آموزش های رایگان') ?>
                </h2>
            </div>
            <div class="row" id="blog-posts-container">
                <!-- Blog posts loaded from DB -->
                <?php
                $stmt = $pdo->query("SELECT * FROM posts WHERE published_at <= NOW() OR published_at IS NULL ORDER BY created_at DESC LIMIT 12");
                $db_posts = $stmt->fetchAll();

                if (empty($db_posts)) {
                    // Show a message if no posts exist
                    echo '<div class="col-12 text-center text-muted">'.__('no_posts_yet', 'هنوز مطلبی منتشر نشده است.').'</div>';
                }

                foreach($db_posts as $post):
                    $post_img = $post['image'] ? rtrim(SITE_URL, '/') . '/panel/storage/' . $post['image'] : 'assets/images/blog/blog-img-1.jpg';
                    
                    // Improved robust date formatting
                    $timestamp = strtotime($post['published_at'] ?: $post['created_at']);
                    $day = date('d', $timestamp);
                    $month_num = date('n', $timestamp);
                    $months = [
                        1 => 'Jan', 2 => 'Feb', 3 => 'Mar', 4 => 'Apr', 5 => 'May', 6 => 'Jun',
                        7 => 'Jul', 8 => 'Aug', 9 => 'Sep', 10 => 'Oct', 11 => 'Nov', 12 => 'Dec'
                    ];
                    $post_date = $day . ' ' . $months[$month_num];
                    
                    // Fetch real approved comment count
                    $stmtCount = $pdo->prepare("SELECT COUNT(*) FROM comments WHERE post_id = ? AND status = 'approved'");
                    $stmtCount->execute([$post['id']]);
                    $comment_count = $stmtCount->fetchColumn();
                ?>
                <div class="col-lg-4 col-md-6 col-6">
                    <div
                        class="blog-card-style-two tmponhover image-box-hover tmp-scroll-trigger tmp-fade-in animation-order-3">
                        <div class="blog-card-img">
                            <div class="img-box"><a href="blog-details.php?id=<?= $post['id'] ?>"> <img alt="<?= htmlspecialchars($post['title']) ?>" class="w-100"
                                        src="<?= $post_img ?>" style="height: 250px; object-fit: cover;">
                                </a></div>
                            <span><?= $post_date ?></span>
                        </div>
                        <div class="blog-content-wrap">
                            <div class="blog-tags">
                                <ul>
                                    <li><a href="#"><i class="fa-regular fa-user"></i><?= htmlspecialchars($post['author_name']) ?></a></li>
                                    <li><a href="#"><i class="fa-regular fa-comments"></i> <?= __('comments_label', 'نظرات') ?> (<?= $comment_count ?>)</a></li>
                                </ul>
                            </div>
                            <h3 class="blog-title"><a href="blog-details.php?id=<?= $post['id'] ?>"><?= htmlspecialchars($post['title']) ?></a>
                            </h3>
                            <div class="read-more-btn"><a
                                    class="tmp-btn hover-icon-reverse radius-round btn-border btn-md"
                                    href="blog-details.php?id=<?= $post['id'] ?>"> <span class="icon-reverse-wrapper"> <span
                                            class="btn-text"><?= __('read_more', 'بیشتر بخوانید') ?></span> <span class="btn-icon"><i
                                                class="fa-sharp fa-regular fa-arrow-left"></i></span> <span
                                            class="btn-icon"><i class="fa-sharp fa-regular fa-arrow-left"></i></span>
                                    </span> </a>
                            </div>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </section>
    <!-- SEO Content Section -->
    <section class="seo-content-section tmp-section-gapBottom">
        <div class="container">
            <div class="row">
                <div class="col-lg-12">
                    <div class="section-head text-center mb--50">
                        <h2 class="title"><?= __('seo_section_title', 'مرجع تخصصی گواهینامه آلمانی به فارسی') ?></h2>
                    </div>
                    <div class="seo-text-content text-center" style="max-width: 800px; margin: 0 auto; line-height: 1.8;">
                        <p>
                            <?= __('seo_description_text', 'اگر به دنبال <strong>آموزش گواهینامه آلمانی</strong> هستید، سایت farsifahr (farsifahr) جامع‌ترین ابزارها را برای شما فراهم کرده است. ما با ارائه <strong>ترجمه و آموزش سوالات گواهینامه آلمانی</strong> به صورت دقیق و روان، مسیر موفقیت در <strong>آزمون تئوری گواهینامه آلمانی</strong> را برای فارسی‌زبانان هموار کرده‌ایم. در این سامانه می‌توانید به <strong>گواهینامه آلمانی به فارسی</strong> دسترسی داشته باشید و از امکانات <strong>رایگان</strong> ما برای تست و تمرین استفاده کنید.') ?>
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <footer id="contact" class="footer-area footer-style-two-wrapper bg-color-footer bg_images tmp-section-gap">
        <div class="container">
            <div class="footer-main footer-style-two">
                <div class="row g-5">
                    <div class="col-lg-4 col-md-4 col-sm-6">
                        <div class="single-footer-wrapper border-right mr--20">
                            <div class="logo"><a href="<?= SITE_URL ?>"> <img alt="Farsi - Fahr"
                                        src="assets/images/logo/logoAsset%201.svg" loading="lazy"> </a></div>
                            <p class="description"><?= __('footer_description', FOOTER_DESCRIPTION) ?></p>
                            <div class="social-link footer"><a target="_blank" href="<?= INSTAGRAM_URL ?>"><i class="fa-brands fa-instagram"></i></a> <a target="_blank" href="<?= TELEGRAM_CHANNEL_URL ?>"><i class="fa-brands fa-telegram"></i></a></div>
                        </div>
                    </div>
                    <div class="col-lg-4 col-md-4 col-sm-6">
                        <div class="single-footer-wrapper contact-wrap">
                            <h5 class="ft-title"><?= __('contact', 'تماس') ?></h5>
                            <ul
                                class="ft-link tmp-scroll-trigger animation-order-1 tmp-link-animation tmp-scroll-trigger--offscreen">
                                <li><span class="ft-icon"><i class="fa-brands fa-whatsapp"></i></span><a
                                        href="<?= WHATSAPP_URL ?>" target="_blank"><?= CONTACT_PHONE ?> (<?= __('whatsapp', 'واتس‌اپ') ?>)</a>
                                </li>
                                <li><span class="ft-icon"><i class="fa-brands fa-telegram"></i></span><a
                                        href="<?= TELEGRAM_SUPPORT_URL ?>" target="_blank"><?= __('telegram_support', 'پشتیبانی تلگرام') ?></a>
                                </li>
                                <li><span class="ft-icon"><i class="fa-solid fa-envelope"></i></span><a
                                        href="mailto:<?= CONTACT_EMAIL ?>"><?= CONTACT_EMAIL ?></a>
                                </li>
                            </ul>
                        </div>
                    </div>
                    <div class="col-lg-4 col-md-6 col-sm-6">
                        <div class="newslatter tmp-scroll-trigger animation-order-1 tmp-scroll-trigger--offscreen">
                            <h3 class="title"><?= __('newsletter', 'خبرنامه') ?></h3>
                            <p class="para"><?= __('newsletter_desc', 'از آخرین تغییرات ما در لحظه با خبر باشید') ?></p>
                            <form action="#" class="newsletter-form-1"><input placeholder="<?= __('your_email', 'ایمیل شما') ?>" type="email">
                                <span> <a class="form-icon" href="#"><i class="fa-solid fa-arrow-left"></i></a> </span>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </footer>
    <div class="copyright-area-one">
        <div class="container">
            <div class="row">
                <div class="col-lg-12">
                    <div class="main-wrapper tmp-scroll-trigger animation-order-1 tmp-scroll-trigger--offscreen">
                        <p class="copy-right-para"><?= __('copyright_text', COPYRIGHT_TEXT) ?>
                        </p>
                    </div>
                    <!-- Site Designer Credit -->
                    <div class="site-designer-credit mt-2 text-center" style="font-size: 10px; opacity: 0.6; direction: ltr;">
                        <?php
                        $designer_label = [
                            'fa' => 'طراح سایت: miad',
                            'en' => 'Site Designer: miad',
                            'de' => 'Webdesigner: miad'
                        ][get_current_lang()];
                        $email_label = [
                            'fa' => 'ارسال ایمیل',
                            'en' => 'Send Email',
                            'de' => 'E-Mail senden'
                        ][get_current_lang()];
                        ?>
                        <span><?= $designer_label ?></span>
                        <span class="mx-1">|</span>
                        <a href="mailto:miadaleali@gmail.com" style="color: inherit; text-decoration: none;">
                            <i class="fa-solid fa-envelope me-1"></i><?= $email_label ?>
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <!-- <div class="ready-chatting-option tmp-ready-chat chat-visible"><input id="click" type="checkbox"> <label
            for="click"> <i class="fab fa-facebook-messenger"></i> <i class="fas fa-times"></i> </label>
        <div class="wrapper">
            <div class="head-text">بیایید با من گپ بزنیم؟- آنلاین</div>
            <div class="chat-box">
                <div class="desc-text">لطفاً فرم زیر را پر کنید تا مستقیماً با من گپ بزنید.</div>
                <form action="#" class="tmp-dynamic-form">
                    <div class="field"><input class="input-field" name="name" placeholder="نام شما" required=""
                            type="text">
                    </div>
                    <div class="field"><input class="input-field" name="email" placeholder="ایمیل شما" required=""
                            type="email"></div>
                    <div class="field textarea"><textarea class="input-field" name="message" placeholder="پیام شما"
                            required=""></textarea></div>
                    <div class="field">
                        <button name="submit" type="submit">ارسال پیام</button>
                    </div>
                </form>
            </div>
        </div>
    </div> -->
    <div class="scrollToTop active-progress" style="display: block;">
        <div class="arrowUp"><i class="fa-light fa-arrow-up"></i></div>
        <div class="water" style="transform: translate(0px, 91%);">
            <svg class="water_wave water_wave_back" viewBox="0 0 560 20">
                <use xlink:href="#wave"></use>
            </svg>
            <svg class="water_wave water_wave_front" viewBox="0 0 560 20">
                <use xlink:href="#wave"></use>
            </svg>
            <svg style="display: none;" version="1.1" viewBox="0 0 560 20" xmlns="http://www.w3.org/2000/svg"
                xmlns:xlink="http://www.w3.org/1999/xlink">
                <symbol id="wave">
                    <path
                        d="M420,20c21.5-0.4,38.8-2.5,51.1-4.5c13.4-2.2,26.5-5.2,27.3-5.4C514,6.5,518,4.7,528.5,2.7c7.1-1.3,17.9-2.8,31.5-2.7c0,0,0,0,0,0v20H420z"
                        fill="#FF014F"
                        style="transition: stroke-dashoffset 10ms linear; stroke-dasharray: 301.839, 301.839; stroke-dashoffset: 301.839px;">
                    </path>
                    <path
                        d="M420,20c-21.5-0.4-38.8-2.5-51.1-4.5c-13.4-2.2-26.5-5.2-27.3-5.4C326,6.5,322,4.7,311.5,2.7C304.3,1.4,293.6-0.1,280,0c0,0,0,0,0,0v20H420z"
                        fill="#FF014F"></path>
                    <path
                        d="M140,20c21.5-0.4,38.8-2.5,51.1-4.5c13.4-2.2,26.5-5.2,27.3-5.4C234,6.5,238,4.7,248.5,2.7c7.1-1.3,17.9-2.8,31.5-2.7c0,0,0,0,0,0v20H140z"
                        fill="#FF014F"></path>
                    <path
                        d="M140,20c-21.5-0.4-38.8-2.5-51.1-4.5c-13.4-2.2-26.5-5.2-27.3-5.4C46,6.5,42,4.7,31.5,2.7C24.3,1.4,13.6-0.1,0,0c0,0,0,0,0,0l0,20H140z"
                        fill="#FF014F"></path>
                </symbol>
            </svg>
        </div>
    </div>

    <script defer src="assets/js/vendor/jquery.js"></script>
    <script defer src="assets/js/vendor/jquery-ui.min.js"></script>
    <script defer src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <script defer src="assets/js/vendor/waypoints.min.js"></script>
    <script defer src="assets/js/plugins/odometer.js"></script>
    <script defer src="assets/js/vendor/appear.js"></script>
    <script defer src="assets/js/vendor/jquery-one-page-nav.js"></script>
    <script defer src="assets/js/plugins/swiper.js"></script>
    <script defer src="assets/js/plugins/gsap.js"></script>
    <script defer src="assets/js/plugins/splittext.js"></script>
    <script defer src="assets/js/plugins/scrolltigger.js"></script>
    <script defer src="assets/js/plugins/scrolltoplugins.js"></script>
    <script defer src="assets/js/plugins/smoothscroll.js"></script>
    <script defer src="assets/js/vendor/bootstrap.min.js"></script>
    <script defer src="assets/js/vendor/waw.js"></script>
    <script defer src="assets/js/plugins/isotop.js"></script>
    <script defer src="assets/js/plugins/animation.js"></script>
    <script defer src="assets/js/plugins/contact.form.js"></script>
    <script defer src="assets/js/vendor/backtop.js"></script>
    <script defer src="assets/js/plugins/text-type.js"></script>
    <script defer src="assets/js/main.js"></script>
    <script>
        function togglePasswordVisibility(inputId, button) {
            const input = document.getElementById(inputId);
            const icon = button.querySelector('i');
            if (input.type === 'password') {
                input.type = 'text';
                icon.classList.remove('fa-eye');
                icon.classList.add('fa-eye-slash');
            } else {
                input.type = 'password';
                icon.classList.remove('fa-eye-slash');
                icon.classList.add('fa-eye');
            }
        }
        
        const authTranslations = {
            logging_in: '<?= __("logging_in", "در حال ورود...") ?>',
            success: '<?= __("success", "موفقیت‌آمیز") ?>',
            email_verification_required: '<?= __("email_verification_required", "تایید ایمیل الزامی است") ?>',
            resend_verification_email: '<?= __("resend_verification_email", "ارسال مجدد ایمیل تایید") ?>',
            can_resend_in: '<?= __("can_resend_in", "امکان ارسال مجدد تا") ?>',
            seconds_later: '<?= __("seconds_later", "ثانیه دیگر") ?>',
            got_it: '<?= __("got_it", "متوجه شدم") ?>',
            sending: '<?= __("sending", "در حال ارسال...") ?>',
            sent: '<?= __("sent", "ارسال شد") ?>',
            connection_error: '<?= __("connection_error", "خطا در برقراری ارتباط") ?>',
            error_msg_title: '<?= __("error_msg_title", "خطا") ?>',
            login_button: '<?= __("login_button", "ورود") ?>',
            password_mismatch: '<?= __("password_mismatch", "رمز عبور و تکرار آن مطابقت ندارند") ?>',
            registering: '<?= __("registering", "در حال ثبت نام...") ?>',
            register_title: '<?= __("register_title", "ثبت نام") ?>',
            send_reset_link: '<?= __("send_reset_link", "ارسال لینک بازیابی") ?>',
            weak_password: '<?= __("weak_password", "رمز عبور ضعیف") ?>',
            medium_password: '<?= __("medium_password", "رمز عبور متوسط") ?>',
            strong_password: '<?= __("strong_password", "رمز عبور قوی") ?>',
            ok: '<?= __("ok", "باشه") ?>'
        };
    </script>
    <script defer src="assets/js/script.js"></script>

    <!-- Billing Toggle Script -->
    <script>
        // تابع برای تغییر نمایش قیمت‌ها بین ماهانه و سالانه
        document.getElementById('billingToggle').addEventListener('change', function () {
            const monthlyPrices = document.querySelectorAll('.monthly-price');
            const yearlyPrices = document.querySelectorAll('.yearly-price');
            const perMonthTexts = document.querySelectorAll('.per-month');

            if (this.checked) {
                monthlyPrices.forEach(el => el.classList.add('d-none'));
                yearlyPrices.forEach(el => el.classList.remove('d-none'));
                perMonthTexts.forEach(el => el.textContent = '<?= __("yearly_discounted", "سالانه (با تخفیف)") ?>');
            } else {
                monthlyPrices.forEach(el => el.classList.remove('d-none'));
                yearlyPrices.forEach(el => el.classList.add('d-none'));
                perMonthTexts.forEach(el => el.textContent = '<?= __("monthly", "ماهانه") ?>');
            }
        });
</script>
<script src="/chat/widget.js?v=2.6" async></script>

    <?php if (isset($_COOKIE['concurrent_login']) && $_COOKIE['concurrent_login'] === '1'): ?>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            Swal.fire({
                icon: 'warning',
                title: '<?= __("logout_alert_title", "خروج از حساب") ?>',
                text: '<?= __("logout_alert_desc", "شما در دستگاه یا مرورگر دیگری وارد حساب خود شدید. بنابراین از این نشست خارج شدید.") ?>',
                confirmButtonText: '<?= __("got_it", "متوجه شدم") ?>'
            });
            document.cookie = 'concurrent_login=; expires=Thu, 01 Jan 1970 00:00:00 UTC; path=/;';
        });
    </script>
    <?php endif; ?>
    <script>
        // Service Worker Registration
        if ('serviceWorker' in navigator) {
            window.addEventListener('load', () => {
                navigator.serviceWorker.register('/sw.js?v=5')
                    .then(reg => console.log('Service Worker registered'))
                    .catch(err => console.log('Service Worker registration failed: ', err));
            });
        }

        // PWA Install Prompt Logic
        let deferredPrompt;
        const isIOS = /iPad|iPhone|iPod/.test(navigator.userAgent) && !window.MSStream || (navigator.platform === 'MacIntel' && navigator.maxTouchPoints > 1);
        const isStandalone = window.matchMedia('(display-mode: standalone)').matches || window.navigator.standalone;

        function showIOSInstructions() {
            if (typeof Swal !== 'undefined') {
                Swal.fire({
                    title: '<?= __('install_on_ios', 'نصب در آیفون') ?>',
                    html: `
                        <div class="text-<?= get_lang_dir() === 'rtl' ? 'end' : 'start' ?>" style="direction: <?= get_lang_dir() ?>;">
                            <p><?= __('ios_install_instruction', 'برای نصب اپلیکیشن در آیفون، مراحل زیر را دنبال کنید:') ?></p>
                            <ol class="ps-3" style="margin-bottom: 0;">
                                <li class="mb-2"><?= __('ios_step_1', 'در نوار پایین مرورگر دکمه <b>Share</b> <i class="fas fa-share-square"></i> را بزنید.') ?></li>
                                <li class="mb-2"><?= __('ios_step_2', 'در منوی باز شده، گزینه <b>Add to Home Screen</b> را انتخاب کنید.') ?></li>
                                <li><?= __('ios_step_3', 'در بالا سمت راست، دکمه <b>Add</b> را بزنید.') ?></li>
                            </ol>
                        </div>
                    `,
                    icon: 'info',
                    confirmButtonText: '<?= __('got_it', 'متوجه شدم') ?>',
                    customClass: {
                        confirmButton: 'btn btn-primary'
                    },
                    buttonsStyling: false
                });
            } else {
                alert('<?= __('ios_install_instruction', 'برای نصب در آیفون: دکمه Share را زده و Add to Home Screen را انتخاب کنید.') ?>');
            }
        }

        function showTutorial(platform) {
            let title, desc;
            
            if (platform === 'android') {
                title = 'آموزش نصب در اندروید';
                desc = 'برای نصب در اندروید، روی دکمه "نصب مستقیم" کلیک کنید یا از منوی مرورگر گزینه Install را انتخاب کنید.';
            } else if (platform === 'ios') {
                title = 'آموزش نصب در آیفون';
                desc = 'برای نصب در آیفون، در مرورگر Safari دکمه Share را زده و گزینه Add to Home Screen را انتخاب کنید.';
            } else {
                title = 'آموزش نصب در دسکتاپ';
                desc = 'برای نصب در ویندوز یا مک، در نوار آدرس مرورگر (Chrome یا Edge) روی آیکون نصب <i class="fa-regular fa-display-arrow-down"></i> کلیک کنید.';
            }
            
            Swal.fire({
                title: title,
                html: `
                    <div class="text-center">
                        <p class="mb-0">${desc}</p>
                    </div>
                `,
                confirmButtonText: 'متوجه شدم',
                customClass: {
                    confirmButton: 'btn btn-primary'
                }
            });
        }

        window.addEventListener('beforeinstallprompt', (e) => {
            e.preventDefault();
            deferredPrompt = e;
        });

        document.addEventListener('DOMContentLoaded', function() {
            // Hero Android Install Button
            const heroAndroidBtn = document.getElementById('btn-pwa-android-hero');
            if (heroAndroidBtn) {
                heroAndroidBtn.addEventListener('click', function() {
                    if (deferredPrompt) {
                        deferredPrompt.prompt();
                        deferredPrompt.userChoice.then((choiceResult) => {
                            deferredPrompt = null;
                        });
                    } else {
                        showTutorial('android');
                    }
                });
            }
            // Show manual install in menu if not installed
            if (!isStandalone) {
                const menuInstallLi = document.getElementById('menu-install-li');
                if (menuInstallLi) menuInstallLi.style.display = 'block';
            }
            
            // Add listener to manual install button in menu
            const manualInstallBtn = document.getElementById('btn-manual-install');
            if (manualInstallBtn) {
                const handleInstall = (e) => {
                    e.preventDefault();
                    if (isIOS) {
                        showIOSInstructions();
                    } else if (deferredPrompt) {
                        deferredPrompt.prompt();
                    } else {
                        if (typeof Swal !== 'undefined') {
                            Swal.fire({
                                title: '<?= __('install', 'نصب') ?>',
                                text: '<?= __('pwa_manual_install_info', 'برای نصب اپلیکیشن، از منوی مرورگر خود گزینه Install یا Add to Home Screen را انتخاب کنید.') ?>',
                                icon: 'info'
                            });
                        } else {
                            alert('<?= __('pwa_manual_install_info', 'برای نصب اپلیکیشن، از منوی مرورگر خود گزینه Install یا Add to Home Screen را انتخاب کنید.') ?>');
                        }
                    }
                };
                manualInstallBtn.addEventListener('click', handleInstall);
                manualInstallBtn.addEventListener('touchstart', handleInstall, {passive: false});
            }
        });

        function showInstallPrompt(platform) {
            // Function no longer needed for automatic prompt
        }
    </script>
<script>
    $(document).ready(function() {
        // Odometer initialization - optimized for performance
        let odometerTriggered = false;
        function triggerOdometer() {
            if (odometerTriggered) return;
            
            $('.odometer').each(function() {
                const element = this;
                const count = $(element).attr('data-count');
                const rect = element.getBoundingClientRect();
                const isVisible = rect.top < window.innerHeight && rect.bottom >= 0;

                if (isVisible && !$(element).hasClass('odometer-triggered')) {
                    $(element).html(count);
                    $(element).addClass('odometer-triggered');
                }
            });
        }

        // Use IntersectionObserver if available for better performance
        if ('IntersectionObserver' in window) {
            const odoObserver = new IntersectionObserver((entries) => {
                entries.forEach(entry => {
                    if (entry.isIntersecting) {
                        const el = $(entry.target);
                        if (!el.hasClass('odometer-triggered')) {
                            el.html(el.attr('data-count'));
                            el.addClass('odometer-triggered');
                        }
                    }
                });
            }, { threshold: 0.1 });
            $('.odometer').each(function() { odoObserver.observe(this); });
        } else {
            triggerOdometer();
            $(window).on('scroll', triggerOdometer);
        }
    });

    // جلوگیری از مشکل کش شدن مرورگر هنگام استفاده از دکمه Back
    window.addEventListener('pageshow', function(event) {
        if (event.persisted) {
            window.location.reload();
        }
    });

    // تبدیل تمام اعداد فارسی و عربی به لاتین برای زبان‌های غیرفارسی
    <?php if (get_lang_dir() === 'ltr'): ?>
    (function() {
        function toLatin(str) {
            if (!str || typeof str !== 'string') return str;
            return str.replace(/[۰-۹٠-٩]/g, function(d) {
                const charCode = d.charCodeAt(0);
                if (charCode >= 0x06F0 && charCode <= 0x06F9) return charCode - 0x06F0; // Persian
                if (charCode >= 0x0660 && charCode <= 0x0669) return charCode - 0x0660; // Arabic
                return d;
            });
        }
        
        function convertDigits(node) {
            if (node.nodeType === 3) { // Text node
                const original = node.nodeValue;
                const converted = toLatin(original);
                if (original !== converted) node.nodeValue = converted;
            } else if (node.nodeType === 1 && node.nodeName !== 'SCRIPT' && node.nodeName !== 'STYLE') {
                for (let i = 0; i < node.childNodes.length; i++) {
                    convertDigits(node.childNodes[i]);
                }
                if (node.placeholder) node.placeholder = toLatin(node.placeholder);
            }
        }

        // Run conversion with a small delay and only once if possible
        const runConversion = () => {
            requestAnimationFrame(() => convertDigits(document.body));
        };
        
        if (document.readyState === 'complete') {
            runConversion();
        } else {
            window.addEventListener('load', runConversion);
        }
        
        // Use a more conservative observer for dynamic content
        let timeout;
        const observer = new MutationObserver((mutations) => {
            if (timeout) clearTimeout(timeout);
            timeout = setTimeout(() => {
                mutations.forEach((mutation) => {
                    if (mutation.type === 'childList') {
                        mutation.addedNodes.forEach((node) => convertDigits(node));
                    }
                });
            }, 250);
        });
        
        observer.observe(document.body, {
            childList: true,
            subtree: true
        });
    })();
    <?php endif; ?>
</script>
    <?php if (is_super_admin()): ?>
    <!-- Admin Desktop Toggle -->
    <button class="admin-desktop-toggle is-admin" onclick="toggleDesktopMode()" title="<?= __("toggle_desktop_title", "تغییر به حالت دسکتاپ/موبایل") ?>">
        <i class="fas fa-desktop"></i>
    </button>
    <script>
        function toggleDesktopMode() {
            const isDesktop = localStorage.getItem('adminDesktopMode') === 'true';
            localStorage.setItem('adminDesktopMode', isDesktop ? 'false' : 'true');
            window.location.reload();
        }

        (function() {
            const isDesktop = localStorage.getItem('adminDesktopMode') === 'true';
            const viewport = document.querySelector('meta[name="viewport"]');
            const icon = document.querySelector('.admin-desktop-toggle i');
            
            if (isDesktop) {
                document.body.classList.add('admin-desktop-active');
                if (viewport) {
                    viewport.setAttribute('content', 'width=1200, initial-scale=0.3, maximum-scale=5.0, user-scalable=yes');
                }
                if (icon) icon.className = 'fas fa-mobile-alt';
            } else {
                document.body.classList.remove('admin-desktop-active');
                if (viewport) {
                    viewport.setAttribute('content', 'width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no');
                }
                if (icon) icon.className = 'fas fa-desktop';
            }
        })();
    </script>
    <?php endif; ?>
</body>

</html>
