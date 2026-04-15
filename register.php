<?php
require_once 'vendor/autoload.php';
require_once 'includes/sessions.php';
require_once 'includes/env_loader.php';
require_once 'config/database.php';
require_once 'src/Auth.php';
require_once 'includes/functions.php';

use App\Auth;

// Redirect if already logged in
if (Auth::check()) {
    header('Location: index.php');
    exit;
}

// Check if registration is allowed
$isRegistrationEnabled = getSetting('allow_registration', '1') === '1';
if (!$isRegistrationEnabled) {
    $pageTitle = 'Registration Disabled - My Library';
    ?>
    <!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title><?= $pageTitle ?></title>
        <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
        <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;700&family=Playfair+Display:wght@700&display=swap" rel="stylesheet">
        <style>
            body { background: #FFF3F0; font-family: 'Inter', sans-serif; height: 100vh; display: flex; align-items: center; justify-content: center; }
            .notice-card { background: white; border-radius: 30px; padding: 50px; text-align: center; max-width: 500px; box-shadow: 0 20px 50px rgba(0,0,0,0.1); border: 1px solid rgba(0,0,0,0.05); }
            h2 { font-family: 'Playfair Display', serif; color: #3D405B; margin-bottom: 20px; font-weight: 800; }
            p { color: #636E72; line-height: 1.6; margin-bottom: 30px; }
            .btn-home { background: #E07A5F; color: white; border-radius: 12px; padding: 12px 30px; font-weight: 700; text-decoration: none; display: inline-block; transition: all 0.3s; }
            .btn-home:hover { background: #cf6a50; transform: translateY(-3px); color: white; }
            .icon-box { font-size: 4rem; color: #E07A5F; margin-bottom: 25px; opacity: 0.2; }
        </style>
    </head>
    <body>
        <div class="notice-card animate__animated animate__fadeInUp">
            <div class="icon-box"><i class="fas fa-user-lock"></i></div>
            <h2>Membership Closed</h2>
            <p>We are currently not accepting new member registrations at this time. Please check back later or contact our support team if you have an inquiry.</p>
            <a href="index.php" class="btn-home"><i class="fas fa-arrow-left me-2"></i>Return Home</a>
        </div>
    </body>
    </html>
    <?php
    exit;
}

$config = require 'config/database.php';
$dsn = "mysql:host={$config['host']};dbname={$config['dbname']};charset={$config['charset']}";
$pdo = new PDO($dsn, $config['username'], $config['password'], $config['options']);

$errors = [];
$success = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirmPassword = $_POST['confirm_password'] ?? '';
    
    // Validation
    if (empty($username)) {
        $errors[] = 'Username is required';
    } elseif (strlen($username) < 3) {
        $errors[] = 'Username must be at least 3 characters';
    }
    
    if (empty($email)) {
        $errors[] = 'Email is required';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Invalid email format';
    }
    
    if (empty($password)) {
        $errors[] = 'Password is required';
    } elseif (strlen($password) < 6) {
        $errors[] = 'Password must be at least 6 characters';
    }
    
    if ($password !== $confirmPassword) {
        $errors[] = 'Passwords do not match';
    }
    
    // Check if username or email already exists
    if (empty($errors)) {
        $stmt = $pdo->prepare("SELECT id FROM users WHERE username = ? OR email = ?");
        $stmt->execute([$username, $email]);
        if ($stmt->fetch()) {
            $errors[] = 'Username or email already exists';
        }
    }
    
    // Register user
    if (empty($errors)) {
        $auth = new Auth($pdo);
        if ($auth->register($username, $email, $password)) {
            $success = true;
            $_SESSION['flash_message'] = [
                'text' => 'Registration successful! Please login.',
                'type' => 'success'
            ];
        } else {
            $errors[] = 'Registration failed. Please try again.';
        }
    }
}

$pageTitle = 'Register - My Library';
?>
<!DOCTYPE html>
<html lang="en" data-bs-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($pageTitle) ?></title>
    
    <!-- Link dependencies -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Playfair+Display:wght@700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="public/css/custom.css">
    <link rel="stylesheet" href="public/css/premium_new.css">

    <style>
        :root {
            --primary-color: #E07A5F;
            --primary-hover: #d16a4f;
            --beige-bg: #FFF3F0;
            --text-main: #2D3436;
            --text-light: #636E72;
            --card-radius: 28px;
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
            box-shadow: 0 50px 100px -20px rgba(0, 0, 0, 0.15);
            overflow: hidden;
            max-width: 900px;
            width: 100%;
            display: flex;
            border: 1px solid rgba(0,0,0,0.05);
            position: relative;
        }

        [data-bs-theme="dark"] .auth-card {
            border-color: rgba(255,255,255,0.03);
            box-shadow: 0 50px 100px -20px rgba(0, 0, 0, 0.45);
        }
        
        .auth-left {
            background: url('https://images.unsplash.com/photo-1512820790803-83ca734da794?ixlib=rb-1.2.1&auto=format&fit=crop&w=1200&q=80') center/cover;
            padding: 30px 40px;
            width: 48%;
            color: white;
            position: relative;
            display: flex;
            flex-direction: column;
            justify-content: center;
        }
        
        .auth-left::after {
            content: '';
            position: absolute;
            top: 0; left: 0; right: 0; bottom: 0;
            background: linear-gradient(135deg, rgba(224, 122, 95, 0.96) 0%, rgba(61, 64, 91, 0.85) 100%);
            z-index: 1;
        }
        
        .auth-left-content {
            position: relative;
            z-index: 2;
        }
        
        .auth-logo-icon {
            font-size: 2.2rem;
            margin-bottom: 12px;
            display: inline-block;
            filter: drop-shadow(0 4px 12px rgba(0,0,0,0.25));
        }
        
        .auth-left h1 {
            font-family: 'Playfair Display', serif;
            font-size: 2rem;
            font-weight: 700;
            margin-bottom: 15px;
            line-height: 1.1;
        }
        
        .auth-left p {
            font-size: 0.95rem;
            opacity: 0.9;
            margin-bottom: 25px;
            line-height: 1.5;
            font-weight: 400;
        }
        
        .features-list {
            list-style: none;
            padding: 0;
            margin-top: 20px;
        }
        
        .feature-item {
            display: flex;
            align-items: center;
            gap: 10px;
            margin-bottom: 10px;
            background: rgba(255, 255, 255, 0.15);
            backdrop-filter: blur(10px);
            padding: 8px 12px;
            border-radius: 14px;
            border: 1px solid rgba(255, 255, 255, 0.2);
            transition: all 0.3s ease;
        }

        .feature-item:hover {
            transform: translateX(10px);
            background: rgba(255, 255, 255, 0.25);
        }
        
        .feature-item i {
            font-size: 1.2rem;
            color: #fff;
        }

        .feature-item span {
            font-weight: 500;
            font-size: 1.05rem;
        }
        
        .auth-right {
            padding: 30px 50px;
            width: 52%;
            display: flex;
            flex-direction: column;
            justify-content: center;
            max-height: 95vh;
            overflow-y: auto;
        }
        
        .form-header {
            margin-bottom: 20px;
        }
        
        .form-header h2 {
            font-family: 'Playfair Display', serif;
            font-size: 1.8rem;
            font-weight: 700;
            color: var(--text-main);
            margin-bottom: 5px;
        }
        
        .form-header p {
            color: var(--text-light);
            font-size: 0.95rem;
        }

        .form-label {
            font-weight: 600;
            color: var(--text-main);
            margin-bottom: 10px;
            font-size: 0.95rem;
        }

        .form-control {
            border: 2px solid rgba(0,0,0,0.06);
            border-radius: 12px;
            padding: 10px 14px;
            font-size: 0.95rem;
            transition: all 0.4s cubic-bezier(0.165, 0.84, 0.44, 1);
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
            box-shadow: 0 0 0 5px rgba(224, 122, 95, 0.12);
        }

        .btn-primary-custom {
            background: linear-gradient(135deg, var(--primary-color) 0%, var(--primary-hover) 100%);
            color: white;
            border: none;
            border-radius: 12px;
            padding: 12px;
            font-weight: 700;
            font-size: 1rem;
            width: 100%;
            cursor: pointer;
            transition: all 0.4s cubic-bezier(0.19, 1, 0.22, 1);
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 12px;
            box-shadow: 0 12px 24px -6px rgba(224, 122, 95, 0.3);
            margin-top: 15px;
        }

        .btn-primary-custom:hover {
            transform: translateY(-4px);
            box-shadow: 0 20px 40px -8px rgba(224, 122, 95, 0.4);
            color: white;
            filter: brightness(1.1);
        }
        
        .divider {
            display: flex;
            align-items: center;
            text-align: center;
            margin: 20px 0;
            color: var(--text-light);
            font-size: 0.95rem;
            font-weight: 600;
        }
        
        .divider::before, .divider::after {
            content: '';
            flex: 1;
            border-bottom: 2px solid rgba(0,0,0,0.05);
        }

        [data-bs-theme="dark"] .divider::before, 
        [data-bs-theme="dark"] .divider::after {
            border-bottom-color: rgba(255,255,255,0.05);
        }
        
        .divider:not(:empty)::before { margin-right: 25px; }
        .divider:not(:empty)::after { margin-left: 25px; }
        
        .social-buttons {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 12px;
        }
        
        .btn-social {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            padding: 10px;
            border: 2px solid rgba(0,0,0,0.05);
            border-radius: 16px;
            background: transparent;
            color: var(--text-main);
            font-weight: 600;
            text-decoration: none;
            transition: all 0.3s ease;
        }

        [data-bs-theme="dark"] .btn-social {
            border-color: rgba(255,255,255,0.05);
        }
        
        .btn-social:hover {
            border-color: var(--primary-color);
            background: rgba(224, 122, 95, 0.05);
            transform: translateY(-3px);
        }
        
        .btn-google i { color: #DB4437; }
        .btn-facebook i { color: #1877F2; }

        .footer-links {
            margin-top: 25px;
            text-align: center;
            font-size: 0.9rem;
            color: var(--text-light);
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 6px;
            flex-wrap: nowrap;
        }
        
        .footer-links a {
            color: var(--primary-color);
            text-decoration: none;
            font-weight: 700;
            margin-left: 8px;
            transition: all 0.3s ease;
        }
        
        .footer-links a:hover {
            color: var(--primary-hover);
            text-decoration: underline;
        }

        .mb-3 { margin-bottom: 0.75rem!important; }
        .mb-4 { margin-bottom: 1rem!important; }

        .password-container {
            position: relative;
        }

        .password-toggle {
            position: absolute;
            right: 20px;
            top: 50%;
            transform: translateY(-50%);
            color: var(--text-light);
            cursor: pointer;
            font-size: 1.25rem;
            transition: color 0.3s;
        }

        .password-toggle:hover {
            color: var(--primary-color);
        }
        
        @media (max-width: 1080px) {
            .auth-card { max-width: 950px; }
            .auth-left { padding: 60px 40px; }
            .auth-right { padding: 60px 50px; }
        }

        @media (max-width: 992px) {
            .auth-left { display: none; }
            .auth-right { width: 100%; padding: 70px 50px; }
            .auth-card { max-width: 580px; }
        }
        
        @media (max-width: 576px) {
            .auth-right { padding: 50px 30px; }
            .social-buttons { grid-template-columns: 1fr; }
            .form-header h2 { font-size: 2.5rem; }
        }
    </style>
</head>
<body>
    <div class="auth-container">
        <div class="auth-card animate__animated animate__zoomIn">
            <!-- Left Branding Section -->
            <div class="auth-left">
                <div class="auth-left-content">
                    <div class="auth-logo-icon">
                        <i class="fas fa-book-reader"></i>
                    </div>
                    <h1>Join My Library</h1>
                    <p>Start your reading journey today and discover new worlds with our community.</p>
                    
                    <ul class="features-list">
                        <li class="feature-item">
                            <i class="fas fa-check-circle"></i>
                            <span>Access 5k+ Premium Books</span>
                        </li>
                        <li class="feature-item">
                            <i class="fas fa-check-circle"></i>
                            <span>Personalized Library Dashboard</span>
                        </li>
                        <li class="feature-item">
                            <i class="fas fa-check-circle"></i>
                            <span>Connect with other Readers</span>
                        </li>
                    </ul>
                </div>
            </div>
            
            <!-- Right Form Section -->
            <div class="auth-right">
                <div class="form-header">
                    <h2>Create Account</h2>
                    <p>Be part of a growing community of book lovers</p>
                </div>
                
                <?php if ($success): ?>
                    <script>
                        document.addEventListener('DOMContentLoaded', function() {
                            Swal.fire({
                                icon: 'success',
                                title: 'Registration Successful!',
                                text: 'Welcome to the club! Redirecting to login...',
                                confirmButtonColor: '#E07A5F',
                                timer: 3000,
                                timerProgressBar: false
                            }).then(() => {
                                window.location.href = 'login.php';
                            });
                        });
                    </script>
                <?php endif; ?>
                
                <?php if (!empty($errors)): ?>
                    <script>
                        document.addEventListener('DOMContentLoaded', function() {
                            Swal.fire({
                                icon: 'error',
                                title: 'Registration Failed',
                                html: '<ul style="text-align: left; padding-left: 20px;"><?php foreach ($errors as $error): ?><li><?= htmlspecialchars($error) ?></li><?php endforeach; ?></ul>',
                                confirmButtonColor: '#E07A5F'
                            });
                        });
                    </script>
                <?php endif; ?>
                
                <?php if (!$success): ?>
                <form method="POST" action="" id="registerForm">
                    <div class="mb-3">
                        <input type="text" class="form-control" id="username" name="username" 
                               placeholder="Username" value="<?= htmlspecialchars($_POST['username'] ?? '') ?>" required>
                    </div>
                    
                    <div class="mb-3">
                        <input type="email" class="form-control" id="email" name="email" 
                               placeholder="Email Address" value="<?= htmlspecialchars($_POST['email'] ?? '') ?>" required>
                    </div>
                    
                    <div class="mb-3">
                        <div class="password-container">
                            <input type="password" class="form-control" id="password" name="password" 
                                   placeholder="Password" required>
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
                    
                    <div class="form-check mb-4 d-flex align-items-center gap-2">
                        <input class="form-check-input" type="checkbox" id="terms" required>
                        <label class="form-check-label small text-muted" for="terms">
                            I agree to the <a href="#">Terms</a> and <a href="#">Privacy Policy</a>
                        </label>
                    </div>
                    
                    <button type="submit" class="btn btn-primary-custom">
                        Create Account
                    </button>
                </form>
                
                <div class="divider">or register with</div>
                
                <div class="social-buttons">
                    <a href="oauth/google/login.php" class="btn-social btn-google">
                        <i class="fab fa-google"></i> Google
                    </a>
                    <a href="oauth/facebook/login.php" class="btn-social btn-facebook">
                        <i class="fab fa-facebook-f"></i> Facebook
                    </a>
                </div>
                <?php endif; ?>
                
                <div class="footer-links">
                    Already have an account? <a href="login.php">Sign In</a>
                </div>
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
        
        document.getElementById('registerForm')?.addEventListener('submit', function(e) {
            const password = document.getElementById('password').value;
            const confirmPassword = document.getElementById('confirm_password').value;
            
            if (password !== confirmPassword) {
                e.preventDefault();
                Swal.fire({
                    icon: 'error',
                    title: 'Passwords Do Not Match',
                    text: 'Please make sure both passwords are the same',
                    confirmButtonColor: '#E07A5F'
                });
                return false;
            }
            
            Swal.fire({
                title: 'Creating Account...',
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
