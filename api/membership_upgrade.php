<?php
// api/membership_upgrade.php
header('Content-Type: application/json');
require_once '../includes/sessions.php';
require_once '../vendor/autoload.php';
require_once '../includes/env_loader.php';
require_once '../includes/functions.php';

use App\Auth;

if (!Auth::check()) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);
$newTier = strtolower($data['tier'] ?? '');
$allowedTiers = ['bronze', 'silver', 'gold', 'platinum'];

if (!in_array($newTier, $allowedTiers)) {
    echo json_encode(['success' => false, 'message' => 'Invalid tier selected']);
    exit;
}

try {
    $config = require '../config/database.php';
    $dsn = "mysql:host={$config['host']};dbname={$config['dbname']};charset={$config['charset']}";
    $pdo = new \PDO($dsn, $config['username'], $config['password'], $config['options']);

    $userId = Auth::id();

    // Check if user already in that tier
    $stmt = $pdo->prepare("SELECT membership_tier FROM users WHERE id = ?");
    $stmt->execute([$userId]);
    $currentTier = strtolower($stmt->fetchColumn() ?: 'bronze');

    if ($currentTier === $newTier) {
        echo json_encode(['success' => false, 'message' => 'You are already in this membership tier.']);
        exit;
    }

    // Update user tier
    $stmt = $pdo->prepare("UPDATE users SET membership_tier = ? WHERE id = ?");
    $stmt->execute([$newTier, $userId]);

    echo json_encode(['success' => true, 'message' => 'Upgrade successful!']);

} catch (\Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}
