<?php
$config = require 'config/database.php';
$pdo = new PDO("mysql:host={$config['host']};dbname={$config['dbname']}", $config['username'], $config['password'], $config['options']);
$stmt = $pdo->query('DESCRIBE membership_requests');
print_r($stmt->fetchAll(PDO::FETCH_ASSOC));
