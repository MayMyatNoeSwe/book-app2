<?php
// Setup Reviews and Ratings Table
require_once 'includes/env_loader.php';
require_once 'config/database.php';

$config = require 'config/database.php';
$dsn = "mysql:host={$config['host']};dbname={$config['dbname']};charset={$config['charset']}";

try {
    $pdo = new PDO($dsn, $config['username'], $config['password'], $config['options']);
    
    echo "Setting up reviews and ratings system...\n\n";
    
    // Create reviews table
    $sql = "
        CREATE TABLE IF NOT EXISTS reviews (
            id INT AUTO_INCREMENT PRIMARY KEY,
            book_id INT NOT NULL,
            user_id INT NOT NULL,
            rating INT NOT NULL CHECK (rating >= 1 AND rating <= 5),
            comment TEXT,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            UNIQUE KEY unique_user_book (user_id, book_id),
            FOREIGN KEY (book_id) REFERENCES books(id) ON DELETE CASCADE,
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
            INDEX idx_book_id (book_id),
            INDEX idx_user_id (user_id),
            INDEX idx_created_at (created_at)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ";
    
    $pdo->exec($sql);
    echo "✓ Created reviews table\n";
    
    // Add average_rating column to books table if it doesn't exist
    $stmt = $pdo->query("SHOW COLUMNS FROM books LIKE 'average_rating'");
    if (!$stmt->fetch()) {
        $pdo->exec("ALTER TABLE books ADD COLUMN average_rating DECIMAL(3,2) DEFAULT 0.00 AFTER category");
        echo "✓ Added average_rating column to books table\n";
    } else {
        echo "✓ average_rating column already exists\n";
    }
    
    // Add review_count column to books table if it doesn't exist
    $stmt = $pdo->query("SHOW COLUMNS FROM books LIKE 'review_count'");
    if (!$stmt->fetch()) {
        $pdo->exec("ALTER TABLE books ADD COLUMN review_count INT DEFAULT 0 AFTER average_rating");
        echo "✓ Added review_count column to books table\n";
    } else {
        echo "✓ review_count column already exists\n";
    }
    
    echo "\n✓ Reviews and ratings system setup completed successfully!\n";
    echo "\nYou can now:\n";
    echo "- View book-details.php to see the review section\n";
    echo "- Logged-in users can add ratings and comments\n";
    echo "- Users can edit/delete their own reviews\n";
    
} catch (PDOException $e) {
    echo "✗ Error: " . $e->getMessage() . "\n";
    exit(1);
}
