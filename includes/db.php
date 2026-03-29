<?php
// includes/db.php
$config = require __DIR__ . '/../config/database.php';
$dsn = "mysql:host={$config['host']};dbname={$config['dbname']};charset={$config['charset']}";

try {
    $pdo = new PDO($dsn, $config['username'], $config['password'], $config['options']);
    return $pdo;
} catch (PDOException $e) {
    die("Database Connection Error: " . $e->getMessage());
}
