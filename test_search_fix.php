<?php
// Test the search fix
require_once 'includes/sessions.php';
require_once 'vendor/autoload.php';
require_once 'includes/env_loader.php';
require_once 'includes/functions.php';

use App\Library;

$library = new Library();

echo "<h2>Search Fix Verification</h2>";

// Test various search scenarios
$testCases = [
    ['search' => 'php', 'description' => 'Search for "php"'],
    ['search' => 'harry', 'description' => 'Search for "harry"'],
    ['search' => 'tolkien', 'description' => 'Search for "tolkien"'],
    ['search' => 'nonexistent', 'description' => 'Search for non-existent term'],
    ['search' => '', 'description' => 'Empty search'],
    ['search' => '   ', 'description' => 'Whitespace-only search'],
];

foreach ($testCases as $test) {
    echo "<h3>{$test['description']}</h3>";
    
    $searchTerm = $test['search'];
    $books = $library->getBooksPaginated(10, 0, null, $searchTerm);
    $count = $library->countBooks(null, $searchTerm);
    
    echo "<div style='background: #f8f9fa; padding: 10px; margin: 10px 0; border-radius: 5px;'>";
    echo "<strong>Search term:</strong> '" . htmlspecialchars($searchTerm) . "'<br>";
    echo "<strong>Trimmed search:</strong> '" . htmlspecialchars(trim($searchTerm)) . "'<br>";
    echo "<strong>Is empty after trim:</strong> " . (trim($searchTerm) === '' ? 'YES' : 'NO') . "<br>";
    echo "<strong>Total count:</strong> $count<br>";
    echo "<strong>Books returned:</strong> " . count($books) . "<br>";
    echo "</div>";
    
    if (!empty($books)) {
        echo "<strong>Results:</strong><br>";
        foreach ($books as $book) {
            $title = $book->getTitle();
            $author = $book->getAuthor();
            
            // Check if this book actually matches the search
            $titleMatch = stripos($title, trim($searchTerm)) !== false;
            $authorMatch = stripos($author, trim($searchTerm)) !== false;
            $shouldMatch = trim($searchTerm) !== '';
            
            $style = '';
            if (!$shouldMatch) {
                $style = 'style="background: #e7f3ff; padding: 5px;"'; // No search term
            } elseif ($titleMatch || $authorMatch) {
                $style = 'style="background: #d4edda; padding: 5px;"'; // Correct match
            } else {
                $style = 'style="background: #f8d7da; padding: 5px;"'; // Incorrect match
            }
            
            echo "<div $style>";
            echo "- <strong>$title</strong> by $author";
            
            if ($shouldMatch) {
                if ($titleMatch) echo " [TITLE MATCH]";
                if ($authorMatch) echo " [AUTHOR MATCH]";
                if (!$titleMatch && !$authorMatch) echo " [❌ NO MATCH - BUG!]";
            }
            
            echo "</div>";
        }
    } else {
        echo "<em>No results</em><br>";
    }
    
    echo "<hr>";
}

// Test API endpoint
echo "<h3>API Endpoint Test</h3>";
echo "<p>Test the API endpoint directly:</p>";
echo "<ul>";
echo "<li><a href='api/load_books.php?page=1&q=php' target='_blank'>API: Search for 'php'</a></li>";
echo "<li><a href='api/load_books.php?page=1&q=harry' target='_blank'>API: Search for 'harry'</a></li>";
echo "<li><a href='api/load_books.php?page=1&q=' target='_blank'>API: Empty search</a></li>";
echo "</ul>";

echo "<h3>✅ Search Fix Test Complete</h3>";
echo "<p><strong>What to look for:</strong></p>";
echo "<ul>";
echo "<li>Green background = Correct matches</li>";
echo "<li>Red background = Incorrect matches (these are bugs)</li>";
echo "<li>Blue background = No search term (should show all books)</li>";
echo "</ul>";

echo "<p><a href='index.php'>Test on Home Page</a></p>";
?>