<?php
require_once __DIR__ . '/vendor/autoload.php';
require_once __DIR__ . '/includes/env_loader.php';
require_once __DIR__ . '/config/database.php';

try {
    $config = require __DIR__ . '/config/database.php';
    $dsn = "mysql:host={$config['host']};dbname={$config['dbname']};charset={$config['charset']}";
    $pdo = new PDO($dsn, $config['username'], $config['password'], $config['options']);

    // 1. Transactions Table
    $sql1 = "CREATE TABLE IF NOT EXISTS transactions (
        id INT AUTO_INCREMENT PRIMARY KEY,
        type ENUM('income', 'expense') NOT NULL,
        category ENUM('membership_fee', 'borrow_fee', 'sale', 'penalty_fee', 'expense') NOT NULL,
        amount DECIMAL(10, 2) NOT NULL,
        description TEXT,
        reference_id VARCHAR(50) NULL,
        reference_table VARCHAR(50) NULL,
        user_id INT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;";

    $pdo->exec($sql1);
    echo "Transactions table created successfully.\n";

    // 2. Expenses Table
    $sql2 = "CREATE TABLE IF NOT EXISTS expenses (
        id INT AUTO_INCREMENT PRIMARY KEY,
        title VARCHAR(255) NOT NULL,
        amount DECIMAL(10, 2) NOT NULL,
        category VARCHAR(100) NOT NULL,
        description TEXT,
        date DATE NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;";

    $pdo->exec($sql2);
    echo "Expenses table created successfully.\n";

    // 3. Migrate existing data into transactions (Initial Sync)
    
    // 3a. Migrate completed orders
    $pdo->exec("INSERT IGNORE INTO transactions (type, category, amount, description, reference_id, reference_table, user_id, created_at)
                SELECT 'income', 'sale', total_amount, CONCAT('Order #', order_number), id, 'orders', user_id, created_at
                FROM orders WHERE status = 'completed'");
    echo "Existing completed orders migrated to transactions.\n";

    // 3b. Migrate approved membership requests
    // Note: We don't have price in membership_requests, so we have to guess or use a mapping.
    // Based on membership.php: silver=10000, gold=25000, platinum=50000
    $pdo->exec("INSERT IGNORE INTO transactions (type, category, amount, description, reference_id, reference_table, user_id, created_at)
                SELECT 'income', 'membership_fee', 
                CASE 
                    WHEN tier = 'silver' THEN 10000
                    WHEN tier = 'gold' THEN 25000
                    WHEN tier = 'platinum' THEN 50000
                    ELSE 0
                END,
                CONCAT('Membership tier: ', tier), id, 'membership_requests', user_id, created_at
                FROM membership_requests WHERE status = 'approved'");
    echo "Existing approved membership requests migrated to transactions.\n";

    // 3c. Migrate approved borrows
    $pdo->exec("INSERT IGNORE INTO transactions (type, category, amount, description, reference_id, reference_table, user_id, created_at)
                SELECT 'income', 'borrow_fee', b.borrow_price, CONCAT('Borrow: ', b.title), bh.id, 'borrowing_history', bh.user_id, bh.borrowed_at
                FROM borrowing_history bh
                JOIN books b ON bh.book_id = b.id
                WHERE bh.status IN ('approved', 'returned', 'return_pending')");
    echo "Existing borrow records migrated to transactions.\n";

    // 3d. Migrate paid penalties
    $pdo->exec("INSERT IGNORE INTO transactions (type, category, amount, description, reference_id, reference_table, user_id, created_at)
                SELECT 'income', 'penalty_fee', penalty_fee, 'Overdue penalty fine', id, 'borrowing_history', user_id, returned_at
                FROM borrowing_history
                WHERE penalty_paid = 1 AND penalty_fee > 0");
    echo "Existing paid penalties migrated to transactions.\n";

} catch (Exception $e) {
    die("Error: " . $e->getMessage());
}
