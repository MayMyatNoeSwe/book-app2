<?php
header('Content-Type: application/json');
require_once '../includes/sessions.php';
require_once '../vendor/autoload.php';
require_once '../includes/env_loader.php';
require_once '../includes/functions.php';

use App\Auth;
use App\Cart;

if (!Auth::check()) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);
if (!$input) {
    echo json_encode(['success' => false, 'message' => 'Invalid input']);
    exit;
}

$config = require '../config/database.php';
$dsn = "mysql:host={$config['host']};dbname={$config['dbname']};charset={$config['charset']}";
$pdo = new PDO($dsn, $config['username'], $config['password'], $config['options']);

$cart = new Cart($pdo);
$userId = Auth::id();

$orderData = [
    'shipping_cost' => $input['shipping_cost'] ?? 0,
    'payment_method' => $input['payment_method'] ?? 'cash',
    'delivery_method' => $input['delivery_method'] ?? null,
    'delivery_location' => $input['delivery_location'] ?? null,
    'shipping_address' => $input['shipping_address'] ?? '',
    'notes' => $input['notes'] ?? ''
];

$orderNumber = $cart->createOrder($userId, $orderData);

if ($orderNumber) {
    echo json_encode(['success' => true, 'order_number' => $orderNumber]);
} else {
    echo json_encode(['success' => false, 'message' => 'Failed to place order']);
}
