<?php
require_once 'includes/sessions.php';
require_once 'src/Auth.php';

use App\Auth;

// Store username before logout for display
$username = Auth::user() ?? 'Guest';

// Perform logout
Auth::logout();

$pageTitle = 'Logged Out - My Library';
?>
<!DOCTYPE html>
<html lang="en" data-bs-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($pageTitle) ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="public/css/custom.css">
    <style>
        .logout-container {
            min-height: 100vh;
            background: linear-gradient(135deg, #c8e6f5 0%, #b8e6d5 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 40px 20px;
            position: relative;
            overflow: hidden;
        }
        
        .logout-container::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: url('https://images.unsplash.com/photo-1481627834876-b7833e8f5570?ixlib=rb-1.2.1&auto=format&fit=crop&w=1920&q=80') center/cover;
            opacity: 0.05;
            z-index: 0;
        }
        
        .logout-card {
            background: white;
            border-radius: 24px;
            box-shadow: 0 15px 45px rgba(0, 0, 0, 0.1);
            overflow: hidden;
            max-width: 600px;
            width: 100%;
            position: relative;
            z-index: 1;
            text-align: center;
            padding: 30px 40px;
        }
        
        [data-bs-theme="dark"] .logout-card {
            background: #1e293b;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.4);
        }
        
        .logout-icon-wrapper {
            width: 80px;
            height: 80px;
            margin: 0 auto 20px;
            background: linear-gradient(135deg, var(--primary-color) 0%, #34495e 100%);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            position: relative;
            animation: fadeInScale 0.6s ease-out;
        }
        
        .logout-icon-wrapper::before {
            content: '';
            position: absolute;
            inset: -6px;
            border-radius: 50%;
            background: linear-gradient(135deg, var(--primary-color) 0%, #34495e 100%);
            opacity: 0.2;
            animation: pulse-ring 2s ease-out infinite;
        }
        
        @keyframes pulse-ring {
            0% {
                transform: scale(0.95);
                opacity: 0.3;
            }
            50% {
                transform: scale(1.05);
                opacity: 0.1;
            }
            100% {
                transform: scale(0.95);
                opacity: 0.3;
            }
        }
        
        @keyframes fadeInScale {
            0% {
                opacity: 0;
                transform: scale(0.5);
            }
            100% {
                opacity: 1;
                transform: scale(1);
            }
        }
        
        .logout-icon {
            font-size: 2.2rem;
            color: white;
            position: relative;
            z-index: 1;
        }
        
        .logout-title {
            font-family: 'Playfair Display', serif;
            font-size: 2rem;
            font-weight: 700;
            color: var(--text-dark);
            margin-bottom: 10px;
            animation: fadeInUp 0.6s ease-out 0.2s both;
        }
        
        [data-bs-theme="dark"] .logout-title {
            color: #e2e8f0;
        }
        
        .logout-message {
            font-size: 1rem;
            color: #6c757d;
            margin-bottom: 5px;
            animation: fadeInUp 0.6s ease-out 0.3s both;
        }
        
        [data-bs-theme="dark"] .logout-message {
            color: #94a3b8;
        }
        
        .logout-username {
            font-size: 1.1rem;
            font-weight: 600;
            color: var(--primary-color);
            margin-bottom: 25px;
            animation: fadeInUp 0.6s ease-out 0.4s both;
        }
        
        [data-bs-theme="dark"] .logout-username {
            color: var(--accent-color);
        }
        
        @keyframes fadeInUp {
            0% {
                opacity: 0;
                transform: translateY(20px);
            }
            100% {
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        .action-buttons {
            display: flex;
            gap: 15px;
            justify-content: center;
            flex-wrap: wrap;
            animation: fadeInUp 0.6s ease-out 0.5s both;
        }
        
        .btn-action {
            padding: 10px 24px;
            border-radius: 12px;
            font-weight: 600;
            font-size: 1rem;
            transition: all 0.3s ease;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 10px;
        }
        
        .btn-primary-action {
            background: linear-gradient(135deg, var(--primary-color) 0%, #34495e 100%);
            color: white;
            border: none;
        }
        
        .btn-primary-action:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(46, 138, 64, 0.3);
            color: white;
        }
        
        .btn-secondary-action {
            background: white;
            color: var(--primary-color);
            border: 2px solid var(--primary-color);
        }
        
        .btn-secondary-action:hover {
            background: var(--primary-color);
            color: white;
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(46, 138, 64, 0.2);
        }
        
        [data-bs-theme="dark"] .btn-secondary-action {
            background: #0f172a;
            color: var(--accent-color);
            border-color: var(--accent-color);
        }
        
        [data-bs-theme="dark"] .btn-secondary-action:hover {
            background: var(--accent-color);
            color: #0f172a;
        }
        
        .features-grid {
            margin-top: 30px;
            padding-top: 25px;
            border-top: 1px solid #e9ecef;
            animation: fadeInUp 0.6s ease-out 0.6s both;
        }
        
        [data-bs-theme="dark"] .features-grid {
            border-top-color: #334155;
        }
        
        .features-title {
            font-size: 1.1rem;
            font-weight: 600;
            color: var(--text-dark);
            margin-bottom: 25px;
        }
        
        [data-bs-theme="dark"] .features-title {
            color: #e2e8f0;
        }
        
        .feature-items {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
            gap: 20px;
        }
        
        .feature-item {
            padding: 15px 10px;
            background: #f8f9fa;
            border-radius: 12px;
            transition: all 0.3s ease;
        }
        
        .feature-item:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 20px rgba(0, 0, 0, 0.1);
        }
        
        [data-bs-theme="dark"] .feature-item {
            background: #0f172a;
        }
        
        .feature-item i {
            font-size: 1.5rem;
            color: var(--primary-color);
            margin-bottom: 8px;
        }
        
        [data-bs-theme="dark"] .feature-item i {
            color: var(--accent-color);
        }
        
        .feature-item .title {
            font-weight: 600;
            color: var(--text-dark);
            font-size: 0.9rem;
            margin-bottom: 5px;
        }
        
        [data-bs-theme="dark"] .feature-item .title {
            color: #e2e8f0;
        }
        
        .feature-item .description {
            font-size: 0.8rem;
            color: #6c757d;
        }
        
        [data-bs-theme="dark"] .feature-item .description {
            color: #94a3b8;
        }
        
        .theme-toggle {
            position: absolute;
            top: 20px;
            right: 20px;
            z-index: 10;
        }
        
        .theme-toggle .btn {
            width: 45px;
            height: 45px;
            border-radius: 50%;
            background: rgba(255, 255, 255, 0.9);
            border: none;
            color: var(--primary-color);
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
            transition: all 0.3s ease;
        }
        
        [data-bs-theme="dark"] .theme-toggle .btn {
            background: rgba(30, 41, 59, 0.9);
            color: var(--accent-color);
        }
        
        .theme-toggle .btn:hover {
            transform: rotate(180deg) scale(1.1);
            box-shadow: 0 6px 20px rgba(0, 0, 0, 0.15);
        }
        
        .countdown-text {
            font-size: 0.85rem;
            color: #6c757d;
            margin-top: 20px;
            animation: fadeInUp 0.6s ease-out 0.7s both;
        }
        
        [data-bs-theme="dark"] .countdown-text {
            color: #94a3b8;
        }
        
        .countdown-number {
            font-weight: 700;
            color: var(--primary-color);
        }
        
        [data-bs-theme="dark"] .countdown-number {
            color: var(--accent-color);
        }
        
        @media (max-width: 576px) {
            .logout-card {
                padding: 40px 25px;
            }
            
            .logout-title {
                font-size: 2rem;
            }
            
            .action-buttons {
                flex-direction: column;
            }
            
            .btn-action {
                width: 100%;
                justify-content: center;
            }
            
            .feature-items {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <div class="logout-container">
        <div class="theme-toggle">
            <button class="btn" onclick="toggleTheme()" id="themeToggle">
                <i class="fas fa-moon"></i>
            </button>
        </div>
        
        <div class="logout-card">
            <div class="logout-icon-wrapper">
                <i class="fas fa-check-circle logout-icon"></i>
            </div>
            
            <h1 class="logout-title">Successfully Logged Out</h1>
            <p class="logout-message">See you soon,</p>
            <p class="logout-username"><?= htmlspecialchars($username) ?>!</p>
            
            <div class="action-buttons">
                <a href="login.php" class="btn-action btn-primary-action">
                    <i class="fas fa-sign-in-alt"></i>
                    Sign In Again
                </a>
                <a href="index.php" class="btn-action btn-secondary-action">
                    <i class="fas fa-home"></i>
                    Go to Home
                </a>
            </div>
            
            <p class="countdown-text">
                <i class="fas fa-clock me-1"></i>
                Redirecting to home in <span class="countdown-number" id="countdown">3</span> seconds...
            </p>
            
            <div class="features-grid">
                <h3 class="features-title">What You Can Do Next</h3>
                <div class="feature-items">
                    <div class="feature-item">
                        <i class="fas fa-book"></i>
                        <div class="title">Browse Books</div>
                        <div class="description">Explore our collection</div>
                    </div>
                    <div class="feature-item">
                        <i class="fas fa-user-plus"></i>
                        <div class="title">Create Account</div>
                        <div class="description">Join our community</div>
                    </div>
                    <div class="feature-item">
                        <i class="fas fa-search"></i>
                        <div class="title">Search Library</div>
                        <div class="description">Find your next read</div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Theme Toggle
        function toggleTheme() {
            const html = document.documentElement;
            const currentTheme = html.getAttribute('data-bs-theme');
            const newTheme = currentTheme === 'light' ? 'dark' : 'light';
            html.setAttribute('data-bs-theme', newTheme);
            
            const icon = document.querySelector('#themeToggle i');
            icon.className = newTheme === 'light' ? 'fas fa-moon' : 'fas fa-sun';
            
            localStorage.setItem('theme', newTheme);
        }
        
        // Load saved theme
        const savedTheme = localStorage.getItem('theme') || 'light';
        document.documentElement.setAttribute('data-bs-theme', savedTheme);
        document.querySelector('#themeToggle i').className = savedTheme === 'light' ? 'fas fa-moon' : 'fas fa-sun';
        
        // Countdown and redirect
        let countdown = 3;
        const countdownElement = document.getElementById('countdown');
        
        const countdownInterval = setInterval(() => {
            countdown--;
            countdownElement.textContent = countdown;
            
            if (countdown <= 0) {
                clearInterval(countdownInterval);
                window.location.href = 'index.php';
            }
        }, 1000);
        
        // Cancel countdown if user clicks any button
        document.querySelectorAll('.btn-action').forEach(btn => {
            btn.addEventListener('click', () => {
                clearInterval(countdownInterval);
            });
        });
    </script>
</body>
</html>
