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
    $lib = new \App\Library($pdo);
    
    // Create a 30-day subscription for the new tier
    $stmt = $pdo->prepare("INSERT INTO user_subscriptions (user_id, is_host, tier, expires_at) VALUES (?, 1, ?, DATE_ADD(NOW(), INTERVAL 1 MONTH))");
    $stmt->execute([$userId, $newTier]);
    $subId = $pdo->lastInsertId();

    // Set as active card for the user
    $stmt = $pdo->prepare("UPDATE users SET active_subscription_id = ? WHERE id = ?");
    $stmt->execute([$subId, $userId]);

    // Sync profile status
    $lib->syncUserProfileTier($userId);

    echo json_encode(['success' => true, 'message' => 'Your account has been upgraded to ' . ucfirst($newTier) . '!']);

} catch (\Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}
