<?php
$config = require 'config/database.php';
$pdo = new PDO("mysql:host={$config['host']};dbname={$config['dbname']}", $config['username'], $config['password'], $config['options']);

echo "Checking membership_requests...\n";
$stmt = $pdo->query("SELECT id, user_id, tier, status, redeem_code FROM membership_requests ORDER BY id DESC LIMIT 5");
print_r($stmt->fetchAll(PDO::FETCH_ASSOC));

echo "\nChecking membership_codes...\n";
$stmt = $pdo->query("SELECT id, code, tier, usage_limit FROM membership_codes ORDER BY id DESC LIMIT 5");
print_r($stmt->fetchAll(PDO::FETCH_ASSOC));
