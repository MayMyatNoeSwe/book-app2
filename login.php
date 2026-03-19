<?php
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

$config = require 'config/database.php';
$dsn = "mysql:host={$config['host']};dbname={$config['dbname']};charset={$config['charset']}";
$pdo = new PDO($dsn, $config['username'], $config['password'], $config['options']);

$error = '';
$success = $_SESSION['flash_message'] ?? null;
unset($_SESSION['flash_message']);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $usernameOrEmail = trim($_POST['username_email'] ?? '');
    $password = $_POST['password'] ?? '';
    $remember = isset($_POST['remember']);
    
    if (empty($usernameOrEmail) || empty($password)) {
        $error = 'Please enter both username/email and password';
    } else {
        $auth = new Auth($pdo);
        if ($auth->attempt($usernameOrEmail, $password)) {
            // Set remember me cookie if checked
            if ($remember) {
                setcookie('remember_user', $usernameOrEmail, time() + (86400 * 30), '/'); // 30 days
            }
            
            // Redirect to intended page or home
            setFlashMessage("Welcome back! You have successfully signed in.", "success");
            $redirect = $_GET['redirect'] ?? 'index.php';
            header('Location: ' . $redirect);
            exit;
        } else {
            $error = 'Invalid username/email or password';
        }
    }
}

$pageTitle = 'Login - My Library';
$rememberedUser = $_COOKIE['remember_user'] ?? '';
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
            background: url('https://images.unsplash.com/photo-1524995997946-a1c2e315a42f?ixlib=rb-1.2.1&auto=format&fit=crop&w=1200&q=80') center/cover;
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
            font-size: 2.5rem;
            margin-bottom: 15px;
            display: inline-block;
            filter: drop-shadow(0 4px 12px rgba(0,0,0,0.25));
        }
        
        .auth-left h1 {
            font-family: 'Playfair Display', serif;
            font-size: 2.2rem;
            font-weight: 700;
            margin-bottom: 10px;
            line-height: 1.1;
        }
        
        .auth-left p {
            font-size: 1rem;
            opacity: 0.9;
            margin-bottom: 30px;
            line-height: 1.6;
            font-weight: 400;
        }
        
        .stats-grid {
            display: flex;
            gap: 24px;
        }
        
        .stat-item {
            background: rgba(255, 255, 255, 0.18);
            backdrop-filter: blur(15px);
            padding: 12px 15px;
            border-radius: 15px;
            border: 1px solid rgba(255, 255, 255, 0.25);
            flex: 1;
            transition: all 0.3s cubic-bezier(0.175, 0.885, 0.32, 1.275);
        }

        .stat-item:hover {
            transform: translateY(-8px);
            background: rgba(255, 255, 255, 0.25);
            border-color: rgba(255, 255, 255, 0.4);
        }
        
        .stat-number {
            font-size: 1.5rem;
            font-weight: 700;
            display: block;
            margin-bottom: 4px;
        }
        
        .stat-label {
            font-size: 0.8rem;
            opacity: 0.85;
            font-weight: 500;
            letter-spacing: 0.5px;
            text-transform: uppercase;
        }
        
        .auth-right {
            padding: 40px 50px;
            width: 52%;
            display: flex;
            flex-direction: column;
            justify-content: center;
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
            font-size: 1rem;
        }

        .form-control {
            border: 2px solid rgba(0,0,0,0.06);
            border-radius: 12px;
            padding: 10px 15px;
            font-size: 1rem;
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
            transform: scale(1.01);
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
            text-decoration: none;
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
        
        .forgot-password {
            display: block;
            text-align: right;
            margin-top: 12px;
            font-size: 0.95rem;
            color: var(--primary-color);
            text-decoration: none;
            font-weight: 600;
            transition: all 0.3s ease;
        }

        .forgot-password:hover {
            color: var(--primary-hover);
            text-decoration: underline;
        }

        .form-check-input:checked {
            background-color: var(--primary-color);
            border-color: var(--primary-color);
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
            .stats-grid { flex-direction: column; }
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
                    <h1>Welcome Back!</h1>
                    <p>Relive the magic of your favorite stories and discover new worlds today.</p>
                    
                    <div class="stats-grid">
                        <div class="stat-item">
                            <span class="stat-number">1k+</span>
                            <span class="stat-label">Readers</span>
                        </div>
                        <div class="stat-item">
                            <span class="stat-number">5k+</span>
                            <span class="stat-label">Collections</span>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Right Form Section -->
            <div class="auth-right">
                <div class="form-header">
                    <h2>Sign In</h2>
                    <p>Access your personal library dashboard</p>
                </div>
                
                <form method="POST" action="" id="loginForm">
                    <div class="mb-4">
                        <input type="text" class="form-control" id="username_email" name="username_email" 
                               placeholder="Username or Email" value="<?= htmlspecialchars($rememberedUser) ?>" required autofocus>
                    </div>
                    
                    <div class="mb-4">
                        <div class="password-container">
                            <input type="password" class="form-control" id="password" name="password" 
                                   placeholder="Password" required>
                            <span class="password-toggle" onclick="togglePassword('password')">
                                <i class="fas fa-eye" id="password-icon"></i>
                            </span>
                        </div>
                        <a href="forgot_password.php" class="forgot-password">Forgot password?</a>
                    </div>
                    
                    <div class="mb-4 form-check d-flex align-items-center gap-2">
                        <input type="checkbox" class="form-check-input" id="remember" name="remember" <?= $rememberedUser ? 'checked' : '' ?>>
                        <label class="form-check-label" for="remember">Keep me signed in</label>
                    </div>
                    
                    <button type="submit" class="btn btn-primary-custom">
                        Sign In
                    </button>
                </form>
                
                <div class="divider">or continue with</div>
                
                <div class="social-buttons">
                    <a href="oauth/google/login.php" class="btn-social btn-google">
                        <i class="fab fa-google"></i> Google
                    </a>
                    <a href="oauth/facebook/login.php" class="btn-social btn-facebook">
                        <i class="fab fa-facebook-f"></i> Facebook
                    </a>
                </div>
                
                <div class="footer-links">
                    Don't have an account? <a href="register.php">Create Free Account</a>
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
        
        <?php if ($error): ?>
        Swal.fire({
            icon: 'error',
            title: 'Login Failed',
            text: <?= json_encode($error) ?>,
            confirmButtonColor: '#E07A5F'
        });
        <?php endif; ?>
        
        <?php if ($success): ?>
        Swal.fire({
            icon: '<?= $success['type'] === 'success' ? 'success' : 'info' ?>',
            title: '<?= $success['type'] === 'success' ? 'Success!' : 'Notice' ?>',
            text: <?= json_encode($success['text']) ?>,
            confirmButtonColor: '#E07A5F'
        });
        <?php endif; ?>
        
        document.getElementById('loginForm').addEventListener('submit', function(e) {
            Swal.fire({
                title: 'Signing In...',
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
