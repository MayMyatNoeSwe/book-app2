<?php
$config = require 'config/database.php';
$pdo = new PDO('mysql:host=' . $config['host'] . ';dbname=' . $config['dbname'], $config['username'], $config['password']);
$stmt = $pdo->query("SELECT * FROM users WHERE id = 50");
print_r($stmt->fetch(PDO::FETCH_ASSOC));
