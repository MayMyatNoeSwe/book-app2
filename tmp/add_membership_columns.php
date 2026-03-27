<?php
// tmp/add_membership_columns.php
require_once 'vendor/autoload.php';
require_once 'includes/env_loader.php';
$config = require 'config/database.php';
$dsn = "mysql:host={$config['host']};dbname={$config['dbname']};charset={$config['charset']}";
$pdo = new PDO($dsn, $config['username'], $config['password'], $config['options']);

try {
    // Add columns if they don't exist
    $pdo->exec("ALTER TABLE users ADD COLUMN membership_tier VARCHAR(20) DEFAULT 'bronze' AFTER role");
    $pdo->exec("ALTER TABLE users ADD COLUMN membership_id VARCHAR(20) UNIQUE AFTER membership_tier");
    echo "Columns added successfully.\n";

    // Populate membership_id for existing users
    $stmt = $pdo->query("SELECT id FROM users WHERE membership_id IS NULL");
    $users = $stmt->fetchAll();

    $updateStmt = $pdo->prepare("UPDATE users SET membership_id = ? WHERE id = ?");
    foreach ($users as $u) {
        $mid = 'LIB-' . str_pad($u['id'], 6, '0', STR_PAD_LEFT);
        $updateStmt->execute([$mid, $u['id']]);
    }
    echo "Populated membership IDs for " . count($users) . " users.\n";

} catch (PDOException $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>
