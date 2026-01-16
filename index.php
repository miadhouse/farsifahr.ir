<?php
/**
 * PHP 8.4 Pure Functional Script
 */

// Include configuration
require_once __DIR__ . '/incloud/functions.php';
require_once __DIR__ . '/incloud/subscription-functions.php';

if (is_logged_in()) {
    // header("Location: dashboard.php");
    // exit();
}

?>
<html lang="fa">

<head>
    <meta charset="utf-8">
    <meta content="IE=edge" http-equiv="X-UA-Compatible">
    <meta content="width=device-width, initial-scale=1.0" name="viewport">
    <meta content="Farsi Fahr | آموزش و آمادگی آزمون تئوری گواهینامه آلمانی" name="description">
    <link href="assets/images/favicon.svg" rel="shortcut icon" type="image/x-icon">
    <title>Farsi Fahr | آموزش و آمادگی آزمون تئوری گواهینامه آلمانی</title>
    <link href="assets/css/vendor/fontawesome.css" rel="stylesheet">
    <link href="assets/css/plugins/swiper.rtl.css" rel="stylesheet">
    <link href="assets/css/plugins/odometer.rtl.css" rel="stylesheet">
    <link href="assets/css/vendor/animate.min.css" rel="stylesheet">
    <link href="assets/css/vendor/bootstrap.min.rtl.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">

    <link href="assets/css/style.rtl.css" rel="stylesheet">

