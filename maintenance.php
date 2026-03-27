<?php
// maintenance.php — Premium Maintenance Mode Page
require_once 'vendor/autoload.php';
require_once 'includes/sessions.php';
require_once 'includes/env_loader.php';
require_once 'includes/functions.php';

// If maintenance mode is OFF, redirect back to home
if (getSetting('maintenance_mode') !== '1') {
    header("Location: index.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Under Maintenance | <?= e(getSetting('site_name', 'BookHouse')) ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Manrope:wght@400;600;800&family=Playfair+Display:wght@700;900&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary: #E07A5F;
            --dark: #3D405B;
            --light: #F4F1DE;
            --mint: #81B29A;
            --yellow: #F2CC8F;
        }

        body {
            font-family: 'Manrope', sans-serif;
            background-color: var(--light);
            color: var(--dark);
            height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0;
            overflow: hidden;
            position: relative;
        }

        /* Animated Background Elements */
        .orb {
            position: absolute;
            border-radius: 50%;
            filter: blur(80px);
            z-index: -1;
            opacity: 0.5;
            animation: move 20s infinite alternate;
        }
        .orb-1 { width: 400px; height: 400px; background: var(--primary); top: -100px; left: -100px; }
        .orb-2 { width: 300px; height: 300px; background: var(--mint); bottom: -50px; right: -50px; animation-delay: -5s; }
        .orb-3 { width: 250px; height: 250px; background: var(--yellow); top: 20%; right: 10%; animation-delay: -10s; }

        @keyframes move {
            from { transform: translate(0, 0) scale(1); }
            to { transform: translate(50px, 50px) scale(1.1); }
        }

        .maintenance-card {
            background: rgba(255, 255, 255, 0.8);
            backdrop-filter: blur(20px);
            border: 1px solid rgba(255, 255, 255, 0.5);
            border-radius: 40px;
            padding: 60px;
            max-width: 600px;
            text-align: center;
            box-shadow: 0 25px 50px -12px rgba(61, 64, 91, 0.15);
            animation: fadeIn 1s ease-out;
        }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(30px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .icon-wrapper {
            width: 100px;
            height: 100px;
            background: white;
            border-radius: 30px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 40px;
            color: var(--primary);
            margin: 0 auto 30px;
            box-shadow: 0 10px 30px rgba(224, 122, 95, 0.2);
        }

        h1 {
            font-family: 'Playfair Display', serif;
            font-weight: 900;
            font-size: 2.5rem;
            margin-bottom: 20px;
            color: var(--dark);
        }

        p {
            font-size: 1.1rem;
            line-height: 1.6;
            color: #6c757d;
            margin-bottom: 40px;
        }

        .progress-container {
            height: 8px;
            background: rgba(61, 64, 91, 0.05);
            border-radius: 999px;
            margin-bottom: 40px;
            overflow: hidden;
        }

        .progress-bar {
            height: 100%;
            background: linear-gradient(90deg, var(--primary), var(--yellow));
            border-radius: 999px;
            width: 75%;
            animation: progress 2s ease-in-out;
        }

        @keyframes progress {
            from { width: 0; }
            to { width: 75%; }
        }

        .contact-group {
            display: flex;
            justify-content: center;
            gap: 20px;
        }

        .btn-contact {
            padding: 12px 24px;
            border-radius: 16px;
            font-weight: 700;
            text-decoration: none;
            transition: all 0.3s;
        }

        .btn-email {
            background: var(--dark);
            color: white;
        }

        .btn-email:hover {
            background: #2d2f44;
            transform: translateY(-2px);
            color: white;
        }

        .admin-link {
            position: fixed;
            bottom: 20px;
            right: 20px;
            color: var(--dark);
            opacity: 0.3;
            text-decoration: none;
            font-size: 12px;
            font-weight: 600;
        }

        .admin-link:hover {
            opacity: 1;
        }
    </style>
</head>
<body>
    <div class="orb orb-1"></div>
    <div class="orb orb-2"></div>
    <div class="orb orb-3"></div>

    <div class="maintenance-card">
        <div class="icon-wrapper">
            <i class="fas fa-tools"></i>
        </div>
        <h1>Improving Your Experience</h1>
        <p>
            We're currently performing some scheduled maintenance to polish the shelves and organize the books. We'll be back online shortly. Thank you for your patience!
        </p>

        <div class="progress-container">
            <div class="progress-bar"></div>
        </div>

        <div class="contact-group">
            <a href="contact_us.php" class="btn-contact btn-email">
                <i class="fas fa-envelope me-2"></i> Contact Us
            </a>
        </div>
    </div>

    <?php if (isAdmin()): ?>
    <a href="admin/settings.php" class="admin-link">
        <i class="fas fa-shield-alt me-1"></i> Admin Dashboard
    </a>
    <?php else: ?>
    <a href="login.php" class="admin-link">
        <i class="fas fa-sign-in-alt me-1"></i> Staff Login
    </a>
    <?php endif; ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
