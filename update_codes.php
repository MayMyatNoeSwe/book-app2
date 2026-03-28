<?php
$config = require 'config/database.php';
$pdo = new PDO("mysql:host={$config['host']};dbname={$config['dbname']}", $config['username'], $config['password'], $config['options']);
$pdo->exec("UPDATE membership_codes SET usage_limit = 5 WHERE code LIKE 'MS-%'");
echo "All existing released keys updated to limit 5.";
