<?php
require_once 'vendor/autoload.php';
require_once 'includes/env_loader.php';
$c = require 'config/database.php';
$pdo = new PDO("mysql:host={$c['host']};dbname={$c['dbname']}", $c['username'], $c['password']);

try {
    $pdo->exec("ALTER TABLE borrowing_history 
                ADD COLUMN return_payment_method VARCHAR(50) NULL,
                ADD COLUMN return_screenshot VARCHAR(255) NULL");
    echo "Columns added successfully.\n";
} catch (Exception $e) {
    if (strpos($e->getMessage(), 'Duplicate column name') !== false) {
        echo "Columns already exist.\n";
    } else {
        echo "Error: " . $e->getMessage() . "\n";
    }
}
