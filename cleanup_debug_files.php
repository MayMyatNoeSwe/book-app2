<?php
// Cleanup debug files
echo "<h2>Cleanup Debug Files</h2>";

$debugFiles = [
    'debug_index.php',
    'comprehensive_search_test.php',
    'test_search_simple.php',
    'debug_search.php',
    'test_search_fix.php',
    'cleanup_debug_files.php'
];

foreach ($debugFiles as $file) {
    if (file_exists($file)) {
        if (unlink($file)) {
            echo "✅ Deleted: $file<br>";
        } else {
            echo "❌ Failed to delete: $file<br>";
        }
    } else {
        echo "⏭️ Not found: $file<br>";
    }
}

echo "<h3>✅ Cleanup Complete</h3>";
echo "<p><a href='index.php'>Back to Home Page</a></p>";
?>