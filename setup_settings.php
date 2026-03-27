<?php
// setup_settings.php
require_once __DIR__ . '/vendor/autoload.php';
require_once __DIR__ . '/includes/env_loader.php';
require_once __DIR__ . '/config/database.php';

use App\Library;

try {
    $library = new Library();
    $pdo = $library->getPdo();

    $sql = "CREATE TABLE IF NOT EXISTS settings (
        `key` VARCHAR(50) PRIMARY KEY,
        `value` TEXT NULL,
        `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;";

    $pdo->exec($sql);
    echo "Settings table created successfully.\n";

    // Insert some default settings if they don't exist
    $defaults = [
        'site_name' => 'BookHouse Library',
        'contact_email' => 'admin@bookhouse.com',
        'library_address' => '123 Library St, Yangon, Myanmar',
        'maintenance_mode' => '0',
        'allow_registration' => '1',
        'borrow_limit' => '5',
        'borrow_duration' => '14',
        'fine_per_day' => '500',
        'currency' => 'MMK',
        'accent_color' => '#4e73df'
    ];

    $stmt = $pdo->prepare("INSERT IGNORE INTO settings (`key`, `value`) VALUES (?, ?)");
    foreach ($defaults as $key => $value) {
        $stmt->execute([$key, $value]);
    }
    echo "Default settings initialized.\n";

} catch (Exception $e) {
    die("Error: " . $e->getMessage());
}
