<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

require_once 'includes/env_loader.php';
$config = require 'config/database.php';

echo "<h3>Environment Check</h3>";
echo "DB_PASS in \$_ENV: [" . ($_ENV['DB_PASS'] ?? 'NOT SET') . "]<br>";
echo "DB_PASS in \$_SERVER: [" . ($_SERVER['DB_PASS'] ?? 'NOT SET') . "]<br>";
echo "DB_PASS in getenv: [" . getenv('DB_PASS') . "]<br>";

echo "<h3>Database ConfigurationDiagnostic</h3>";
echo "Host: " . $config['host'] . "<br>";
echo "User: " . $config['username'] . "<br>";
echo "Password Length: " . strlen($config['password']) . "<br>";

try {
    $dsn = "mysql:host={$config['host']};dbname={$config['dbname']}";
    $pdo = new PDO($dsn, $config['username'], $config['password']);
    echo "<h3 style='color:green'>Connection Successful!</h3>";
} catch (PDOException $e) {
    echo "<h3 style='color:red'>Connection Failed!</h3>";
    echo "Error: " . $e->getMessage() . "<br>";
}
