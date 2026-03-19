<?php
require_once '../../includes/sessions.php';
require_once '../../includes/env_loader.php';
require_once '../../config/database.php';
require_once '../../src/OAuth.php';
require_once '../../src/Auth.php';
require_once '../../includes/functions.php';

use App\OAuth;
use App\Auth;

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Check for error
if (isset($_GET['error'])) {
    setFlashMessage('OAuth authentication cancelled: ' . ($_GET['error_description'] ?? $_GET['error']), 'warning');
    header('Location: ../../register.php');
    exit;
}

// Get authorization code
$code = $_GET['code'] ?? null;

if (!$code) {
    setFlashMessage('OAuth authentication failed - no authorization code received', 'danger');
    header('Location: ../../register.php');
    exit;
}

try {
    // Google OAuth Configuration
    $clientId = getenv('GOOGLE_CLIENT_ID');
    $clientSecret = getenv('GOOGLE_CLIENT_SECRET');
    $redirectUri = getenv('GOOGLE_REDIRECT_URI');

    if (!$clientId || !$clientSecret) {
        throw new Exception('Google OAuth credentials not configured. Please check your .env file.');
    }

    // Exchange code for access token
    $tokenUrl = 'https://oauth2.googleapis.com/token';
    $tokenData = [
        'code' => $code,
        'client_id' => $clientId,
        'client_secret' => $clientSecret,
        'redirect_uri' => $redirectUri,
        'grant_type' => 'authorization_code'
    ];

    $ch = curl_init($tokenUrl);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($tokenData));
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); // For local development
    $response = curl_exec($ch);
    
    if (curl_errno($ch)) {
        throw new Exception('cURL Error: ' . curl_error($ch));
    }
    
    curl_close($ch);

    $tokenInfo = json_decode($response, true);

    if (!isset($tokenInfo['access_token'])) {
        $errorMsg = isset($tokenInfo['error_description']) ? $tokenInfo['error_description'] : 'Failed to get access token';
        throw new Exception($errorMsg);
    }

    // Get user info
    $userInfoUrl = 'https://www.googleapis.com/oauth2/v2/userinfo';
    $ch = curl_init($userInfoUrl);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Authorization: Bearer ' . $tokenInfo['access_token']
    ]);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); // For local development
    $userInfoResponse = curl_exec($ch);
    
    if (curl_errno($ch)) {
        throw new Exception('cURL Error: ' . curl_error($ch));
    }
    
    curl_close($ch);

    $userInfo = json_decode($userInfoResponse, true);

    if (!isset($userInfo['id'])) {
        throw new Exception('Failed to get user information from Google');
    }

    // Create or find user
    $config = require '../../config/database.php';
    $dsn = "mysql:host={$config['host']};dbname={$config['dbname']};charset={$config['charset']}";
    $pdo = new PDO($dsn, $config['username'], $config['password'], $config['options']);

    $oauth = new OAuth($pdo);
    $user = $oauth->findOrCreateUser(
        'google',
        $userInfo['id'],
        $userInfo['email'],
        $userInfo['name'],
        $userInfo['picture'] ?? null
    );

    if ($user) {
        $oauth->loginUser($user);
        
        // Force session write and restart
        session_write_close();
        session_start();
        
        // Debug: Verify session was set
        error_log('OAuth Login - User ID: ' . $user['id']);
        error_log('OAuth Login - Username: ' . $user['username']);
        error_log('OAuth Login - Session after loginUser: ' . print_r($_SESSION, true));
        error_log('OAuth Login - Session ID: ' . session_id());
        error_log('OAuth Login - Auth::check(): ' . (Auth::check() ? 'true' : 'false'));
        
        setFlashMessage('Successfully logged in with Google! Welcome ' . $user['username'], 'success');
        
        // Use relative path for redirect
        header('Location: ../../index.php');
        exit;
    }

    throw new Exception('Failed to create or find user');

} catch (Exception $e) {
    error_log('OAuth Error: ' . $e->getMessage());
    setFlashMessage('OAuth error: ' . $e->getMessage(), 'danger');
    header('Location: ../../register.php');
    exit;
}
