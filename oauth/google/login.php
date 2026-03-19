<?php
require_once '../../includes/sessions.php';
require_once '../../includes/env_loader.php';

// Google OAuth Configuration
$clientId = getenv('GOOGLE_CLIENT_ID');
$redirectUri = getenv('GOOGLE_REDIRECT_URI');

if (!$clientId || !$redirectUri) {
    die('Google OAuth not configured. Please add GOOGLE_CLIENT_ID and GOOGLE_REDIRECT_URI to your .env file.');
}

// Build Google OAuth URL
$params = [
    'client_id' => $clientId,
    'redirect_uri' => $redirectUri,
    'response_type' => 'code',
    'scope' => 'email profile',
    'access_type' => 'online',
    'prompt' => 'select_account'
];

$authUrl = 'https://accounts.google.com/o/oauth2/v2/auth?' . http_build_query($params);

// Redirect to Google
header('Location: ' . $authUrl);
exit;
