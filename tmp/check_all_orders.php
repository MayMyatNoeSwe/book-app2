<?php
require_once 'vendor/autoload.php';
require_once 'includes/env_loader.php';
$c = require 'config/database.php';
$pdo = new PDO("mysql:host={$c['host']};dbname={$c['dbname']}", $c['username'], $c['password']);

echo "### Orders in database ###\n";
$res = $pdo->query("SELECT id, user_id, order_number, status FROM orders")->fetchAll(PDO::FETCH_ASSOC);
print_r($res);
