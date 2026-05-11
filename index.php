<?php
/**
 * PHP 8.4 Pure Functional Script
 */

// Include configuration
require_once __DIR__ . '/incloud/functions.php';
require_once __DIR__ . '/incloud/subscription-functions.php';

// جلوگیری از کش شدن صفحه در حالت‌های مختلف لاگین
header('Vary: Cookie');
header('Cache-Control: no-cache, no-store, must-revalidate'); // HTTP 1.1
header('Pragma: no-cache'); // HTTP 1.0
header('Expires: 0'); // Proxies
header('X-LiteSpeed-Cache-Control: no-cache'); // LiteSpeed Server

// بررسی وجود برنامه مطالعه
$has_study_plan = false;
$study_plan_data = null;
$days_remaining = 0;
if (is_logged_in()) {
    $stmtPlan = $pdo->prepare("SELECT * FROM study_plans WHERE user_id = ?");
    $stmtPlan->execute([$_SESSION['user_id']]);
    $study_plan_data = $stmtPlan->fetch();
    if ($study_plan_data) {
        $has_study_plan = true;
        $days_passed = floor((time() - strtotime($study_plan_data['created_at'])) / (60 * 60 * 24));
        $days_remaining = max(0, $study_plan_data['estimated_total_days'] - $days_passed);
    }
}
?>
<html lang="<?= get_current_lang() ?>" dir="<?= get_lang_dir() ?>">

