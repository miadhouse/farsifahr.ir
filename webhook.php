<?php

$secret = "fFehzxXvUKBd7utDESpmcal5L6KJWuyRTlIhWEWDmxELGcTbkhDZxZ0WPahlufvV";

$payload = file_get_contents('php://input');
$signature = $_SERVER['HTTP_X_HUB_SIGNATURE_256'] ?? '';

$hash = 'sha256=' . hash_hmac('sha256', $payload, $secret);

// مسیر فایل لاگ webhook
$log_file = "/home/Imiad.online/webhook.log";

// ثبت هر درخواست
file_put_contents($log_file, "-----------------------------\n", FILE_APPEND);
file_put_contents($log_file, "Webhook received at " . date('Y-m-d H:i:s') . "\n", FILE_APPEND);
file_put_contents($log_file, "Payload: $payload\n", FILE_APPEND);

if (!hash_equals($hash, $signature)) {
    file_put_contents($log_file, "❌ Invalid signature\n", FILE_APPEND);
    http_response_code(403);
    exit('Invalid signature');
}

$data = json_decode($payload, true);

if ($data['ref'] === 'refs/heads/dev') {
    exec('/home/Imiad.online/deploy.sh >> /home/Imiad.online/deploy.log 2>&1 &');
    file_put_contents($log_file, "✅ Deploy triggered\n", FILE_APPEND);
    echo "Deployed";
} else {
    file_put_contents($log_file, "⚠ Not dev branch\n", FILE_APPEND);
    echo "Not dev branch";
}