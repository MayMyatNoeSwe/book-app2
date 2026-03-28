<?php
$config = require 'config/database.php';
$pdo = new PDO("mysql:host={$config['host']};dbname={$config['dbname']}", $config['username'], $config['password'], $config['options']);

echo "Checking tables...\n";
$tables = ['membership_codes', 'membership_code_usage', 'membership_requests'];
foreach ($tables as $t) {
    $stmt = $pdo->query("SHOW TABLES LIKE '$t'");
    if ($stmt->fetch()) {
        echo "[OK] Table $t exists.\n";
        $stmt2 = $pdo->query("DESCRIBE $t");
        print_r($stmt2->fetchAll(PDO::FETCH_ASSOC));
    } else {
        echo "[MISSING] Table $t NOT FOUND!\n";
    }
}
