<?php
require_once '../includes/sessions.php';
require_once '../includes/env_loader.php';
require_once '../config/database.php';
require_once '../vendor/autoload.php';

use App\Auth;
use App\Cart;

header('Content-Type: application/json');

// Check if user is logged in
if (!Auth::check()) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Please login to add items to cart']);
    exit;
}

// Get JSON data
$input = json_decode(file_get_contents('php://input'), true);
$bookId = $input['book_id'] ?? null;
$quantity = (int) ($input['quantity'] ?? 1);

if (empty($bookId)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid book ID']);
    exit;
}

if ($quantity <= 0) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid quantity']);
    exit;
}

try {
    $config = require '../config/database.php';
    $dsn = "mysql:host={$config['host']};dbname={$config['dbname']};charset={$config['charset']}";
    $pdo = new PDO($dsn, $config['username'], $config['password'], $config['options']);

    $cart = new Cart($pdo);
    $userId = Auth::id();

    $result = $cart->addItem($userId, $bookId, $quantity);
    
    // Check availability for message
    $stmt = $pdo->prepare("SELECT available_copies FROM books WHERE id = ?");
    $stmt->execute([$bookId]);
    $availableCopies = $stmt->fetchColumn();
    $isPreorder = ($availableCopies <= 0);

    if ($result) {
        $cartCount = $cart->getCount($userId);
        echo json_encode([
            'success' => true,
            'message' => $isPreorder ? 'Pre-order added to cart!' : 'Book added to cart!',
            'cart_count' => $cartCount
        ]);
    } else {
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'message' => 'Failed to add book to cart'
        ]);
    }
} catch (Exception $e) {
    error_log('Add to cart error: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'An error occurred'
    ]);
}
