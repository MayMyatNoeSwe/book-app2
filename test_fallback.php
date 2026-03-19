<?php
// Test the fallback functionality without authors table
require_once 'includes/sessions.php';
require_once 'vendor/autoload.php';
require_once 'includes/env_loader.php';
require_once 'includes/functions.php';

use App\Library;

try {
    $library = new Library();
    
    echo "<h2>Testing Fallback Functionality</h2>";
    
    // Test getTopAuthors without authors table
    echo "<h3>Testing getTopAuthors() method</h3>";
    $topAuthors = $library->getTopAuthors(5);
    
    if (!empty($topAuthors)) {
        echo "✅ Method works! Found " . count($topAuthors) . " authors:<br><br>";
        
        foreach ($topAuthors as $author) {
            echo "<div style='border: 1px solid #ddd; padding: 10px; margin: 5px 0;'>";
            echo "<strong>" . htmlspecialchars($author['author']) . "</strong><br>";
            echo "Books: " . $author['book_count'] . " | ";
            echo "Borrows: " . $author['total_borrows'] . " | ";
            echo "Rating: " . number_format($author['avg_rating'], 1) . "<br>";
            
            // Check photo and bio fields
            $photo = $author['author_photo'] ?? 'NULL';
            $bio = $author['author_bio'] ?? 'NULL';
            echo "Photo: " . ($photo ?: 'NULL') . " | Bio: " . ($bio ?: 'NULL') . "<br>";
            echo "</div>";
        }
    } else {
        echo "⚠️ No authors found. Make sure you have books and borrowing data.";
    }
    
    echo "<h3>Next Steps</h3>";
    echo "<p>The system is working with dummy images. To enable real author photos:</p>";
    echo "<ol>";
    echo "<li><a href='setup_authors_table.php'>Run Setup Authors Table</a></li>";
    echo "<li><a href='index.php'>View Home Page</a></li>";
    echo "</ol>";
    
} catch (Exception $e) {
    echo "<h3>❌ Error: " . $e->getMessage() . "</h3>";
    echo "<p>Please check the error and try again.</p>";
}
?>