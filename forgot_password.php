<?php
require_once 'includes/sessions.php';
require_once 'includes/env_loader.php';
require_once 'config/database.php';

// Redirect if already logged in
if (isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true) {
    header('Location: index.php');
    exit;
}

$config = require 'config/database.php';
$dsn = "mysql:host={$config['host']};dbname={$config['dbname']};charset={$config['charset']}";
$pdo = new PDO($dsn, $config['username'], $config['password'], $config['options']);

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    
    if (empty($email)) {
        $error = 'Please enter your email address';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Please enter a valid email address';
    } else {
        // Check if email exists
        $stmt = $pdo->prepare("SELECT id, username, email FROM users WHERE email = ? LIMIT 1");
        $stmt->execute([$email]);
        $user = $stmt->fetch();
        
        if ($user) {
            // Generate reset token
            $token = bin2hex(random_bytes(32));
            $expiry = date('Y-m-d H:i:s', strtotime('+1 hour'));
            
            // Store token in database
            $stmt = $pdo->prepare("UPDATE users SET reset_token = ?, reset_token_expiry = ? WHERE id = ?");
            $stmt->execute([$token, $expiry, $user['id']]);
            
            // Send email with reset link
            try {
                require 'vendor/autoload.php';
                
                $mail = new PHPMailer\PHPMailer\PHPMailer(true);
                
                // Server settings
                $mail->isSMTP();
                $mail->Host = getenv('MAIL_HOST') ?: 'sandbox.smtp.mailtrap.io';
                $mail->SMTPAuth = true;
                $mail->Username = getenv('MAIL_USERNAME');
                $mail->Password = getenv('MAIL_PASSWORD');
                $mail->SMTPSecure = PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_STARTTLS;
                $mail->Port = getenv('MAIL_PORT') ?: 2525;
                
                // Disable SSL verification for Mailtrap (development only)
                $mail->SMTPOptions = array(
                    'ssl' => array(
                        'verify_peer' => false,
                        'verify_peer_name' => false,
                        'allow_self_signed' => true
                    )
                );
                
                // Recipients
                $mail->setFrom(getenv('MAIL_FROM_ADDRESS') ?: 'noreply@mylibrary.com', getenv('MAIL_FROM_NAME') ?: 'My Library');
                $mail->addAddress($user['email'], $user['username']);
                
                // Content
                $resetLink = (getenv('APP_URL') ?: 'http://localhost:3400/') . '/reset_password.php?token=' . $token;
                $mail->isHTML(true);
                $mail->Subject = 'Password Reset Request - My Library';
                $mail->Body = "
                    <html>
                    <body style='font-family: Arial, sans-serif; line-height: 1.6; color: #333;'>
                        <div style='max-width: 600px; margin: 0 auto; padding: 20px; background: #FFF3F0; border-radius: 10px;'>
                            <div style='background: #E07A5F; color: white; padding: 20px; border-radius: 10px 10px 0 0; text-align: center;'>
                                <h1 style='margin: 0;'>Password Reset Request</h1>
                            </div>
                            <div style='background: white; padding: 30px; border-radius: 0 0 10px 10px;'>
                                <p>Hello <strong>{$user['username']}</strong>,</p>
                                <p>We received a request to reset your password. Click the button below to reset it:</p>
                                <div style='text-align: center; margin: 30px 0;'>
                                    <a href='{$resetLink}' style='background: #E07A5F; color: white; padding: 12px 30px; text-decoration: none; border-radius: 5px; display: inline-block; font-weight: bold;'>Reset Password</a>
                                </div>
                                <p>Or copy and paste this link into your browser:</p>
                                <p style='background: #f8f9fa; padding: 10px; border-radius: 5px; word-break: break-all;'>{$resetLink}</p>
                                <p><strong>This link will expire in 1 hour.</strong></p>
                                <p>If you didn't request a password reset, please ignore this email.</p>
                                <hr style='border: none; border-top: 1px solid #e9ecef; margin: 20px 0;'>
                                <p style='font-size: 0.9rem; color: #6c757d;'>Best regards,<br>My Library Team</p>
                            </div>
                        </div>
                    </body>
                    </html>
                ";
                $mail->AltBody = "Hello {$user['username']},\n\nWe received a request to reset your password. Click the link below to reset it:\n\n{$resetLink}\n\nThis link will expire in 1 hour.\n\nIf you didn't request a password reset, please ignore this email.\n\nBest regards,\nMy Library Team";
                
                $mail->send();
                $success = 'Password reset instructions have been sent to your email address. Please check your inbox.';
            } catch (Exception $e) {
                error_log('Email sending failed: ' . $mail->ErrorInfo);
                // Still show success to user (security best practice)
                $success = 'If an account exists with this email, password reset instructions have been sent.';
            }
        } else {
            // Don't reveal if email exists or not (security best practice)
            $success = 'If an account exists with this email, password reset instructions have been sent.';
        }
    }
}

$pageTitle = 'Forgot Password - My Library';
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
        
        .info-message {
            color: var(--text-light);
            font-size: 1rem;
            line-height: 1.6;
            margin-bottom: 25px;
            text-align: center;
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
    </style>
</head>
<body>
    <div class="auth-container">
        <div class="auth-card animate__animated animate__zoomIn">
            <div class="auth-header">
                <div class="auth-header-content">
                    <div class="auth-icon-circle">
                        <i class="fas fa-fingerprint"></i>
                    </div>
                    <h2>Reset Access</h2>
                    <p>Unlock your reading journey</p>
                </div>
            </div>
            
            <div class="auth-body">
                <div class="info-message">
                    Enter the email associated with your account and we'll send a secure link to reset your password.
                </div>
                
                <form method="POST" action="" id="forgotPasswordForm">
                    <div class="mb-4">
                        <input type="email" class="form-control" id="email" name="email" 
                               placeholder="Email Address" required autofocus>
                    </div>
                    
                    <button type="submit" class="btn btn-primary-custom">
                        Send Recovery Link
                    </button>
                </form>
                
                <a href="login.php" class="back-link">
                    <i class="fas fa-arrow-left"></i> Back to login
                </a>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script>
        <?php if ($error): ?>
        Swal.fire({
            icon: 'error',
            title: 'Request Failed',
            text: <?= json_encode($error) ?>,
            confirmButtonColor: '#E07A5F'
        });
        <?php endif; ?>
        
        <?php if ($success): ?>
        Swal.fire({
            icon: 'success',
            title: 'Recovery Email Sent',
            text: <?= json_encode($success) ?>,
            confirmButtonColor: '#E07A5F'
        }).then(() => {
            window.location.href = 'login.php';
        });
        <?php endif; ?>
        
        document.getElementById('forgotPasswordForm').addEventListener('submit', function(e) {
            Swal.fire({
                title: 'Sending Recovery Link...',
                text: 'Please wait',
                allowOutsideClick: false,
                allowEscapeKey: false,
                showConfirmButton: false,
                didOpen: () => {
                    Swal.showLoading();
                }
            });
        });
    </script>
</body>
</html>
