<?php
// api/admin_users.php
require_once dirname(__DIR__) . '/vendor/autoload.php';
require_once dirname(__DIR__) . '/includes/sessions.php';
require_once dirname(__DIR__) . '/includes/env_loader.php';
require_once dirname(__DIR__) . '/includes/functions.php';

use App\User;
use App\Auth;

if (!Auth::check() || $_SESSION['role'] !== 'admin') {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);
$action = $data['action'] ?? '';
$userId = (int)($data['id'] ?? 0);

if (!$userId || !$action) {
    echo json_encode(['success' => false, 'message' => 'Invalid request']);
    exit;
}

$userSvc = new User();

switch ($action) {
    case 'update_role':
        $role = $data['role'] ?? 'user';
        if ($userSvc->updateUserRole($userId, $role)) {
            echo json_encode(['success' => true]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Update failed']);
        }
        break;

    case 'update_tier':
        $tier = $data['tier'] ?? 'bronze';
        if ($userSvc->updateMembershipTier($userId, $tier)) {
            echo json_encode(['success' => true]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Update failed']);
        }
        break;

    case 'delete_user':
        if ($userSvc->deleteUser($userId)) {
            echo json_encode(['success' => true]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Delete failed']);
        }
        break;

    default:
        echo json_encode(['success' => false, 'message' => 'Unknown action']);
}
?>
