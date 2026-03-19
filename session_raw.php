<?php
session_start();
header('Content-Type: text/plain');

echo "=== SESSION RAW OUTPUT ===\n\n";
echo "Session ID: " . session_id() . "\n\n";
echo "Session Data:\n";
print_r($_SESSION);
echo "\n\n";

echo "Individual Values:\n";
echo "logged_in: " . (isset($_SESSION['logged_in']) ? ($_SESSION['logged_in'] ? 'TRUE' : 'FALSE') : 'NOT SET') . "\n";
echo "username: " . ($_SESSION['username'] ?? 'NOT SET') . "\n";
echo "user_id: " . ($_SESSION['user_id'] ?? 'NOT SET') . "\n";
echo "email: " . ($_SESSION['email'] ?? 'NOT SET') . "\n";
echo "role: " . ($_SESSION['role'] ?? 'NOT SET') . "\n";
echo "avatar_url: " . ($_SESSION['avatar_url'] ?? 'NOT SET') . "\n";
