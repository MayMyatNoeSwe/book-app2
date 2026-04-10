<?php
// add_custom_limit_column.php
require_once __DIR__ . '/vendor/autoload.php';
$config = require __DIR__ . '/config/database.php';
$dsn = "mysql:host={$config['host']};dbname={$config['dbname']};charset={$config['charset']}";

try {
    $pdo = new PDO($dsn, $config['username'], $config['password'], $config['options']);
    echo "Adding custom_borrow_limit to user_subscriptions...\n";

    $pdo->exec("ALTER TABLE user_subscriptions ADD COLUMN custom_borrow_limit INT NULL DEFAULT NULL");
    echo "[OK] table altered successfully.\n";

} catch (PDOException $e) {
    echo "Error: " . $e->getMessage();
}
