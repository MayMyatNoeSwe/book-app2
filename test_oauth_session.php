<?php
require_once 'includes/sessions.php';
require_once 'vendor/autoload.php';

use App\Auth;

echo "<h2>OAuth Session Test</h2>";

echo "<h3>Raw Session Data:</h3>";
echo "<pre>";
print_r($_SESSION);
echo "</pre>";

echo "<h3>Auth Class Tests:</h3>";
echo "<ul>";
echo "<li>Auth::check(): <strong>" . (Auth::check() ? '✅ TRUE' : '❌ FALSE') . "</strong></li>";
echo "<li>Auth::user(): <strong>" . (Auth::user() ?? '❌ NULL') . "</strong></li>";
echo "<li>Auth::id(): <strong>" . (Auth::id() ?? '❌ NULL') . "</strong></li>";
echo "<li>Auth::isAdmin(): <strong>" . (Auth::isAdmin() ? '✅ TRUE' : '❌ FALSE') . "</strong></li>";
echo "</ul>";

echo "<h3>Manual Session Check:</h3>";
echo "<ul>";
echo "<li>\$_SESSION['logged_in']: <strong>" . (isset($_SESSION['logged_in']) ? ($_SESSION['logged_in'] ? '✅ TRUE' : '❌ FALSE') : '❌ NOT SET') . "</strong></li>";
echo "<li>\$_SESSION['username']: <strong>" . ($_SESSION['username'] ?? '❌ NOT SET') . "</strong></li>";
echo "<li>\$_SESSION['user_id']: <strong>" . ($_SESSION['user_id'] ?? '❌ NOT SET') . "</strong></li>";
echo "<li>\$_SESSION['email']: <strong>" . ($_SESSION['email'] ?? '❌ NOT SET') . "</strong></li>";
echo "<li>\$_SESSION['role']: <strong>" . ($_SESSION['role'] ?? '❌ NOT SET') . "</strong></li>";
echo "</ul>";

echo "<h3>Actions:</h3>";
echo "<p>";
echo "<a href='oauth/google/login.php' style='padding: 10px 20px; background: #4285f4; color: white; text-decoration: none; border-radius: 5px; margin-right: 10px;'>Login with Google</a>";
echo "<a href='logout.php' style='padding: 10px 20px; background: #dc3545; color: white; text-decoration: none; border-radius: 5px; margin-right: 10px;'>Logout</a>";
echo "<a href='index.php' style='padding: 10px 20px; background: #28a745; color: white; text-decoration: none; border-radius: 5px;'>Go to Home</a>";
echo "</p>";

echo "<style>
body { font-family: Arial, sans-serif; padding: 20px; max-width: 800px; margin: 0 auto; }
pre { background: #f4f4f4; padding: 15px; border-radius: 5px; overflow-x: auto; }
ul { list-style: none; padding: 0; }
li { padding: 8px; margin: 5px 0; background: #f8f9fa; border-radius: 3px; }
</style>";
?>
