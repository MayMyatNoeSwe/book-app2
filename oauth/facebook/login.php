<?php
require_once '../../includes/sessions.php';
require_once '../../includes/env_loader.php';

// Facebook OAuth Configuration
$appId = getenv('FACEBOOK_CLIENT_ID') ?: 'YOUR_FACEBOOK_APP_ID';
$redirectUri = getenv('FACEBOOK_REDIRECT_URI') ?: 'http://localhost/book-app/oauth/facebook/callback.php';

// Build Facebook OAuth URL
$params = [
    'client_id' => $appId,
    'redirect_uri' => $redirectUri,
    'scope' => 'email,public_profile',
    'response_type' => 'code',
    'state' => bin2hex(random_bytes(16))
];

$_SESSION['oauth_state'] = $params['state'];

$authUrl = 'https://www.facebook.com/v18.0/dialog/oauth?' . http_build_query($params);

// Redirect to Facebook
header('Location: ' . $authUrl);
exit;
