<?php
/**
 * Check if reset_token columns exist in users table
 */

require_once 'config/database.php';

$config = require 'config/database.php';
$dsn = "mysql:host={$config['host']};dbname={$config['dbname']};charset={$config['charset']}";
$pdo = new PDO($dsn, $config['username'], $config['password'], $config['options']);

echo "<h2>Checking Users Table Structure</h2>";

// Get table structure
$stmt = $pdo->query("DESCRIBE users");
$columns = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
echo "<tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th></tr>";

$hasResetToken = false;
$hasResetTokenExpiry = false;

foreach ($columns as $column) {
    echo "<tr>";
    echo "<td><strong>" . htmlspecialchars($column['Field']) . "</strong></td>";
    echo "<td>" . htmlspecialchars($column['Type']) . "</td>";
    echo "<td>" . htmlspecialchars($column['Null']) . "</td>";
    echo "<td>" . htmlspecialchars($column['Key']) . "</td>";
    echo "<td>" . htmlspecialchars($column['Default'] ?? 'NULL') . "</td>";
    echo "</tr>";
    
    if ($column['Field'] === 'reset_token') {
        $hasResetToken = true;
    }
    if ($column['Field'] === 'reset_token_expiry') {
        $hasResetTokenExpiry = true;
    }
}

echo "</table>";

echo "<h3>Status:</h3>";
echo "<p>reset_token column: " . ($hasResetToken ? "✅ EXISTS" : "❌ MISSING") . "</p>";
echo "<p>reset_token_expiry column: " . ($hasResetTokenExpiry ? "✅ EXISTS" : "❌ MISSING") . "</p>";

if (!$hasResetToken || !$hasResetTokenExpiry) {
    echo "<div style='background: #fff3cd; padding: 20px; border-radius: 10px; margin: 20px 0;'>";
    echo "<h4>⚠️ Missing Columns Detected!</h4>";
    echo "<p>The password reset columns are missing. You need to run the setup script.</p>";
    echo "<p><a href='setup_password_reset.php' style='background: #2e8a40; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px; display: inline-block;'>Run Setup Script</a></p>";
    echo "</div>";
} else {
    echo "<div style='background: #d4edda; padding: 20px; border-radius: 10px; margin: 20px 0;'>";
    echo "<h4>✅ All columns exist!</h4>";
    echo "<p>The password reset functionality should work correctly.</p>";
    echo "<p><a href='generate_reset_token.php' style='background: #2e8a40; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px; display: inline-block;'>Generate Test Token</a></p>";
    echo "</div>";
}

echo "<hr>";
echo "<h3>Quick Actions:</h3>";
echo "<ul>";
echo "<li><a href='generate_reset_token.php'>Generate Reset Token</a></li>";
echo "<li><a href='forgot_password.php'>Test Forgot Password</a></li>";
echo "<li><a href='debug_reset_password.php'>Debug Reset Password</a></li>";
echo "<li><a href='index.php'>Back to Home</a></li>";
echo "</ul>";
?>

<style>
    body {
        font-family: Arial, sans-serif;
        max-width: 900px;
        margin: 50px auto;
        padding: 20px;
        background: #f5f5f5;
    }
    h2, h3 {
        color: #2e8a40;
    }
    table {
        background: white;
        margin: 20px 0;
    }
    th {
        background: #2e8a40;
        color: white;
        padding: 10px;
    }
    td {
        padding: 8px;
    }
    tr:nth-child(even) {
        background: #f8f9fa;
    }
</style>
