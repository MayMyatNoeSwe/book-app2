<?php
// This script helps set up OAuth
echo "<h2>OAuth Setup Instructions</h2>";
echo "<p>To enable OAuth authentication, follow these steps:</p>";

echo "<h3>1. Install Dependencies</h3>";
echo "<p>Run this command in your terminal:</p>";
echo "<code>composer require league/oauth2-google league/oauth2-facebook</code>";

echo "<h3>2. Update Database</h3>";
echo "<p>Run the SQL file to add OAuth columns:</p>";
echo "<code>mysql -u root -p book_library < setup_oauth.sql</code>";

echo "<h3>3. Get OAuth Credentials</h3>";
echo "<ul>";
echo "<li><strong>Google:</strong> <a href='https://console.cloud.google.com/' target='_blank'>Google Cloud Console</a></li>";
echo "<li><strong>Facebook:</strong> <a href='https://developers.facebook.com/' target='_blank'>Facebook Developers</a></li>";
echo "</ul>";

echo "<h3>4. Add to .env file</h3>";
echo "<pre>";
echo "GOOGLE_CLIENT_ID=your_google_client_id
GOOGLE_CLIENT_SECRET=your_google_client_secret
GOOGLE_REDIRECT_URI=http://localhost/book-app/oauth/google/callback.php

FACEBOOK_CLIENT_ID=your_facebook_app_id
FACEBOOK_CLIENT_SECRET=your_facebook_app_secret
FACEBOOK_REDIRECT_URI=http://localhost/book-app/oauth/facebook/callback.php
</pre>";

echo "<h3>5. Test OAuth</h3>";
echo "<p>Visit <a href='register.php'>register.php</a> and click on Google or Facebook buttons</p>";
?>
