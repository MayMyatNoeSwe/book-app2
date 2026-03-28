<?php
$config = require 'config/database.php';
$pdo = new PDO("mysql:host={$config['host']};dbname={$config['dbname']}", $config['username'], $config['password'], $config['options']);
$pdo->exec("ALTER TABLE membership_requests ADD COLUMN redeem_code VARCHAR(50) NULL AFTER status");
echo "Column 'redeem_code' added successfully.";
