<?php
// api/membership_update_limit.php
header('Content-Type: application/json');
require_once '../includes/sessions.php';
require_once '../vendor/autoload.php';
require_once '../includes/env_loader.php';
require_once '../includes/functions.php';

use App\Auth;
use App\Library;

if (!Auth::check()) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$pdo = require_once '../includes/db.php';
$userId = Auth::id();

$subId = (int)($_POST['sub_id'] ?? 0);
$newLimit = (int)($_POST['limit'] ?? 0);

// 1. Verify that the current user is the HOST of the group this sub belongs to
$stmt = $pdo->prepare("
    SELECT us.id 
    FROM user_subscriptions us
    JOIN user_subscriptions parent ON us.parent_id = parent.id
    WHERE us.id = ? AND parent.user_id = ? AND parent.is_host = 1
");
$stmt->execute([$subId, $userId]);
$isAuthorized = $stmt->fetch();

// Also check if they are trying to update THEIR OWN host sub limit (if they want to)
if (!$isAuthorized) {
    $stmt = $pdo->prepare("SELECT id FROM user_subscriptions WHERE id = ? AND user_id = ? AND is_host = 1");
    $stmt->execute([$subId, $userId]);
    $isAuthorized = $stmt->fetch();
}

if (!$isAuthorized) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized or invalid subscription.']);
    exit;
}

$stmt = $pdo->prepare("UPDATE user_subscriptions SET custom_borrow_limit = ? WHERE id = ?");
$success = $stmt->execute([$newLimit > 0 ? $newLimit : null, $subId]);

if ($success) {
    echo json_encode(['success' => true, 'message' => 'Borrow limit updated successfully.']);
} else {
    echo json_encode(['success' => false, 'message' => 'Failed to update limit.']);
}
