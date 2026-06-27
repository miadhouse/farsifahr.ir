<?php 
require_once(__DIR__ . '/../../incloud/functions.php');
require_once(__DIR__ . '/../../incloud/categories.php');
require_once(__DIR__ . '/../../incloud/subscription-functions.php');

// بررسی ورود کاربر
if (!is_logged_in() || !validate_session($pdo)) {
    if (isset($GLOBALS['concurrent_login_flag']) && $GLOBALS['concurrent_login_flag'] === true) {
        header("Location: /index.php");
    } else {
        header("Location: /register.php");
    }
    exit();
}

require_once(__DIR__ . '/../../incloud/pending-request-handler.php');

// دریافت اطلاعات کاربر
$user_name = $_SESSION['name'] ?? 'کاربر';
$user_role = $_SESSION['role'] ?? 'user';
$user_email = $_SESSION['email'] ?? '';

// دریافت آخرین ورودها
$stmt = $pdo->prepare("
    SELECT * FROM user_logs 
    WHERE user_id = ? AND action = 'login' 
    ORDER BY created_at DESC 
    LIMIT 5
");
$stmt->execute([$_SESSION['user_id']]);
$recent_logins = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="<?= get_current_lang() ?>" class="dark-style layout-navbar-fixed layout-menu-fixed" dir="<?= get_lang_dir() ?>" data-theme="theme-default" data-assets-path="assets/" data-template="vertical-menu-template">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=no, minimum-scale=1.0, maximum-scale=1.0">

    <title><?= __('dashboard') ?></title>

    <meta name="description" content="">

    <!-- Favicon -->
    <link rel="icon" type="image/x-icon" href="https://v3dboy.ir/previews/html/frest/frest/assets/img/favicon/favicon.ico">

    <!-- Icons -->
    <link rel="stylesheet" href="assets/vendor/fonts/boxicons.css">
    <link rel="stylesheet" href="assets/vendor/fonts/fontawesome.css">
    <link rel="stylesheet" href="assets/vendor/fonts/flag-icons.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/gh/lipis/flag-icons@7.2.3/css/flag-icons.min.css" />
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">

    <!-- Core CSS -->
    <link rel="stylesheet" href="assets/vendor/css/rtl/core.css" class="template-customizer-core-css">
    <link rel="stylesheet" href="assets/vendor/css/rtl/theme-default-dark.css" class="template-customizer-theme-css">
    <link rel="stylesheet" href="assets/css/demo.css">
    <link rel="stylesheet" href="assets/vendor/css/rtl/rtl.css">

    <style>
        <?php if (get_lang_dir() === 'ltr'): ?>
        /* فونت و تنظیمات عمومی LTR - با اولویت بالاتر */
        *:not(i):not([class*="fa-"]):not([class*="bx-"]):not(.fi):not(.icon) {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif !important;
            direction: ltr !important;
            unicode-bidi: isolate !important;
        }
        <?php endif; ?>
    </style>

    <!-- Vendors CSS -->
    <link rel="stylesheet" href="/chat/widget.css?v=2.3">
    <link rel="stylesheet" href="assets/vendor/libs/perfect-scrollbar/perfect-scrollbar.css">
    <link rel="stylesheet" href="assets/vendor/libs/typeahead-js/typeahead.css">
    <link rel="stylesheet" href="assets/vendor/libs/apex-charts/apex-charts.css">
    <link rel="stylesheet" href="assets/vendor/libs/sweetalert2/sweetalert2.css">

    <!-- PWA Meta Tags -->
    <link rel="manifest" href="/manifest.json">
    <meta name="theme-color" content="#667eea">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
    <meta name="apple-mobile-web-app-title" content="farsifahr">
    <link rel="apple-touch-icon" href="/assets/imgT24Logo.png">

    <!-- Page CSS -->

    <!-- Helpers -->
    <script src="assets/vendor/js/helpers.js"></script>
<script src="assets/vendor/js/template-customizer.js"></script>
    <!-- <script src="assets/vendor/js/template-customizer.js"></script> -->
    <script src="assets/js/config.js"></script>
    <style>
    /* Page Loader */
    .page-loader {
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background-color: #283144; /* Dark background */
        z-index: 999999;
        display: flex;
        justify-content: center;
        align-items: center;
        transition: opacity 0.5s ease, visibility 0.5s ease;
    }
    .light-style .page-loader, html[data-theme="theme-default"]:not(.dark-style) .page-loader {
        background-color: #f5f5f9; /* Light background */
    }
    .page-loader .spinner {
        width: 50px;
        height: 50px;
        position: relative;
    }
    .page-loader .double-bounce1, .page-loader .double-bounce2 {
        width: 100%;
        height: 100%;
        border-radius: 50%;
        background-color: #5a8dee; /* Primary color */
        opacity: 0.6;
        position: absolute;
        top: 0;
        left: 0;
        animation: sk-bounce 2.0s infinite ease-in-out;
    }
    .page-loader .double-bounce2 {
        animation-delay: -1.0s;
    }
    @keyframes sk-bounce {
        0%, 100% { transform: scale(0.0) }
        50% { transform: scale(1.0) }
    }
    .page-loader.fade-out {
        opacity: 0;
        visibility: hidden;
    }
    </style>
  </head>

  <body>
    <!-- Preloader -->
    <div id="page-loader" class="page-loader">
      <div class="spinner">
        <div class="double-bounce1"></div>
        <div class="double-bounce2"></div>
      </div>
    </div>