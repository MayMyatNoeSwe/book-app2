<?php
require 'vendor/autoload.php';
$config = require 'config/database.php';
$pdo = new PDO('mysql:host=' . $config['host'] . ';dbname=' . $config['dbname'], $config['username'], $config['password']);
$stmt = $pdo->prepare("SELECT bh.*, b.title FROM borrowing_history bh JOIN books b ON bh.book_id = b.id WHERE user_id = 50");
$stmt->execute();
$results = $stmt->fetchAll(PDO::FETCH_ASSOC);
print_r($results);
