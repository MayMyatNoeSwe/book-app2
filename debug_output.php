<?php
// Debug output issues
header('Content-Type: text/html; charset=utf-8');

echo "<!DOCTYPE html>";
echo "<html><head><title>Debug</title></head><body>";

echo "<h1>PHP Output Debug</h1>";

echo "<h2>PHP Info:</h2>";
echo "PHP Version: " . phpversion() . "<br>";
echo "Short tags enabled: " . (ini_get('short_open_tag') ? 'Yes' : 'No') . "<br>";
echo "Output buffering: " . (ob_get_level() > 0 ? 'Active' : 'Inactive') . "<br>";

echo "<h2>Test HTML Output:</h2>";
?>

<div class="test-buttons">
    <a href="#" class="btn btn-primary">Test Button 1</a>
    <a href="#" class="btn btn-light">Test Button 2</a>
</div>

<h2>Test PHP Variables:</h2>
<?php
$testVar = "Fiction";
echo "Variable: " . htmlspecialchars($testVar) . "<br>";
?>

<h2>Test Category Loop:</h2>
<?php
$categories = ['Fiction', 'Non-Fiction', 'Mystery'];
foreach ($categories as $cat) {
    echo '<a href="#" class="btn btn-light">' . htmlspecialchars($cat) . '</a> ';
}
?>

</body></html>