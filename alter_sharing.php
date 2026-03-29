<?php
// alter_sharing.php
require_once __DIR__ . '/vendor/autoload.php';
$config = require __DIR__ . '/config/database.php';
$dsn = "mysql:host={$config['host']};dbname={$config['dbname']};charset={$config['charset']}";

try {
    $pdo = new PDO($dsn, $config['username'], $config['password'], $config['options']);
    
    echo "Altering database for Gold Membership Sharing System...\n";

    // 1. Update user_subscriptions
    $pdo->exec("ALTER TABLE user_subscriptions ADD COLUMN parent_id INT NULL DEFAULT NULL AFTER user_id");
    $pdo->exec("ALTER TABLE user_subscriptions ADD COLUMN is_host TINYINT(1) DEFAULT 0 AFTER parent_id");
    echo "[OK] Modified user_subscriptions table.\n";

    // 2. Update membership_codes
    $pdo->exec("ALTER TABLE membership_codes ADD COLUMN owner_id INT NULL DEFAULT NULL AFTER usage_limit");
    echo "[OK] Modified membership_codes table.\n";

    // 3. Mark existing primary subscriptions as hosts (optional but good for data integrity)
    $pdo->exec("UPDATE user_subscriptions SET is_host = 1 WHERE parent_id IS NULL");
    echo "[OK] Updated existing subscriptions as primary (hosts).\n";

    echo "\nMigration successful!";
} catch (PDOException $e) {
    echo "Migration failed: " . $e->getMessage();
}
