<?php
require_once '../../includes/sessions.php';
require_once '../../includes/env_loader.php';
require_once '../../config/database.php';
require_once '../../src/OAuth.php';
require_once '../../includes/functions.php';

use App\OAuth;

// Check for error
if (isset($_GET['error'])) {
    setFlashMessage('OAuth authentication cancelled', 'warning');
    header('Location: ../../register.php');
    exit;
}

// Verify state
$state = $_GET['state'] ?? null;
if (!$state || $state !== ($_SESSION['oauth_state'] ?? '')) {
    setFlashMessage('Invalid OAuth state', 'danger');
    header('Location: ../../register.php');
    exit;
}

// Get authorization code
$code = $_GET['code'] ?? null;

if (!$code) {
    setFlashMessage('OAuth authentication failed', 'danger');
    header('Location: ../../register.php');
    exit;
}

try {
    // Facebook OAuth Configuration
    $appId = getenv('FACEBOOK_CLIENT_ID') ?: 'YOUR_FACEBOOK_APP_ID';
    $appSecret = getenv('FACEBOOK_CLIENT_SECRET') ?: 'YOUR_FACEBOOK_APP_SECRET';
    $redirectUri = getenv('FACEBOOK_REDIRECT_URI') ?: 'http://localhost/book-app/oauth/facebook/callback.php';

    // Exchange code for access token
    $tokenUrl = 'https://graph.facebook.com/v18.0/oauth/access_token';
    $tokenParams = [
        'client_id' => $appId,
        'client_secret' => $appSecret,
        'redirect_uri' => $redirectUri,
        'code' => $code
    ];

    $ch = curl_init($tokenUrl . '?' . http_build_query($tokenParams));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    $response = curl_exec($ch);
    curl_close($ch);

    $tokenInfo = json_decode($response, true);

    if (!isset($tokenInfo['access_token'])) {
        throw new Exception('Failed to get access token');
    }

    // Get user info
    $userInfoUrl = 'https://graph.facebook.com/me';
    $userInfoParams = [
        'fields' => 'id,name,email,picture.type(large)',
        'access_token' => $tokenInfo['access_token']
    ];

    $ch = curl_init($userInfoUrl . '?' . http_build_query($userInfoParams));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    $userInfoResponse = curl_exec($ch);
    curl_close($ch);

    $userInfo = json_decode($userInfoResponse, true);

    if (!isset($userInfo['id'])) {
        throw new Exception('Failed to get user information');
    }

    // Create or find user
    $config = require '../../config/database.php';
    $dsn = "mysql:host={$config['host']};dbname={$config['dbname']};charset={$config['charset']}";
    $pdo = new PDO($dsn, $config['username'], $config['password'], $config['options']);

    $oauth = new OAuth($pdo);
    $user = $oauth->findOrCreateUser(
        'facebook',
        $userInfo['id'],
        $userInfo['email'] ?? $userInfo['id'] . '@facebook.com',
        $userInfo['name'],
        $userInfo['picture']['data']['url'] ?? null
    );

    if ($user) {
        $oauth->loginUser($user);
        
        // Force session write and restart
        session_write_close();
        session_start();
        
        // Debug: Verify session was set
        error_log('Facebook OAuth Login - User ID: ' . $user['id']);
        error_log('Facebook OAuth Login - Username: ' . $user['username']);
        error_log('Facebook OAuth Login - Session after loginUser: ' . print_r($_SESSION, true));
        error_log('Facebook OAuth Login - Session ID: ' . session_id());
        
        setFlashMessage('Successfully logged in with Facebook! Welcome ' . $user['username'], 'success');
        header('Location: ../../index.php');
        exit;
    }

    throw new Exception('Failed to create user');

} catch (Exception $e) {
    setFlashMessage('OAuth error: ' . $e->getMessage(), 'danger');
    header('Location: ../../register.php');
    exit;
}
