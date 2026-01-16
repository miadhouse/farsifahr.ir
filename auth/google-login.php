<?php
// google-login.php
require_once '../incloud/config.php';
require_once '../vendor/autoload.php'; // Google API Client

// تنظیم Google Client
$client = new Google_Client();
$client->setClientId(GOOGLE_CLIENT_ID);
$client->setClientSecret(GOOGLE_CLIENT_SECRET);
$client->setRedirectUri(GOOGLE_REDIRECT_URI);
$client->addScope("email");
$client->addScope("profile");

// تولید URL احراز هویت
$auth_url = $client->createAuthUrl();

// هدایت به گوگل
header("Location: " . filter_var($auth_url, FILTER_SANITIZE_URL));
exit();