<?php
// Debug version of index.php to see what's happening
$pageTitle = "Debug Home";
require_once 'includes/sessions.php';
require_once 'vendor/autoload.php';
require_once 'includes/env_loader.php';
require_once 'includes/functions.php';

use App\Auth;
use App\Library;

$library = new Library();

// Pagination Settings
$page = 1;
$limit = 12;
$offset = 0;

// Filter Parameters
$category = $_GET['cat'] ?? null;
$search = $_GET['q'] ?? null;

// Clean and validate search parameter
if ($search !== null) {
    $search = trim($search);
    if ($search === '') {
        $search = null;
    }
}

// Validate Category
if ($category && !in_array($category, getCategories())) {
    $category = null;
}

echo "<h2>Debug Information</h2>";
echo "<div style='background: #f8f9fa; padding: 15px; margin: 15px 0; border-radius: 5px;'>";
echo "<strong>Parameters:</strong><br>";
echo "Category: " . ($category ?: 'NULL') . "<br>";
echo "Search: " . ($search ?: 'NULL') . "<br>";
echo "Search Length: " . ($search ? strlen($search) : '0') . "<br>";
echo "</div>";

// Fetch Initial Batch
$books = $library->getBooksPaginated($limit, $offset, $category, $search);
$totalBooks = $library->countBooks($category, $search);

echo "<div style='background: #e7f3ff; padding: 15px; margin: 15px 0; border-radius: 5px;'>";
echo "<strong>Query Results:</strong><br>";
echo "Books returned: " . count($books) . "<br>";
echo "Total books count: " . $totalBooks . "<br>";
echo "</div>";

// Show the actual books
if (!empty($books)) {
    echo "<h3>Books Retrieved:</h3>";
    echo "<ol>";
    foreach ($books as $book) {
        $title = $book->getTitle();
        $author = $book->getAuthor();
        
        // Highlight search terms if searching
        if ($search) {
            $titleMatch = stripos($title, $search) !== false;
            $authorMatch = stripos($author, $search) !== false;
            
            $style = '';
            if ($titleMatch || $authorMatch) {
                $style = 'style="background: #d4edda; padding: 5px;"';
            } else {
                $style = 'style="background: #f8d7da; padding: 5px;"';
            }
            
            echo "<li $style>";
            echo "<strong>$title</strong> by $author (" . $book->getCategory() . ")";
            
            if ($titleMatch) echo " [TITLE MATCH]";
            if ($authorMatch) echo " [AUTHOR MATCH]";
            if (!$titleMatch && !$authorMatch) echo " [NO MATCH - WHY IS THIS HERE?]";
            
            echo "</li>";
        } else {
            echo "<li><strong>$title</strong> by $author (" . $book->getCategory() . ")</li>";
        }
    }
    echo "</ol>";
} else {
    echo "<p><em>No books found.</em></p>";
}

// Test the search functionality directly
if ($search) {
    echo "<h3>Direct Search Test</h3>";
    
    try {
        $pdo = $library->getPdo();
        $sql = "SELECT title, author, category FROM books WHERE (title LIKE ? OR author LIKE ?) ORDER BY title LIMIT 20";
        $stmt = $pdo->prepare($sql);
        $stmt->execute(["%$search%", "%$search%"]);
        
        $directResults = $stmt->fetchAll();
        
        echo "<div style='background: #fff3cd; padding: 15px; margin: 15px 0; border-radius: 5px;'>";
        echo "<strong>Direct SQL Results for '$search':</strong><br>";
        echo "Found: " . count($directResults) . " matches<br>";
        
        if (!empty($directResults)) {
            echo "<ol>";
            foreach ($directResults as $row) {
                echo "<li>" . $row['title'] . " by " . $row['author'] . " (" . $row['category'] . ")</li>";
            }
            echo "</ol>";
        }
        echo "</div>";
        
    } catch (Exception $e) {
        echo "<div style='background: #f8d7da; padding: 15px; margin: 15px 0; border-radius: 5px;'>";
        echo "SQL Error: " . $e->getMessage();
        echo "</div>";
    }
}

echo "<hr>";
echo "<h3>Test Search</h3>";
echo "<form method='GET'>";
echo "<input type='text' name='q' value='" . htmlspecialchars($search ?: '') . "' placeholder='Search term'>";
echo "<button type='submit'>Search</button>";
echo "<a href='debug_index.php' style='margin-left: 10px;'>Clear</a>";
echo "</form>";

echo "<p><a href='index.php'>← Back to Normal Home Page</a></p>";
?>