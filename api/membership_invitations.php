<?php
// api/membership_invitations.php
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
$lib = new Library($pdo);
$userId = Auth::id();

$action = $_POST['action'] ?? '';

if ($action === 'send') {
    $subId = (int)($_POST['sub_id'] ?? 0);
    $email = trim($_POST['email'] ?? '');

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $_SESSION['flash_message'] = ['text' => 'Invalid email address.', 'type' => 'danger'];
        header("Location: ../user-details.php");
        exit;
    }

    $result = $lib->sendInvitation($userId, $subId, $email);
    $_SESSION['flash_message'] = [
        'text' => $result['message'],
        'type' => $result['success'] ? 'success' : 'danger'
    ];
    header("Location: ../user-details.php");
    exit;

} elseif ($action === 'accept') {
    $token = $_POST['token'] ?? '';
    if (empty($token)) {
        $_SESSION['flash_message'] = ['text' => 'Missing token.', 'type' => 'danger'];
        header("Location: ../user-details.php");
        exit;
    }

    $result = $lib->acceptInvitation($userId, $token);
    $_SESSION['flash_message'] = [
        'text' => $result['message'],
        'type' => $result['success'] ? 'success' : 'danger'
    ];
    header("Location: ../user-details.php");
    exit;
}

echo json_encode(['success' => false, 'message' => 'Invalid action']);
