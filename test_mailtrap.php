<?php
/**
 * Mailtrap Email Test Script
 * 
 * This script tests your Mailtrap.io configuration by sending a test email.
 * Run this file to verify your email settings are working correctly.
 */

require_once 'includes/env_loader.php';
require_once 'vendor/autoload.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mailtrap Email Test</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }
        .test-card {
            background: white;
            border-radius: 20px;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
            max-width: 800px;
            width: 100%;
        }
        .test-header {
            background: linear-gradient(135deg, #2e8a40, #34495e);
            color: white;
            padding: 30px;
            border-radius: 20px 20px 0 0;
            text-align: center;
        }
        .test-body {
            padding: 40px;
        }
        .config-item {
            background: #f8f9fa;
            padding: 15px;
            border-radius: 10px;
            margin-bottom: 15px;
            border-left: 4px solid #2e8a40;
        }
        .config-label {
            font-weight: 600;
            color: #6c757d;
            font-size: 0.9rem;
            margin-bottom: 5px;
        }
        .config-value {
            font-family: 'Courier New', monospace;
            color: #2e8a40;
            font-size: 1rem;
        }
        .status-success {
            background: #d4edda;
            border-color: #28a745;
            color: #155724;
        }
        .status-error {
            background: #f8d7da;
            border-color: #dc3545;
            color: #721c24;
        }
        .status-warning {
            background: #fff3cd;
            border-color: #ffc107;
            color: #856404;
        }
    </style>
</head>
<body>
    <div class="test-card">
        <div class="test-header">
            <h1><i class="fas fa-envelope-open-text me-3"></i>Mailtrap Email Test</h1>
            <p class="mb-0">Testing your email configuration</p>
        </div>
        
        <div class="test-body">
            <h4 class="mb-4"><i class="fas fa-cog me-2"></i>Current Configuration</h4>
            
            <div class="config-item">
                <div class="config-label">MAIL_HOST</div>
                <div class="config-value"><?= getenv('MAIL_HOST') ?: 'Not set' ?></div>
            </div>
            
            <div class="config-item">
                <div class="config-label">MAIL_PORT</div>
                <div class="config-value"><?= getenv('MAIL_PORT') ?: 'Not set' ?></div>
            </div>
            
            <div class="config-item">
                <div class="config-label">MAIL_USERNAME</div>
                <div class="config-value"><?= getenv('MAIL_USERNAME') ? '***' . substr(getenv('MAIL_USERNAME'), -4) : 'Not set' ?></div>
            </div>
            
            <div class="config-item">
                <div class="config-label">MAIL_PASSWORD</div>
                <div class="config-value"><?= getenv('MAIL_PASSWORD') ? '***' . substr(getenv('MAIL_PASSWORD'), -4) : 'Not set' ?></div>
            </div>
            
            <div class="config-item">
                <div class="config-label">MAIL_FROM_ADDRESS</div>
                <div class="config-value"><?= getenv('MAIL_FROM_ADDRESS') ?: 'Not set' ?></div>
            </div>
            
            <hr class="my-4">
            
            <?php
            if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                $testEmail = trim($_POST['test_email'] ?? '');
                
                if (empty($testEmail) || !filter_var($testEmail, FILTER_VALIDATE_EMAIL)) {
                    echo '<div class="alert status-warning"><i class="fas fa-exclamation-triangle me-2"></i>Please enter a valid email address</div>';
                } else {
                    try {
                        $mail = new PHPMailer(true);
                        
                        // Server settings
                        $mail->isSMTP();
                        $mail->Host = getenv('MAIL_HOST') ?: 'sandbox.smtp.mailtrap.io';
                        $mail->SMTPAuth = true;
                        $mail->Username = getenv('MAIL_USERNAME');
                        $mail->Password = getenv('MAIL_PASSWORD');
                        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
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
                        $mail->addAddress($testEmail, 'Test User');
                        
                        // Content
                        $mail->isHTML(true);
                        $mail->Subject = 'Mailtrap Test Email - My Library';
                        $mail->Body = "
                            <html>
                            <body style='font-family: Arial, sans-serif; line-height: 1.6; color: #333;'>
                                <div style='max-width: 600px; margin: 0 auto; padding: 20px; background: #f8f9fa; border-radius: 10px;'>
                                    <div style='background: #2e8a40; color: white; padding: 20px; border-radius: 10px 10px 0 0; text-align: center;'>
                                        <h1 style='margin: 0;'>✅ Email Test Successful!</h1>
                                    </div>
                                    <div style='background: white; padding: 30px; border-radius: 0 0 10px 10px;'>
                                        <h2 style='color: #2e8a40;'>Congratulations!</h2>
                                        <p>Your Mailtrap email configuration is working correctly.</p>
                                        
                                        <div style='background: #f8f9fa; padding: 15px; border-radius: 5px; margin: 20px 0;'>
                                            <strong>Configuration Details:</strong><br>
                                            Host: " . getenv('MAIL_HOST') . "<br>
                                            Port: " . getenv('MAIL_PORT') . "<br>
                                            From: " . getenv('MAIL_FROM_ADDRESS') . "
                                        </div>
                                        
                                        <p>You can now use the password reset feature with confidence!</p>
                                        
                                        <div style='text-align: center; margin: 30px 0;'>
                                            <a href='http://localhost:3400/forgot_password.php' style='background: #2e8a40; color: white; padding: 12px 30px; text-decoration: none; border-radius: 5px; display: inline-block; font-weight: bold;'>Test Password Reset</a>
                                        </div>
                                        
                                        <hr style='border: none; border-top: 1px solid #e9ecef; margin: 20px 0;'>
                                        <p style='font-size: 0.9rem; color: #6c757d;'>
                                            This is a test email sent at " . date('Y-m-d H:i:s') . "<br>
                                            My Library - Book Management System
                                        </p>
                                    </div>
                                </div>
                            </body>
                            </html>
                        ";
                        $mail->AltBody = "Email Test Successful!\n\nYour Mailtrap email configuration is working correctly.\n\nConfiguration:\nHost: " . getenv('MAIL_HOST') . "\nPort: " . getenv('MAIL_PORT') . "\nFrom: " . getenv('MAIL_FROM_ADDRESS') . "\n\nYou can now use the password reset feature!\n\nMy Library Team";
                        
                        $mail->send();
                        
                        echo '<div class="alert status-success">
                            <h5><i class="fas fa-check-circle me-2"></i>Email Sent Successfully!</h5>
                            <p class="mb-2">Test email has been sent to: <strong>' . htmlspecialchars($testEmail) . '</strong></p>
                            <p class="mb-0">Check your Mailtrap inbox at <a href="https://mailtrap.io/inboxes" target="_blank">https://mailtrap.io/inboxes</a></p>
                        </div>';
                        
                        echo '<div class="alert alerttps://www.pannsattlann.com-info mt-3">
                            <h6><i class="fas fa-info-circle me-2"></i>Next Steps:</h6>
                            <ol class="mb-0">
                                <li>Go to your Mailtrap inbox</li>
                                <li>You should see the test email</li>
                                <li>Click on it to view the HTML and text versions</li>
                                <li>Check the spam score and validation</li>
                                <li>Now test the actual password reset feature!</li>
                            </ol>
                        </div>';
                        
                    } catch (Exception $e) {
                        echo '<div class="alert status-error">
                            <h5><i class="fas fa-times-circle me-2"></i>Email Sending Failed</h5>
                            <p class="mb-2"><strong>Error:</strong> ' . htmlspecialchars($mail->ErrorInfo) . '</p>
                            <hr>
                            <h6>Troubleshooting:</h6>
                            <ul class="mb-0">
                                <li>Check your .env file has correct Mailtrap credentials</li>
                                <li>Verify MAIL_USERNAME and MAIL_PASSWORD are set</li>
                                <li>Make sure you copied the credentials from Mailtrap correctly</li>
                                <li>Check if there are any spaces or quotes in your .env values</li>
                                <li>See MAILTRAP_SETUP_GUIDE.md for detailed instructions</li>
                            </ul>
                        </div>';
                    }
                }
            }
            ?>
            
            <h4 class="mb-3"><i class="fas fa-paper-plane me-2"></i>Send Test Email</h4>
            <form method="POST">
                <div class="mb-3">
                    <label for="test_email" class="form-label">Test Email Address</label>
                    <input type="email" class="form-control form-control-lg" id="test_email" name="test_email" 
                           placeholder="test@example.com" required>
                    <div class="form-text">
                        <i class="fas fa-lightbulb me-1"></i>
                        This can be any email address - it will be captured by Mailtrap, not sent to a real inbox
                    </div>
                </div>
                <button type="submit" class="btn btn-primary btn-lg w-100">
                    <i class="fas fa-envelope me-2"></i>Send Test Email
                </button>
            </form>
            
            <hr class="my-4">
            
            <div class="alert alert-info">
                <h6><i class="fas fa-book me-2"></i>Documentation</h6>
                <ul class="mb-0">
                    <li><a href="MAILTRAP_SETUP_GUIDE.md" target="_blank">Mailtrap Setup Guide</a></li>
                    <li><a href="EMAIL_SETUP_GUIDE.md" target="_blank">Email Setup Guide</a></li>
                    <li><a href="https://mailtrap.io/inboxes" target="_blank">Mailtrap Inbox</a></li>
                </ul>
            </div>
            
            <div class="text-center mt-4">
                <a href="forgot_password.php" class="btn btn-outline-primary">
                    <i class="fas fa-key me-2"></i>Test Password Reset
                </a>
                <a href="index.php" class="btn btn-outline-secondary">
                    <i class="fas fa-home me-2"></i>Back to Home
                </a>
            </div>
        </div>
    </div>
</body>
</html>
