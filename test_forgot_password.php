<?php
// Test Forgot Password Setup
require_once 'includes/env_loader.php';
require_once 'config/database.php';

$config = require 'config/database.php';
$dsn = "mysql:host={$config['host']};dbname={$config['dbname']};charset={$config['charset']}";

header('Content-Type: text/html; charset=utf-8');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Test Forgot Password</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { padding: 20px; background: #f8f9fa; }
        .test-box { background: white; padding: 20px; border-radius: 8px; margin-bottom: 20px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); }
        .status-ok { color: #28a745; font-weight: bold; }
        .status-error { color: #dc3545; font-weight: bold; }
    </style>
</head>
<body>
    <div class="container">
        <h1 class="mb-4">Forgot Password Test</h1>
        
        <!-- Database Check -->
        <div class="test-box">
            <h4>1. Database Connection</h4>
            <?php
            try {
                $pdo = new PDO($dsn, $config['username'], $config['password'], $config['options']);
                echo '<p class="status-ok">✓ Database connected successfully</p>';
            } catch (PDOException $e) {
                echo '<p class="status-error">✗ Database connection failed: ' . htmlspecialchars($e->getMessage()) . '</p>';
                exit;
            }
            ?>
        </div>
        
        <!-- Table Structure Check -->
        <div class="test-box">
            <h4>2. Users Table Structure</h4>
            <?php
            try {
                $stmt = $pdo->query("DESCRIBE users");
                $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
                
                $hasResetToken = false;
                $hasResetExpiry = false;
                
                echo '<table class="table table-sm">';
                echo '<thead><tr><th>Field</th><th>Type</th><th>Null</th></tr></thead>';
                echo '<tbody>';
                foreach ($columns as $column) {
                    echo '<tr>';
                    echo '<td>' . htmlspecialchars($column['Field']) . '</td>';
                    echo '<td>' . htmlspecialchars($column['Type']) . '</td>';
                    echo '<td>' . htmlspecialchars($column['Null']) . '</td>';
                    echo '</tr>';
                    
                    if ($column['Field'] === 'reset_token') $hasResetToken = true;
                    if ($column['Field'] === 'reset_token_expiry') $hasResetExpiry = true;
                }
                echo '</tbody></table>';
                
                if ($hasResetToken && $hasResetExpiry) {
                    echo '<p class="status-ok">✓ Reset token columns exist</p>';
                } else {
                    echo '<p class="status-error">✗ Missing reset token columns. Run setup_password_reset.php</p>';
                }
            } catch (PDOException $e) {
                echo '<p class="status-error">✗ Error: ' . htmlspecialchars($e->getMessage()) . '</p>';
            }
            ?>
        </div>
        
        <!-- Test Users -->
        <div class="test-box">
            <h4>3. Test Users (with email)</h4>
            <?php
            try {
                $stmt = $pdo->query("SELECT id, username, email FROM users WHERE email IS NOT NULL AND email != '' LIMIT 5");
                $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
                
                if ($users) {
                    echo '<table class="table table-sm">';
                    echo '<thead><tr><th>ID</th><th>Username</th><th>Email</th></tr></thead>';
                    echo '<tbody>';
                    foreach ($users as $user) {
                        echo '<tr>';
                        echo '<td>' . $user['id'] . '</td>';
                        echo '<td>' . htmlspecialchars($user['username']) . '</td>';
                        echo '<td>' . htmlspecialchars($user['email']) . '</td>';
                        echo '</tr>';
                    }
                    echo '</tbody></table>';
                    echo '<p class="status-ok">✓ Found ' . count($users) . ' users with email addresses</p>';
                } else {
                    echo '<p class="status-error">✗ No users with email addresses found</p>';
                    echo '<p>You need to have users with valid email addresses to test password reset.</p>';
                }
            } catch (PDOException $e) {
                echo '<p class="status-error">✗ Error: ' . htmlspecialchars($e->getMessage()) . '</p>';
            }
            ?>
        </div>
        
        <!-- Test Token Generation -->
        <div class="test-box">
            <h4>4. Test Token Generation</h4>
            <?php
            $testToken = bin2hex(random_bytes(32));
            $testExpiry = date('Y-m-d H:i:s', strtotime('+1 hour'));
            
            echo '<p><strong>Sample Token:</strong> <code>' . htmlspecialchars($testToken) . '</code></p>';
            echo '<p><strong>Sample Expiry:</strong> ' . htmlspecialchars($testExpiry) . '</p>';
            echo '<p class="status-ok">✓ Token generation working</p>';
            ?>
        </div>
        
        <!-- File Checks -->
        <div class="test-box">
            <h4>5. Required Files</h4>
            <?php
            $files = [
                'forgot_password.php' => 'Forgot Password Page',
                'reset_password.php' => 'Reset Password Page',
                'setup_password_reset.php' => 'Database Setup Script'
            ];
            
            foreach ($files as $file => $description) {
                if (file_exists($file)) {
                    echo '<p class="status-ok">✓ ' . $description . ' (' . $file . ')</p>';
                } else {
                    echo '<p class="status-error">✗ Missing: ' . $description . ' (' . $file . ')</p>';
                }
            }
            ?>
        </div>
        
        <!-- Test Links -->
        <div class="test-box">
            <h4>6. Test the Feature</h4>
            <div class="btn-group" role="group">
                <a href="forgot_password.php" class="btn btn-primary">Go to Forgot Password</a>
                <a href="login.php" class="btn btn-success">Go to Login</a>
            </div>
        </div>
        
        <!-- Instructions -->
        <div class="test-box">
            <h4>7. How to Test</h4>
            <ol>
                <li>Make sure you have a user with a valid email address</li>
                <li>Go to <a href="forgot_password.php">forgot_password.php</a></li>
                <li>Enter the email address</li>
                <li>Check the PHP error log for the reset link (since email isn't configured)</li>
                <li>Copy the token from the log and visit: <code>reset_password.php?token=YOUR_TOKEN</code></li>
                <li>Enter new password and confirm</li>
            </ol>
            
            <div class="alert alert-info mt-3">
                <strong>Note:</strong> Since email is not configured, the reset link will be logged to the PHP error log. 
                In production, you would integrate an email service to send the link.
            </div>
        </div>
    </div>
</body>
</html>
