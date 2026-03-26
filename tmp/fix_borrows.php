<?php
require_once 'vendor/autoload.php';
require_once 'includes/env_loader.php';
$c = require 'config/database.php';
$pdo = new PDO("mysql:host={$c['host']};dbname={$c['dbname']}", $c['username'], $c['password']);

$stmt = $pdo->prepare("UPDATE borrowing_history SET status = 'returned' WHERE returned_at IS NOT NULL AND status != 'returned'");
$stmt->execute();
echo "Updated " . $stmt->rowCount() . " records.\n";
