<!DOCTYPE html>
<html lang="fa" class="dark-style layout-navbar-fixed layout-menu-fixed" dir="rtl" data-theme="theme-default" data-assets-path="assets/" data-template="vertical-menu-template">
  <?php require_once(__DIR__ . '/../../incloud/functions.php'); ?>
  <?php require_once(__DIR__ . '/../../incloud/categories.php'); ?>
<?php require_once(__DIR__ . '/../../incloud/subscription-functions.php'); ?>
<?php
// dashboard.php

// بررسی ورود کاربر
if (!is_logged_in() || !validate_session($pdo)) {
    header("Location: ../index.php");
    exit();
}

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
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=no, minimum-scale=1.0, maximum-scale=1.0">

    <title>داشبورد</title>

    <meta name="description" content="">

    <!-- Favicon -->
    <link rel="icon" type="image/x-icon" href="https://v3dboy.ir/previews/html/frest/frest/assets/img/favicon/favicon.ico">

    <!-- Icons -->
    <link rel="stylesheet" href="assets/vendor/fonts/boxicons.css">
    <link rel="stylesheet" href="assets/vendor/fonts/fontawesome.css">
    <link rel="stylesheet" href="assets/vendor/fonts/flag-icons.css">

    <!-- Core CSS -->
    <link rel="stylesheet" href="assets/vendor/css/rtl/core.css" class="template-customizer-core-css">
    <link rel="stylesheet" href="assets/vendor/css/rtl/theme-default-dark.css" class="template-customizer-theme-css">
    <link rel="stylesheet" href="assets/css/demo.css">
    <link rel="stylesheet" href="assets/vendor/css/rtl/rtl.css">

    <!-- Vendors CSS -->
    <link rel="stylesheet" href="assets/vendor/libs/perfect-scrollbar/perfect-scrollbar.css">
    <link rel="stylesheet" href="assets/vendor/libs/typeahead-js/typeahead.css">
    <link rel="stylesheet" href="assets/vendor/libs/apex-charts/apex-charts.css">
    <link rel="stylesheet" href="assets/vendor/libs/sweetalert2/sweetalert2.css">

    <!-- Page CSS -->

    <!-- Helpers -->
    <script src="assets/vendor/js/helpers.js"></script>
<script src="assets/vendor/js/template-customizer.js"></script>
    <!-- <script src="assets/vendor/js/template-customizer.js"></script> -->
    <script src="assets/js/config.js"></script>
  </head>

  <body>