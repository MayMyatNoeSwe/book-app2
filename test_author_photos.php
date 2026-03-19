<?php
// Test author photos feature
require_once 'includes/sessions.php';
require_once 'vendor/autoload.php';
require_once 'includes/env_loader.php';
require_once 'includes/functions.php';

use App\Library;

try {
    $library = new Library();
    
    echo "<h2>Testing Author Photos Feature</h2>";
    
    // Test the updated getTopAuthors method
    echo "<h3>1. Top Authors with Photo Data</h3>";
    $topAuthors = $library->getTopAuthors(10);
    echo "Found " . count($topAuthors) . " authors:<br><br>";
    
    foreach ($topAuthors as $author) {
        echo "<div style='border: 1px solid #ddd; padding: 15px; margin: 10px 0; border-radius: 8px;'>";
        echo "<strong>" . htmlspecialchars($author['author']) . "</strong><br>";
        echo "Books: " . $author['book_count'] . " | ";
        echo "Borrows: " . $author['total_borrows'] . " | ";
        echo "Rating: " . number_format($author['avg_rating'], 1) . "<br>";
        
        // Check photo
        $authorPhoto = $author['author_photo'] ?? null;
        if ($authorPhoto) {
            $photoPath = __DIR__ . '/public/uploads/authors/' . $authorPhoto;
            if (file_exists($photoPath)) {
                echo "📸 Photo: <span style='color: green;'>✅ " . $authorPhoto . " (exists)</span><br>";
            } else {
                echo "📸 Photo: <span style='color: orange;'>⚠️ " . $authorPhoto . " (missing - will use dummy)</span><br>";
            }
        } else {
            echo "📸 Photo: <span style='color: blue;'>🔄 No photo set (will use dummy)</span><br>";
        }
        
        // Show bio if available
        if (!empty($author['author_bio'])) {
            echo "📝 Bio: " . htmlspecialchars(substr($author['author_bio'], 0, 100)) . "...<br>";
        }
        
        // Generate dummy image URLs for testing
        $authorName = $author['author'];
        $dummyImageUrl = "https://ui-avatars.com/api/?name=" . urlencode($authorName) . "&size=120&background=random&color=fff&bold=true";
        $fallbackUrl = "https://via.placeholder.com/120x120/6c757d/ffffff?text=" . urlencode(substr($authorName, 0, 2));
        
        echo "🔗 Dummy URL: <a href='" . $dummyImageUrl . "' target='_blank'>UI Avatar</a> | ";
        echo "<a href='" . $fallbackUrl . "' target='_blank'>Fallback</a><br>";
        
        echo "</div>";
    }
    
    // Check authors directory
    echo "<h3>2. Authors Directory Status</h3>";
    $authorsDir = __DIR__ . '/public/uploads/authors/';
    if (is_dir($authorsDir)) {
        echo "✅ Authors directory exists<br>";
        $files = scandir($authorsDir);
        $imageFiles = array_filter($files, function($file) {
            return in_array(strtolower(pathinfo($file, PATHINFO_EXTENSION)), ['jpg', 'jpeg', 'png', 'gif']);
        });
        
        if (count($imageFiles) > 0) {
            echo "📸 Found " . count($imageFiles) . " image files:<br>";
            foreach ($imageFiles as $file) {
                echo "- " . $file . "<br>";
            }
        } else {
            echo "📁 No image files found (dummy images will be used)<br>";
        }
    } else {
        echo "❌ Authors directory does not exist<br>";
    }
    
    echo "<h3>3. Database Authors Table</h3>";
    $pdo = $library->getPdo();
    
    try {
        $stmt = $pdo->query("SELECT COUNT(*) FROM authors");
        $authorCount = $stmt->fetchColumn();
        echo "✅ Authors table exists with " . $authorCount . " records<br>";
        
        if ($authorCount > 0) {
            $stmt = $pdo->query("SELECT name, photo, bio FROM authors LIMIT 5");
            echo "<strong>Sample authors:</strong><br>";
            while ($row = $stmt->fetch()) {
                echo "- " . htmlspecialchars($row['name']) . " (photo: " . ($row['photo'] ?: 'none') . ")<br>";
            }
        }
    } catch (Exception $e) {
        echo "❌ Authors table does not exist or error: " . $e->getMessage() . "<br>";
        echo "💡 Run add_sample_data.php to create the table and add sample data<br>";
    }
    
    echo "<h3>✅ Author Photos Feature Test Complete!</h3>";
    echo "<p><strong>Next Steps:</strong></p>";
    echo "<ul>";
    echo "<li><a href='add_sample_data.php'>Add Sample Data</a> (creates authors table and sample data)</li>";
    echo "<li><a href='download_author_photos.php'>Download Sample Photos</a> (optional - downloads real photos)</li>";
    echo "<li><a href='index.php'>View Home Page</a> (see the results)</li>";
    echo "</ul>";
    
} catch (Exception $e) {
    echo "<h3>❌ Error: " . $e->getMessage() . "</h3>";
    echo "<pre>" . $e->getTraceAsString() . "</pre>";
}
?>