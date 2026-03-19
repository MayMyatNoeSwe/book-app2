<?php
// Debug OAuth Login - Comprehensive Session Check
require_once 'includes/sessions.php';
require_once 'includes/env_loader.php';
require_once 'config/database.php';
require_once 'src/Auth.php';

use App\Auth;

header('Content-Type: text/html; charset=utf-8');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>OAuth Login Debug</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { padding: 20px; background: #f8f9fa; }
        .debug-box { background: white; padding: 20px; border-radius: 8px; margin-bottom: 20px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); }
        .debug-title { font-weight: bold; color: #2e8a40; margin-bottom: 15px; font-size: 1.2rem; }
        .status-ok { color: #28a745; font-weight: bold; }
        .status-error { color: #dc3545; font-weight: bold; }
        .status-warning { color: #ffc107; font-weight: bold; }
        pre { background: #f8f9fa; padding: 15px; border-radius: 4px; border: 1px solid #dee2e6; }
        .test-result { padding: 10px; margin: 5px 0; border-radius: 4px; }
        .test-pass { background: #d4edda; border: 1px solid #c3e6cb; }
        .test-fail { background: #f8d7da; border: 1px solid #f5c6cb; }
    </style>
</head>
<body>
    <div class="container">
        <h1 class="mb-4">OAuth Login Debug Panel</h1>
        
        <!-- Session Information -->
        <div class="debug-box">
            <div class="debug-title">1. Session Information</div>
            <table class="table table-sm">
                <tr>
                    <td><strong>Session ID:</strong></td>
                    <td><?= session_id() ?></td>
                </tr>
                <tr>
                    <td><strong>Session Status:</strong></td>
                    <td><?= session_status() === PHP_SESSION_ACTIVE ? '<span class="status-ok">ACTIVE</span>' : '<span class="status-error">INACTIVE</span>' ?></td>
                </tr>
                <tr>
                    <td><strong>Session Save Path:</strong></td>
                    <td><?= session_save_path() ?></td>
                </tr>
            </table>
        </div>
        
        <!-- Session Variables -->
        <div class="debug-box">
            <div class="debug-title">2. Session Variables</div>
            <table class="table table-sm">
                <tr>
                    <td><strong>$_SESSION['logged_in']:</strong></td>
                    <td>
                        <?php if (isset($_SESSION['logged_in'])): ?>
                            <?= $_SESSION['logged_in'] === true ? '<span class="status-ok">TRUE</span>' : '<span class="status-error">FALSE</span>' ?>
                        <?php else: ?>
                            <span class="status-error">NOT SET</span>
                        <?php endif; ?>
                    </td>
                </tr>
                <tr>
                    <td><strong>$_SESSION['user_id']:</strong></td>
                    <td><?= isset($_SESSION['user_id']) ? '<span class="status-ok">' . $_SESSION['user_id'] . '</span>' : '<span class="status-error">NOT SET</span>' ?></td>
                </tr>
                <tr>
                    <td><strong>$_SESSION['username']:</strong></td>
                    <td><?= isset($_SESSION['username']) ? '<span class="status-ok">' . htmlspecialchars($_SESSION['username']) . '</span>' : '<span class="status-error">NOT SET</span>' ?></td>
                </tr>
                <tr>
                    <td><strong>$_SESSION['email']:</strong></td>
                    <td><?= isset($_SESSION['email']) ? '<span class="status-ok">' . htmlspecialchars($_SESSION['email']) . '</span>' : '<span class="status-error">NOT SET</span>' ?></td>
                </tr>
                <tr>
                    <td><strong>$_SESSION['role']:</strong></td>
                    <td><?= isset($_SESSION['role']) ? '<span class="status-ok">' . htmlspecialchars($_SESSION['role']) . '</span>' : '<span class="status-error">NOT SET</span>' ?></td>
                </tr>
                <tr>
                    <td><strong>$_SESSION['avatar_url']:</strong></td>
                    <td><?= isset($_SESSION['avatar_url']) ? '<span class="status-ok">' . htmlspecialchars($_SESSION['avatar_url']) . '</span>' : '<span class="status-warning">NOT SET</span>' ?></td>
                </tr>
            </table>
            
            <div class="mt-3">
                <strong>Full Session Array:</strong>
                <pre><?php print_r($_SESSION); ?></pre>
            </div>
        </div>
        
        <!-- Auth Class Tests -->
        <div class="debug-box">
            <div class="debug-title">3. Auth Class Tests</div>
            <div class="test-result <?= Auth::check() ? 'test-pass' : 'test-fail' ?>">
                <strong>Auth::check():</strong> <?= Auth::check() ? '<span class="status-ok">TRUE (User is logged in)</span>' : '<span class="status-error">FALSE (User is NOT logged in)</span>' ?>
            </div>
            <div class="test-result <?= Auth::user() ? 'test-pass' : 'test-fail' ?>">
                <strong>Auth::user():</strong> <?= Auth::user() ? '<span class="status-ok">' . htmlspecialchars(Auth::user()) . '</span>' : '<span class="status-error">NULL</span>' ?>
            </div>
            <div class="test-result <?= Auth::id() ? 'test-pass' : 'test-fail' ?>">
                <strong>Auth::id():</strong> <?= Auth::id() ? '<span class="status-ok">' . Auth::id() . '</span>' : '<span class="status-error">NULL</span>' ?>
            </div>
            <div class="test-result <?= Auth::isAdmin() ? 'test-pass' : 'test-fail' ?>">
                <strong>Auth::isAdmin():</strong> <?= Auth::isAdmin() ? '<span class="status-ok">TRUE</span>' : '<span class="status-warning">FALSE</span>' ?>
            </div>
        </div>
        
        <!-- Database Check -->
        <div class="debug-box">
            <div class="debug-title">4. Database Check (Last 5 Users)</div>
            <?php
            try {
                $config = require 'config/database.php';
                $dsn = "mysql:host={$config['host']};dbname={$config['dbname']};charset={$config['charset']}";
                $pdo = new PDO($dsn, $config['username'], $config['password'], $config['options']);
                
                $stmt = $pdo->query("SELECT id, username, email, oauth_provider, oauth_id, created_at FROM users ORDER BY id DESC LIMIT 5");
                $users = $stmt->fetchAll();
                
                if ($users) {
                    echo '<table class="table table-sm table-striped">';
                    echo '<thead><tr><th>ID</th><th>Username</th><th>Email</th><th>OAuth Provider</th><th>OAuth ID</th><th>Created</th></tr></thead>';
                    echo '<tbody>';
                    foreach ($users as $user) {
                        echo '<tr>';
                        echo '<td>' . $user['id'] . '</td>';
                        echo '<td>' . htmlspecialchars($user['username']) . '</td>';
                        echo '<td>' . htmlspecialchars($user['email']) . '</td>';
                        echo '<td>' . ($user['oauth_provider'] ?: '<em>none</em>') . '</td>';
                        echo '<td>' . ($user['oauth_id'] ?: '<em>none</em>') . '</td>';
                        echo '<td>' . $user['created_at'] . '</td>';
                        echo '</tr>';
                    }
                    echo '</tbody></table>';
                } else {
                    echo '<p class="status-warning">No users found in database</p>';
                }
            } catch (Exception $e) {
                echo '<p class="status-error">Database Error: ' . htmlspecialchars($e->getMessage()) . '</p>';
            }
            ?>
        </div>
        
        <!-- OAuth Configuration -->
        <div class="debug-box">
            <div class="debug-title">5. OAuth Configuration</div>
            <table class="table table-sm">
                <tr>
                    <td><strong>Google Client ID:</strong></td>
                    <td><?= getenv('GOOGLE_CLIENT_ID') ? '<span class="status-ok">SET</span>' : '<span class="status-error">NOT SET</span>' ?></td>
                </tr>
                <tr>
                    <td><strong>Google Client Secret:</strong></td>
                    <td><?= getenv('GOOGLE_CLIENT_SECRET') ? '<span class="status-ok">SET</span>' : '<span class="status-error">NOT SET</span>' ?></td>
                </tr>
                <tr>
                    <td><strong>Google Redirect URI:</strong></td>
                    <td><?= getenv('GOOGLE_REDIRECT_URI') ? '<span class="status-ok">' . htmlspecialchars(getenv('GOOGLE_REDIRECT_URI')) . '</span>' : '<span class="status-error">NOT SET</span>' ?></td>
                </tr>
            </table>
        </div>
        
        <!-- Recommendations -->
        <div class="debug-box">
            <div class="debug-title">6. Troubleshooting Steps</div>
            <?php if (!Auth::check()): ?>
                <div class="alert alert-warning">
                    <strong>User is NOT logged in. Possible issues:</strong>
                    <ol>
                        <li>Session variable <code>$_SESSION['logged_in']</code> is not set to TRUE</li>
                        <li>Session is being destroyed somewhere after OAuth callback</li>
                        <li>Session cookies are not being sent/received properly</li>
                        <li>Session configuration issue in includes/sessions.php</li>
                    </ol>
                    
                    <strong>Next Steps:</strong>
                    <ul>
                        <li>Try logging in with Google OAuth again</li>
                        <li>Immediately after redirect, refresh this page</li>
                        <li>Check browser console for errors</li>
                        <li>Check browser cookies (look for PHPSESSID)</li>
                    </ul>
                </div>
            <?php else: ?>
                <div class="alert alert-success">
                    <strong>✓ User is successfully logged in!</strong>
                    <p>All session variables are set correctly and Auth::check() returns TRUE.</p>
                </div>
            <?php endif; ?>
        </div>
        
        <!-- Actions -->
        <div class="debug-box">
            <div class="debug-title">7. Test Actions</div>
            <div class="btn-group" role="group">
                <a href="oauth/google/login.php" class="btn btn-primary">
                    <i class="fab fa-google me-2"></i>Test Google OAuth Login
                </a>
                <a href="index.php" class="btn btn-success">Go to Home Page</a>
                <a href="logout.php" class="btn btn-danger">Logout</a>
                <button onclick="location.reload()" class="btn btn-secondary">Refresh Debug</button>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/js/all.min.js"></script>
</body>
</html>
