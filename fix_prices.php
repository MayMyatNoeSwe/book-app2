<?php
require_once 'includes/env_loader.php';
$config = include 'config/database.php';
$dsn = "mysql:host={$config['host']};dbname={$config['dbname']};charset={$config['charset']}";

try {
    $pdo = new PDO($dsn, $config['username'], $config['password'], $config['options']);
    
    // Set Add to Cart price (5000+)
    $pdo->exec("UPDATE books SET price = (FLOOR(5 + (RAND() * 20)) * 1000)");
    
    // Set Borrow price (30-40% of Add to Cart price)
    $pdo->exec("UPDATE books SET borrow_price = (FLOOR((price * (0.3 + (RAND() * 0.1))) / 100) * 100)");
    
    echo "Successfully updated prices and borrow fees!";
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage();
}
