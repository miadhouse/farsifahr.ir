<?php
define('DB_HOST', 'localhost');
define('DB_NAME', 'fars_lara');
define('DB_USER', 'fars_miadlara');
define('DB_PASS', 'c@GuNBl%6JMlP!E9');

try {
    $pdo = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4", DB_USER, DB_PASS);
    $stmt = $pdo->query("SELECT COUNT(*) FROM users");
    echo "Users in fars_lara: " . $stmt->fetchColumn() . "\n";
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
