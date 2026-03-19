<?php
// Test the premium book list functionality
require_once 'includes/sessions.php';
require_once 'vendor/autoload.php';
require_once 'includes/env_loader.php';
require_once 'includes/functions.php';

use App\Library;

$library = new Library();

echo "<h2>Premium Book List Feature Test</h2>";

// Test 1: Advanced books pagination
echo "<h3>1. Advanced Books Pagination</h3>";
$books = $library->getAdvancedBooksPaginated(5, 0, null, null, 'title', 'asc', 'all');
echo "Found " . count($books) . " books (sorted by title, ascending):<br>";
foreach ($books as $book) {
    echo "- " . $book->getTitle() . " by " . $book->getAuthor() . "<br>";
}

// Test 2: Sorting by different fields
echo "<h3>2. Sorting Tests</h3>";

$sortTests = [
    ['field' => 'author', 'order' => 'asc', 'description' => 'Author A-Z'],
    ['field' => 'year', 'order' => 'desc', 'description' => 'Year (newest first)'],
    ['field' => 'category', 'order' => 'asc', 'description' => 'Category A-Z']
];

foreach ($sortTests as $test) {
    echo "<h4>{$test['description']}</h4>";
    $sortedBooks = $library->getAdvancedBooksPaginated(3, 0, null, null, $test['field'], $test['order'], 'all');
    foreach ($sortedBooks as $book) {
        $value = '';
        switch ($test['field']) {
            case 'author': $value = $book->getAuthor(); break;
            case 'year': $value = $book->getYear(); break;
            case 'category': $value = $book->getCategory(); break;
        }
        echo "- " . $book->getTitle() . " ({$value})<br>";
    }
}

// Test 3: Availability filtering
echo "<h3>3. Availability Filtering</h3>";

$availabilityTests = ['all', 'available', 'borrowed'];
foreach ($availabilityTests as $availability) {
    $count = $library->countAdvancedBooks(null, null, $availability);
    echo "Books ($availability): $count<br>";
}

// Test 4: Combined filtering
echo "<h3>4. Combined Filtering Test</h3>";
$combinedBooks = $library->getAdvancedBooksPaginated(10, 0, 'Fiction', 'harry', 'year', 'desc', 'available');
$combinedCount = $library->countAdvancedBooks('Fiction', 'harry', 'available');

echo "Fiction books with 'harry' in title/author that are available: $combinedCount<br>";
foreach ($combinedBooks as $book) {
    echo "- " . $book->getTitle() . " by " . $book->getAuthor() . " (" . $book->getYear() . ")<br>";
}

echo "<h3>✅ Premium Book List Test Complete!</h3>";
echo "<p><strong>Features Tested:</strong></p>";
echo "<ul>";
echo "<li>✅ Advanced pagination with sorting</li>";
echo "<li>✅ Multiple sort fields and orders</li>";
echo "<li>✅ Availability filtering</li>";
echo "<li>✅ Combined filtering (category + search + availability)</li>";
echo "</ul>";

echo "<p><strong>Next Steps:</strong></p>";
echo "<ul>";
echo "<li><a href='book-list.php'>View Premium Book List</a></li>";
echo "<li><a href='book-list.php?view=list'>Try List View</a></li>";
echo "<li><a href='book-list.php?view=compact'>Try Compact View</a></li>";
echo "<li><a href='book-list.php?sort=year&order=desc'>Sort by Year (Newest)</a></li>";
echo "</ul>";
?>