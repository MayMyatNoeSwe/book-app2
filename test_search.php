<?php
// Quick test script for search functionality
require_once 'includes/sessions.php';
require_once 'vendor/autoload.php';
require_once 'includes/env_loader.php';
require_once 'includes/functions.php';

use App\Library;

try {
    $library = new Library();
    
    echo "<h2>Testing Search Functionality</h2>";
    
    // Test 1: Search for "fiction"
    echo "<h3>Test 1: Search for 'fiction'</h3>";
    $books = $library->getBooksPaginated(10, 0, null, 'fiction');
    echo "Found " . count($books) . " books<br>";
    foreach ($books as $book) {
        echo "- " . $book->getTitle() . " by " . $book->getAuthor() . "<br>";
    }
    
    // Test 2: Search for "test"
    echo "<h3>Test 2: Search for 'test'</h3>";
    $books = $library->getBooksPaginated(10, 0, null, 'test');
    echo "Found " . count($books) . " books<br>";
    foreach ($books as $book) {
        echo "- " . $book->getTitle() . " by " . $book->getAuthor() . "<br>";
    }
    
    // Test 3: Category filter
    echo "<h3>Test 3: Category filter 'Fiction'</h3>";
    $books = $library->getBooksPaginated(10, 0, 'Fiction', null);
    echo "Found " . count($books) . " books<br>";
    foreach ($books as $book) {
        echo "- " . $book->getTitle() . " (" . $book->getCategory() . ")<br>";
    }
    
    // Test 4: Count books
    echo "<h3>Test 4: Count books with search 'fiction'</h3>";
    $count = $library->countBooks(null, 'fiction');
    echo "Total count: " . $count . "<br>";
    
    echo "<h3>✅ All tests completed successfully!</h3>";
    
} catch (Exception $e) {
    echo "<h3>❌ Error: " . $e->getMessage() . "</h3>";
    echo "<pre>" . $e->getTraceAsString() . "</pre>";
}
?>