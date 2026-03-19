<?php
// Quick setup script to create authors table
require_once 'includes/sessions.php';
require_once 'vendor/autoload.php';
require_once 'includes/env_loader.php';

use App\Library;

try {
    $library = new Library();
    $pdo = $library->getPdo();
    
    echo "<h2>Setting Up Authors Table</h2>";
    
    // Create authors table
    $createAuthorsTable = "
        CREATE TABLE IF NOT EXISTS authors (
            id INT AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(255) UNIQUE NOT NULL,
            photo VARCHAR(255) NULL,
            bio TEXT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )
    ";
    
    $pdo->exec($createAuthorsTable);
    echo "✅ Authors table created successfully<br><br>";
    
    // Add sample authors
    $authors = [
        ['J.K. Rowling', 'jk_rowling.jpg', 'British author best known for the Harry Potter series.'],
        ['J.R.R. Tolkien', 'tolkien.jpg', 'English author and philologist, creator of Middle-earth.'],
        ['George Orwell', 'orwell.jpg', 'English novelist and essayist, known for dystopian fiction.'],
        ['Jane Austen', 'austen.jpg', 'English novelist known for her social commentary and wit.'],
        ['F. Scott Fitzgerald', 'fitzgerald.jpg', 'American novelist of the Jazz Age.'],
        ['Harper Lee', 'harper_lee.jpg', 'American novelist, author of To Kill a Mockingbird.'],
        ['Frank Herbert', 'herbert.jpg', 'American science fiction author, creator of Dune.'],
        ['Douglas Crockford', 'crockford.jpg', 'American computer programmer and author.'],
        ['Robert C. Martin', 'martin.jpg', 'American software engineer and author.'],
        ['J.D. Salinger', 'salinger.jpg', 'American writer known for The Catcher in the Rye.'],
        ['John Doe', 'john_doe.jpg', 'Programming instructor and author.'],
    ];
    
    $stmt = $pdo->prepare("INSERT IGNORE INTO authors (name, photo, bio) VALUES (?, ?, ?)");
    
    $addedCount = 0;
    foreach ($authors as $author) {
        $result = $stmt->execute($author);
        if ($stmt->rowCount() > 0) {
            echo "✅ Added author: {$author[0]}<br>";
            $addedCount++;
        } else {
            echo "⏭️ Author already exists: {$author[0]}<br>";
        }
    }
    
    echo "<br><strong>Summary:</strong><br>";
    echo "- Authors table: ✅ Created<br>";
    echo "- Sample authors: {$addedCount} added<br>";
    
    echo "<h3>✅ Setup Complete!</h3>";
    echo "<p><strong>What's Next:</strong></p>";
    echo "<ul>";
    echo "<li><a href='index.php'>View Home Page</a> - See the authors with dummy photos</li>";
    echo "<li><a href='download_author_photos.php'>Download Sample Photos</a> - Optional: Get real author photos</li>";
    echo "<li><a href='test_author_photos.php'>Test Author Photos</a> - Verify everything works</li>";
    echo "</ul>";
    
} catch (Exception $e) {
    echo "<h3>❌ Error: " . $e->getMessage() . "</h3>";
    echo "<p>Please check your database connection and try again.</p>";
}
?>