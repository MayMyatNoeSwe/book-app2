<?php
require_once 'vendor/autoload.php';
require_once 'includes/env_loader.php';

use App\Library;

$library = new Library();

echo "<h2>Debug: Popular Books</h2>";

// Check if borrowing_history table exists
try {
    $pdo = new PDO(
        "mysql:host=" . $_ENV['DB_HOST'] . ";dbname=" . $_ENV['DB_NAME'],
        $_ENV['DB_USER'],
        $_ENV['DB_PASS']
    );
    
    // Check borrowing_history table
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM borrowing_history");
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    echo "<p>Borrowing history records: " . $result['count'] . "</p>";
    
    // Get best selling books
    $bestSellingBooks = $library->getBestSellingBooks(10);
    echo "<p>Best selling books found: " . count($bestSellingBooks) . "</p>";
    
    if (!empty($bestSellingBooks)) {
        echo "<h3>Books:</h3>";
        echo "<ul>";
        foreach ($bestSellingBooks as $book) {
            echo "<li>" . htmlspecialchars($book->getTitle()) . " by " . htmlspecialchars($book->getAuthor()) . "</li>";
        }
        echo "</ul>";
    } else {
        echo "<p style='color: red;'>No books found! This might be why the section is empty.</p>";
        
        // Check if there are any books at all
        $stmt = $pdo->query("SELECT COUNT(*) as count FROM books");
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        echo "<p>Total books in database: " . $result['count'] . "</p>";
    }
    
} catch (Exception $e) {
    echo "<p style='color: red;'>Error: " . $e->getMessage() . "</p>";
}
