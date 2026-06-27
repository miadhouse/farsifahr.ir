<?php
require_once(__DIR__ . '/config/config.php');

try {
    $stmt = $pdo->query("SELECT COUNT(*) FROM users");
    echo "Users count in main DB: " . $stmt->fetchColumn() . "\n";
    
    $stmt = $pdo->query("DESCRIBE users");
    echo "Users table structure:\n";
    while($row = $stmt->fetch()) {
        echo $row['Field'] . " - " . $row['Type'] . "\n";
    }
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
