<?php
/**
 * Debug Reset Password
 * This file helps diagnose issues with the password reset functionality
 */

echo "<h1>Reset Password Debug</h1>";
echo "<hr>";

// Check if token is in URL
echo "<h3>1. Token Check</h3>";
$token = $_GET['token'] ?? null;
if ($token) {
    echo "✅ Token found in URL: <code>" . htmlspecialchars($token) . "</code><br>";
    echo "Token length: " . strlen($token) . " characters<br>";
} else {
    echo "❌ No token found in URL<br>";
    echo "Current URL: " . htmlspecialchars($_SERVER['REQUEST_URI']) . "<br>";
}

echo "<hr>";

// Check database connection
echo "<h3>2. Database Connection</h3>";
try {
    require_once 'config/database.php';
    $config = require 'config/database.php';
    $dsn = "mysql:host={$config['host']};dbname={$config['dbname']};charset={$config['charset']}";
    $pdo = new PDO($dsn, $config['username'], $config['password'], $config['options']);
    echo "✅ Database connected successfully<br>";
    
    if ($token) {
        echo "<hr>";
        echo "<h3>3. Token Validation</h3>";
        
        // Check if token exists
        $stmt = $pdo->prepare("SELECT id, username, email, reset_token, reset_token_expiry FROM users WHERE reset_token = ? LIMIT 1");
        $stmt->execute([$token]);
        $user = $stmt->fetch();
        
        if ($user) {
            echo "✅ Token found in database<br>";
            echo "User: " . htmlspecialchars($user['username']) . " (" . htmlspecialchars($user['email']) . ")<br>";
            echo "Token expiry: " . $user['reset_token_expiry'] . "<br>";
            
            // Check if expired
            $now = new DateTime();
            $expiry = new DateTime($user['reset_token_expiry']);
            
            if ($now < $expiry) {
                echo "✅ Token is still valid<br>";
                $diff = $now->diff($expiry);
                echo "Time remaining: " . $diff->format('%i minutes %s seconds') . "<br>";
                
                echo "<hr>";
                echo "<h3>4. Test Reset Link</h3>";
                $resetUrl = "http://" . $_SERVER['HTTP_HOST'] . dirname($_SERVER['PHP_SELF']) . "/reset_password.php?token=" . urlencode($token);
                echo "Your reset link: <a href='" . htmlspecialchars($resetUrl) . "'>" . htmlspecialchars($resetUrl) . "</a><br>";
                echo "<br><a href='reset_password.php?token=" . urlencode($token) . "' class='btn'>Go to Reset Password Page</a>";
            } else {
                echo "❌ Token has expired<br>";
                echo "Expired: " . $diff->format('%i minutes %s seconds') . " ago<br>";
            }
        } else {
            echo "❌ Token not found in database<br>";
            echo "This could mean:<br>";
            echo "- The token was already used<br>";
            echo "- The token is incorrect<br>";
            echo "- The token was never generated<br>";
        }
    }
    
} catch (PDOException $e) {
    echo "❌ Database connection failed: " . $e->getMessage() . "<br>";
}

echo "<hr>";
echo "<h3>5. Session Info</h3>";
require_once 'includes/sessions.php';
echo "Session started: " . (session_status() === PHP_SESSION_ACTIVE ? "✅ Yes" : "❌ No") . "<br>";
echo "Logged in: " . (isset($_SESSION['logged_in']) && $_SESSION['logged_in'] ? "✅ Yes" : "❌ No") . "<br>";
if (isset($_SESSION['user_id'])) {
    echo "User ID: " . $_SESSION['user_id'] . "<br>";
}

echo "<hr>";
echo "<h3>6. Quick Actions</h3>";
echo "<a href='forgot_password.php'>Request New Reset Link</a> | ";
echo "<a href='login.php'>Go to Login</a> | ";
echo "<a href='index.php'>Go to Home</a>";

?>

<style>
    body {
        font-family: Arial, sans-serif;
        max-width: 800px;
        margin: 50px auto;
        padding: 20px;
        background: #f5f5f5;
    }
    h1 {
        color: #2e8a40;
    }
    h3 {
        color: #34495e;
        margin-top: 20px;
    }
    code {
        background: #e9ecef;
        padding: 2px 6px;
        border-radius: 3px;
        font-family: 'Courier New', monospace;
    }
    .btn {
        display: inline-block;
        background: #2e8a40;
        color: white;
        padding: 10px 20px;
        text-decoration: none;
        border-radius: 5px;
        margin-top: 10px;
    }
    .btn:hover {
        background: #267a36;
    }
    a {
        color: #2e8a40;
    }
</style>
