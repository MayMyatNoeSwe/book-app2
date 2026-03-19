<?php
require_once 'includes/env_loader.php';
require_once 'config/database.php';

try {
    $config = require 'config/database.php';
    $dsn = "mysql:host={$config['host']};dbname={$config['dbname']};charset={$config['charset']}";
    $pdo = new PDO($dsn, $config['username'], $config['password'], $config['options']);
    
    echo "<h2>Setting Up OAuth Database</h2>";
    
    // Check if columns already exist
    $stmt = $pdo->query("SHOW COLUMNS FROM users LIKE 'oauth_provider'");
    if ($stmt->rowCount() > 0) {
        echo "<p>✅ OAuth columns already exist!</p>";
    } else {
        echo "<p>Adding OAuth columns...</p>";
        
        // Add OAuth columns
        $pdo->exec("ALTER TABLE users 
            ADD COLUMN oauth_provider VARCHAR(50) NULL AFTER password,
            ADD COLUMN oauth_id VARCHAR(255) NULL AFTER oauth_provider,
            ADD COLUMN avatar_url VARCHAR(500) NULL AFTER oauth_id,
            ADD COLUMN email_verified BOOLEAN DEFAULT FALSE AFTER email");
        
        echo "<p>✅ OAuth columns added successfully!</p>";
        
        // Create index
        $pdo->exec("CREATE INDEX idx_oauth ON users(oauth_provider, oauth_id)");
        echo "<p>✅ OAuth index created!</p>";
        
        // Update existing users
        $pdo->exec("UPDATE users SET email_verified = TRUE WHERE oauth_provider IS NULL");
        echo "<p>✅ Existing users updated!</p>";
    }
    
    echo "<h3>Next Steps:</h3>";
    echo "<ol>";
    echo "<li>Get OAuth credentials from <a href='https://console.cloud.google.com/' target='_blank'>Google</a> and <a href='https://developers.facebook.com/' target='_blank'>Facebook</a></li>";
    echo "<li>Add credentials to your .env file</li>";
    echo "<li>Test OAuth by visiting <a href='register.php'>register.php</a></li>";
    echo "</ol>";
    
    echo "<h3>Add to .env:</h3>";
    echo "<pre>";
    echo "GOOGLE_CLIENT_ID=your_google_client_id_here
GOOGLE_CLIENT_SECRET=your_google_client_secret_here
GOOGLE_REDIRECT_URI=http://localhost/book-app/oauth/google/callback.php

FACEBOOK_CLIENT_ID=your_facebook_app_id_here
FACEBOOK_CLIENT_SECRET=your_facebook_app_secret_here
FACEBOOK_REDIRECT_URI=http://localhost/book-app/oauth/facebook/callback.php";
    echo "</pre>";
    
} catch (PDOException $e) {
    echo "<p style='color: red;'>❌ Error: " . $e->getMessage() . "</p>";
}
?>
