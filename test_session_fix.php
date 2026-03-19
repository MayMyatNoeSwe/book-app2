<?php
// Test Session Fix - Simple verification
require_once 'includes/sessions.php';
require_once 'src/Auth.php';

use App\Auth;

// Simulate OAuth login
$_SESSION['user_id'] = 999;
$_SESSION['username'] = 'test_oauth_user';
$_SESSION['email'] = 'test@example.com';
$_SESSION['role'] = 'user';
$_SESSION['logged_in'] = true;

header('Content-Type: text/html; charset=utf-8');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Session Fix Test</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
    <div class="container mt-5">
        <div class="card">
            <div class="card-header bg-primary text-white">
                <h3>Session Fix Test Results</h3>
            </div>
            <div class="card-body">
                <h5>Session Configuration:</h5>
                <ul>
                    <li>cookie_samesite: <?= ini_get('session.cookie_samesite') ?></li>
                    <li>cookie_secure: <?= ini_get('session.cookie_secure') ?></li>
                    <li>cookie_httponly: <?= ini_get('session.cookie_httponly') ?></li>
                    <li>cookie_path: <?= ini_get('session.cookie_path') ?></li>
                </ul>
                
                <h5 class="mt-4">Session Variables:</h5>
                <pre><?php print_r($_SESSION); ?></pre>
                
                <h5 class="mt-4">Auth Class Tests:</h5>
                <div class="alert alert-<?= Auth::check() ? 'success' : 'danger' ?>">
                    <strong>Auth::check():</strong> <?= Auth::check() ? 'TRUE ✓' : 'FALSE ✗' ?>
                </div>
                <div class="alert alert-<?= Auth::user() ? 'success' : 'danger' ?>">
                    <strong>Auth::user():</strong> <?= Auth::user() ?? 'NULL' ?>
                </div>
                <div class="alert alert-<?= Auth::id() ? 'success' : 'danger' ?>">
                    <strong>Auth::id():</strong> <?= Auth::id() ?? 'NULL' ?>
                </div>
                
                <div class="mt-4">
                    <a href="index.php" class="btn btn-primary">Go to Home Page</a>
                    <a href="debug_oauth_login.php" class="btn btn-info">Full Debug Panel</a>
                    <a href="logout.php" class="btn btn-danger">Clear Session</a>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
