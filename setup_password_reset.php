<?php
// Setup Password Reset Tokens in Database
require_once 'includes/env_loader.php';
require_once 'config/database.php';

$config = require 'config/database.php';
$dsn = "mysql:host={$config['host']};dbname={$config['dbname']};charset={$config['charset']}";

try {
    $pdo = new PDO($dsn, $config['username'], $config['password'], $config['options']);
    
    echo "Setting up password reset functionality...\n\n";
    
    // Check if columns already exist
    $stmt = $pdo->query("SHOW COLUMNS FROM users LIKE 'reset_token'");
    $tokenExists = $stmt->fetch();
    
    if (!$tokenExists) {
        // Add reset_token and reset_token_expiry columns
        $sql = "
            ALTER TABLE users 
            ADD COLUMN reset_token VARCHAR(64) NULL AFTER password,
            ADD COLUMN reset_token_expiry DATETIME NULL AFTER reset_token
        ";
        
        $pdo->exec($sql);
        echo "✓ Added reset_token and reset_token_expiry columns to users table\n";
    } else {
        echo "✓ Password reset columns already exist\n";
    }
    
    echo "\n✓ Password reset setup completed successfully!\n";
    echo "\nYou can now use the forgot password feature at: forgot_password.php\n";
    
} catch (PDOException $e) {
    echo "✗ Error: " . $e->getMessage() . "\n";
    exit(1);
}
