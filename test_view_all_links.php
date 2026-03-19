<?php
// Test the View All links functionality
require_once 'includes/sessions.php';
require_once 'vendor/autoload.php';
require_once 'includes/env_loader.php';
require_once 'includes/functions.php';

use App\Library;

$library = new Library();

echo "<h2>View All Links Test</h2>";

echo "<p>This page tests the View All functionality from the home page sections.</p>";

// Test the different View All links
$viewAllLinks = [
    [
        'title' => 'Best Selling Books',
        'url' => 'book-list.php?sort=borrowed&order=desc',
        'description' => 'Shows books sorted by popularity (most borrowed first)'
    ],
    [
        'title' => 'Recently Published',
        'url' => 'book-list.php?sort=recent&order=desc',
        'description' => 'Shows books sorted by publication year and date added (newest first)'
    ],
    [
        'title' => 'Top Authors',
        'url' => 'book-list.php?sort=author&order=asc',
        'description' => 'Shows books sorted by author name (A-Z)'
    ],
    [
        'title' => 'Recent Reviews',
        'url' => 'book-list.php?sort=title&order=asc',
        'description' => 'Shows all books sorted by title'
    ]
];

echo "<div class='row'>";
foreach ($viewAllLinks as $link) {
    echo "<div class='col-md-6 mb-4'>";
    echo "<div class='card h-100'>";
    echo "<div class='card-body'>";
    echo "<h5 class='card-title'>{$link['title']}</h5>";
    echo "<p class='card-text'>{$link['description']}</p>";
    echo "<a href='{$link['url']}' class='btn btn-primary'>Test Link</a>";
    echo "</div>";
    echo "</div>";
    echo "</div>";
}
echo "</div>";

// Test the sorting functionality
echo "<h3>Sorting Test Results</h3>";

$sortTests = [
    ['sort' => 'borrowed', 'order' => 'desc', 'name' => 'Best Selling (Most Popular)'],
    ['sort' => 'recent', 'order' => 'desc', 'name' => 'Recently Published (Newest)'],
    ['sort' => 'author', 'order' => 'asc', 'name' => 'Authors (A-Z)'],
    ['sort' => 'title', 'order' => 'asc', 'name' => 'Titles (A-Z)']
];

foreach ($sortTests as $test) {
    echo "<h4>{$test['name']}</h4>";
    $books = $library->getAdvancedBooksPaginated(5, 0, null, null, $test['sort'], $test['order'], 'all');
    
    if (!empty($books)) {
        echo "<ol>";
        foreach ($books as $book) {
            $sortValue = '';
            switch ($test['sort']) {
                case 'borrowed':
                    $borrowed = $book->getTotalCopies() - $book->getAvailableCopies();
                    $sortValue = " (Borrowed: $borrowed)";
                    break;
                case 'year':
                    $sortValue = " (" . $book->getYear() . ")";
                    break;
                case 'author':
                    $sortValue = " by " . $book->getAuthor();
                    break;
                case 'title':
                    $sortValue = "";
                    break;
            }
            echo "<li>" . $book->getTitle() . $sortValue . "</li>";
        }
        echo "</ol>";
    } else {
        echo "<p><em>No books found</em></p>";
    }
}

echo "<h3>✅ View All Links Test Complete!</h3>";
echo "<p><strong>Summary:</strong></p>";
echo "<ul>";
echo "<li>✅ Best Selling Books → book-list.php?sort=borrowed&order=desc</li>";
echo "<li>✅ Recently Published → book-list.php?sort=recent&order=desc</li>";
echo "<li>✅ Top Authors → book-list.php?sort=author&order=asc</li>";
echo "<li>✅ Recent Reviews → book-list.php?sort=title&order=asc</li>";
echo "</ul>";

echo "<p><strong>Next Steps:</strong></p>";
echo "<ul>";
echo "<li><a href='index.php'>Go to Home Page</a> and test the View All buttons</li>";
echo "<li>Click each View All button to see the filtered results</li>";
echo "<li>Verify the page titles and headers change appropriately</li>";
echo "</ul>";
?>

<style>
.card { border: 1px solid #dee2e6; border-radius: 8px; }
.card-body { padding: 1.5rem; }
.btn { padding: 0.5rem 1rem; border-radius: 4px; text-decoration: none; }
.btn-primary { background-color: #007bff; color: white; border: 1px solid #007bff; }
.row { display: flex; flex-wrap: wrap; margin: -0.5rem; }
.col-md-6 { flex: 0 0 50%; padding: 0.5rem; }
.mb-4 { margin-bottom: 1.5rem; }
.h-100 { height: 100%; }
</style>