<?php
// Centralized Session Management
date_default_timezone_set('Asia/Yangon');

/**
 * Start or resume session with secure settings
 * Call this at the very top of every entry point (before any output)
 */
// $allowedHosts = ['localhost:4000', 'localhost:3500','www.booklibrary.com','booklibrary.com'];

if (session_status() === PHP_SESSION_NONE) {
    // Secure session configuration
    ini_set('session.cookie_httponly', 1);     // Prevent JS access to cookies
    ini_set('session.cookie_secure', 0);       // Set to 0 for localhost (no HTTPS)
    ini_set('session.cookie_samesite', 'Lax'); // Changed from Strict to Lax for OAuth redirects
    ini_set('session.use_strict_mode', 1);     // Reject uninitialized session IDs
    ini_set('session.cookie_path', '/');       // Ensure cookie works across all paths

    session_start();

    // Optional: Regenerate session ID periodically for security
    if (!isset($_SESSION['initiated'])) {
        session_regenerate_id(true);
        $_SESSION['initiated'] = true;
    }

    // Optional: Regenerate ID every 30 minutes
    if (isset($_SESSION['last_regeneration'])) {
        if (time() - $_SESSION['last_regeneration'] > 30 * 60) {
            session_regenerate_id(true);
            $_SESSION['last_regeneration'] = time();
        }
    } else {
        $_SESSION['last_regeneration'] = time();
    }
}

// Optional: Set default session values if not set
if (!isset($_SESSION['flash_message'])) {
    $_SESSION['flash_message'] = null;
}
