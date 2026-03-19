<?php
require_once 'includes/functions.php';

// Test the dummy cover function
echo "<h1>Testing Dummy Book Covers</h1>";

$testBooks = [
    ['title' => 'နတ်ဘုရားမျက်စိ', 'author' => 'ဦးမျ'],
    ['title' => 'ဖြေတော့၏ အချိန်', 'author' => 'မမြို့'],
    ['title' => 'ရန်ကုန်မြို့၏ ညဥ့်ဘက်', 'author' => 'မလှ'],
    ['title' => 'လူသားမြေတောမှ', 'author' => 'ဖလှ'],
];

echo "<div style='display: flex; gap: 20px; flex-wrap: wrap;'>";

foreach ($testBooks as $book) {
    $dummyUrl = getDummyBookCover($book['title'], $book['author'], 200, 300);
    
    echo "<div style='border: 1px solid #ccc; padding: 10px; text-align: center;'>";
    echo "<h3>{$book['title']}</h3>";
    echo "<p>by {$book['author']}</p>";
    echo "<img src='{$dummyUrl}' alt='Cover' style='max-width: 200px;'><br>";
    echo "<small style='word-break: break-all;'>{$dummyUrl}</small>";
    echo "</div>";
}

echo "</div>";

echo "<hr>";
echo "<h2>Testing getBookCoverUrl with non-existent file</h2>";

// Simulate a book object
class TestBook {
    private $title;
    private $author;
    private $coverImage;
    
    public function __construct($title, $author, $coverImage = null) {
        $this->title = $title;
        $this->author = $author;
        $this->coverImage = $coverImage;
    }
    
    public function getTitle() { return $this->title; }
    public function getAuthor() { return $this->author; }
    public function getCoverImage() { return $this->coverImage; }
}

$testBook = new TestBook('Test Book Title', 'Test Author', 'nonexistent.jpg');
$coverUrl = getBookCoverUrl($testBook, $testBook->getTitle(), $testBook->getAuthor());

echo "<div style='border: 1px solid #ccc; padding: 10px; max-width: 400px;'>";
echo "<h3>Book with non-existent cover file</h3>";
echo "<p>Cover Image in DB: nonexistent.jpg</p>";
echo "<img src='{$coverUrl}' alt='Cover' style='max-width: 200px;'><br>";
echo "<small style='word-break: break-all;'>{$coverUrl}</small>";
echo "</div>";
?>
