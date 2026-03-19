<?php
/**
 * Generate Reset Token Tool
 * This tool helps you manually generate a password reset token for testing
 */

require_once 'config/database.php';

$config = require 'config/database.php';
$dsn = "mysql:host={$config['host']};dbname={$config['dbname']};charset={$config['charset']}";
$pdo = new PDO($dsn, $config['username'], $config['password'], $config['options']);

$message = '';
$messageType = '';
$resetLink = '';

// Get all users
$stmt = $pdo->query("SELECT id, username, email FROM users ORDER BY id DESC LIMIT 20");
$users = $stmt->fetchAll(PDO::FETCH_ASSOC);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $userId = $_POST['user_id'] ?? null;
    
    if ($userId) {
        // Generate new token
        $token = bin2hex(random_bytes(32));
        $expiry = date('Y-m-d H:i:s', strtotime('+1 hour'));
        
        // Update user with new token
        $stmt = $pdo->prepare("UPDATE users SET reset_token = ?, reset_token_expiry = ? WHERE id = ?");
        if ($stmt->execute([$token, $expiry, $userId])) {
            // Get user info
            $stmt = $pdo->prepare("SELECT username, email FROM users WHERE id = ?");
            $stmt->execute([$userId]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
            
            $resetLink = "http://" . $_SERVER['HTTP_HOST'] . dirname($_SERVER['PHP_SELF']) . "/reset_password.php?token=" . $token;
            $message = "Reset token generated successfully for " . $user['username'] . " (" . $user['email'] . ")";
            $messageType = 'success';
        } else {
            $message = "Failed to generate token";
            $messageType = 'error';
        }
    }
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Generate Reset Token</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 40px 20px;
        }
        .tool-card {
            background: white;
            border-radius: 20px;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
            max-width: 900px;
            margin: 0 auto;
        }
        .tool-header {
            background: linear-gradient(135deg, #2e8a40, #34495e);
            color: white;
            padding: 30px;
            border-radius: 20px 20px 0 0;
            text-align: center;
        }
        .tool-body {
            padding: 40px;
        }
        .user-card {
            background: #f8f9fa;
            border-radius: 10px;
            padding: 15px;
            margin-bottom: 10px;
            border-left: 4px solid #2e8a40;
            cursor: pointer;
            transition: all 0.3s ease;
        }
        .user-card:hover {
            background: #e9ecef;
            transform: translateX(5px);
        }
        .user-card input[type="radio"] {
            margin-right: 10px;
        }
        .reset-link-box {
            background: #d4edda;
            border: 2px solid #28a745;
            border-radius: 10px;
            padding: 20px;
            margin-top: 20px;
        }
        .reset-link {
            background: white;
            padding: 15px;
            border-radius: 5px;
            word-break: break-all;
            font-family: 'Courier New', monospace;
            font-size: 0.9rem;
        }
        .btn-generate {
            background: #2e8a40;
            color: white;
            padding: 12px 30px;
            border: none;
            border-radius: 10px;
            font-weight: 600;
            transition: all 0.3s ease;
        }
        .btn-generate:hover {
            background: #267a36;
            transform: translateY(-2px);
        }
    </style>
</head>
<body>
    <div class="tool-card">
        <div class="tool-header">
            <h1><i class="fas fa-key me-3"></i>Generate Reset Token</h1>
            <p class="mb-0">Manually create a password reset token for testing</p>
        </div>
        
        <div class="tool-body">
            <?php if ($message): ?>
                <div class="alert alert-<?= $messageType === 'success' ? 'success' : 'danger' ?>">
                    <i class="fas fa-<?= $messageType === 'success' ? 'check-circle' : 'exclamation-circle' ?> me-2"></i>
                    <?= htmlspecialchars($message) ?>
                </div>
            <?php endif; ?>
            
            <?php if ($resetLink): ?>
                <div class="reset-link-box">
                    <h5><i class="fas fa-link me-2"></i>Your Reset Link:</h5>
                    <div class="reset-link mb-3">
                        <?= htmlspecialchars($resetLink) ?>
                    </div>
                    <div class="d-flex gap-2">
                        <a href="<?= htmlspecialchars($resetLink) ?>" class="btn btn-success" target="_blank">
                            <i class="fas fa-external-link-alt me-2"></i>Open Reset Page
                        </a>
                        <button class="btn btn-primary" onclick="copyToClipboard('<?= htmlspecialchars($resetLink) ?>')">
                            <i class="fas fa-copy me-2"></i>Copy Link
                        </button>
                    </div>
                    <div class="mt-3">
                        <small class="text-muted">
                            <i class="fas fa-clock me-1"></i>This token will expire in 1 hour
                        </small>
                    </div>
                </div>
                <hr class="my-4">
            <?php endif; ?>
            
            <h4 class="mb-3"><i class="fas fa-users me-2"></i>Select a User</h4>
            <p class="text-muted">Choose a user to generate a password reset token for:</p>
            
            <form method="POST">
                <div class="mb-4" style="max-height: 400px; overflow-y: auto;">
                    <?php foreach ($users as $user): ?>
                        <label class="user-card">
                            <input type="radio" name="user_id" value="<?= $user['id'] ?>" required>
                            <strong><?= htmlspecialchars($user['username']) ?></strong>
                            <br>
                            <small class="text-muted"><?= htmlspecialchars($user['email']) ?></small>
                        </label>
                    <?php endforeach; ?>
                </div>
                
                <button type="submit" class="btn btn-generate w-100">
                    <i class="fas fa-magic me-2"></i>Generate Reset Token
                </button>
            </form>
            
            <hr class="my-4">
            
            <div class="alert alert-info">
                <h6><i class="fas fa-info-circle me-2"></i>How to Use:</h6>
                <ol class="mb-0">
                    <li>Select a user from the list above</li>
                    <li>Click "Generate Reset Token"</li>
                    <li>Copy the generated link or click "Open Reset Page"</li>
                    <li>Use the link to reset the password</li>
                    <li>Token expires in 1 hour</li>
                </ol>
            </div>
            
            <div class="text-center mt-4">
                <a href="forgot_password.php" class="btn btn-outline-primary me-2">
                    <i class="fas fa-envelope me-2"></i>Test Email Flow
                </a>
                <a href="debug_reset_password.php" class="btn btn-outline-secondary me-2">
                    <i class="fas fa-bug me-2"></i>Debug Tool
                </a>
                <a href="index.php" class="btn btn-outline-secondary">
                    <i class="fas fa-home me-2"></i>Home
                </a>
            </div>
        </div>
    </div>
    
    <script>
        function copyToClipboard(text) {
            navigator.clipboard.writeText(text).then(() => {
                alert('Reset link copied to clipboard!');
            }).catch(err => {
                console.error('Failed to copy:', err);
            });
        }
    </script>
</body>
</html>
