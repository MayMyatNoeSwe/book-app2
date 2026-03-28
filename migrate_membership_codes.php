<?php
// migrate_membership_codes.php
require_once __DIR__ . '/vendor/autoload.php';
require_once __DIR__ . '/includes/env_loader.php';

$config = require __DIR__ . '/config/database.php';
$dsn = "mysql:host={$config['host']};dbname={$config['dbname']};charset={$config['charset']}";

try {
    $pdo = new PDO($dsn, $config['username'], $config['password'], $config['options']);
    
    // Create membership_codes table
    $pdo->exec("CREATE TABLE IF NOT EXISTS membership_codes (
        id INT AUTO_INCREMENT PRIMARY KEY,
        code VARCHAR(50) UNIQUE NOT NULL,
        tier VARCHAR(20) NOT NULL,
        usage_limit INT DEFAULT 5,
        expires_at DATETIME NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB");

    // Create membership_code_usage table
    $pdo->exec("CREATE TABLE IF NOT EXISTS membership_code_usage (
        id INT AUTO_INCREMENT PRIMARY KEY,
        code_id INT NOT NULL,
        user_id INT NOT NULL,
        used_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (code_id) REFERENCES membership_codes(id) ON DELETE CASCADE,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
        UNIQUE KEY unique_user_code (code_id, user_id)
    ) ENGINE=InnoDB");

    echo "Migration successful: membership_codes and membership_code_usage tables created.";
} catch (PDOException $e) {
    echo "Migration failed: " . $e->getMessage();
}
