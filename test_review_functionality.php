<?php
/**
 * Test Review Edit/Delete Functionality
 * 
 * This script tests the review edit/delete functionality across multiple books
 */

require_once 'includes/sessions.php';
require_once 'vendor/autoload.php';
require_once 'includes/env_loader.php';

use App\Auth;
use App\Library;

// Check if user is logged in
if (!Auth::check()) {
    die("Please login first to test review functionality");
}

$library = new Library();
$userId = Auth::id();

echo "<h1>Review Functionality Test</h1>";
echo "<p>Testing review edit/delete across multiple books</p>";
echo "<hr>";

// Get all books with reviews
$stmt = $library->getPdo()->prepare("
    SELECT DISTINCT b.id, b.title, b.author, COUNT(r.id) as review_count
    FROM books b
    LEFT JOIN reviews r ON b.id = r.book_id
    GROUP BY b.id
    HAVING review_count > 0
    LIMIT 5
");
$stmt->execute();
$booksWithReviews = $stmt->fetchAll();

echo "<h2>Books with Reviews:</h2>";
foreach ($booksWithReviews as $book) {
    echo "<div style='margin: 20px 0; padding: 15px; border: 1px solid #ddd; border-radius: 8px;'>";
    echo "<h3>{$book['title']} by {$book['author']}</h3>";
    echo "<p>Total Reviews: {$book['review_count']}</p>";
    
    // Get reviews for this book
    $reviews = $library->getReviews($book['id']);
    
    echo "<h4>Reviews:</h4>";
    foreach ($reviews as $review) {
        $isOwner = ($review['user_id'] == $userId);
        $ownerBadge = $isOwner ? " <span style='color: green;'>(Your Review)</span>" : "";
        
        echo "<div style='margin: 10px 0; padding: 10px; background: #f8f9fa; border-radius: 5px;'>";
        echo "<strong>{$review['username']}</strong>{$ownerBadge}<br>";
        echo "Rating: " . str_repeat("⭐", $review['rating']) . "<br>";
        echo "Review: {$review['review_text']}<br>";
        echo "Posted: {$review['created_at']}<br>";
        
        if ($isOwner) {
            echo "<a href='book-details.php?id={$book['id']}' style='color: blue;'>View & Edit/Delete</a>";
        }
        
        echo "</div>";
    }
    
    echo "<a href='book-details.php?id={$book['id']}' class='btn btn-primary' style='display: inline-block; padding: 8px 16px; background: #007bff; color: white; text-decoration: none; border-radius: 5px; margin-top: 10px;'>Go to Book Details</a>";
    echo "</div>";
}

echo "<hr>";
echo "<h2>Test Instructions:</h2>";
echo "<ol>";
echo "<li>Click on a book above to go to its details page</li>";
echo "<li>Try editing your review (if you have one)</li>";
echo "<li>Try deleting your review</li>";
echo "<li>Navigate to another book and repeat</li>";
echo "<li>Verify that changes persist across page navigations</li>";
echo "</ol>";

echo "<hr>";
echo "<h2>Expected Behavior:</h2>";
echo "<ul>";
echo "<li>✓ Edit button should only appear on YOUR reviews</li>";
echo "<li>✓ Delete button should only appear on YOUR reviews</li>";
echo "<li>✓ After deleting, page should reload and review should be gone</li>";
echo "<li>✓ After editing, page should reload and show updated review</li>";
echo "<li>✓ Star rating should work left-to-right (1-5 stars)</li>";
echo "<li>✓ Changes should persist when navigating between books</li>";
echo "</ul>";
?>
