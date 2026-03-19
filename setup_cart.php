<?php
/**
 * Setup Shopping Cart Tables
 * Run this once to create the cart and orders tables
 */

require_once 'includes/sessions.php';
require_once 'includes/env_loader.php';

try {
    $config = require 'config/database.php';
    $dsn = "mysql:host={$config['host']};dbname={$config['dbname']};charset={$config['charset']}";
    $pdo = new PDO($dsn, $config['username'], $config['password'], $config['options']);
    
    echo "<!DOCTYPE html><html><head><title>Cart Setup</title>";
    echo "<link href='https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css' rel='stylesheet'>";
    echo "</head><body><div class='container my-5'>";
    
    echo "<h2>Setting up Shopping Cart Tables...</h2><hr>";
    
    // Create cart table
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS cart (
            id INT PRIMARY KEY AUTO_INCREMENT,
            user_id INT NOT NULL,
            book_id INT NOT NULL,
            quantity INT NOT NULL DEFAULT 1,
            added_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
            FOREIGN KEY (book_id) REFERENCES books(id) ON DELETE CASCADE,
            UNIQUE KEY unique_user_book (user_id, book_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");
    echo "<p class='text-success'>✓ Cart table created</p>";
    
    // Create orders table
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS orders (
            id INT PRIMARY KEY AUTO_INCREMENT,
            user_id INT NOT NULL,
            order_number VARCHAR(50) UNIQUE NOT NULL,
            total_amount DECIMAL(10, 2) NOT NULL,
            status ENUM('pending', 'processing', 'completed', 'cancelled') DEFAULT 'pending',
            payment_method VARCHAR(50),
            shipping_address TEXT,
            notes TEXT,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
            INDEX idx_user_id (user_id),
            INDEX idx_order_number (order_number),
            INDEX idx_status (status)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");
    echo "<p class='text-success'>✓ Orders table created</p>";
    
    // Create order_items table
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS order_items (
            id INT PRIMARY KEY AUTO_INCREMENT,
            order_id INT NOT NULL,
            book_id INT NOT NULL,
            quantity INT NOT NULL,
            price DECIMAL(10, 2) NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE,
            FOREIGN KEY (book_id) REFERENCES books(id) ON DELETE CASCADE,
            INDEX idx_order_id (order_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");
    echo "<p class='text-success'>✓ Order items table created</p>";
    
    // Add price column to books table if it doesn't exist
    $stmt = $pdo->query("SHOW COLUMNS FROM books LIKE 'price'");
    if ($stmt->rowCount() == 0) {
        $pdo->exec("ALTER TABLE books ADD COLUMN price DECIMAL(10, 2) DEFAULT 9.99 AFTER year");
        echo "<p class='text-success'>✓ Added price column to books table</p>";
        
        // Set random prices for existing books
        $pdo->exec("UPDATE books SET price = ROUND(5 + (RAND() * 20), 2) WHERE price IS NULL");
        echo "<p class='text-success'>✓ Set prices for existing books</p>";
    } else {
        echo "<p class='text-info'>✓ Price column already exists</p>";
    }
    
    echo "<hr><h3 class='text-success'>✓ Shopping Cart Setup Complete!</h3>";
    echo "<div class='alert alert-success'>";
    echo "<h5>You can now:</h5>";
    echo "<ul>";
    echo "<li>Add books to cart</li>";
    echo "<li>View cart items</li>";
    echo "<li>Update quantities</li>";
    echo "<li>Remove items</li>";
    echo "<li>Place orders</li>";
    echo "</ul>";
    echo "</div>";
    echo "<a href='index.php' class='btn btn-primary'>Go to Home Page</a> ";
    echo "<a href='cart.php' class='btn btn-success'>View Cart</a>";
    echo "</div></body></html>";
    
} catch (PDOException $e) {
    echo "<!DOCTYPE html><html><head><title>Setup Error</title>";
    echo "<link href='https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css' rel='stylesheet'>";
    echo "</head><body><div class='container my-5'>";
    echo "<div class='alert alert-danger'>";
    echo "<h3>Error: " . htmlspecialchars($e->getMessage()) . "</h3>";
    echo "<p>Please check your database configuration in <code>config/database.php</code></p>";
    echo "</div>";
    echo "<a href='index.php' class='btn btn-secondary'>Go Back</a>";
    echo "</div></body></html>";
}
