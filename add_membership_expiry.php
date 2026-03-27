<?php
// add_membership_expiry.php
require_once __DIR__ . '/vendor/autoload.php';
require_once __DIR__ . '/includes/env_loader.php';
require_once __DIR__ . '/config/database.php';

try {
    $config = require __DIR__ . '/config/database.php';
    $dsn = "mysql:host={$config['host']};dbname={$config['dbname']};charset={$config['charset']}";
    $pdo = new PDO($dsn, $config['username'], $config['password'], $config['options']);

    // Check if column exists
    $check = $pdo->query("SHOW COLUMNS FROM users LIKE 'membership_expires_at'");
    if (!$check->fetch()) {
        $pdo->exec("ALTER TABLE users ADD COLUMN membership_expires_at TIMESTAMP NULL AFTER membership_tier");
        echo "Successfully added membership_expires_at column to users table.\n";
    } else {
        echo "Column membership_expires_at already exists.\n";
    }

} catch (Exception $e) {
    die("Error: " . $e->getMessage());
}
