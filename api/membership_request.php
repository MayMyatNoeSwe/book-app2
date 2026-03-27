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

    $stmt = $pdo->prepare("INSERT INTO membership_requests (user_id, tier, payment_method, payment_screenshot, status) VALUES (?, ?, ?, ?, 'pending')");
    $stmt->execute([$userId, $tier, $method, $screenshotPath]);

    echo json_encode(['success' => true, 'message' => 'Your membership upgrade request has been submitted to wait for admin approval.']);

} catch (\Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}
