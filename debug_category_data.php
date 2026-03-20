<?php
require_once 'includes/sessions.php';
require_once 'vendor/autoload.php';
require_once 'includes/env_loader.php'; // Load env first
require_once 'includes/functions.php';

echo "<h2>getCategories() Result:</h2>";
$cats = getCategories();
echo "<pre>";
print_r($cats);
echo "</pre>";

$config = include 'config/database.php';
try {
    $dsn = "mysql:host={$config['host']};dbname={$config['dbname']};charset={$config['charset']}";
    $pdo = new PDO($dsn, $config['username'], $config['password'], $config['options']);

    echo "<h2>Checking categories table:</h2>";
    $stmt = $pdo->query("SHOW TABLES LIKE 'categories'");
    if ($stmt->fetch()) {
        echo "Table 'categories' exists.<br>";
        $stmt = $pdo->query("SELECT name FROM categories");
        $results = $stmt->fetchAll(PDO::FETCH_COLUMN);
        echo "Count: " . count($results) . "<br>";
        echo "<pre>";
        print_r($results);
        echo "</pre>";
    } else {
        echo "Table 'categories' does NOT exist.<br>";
    }

    echo "<h2>Checking books table unique categories:</h2>";
    $stmt = $pdo->query("SELECT DISTINCT category FROM books WHERE category IS NOT NULL AND TRIM(category) <> ''");
    $results = $stmt->fetchAll(PDO::FETCH_COLUMN);
    echo "Count: " . count($results) . "<br>";
    echo "<pre>";
    print_r($results);
    echo "</pre>";

} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
