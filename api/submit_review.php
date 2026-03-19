<?php
require_once '../includes/sessions.php';
require_once '../includes/env_loader.php';
require_once '../config/database.php';
require_once '../vendor/autoload.php';

use App\Auth;
use App\Review;

header('Content-Type: application/json');

// Check if user is logged in
if (!Auth::check()) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'You must be logged in to submit a review']);
    exit;
}

// Get POST data
$bookId = (int) ($_POST['book_id'] ?? 0);
$rating = (int) ($_POST['rating'] ?? 0);
$comment = trim($_POST['comment'] ?? '');

// Validate input
if ($bookId <= 0) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid book ID']);
    exit;
}

if ($rating < 1 || $rating > 5) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Rating must be between 1 and 5']);
    exit;
}

if (empty($comment)) {
    $comment = null;
}

try {
    $config = require '../config/database.php';
    $dsn = "mysql:host={$config['host']};dbname={$config['dbname']};charset={$config['charset']}";
    $pdo = new PDO($dsn, $config['username'], $config['password'], $config['options']);

    $review = new Review($pdo);
    $userId = Auth::id();

    if ($review->addReview($bookId, $userId, $rating, $comment)) {
        echo json_encode([
            'success' => true,
            'message' => 'Review submitted successfully!'
        ]);
    } else {
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'message' => 'Failed to submit review. Please try again.'
        ]);
    }
} catch (Exception $e) {
    error_log('Review submission error: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'An error occurred. Please try again later.'
    ]);
}
