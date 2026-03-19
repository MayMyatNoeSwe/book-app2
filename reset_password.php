<?php
require_once 'includes/sessions.php';
require_once 'includes/env_loader.php';
require_once 'config/database.php';

// Allow password reset even if logged in (user might want to reset from email link)
// Don't redirect logged-in users

$config = require 'config/database.php';
$dsn = "mysql:host={$config['host']};dbname={$config['dbname']};charset={$config['charset']}";
$pdo = new PDO($dsn, $config['username'], $config['password'], $config['options']);

$error = '';
$success = '';
$token = $_GET['token'] ?? '';
$validToken = false;

// Debug: Log the token
error_log("Reset Password - Token received: " . ($token ? 'Yes' : 'No'));

// Verify token
if ($token) {
    $stmt = $pdo->prepare("
        SELECT id, username, email, reset_token_expiry 
        FROM users 
        WHERE reset_token = ? AND reset_token_expiry > NOW()
        LIMIT 1
    ");
    $stmt->execute([$token]);
    $user = $stmt->fetch();
    
    if ($user) {
        $validToken = true;
        error_log("Reset Password - Valid token for user: " . $user['email']);
    } else {
        $error = 'Invalid or expired reset token. Please request a new password reset.';
        error_log("Reset Password - Invalid or expired token");
    }
} else {
    error_log("Reset Password - No token provided in URL");
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $validToken) {
    $password = $_POST['password'] ?? '';
    $confirmPassword = $_POST['confirm_password'] ?? '';
    
    if (empty($password) || empty($confirmPassword)) {
        $error = 'Please fill in all fields';
    } elseif (strlen($password) < 6) {
        $error = 'Password must be at least 6 characters long';
    } elseif ($password !== $confirmPassword) {
        $error = 'Passwords do not match';
    } else {
        // Update password
        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
        $stmt = $pdo->prepare("
            UPDATE users 
            SET password = ?, reset_token = NULL, reset_token_expiry = NULL 
            WHERE reset_token = ?
        ");
        
        if ($stmt->execute([$hashedPassword, $token])) {
            $success = 'Your password has been reset successfully! You can now login with your new password.';
            $validToken = false; // Prevent form from showing again
        } else {
            $error = 'Failed to reset password. Please try again.';
        }
    }
}

$pageTitle = 'Reset Password - My Library';
?>
<!DOCTYPE html>
<html lang="en" data-bs-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($pageTitle) ?></title>
    
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Playfair+Display:wght@700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="public/css/premium_new.css">

    <style>
        :root {
            --primary-color: #E07A5F;
            --primary-hover: #d16a4f;
            --beige-bg: #FFF3F0;
            --text-main: #2D3436;
            --text-light: #636E72;
            --card-radius: 24px;
        }

        [data-bs-theme="dark"] {
            --beige-bg: #0f172a;
            --text-main: #f1f5f9;
            --text-light: #94a3b8;
        }

        body {
            font-family: 'Inter', sans-serif;
            background-color: var(--beige-bg);
            transition: background-color 0.4s ease;
            min-height: 100vh;
        }
        
        .auth-container {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 40px 20px;
        }
        
        .auth-card {
            background: var(--bs-body-bg);
            border-radius: var(--card-radius);
            box-shadow: 0 40px 100px -20px rgba(0, 0, 0, 0.15);
            overflow: hidden;
            max-width: 480px;
            width: 100%;
            border: 1px solid rgba(0,0,0,0.05);
        }

        [data-bs-theme="dark"] .auth-card {
            border-color: rgba(255,255,255,0.05);
            box-shadow: 0 40px 100px -20px rgba(0, 0, 0, 0.45);
        }
        
        .auth-header {
            background: url('https://images.unsplash.com/photo-1512820790803-83ca734da794?ixlib=rb-1.2.1&auto=format&fit=crop&w=800&q=80') center/cover;
            padding: 40px 30px;
            text-align: center;
            position: relative;
        }
        
        .auth-header::after {
            content: '';
            position: absolute;
            top: 0; left: 0; right: 0; bottom: 0;
            background: linear-gradient(135deg, rgba(224, 122, 95, 0.95) 0%, rgba(61, 64, 91, 0.8) 100%);
            z-index: 1;
        }
        
        .auth-header-content {
            position: relative;
            z-index: 2;
            color: white;
        }
        
        .auth-icon-circle {
            width: 70px;
            height: 70px;
            background: rgba(255, 255, 255, 0.2);
            border: 1px solid rgba(255, 255, 255, 0.3);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 20px;
            backdrop-filter: blur(10px);
            font-size: 1.8rem;
            filter: drop-shadow(0 4px 10px rgba(0,0,0,0.2));
        }
        
        .auth-header h2 {
            font-family: 'Playfair Display', serif;
            font-weight: 700;
            font-size: 2rem;
            margin-bottom: 6px;
        }
        
        .auth-body {
            padding: 30px;
        }
        
        .form-label {
            font-weight: 600;
            color: var(--text-main);
            margin-bottom: 10px;
        }

        .form-control {
            border: 2px solid rgba(0,0,0,0.06);
            border-radius: 14px;
            padding: 12px 16px;
            font-size: 1rem;
            transition: all 0.3s ease;
            background: rgba(0,0,0,0.02);
            color: var(--text-main);
        }

        [data-bs-theme="dark"] .form-control {
            border-color: rgba(255,255,255,0.05);
            background: rgba(255,255,255,0.03);
        }

        .form-control:focus {
            border-color: var(--primary-color);
            background: transparent;
            box-shadow: 0 0 0 5px rgba(224, 122, 95, 0.1);
        }

        .btn-primary-custom {
            background: linear-gradient(135deg, var(--primary-color) 0%, var(--primary-hover) 100%);
            color: white;
            border: none;
            border-radius: 14px;
            padding: 14px;
            font-weight: 700;
            font-size: 1.1rem;
            width: 100%;
            cursor: pointer;
            transition: all 0.3s cubic-bezier(0.175, 0.885, 0.32, 1.275);
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 12px;
            box-shadow: 0 10px 20px -5px rgba(224, 122, 95, 0.3);
        }

        .btn-primary-custom:hover {
            transform: translateY(-3px);
            box-shadow: 0 15px 30px -5px rgba(224, 122, 95, 0.4);
            color: white;
        }

        .custom-alert {
            background: rgba(224, 122, 95, 0.1);
            border: 1px solid rgba(224, 122, 95, 0.2);
            border-radius: 14px;
            padding: 20px;
            display: flex;
            gap: 16px;
            margin-bottom: 30px;
            color: var(--text-main);
        }

        .custom-alert i {
            color: var(--primary-color);
            font-size: 1.25rem;
            margin-top: 2px;
        }
        
        .back-link {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            color: var(--primary-color);
            text-decoration: none;
            font-weight: 700;
            font-size: 1.05rem;
            transition: all 0.3s ease;
            margin-top: 25px;
            width: 100%;
        }
        
        .back-link:hover {
            color: var(--primary-hover);
            transform: translateX(-5px);
            text-decoration: underline;
        }

        .password-container {
            position: relative;
        }

        .password-toggle {
            position: absolute;
            right: 18px;
            top: 50%;
            transform: translateY(-50%);
            color: var(--text-light);
            cursor: pointer;
            font-size: 1.1rem;
            transition: color 0.3s;
        }

        .password-toggle:hover {
            color: var(--text-main);
        }
    </style>
</head>
<body>
    <div class="auth-container">
        <div class="auth-card animate__animated animate__zoomIn">
            <div class="auth-header">
                <div class="auth-header-content">
                    <div class="auth-icon-circle">
                        <i class="fas fa-shield-alt"></i>
                    </div>
                    <h2>Secure Reset</h2>
                    <p>Enter your new password</p>
                </div>
            </div>
            
            <div class="auth-body">
                <?php if (!$token || (!$validToken && !$success)): ?>
                    <div class="custom-alert">
                        <i class="fas fa-exclamation-circle"></i>
                        <div class="custom-alert-text">
                            <?= $error ?: 'Invalid or expired reset token. Please request a new password reset.' ?>
                        </div>
                    </div>
                    
                    <div class="text-center">
                        <a href="forgot_password.php" class="btn btn-primary-custom" style="padding: 12px 24px; display: inline-flex; width: auto;">
                            Request New Link
                        </a>
                    </div>
                <?php elseif ($success): ?>
                    <script>
                        document.addEventListener('DOMContentLoaded', function() {
                            Swal.fire({
                                icon: 'success',
                                title: 'Password Updated!',
                                text: 'Your new password is set. Redirecting to login...',
                                confirmButtonColor: '#E07A5F',
                                timer: 3000,
                                timerProgressBar: true
                            }).then(() => {
                                window.location.href = 'login.php';
                            });
                        });
                    </script>
                    <div class="text-center">
                        <a href="login.php" class="btn btn-primary-custom">
                            Go to Login
                        </a>
                    </div>
                <?php else: ?>
                    <form method="POST" action="" id="resetPasswordForm">
                        <div class="mb-4">
                            <div class="password-container">
                                <input type="password" class="form-control" id="password" name="password" 
                                       placeholder="New Password" required autofocus>
                                <span class="password-toggle" onclick="togglePassword('password')">
                                    <i class="fas fa-eye" id="password-icon"></i>
                                </span>
                            </div>
                        </div>
                        
                        <div class="mb-4">
                            <div class="password-container">
                                <input type="password" class="form-control" id="confirm_password" name="confirm_password" 
                                       placeholder="Confirm Password" required>
                                <span class="password-toggle" onclick="togglePassword('confirm_password')">
                                    <i class="fas fa-eye" id="confirm_password-icon"></i>
                                </span>
                            </div>
                        </div>
                        
                        <button type="submit" class="btn btn-primary-custom">
                            Update Password
                        </button>
                    </form>
                <?php endif; ?>
                
                <a href="login.php" class="back-link">
                    <i class="fas fa-arrow-left"></i> Back to login
                </a>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script>
        function togglePassword(fieldId) {
            const field = document.getElementById(fieldId);
            const icon = document.getElementById(fieldId + '-icon');
            
            if (field.type === 'password') {
                field.type = 'text';
                icon.classList.remove('fa-eye');
                icon.classList.add('fa-eye-slash');
            } else {
                field.type = 'password';
                icon.classList.remove('fa-eye-slash');
                icon.classList.add('fa-eye');
            }
        }
        
        <?php if ($error && $validToken): ?>
        Swal.fire({
            icon: 'error',
            title: 'Update Failed',
            text: <?= json_encode($error) ?>,
            confirmButtonColor: '#E07A5F'
        });
        <?php endif; ?>
        
        <?php if ($validToken && !$success): ?>
        document.getElementById('resetPasswordForm').addEventListener('submit', function(e) {
            const password = document.getElementById('password').value;
            const confirmPassword = document.getElementById('confirm_password').value;
            
            if (password !== confirmPassword) {
                e.preventDefault();
                Swal.fire({
                    icon: 'error',
                    title: 'Passwords Do Not Match',
                    text: 'Please ensure both passwords are identical.',
                    confirmButtonColor: '#E07A5F'
                });
                return false;
            }
            
            Swal.fire({
                title: 'Updating Password...',
                text: 'Please wait',
                allowOutsideClick: false,
                allowEscapeKey: false,
                showConfirmButton: false,
                didOpen: () => {
                    Swal.showLoading();
                }
            });
        });
        <?php endif; ?>
    </script>
</body>
</html>
