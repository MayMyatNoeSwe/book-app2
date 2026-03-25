<?php
require_once 'vendor/autoload.php';
require_once 'includes/env_loader.php';
$library = new App\Library();
$pdo = $library->getPdo();
$tables = ['borrowing_history', 'users', 'orders'];
foreach ($tables as $table) {
    echo "--- $table ---\n";
    $res = $pdo->query("SHOW COLUMNS FROM $table");
    foreach ($res as $col) echo $col['Field'] . "\n";
}
