<?php
require_once 'includes/sessions.php';
require_once 'src/Auth.php';

use App\Auth;

echo "<h2>Session Debug Information</h2>";

echo "<h3>Session Variables:</h3>";
echo "<pre>";
print_r($_SESSION);
echo "</pre>";

echo "<h3>Auth Class Methods:</h3>";
echo "<table border='1' cellpadding='10'>";
echo "<tr><th>Method</th><th>Result</th></tr>";
echo "<tr><td>Auth::check()</td><td>" . (Auth::check() ? '✅ TRUE' : '❌ FALSE') . "</td></tr>";
echo "<tr><td>Auth::user()</td><td>" . (Auth::user() ?? 'NULL') . "</td></tr>";
echo "<tr><td>Auth::id()</td><td>" . (Auth::id() ?? 'NULL') . "</td></tr>";
echo "<tr><td>Auth::isAdmin()</td><td>" . (Auth::isAdmin() ? '✅ TRUE' : '❌ FALSE') . "</td></tr>";
echo "</table>";

echo "<h3>Expected Session Keys:</h3>";
echo "<ul>";
$expectedKeys = ['user_id', 'username', 'email', 'role', 'logged_in', 'avatar_url'];
foreach ($expectedKeys as $key) {
    $exists = isset($_SESSION[$key]);
    $value = $_SESSION[$key] ?? 'NOT SET';
    echo "<li><strong>$key:</strong> " . ($exists ? "✅ $value" : "❌ NOT SET") . "</li>";
}
echo "</ul>";

echo "<h3>Actions:</h3>";
echo "<p><a href='logout.php'>Logout</a> | <a href='index.php'>Go to Home</a></p>";

echo "<style>
body { font-family: Arial, sans-serif; padding: 20px; }
table { border-collapse: collapse; margin: 20px 0; }
th { background: #2e8a40; color: white; }
td, th { padding: 10px; text-align: left; }
pre { background: #f4f4f4; padding: 15px; border-radius: 5px; overflow-x: auto; }
</style>";
?>
