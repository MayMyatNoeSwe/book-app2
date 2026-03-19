<?php
// Add sample data for home page features
require_once 'includes/sessions.php';
require_once 'vendor/autoload.php';
require_once 'includes/env_loader.php';

use App\Library;

try {
    $library = new Library();
    $pdo = $library->getPdo();
    
    echo "<h2>Adding Sample Data for Home Page Features</h2>";
    
    // Create authors table if it doesn't exist
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
    echo "✅ Authors table created/verified<br>";
    
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
    ];
    
    $stmt = $pdo->prepare("INSERT IGNORE INTO authors (name, photo, bio) VALUES (?, ?, ?)");
    
    foreach ($authors as $author) {
        $stmt->execute($author);
        echo "Added author: {$author[0]}<br>";
    }
    
    // Add more books
    $books = [
        ['book_007', 'The Hobbit', 'J.R.R. Tolkien', 1937, 'Fantasy', 8, 5],
        ['book_008', 'Pride and Prejudice', 'Jane Austen', 1813, 'Romance', 4, 3],
        ['book_009', 'The Catcher in the Rye', 'J.D. Salinger', 1951, 'Fiction', 3, 2],
        ['book_010', 'JavaScript: The Good Parts', 'Douglas Crockford', 2008, 'Non-Fiction', 3, 2],
        ['book_011', 'Dune', 'Frank Herbert', 1965, 'Sci-Fi', 5, 3],
        ['book_012', 'The Lord of the Rings', 'J.R.R. Tolkien', 1954, 'Fantasy', 6, 4],
    ];
    
    $stmt = $pdo->prepare("INSERT IGNORE INTO books (id, title, author, year, category, total_copies, available_copies) VALUES (?, ?, ?, ?, ?, ?, ?)");
    
    foreach ($books as $book) {
        $stmt->execute($book);
        echo "Added book: {$book[1]}<br>";
    }
    
    // Add more borrowing history
    $borrows = [
        [2, 'book_006', '2025-12-05 10:00:00', '2025-12-15 14:30:00', '2025-12-19'],
        [3, 'book_006', '2025-12-20 11:00:00', NULL, '2026-01-03'],
        [2, 'book_007', '2025-12-12 16:00:00', '2025-12-22 09:00:00', '2025-12-26'],
        [3, 'book_008', '2025-12-25 14:00:00', NULL, '2026-01-08'],
        [2, 'book_010', '2025-12-08 13:00:00', '2025-12-18 10:00:00', '2025-12-22'],
    ];
    
    $stmt = $pdo->prepare("INSERT IGNORE INTO borrowing_history (user_id, book_id, borrowed_at, returned_at, due_date) VALUES (?, ?, ?, ?, ?)");
    
    foreach ($borrows as $borrow) {
        $stmt->execute($borrow);
        echo "Added borrow record for book: {$borrow[1]}<br>";
    }
    
    // Add more reviews
    $reviews = [
        [3, 'book_006', 5, 'Magical! Perfect for both children and adults. J.K. Rowling created an amazing world.', '2025-12-23 19:45:00'],
        [2, 'book_007', 4, 'A wonderful adventure story. Tolkien\'s world-building is incredible.', '2025-12-24 11:30:00'],
        [3, 'book_008', 4, 'Jane Austen\'s wit and social commentary are brilliant. A delightful read.', '2025-12-25 16:20:00'],
        [2, 'book_010', 4, 'Great insights into JavaScript. Essential for web developers.', '2025-12-26 10:15:00'],
        [3, 'book_011', 5, 'Epic science fiction at its finest. Complex and rewarding.', '2025-12-27 14:00:00'],
    ];
    
    $stmt = $pdo->prepare("INSERT IGNORE INTO reviews (user_id, book_id, rating, review_text, created_at) VALUES (?, ?, ?, ?, ?)");
    
    foreach ($reviews as $review) {
        $stmt->execute($review);
        echo "Added review for book: {$review[1]}<br>";
    }
    
    echo "<h3>✅ Sample data added successfully!</h3>";
    echo "<p><a href='index.php'>View Home Page</a></p>";
    
} catch (Exception $e) {
    echo "<h3>❌ Error: " . $e->getMessage() . "</h3>";
}
?>