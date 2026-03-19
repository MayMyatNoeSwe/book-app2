<?php
// Check database status and tables
require_once 'includes/sessions.php';
require_once 'vendor/autoload.php';
require_once 'includes/env_loader.php';

use App\Library;

try {
    $library = new Library();
    $pdo = $library->getPdo();
    
    echo "<h2>Database Status Check</h2>";
    
    // Check all tables
    $tables = ['users', 'books', 'borrowing_history', 'reviews', 'reservations', 'authors'];
    
    echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
    echo "<tr><th>Table</th><th>Status</th><th>Records</th><th>Action</th></tr>";
    
    foreach ($tables as $table) {
        echo "<tr>";
        echo "<td><strong>$table</strong></td>";
        
        try {
            $stmt = $pdo->query("SELECT COUNT(*) FROM $table");
            $count = $stmt->fetchColumn();
            echo "<td style='color: green;'>✅ Exists</td>";
            echo "<td>$count</td>";
            
            if ($table === 'authors' && $count === 0) {
                echo "<td><a href='setup_authors_table.php'>Add Sample Data</a></td>";
            } else {
                echo "<td>-</td>";
            }
        } catch (Exception $e) {
            echo "<td style='color: red;'>❌ Missing</td>";
            echo "<td>-</td>";
            
            if ($table === 'authors') {
                echo "<td><a href='setup_authors_table.php'><strong>Create Table</strong></a></td>";
            } else {
                echo "<td>Run main SQL file</td>";
            }
        }
        
        echo "</tr>";
    }
    
    echo "</table>";
    
    // Check directories
    echo "<h3>Directory Status</h3>";
    $directories = [
        'public/uploads/covers' => 'Book covers',
        'public/uploads/authors' => 'Author photos'
    ];
    
    foreach ($directories as $dir => $description) {
        $fullPath = __DIR__ . '/' . $dir;
        if (is_dir($fullPath)) {
            $files = glob($fullPath . '/*.{jpg,jpeg,png,gif}', GLOB_BRACE);
            echo "✅ $description: " . count($files) . " files in $dir<br>";
        } else {
            echo "❌ $description: Directory $dir does not exist<br>";
        }
    }
    
    echo "<h3>Quick Actions</h3>";
    echo "<ul>";
    echo "<li><a href='setup_authors_table.php'>Setup Authors Table</a> - Create authors table and add sample data</li>";
    echo "<li><a href='add_sample_data.php'>Add More Sample Data</a> - Add more books, reviews, etc.</li>";
    echo "<li><a href='download_author_photos.php'>Download Author Photos</a> - Get sample author photos</li>";
    echo "<li><a href='index.php'>View Home Page</a> - See the results</li>";
    echo "</ul>";
    
} catch (Exception $e) {
    echo "<h3>❌ Database Connection Error</h3>";
    echo "<p>" . $e->getMessage() . "</p>";
    echo "<p>Please check your database configuration in config/database.php</p>";
}
?>