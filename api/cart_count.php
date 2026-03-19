<?php
require_once '../includes/sessions.php';
require_once '../includes/env_loader.php';
require_once '../config/database.php';
require_once '../vendor/autoload.php';

use App\Auth;
use App\Cart;

header('Content-Type: application/json');

if (!Auth::check()) {
    echo json_encode(['success' => true, 'count' => 0]);
    exit;
}

try {
    $config = require '../config/database.php';
    $dsn = "mysql:host={$config['host']};dbname={$config['dbname']};charset={$config['charset']}";
    $pdo = new PDO($dsn, $config['username'], $config['password'], $config['options']);

    $cart = new Cart($pdo);
    $count = $cart->getCount(Auth::id());

    echo json_encode([
        'success' => true,
        'count' => $count
    ]);
} catch (Exception $e) {
    error_log('Cart count error: ' . $e->getMessage());
    echo json_encode(['success' => false, 'count' => 0]);
}
