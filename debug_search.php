<?php
// Debug search functionality
require_once 'includes/sessions.php';
require_once 'vendor/autoload.php';
require_once 'includes/env_loader.php';
require_once 'includes/functions.php';

use App\Library;

$library = new Library();

echo "<h2>Search Functionality Debug</h2>";

// Test different search scenarios
$testSearches = [
    'php' => 'Search for "php"',
    'harry' => 'Search for "harry"',
    'tolkien' => 'Search for "tolkien"',
    'fiction' => 'Search for "fiction"',
    'nonexistent' => 'Search for "nonexistent"'
];

foreach ($testSearches as $searchTerm => $description) {
    echo "<h3>$description</h3>";
    
    // Test the search
    $books = $library->getBooksPaginated(10, 0, null, $searchTerm);
    $count = $library->countBooks(null, $searchTerm);
    
    echo "Total matching books: $count<br>";
    echo "Books returned: " . count($books) . "<br>";
    
    if (!empty($books)) {
        echo "<strong>Results:</strong><br>";
        foreach ($books as $book) {
            $title = $book->getTitle();
            $author = $book->getAuthor();
            
            // Highlight matching terms
            $highlightedTitle = str_ireplace($searchTerm, "<mark>$searchTerm</mark>", $title);
            $highlightedAuthor = str_ireplace($searchTerm, "<mark>$searchTerm</mark>", $author);
            
            echo "- $highlightedTitle by $highlightedAuthor<br>";
        }
    } else {
        echo "<em>No results found</em><br>";
    }
    
    echo "<hr>";
}

// Test the API endpoint directly
echo "<h3>API Endpoint Test</h3>";
echo "<p>Testing the API endpoint that infinite scroll uses:</p>";

$testApiUrl = "api/load_books.php?page=1&q=php";
echo "<a href='$testApiUrl' target='_blank'>Test API: $testApiUrl</a><br>";

// Test current URL parameters
echo "<h3>Current URL Parameters</h3>";
$currentCategory = $_GET['cat'] ?? 'none';
$currentSearch = $_GET['q'] ?? 'none';

echo "Category: $currentCategory<br>";
echo "Search: $currentSearch<br>";

if ($currentSearch !== 'none') {
    echo "<h4>Testing current search: '$currentSearch'</h4>";
    $currentBooks = $library->getBooksPaginated(10, 0, $currentCategory !== 'none' ? $currentCategory : null, $currentSearch);
    echo "Found " . count($currentBooks) . " books for current search<br>";
    
    foreach ($currentBooks as $book) {
        echo "- " . $book->getTitle() . " by " . $book->getAuthor() . "<br>";
    }
}

echo "<h3>Database Query Test</h3>";
echo "<p>Let's test the raw SQL query:</p>";

try {
    $pdo = $library->getPdo();
    $testSearch = 'php';
    
    $sql = "SELECT title, author FROM books WHERE (title LIKE ? OR author LIKE ?) LIMIT 5";
    $stmt = $pdo->prepare($sql);
    $stmt->execute(["%$testSearch%", "%$testSearch%"]);
    
    echo "<strong>Raw SQL results for '$testSearch':</strong><br>";
    while ($row = $stmt->fetch()) {
        echo "- " . $row['title'] . " by " . $row['author'] . "<br>";
    }
    
} catch (Exception $e) {
    echo "SQL Error: " . $e->getMessage();
}
?>