<?php
require_once 'vendor/autoload.php';
require_once 'includes/env_loader.php';
$c = require 'config/database.php';
$pdo = new PDO("mysql:host={$c['host']};dbname={$c['dbname']}", $c['username'], $c['password']);

echo "### orders table ###\n";
foreach($pdo->query('DESCRIBE orders')->fetchAll(PDO::FETCH_ASSOC) as $r) echo "{$r['Field']} ({$r['Type']})\n";

echo "\n### order_items table ###\n";
foreach($pdo->query('DESCRIBE order_items')->fetchAll(PDO::FETCH_ASSOC) as $r) echo "{$r['Field']} ({$r['Type']})\n";
