<?php
// api/membership_request.php
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

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request']);
    exit;
}

$userId = Auth::id();
$tier = strtolower($_POST['tier'] ?? '');
$method = $_POST['payment_method'] ?? '';

if (empty($tier) || empty($method)) {
    echo json_encode(['success' => false, 'message' => 'Missing required fields']);
    exit;
}

// Handle Screenshot Upload
$screenshotPath = null;
if (isset($_FILES['screenshot']) && $_FILES['screenshot']['error'] === UPLOAD_ERR_OK) {
    $uploadDir = '../uploads/payments/';
    if (!is_dir($uploadDir)) mkdir($uploadDir, 0777, true);

    $ext = pathinfo($_FILES['screenshot']['name'], PATHINFO_EXTENSION);
    $fileName = 'membership_' . $userId . '_' . time() . '.' . $ext;
    $targetPath = $uploadDir . $fileName;

    if (move_uploaded_file($_FILES['screenshot']['tmp_name'], $targetPath)) {
        $screenshotPath = 'uploads/payments/' . $fileName;
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to save screenshot']);
        exit;
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Payment screenshot is required']);
    exit;
}

try {
    $config = require '../config/database.php';
    $dsn = "mysql:host={$config['host']};dbname={$config['dbname']};charset={$config['charset']}";
    $pdo = new \PDO($dsn, $config['username'], $config['password'], $config['options']);

    // Check if user already has a pending request for this tier
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM membership_requests WHERE user_id = ? AND tier = ? AND status = 'pending'");
    $stmt->execute([$userId, $tier]);
    if ($stmt->fetchColumn() > 0) {
        echo json_encode(['success' => false, 'message' => 'You already have a pending request for ' . ucfirst($tier) . ' membership.']);
        exit;
    }

    // Check if user already has an active subscription for this tier (owned OR shared)
    $stmt = $pdo->prepare("
        SELECT us.id FROM user_subscriptions us 
        LEFT JOIN user_subscriptions parent ON us.parent_id = parent.id
        WHERE us.user_id = ? AND us.tier = ? 
        AND (
            (us.parent_id IS NULL AND us.expires_at > NOW()) 
            OR 
            (us.parent_id IS NOT NULL AND parent.expires_at > NOW())
        )
        LIMIT 1
    ");
    $stmt->execute([$userId, $tier]);
    if ($stmt->fetchColumn()) {
        echo json_encode(['success' => false, 'message' => 'You already have an active ' . ucfirst($tier) . ' membership card.']);
        exit;
    }

    $stmt = $pdo->prepare("INSERT INTO membership_requests (user_id, tier, payment_method, payment_screenshot, status) VALUES (?, ?, ?, ?, 'pending')");
    $stmt->execute([$userId, $tier, $method, $screenshotPath]);

    echo json_encode(['success' => true, 'message' => 'Your membership upgrade request has been submitted to wait for admin approval.']);

} catch (\Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}
