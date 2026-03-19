<?php
require_once '../includes/sessions.php';
require_once '../includes/env_loader.php';
require_once '../config/database.php';
require_once '../vendor/autoload.php';

use App\Auth;
use App\Cart;

header('Content-Type: application/json');

if (!Auth::check()) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);
$cartId = (int) ($input['cart_id'] ?? 0);
$quantity = (int) ($input['quantity'] ?? 0);

if ($cartId <= 0) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid cart ID']);
    exit;
}

try {
    $config = require '../config/database.php';
    $dsn = "mysql:host={$config['host']};dbname={$config['dbname']};charset={$config['charset']}";
    $pdo = new PDO($dsn, $config['username'], $config['password'], $config['options']);

    $cart = new Cart($pdo);
    $userId = Auth::id();

    $result = $cart->updateQuantity($cartId, $userId, $quantity);
    
    if ($result) {
        $cartCount = $cart->getCount($userId);
        $cartTotal = $cart->getTotal($userId);
        
        echo json_encode([
            'success' => true,
            'message' => 'Cart updated',
            'cart_count' => $cartCount,
            'cart_total' => number_format($cartTotal, 2)
        ]);
    } else {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Failed to update cart']);
    }
} catch (Exception $e) {
    error_log('Update cart error: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'An error occurred']);
}
