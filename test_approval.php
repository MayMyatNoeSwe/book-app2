<?php
require_once 'vendor/autoload.php';
require_once 'includes/env_loader.php';
require_once 'includes/functions.php';

use App\Library;

$config = require 'config/database.php';
$pdo = new PDO("mysql:host={$config['host']};dbname={$config['dbname']}", $config['username'], $config['password'], $config['options']);
$library = new Library($pdo);

// Find a pending request
$stmt = $pdo->query("SELECT id FROM membership_requests WHERE status = 'pending' LIMIT 1");
$reqId = $stmt->fetchColumn();

if ($reqId) {
    echo "Attempting to approve request #$reqId...\n";
    try {
        $res = $library->approveMembershipUpgrade((int)$reqId);
        if ($res) {
            echo "Success!\n";
        } else {
            echo "Failed (returned false).\n";
        }
    } catch (Exception $e) {
        echo "Exception: " . $e->getMessage() . "\n";
    }
} else {
    echo "No pending requests found to test.\n";
}
