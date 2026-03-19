<?php

require_once 'vendor/autoload.php';
require_once 'includes/env_loader.php';
require_once 'includes/functions.php';

use App\Book;
use App\EBook;
use Faker\Factory;

$config = require 'config/database.php';

try {
    $dsn = "mysql:host={$config['host']};dbname={$config['dbname']};charset={$config['charset']}";
    $pdo = new PDO($dsn, $config['username'], $config['password'], $config['options']);
} catch (PDOException $e) {
    die("Database connection failed: " . $e->getMessage());
}

$faker = Factory::create();

echo "Seeding database...\n";

// Seed Users
$roles = ['user', 'admin'];
$passwordHash = password_hash('password123', PASSWORD_DEFAULT);

echo "Seeding users...\n";
$stmt = $pdo->prepare("INSERT INTO users (username, email, password, role) VALUES (?, ?, ?, ?)");
for ($i = 0; $i < 10; $i++) {
    try {
        $stmt->execute([
            $faker->unique()->userName,
            $faker->unique()->email,
            $passwordHash,
            $faker->randomElement($roles)
        ]);
    } catch (PDOException $e) {
        // Ignore duplicate entry errors
        continue;
    }
}

// Seed Books
echo "Seeding books...\n";
$categories = getCategories();
// Remove 'Uncategorized' for better data
$categories = array_filter($categories, fn($c) => $c !== 'Uncategorized');

$stmt = $pdo->prepare("INSERT INTO books (id, title, author, year, cover_image, category, total_copies, available_copies, type, file_size, download_link) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");

for ($i = 0; $i < 20; $i++) {
    $isEbook = $faker->boolean(30); // 30% chance of being an ebook
    $id = 'book_' . uniqid();
    $title = $faker->sentence(3);
    $author = $faker->name;
    $year = $faker->year;
    $category = $faker->randomElement($categories);
    $totalCopies = $faker->numberBetween(1, 10);
    $availableCopies = $faker->numberBetween(0, $totalCopies);
    
    // Using placeholder images
    $coverImage = null; // In a real app, we might download images or just leave null

    if ($isEbook) {
        $type = 'ebook';
        $fileSize = $faker->numberBetween(1, 50) . ' MB';
        $downloadLink = $faker->url;
    } else {
        $type = 'physical';
        $fileSize = null;
        $downloadLink = null;
    }

    $stmt->execute([
        $id,
        $title,
        $author,
        $year,
        $coverImage,
        $category,
        $totalCopies,
        $availableCopies,
        $type,
        $fileSize,
        $downloadLink
    ]);
}

echo "Database seeded successfully!\n";
