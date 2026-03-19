<?php
require_once 'includes/functions.php';

echo "<h2>Testing Categories Display</h2>";

echo "<h3>Raw Categories Array:</h3>";
$categories = getCategories();
echo "<pre>";
print_r($categories);
echo "</pre>";

echo "<h3>HTML Output Test:</h3>";
foreach ($categories as $cat) {
    if ($cat === 'Uncategorized') continue;
    echo "<p>Category: " . e($cat) . "</p>";
}

echo "<h3>Button HTML Test:</h3>";
$category = null; // Simulate no category selected
foreach ($categories as $cat) {
    if ($cat === 'Uncategorized') continue;
    $isActive = $category === $cat ? 'btn-primary' : 'btn-light text-muted border';
    echo '<a href="index.php?cat=' . urlencode($cat) . '" class="btn rounded-pill px-4 py-2 ' . $isActive . '">';
    echo e($cat);
    echo '</a> ';
}
?>