<?php
require_once 'vendor/autoload.php';
require_once 'includes/env_loader.php';
require_once 'includes/functions.php';

$config = require 'config/database.php';
$dsn = "mysql:host={$config['host']};dbname={$config['dbname']};charset={$config['charset']}";
$pdo = new PDO($dsn, $config['username'], $config['password'], $config['options']);

$stmt = $pdo->prepare("SELECT id, title, author, cover_image FROM books WHERE id = ?");
$stmt->execute(['book_045']);
$book = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$book) {
    echo "NO BOOK WITH ID 'book_045' FOUND.\n";
} else {
    $cover = $book['cover_image'] ?: 'NULL';
    echo "Book:\n";
    echo "- {$book['title']} (ID: {$book['id']}, Cover: {$cover})\n";
}
