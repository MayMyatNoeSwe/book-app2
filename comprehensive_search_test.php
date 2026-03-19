<?php
// Comprehensive search test to identify the issue
require_once 'includes/sessions.php';
require_once 'vendor/autoload.php';
require_once 'includes/env_loader.php';
require_once 'includes/functions.php';

use App\Library;

$library = new Library();

echo "<h2>Comprehensive Search Test</h2>";

// Test 1: Check all books in database
echo "<h3>1. All Books in Database</h3>";
$allBooks = $library->getBooksPaginated(50, 0, null, null);
echo "Total books in database: " . count($allBooks) . "<br>";

echo "<strong>All books:</strong><br>";
foreach ($allBooks as $book) {
    echo "- " . $book->getTitle() . " by " . $book->getAuthor() . " (" . $book->getCategory() . ")<br>";
}

// Test 2: Search for specific terms
echo "<h3>2. Search Tests</h3>";

$searchTerms = ['php', 'harry', 'tolkien', 'great', '1984'];

foreach ($searchTerms as $term) {
    echo "<h4>Searching for: '$term'</h4>";
    
    $searchBooks = $library->getBooksPaginated(50, 0, null, $term);
    $searchCount = $library->countBooks(null, $term);
    
    echo "Count method result: $searchCount<br>";
    echo "Paginated method result: " . count($searchBooks) . "<br>";
    
    if (!empty($searchBooks)) {
        echo "<strong>Matching books:</strong><br>";
        foreach ($searchBooks as $book) {
            $title = $book->getTitle();
            $author = $book->getAuthor();
            
            // Check if title or author contains the search term
            $titleMatch = stripos($title, $term) !== false;
            $authorMatch = stripos($author, $term) !== false;
            
            $matchType = [];
            if ($titleMatch) $matchType[] = 'title';
            if ($authorMatch) $matchType[] = 'author';
            
            echo "- " . $title . " by " . $author . " (matches: " . implode(', ', $matchType) . ")<br>";
        }
    } else {
        echo "<em>No matches found</em><br>";
    }
    echo "<hr>";
}

// Test 3: Direct SQL query
echo "<h3>3. Direct SQL Test</h3>";
try {
    $pdo = $library->getPdo();
    
    $testTerm = 'php';
    $sql = "SELECT title, author FROM books WHERE (title LIKE ? OR author LIKE ?)";
    $stmt = $pdo->prepare($sql);
    $stmt->execute(["%$testTerm%", "%$testTerm%"]);
    
    echo "<strong>Direct SQL query for '$testTerm':</strong><br>";
    $sqlResults = $stmt->fetchAll();
    
    if (!empty($sqlResults)) {
        foreach ($sqlResults as $row) {
            echo "- " . $row['title'] . " by " . $row['author'] . "<br>";
        }
    } else {
        echo "<em>No SQL results</em><br>";
    }
    
} catch (Exception $e) {
    echo "SQL Error: " . $e->getMessage() . "<br>";
}

// Test 4: Check if the issue is with the home page display
echo "<h3>4. Home Page Logic Test</h3>";

// Simulate the home page logic
$testSearch = 'php';
$testCategory = null;

echo "Testing with search='$testSearch', category=" . ($testCategory ?: 'null') . "<br>";

$homePageBooks = $library->getBooksPaginated(12, 0, $testCategory, $testSearch);
$homePageCount = $library->countBooks($testCategory, $testSearch);

echo "Home page would show: " . count($homePageBooks) . " books<br>";
echo "Total count: $homePageCount<br>";

if (!empty($homePageBooks)) {
    echo "<strong>Books that would appear on home page:</strong><br>";
    foreach ($homePageBooks as $book) {
        echo "- " . $book->getTitle() . " by " . $book->getAuthor() . "<br>";
    }
}

echo "<h3>Conclusion</h3>";
echo "<p>If you see non-matching books in the results above, then there's an issue with the search logic.</p>";
echo "<p>If the results look correct here but wrong on the home page, then there's an issue with the display logic.</p>";

echo "<p><a href='test_search_simple.php'>Try Simple Search Test</a></p>";
echo "<p><a href='index.php'>Back to Home Page</a></p>";
?>