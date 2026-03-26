<?php
require_once 'vendor/autoload.php';
require_once 'includes/env_loader.php';
$c = require 'config/database.php';
$pdo = new PDO("mysql:host={$c['host']};dbname={$c['dbname']}", $c['username'], $c['password']);
$res = $pdo->query('DESCRIBE borrowing_history')->fetchAll(PDO::FETCH_ASSOC);
print_r($res);
