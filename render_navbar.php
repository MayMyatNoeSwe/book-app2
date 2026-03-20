<?php
require_once 'includes/sessions.php';
require_once 'vendor/autoload.php';
require_once 'includes/env_loader.php';
require_once 'includes/functions.php';
use App\Auth;
$navCats = getCategories();
echo "--- NAV CATS ---\n";
print_r($navCats);
echo "\n--- NAVBAR HTML Snippet ---\n";
?>
<ul class="dropdown-menu dropdown-menu-start border-0 shadow" aria-labelledby="browseDropdown">
    <?php foreach ($navCats as $navCat): ?>
    <li><a class="dropdown-item" href="book-list.php?category=<?= urlencode($navCat) ?>"><?= $navCat ?></a></li>
    <?php endforeach; ?>
    <li><hr class="dropdown-divider"></li>
    <li><a class="dropdown-item" href="book-list.php">View All Books</a></li>
</ul>
