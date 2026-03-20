<?php
// setup_categories.php
require_once 'includes/sessions.php';
require_once 'vendor/autoload.php';
require_once 'includes/env_loader.php';

use App\Library;

try {
    $library = new Library();
    $pdo = $library->getPdo();
    
    echo "--- Starting Categories Setup ---\n";

    // 1. Create categories table
    $createTable = "CREATE TABLE IF NOT EXISTS categories (
        id INT AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(50) UNIQUE NOT NULL,
        slug VARCHAR(50) UNIQUE NOT NULL,
        icon VARCHAR(50) NULL,
        description TEXT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;";
    
    $pdo->exec($createTable);
    echo "✅ Categories table ready.\n";

    // 2. Add category_id column if not exists
    $checkCol = $pdo->query("SHOW COLUMNS FROM books LIKE 'category_id'");
    if ($checkCol->rowCount() == 0) {
        $pdo->exec("ALTER TABLE books ADD COLUMN category_id INT NULL AFTER category");
        echo "✅ Added 'category_id' column to 'books' table.\n";
    } else {
        echo "⏭️ 'category_id' column already exists in 'books' table.\n";
    }

    // 3. Add foreign key if not exists (checked via table constraints)
    $checkFK = $pdo->query("SELECT CONSTRAINT_NAME FROM information_schema.KEY_COLUMN_USAGE 
                            WHERE TABLE_NAME = 'books' AND COLUMN_NAME = 'category_id' 
                            AND CONSTRAINT_NAME = 'fk_book_category'");
    if ($checkFK->rowCount() == 0) {
        try {
            $pdo->exec("ALTER TABLE books ADD CONSTRAINT fk_book_category FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE SET NULL");
            echo "✅ Foreign key 'fk_book_category' added.\n";
        } catch (PDOException $e) {
            echo "⚠️ Could not add foreign key: " . $e->getMessage() . "\n";
        }
    } else {
        echo "⏭️ Foreign key 'fk_book_category' already exists.\n";
    }

    // 4. Fetch unique categories and migrate
    $stmt = $pdo->query("SELECT DISTINCT category FROM books WHERE category IS NOT NULL AND TRIM(category) <> ''");
    $existingCategories = $stmt->fetchAll(PDO::FETCH_COLUMN);

    $defaults = ['Fiction', 'Non-Fiction', 'Mystery', 'Romance', 'Sci-Fi', 'Fantasy', 'Biography', 'History', 'Self-Help', 'Children', 'Horror', 'Thriller', 'Poetry'];
    $allCategories = array_unique(array_merge($existingCategories, $defaults));

    echo "--- Migrating Categories ---\n";
    $insertCat = $pdo->prepare("INSERT IGNORE INTO categories (name, slug) VALUES (?, ?)");
    
    foreach ($allCategories as $cat) {
        $slug = strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $cat)));
        $insertCat->execute([$cat, $slug]);
        echo "Inserted/Matched: $cat ($slug)\n";
    }

    // 5. Update books.category_id
    echo "--- Updating books.category_id ---\n";
    $categories = $pdo->query("SELECT id, name FROM categories")->fetchAll();
    $updateBook = $pdo->prepare("UPDATE books SET category_id = ? WHERE category = ?");
    
    $updatedCount = 0;
    foreach ($categories as $cat) {
        $updateBook->execute([$cat['id'], $cat['name']]);
        $updatedCount += $updateBook->rowCount();
    }
    
    echo "SUCCESS: Updated $updatedCount books with category_id.\n";
    echo "--- Setup Complete ---\n";

} catch (Exception $e) {
    echo "❌ ERROR: " . $e->getMessage() . "\n";
}