<head>
    <meta charset="utf-8">
    <meta content="IE=edge" http-equiv="X-UA-Compatible">
    <meta content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no" name="viewport">
    <meta content="Farsi Fahr | آموزش و آمادگی آزمون تئوری گواهینامه آلمانی" name="description">
    <link href="assets/images/favicon.svg" rel="shortcut icon" type="image/x-icon">
    <title>Farsi Fahr | آموزش و آمادگی آزمون تئوری گواهینامه آلمانی</title>
    <link href="assets/css/vendor/fontawesome.css" rel="stylesheet">
    <link href="assets/css/vendor/animate.min.css" rel="stylesheet">
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
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/gh/lipis/flag-icons@7.2.3/css/flag-icons.min.css" />
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">

    <link rel="manifest" href="/manifest.json">
    <meta name="theme-color" content="#667eea">
    <script src="https://www.google.com/recaptcha/api.js?hl=<?= get_current_lang() ?>" async defer></script>

    <style>
        <?php if (get_lang_dir() === 'ltr'): ?>
        body, h1, h2, h3, h4, h5, h6, .title, .btn, .nav-link, span, p {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif !important;
        }
        <?php endif; ?>
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
        background: rgba(15, 23, 42, 0.7) !important;
        border: 1px solid rgba(255, 255, 255, 0.1);
        border-radius: 20px;
        box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.5);
        color: #e2e8f0;
        backdrop-filter: blur(15px) saturate(180%);
        -webkit-backdrop-filter: blur(15px) saturate(180%);
    }

    /* هدر modal */
    .modal.slidedown .modal-header {
        background: transparent;
        border-bottom: 1px solid rgba(255, 255, 255, 0.1);
        border-radius: 20px 20px 0 0;
        padding: 20px 25px;
    }

    .modal.slidedown .modal-title {
        color: #ffffff;
        font-weight: 600;
        font-size: 1.5rem;
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
        background: transparent;
    }

    /* فرم ها */
    .modal.slidedown .form-label {
        color: #cbd5e0;
        font-weight: 500;
        margin-bottom: 8px;
        font-size: 1.2rem;
    }

    .modal.slidedown .form-control {
        background: rgba(45, 55, 72, 0.8);
        border: 2px solid #4a5568;
        border-radius: 12px;
        color: #e2e8f0;
        padding: 14px 18px;
        font-size: max(16px, 1.25rem);
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
        font-size: 1.15rem;
        transition: all 0.3s ease;
        border: 2px solid transparent;
    }

    .modal.slidedown .btn-primary {
        background: rgba(102, 126, 234, 0.15) !important;
        border: 2px solid #667eea !important;
        color: #fff !important;
        box-shadow: 0 4px 15px rgba(102, 126, 234, 0.2);
    }

    .modal.slidedown .btn-primary:hover {
        background: rgba(102, 126, 234, 0.3) !important;
        transform: translateY(-2px);
        box-shadow: 0 6px 20px rgba(102, 126, 234, 0.4);
    }

    .modal.slidedown .btn-success {
        background: rgba(72, 187, 120, 0.15) !important;
        border: 2px solid #48bb78 !important;
        color: #fff !important;
        box-shadow: 0 4px 15px rgba(72, 187, 120, 0.2);
    }

    .modal.slidedown .btn-success:hover {
        background: rgba(72, 187, 120, 0.3) !important;
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
        font-size: 1.05rem;
    }

    .modal.slidedown a:hover {
        color: #90cdf4;
        text-decoration: underline;
    }

    /* متن کمکی */
    .modal.slidedown .text-muted {
        color: #a0aec0 !important;
        font-size: 0.95rem;
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



   .pricing-card {
        transition: all 0.3s ease;
        transform: translateY(0);
    }
    
    .pricing-card:hover {
        transform: translateY(-10px);
    }
    
    .popular-card {
        position: relative;
        z-index: 2;
        box-shadow: 0 15px 40px rgba(0, 123, 255, 0.3) !important;
    }
    
    .features-list li:last-child {
        border-bottom: none !important;
    }
    
    .billing-switch .form-check-input:checked {
        background-color: #28a745;
        border-color: #28a745;
    }
    
    .billing-switch .form-check-input {
        cursor: pointer;
    }
    
    .comparison-table th, 
    .comparison-table td {
        vertical-align: middle;
    }
    
    /* انیمیشن برای نمایش کارت‌ها */
    @keyframes fadeInUp {
        from {
            opacity: 0;
            transform: translateY(30px);
        }
        to {
            opacity: 1;
            transform: translateY(0);
        }
    }
    
    .pricing-card {
        animation: fadeInUp 0.6s ease forwards;
    }
    
    .pricing-card:nth-child(1) { animation-delay: 0.1s; }
    .pricing-card:nth-child(2) { animation-delay: 0.2s; }
    .pricing-card:nth-child(3) { animation-delay: 0.3s; }
    
    /* استایل برای موبایل */
    @media (max-width: 768px) {
        .pricing-card {
            margin-bottom: 30px;
        }
        
        .comparison-table {
            display: none;
        }

        .banner-two-main-wrapper .banner-right-content .main-img img {
            max-width: 60%;
            margin: 0 auto;
        }
        .banner-two-main-wrapper .banner-right-content .main-img::after {
            height: 300px;
        }
        .banner-two-main-wrapper .banner-right-content .main-img .benner-two-bg-red-img img {
            max-width: 60%;
        }

        /* Header Mobile Fixes */
        .header-one .header-content {
            gap: 10px;
        }
        .header-one .tmp-header-right {
            gap: 10px !important;
        }
        .header-one .logo img {
            max-width: 70px !important;
        }
    }

    .header-one .logo img {
        max-width: 110px;
        height: auto;
    }

    /* Rounded Buttons Styling */
    .header-right-group .btn, 
    .header-right-group .tmp-menu-bars,
    .mobile-hamburger-wrap .tmp-menu-bars {
        border-radius: 50px !important; /* Capsule shape */
        font-weight: 600 !important;
        display: flex !important;
        align-items: center;
        justify-content: center;
        transition: all 0.3s ease;
    }

    .header-right-group .btn-sm, .nav-action-item .btn-sm {
        padding: 6px 18px !important;
        font-size: 14px !important;
        border-radius: 0 !important;
        height: 40px !important;
        display: flex !important;
        align-items: center !important;
        justify-content: center !important;
        background: transparent !important;
        border: none !important;
        box-shadow: none !important;
        color: rgba(255,255,255,0.8) !important;
    }

    /* Vertical line separator fix */
    .nav-action-item {
        position: relative;
        display: flex;
        align-items: center;
    }

    /* Add border to all but last item */
    .nav-action-item:not(:last-child)::after {
        content: "";
        position: absolute;
        width: 1px;
        height: 20px;
        background: rgba(255, 255, 255, 0.2);
        top: 50%;
        transform: translateY(-50%);
    }

    [dir="rtl"] .nav-action-item:not(:last-child)::after {
        left: 0;
    }

    [dir="ltr"] .nav-action-item:not(:last-child)::after {
        right: 0;
    }
    
    .header-right-group {
        gap: 0 !important;
    }
    .nav-actions-wrapper {
        gap: 0 !important;
    }

    /* Perfect circle for icon-only buttons */
    .header-right-group .dropdown button.btn-sm,
    .header-right-group .tmp-menu-bars,
    .mobile-hamburger-wrap .tmp-menu-bars {
        width: 40px !important;
        height: 40px !important;
        padding: 0 !important;
        border-radius: 0 !important;
        display: flex !important;
        align-items: center !important;
        justify-content: center !important;
        background: transparent !important;
        border: none !important;
        box-shadow: none !important;
    }

    /* Center the flag icon specifically */
    .header-right-group .dropdown button.btn-sm .fi {
        margin: 0 !important;
        width: 20px;
        height: 14px;
    }

    .header-right-group .tmp-menu-bars,
    .mobile-hamburger-wrap .tmp-menu-bars {
        background: transparent !important;
        border: none !important;
        color: #fff;
    }
    
    .header-right-group .tmp-menu-bars:hover,
    .mobile-hamburger-wrap .tmp-menu-bars:hover {
        background: rgba(255,255,255,0.1) !important;
        color: #fff !important;
    }

    /* Centered Logo and Mobile Ordering */
    @media (max-width: 1199px) {
        .header-content {
            display: flex !important;
            flex-direction: row !important;
            justify-content: space-between !important;
            align-items: center !important;
            position: relative;
        }

        /* Centered logo for both RTL and LTR on mobile */
        .header-content .logo {
            position: absolute !important;
            left: 50% !important;
            transform: translateX(-50%) !important;
            z-index: 10;
            margin: 0 !important;
        }

        /* RTL Handling: Hamburger Right (1), Buttons Left (3) */
        [dir="rtl"] .mobile-hamburger-wrap { order: 1; }
        [dir="rtl"] .header-right-group { order: 3; }
    }

    /* Remove dropdown arrow for language switcher to keep it circular */
    .header-right-group .dropdown-toggle::after {
        display: none !important;
    }

    @media (max-width: 576px) {
        .header-right-group .btn-sm, .nav-action-item .btn-sm {
            padding: 5px 12px !important;
            height: 36px !important;
            font-size: 13px !important;
        }
        .header-right-group .dropdown button.btn-sm,
        .header-right-group .tmp-menu-bars {
            width: 36px !important;
            height: 36px !important;
        }
        .header-right-group .dropdown button.btn-sm .fi {
            width: 18px;
            height: 12px;
        }
    }

    /* Multi-step Form Styles */
    .step-form-container {
        max-width: 900px;
        margin: 0 auto;
        background: rgba(15, 23, 42, 0.6);
        backdrop-filter: blur(15px);
        border: 1px solid rgba(255, 255, 255, 0.1);
        border-radius: 30px;
        padding: 40px;
        box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.5);
    }
    .form-step {
        display: none;
    }
    .form-step.active {
        display: block;
        animation: fadeIn 0.5s ease;
    }
    .progress-wrapper {
        margin-bottom: 40px;
    }
    .step-progress {
        height: 6px;
        background: rgba(255, 255, 255, 0.1);
        border-radius: 10px;
        overflow: hidden;
    }
    .progress-bar-fill {
        height: 100%;
        background: linear-gradient(90deg, #5a8dee, #4868f8);
        width: 33.33%;
        transition: width 0.4s ease;
    }
    .live-result-container {
        margin-top: 50px;
        padding: 30px;
        background: rgba(90, 141, 238, 0.05);
        border-radius: 20px;
        border: 1px solid rgba(255, 255, 255, 0.05);
    }
    .big-days-label {
        font-size: 4.5rem;
        font-weight: 800;
        color: #fff;
        text-shadow: 0 0 30px rgba(90, 141, 238, 0.4);
        line-height: 1;
        margin: 15px 0;
        animation: pulse 2s infinite;
    }
    @keyframes pulse {
        0% { transform: scale(1); }
        50% { transform: scale(1.02); }
        100% { transform: scale(1); }
    }
    .days-text {
        font-size: 1.3rem;
        color: #8295ba;
        font-weight: 500;
    }
    .level-select-item {
        background: rgba(255, 255, 255, 0.03);
        border: 2px solid rgba(255, 255, 255, 0.1);
        border-radius: 15px;
        padding: 20px;
        cursor: pointer;
        transition: all 0.3s ease;
        text-align: center;
    }
    .level-select-item:hover {
        background: rgba(90, 141, 238, 0.1);
        border-color: #5a8dee;
        transform: translateY(-5px);
    }
    .level-select-item.active {
        background: #5a8dee;
        border-color: #5a8dee;
        color: white;
        box-shadow: 0 10px 20px rgba(90, 141, 238, 0.3);
    }
    .summary-item {
        background: rgba(255, 255, 255, 0.05);
        padding: 15px 20px;
        border-radius: 12px;
        margin-bottom: 10px;
        display: flex;
        justify-content: space-between;
        align-items: center;
    }
    .summary-label { color: #8295ba; }
    .summary-value { font-weight: bold; color: #fff; }

    @keyframes fadeIn {
        from { opacity: 0; transform: translateY(10px); }
        to { opacity: 1; transform: translateY(0); }
    }

    /* PWA Install Banner */
    #pwa-install-banner {
        display: none;
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        background: #5a8dee;
        color: white;
        padding: 10px 15px;
        z-index: 10000;
        box-shadow: 0 2px 10px rgba(0,0,0,0.2);
        justify-content: space-between;
        align-items: center;
        font-size: 14px;
        animation: pwaSlideDown 0.5s ease;
        direction: <?= get_lang_dir() ?>;
    }

    /* LTR Special Styles */
    <?php if (get_lang_dir() === 'ltr'): ?>
    :root {
        --font-primary: 'Inter', sans-serif !important;
        --font-secondary: 'Inter', sans-serif !important;
        --font-three: 'Inter', sans-serif !important;
    }
    
    /* Extreme specificity to override aggressive theme fonts */
    html[dir="ltr"] body, 
    html[dir="ltr"] h1, html[dir="ltr"] h2, html[dir="ltr"] h3, 
    html[dir="ltr"] h4, html[dir="ltr"] h5, html[dir="ltr"] h6, 
    html[dir="ltr"] .title, html[dir="ltr"] .btn, html[dir="ltr"] .nav-link, 
    html[dir="ltr"] span, html[dir="ltr"] p, html[dir="ltr"] div, 
    html[dir="ltr"] a, html[dir="ltr"] button, html[dir="ltr"] input, 
    html[dir="ltr"] textarea, html[dir="ltr"] select,
    html[dir="ltr"] .odometer, html[dir="ltr"] .counter,
    html[dir="ltr"] :not(i):not([class*="fa-"]):not([class*="bi-"]) {
        font-family: 'Inter', -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif !important;
    }
    
    /* Re-protect icons with even higher specificity */
    html[dir="ltr"] i, 
    html[dir="ltr"] [class^="fa-"], html[dir="ltr"] [class*=" fa-"], 
    html[dir="ltr"] [class^="bi-"], html[dir="ltr"] [class*=" bi-"], 
    html[dir="ltr"] .fas, html[dir="ltr"] .far, html[dir="ltr"] .fab, 
    html[dir="ltr"] .fa-regular, html[dir="ltr"] .fa-solid, html[dir="ltr"] .fa-brands, 
    html[dir="ltr"] .fa, html[dir="ltr"] .bi {
        font-family: "Font Awesome 6 Pro", "bootstrap-icons", "fontawesome", "feather" !important;
    }
    
    html[dir="ltr"] body {
        text-align: left !important;
        direction: ltr !important;
    }
    .text-end {
        text-align: right !important;
    }
    .text-start {
        text-align: left !important;
    }
    
    /* Header components overhaul for LTR */
    .header-content {
        display: flex !important;
        flex-direction: row !important; /* Standard LTR direction */
        justify-content: space-between !important;
        align-items: center !important;
        position: relative !important;
    }
    
    /* MOBILE/TABLET LTR: Actions Left, Logo Center, Hamburger Right */
    @media (max-width: 1199px) {
        .header-content {
            flex-direction: row-reverse !important; /* Mirroring for specific mobile layout */
        }
        .header-content .logo {
            position: absolute !important;
            left: 50% !important;
            transform: translateX(-50%) !important;
            margin: 0 !important;
            z-index: 10;
        }
        .header-content .header-right-group {
            order: 1 !important; /* First in row-reverse = Left */
        }
        .header-content .mobile-hamburger-wrap {
            order: 4 !important; /* Last in row-reverse = Right */
        }
        .header-content .tmp-mainmenu-nav {
            display: none !important;
        }
    }

    /* DESKTOP LTR: Logo Left, Menu Center, Actions Right */
    @media (min-width: 1200px) {
        .header-content .logo {
            order: 1 !important;
            position: static !important;
            transform: none !important;
        }
        .header-content .tmp-mainmenu-nav {
            order: 2 !important;
            display: block !important;
            margin: 0 auto !important;
        }
        .header-content .header-right-group {
            order: 3 !important;
            margin-left: 0 !important;
        }
    }
    
    /* Navigation fix */
    .tmp-mainmenu-nav {
        direction: ltr !important;
    }
    .tmp-mainmenu-nav .mainmenu {
        justify-content: flex-start !important;
        flex-direction: row !important;
    }
    
    /* Footer fix */
    .footer-widget {
        text-align: left !important;
    }
    .footer-widget .ft-title::after {
        left: 0 !important;
        right: auto !important;
    }
    
    /* Buttons and Icons */
    .btn i, .btn span {
        margin-right: 8px !important;
        margin-left: 0 !important;
    }
    
    /* Hero section fix */
    .banner-content-two {
        text-align: left !important;
    }
    /* Hero section image flip for LTR */
    .banner-two-main-wrapper .banner-right-content .main-img > img,
    .benner-two-bg-red-img img,
    .logo-under-img img {
        transform: scaleX(-1) !important;
    }
    .banner-two-main-wrapper .banner-right-content .main-img .banner-big-text-1,
    .banner-two-main-wrapper .banner-right-content .main-img .banner-big-text-2 {
        white-space: nowrap !important;
        width: max-content !important;
    }
    <?php endif; ?>
    @keyframes pwaSlideDown {
        from { transform: translateY(-100%); }
        to { transform: translateY(0); }
    }
    #pwa-install-banner .banner-content {
        display: flex;
        align-items: center;
        gap: 10px;
        flex-grow: 1;
    }
    #pwa-install-banner .banner-actions {
        display: flex;
        gap: 8px;
        align-items: center;
    }
    #pwa-install-banner .btn-install {
        background: white !important;
        color: #5a8dee !important;
        border: none !important;
        padding: 5px 15px !important;
        border-radius: 5px !important;
        font-weight: bold !important;
        font-size: 13px !important;
        cursor: pointer !important;
        margin: 0 !important;
    }
    #pwa-install-banner .btn-close-banner {
        background: transparent !important;
        color: white !important;
        border: none !important;
        font-size: 24px !important;
        cursor: pointer !important;
        padding: 0 5px !important;
        line-height: 1 !important;
        margin: 0 !important;
    }



    /* Study Plan Estimator Styles */
    .estimator-section {
        background: linear-gradient(135deg, #1c222f 0%, #283144 100%);
        border-radius: 20px;
        padding: 60px 40px;
        margin-bottom: 80px;
        box-shadow: 0 10px 30px rgba(0,0,0,0.3);
        border: 1px solid #36445d;
    }
    .estimator-modal .modal-content {
        color: #e2e8f0;
    }
    .estimator-modal .form-label {
        color: #cbd5e0;
        font-size: 1.1rem;
        margin-bottom: 12px;
    }
    .range-slider {
        -webkit-appearance: none;
        width: 100%;
        height: 8px;
        border-radius: 5px;
        background: #36445d;
        outline: none;
        margin: 20px 0;
    }
    .range-slider::-webkit-slider-thumb {
        -webkit-appearance: none;
        appearance: none;
        width: 25px;
        height: 25px;
        border-radius: 50%;
        background: #5a8dee;
        cursor: pointer;
        box-shadow: 0 0 10px rgba(90, 141, 238, 0.5);
    }
    .day-checkbox-group {
        display: flex;
        flex-wrap: wrap;
        gap: 10px;
        justify-content: center;
        margin-top: 20px;
    }
    .day-checkbox-item {
        position: relative;
    }
    .day-checkbox-item input {
        position: absolute;
        opacity: 0;
        cursor: pointer;
        height: 0;
        width: 0;
    }
    .day-checkbox-item label {
        display: block;
        padding: 12px 25px;
        background: rgba(255, 255, 255, 0.03);
        border: 1px solid rgba(255, 255, 255, 0.1);
        border-radius: 12px;
        cursor: pointer;
        transition: all 0.3s ease;
        font-weight: 500;
        color: #8295ba;
        text-align: center;
    }
    .day-checkbox-item input:checked + label {
        background: rgba(90, 141, 238, 0.15);
        border-color: #5a8dee;
        color: #fff;
        box-shadow: 0 5px 15px rgba(90, 141, 238, 0.2);
    }
    .day-checkbox-item:hover label {
        border-color: rgba(255, 255, 255, 0.3);
    }
    /* Glass Button Style */
    .glass-btn {
        background: rgba(255, 255, 255, 0.1) !important;
        backdrop-filter: blur(10px);
        -webkit-backdrop-filter: blur(10px);
        border: 1px solid rgba(255, 255, 255, 0.2) !important;
        color: #fff !important;
        padding: 20px 50px !important;
        font-size: 1.5rem !important;
        font-weight: 600 !important;
        display: inline-flex !important;
        align-items: center !important;
        justify-content: center !important;
        transition: all 0.3s ease !important;
        box-shadow: 0 8px 32px 0 rgba(0, 0, 0, 0.3);
    }
    .glass-btn:hover {
        background: rgba(255, 255, 255, 0.2) !important;
        border-color: rgba(255, 255, 255, 0.4) !important;
        transform: translateY(-3px);
        box-shadow: 0 12px 40px 0 rgba(0, 0, 0, 0.4);
    }
    .glass-btn .btn-text {
        line-height: 1;
    }
    .admin-desktop-toggle {
        position: fixed;
        bottom: 20px;
        left: 20px;
        z-index: 9999;
        width: 50px;
        height: 50px;
        border-radius: 50%;
        background: #5a8dee;
        color: white;
        border: none;
        box-shadow: 0 4px 15px rgba(0,0,0,0.3);
        display: none; /* Hidden by default, shown via JS if mobile */
        align-items: center;
        justify-content: center;
        cursor: pointer;
        transition: all 0.3s ease;
    }
    .admin-desktop-toggle:hover {
        transform: scale(1.1);
        background: #4868f8;
    }
    @media (max-width: 1199px) {
        .admin-desktop-toggle.is-admin {
            display: flex;
        }
    }
    /* Styles for when Desktop Mode is active on mobile */
    body.admin-desktop-active .admin-desktop-toggle {
        display: flex !important;
        width: 80px;
        height: 80px;
        bottom: 30px;
        left: 30px;
        font-size: 1.8rem;
        background: #f56565; /* Red color to stand out */
    }
</style>

<body>
    <!-- PWA Install Banner -->
    <div id="pwa-install-banner">
        <div class="banner-content">
            <i class="fas fa-mobile-alt"></i>
            <span><?= __('pwa_install_text', 'نصب اپلیکیشن فارسی‌فهر برای دسترسی سریع‌تر') ?></span>
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
                                <img alt="Farsi Fahr" class="logo-dark" src="assets/images/logo/logoAsset%201.svg">
                                <img alt="Farsi Fahr" class="logo-white" src="assets/images/logo/logoAsset%201.svg">
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
                                    <a class="nav-link" href="#pricing">
                                        <i class="bi bi-crown me-1"></i><?= __('subscription') ?>
                                    </a>
                                </li>
                                <li class="nav-item">
                                    <a class="nav-link" href="#study-plan">
                                        <i class="bi bi-calendar-check me-1"></i><?= __('study_plan') ?>
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
                                    <button class="btn btn-dark btn-md text-white dropdown-toggle d-flex align-items-center gap-2" 
                                            type="button" 
                                            id="userDropdown" 
                                            data-bs-toggle="dropdown" 
                                            aria-expanded="false">
                                        <i class="fa-regular fa-user-circle"></i>
                                        <span class="d-none d-sm-inline"><?= $_SESSION['name'] ?></span>
                                    </button>
                                    <ul style="background-color: #212529 !important;" class="dropdown-menu bg-black text-light w-100  dropdown-menu-end" aria-labelledby="userDropdown">
                                        <li>
                                            <a class="dropdown-item bg-black text-light" href="admin">
                                                <i class="fa-regular fa-speedometer me-2"></i>
                                                داشبورد
                                            </a>
                                        </li>
                                        <li>
                                            <a class="dropdown-item bg-black text-light" href="admin/subscription.php">
                                                <i class="fa-regular fa-crown me-2"></i>
                                                اشتراک
                                            </a>
                                        </li>
                                        <li><hr class="dropdown-divider"></li>
                                        <li>
                                            <a class="dropdown-item bg-black text-danger" href="logout.php">
                                                <i class="fa-regular fa-arrow-right-from-bracket me-2"></i>
                                                خروج
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
                                    <a href="javascript:void(0)" class="btn btn-outline-light btn-sm nav-action-item" data-bs-toggle="modal"
                                        data-bs-target="#loginModal">
                                        <i class="fa-regular fa-arrow-right-to-bracket"></i>
                                        <span class="d-none d-sm-inline"><?= __('login') ?></span>
                                    </a>
                                    <a href="javascript:void(0)" class="btn btn-outline-primary btn-sm text-white nav-action-item" data-bs-toggle="modal"
                                        data-bs-target="#registerModal">
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
                    <li><a href="#pricing"><?= __('subscription') ?></a></li>
                    <li><a href="#study-plan"><?= __('study_plan') ?></a></li>
                    <li><a href="#contact"><?= __('contact_us') ?></a></li>
                    <li class="mt-4">
                        <?php if (is_logged_in()): ?>
                            <a href="admin" class="btn btn-primary w-100 text-white"><?= __('dashboard') ?></a>
                        <?php else: ?>
                            <div class="d-grid gap-2">
                                <a href="javascript:void(0)" class="btn btn-outline-light" data-bs-toggle="modal" data-bs-target="#loginModal"><?= __('login') ?></a>
                                <a href="javascript:void(0)" class="btn btn-primary text-white" style="background-color: #5a8dee;" data-bs-toggle="modal" data-bs-target="#registerModal"><?= __('register') ?></a>
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
                                    class="tmp-btn hover-icon-reverse radius-round" href="#pricing"> <span
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
        <div class="banner-shape-two"><img alt="" src="assets/images/banner/banner-shape-two.png"></div>
    </div>
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
                                        <div class="logo-img"><img alt="logo" src="assets/images/about/logo-1.svg">
                                        </div>
                                        <h3 class="card-title"><?= __('fastest_time_title') ?></h3>
                                    </div>
                                    <p class="card-para"><?= __('fastest_time_desc') ?></p>
                                </div>
                            </div>
                            <div class="col-lg-6 col-md-6 col-sm-6 col-12">
                                <div class="about-us-card tmponhover tmp-scroll-trigger tmp-fade-in animation-order-5">
                                    <div class="card-head">
                                        <div class="logo-img"><img alt="logo" src="assets/images/about/logo-2.svg">
                                        </div>
                                        <h3 class="card-title"><?= __('language_problem_title') ?></h3>
                                    </div>
                                    <p class="card-para"><?= __('language_problem_desc') ?></p>
                                </div>
                            </div>
                        </div>
                        <div
                            class="about-btn mt--40 tmp-scroll-trigger tmp-fade-in animation-order-6 tmp-scroll-trigger--offscreen">
                            <a class="tmp-btn hover-icon-reverse radius-round" href="#pricing"> <span
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
    <!-- Study Plan Section Start -->
    <section class="study-plan-area tmp-section-gapTop" id="study-plan">
        <div class="container">
            <div class="section-head text-center mb--50">
                <span class="subtitle" style="color: #5a8dee; font-weight: bold; margin-bottom: 15px; display: block;"><?= __('study_plan_subtitle') ?></span>
                <h2 class="title" style="color: #fff; font-size: 2.2rem; margin-bottom: 20px;">
                    <?= __('study_plan_new_title', 'دوست داری بدونی حدودا چند روزه امتحان میشی، فرم زیر رو رایگان پر کن تا بهت بگم.') ?>
                </h2>
            </div>

            <div class="step-form-container">
                <div class="progress-wrapper">
                    <div class="step-progress">
                        <div class="progress-bar-fill" id="stepProgressBar"></div>
                    </div>
                </div>

                <form id="studyPlanForm">
                    <!-- Step 1: German Level -->
                    <div class="form-step active" data-step="1">
                        <label class="form-label d-block text-center mb-4 fs-4"><?= __('german_level_question', 'سطح زبان آلمانی فعلی شما چقدر است؟') ?></label>
                        <div class="row g-3 justify-content-center" id="levelSelector">
                            <div class="col-6 col-md-4 col-lg-2">
                                <div class="level-select-item" data-level="none">
                                    <div class="fs-2 mb-1">❌</div>
                                    <div class="small"><?= __('no_knowledge', 'بدون دانش') ?></div>
                                </div>
                            </div>
                            <div class="col-6 col-md-4 col-lg-2">
                                <div class="level-select-item" data-level="a1">
                                    <div class="fs-2 mb-1">🇩🇪</div>
                                    <div>A1</div>
                                </div>
                            </div>
                            <div class="col-6 col-md-4 col-lg-2">
                                <div class="level-select-item" data-level="a2">
                                    <div class="fs-2 mb-1">🇩🇪</div>
                                    <div>A2</div>
                                </div>
                            </div>
                            <div class="col-6 col-md-4 col-lg-2">
                                <div class="level-select-item active" data-level="b1">
                                    <div class="fs-2 mb-1">🇩🇪</div>
                                    <div>B1</div>
                                </div>
                            </div>
                            <div class="col-6 col-md-4 col-lg-2">
                                <div class="level-select-item" data-level="b2">
                                    <div class="fs-2 mb-1">🇩🇪</div>
                                    <div>B2</div>
                                </div>
                            </div>
                            <div class="col-6 col-md-4 col-lg-2">
                                <div class="level-select-item" data-level="c1">
                                    <div class="fs-2 mb-1">🇩🇪</div>
                                    <div>C1</div>
                                </div>
                            </div>
                        </div>
                        <input type="hidden" name="german_level" id="input_level" value="b1">
                    </div>

                    <!-- Step 2: Previous Study -->
                    <div class="form-step" data-step="2">
                        <div class="row g-5">
                            <div class="col-md-6">
                                <label class="form-label fs-5 mb-4 d-block"><?= __('study_history_label', 'سابقه مطالعه سوالات (چند درصد؟)') ?></label>
                                <div class="d-flex align-items-center gap-3">
                                    <input type="range" class="range-slider flex-grow-1" name="previous_study_percent" id="input_percent" min="0" max="100" value="0">
                                    <span class="fs-4 fw-bold text-primary" id="percent_label">0%</span>
                                </div>
                                <small class="text-muted mt-2 d-block"><?= __('study_history_desc', 'چند درصد از کل سوالات را تا به حال مطالعه کرده‌اید؟') ?></small>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fs-5 mb-4 d-block"><?= __('daily_study_hours_question', 'روزانه چند ساعت مطالعه می‌کنید؟') ?></label>
                                <div class="d-flex align-items-center gap-3">
                                    <input type="range" class="range-slider flex-grow-1" name="daily_hours" id="input_hours" min="1" max="8" value="2">
                                    <span class="fs-4 fw-bold text-primary" id="hours_label">2 <?= __('hours', 'ساعت') ?></span>
                                </div>
                                <small class="text-muted mt-2 d-block"><?= __('daily_study_hours_desc', 'زمان مفیدی که در هر روز برای یادگیری اختصاص می‌دهید.') ?></small>
                            </div>
                        </div>
                    </div>

                    <!-- Step 3: Study Days -->
                    <div class="form-step" data-step="3">
                        <label class="form-label d-block text-center mb-4 fs-4"><?= __('study_days_question', 'چه روزهایی در هفته مطالعه می‌کنید؟') ?></label>
                        <div class="day-checkbox-group justify-content-center">
                            <div class="day-checkbox-item">
                                <input type="checkbox" name="study_days[]" value="Sat" id="day_sat" checked>
                                <label for="day_sat"><?= __('sat', 'شنبه') ?></label>
                            </div>
                            <div class="day-checkbox-item">
                                <input type="checkbox" name="study_days[]" value="Sun" id="day_sun" checked>
                                <label for="day_sun"><?= __('sun', 'یکشنبه') ?></label>
                            </div>
                            <div class="day-checkbox-item">
                                <input type="checkbox" name="study_days[]" value="Mon" id="day_mon" checked>
                                <label for="day_mon"><?= __('mon', 'دوشنبه') ?></label>
                            </div>
                            <div class="day-checkbox-item">
                                <input type="checkbox" name="study_days[]" value="Tue" id="day_tue" checked>
                                <label for="day_tue"><?= __('tue', 'سه‌شنبه') ?></label>
                            </div>
                            <div class="day-checkbox-item">
                                <input type="checkbox" name="study_days[]" value="Wed" id="day_wed" checked>
                                <label for="day_wed"><?= __('wed', 'چهارشنبه') ?></label>
                            </div>
                            <div class="day-checkbox-item">
                                <input type="checkbox" name="study_days[]" value="Thu" id="day_thu" checked>
                                <label for="day_thu"><?= __('thu', 'پنجشنبه') ?></label>
                            </div>
                            <div class="day-checkbox-item">
                                <input type="checkbox" name="study_days[]" value="Fri" id="day_fri" checked>
                                <label for="day_fri"><?= __('fri', 'جمعه') ?></label>
                            </div>
                        </div>
                    </div>

                    <!-- Step 4: Summary -->
                    <div class="form-step" data-step="4">
                        <h4 class="text-center mb-4"><?= __('final_confirmation', 'تایید نهایی اطلاعات') ?></h4>
                        <div class="summary-container mb-4">
                            <div class="summary-item">
                                <span class="summary-label"><?= __('german_level_label', 'سطح زبان آلمانی:') ?></span>
                                <span class="summary-value" id="sum_level">-</span>
                            </div>
                            <div class="summary-item">
                                <span class="summary-label"><?= __('current_progress_label', 'پیشرفت فعلی:') ?></span>
                                <span class="summary-value" id="sum_percent">-</span>
                            </div>
                            <div class="summary-item">
                                <span class="summary-label"><?= __('daily_study_hours_label', 'ساعت مطالعه روزانه:') ?></span>
                                <span class="summary-value" id="sum_hours">-</span>
                            </div>
                            <div class="summary-item">
                                <span class="summary-label"><?= __('study_days_label', 'روزهای مطالعه:') ?></span>
                                <span class="summary-value" id="sum_days">-</span>
                            </div>
                        </div>

                        <?php if (!is_logged_in()): ?>
                        <!-- Registration Fields for Guest -->
                        <div class="row g-3 mt-4 border-top border-secondary pt-4">
                            <div class="col-12"><p class="text-warning small text-center"><?= __('guest_save_notice', 'برای ذخیره برنامه و دریافت آن در ایمیل، لطفا اطلاعات زیر را تکمیل کنید:') ?></p></div>
                            <div class="col-md-6">
                                <input type="text" class="form-control" name="name" placeholder="<?= __('your_name', 'نام شما') ?>" style="background: rgba(255,255,255,0.05); border: 1px solid rgba(255,255,255,0.1); color: #fff;">
                            </div>
                            <div class="col-md-6">
                                <input type="email" class="form-control" name="email" placeholder="<?= __('your_email', 'ایمیل شما') ?>" required style="background: rgba(255,255,255,0.05); border: 1px solid rgba(255,255,255,0.1); color: #fff;">
                            </div>
                            <div class="col-12">
                                <input type="password" class="form-control" name="password" placeholder="<?= __('choose_password', 'یک رمز عبور انتخاب کنید') ?>" required style="background: rgba(255,255,255,0.05); border: 1px solid rgba(255,255,255,0.1); color: #fff;">
                            </div>
                        </div>
                        <?php endif; ?>
                    </div>

                    <!-- Live Result Display -->
                    <div class="live-result-container text-center">
                        <div class="days-text"><?= __('estimated_time_label', 'زمان تخمینی تا امتحان:') ?></div>
                        <div class="big-days-label"><span id="result_days">--</span> <small class="fs-3" style="-webkit-text-fill-color: #8295ba;"><?= __('days', 'روز') ?></small></div>
                    </div>

                    <!-- Navigation Buttons -->
                    <div class="d-flex justify-content-between mt-5">
                        <button type="button" class="btn btn-outline-secondary px-5" id="prevStep" style="display: none; border-radius: 50px; height: 50px;"><?= __('previous', 'قبلی') ?></button>
                        <button type="button" class="btn btn-primary px-5 ms-auto" id="nextStep" style="border-radius: 50px; height: 50px;"><?= __('next', 'بعدی') ?></button>
                        <button type="button" class="btn btn-success px-5 ms-auto" id="btnSaveStudyPlan" style="display: none; border-radius: 50px; height: 50px;"><?= __('save_and_get_plan', 'ذخیره و دریافت برنامه') ?></button>
                    </div>
                </form>
            </div>
        </div>
    </section>
    <!-- Study Plan Section End -->

    <!-- Tpm My Price plan Start -->
<section class="our-price-plan-area tmp-section-gapTop" id="pricing">
    <div class="container">
        <div class="section-head">
            <div class="section-sub-title center-title tmp-scroll-trigger tmp-fade-in animation-order-1">
                <span class="subtitle"><?= __('subscription_table', 'جدول اشتراک‌ها') ?></span>
            </div>
            <h2 class="title split-collab tmp-scroll-trigger tmp-fade-in animation-order-2"><?= __('choose_new_subscription', 'انتخاب اشتراک جدید') ?></h2>
            <p><?= __('subscription_description', 'طرحی را انتخاب کنید که به بهترین وجه با نیازهای شما مطابقت داشته باشد.') ?></p>
        </div>
        
        <div class="row justify-content-center mt-5">
            <?php
            // در صفحه اصلی، کاربر لزوما لاگین نیست
            $is_user_logged_in = is_logged_in();
            $user_sub = false;
            $pending_sub = false;
            $user_plan_status = 'free';

            if ($is_user_logged_in) {
                $user_sub = get_user_active_subscription($_SESSION['user_id'], $pdo);
                $pending_sub = get_user_pending_subscription($_SESSION['user_id'], $pdo);
                if ($user_sub !== false && $user_sub !== null) {
                    if ($user_sub['plan_slug'] !== 'free') {
                        $user_plan_status = 'active';
                    }
                }
            }

            $stmt = $pdo->prepare("SELECT * FROM subscription_plans WHERE is_active = 1 ORDER BY sort_order ASC");
            $stmt->execute();
            $plans = $stmt->fetchAll(PDO::FETCH_ASSOC);

            foreach ($plans as $plan):
              $plan_class = ($plan['slug'] == 'vip') ? 'text-primary' : '';
              $card_class = ($plan['slug'] == 'vip') ? 'border-primary' : '';
              ?>
              <div class="col-lg-6 col-12 mb-4">
                <div class="card <?= $card_class ?> h-100 shadow-sm" style="border-radius: 15px; border: 1px solid #36445d; background-color: #283144; box-shadow: 0 4px 24px 0 rgba(0, 0, 0, 0.2);">
                  <div class="card-header bg-label-primary py-3" style="background-color: rgba(90, 141, 238, 0.1); border-radius: 15px 15px 0 0; padding: 1.5rem;">
                    <h4 class="<?= $plan_class ?> mb-0 fw-bold" style="color: #5a8dee !important; margin: 0; text-align: center; font-size: 1.8rem;"><?= htmlspecialchars($plan['name']) ?></h4>
                  </div>
                  <div class="card-body pt-4" style="padding: 2rem;">
                    <p class="mb-4 text-center" style="font-size: 1.2rem; color: #a1b0cb;"><?= htmlspecialchars($plan['description']) ?></p>
                    
                    <ul class="list-unstyled mb-4" style="line-height: 2.2; margin-bottom: 2rem; padding-right: 0; font-size: 1.15rem; color: #d8deea;">
                      <?php 
                      $plan_features = get_plan_features($plan['slug']);
                      foreach ($plan_features as $feature):
                          $is_negative = (str_contains($feature, 'عدم') || str_contains($feature, 'بدون') || str_contains($feature, 'محدودیت') || str_contains($feature, 'بالاتر از ۲۰۰'));
                          // Note: We'll keep the check icon for all unless it's a known negative feature, 
                          // but the database features are usually positive.
                      ?>
                              <li class="mb-2"><i class="fa fa-check-circle text-success me-2 fs-4" style="color: #71dd37;"></i> <?= htmlspecialchars($feature) ?></li>
                      <?php 
                      endforeach;
                      ?>
                    </ul>

                    <?php if ($plan['slug'] == 'free'): ?>
                      <div class="text-center py-4 rounded" style="background-color: #1c222f; padding: 2rem; border-radius: 10px; border: 1px solid #36445d;">
                        <h2 class="fw-bold mb-1" style="font-size: 2.5rem; color: #d8deea;">رایگان</h2>
                        <p class="small mb-4" style="color: #8295ba; font-size: 1.1rem;">مناسب برای آشنایی اولیه</p>
                        <?php if ($is_user_logged_in): ?>
                            <?php if ($user_plan_status === 'active'): ?>
                                <button class="btn w-100 fs-5" style="background-color: #36445d; color: #8295ba; padding: 12px; border-radius: 8px; font-weight: bold; border: none;" disabled>غیرقابل استفاده</button>
                            <?php else: ?>
                                <a href="admin/subscription.php" class="btn w-100 fs-5" style="background-color: #8592a3; color: white; padding: 12px; border-radius: 8px; font-weight: bold; text-decoration: none; display: block;">پلن فعلی شما</a>
                            <?php endif; ?>
                        <?php else: ?>
                            <button class="btn w-100 fs-5" data-bs-toggle="modal" data-bs-target="#loginModal" style="background-color: #5a8dee; color: white; padding: 12px; border-radius: 8px; font-weight: bold; border: none; cursor: pointer;">شروع رایگان</button>
                        <?php endif; ?>
                      </div>
                    <?php else: ?>
                      <div class="vip-durations">
                        <?php 
                        $durations = [
                          ['key' => 'price_2_weeks', 'label' => '۲ هفته (۱۴ روز)', 'duration' => '2_weeks', 'color' => 'primary'],
                          ['key' => 'price_1_month', 'label' => '۱ ماه (۳۰ روز)', 'duration' => '1_month', 'color' => 'primary'],
                          ['key' => 'price_3_months', 'label' => '۳ ماه (۹۰ روز)', 'duration' => '3_months', 'color' => 'success', 'special' => true],
                          ['key' => 'price_6_months', 'label' => '۶ ماه (۱۸۰ روز)', 'duration' => '6_months', 'color' => 'info'],
                          ['key' => 'price_1_year', 'label' => '۱ سال (۳۶۵ روز)', 'duration' => '1_year', 'color' => 'warning', 'star' => true]
                        ];

                        foreach ($durations as $dur):
                          if (!isset($plan[$dur['key']]) || $plan[$dur['key']] <= 0) continue;
                          
                          $duration_days = get_duration_days($dur['duration']);
                          $is_active_dur = ($user_sub && $user_sub['plan_id'] == $plan['id'] && $user_sub['duration_days'] == $duration_days);
                          $is_pending_dur = ($pending_sub && $pending_sub['plan_id'] == $plan['id'] && $pending_sub['duration_days'] == $duration_days);

                          $discount = 0;
                          if ($dur['key'] != 'price_1_month' && $plan['price_1_month'] > 0) {
                            $days = $duration_days;
                            $saving = ($plan['price_1_month'] / 30 * $days) - $plan[$dur['key']];
                            if ($saving > 0) {
                              $discount = round(($saving / ($plan['price_1_month'] / 30 * $days)) * 100);
                            }
                          }

                          $row_style = 'transition: all 0.2s ease; border-left: 4px solid transparent; background-color: #1c222f; cursor: pointer; display: flex; justify-content: space-between; align-items: center;';
                          $badge_style = 'font-size: 12px; padding: 5px 10px; border-radius: 4px; background-color: #ff3e1d; color: white; margin-right: 10px;';
                          
                          if ($is_active_dur) {
                              $row_style .= ' border-color: #71dd37; background-color: rgba(113, 221, 55, 0.1);';
                          } elseif ($is_pending_dur) {
                              $row_style .= ' border-color: #ffab00; background-color: rgba(255, 171, 0, 0.1);';
                          } elseif (isset($dur['special']) || isset($dur['star'])) {
                              $row_style .= ' background-color: rgba(90, 141, 238, 0.08); border-color: #5a8dee;';
                          } else {
                              $row_style .= ' border-color: #36445d;';
                          }
                        ?>
                        <div class="pricing-option-row p-3 mb-3 border rounded shadow-sm" style="<?= $row_style ?>" onmouseover="this.style.transform='translateY(-2px)'; this.style.boxShadow='0 5px 15px rgba(0,0,0,0.2)';" onmouseout="this.style.transform='translateY(0)'; this.style.boxShadow='0 2px 5px rgba(0,0,0,0.1)';">
                          <div class="d-flex justify-content-between align-items-center w-100" style="flex-wrap: wrap;">
                            <div class="flex-grow-1" style="flex: 1; min-width: 150px;">
                              <h6 class="mb-1 fw-bold fs-5" style="color: #d8deea; display: inline-block; margin: 0;"><?= $dur['label'] ?></h6>
                              <?php if ($discount > 0): ?>
                                <span class="badge" style="<?= $badge_style ?>"><?= $discount ?>% تخفیف</span>
                              <?php endif; ?>
                            </div>
                            <div class="text-end me-3" style="margin-right: 15px;">
                              <div class="fw-bold text-primary fs-4" style="color: #5a8dee !important; line-height: 1;"><?= number_format($plan[$dur['key']]) ?> <small style="font-size: 0.7em; color: #8295ba;">یورو</small></div>
                              <?php 
                              $euro_rate = defined('EURO_TO_TOMAN_RATE') ? EURO_TO_TOMAN_RATE : 75000;
                              $toman_price = $plan[$dur['key']] * $euro_rate;
                              ?>
                              <div style="font-size: 0.85rem; color: #8295ba; margin-top: 4px;">معادل <?= number_format($toman_price) ?> <small>تومان</small></div>
                            </div>
                            
                            <div style="margin-top: 10px; text-align: left; min-width: 100px;">
                              <?php if ($is_active_dur): ?>
                                <button type="button" class="btn fs-5 py-2 px-4" style="background-color: #71dd37; color: white; border: none; border-radius: 5px; font-weight: bold;" disabled>
                                   فعال است
                                </button>
                              <?php elseif ($is_pending_dur): ?>
                                <button type="button" class="btn fs-5 py-2 px-4" style="background-color: #ffab00; color: white; border: none; border-radius: 5px; font-weight: bold;" disabled>
                                   در حال بررسی
                                </button>
                              <?php elseif ($is_user_logged_in && $user_plan_status === 'active'): ?>
                                <button type="button" class="btn fs-5 py-2 px-4" style="background-color: #8592a3; color: white; border: none; border-radius: 5px; font-weight: bold;" disabled title="شما یک اشتراک فعال دارید">
                                   خرید
                                </button>
                              <?php else: ?>
                                <?php if ($is_user_logged_in): ?>
                                  <a href="admin/subscription.php" class="btn fs-5 py-2 px-5" style="background-color: #5a8dee; color: white; border: none; border-radius: 5px; font-weight: bold; text-decoration: none;">
                                     خرید
                                  </a>
                                <?php else: ?>
                                  <button type="button" data-bs-toggle="modal" data-bs-target="#loginModal" class="btn fs-5 py-2 px-5" style="background-color: #5a8dee; color: white; border: none; border-radius: 5px; font-weight: bold; cursor: pointer;">
                                     خرید
                                  </button>
                                <?php endif; ?>
                              <?php endif; ?>
                            </div>
                          </div>
                        </div>
                        <?php endforeach; ?>
                      </div>
                    <?php endif; ?>
                  </div>
                </div>
              </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>
    <!-- Tpm My Price plan End -->

    <!-- Blog Section Temporarily Hidden 
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
    -->
    <footer id="contact" class="footer-area footer-style-two-wrapper bg-color-footer bg_images tmp-section-gap">
        <div class="container">
            <div class="footer-main footer-style-two">
                <div class="row g-5">
                    <div class="col-lg-3 col-md-4 col-sm-6">
                        <div class="single-footer-wrapper border-right mr--20">
                            <div class="logo"><a href="<?= SITE_URL ?>"> <img alt="Farsi - Fahr"
                                        src="assets/images/logo/logoAsset%201.svg"> </a></div>
                            <p class="description"><?= FOOTER_DESCRIPTION ?></p>
                            <div class="social-link footer"><a target="_blank" href="<?= INSTAGRAM_URL ?>"><i class="fa-brands fa-instagram"></i></a> <a target="_blank" href="<?= TELEGRAM_CHANNEL_URL ?>"><i class="fa-brands fa-telegram"></i></a></div>
                        </div>
                    </div>
                    <div class="col-lg-2 col-md-4 col-sm-6">
                        <div class="quick-link-wrap">
                            <h5 class="ft-title"><?= __('quick_links', 'لینک سریع') ?></h5>
                            <ul
                                class="ft-link tmp-scroll-trigger animation-order-1 tmp-link-animation tmp-scroll-trigger--offscreen">
                                <li><a href="#about"><?= __('about_us', 'درباره ما') ?></a></li>
                                <li><a href="#pricing"><?= __('subscription', 'اشتراک') ?></a></li>
                                <li><a href="#contact"><?= __('contact_us', 'تماس با ما') ?></a></li>
                            </ul>
                        </div>
                    </div>
                    <div class="col-lg-3 col-md-4 col-sm-6">
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
                        <p class="copy-right-para"><?= COPYRIGHT_TEXT ?>
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

                        <div class="mb-3 d-flex justify-content-center">
                            <div class="g-recaptcha" data-sitekey="<?php echo RECAPTCHA_SITE_KEY; ?>"></div>
                        </div>

                        <div class="mb-3 form-check">
                            <input type="checkbox" class="form-check-input" name="remember" id="remember">
                            <label class="form-check-label" for="remember">مرا به خاطر بسپار</label>
                        </div>

                        <div class="d-grid gap-2">
                            <button type="submit" class="btn btn-primary">
                                <i class="bi bi-box-arrow-in-left"></i> ورود
                            </button>
                            <div class="text-center text-muted small mt-2">ورود سریع با گوگل یا ثبت نام سریع با گوگل</div>
                            <button type="button" class="btn btn-outline-danger d-flex align-items-center justify-content-center gap-2" onclick="googleLogin()" style="border: 2px solid #f56565; color: #fff; background: rgba(245, 101, 101, 0.1);">
                                <img src="https://www.gstatic.com/firebasejs/ui/2.0.0/images/auth/google.svg" width="20">
                                ورود سریع با گوگل
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

                        <div class="mb-3 d-flex justify-content-center">
                            <div class="g-recaptcha" data-sitekey="<?php echo RECAPTCHA_SITE_KEY; ?>"></div>
                        </div>

                        <div class="d-grid gap-2">
                            <button type="submit" class="btn btn-success">
                                <i class="bi bi-person-plus"></i> ثبت نام
                            </button>

                            <div class="text-center text-muted small mt-2">ورود سریع با گوگل یا ثبت نام سریع با گوگل</div>
                            <button type="button" class="btn btn-outline-danger d-flex align-items-center justify-content-center gap-2" onclick="googleLogin()" style="border: 2px solid #f56565; color: #fff; background: rgba(245, 101, 101, 0.1);">
                                <img src="https://www.gstatic.com/firebasejs/ui/2.0.0/images/auth/google.svg" width="20">
                                ثبت نام سریع با گوگل
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

    <!-- Study Plan Script -->
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        const studyPlanForm = document.getElementById('studyPlanForm');
        if (!studyPlanForm) return;

        let currentStep = 1;
        const totalSteps = 4;
        
        const formSteps = document.querySelectorAll('.form-step');
        const progressBar = document.getElementById('stepProgressBar');
        const btnPrev = document.getElementById('prevStep');
        const btnNext = document.getElementById('nextStep');
        const btnSave = document.getElementById('btnSaveStudyPlan');
        
        const levelItems = document.querySelectorAll('.level-select-item');
        const inputLevel = document.getElementById('input_level');
        const inputPercent = document.getElementById('input_percent');
        const percentLabel = document.getElementById('percent_label');
        const inputHours = document.getElementById('input_hours');
        const hoursLabel = document.getElementById('hours_label');
        const dayCheckboxes = document.querySelectorAll('input[name="study_days[]"]');
        const resultDays = document.getElementById('result_days');
        
        // Summary elements
        const sumLevel = document.getElementById('sum_level');
        const sumPercent = document.getElementById('sum_percent');
        const sumHours = document.getElementById('sum_hours');
        const sumDays = document.getElementById('sum_days');

        const dayMap = {
            'Sat': 'شنبه', 'Sun': 'یکشنبه', 'Mon': 'دوشنبه',
            'Tue': 'سه‌شنبه', 'Wed': 'چهارشنبه', 'Thu': 'پنجشنبه', 'Fri': 'جمعه'
        };

        const levelMap = {
            'none': 'بدون دانش', 'a1': 'A1', 'a2': 'A2',
            'b1': 'B1', 'b2': 'B2', 'c1': 'C1'
        };

        function updateUI() {
            // Update steps visibility
            formSteps.forEach(step => {
                step.classList.toggle('active', parseInt(step.dataset.step) === currentStep);
            });

            // Update Progress Bar
            const progress = (currentStep / totalSteps) * 100;
            progressBar.style.width = progress + '%';

            // Navigation buttons
            btnPrev.style.display = currentStep > 1 ? 'block' : 'none';
            
            if (currentStep === totalSteps) {
                btnNext.style.display = 'none';
                btnSave.style.display = 'block';
                updateSummary();
            } else {
                btnNext.style.display = 'block';
                btnSave.style.display = 'none';
            }
        }

        function updateSummary() {
            sumLevel.textContent = levelMap[inputLevel.value] || '-';
            sumPercent.textContent = inputPercent.value + '%';
            sumHours.textContent = inputHours.value + ' ساعت';
            
            const selectedDays = Array.from(dayCheckboxes)
                .filter(cb => cb.checked)
                .map(cb => dayMap[cb.value])
                .join('، ');
            sumDays.textContent = selectedDays || 'هیچ روزی انتخاب نشده';
        }

        function updateEstimation() {
            const level = inputLevel.value;
            const percent = parseInt(inputPercent.value);
            const hours = parseInt(inputHours.value);
            const studyDaysCount = Array.from(dayCheckboxes).filter(cb => cb.checked).length;

            percentLabel.textContent = percent + '%';
            hoursLabel.textContent = (hours >= 8 ? 'بالای 8' : hours) + ' ساعت';

            if (studyDaysCount === 0) {
                resultDays.textContent = '--';
                return;
            }

            $.ajax({
                url: 'incloud/study_plan_handler.php',
                type: 'POST',
                data: {
                    action: 'calculate',
                    german_level: level,
                    previous_study_percent: percent,
                    daily_hours: hours,
                    study_days_count: studyDaysCount
                },
                success: function(response) {
                    if (response.success) {
                        // Animate number change
                        const start = parseInt(resultDays.textContent) || 0;
                        const end = parseInt(response.calendar_days);
                        animateValue(resultDays, start, end, 500);
                    }
                }
            });
        }

        function animateValue(obj, start, end, duration) {
            let startTimestamp = null;
            const step = (timestamp) => {
                if (!startTimestamp) startTimestamp = timestamp;
                const progress = Math.min((timestamp - startTimestamp) / duration, 1);
                obj.innerHTML = Math.floor(progress * (end - start) + start);
                if (progress < 1) {
                    window.requestAnimationFrame(step);
                }
            };
            window.requestAnimationFrame(step);
        }

        // Navigation Events
        btnNext.addEventListener('click', () => {
            if (currentStep < totalSteps) {
                currentStep++;
                updateUI();
            }
        });

        btnPrev.addEventListener('click', () => {
            if (currentStep > 1) {
                currentStep--;
                updateUI();
            }
        });

        levelItems.forEach(item => {
            item.addEventListener('click', function() {
                levelItems.forEach(i => i.classList.remove('active'));
                this.classList.add('active');
                inputLevel.value = this.getAttribute('data-level');
                updateEstimation();
                // Auto advance to next step after selection for better UX
                setTimeout(() => { if(currentStep === 1) { currentStep++; updateUI(); } }, 300);
            });
        });

        inputPercent.addEventListener('input', updateEstimation);
        inputHours.addEventListener('input', updateEstimation);
        dayCheckboxes.forEach(cb => cb.addEventListener('change', updateEstimation));

        // Load existing plan if exists
        function loadExistingPlan() {
            $.ajax({
                url: 'incloud/study_plan_handler.php',
                type: 'POST',
                data: { action: 'get_user_plan' },
                success: function(response) {
                    if (response.success && response.plan) {
                        const plan = response.plan;
                        inputLevel.value = plan.german_level;
                        levelItems.forEach(i => {
                            i.classList.toggle('active', i.getAttribute('data-level') === plan.german_level);
                        });
                        inputPercent.value = plan.previous_study_percent;
                        inputHours.value = plan.daily_hours;
                        
                        const days = plan.study_days.split(',');
                        dayCheckboxes.forEach(cb => {
                            cb.checked = days.includes(cb.value);
                        });
                        
                        updateEstimation();
                    }
                }
            });
        }

        loadExistingPlan();
        updateUI();

        // Save Logic
        btnSave.addEventListener('click', function() {
            const formData = new FormData(studyPlanForm);
            formData.append('action', 'save');
            
            const selectedDays = Array.from(dayCheckboxes)
                .filter(cb => cb.checked)
                .map(cb => cb.value)
                .join(',');
            formData.append('study_days', selectedDays);

            if (selectedDays.length === 0) {
                Swal.fire({ icon: 'warning', title: 'توجه', text: 'لطفا حداقل یک روز در هفته را انتخاب کنید' });
                return;
            }

            Swal.fire({ title: 'در حال پردازش...', allowOutsideClick: false, didOpen: () => { Swal.showLoading(); } });

            $.ajax({
                url: 'incloud/study_plan_handler.php',
                type: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                success: function(response) {
                    if (response.success) {
                        Swal.fire({
                            icon: 'success',
                            title: 'موفقیت‌آمیز',
                            text: response.message,
                            confirmButtonText: 'ورود به پنل کاربری'
                        }).then(() => {
                            window.location.href = response.redirect;
                        });
                    } else {
                        Swal.fire({ icon: 'error', title: 'خطا', text: response.message });
                    }
                },
                error: function() {
                    Swal.fire({ icon: 'error', title: 'خطا', text: 'خطا در ارتباط با سرور' });
                }
            });
        });
    });
    </script>
        // Delete Logic
        const btnDelete = document.getElementById('btnDeleteStudyPlan');
        if (btnDelete) {
            btnDelete.addEventListener('click', function() {
                Swal.fire({
                    title: 'حذف برنامه مطالعه',
                    text: 'آیا از حذف برنامه مطالعه خود اطمینان دارید؟ این عمل غیرقابل بازگشت است.',
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonText: 'بله، حذف شود',
                    cancelButtonText: 'انصراف',
                    customClass: { confirmButton: 'btn btn-danger me-3', cancelButton: 'btn btn-label-secondary' },
                    buttonsStyling: false
                }).then((result) => {
                    if (result.isConfirmed) {
                        $.ajax({
                            url: 'incloud/study_plan_handler.php',
                            type: 'POST',
                            data: { action: 'delete' },
                            success: function(response) {
                                if (response.success) {
                                    Swal.fire({ icon: 'success', title: 'حذف شد', text: response.message }).then(() => {
                                        location.reload();
                                    });
                                } else {
                                    Swal.fire({ icon: 'error', title: 'خطا', text: response.message });
                                }
                            }
                        });
                    }
                });
            });
        }
    });
    </script>

    <?php if (isset($_COOKIE['concurrent_login']) && $_COOKIE['concurrent_login'] === '1'): ?>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            Swal.fire({
                icon: 'warning',
                title: 'خروج از حساب',
                text: 'شما در دستگاه یا مرورگر دیگری وارد حساب خود شدید. بنابراین از این نشست خارج شدید.',
                confirmButtonText: 'متوجه شدم'
            });
            document.cookie = 'concurrent_login=; expires=Thu, 01 Jan 1970 00:00:00 UTC; path=/;';
        });
    </script>
    <?php endif; ?>
    <script>
        // Service Worker Registration
        if ('serviceWorker' in navigator) {
            window.addEventListener('load', () => {
                navigator.serviceWorker.register('/sw.js?v=4')
                    .then(reg => console.log('Service Worker registered'))
                    .catch(err => console.log('Service Worker registration failed: ', err));
            });
        }

        // PWA Install Prompt Logic
        let deferredPrompt;
        const isIOS = /iPad|iPhone|iPod/.test(navigator.userAgent) && !window.MSStream;
        const isStandalone = window.matchMedia('(display-mode: standalone)').matches || window.navigator.standalone;

        window.addEventListener('beforeinstallprompt', (e) => {
            e.preventDefault();
            deferredPrompt = e;
            
            if (!sessionStorage.getItem('pwaPromptShown')) {
                showInstallPrompt('android');
                sessionStorage.setItem('pwaPromptShown', 'true');
            }
        });

        document.addEventListener('DOMContentLoaded', function() {
            if (isIOS && !isStandalone && !sessionStorage.getItem('pwaPromptShown')) {
                showInstallPrompt('ios');
                sessionStorage.setItem('pwaPromptShown', 'true');
            }
        });

        function showInstallPrompt(platform) {
            const banner = document.getElementById('pwa-install-banner');
            if (!banner) return;

            banner.style.display = 'flex';

            document.getElementById('btn-pwa-close').addEventListener('click', () => {
                banner.style.display = 'none';
            });

            document.getElementById('btn-pwa-install').addEventListener('click', () => {
                banner.style.display = 'none';
                if (platform === 'android' && deferredPrompt) {
                    deferredPrompt.prompt();
                    deferredPrompt.userChoice.then((choiceResult) => {
                        deferredPrompt = null;
                    });
                } else if (platform === 'ios') {
                    Swal.fire({
                        title: '<?= __('install_on_ios', 'نصب در آیفون') ?>',
                        html: `
                            <div class="text-<?= get_lang_dir() === 'rtl' ? 'end' : 'start' ?>" style="direction: <?= get_lang_dir() ?>;">
                                <p><?= __('ios_install_instruction', 'برای نصب اپلیکیشن در آیفون، مراحل زیر را دنبال کنید:') ?></p>
                                <ol class="ps-3">
                                    <li><?= __('ios_step_1', 'در نوار پایین مرورگر دکمه <b>Share</b> <i class="fas fa-share-square"></i> را بزنید.') ?></li>
                                    <li><?= __('ios_step_2', 'در منوی باز شده، گزینه <b>Add to Home Screen</b> را انتخاب کنید.') ?></li>
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
                }
            });
        }
    </script>
<script>
    $(document).ready(function() {
        // Odometer initialization
        function triggerOdometer() {
            $('.odometer').each(function() {
                const element = this;
                const count = $(element).attr('data-count');
                const rect = element.getBoundingClientRect();
                const isVisible = rect.top >= 0 && rect.bottom <= (window.innerHeight || document.documentElement.clientHeight);

                if (isVisible && !$(element).hasClass('odometer-triggered')) {
                    $(element).html(count);
                    $(element).addClass('odometer-triggered');
                }
            });
        }

        // Run on load and scroll
        triggerOdometer();
        $(window).on('scroll', triggerOdometer);
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
                // Also handle input placeholders and values
                if (node.placeholder) node.placeholder = toLatin(node.placeholder);
                if (node.value && (node.tagName === 'INPUT' || node.tagName === 'TEXTAREA')) {
                   // node.value = toLatin(node.value); // Usually we don't want to auto-convert user input as they type, but maybe for display
                }
            }
        }

        // Run once on load
        const runConversion = () => convertDigits(document.body);
        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', runConversion);
        } else {
            runConversion();
        }
        
        // Observe for changes (like Odometer updates, dynamic modals, etc)
        const observer = new MutationObserver((mutations) => {
            mutations.forEach((mutation) => {
                if (mutation.type === 'childList') {
                    mutation.addedNodes.forEach((node) => convertDigits(node));
                } else if (mutation.type === 'characterData') {
                    const original = mutation.target.nodeValue;
                    const converted = toLatin(original);
                    if (original !== converted) mutation.target.nodeValue = converted;
                }
            });
        });
        
        observer.observe(document.documentElement, {
            childList: true,
            subtree: true,
            characterData: true
        });
    })();
    <?php endif; ?>
</script>
    <?php if (is_admin()): ?>
    <!-- Admin Desktop Toggle -->
    <button class="admin-desktop-toggle is-admin" onclick="toggleDesktopMode()" title="تغییر به حالت دسکتاپ/موبایل">
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

</html >