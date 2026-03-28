<?php
$config = require 'config/database.php';
$pdo = new PDO("mysql:host={$config['host']};dbname={$config['dbname']}", $config['username'], $config['password'], $config['options']);
$stmt = $pdo->query("SHOW TABLES LIKE 'transactions'");
if ($stmt->fetch()) {
    echo "[OK] Transactions table exists.\n";
    $stmt2 = $pdo->query("DESCRIBE transactions");
    print_r($stmt2->fetchAll(PDO::FETCH_ASSOC));
} else {
    echo "[MISSING] Transactions table NOT FOUND!\n";
}
