<?php
// api/load_books.php

require_once __DIR__ . '/../includes/sessions.php';
require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../includes/env_loader.php';
require_once __DIR__ . '/../includes/functions.php';

use App\Library;
use App\Auth;

// Set proper headers
header('Content-Type: text/html; charset=utf-8');
header('Cache-Control: no-cache, must-revalidate');

try {
    $library = new Library();

    $page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
    $limit = 12; // Must match initial load limit
    $offset = ($page - 1) * $limit;

    $category = $_GET['cat'] ?? null;
    $search = $_GET['q'] ?? null;

    // Sanitize inputs
    if ($category === '') $category = null;
    if ($search === '') $search = null;
    
    // Additional cleaning for search
    if ($search !== null) {
        $search = trim($search);
        if ($search === '') {
            $search = null;
        }
    }

    // Validate category if provided
    if ($category && !in_array($category, getCategories())) {
        $category = null;
    }

    $books = $library->getBooksPaginated($limit, $offset, $category, $search);

    if (empty($books)) {
        // Return empty string to indicate no more results
        echo '';
        exit;
    }

    // Buffer output to ensure clean HTML
    ob_start();
    
    foreach ($books as $book) {
        // Important: views/book-card.php expects $book and $library variables
        include __DIR__ . '/../views/book-card.php';
    }
    
    $output = ob_get_clean();
    echo $output;

} catch (Exception $e) {
    // Log error and return empty response
    error_log("Error in load_books.php: " . $e->getMessage());
    echo '';
}
?>