</head>
<style>
    .modal-content {
        background-color: #2f506b00 !important;
        border-radius: 1rem !important;
        padding: 2rem !important;
    }

    .text-bronze {
        color: rgba(186, 107, 34, 1) !important;
    }

    .text-silver {
        color: rgb(184, 184, 184) !important;
    }

    .text-gold {
        color: rgb(255, 191, 0) !important;
    }


    /* انیمیشن slidedown برای modal */
    .modal.slidedown .modal-dialog {
        transform: translateY(-20vh);
        opacity: 0;
        transition: all 0.4s cubic-bezier(0.25, 0.46, 0.45, 0.94);
    }

    .modal.slidedown.show .modal-dialog {
        transform: translateY(0);
        opacity: 1;
    }

    /* تم تاریک برای modal */
    .modal.slidedown .modal-content {
        /* background: linear-gradient(135deg, #1a1a2e 0%, #16213e 50%, #0f3460 100%); */
        border: 1px solid #2d3748;
        border-radius: 20px;
        box-shadow: 0 20px 60px rgba(0, 0, 0, 0.5);
        color: #e2e8f0;
        backdrop-filter: blur(10px);
    }

    /* هدر modal */
    .modal.slidedown .modal-header {
        background: linear-gradient(135deg, #4868f8ff 0%, #2a77a8ff 100%);
        border-bottom: 1px solid #4a5568;
        border-radius: 20px 20px 0 0;
        padding: 20px 25px;
    }

    .modal.slidedown .modal-title {
        color: #ffffff;
        font-weight: 600;
        font-size: 1.3rem;
    }

    .modal.slidedown .btn-close {
        background: transparent url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 16 16' fill='%23ffffff'%3e%3cpath d='m.235.678.326-.32a.5.5 0 0 1 .707 0L8 6.193l6.732-5.835a.5.5 0 0 1 .707 0l.326.32a.5.5 0 0 1 0 .707L9.032 8l6.733 6.615a.5.5 0 0 1 0 .707l-.326.32a.5.5 0 0 1-.707 0L8 9.807l-6.732 5.835a.5.5 0 0 1-.707 0l-.326-.32a.5.5 0 0 1 0-.707L6.968 8 .235 1.385a.5.5 0 0 1 0-.707z'/%3e%3c/svg%3e") center/1em auto no-repeat;
        filter: brightness(1.5);
        opacity: 0.8;
        transition: opacity 0.3s ease;
    }

    .modal.slidedown .btn-close:hover {
        opacity: 1;
        transform: scale(1.1);
    }

    /* بدنه modal */
    .modal.slidedown .modal-body {
        padding: 30px 25px;
        background: rgba(26, 32, 44, 0.95);
    }

    /* فرم ها */
    .modal.slidedown .form-label {
        color: #cbd5e0;
        font-weight: 500;
        margin-bottom: 8px;
        font-size: 0.95rem;
    }

    .modal.slidedown .form-control {
        background: rgba(45, 55, 72, 0.8);
        border: 2px solid #4a5568;
        border-radius: 12px;
        color: #e2e8f0;
        padding: 12px 16px;
        font-size: 1rem;
        transition: all 0.3s ease;
    }

    .modal.slidedown .form-control:focus {
        background: rgba(45, 55, 72, 0.95);
        border-color: #667eea;
        box-shadow: 0 0 0 0.2rem rgba(102, 126, 234, 0.25);
        color: #ffffff;
    }

    .modal.slidedown .form-control::placeholder {
        color: #a0aec0;
    }

    /* Input Group */
    .modal.slidedown .input-group-text {
        background: rgba(45, 55, 72, 0.8);
        border: 2px solid #4a5568;
        border-left: none;
        color: #cbd5e0;
        border-radius: 0 12px 12px 0;
    }

    .modal.slidedown .input-group .form-control {
        border-left: 2px solid #4a5568;
        border-radius: 12px 0 0 12px;
    }

    /* Checkbox */
    .modal.slidedown .form-check-input {
        background-color: rgba(45, 55, 72, 0.8);
        border: 2px solid #4a5568;
        border-radius: 6px;
    }

    .modal.slidedown .form-check-input:checked {
        background-color: #667eea;
        border-color: #667eea;
    }

    .modal.slidedown .form-check-label {
        color: #cbd5e0;
    }

    /* دکمه ها */
    .modal.slidedown .btn {
        border-radius: 12px;
        padding: 12px 20px;
        font-weight: 600;
        font-size: 1rem;
        transition: all 0.3s ease;
        border: none;
    }

    .modal.slidedown .btn-primary {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        box-shadow: 0 4px 15px rgba(102, 126, 234, 0.3);
    }

    .modal.slidedown .btn-primary:hover {
        background: linear-gradient(135deg, #5a67d8 0%, #6b46c1 100%);
        transform: translateY(-2px);
        box-shadow: 0 6px 20px rgba(102, 126, 234, 0.4);
    }

    .modal.slidedown .btn-success {
        background: linear-gradient(135deg, #48bb78 0%, #38a169 100%);
        box-shadow: 0 4px 15px rgba(72, 187, 120, 0.3);
    }

    .modal.slidedown .btn-success:hover {
        background: linear-gradient(135deg, #38a169 0%, #2f855a 100%);
        transform: translateY(-2px);
        box-shadow: 0 6px 20px rgba(72, 187, 120, 0.4);
    }

    .modal.slidedown .btn-danger {
        background: linear-gradient(135deg, #f56565 0%, #e53e3e 100%);
        box-shadow: 0 4px 15px rgba(245, 101, 101, 0.3);
    }

    .modal.slidedown .btn-danger:hover {
        background: linear-gradient(135deg, #e53e3e 0%, #c53030 100%);
        transform: translateY(-2px);
        box-shadow: 0 6px 20px rgba(245, 101, 101, 0.4);
    }

    .modal.slidedown .btn-warning {
        background: linear-gradient(135deg, #ed8936 0%, #dd6b20 100%);
        color: #ffffff;
        box-shadow: 0 4px 15px rgba(237, 137, 54, 0.3);
    }

    .modal.slidedown .btn-warning:hover {
        background: linear-gradient(135deg, #dd6b20 0%, #c05621 100%);
        transform: translateY(-2px);
        box-shadow: 0 6px 20px rgba(237, 137, 54, 0.4);
        color: #ffffff;
    }

    /* لینک ها */
    .modal.slidedown a {
        color: #81c3f7;
        text-decoration: none;
        transition: color 0.3s ease;
    }

    .modal.slidedown a:hover {
        color: #90cdf4;
        text-decoration: underline;
    }

    /* متن کمکی */
    .modal.slidedown .text-muted {
        color: #a0aec0 !important;
        font-size: 0.85rem;
    }

    /* بک گراند modal */
    .modal.slidedown .modal-backdrop {
        background-color: rgba(0, 0, 0, 0.7);
        backdrop-filter: blur(5px);
    }

    /* آیکون ها */
    .modal.slidedown .bi {
        margin-left: 8px;
    }

    /* انیمیشن برای فرم المان ها */
    .modal.slidedown .form-control,
    .modal.slidedown .btn {
        animation: fadeInUp 0.6s ease forwards;
    }

    .modal.slidedown .form-control:nth-child(1) {
        animation-delay: 0.1s;
    }

    .modal.slidedown .form-control:nth-child(2) {
        animation-delay: 0.2s;
    }

    .modal.slidedown .form-control:nth-child(3) {
        animation-delay: 0.3s;
    }

    @keyframes fadeInUp {
        from {
            opacity: 0;
            transform: translateY(20px);
        }

        to {
            opacity: 1;
            transform: translateY(0);
        }
    }



    /* Captcha Image Styles */
    .modal.slidedown #captchaImage,
    .modal.slidedown #captchaImage2 {
        border-radius: 8px;
        border: 2px solid #4a5568;
        transition: border-color 0.3s ease;
    }

    .modal.slidedown #captchaImage:hover,
    .modal.slidedown #captchaImage2:hover {
        border-color: #667eea;
    }

    /*Pricing table and price blocks*/
    .pricing_table {
        line-height: 150%;
        font-size: 12px;
        margin: 0 auto;
        width: 100%;
        padding-top: 10px;
        margin-top: 100px;
    }

    .price_block {
        text-align: center;
        width: 100%;
        color: #fff;
        float: left;
        list-style-type: none;
        transition: all 0.25s;
        position: relative;
        box-sizing: border-box;

        margin-bottom: 10px;
        border-bottom: 1px solid transparent;
    }

    /*Price heads*/
    .pricing_table h3 {
        text-transform: uppercase;
        padding: 5px 0;
        background: #333;
        margin: -10px 0 1px 0;
    }

    /*Price tags*/
    .price {
        display: table;
        background: #444;
        width: 100%;
        height: 70px;
    }

    .price_figure {
        font-size: 24px;
        text-transform: uppercase;
        vertical-align: middle;
        display: table-cell;
    }

    .price_number {
        font-weight: bold;
        display: block;
    }

    .price_tenure {
        font-size: 11px;
    }

    /*Features*/
    .features {
        background: #333333;
        color: #000;
    }

    .features li {
        padding: 8px 15px;
        border-bottom: 1px solid #ccc;
        font-size: 11px;
        list-style-type: none;
    }

    .footer {
        padding: 15px;
        background: #333333;
    }

    .action_button {
        text-decoration: none;
        color: #fff;
        font-weight: bold;
        border-radius: 5px;
        background: linear-gradient(#666, #333);
        padding: 5px 20px;
        font-size: 11px;
        text-transform: uppercase;
    }

    .price_block:hover {
        box-shadow: 0 0 0px 5px rgba(0, 0, 0, 0.5);
        transform: scale(1.04) translateY(-5px);
        z-index: 1;
        border-bottom: 0 none;
    }

    .price_block:hover .price {
        background: linear-gradient(#DB7224, #F9B84A);
        box-shadow: inset 0 0 45px 1px #DB7224;
    }

    .price_block:hover h3 {
        background: #222;
    }

    .price_block:hover .action_button {
        background: linear-gradient(#F9B84A, #DB7224);
    }


    @media only screen and (min-width : 480px) and (max-width : 768px) {
        .price_block {
            width: 50%;
        }

        .price_block:nth-child(odd) {
            border-right: 1px solid transparent;
        }

        .price_block:nth-child(3) {
            clear: both;
        }

        .price_block:nth-child(odd):hover {
            border: 0 none;
        }
    }

    @media only screen and (min-width : 768px) {
        .price_block {
            width: 25%;
        }

        .price_block {
            border-right: 1px solid transparent;
            border-bottom: 0 none;
        }

        .price_block:last-child {
            border-right: 0 none;
        }

        .price_block:hover {
            border: 0 none;
        }
    }


.enabled-icon {
    color: white;
    font-weight: bold;
}

.disabled-icon {
    color: wheat;
    opacity: 0.6;
}







</style>

<body>
    <header class="tmp-header-area-start header-one header--sticky header--transparent sticky">
        <div class="container">
            <div class="row">
                <div class="col-lg-12">
                    <div class="header-content">
                        <div class="logo">
                            <a href="<?= SITE_URL ?>/index.php">
                                <img alt="Farsi Fahr" class="logo-dark" src="assets/images/logo/logoAsset%201.svg">
                                <img alt="Farsi Fahr" class="logo-white" src="assets/images/logo/logoAsset%201.svg">
                            </a>
                        </div>
                        <nav class="tmp-mainmenu-nav d-none d-xl-block">
                            <ul class="tmp-mainmenu">
                                <li class="nav-item">
                                    <a class="nav-link <?= basename($_SERVER['PHP_SELF']) === 'index.php' ? 'active' : '' ?>"
                                        href="index.php">
                                        <i class="bi bi-house me-1"></i>خانه
                                    </a>
                                </li>
                                <?php if (is_logged_in()): ?>
                                    <!-- Logged in user navigation -->
                                    <li class="nav-item">
                                        <a class="nav-link <?= strpos($_SERVER['REQUEST_URI'], '/user/') !== false ? 'active' : '' ?>"
                                            href="user/dashboard.php">
                                            <i class="bi bi-speedometer2 me-1"></i>داشبورد
                                        </a>
                                    </li>


                                <?php else: ?>
                                    <!-- Guest navigation -->
                                    <li class="nav-item">
                                        <a class="nav-link" href="#pricing">
                                            <i class="bi bi-tags me-1"></i>تعرفه ها
                                        </a>
                                    </li>

                                <?php endif; ?>
                            </ul>
                        </nav>
                        <div class="tmp-header-right">
                            <div class="social-share-wrapper d-none d-md-block">
                                <div class="social-link"><a href="#"><i class="fa-brands fa-instagram"></i></a> <a
                                        href="#"><i class="fa-brands fa-linkedin-in"></i></a> <a href="#"><i
                                            class="fa-brands fa-twitter"></i></a> <a href="#"><i
                                            class="fa-brands fa-facebook-f"></i></a></div>
                            </div>
                            <div class="actions-area">
                                <div class="tmp-side-collups-area d-none d-xl-block">
                                    <button class="tmp-menu-bars tmp_button_active"><i
                                            class="fa-regular fa-bars-staggered"></i></button>
                                </div>
                                <div class="tmp-side-collups-area d-block d-xl-none">
                                    <button class="tmp-menu-bars humberger_menu_active"><i
                                            class="fa-regular fa-bars-staggered"></i></button>
                                </div>
                            </div>
                        </div>

                        <div class="d-flex align-items-center">
                            <?php if (is_logged_in()): ?>
                                <span><?= $_SESSION['name'] ?></span>
                            <?php else: ?>
                                <!-- Guest User Buttons -->

                                <a class="nav-link btn btn-dark btn-lg text-white fs-4 mx-2" data-bs-toggle="modal"
                                    data-bs-target="#loginModal">
                                    ورود
                                </a>
                                <a class="nav-link btn btn-dark btn-lg text-white fs-4" data-bs-toggle="modal"
                                    data-bs-target="#registerModal">
                                    ثبت نام
                                </a>
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
                        با افتخار آموزگار ، مترجم ، پشتیبان و پارتنر شما در پروسه اخذ گواهینامه آلمانی هستیم
                    </h5>
                    <p class="disc">

                    </p>
                    <div class="short-contact-area">
                        <div class="single-contact"><i class="fa-solid fa-phone"></i>
                            <div class="information tmp-link-animation"><span>تماس واتس اپپ</span> <a class="number"
                                    href="#">004917661812772</a>
                            </div>
                        </div>
                        <div class="single-contact"><i class="fa-solid fa-envelope"></i>
                            <div class="information tmp-link-animation"><span>با ما توسط ایمیل ارتباط بگیرید</span> <a
                                    class="number" href="#">admin@farsiapp.de</a></div>
                        </div>
                        <!--       <div class="single-contact"><i class="fa-solid fa-location-crosshairs"></i>
                               <div class="information tmp-link-animation"><span>آدرس من</span> <span class="number">66 بروکلین ، نیویورک 3269</span>
                               </div>
                           </div>-->
                    </div>
                    <div class="social-wrapper mt--20"><span class="subtitle">یا در فضای مجازی دنبال کنید</span>
                        <div class="social-link"><a href="#"><i class="fa-brands fa-instagram"></i></a> <a href="#"><i
                                    class="fa-brands fa-linkedin-in"></i></a> <a href="#"><i
                                    class="fa-brands fa-twitter"></i></a> <a href="#"><i
                                    class="fa-brands fa-facebook-f"></i></a></div>
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
                    <li><a href="#">خانه</a></li>
                    <li><a href="about.html">در مورد</a></li>
                    <li><a href="contact.html">تماس</a></li>
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
                                    src="assets/images/banner/banner-user-image-two2.png">
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
                        <div class="inner"><span class="sub-title tmp-scroll-trigger tmp-fade-in animation-order-1">سلام
                                دوست من !</span>
                            <h1 class="title tmp-scroll-trigger tmp-fade-in animation-order-2">گواهینامه آلمانی سخت
                                نیست،
                                چون اینجا <br>
                                <span class="header-caption">
                                    <span class="cd-headline clip is-full-width">
                                        <span class="cd-words-wrapper" style="width: 107.73px; overflow: hidden;">
                                            <b class="theme-gradient is-visible">ترجمه اختصاصی</b>
                                            <b class="theme-gradient is-hidden">آموزش فارسی</b>
                                            <b class="theme-gradient is-hidden">برنامه ریزی</b>
                                            <b class="theme-gradient is-hidden">تمرین واژه ها</b>
                                            <b class="theme-gradient is-hidden">مدل امتحان</b>
                                            <b class="theme-gradient is-hidden">پشتیبانی 24 ساعته</b>
                                        </span>
                                    </span>
                                </span> داریم
                            </h1>
                            <p class="disc tmp-scroll-trigger tmp-title-split tmp-fade-in animation-order-3">
                                <span>کنار شما هستیم</span>
                                تا حتی با داشتن سطح زبان آلمانی پایین بتونید در مدت کوتاه برای
                                <span>آزمون تئوری گواهینامه رانندگی آلمانی </span>
                                آماده بشید!
                            </p>
                            <div class="button-area-banner-two tmp-scroll-trigger tmp-fade-in animation-order-4"><a
                                    class="tmp-btn hover-icon-reverse radius-round" href="#"> <span
                                        class="icon-reverse-wrapper"> <span class="btn-text">اطلاعات بیشتر در مورد
                                            ما</span> <span class="btn-icon"><i
                                                class="fa-sharp fa-regular fa-arrow-left"></i></span> <span
                                            class="btn-icon"><i class="fa-sharp fa-regular fa-arrow-left"></i></span>
                                    </span> </a></div>
                            <div class="find-me-on tmp-scroll-trigger tmp-fade-in animation-order-5">
                                <h2 class="find-me-on-title">ما رو دنبال کن</h2>
                                <div class="social-link banner"><a href="#"><i class="fa-brands fa-instagram"></i></a>
                                    <a href="#"><i class="fa-brands fa-linkedin-in"></i></a> <a href="#"><i
                                            class="fa-brands fa-twitter"></i></a> <a href="#"><i
                                            class="fa-brands fa-facebook-f"></i></a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="banner-shape-two"><img alt="" src="assets/images/banner/banner-shape-two.png"></div>
    </div>
    <div class="about-content-area">
        <div class="container tmp-section-gap">
            <div class="text-para-doc-wrap">
                <h2 class="text-para-documents tmp-scroll-trigger tmp-fade-in tmp-title-split-2 animation-order-1">
                    پروسه
                    <span>
                        یادگیری و آمادگی
                    </span>
                    برای آزمون گواهینامه آلمانی
                    برای فارسی زبانان همیشه سخت، هزینه بر و طاقت فرسا بوده است.
                    ترجمه و مشاهده ویدیو های آموزشی همچنین زمان بر
                    و دسترسی به آن ها آسان نیست، با در نظر گرفتن تمام
                    این مشکلات امروز می توانید با

                    <span>
                        متد جدید این سامانه
                    </span>
                    با حداقل مشکلات سابق این مسیر را پشت سر بگذارید.
                </h2>
                <div class="left-bg-text-para"><img alt="" src="assets/images/banner/right-bg-text-para-doc.png"></div>
                <div class="right-bg-text-para"><img alt="" src="assets/images/banner/left-bg-text-para-doc.png"></div>
            </div>
        </div>
    </div>
    <section class="about-us-area">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-lg-6">
                    <div class="about-us-left-content-wrap bg-vactor-one">
                        <div class="years-of-experience-card tmp-scroll-trigger tmp-fade-in animation-order-1">
                            <h2 class="counter card-title ">
                                <span class="odometer ltr" data-count="3500">00</span>+
                            </h2>
                            <p class="card-para">توضیح فارسی برای پاسخ های صحیح و غلط</p>
                        </div>
                        <div class="design-card tmp-scroll-trigger tmp-fade-in animation-order-2">
                            <div class="design-card-img">
                                <div class="icon"><i class="fa-sharp fa-thin fa-lock"></i></div>
                            </div>
                            <div class="card-info">
                                <h3 class="card-title">آموزش های اساسی</h3>
                                <p class="card-para">241 آموزش</p>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-lg-6">
                    <div class="about-us-right-content-wrap">
                        <div class="section-head text-align-left mb--50">
                            <div class="section-sub-title tmp-scroll-trigger tmp-fade-in animation-order-1"><span
                                    class="subtitle">استراتژی ما</span></div>
                            <h2 class="title split-collab tmp-scroll-trigger tmp-fade-in animation-order-2">زمان کوتاه
                                با
                                حداقل سطح زبان</h2>
                            <p class="description tmp-scroll-trigger tmp-fade-in animation-order-3"></p>
                        </div>
                        <div class="about-us-section-card row g-5">
                            <div class="col-lg-6 col-md-6 col-sm-6 col-12">
                                <div class="about-us-card tmponhover tmp-scroll-trigger tmp-fade-in animation-order-4"
                                    style="--x: 146px; --y: 20px;">
                                    <div class="card-head">
                                        <div class="logo-img"><img alt="logo" src="assets/images/about/logo-1.svg">
                                        </div>
                                        <h3 class="card-title"> کوتاه ترین زمان</h3>
                                    </div>
                                    <p class="card-para">با تحقیق از بین صدها متقاضی گواهینامه در ساله های گذشته، طبق یک
                                        فرومول بسیار جذاب، میتوانیم قبل از شروع زمان مورد نیاز برای آمادگی را با توجه به
                                        سطح
                                        زبان و اطلاعات شما تخمین بزنیم.</p>
                                </div>
                            </div>
                            <div class="col-lg-6 col-md-6 col-sm-6 col-12">
                                <div class="about-us-card tmponhover tmp-scroll-trigger tmp-fade-in animation-order-5">
                                    <div class="card-head">
                                        <div class="logo-img"><img alt="logo" src="assets/images/about/logo-2.svg">
                                        </div>
                                        <h3 class="card-title">مشکل سطح زبان </h3>
                                    </div>
                                    <p class="card-para">با پروسه تمرین و تکرار، یادگیری کلمات و یک تمرین ثابت طبق
                                        برنامه،
                                        مشکل پایین بودن سطح زبان آلمانی شما برای یادگیری سوالات را به حداقل می رسانیم.
                                    </p>
                                </div>
                            </div>
                        </div>
                        <div
                            class="about-btn mt--40 tmp-scroll-trigger tmp-fade-in animation-order-6 tmp-scroll-trigger--offscreen">
                            <a class="tmp-btn hover-icon-reverse radius-round" href="about.html"> <span
                                    class="icon-reverse-wrapper"> <span class="btn-text">همین حالا رایگان تست
                                        کنید</span> <span class="btn-icon"><i
                                            class="fa-sharp fa-regular fa-arrow-left"></i></span> <span
                                        class="btn-icon"><i class="fa-sharp fa-regular fa-arrow-left"></i></span>
                                </span> </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
    <!-- Tpm My Price plan Start -->
<section class="our-price-plan-area tmp-section-gapTop" id="pricing">
    <div class="container">
        <div class="section-head">
            <div class="section-sub-title center-title tmp-scroll-trigger tmp-fade-in animation-order-1">
                <span class="subtitle">جدول اشتراک ها</span>
            </div>
            <h2 class="title split-collab tmp-scroll-trigger tmp-fade-in animation-order-2">قیمتگذاری ساده و شفاف</h2>
            <p>طرحی را انتخاب کنید که به بهترین وجه با نیازهای شما مطابقت داشته باشد. همه طرح‌ها شامل ویژگی‌های اصلی ما هستند.</p>
        </div>

        <div class="row align-items-center">
            <ul class="pricing_table">

                <?php
                // دریافت همه پلن‌ها و ویژگی‌ها
                $plans = get_all_subscription_plans($pdo);
                $all_features = get_all_features($pdo);

                foreach ($plans as $plan):
                    $features = get_plan_features($plan['id'], $pdo);

                    // برای تطبیق سریع ویژگی‌ها
                    $feature_map = [];
                    foreach ($features as $f) {
                        $feature_map[$f['id']] = $f;
                    }

                    // کلاس رنگ پلن
                    $plan_class = '';
                    if (strpos(strtolower($plan['name']), 'gold') !== false) {
                        $plan_class = 'text-gold';
                    } elseif (strpos(strtolower($plan['name']), 'silver') !== false) {
                        $plan_class = 'text-silver';
                    } elseif (strpos(strtolower($plan['name']), 'bronze') !== false) {
                        $plan_class = 'text-bronze';
                    }

                    // قیمت
                    $monthly_price = calculate_plan_price($plan['id'], false, $pdo);
                ?>

                <li class="price_block <?= $plan_class ?>">
                    <h3><?= $plan['name'] ?></h3>
                    <div class="price">
                        <div class="price_figure">
                            <span class="price_number"><?= format_price($monthly_price['price']) ?></span>
                        </div>
                    </div>

                    <ul class="features">
                        <?php foreach ($all_features as $feature): 
                            $is_enabled = isset($feature_map[$feature['id']]);
                            $value = $is_enabled
                                ? format_feature_value($feature_map[$feature['id']]['feature_value'], $feature_map[$feature['id']]['is_unlimited'])
                                : '-';

                            $icon_class = $is_enabled ? 'enabled-icon' : 'disabled-icon';
                            $icon_html = $is_enabled
                                ? ' <i class="fa fa-check-circle text-success fs-3"></i> '
                                : ' <i class="fa fa-times-circle fs-3"></i> ';
                        ?>
                            <li class="<?= $icon_class ?> text-start">
                                <?= $icon_html ?> <?= $feature['name'] ?>
                            </li>
                        <?php endforeach; ?>
                    </ul>

                    <div class="footer w-100">
                        <a href="#" class="action_button btn btn-primary p-3 fs-3 w-100">خرید</a>
                    </div>
                </li>

                <?php endforeach; ?>
            </ul>
        </div>
    </div>
</section>

    <!-- Tpm My Price plan End -->

    <section class="blog-and-news-are tmp-section-gap">
        <div class="container">
            <div class="section-head mb--50">
                <div
                    class="section-sub-title center-title tmp-scroll-trigger tmp-fade-in animation-order-1 tmp-scroll-trigger--offscreen">
                    <span class="subtitle">آخرین وبلاگ</span>
                </div>
                <h2
                    class="title split-collab tmp-scroll-trigger tmp-fade-in animation-order-2 tmp-scroll-trigger--offscreen">
                    اطلاعات اولیه و<br>
                    آموزش های رایگان
                </h2>
            </div>
            <div class="row">
                <div class="col-lg-4 col-md-6 col-12">
                    <div
                        class="blog-card-style-two tmponhover image-box-hover tmp-scroll-trigger tmp-fade-in animation-order-3 tmp-scroll-trigger--offscreen">
                        <div class="blog-card-img">
                            <div class="img-box"><a href="blog-details.html"> <img alt="Blog Thumbnail" class="w-100"
                                        src="assets/images/blog/blog-img-1.jpg">
                                </a></div>
                            <span>12 دی</span>
                        </div>
                        <div class="blog-content-wrap">
                            <div class="blog-tags">
                                <ul>
                                    <li><a href="#"><i class="fa-regular fa-user"></i>مسبز</a></li>
                                    <li><a href="#"><i class="fa-regular fa-comments"></i>نظرات (05)</a></li>
                                </ul>
                            </div>
                            <h3 class="blog-title"><a href="blog-details.html">از کجا شروع کنم برای اخذ گواهینامه؟</a>
                            </h3>
                            <div class="read-more-btn"><a
                                    class="tmp-btn hover-icon-reverse radius-round btn-border btn-md"
                                    href="blog-details.html"> <span class="icon-reverse-wrapper"> <span
                                            class="btn-text">بیشتر بخوانید</span> <span class="btn-icon"><i
                                                class="fa-sharp fa-regular fa-arrow-left"></i></span> <span
                                            class="btn-icon"><i class="fa-sharp fa-regular fa-arrow-left"></i></span>
                                    </span> </a>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-lg-4 col-md-6 col-12">
                    <div
                        class="blog-card-style-two tmponhover image-box-hover tmp-scroll-trigger tmp-fade-in animation-order-2 tmp-scroll-trigger--offscreen">
                        <div class="blog-card-img">
                            <div class="img-box"><a href="blog-details.html"> <img alt="Blog Thumbnail" class="w-100"
                                        src="assets/images/blog/blog-img-2.jpg">
                                </a></div>
                            <span>12 دی</span>
                        </div>
                        <div class="blog-content-wrap">
                            <div class="blog-tags">
                                <ul>
                                    <li><a href="#"><i class="fa-regular fa-user"></i>مسبز</a></li>
                                    <li><a href="#"><i class="fa-regular fa-comments"></i>نظرات (05)</a></li>
                                </ul>
                            </div>
                            <h3 class="blog-title"><a href="blog-details.html">مراحل و قوانین ترجمه گواهینامه ایران</a>
                            </h3>
                            <div class="read-more-btn"><a
                                    class="tmp-btn hover-icon-reverse radius-round btn-border btn-md"
                                    href="blog-details.html"> <span class="icon-reverse-wrapper"> <span
                                            class="btn-text">بیشتر بخوانید</span> <span class="btn-icon"><i
                                                class="fa-sharp fa-regular fa-arrow-left"></i></span> <span
                                            class="btn-icon"><i class="fa-sharp fa-regular fa-arrow-left"></i></span>
                                    </span> </a>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-lg-4 col-md-6 col-12">
                    <div
                        class="blog-card-style-two tmponhover image-box-hover tmp-scroll-trigger tmp-fade-in animation-order-3 tmp-scroll-trigger--offscreen">
                        <div class="blog-card-img">
                            <div class="img-box"><a href="blog-details.html"> <img alt="Blog Thumbnail" class="w-100"
                                        src="assets/images/blog/blog-img-3.jpg">
                                </a></div>
                            <span>12 دی</span>
                        </div>
                        <div class="blog-content-wrap">
                            <div class="blog-tags">
                                <ul>
                                    <li><a href="#"><i class="fa-regular fa-user"></i>مسبز</a></li>
                                    <li><a href="#"><i class="fa-regular fa-comments"></i>نظرات (05)</a></li>
                                </ul>
                            </div>
                            <h3 class="blog-title"><a href="blog-details.html">راهکارهایی برای کاهش استرس در امتحان</a>
                            </h3>
                            <div class="read-more-btn"><a
                                    class="tmp-btn hover-icon-reverse radius-round btn-border btn-md"
                                    href="blog-details.html"> <span class="icon-reverse-wrapper"> <span
                                            class="btn-text">بیشتر بخوانید</span> <span class="btn-icon"><i
                                                class="fa-sharp fa-regular fa-arrow-left"></i></span> <span
                                            class="btn-icon"><i class="fa-sharp fa-regular fa-arrow-left"></i></span>
                                    </span> </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
    <footer class="footer-area footer-style-two-wrapper bg-color-footer bg_images tmp-section-gap">
        <div class="container">
            <div class="footer-main footer-style-two">
                <div class="row g-5">
                    <div class="col-lg-3 col-md-4 col-sm-6">
                        <div class="single-footer-wrapper border-right mr--20">
                            <div class="logo"><a href="index.html"> <img alt="Farsi - Fahr"
                                        src="assets/images/logo/logoAsset%201.svg"> </a></div>
                            <p class="description">در راستای حمایت از فارسی زبانان عزیز در کشور آلمان جهت تسهیل فرایند
                                قبولی
                                در آزمون تئوری گواهینامه، برآن شدیم سامانه ای جامع و کامل آماده کنیم که به آخرین بانک
                                سوالات
                                به روز باشد و پس از ترجمه اختصاصی سوالات بدون سیستم های مترجم آنلاین در بحث آموزش به
                                زبان
                                فارسی در تک تک پاسخ ها نیز مجهز باشد تا این مسیر برای همه هموار شود.</p>
                            <div class="social-link footer"><a href="#"><i class="fa-brands fa-instagram"></i></a> <a
                                    href="#"><i class="fa-brands fa-linkedin-in"></i></a> <a href="#"><i
                                        class="fa-brands fa-twitter"></i></a> <a href="#"><i
                                        class="fa-brands fa-facebook-f"></i></a></div>
                        </div>
                    </div>
                    <div class="col-lg-2 col-md-4 col-sm-6">
                        <div class="quick-link-wrap">
                            <h5 class="ft-title">لینک سریع</h5>
                            <ul
                                class="ft-link tmp-scroll-trigger animation-order-1 tmp-link-animation tmp-scroll-trigger--offscreen">
                                <li><a href="about.html">درباره ما</a></li>
                                <li><a href="team.html">خدمت</a></li>
                                <li><a href="contact.html">قیمت گذاری</a></li>
                            </ul>
                        </div>
                    </div>
                    <div class="col-lg-3 col-md-4 col-sm-6">
                        <div class="single-footer-wrapper contact-wrap">
                            <h5 class="ft-title">تماس</h5>
                            <ul
                                class="ft-link tmp-scroll-trigger animation-order-1 tmp-link-animation tmp-scroll-trigger--offscreen">
                                <li><span class="ft-icon"><i class="fa-solid fa-phone"></i></span><a
                                        href="#">004917661812772</a>
                                </li>
                                <li><span class="ft-icon"><i class="fa-solid fa-envelope"></i></span><a
                                        href="#">admin@farsi-app.de</a>
                                </li>
                            </ul>
                        </div>
                    </div>
                    <div class="col-lg-4 col-md-6 col-sm-6">
                        <div class="newslatter tmp-scroll-trigger animation-order-1 tmp-scroll-trigger--offscreen">
                            <h3 class="title">خبرنامه</h3>
                            <p class="para">از آخرین تغییرات ما در لحظه با خبر باشید</p>
                            <form action="#" class="newsletter-form-1"><input placeholder="ایمیل شما" type="email">
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
                        <p class="copy-right-para">© FARSI-APP
                            <script>
                                document.write(new Date().getFullYear())
                            </script>
                            2025 | کلیه حقوق محفوظ است
                        </p>
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

    <!-- Login Modal -->
    <div class="modal fade slidedown" aria-hidden="true" id="loginModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">ورود به حساب کاربری</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <form id="loginForm">
                        <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                        <input type="hidden" name="action" value="login">

                        <div class="mb-3">
                            <label class="form-label">ایمیل</label>
                            <input type="email" class="form-control" name="email" required>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">رمز عبور</label>
                            <input type="password" class="form-control" name="password" required>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">کد امنیتی</label>
                            <div class="input-group">
                                <input type="text" class="form-control" name="captcha" required>
                                <span class="input-group-text p-0">
                                    <img src="incloud/captcha.php" alt="کپچا" id="captchaImage" style="cursor: pointer;"
                                        onclick="refreshCaptcha()">
                                </span>
                            </div>
                        </div>

                        <div class="mb-3 form-check">
                            <input type="checkbox" class="form-check-input" name="remember" id="remember">
                            <label class="form-check-label" for="remember">مرا به خاطر بسپار</label>
                        </div>

                        <div class="d-grid gap-2">
                            <button type="submit" class="btn btn-primary">
                                <i class="bi bi-box-arrow-in-left"></i> ورود
                            </button>

                            <button type="button" class="btn btn-danger" onclick="googleLogin()">
                                <i class="bi bi-google"></i> ورود با گوگل
                            </button>
                        </div>

                        <div class="text-center mt-3">
                            <a href="#" onclick="showResetModal()">رمز عبور خود را فراموش کرده‌اید؟</a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Register Modal -->
    <div class="modal fade slidedown" aria-hidden="true" id="registerModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">ثبت نام</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <form id="registerForm">
                        <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                        <input type="hidden" name="action" value="register">

                        <div class="mb-3">
                            <label class="form-label">نام و نام خانوادگی</label>
                            <input type="text" class="form-control" name="name" required>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">ایمیل</label>
                            <input type="email" class="form-control" name="email" required>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">رمز عبور</label>
                            <input type="password" class="form-control" name="password" id="password" required>
                            <small class="text-muted">حداقل 8 کاراکتر، شامل حروف بزرگ، کوچک و عدد</small>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">تکرار رمز عبور</label>
                            <input type="password" class="form-control" name="password_confirm" required>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">کد امنیتی</label>
                            <div class="input-group">
                                <input type="text" class="form-control" name="captcha" required>
                                <span class="input-group-text p-0">
                                    <img src="incloud/captcha.php" alt="کپچا" id="captchaImage2"
                                        style="cursor: pointer;" onclick="refreshCaptcha2()">
                                </span>
                            </div>
                        </div>

                        <div class="d-grid gap-2">
                            <button type="submit" class="btn btn-success">
                                <i class="bi bi-person-plus"></i> ثبت نام
                            </button>

                            <button type="button" class="btn btn-danger" onclick="googleLogin()">
                                <i class="bi bi-google"></i> ثبت نام با گوگل
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Reset Password Modal -->
    <div class="modal fade slidedown" aria-hidden="true" id="resetModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">بازیابی رمز عبور</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <form id="resetForm">
                        <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                        <input type="hidden" name="action" value="reset">

                        <div class="mb-3">
                            <label class="form-label">ایمیل حساب کاربری</label>
                            <input type="email" class="form-control" name="email" required>
                            <small class="text-muted">لینک بازیابی رمز عبور به این ایمیل ارسال خواهد شد</small>
                        </div>

                        <div class="d-grid">
                            <button type="submit" class="btn btn-warning">
                                <i class="bi bi-envelope"></i> ارسال لینک بازیابی
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script src="assets/js/vendor/jquery.js"></script>
    <script src="assets/js/vendor/jquery-ui.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <script src="assets/js/vendor/waypoints.min.js"></script>
    <script src="assets/js/plugins/odometer.js"></script>
    <script src="assets/js/vendor/appear.js"></script>
    <script src="assets/js/vendor/jquery-one-page-nav.js"></script>
    <script src="assets/js/plugins/swiper.js"></script>
    <script src="assets/js/plugins/gsap.js"></script>
    <script src="assets/js/plugins/splittext.js"></script>
    <script src="assets/js/plugins/scrolltigger.js"></script>
    <script src="assets/js/plugins/scrolltoplugins.js"></script>
    <script src="assets/js/plugins/smoothscroll.js"></script>
    <script src="assets/js/vendor/bootstrap.min.js"></script>
    <script src="assets/js/vendor/waw.js"></script>
    <script src="assets/js/plugins/isotop.js"></script>
    <script src="assets/js/plugins/animation.js"></script>
    <script src="assets/js/plugins/contact.form.js"></script>
    <script src="assets/js/vendor/backtop.js"></script>
    <script src="assets/js/plugins/text-type.js"></script>
    <script src="assets/js/main.js"></script>
    <script src="assets/js/script.js"></script>

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
                perMonthTexts.forEach(el => el.textContent = 'سالانه (با تخفیف)');
            } else {
                monthlyPrices.forEach(el => el.classList.remove('d-none'));
                yearlyPrices.forEach(el => el.classList.add('d-none'));
                perMonthTexts.forEach(el => el.textContent = 'ماهانه');
            }
        });
</script>
<script src="//code.tidio.co/nowavmidkbaonz1hejosf6omswpklxsv.js" async></script>

</body >

</html >