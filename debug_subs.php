<?php
$config = require 'config/database.php';
try {
    $pdo = new PDO("mysql:host={$config['host']};dbname={$config['dbname']}", $config['username'], $config['password'], $config['options']);
    $stmt = $pdo->prepare('SELECT * FROM user_subscriptions WHERE user_id = 42');
    $stmt->execute();
    print_r($stmt->fetchAll(PDO::FETCH_ASSOC));
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
