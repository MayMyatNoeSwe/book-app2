<?php
require_once 'vendor/autoload.php';
require_once 'includes/env_loader.php';
$c = require 'config/database.php';
$pdo = new PDO("mysql:host={$c['host']};dbname={$c['dbname']}", $c['username'], $c['password']);

$orderNumber = 'ORD-20260322-B0EF5D6C';
$stmt = $pdo->prepare("SELECT * FROM orders WHERE order_number = ?");
$stmt->execute([$orderNumber]);
$orders = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo "### Findings for Order #$orderNumber ###\n";
foreach($orders as $o) {
    echo "ID: {$o['id']}\nUser ID: {$o['user_id']}\nStatus: {$o['status']}\nCreatedAt: {$o['created_at']}\n---\n";
}
