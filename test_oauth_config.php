<?php
require_once 'includes/env_loader.php';

echo "<h2>OAuth Configuration Test</h2>";

echo "<h3>Environment Variables:</h3>";
echo "<table border='1' cellpadding='10'>";
echo "<tr><th>Variable</th><th>Value</th><th>Status</th></tr>";

$vars = [
    'GOOGLE_CLIENT_ID',
    'GOOGLE_CLIENT_SECRET',
    'GOOGLE_REDIRECT_URI',
    'FACEBOOK_CLIENT_ID',
    'FACEBOOK_CLIENT_SECRET',
    'FACEBOOK_REDIRECT_URI'
];

foreach ($vars as $var) {
    $value = getenv($var);
    $status = $value ? '✅ Set' : '❌ Not Set';
    $displayValue = $value ? (strlen($value) > 50 ? substr($value, 0, 50) . '...' : $value) : 'Not configured';
    echo "<tr><td><strong>$var</strong></td><td>$displayValue</td><td>$status</td></tr>";
}

echo "</table>";

echo "<h3>Important Notes:</h3>";
echo "<ul>";
echo "<li>Make sure your Google Cloud Console redirect URI is: <code>" . getenv('GOOGLE_REDIRECT_URI') . "</code></li>";
echo "<li>The redirect URI must match EXACTLY (including http/https, port, path)</li>";
echo "<li>For local development, use: <code>http://localhost/book-app/oauth/google/callback.php</code></li>";
echo "</ul>";

echo "<h3>Google Cloud Console Setup:</h3>";
echo "<ol>";
echo "<li>Go to <a href='https://console.cloud.google.com/apis/credentials' target='_blank'>Google Cloud Console - Credentials</a></li>";
echo "<li>Click 'Create Credentials' → 'OAuth 2.0 Client ID'</li>";
echo "<li>Application type: 'Web application'</li>";
echo "<li>Add Authorized redirect URI: <code>" . getenv('GOOGLE_REDIRECT_URI') . "</code></li>";
echo "<li>Copy the Client ID and Client Secret to your .env file</li>";
echo "</ol>";

echo "<h3>Test OAuth:</h3>";
echo "<p><a href='oauth/google/login.php' class='btn'>Test Google OAuth</a></p>";

echo "<style>
body { font-family: Arial, sans-serif; padding: 20px; }
table { border-collapse: collapse; width: 100%; margin: 20px 0; }
th { background: #2e8a40; color: white; }
td, th { padding: 10px; text-align: left; }
.btn { display: inline-block; padding: 10px 20px; background: #2e8a40; color: white; text-decoration: none; border-radius: 5px; }
code { background: #f4f4f4; padding: 2px 6px; border-radius: 3px; }
</style>";
?>
