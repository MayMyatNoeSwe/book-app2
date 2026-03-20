<?php
require_once 'includes/sessions.php';
require_once 'vendor/autoload.php';
require_once 'includes/env_loader.php';

$c = include 'config/database.php';
try {
    $p = new PDO("mysql:host={$c['host']};dbname={$c['dbname']};charset={$c['charset']}", $c['username'], $c['password'], $c['options']);
    echo "\n--- Categories Count ---\n";
    $s = $p->query("SELECT category, COUNT(*) as cnt FROM books GROUP BY category");
    print_r($s->fetchAll(PDO::FETCH_ASSOC));
} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage();
}
