<?php
require_once 'includes/sessions.php';
require_once 'vendor/autoload.php';
require_once 'includes/env_loader.php';

$c = include 'config/database.php';
try {
    $p = new PDO("mysql:host={$c['host']};dbname={$c['dbname']};charset={$c['charset']}", $c['username'], $c['password'], $c['options']);
    echo "--- TABLE Structure (books) ---\n";
    $s = $p->query('DESCRIBE books');
    print_r($s->fetchAll(PDO::FETCH_ASSOC));
    
    echo "\n--- DISTINCT Categories in books ---\n";
    $s = $p->query("SELECT DISTINCT category FROM books");
    print_r($s->fetchAll(PDO::FETCH_COLUMN));
} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage();
}
