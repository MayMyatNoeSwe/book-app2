<?php
require_once 'includes/sessions.php';
require_once 'vendor/autoload.php';
require_once 'includes/env_loader.php';
require_once 'includes/functions.php';

use App\Library;

$library = new Library();

// Get some books
$books = $library->getAllBooks(20);

echo "<h1>Book Cover Status Check</h1>";
echo "<table border='1' cellpadding='10' style='border-collapse: collapse; width: 100%;'>";
echo "<tr>
        <th>Title</th>
        <th>Author</th>
        <th>Cover Image (DB)</th>
        <th>File Exists?</th>
        <th>getBookCoverUrl Result</th>
        <th>Preview</th>
      </tr>";

foreach ($books as $book) {
    $coverImage = $book->getCoverImage();
    $filePath = __DIR__ . '/public/uploads/covers/' . $coverImage;
    $fileExists = $coverImage && file_exists($filePath) ? 'YES' : 'NO';
    $coverUrl = getBookCoverUrl($book, $book->getTitle(), $book->getAuthor());
    
    echo "<tr>";
    echo "<td>" . htmlspecialchars($book->getTitle()) . "</td>";
    echo "<td>" . htmlspecialchars($book->getAuthor()) . "</td>";
    echo "<td>" . htmlspecialchars($coverImage ?: '(empty)') . "</td>";
    echo "<td style='color: " . ($fileExists === 'YES' ? 'green' : 'red') . ";'><strong>{$fileExists}</strong></td>";
    echo "<td style='font-size: 10px; word-break: break-all;'>" . htmlspecialchars($coverUrl) . "</td>";
    echo "<td><img src='{$coverUrl}' style='width: 80px; height: 120px; object-fit: cover;'></td>";
    echo "</tr>";
}

echo "</table>";

echo "<hr>";
echo "<h2>APP_ROOT: " . APP_ROOT . "</h2>";
echo "<h2>Covers Directory: " . APP_ROOT . '/public/uploads/covers/' . "</h2>";
echo "<h2>Directory Exists: " . (is_dir(APP_ROOT . '/public/uploads/covers/') ? 'YES' : 'NO') . "</h2>";
?>
