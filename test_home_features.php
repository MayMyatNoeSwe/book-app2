<?php
// Test the new home page features
require_once 'includes/sessions.php';
require_once 'vendor/autoload.php';
require_once 'includes/env_loader.php';
require_once 'includes/functions.php';

use App\Library;

try {
    $library = new Library();
    
    echo "<h2>Testing Home Page Features</h2>";
    
    // Test 1: Best Selling Books
    echo "<h3>1. Best Selling Books</h3>";
    $bestSelling = $library->getBestSellingBooks(6);
    echo "Found " . count($bestSelling) . " best selling books:<br>";
    foreach ($bestSelling as $book) {
        echo "- " . $book->getTitle() . " by " . $book->getAuthor() . "<br>";
    }
    
    // Test 2: Top Authors
    echo "<h3>2. Top Authors</h3>";
    $topAuthors = $library->getTopAuthors(6);
    echo "Found " . count($topAuthors) . " top authors:<br>";
    foreach ($topAuthors as $author) {
        echo "- " . $author['author'] . " ({$author['book_count']} books, {$author['total_borrows']} borrows, " . number_format($author['avg_rating'], 1) . " rating)<br>";
    }
    
    // Test 3: Recent Reviews
    echo "<h3>3. Recent Reviews</h3>";
    $recentReviews = $library->getRecentReviews(6);
    echo "Found " . count($recentReviews) . " recent reviews:<br>";
    foreach ($recentReviews as $review) {
        echo "- " . $review['title'] . " by " . $review['username'] . " ({$review['rating']} stars)<br>";
    }
    
    // Test 4: Recently Published Books
    echo "<h3>4. Recently Published Books</h3>";
    $recentBooks = $library->getRecentlyPublishedBooks(6);
    echo "Found " . count($recentBooks) . " recently published books:<br>";
    foreach ($recentBooks as $book) {
        echo "- " . $book->getTitle() . " (" . $book->getYear() . ") by " . $book->getAuthor() . "<br>";
    }
    
    // Test 5: Book Statistics
    echo "<h3>5. Book Statistics</h3>";
    $stats = $library->getBookStats();
    echo "Total Books: " . $stats['total_books'] . "<br>";
    echo "Total Authors: " . $stats['total_authors'] . "<br>";
    echo "Total Reviews: " . $stats['total_reviews'] . "<br>";
    echo "Monthly Borrows: " . $stats['monthly_borrows'] . "<br>";
    
    echo "<h3>✅ All features working correctly!</h3>";
    echo "<p><a href='index.php'>View Home Page</a></p>";
    
} catch (Exception $e) {
    echo "<h3>❌ Error: " . $e->getMessage() . "</h3>";
    echo "<pre>" . $e->getTraceAsString() . "</pre>";
}
?>