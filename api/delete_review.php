<?php
require_once '../includes/sessions.php';
require_once '../includes/env_loader.php';
require_once '../config/database.php';
require_once '../vendor/autoload.php';

use App\Auth;
use App\Review;

header('Content-Type: application/json');

// Enable error logging
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);

// Check if user is logged in
if (!Auth::check()) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'You must be logged in']);
    exit;
}

// Get JSON data
$input = json_decode(file_get_contents('php://input'), true);
$reviewId = (int) ($input['review_id'] ?? 0);

if ($reviewId <= 0) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid review ID: ' . $reviewId]);
    exit;
}

try {
    $config = require '../config/database.php';
    $dsn = "mysql:host={$config['host']};dbname={$config['dbname']};charset={$config['charset']}";
    $pdo = new PDO($dsn, $config['username'], $config['password'], $config['options']);

    $review = new Review($pdo);
    $userId = Auth::id();

    $result = $review->deleteReview($reviewId, $userId);
    
    if ($result) {
        echo json_encode([
            'success' => true,
            'message' => 'Review deleted successfully!'
        ]);
    } else {
        http_response_code(403);
        echo json_encode([
            'success' => false,
            'message' => 'You can only delete your own reviews or review not found.'
        ]);
    }
} catch (Exception $e) {
    error_log('Delete review error: ' . $e->getMessage());
    error_log('Stack trace: ' . $e->getTraceAsString());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'An error occurred: ' . $e->getMessage()
    ]);
}
