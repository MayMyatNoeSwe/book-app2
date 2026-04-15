<?php
require 'vendor/autoload.php';
$config = include 'config/database.php';
$dsn = "mysql:host={$config['host']};dbname={$config['dbname']};charset={$config['charset']}";
$pdo = new PDO($dsn, $config['username'], $config['password']);
$statuses = $pdo->query("SELECT DISTINCT status FROM borrowing_history")->fetchAll(PDO::FETCH_COLUMN);
echo implode(", ", $statuses);
