<?php
// setup_membership_requests.php
require_once __DIR__ . '/vendor/autoload.php';
require_once __DIR__ . '/includes/env_loader.php';
require_once __DIR__ . '/config/database.php';

try {
    $config = require __DIR__ . '/config/database.php';
    $dsn = "mysql:host={$config['host']};dbname={$config['dbname']};charset={$config['charset']}";
    $pdo = new PDO($dsn, $config['username'], $config['password'], $config['options']);

    $sql = "CREATE TABLE IF NOT EXISTS membership_requests (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        tier VARCHAR(20) NOT NULL,
        payment_method VARCHAR(50) NOT NULL,
        payment_screenshot VARCHAR(255) NULL,
        status ENUM('pending', 'approved', 'rejected') DEFAULT 'pending',
        admin_note TEXT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;";

    $pdo->exec($sql);
    echo "Membership Requests table created successfully.\n";

} catch (Exception $e) {
    die("Error: " . $e->getMessage());
}
