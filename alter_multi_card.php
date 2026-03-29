<?php
$config = require 'config/database.php';
$pdo = new PDO("mysql:host={$config['host']};dbname={$config['dbname']}", $config['username'], $config['password'], $config['options']);

echo "Altering database for Multi-Card System...\n";

// 1. Add active_subscription_id to users
try {
    $pdo->exec("ALTER TABLE users ADD COLUMN active_subscription_id INT NULL AFTER membership_tier");
    echo "[OK] Added active_subscription_id to users.\n";
} catch (Exception $e) {
    echo "[ERROR/INFO] active_subscription_id might exist: " . $e->getMessage() . "\n";
}

// 2. Add subscription_id to borrowing_history
try {
    $pdo->exec("ALTER TABLE borrowing_history ADD COLUMN subscription_id INT NULL AFTER user_id");
    echo "[OK] Added subscription_id to borrowing_history.\n";
} catch (Exception $e) {
    echo "[ERROR/INFO] subscription_id might exist: " . $e->getMessage() . "\n";
}

// 3. Initialize active_subscription_id for existing users
$pdo->exec("UPDATE users u SET active_subscription_id = (SELECT id FROM user_subscriptions us WHERE us.user_id = u.id AND us.expires_at > NOW() ORDER BY id DESC LIMIT 1) WHERE active_subscription_id IS NULL");
echo "[OK] Initialized active cards for existing users.\n";
