<?php
// Simple search test
require_once 'includes/sessions.php';
require_once 'vendor/autoload.php';
require_once 'includes/env_loader.php';
require_once 'includes/functions.php';

use App\Library;

$library = new Library();
$search = $_GET['q'] ?? null;

if ($search) {
    $search = trim($search);
    if ($search === '') {
        $search = null;
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Search Test</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <div class="container mt-4">
        <h2>Search Test Page</h2>
        
        <!-- Search Form -->
        <form method="GET" class="mb-4">
            <div class="input-group">
                <input type="text" name="q" value="<?= htmlspecialchars($search ?? '') ?>" 
                       class="form-control" placeholder="Search by title or author...">
                <button class="btn btn-primary" type="submit">Search</button>
            </div>
        </form>
        
        <!-- Debug Info -->
        <div class="alert alert-info">
            <strong>Debug Info:</strong><br>
            Search Parameter: <?= $search ? '"' . htmlspecialchars($search) . '"' : 'NULL' ?><br>
            Search Length: <?= $search ? strlen($search) : '0' ?><br>
        </div>
        
        <?php if ($search): ?>
            <?php
            $books = $library->getBooksPaginated(20, 0, null, $search);
            $totalBooks = $library->countBooks(null, $search);
            ?>
            
            <div class="alert alert-success">
                <strong>Search Results:</strong><br>
                Total matching books: <?= $totalBooks ?><br>
                Books displayed: <?= count($books) ?><br>
            </div>
            
            <?php if (!empty($books)): ?>
                <div class="row">
                    <?php foreach ($books as $book): ?>
                        <div class="col-md-4 mb-3">
                            <div class="card">
                                <div class="card-body">
                                    <h6 class="card-title"><?= htmlspecialchars($book->getTitle()) ?></h6>
                                    <p class="card-text">by <?= htmlspecialchars($book->getAuthor()) ?></p>
                                    <small class="text-muted"><?= $book->getYear() ?> | <?= htmlspecialchars($book->getCategory()) ?></small>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <div class="alert alert-warning">
                    No books found matching "<?= htmlspecialchars($search) ?>"
                </div>
            <?php endif; ?>
            
        <?php else: ?>
            <div class="alert alert-secondary">
                Enter a search term to test the search functionality.
            </div>
        <?php endif; ?>
        
        <hr>
        <p><a href="index.php">← Back to Home</a></p>
    </div>
</body>
</html>