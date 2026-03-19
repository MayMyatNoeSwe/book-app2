<?php
require_once 'vendor/autoload.php';
require_once 'includes/env_loader.php';
require_once 'includes/functions.php';

use App\Library;

$library = new Library();

$bestSellingBooks = $library->getBestSellingBooks(10);
$topAuthors = $library->getTopAuthors(6);
$recentReviews = $library->getRecentReviews(6);
$recentlyPublishedBooks = $library->getRecentlyPublishedBooks(10);
$bookStats = $library->getBookStats();
?>
<!DOCTYPE html>
<html>
<head>
    <title>Test Homepage Sections</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
    <div class="container my-5">
        <h1>Homepage Sections Test</h1>
        
        <div class="card mb-4">
            <div class="card-header">
                <h3>Statistics</h3>
            </div>
            <div class="card-body">
                <p>Total Books: <?= $bookStats['total_books'] ?></p>
                <p>Total Authors: <?= $bookStats['total_authors'] ?></p>
                <p>Total Reviews: <?= $bookStats['total_reviews'] ?></p>
                <p>Monthly Borrows: <?= $bookStats['monthly_borrows'] ?></p>
            </div>
        </div>

        <div class="card mb-4">
            <div class="card-header">
                <h3>Best Selling Books (<?= count($bestSellingBooks) ?>)</h3>
            </div>
            <div class="card-body">
                <?php if (empty($bestSellingBooks)): ?>
                    <p class="text-danger">No books found!</p>
                <?php else: ?>
                    <div class="row row-cols-5 g-3">
                        <?php foreach ($bestSellingBooks as $book): ?>
                            <div class="col">
                                <div class="card">
                                    <img src="<?= getBookCoverUrl($book, $book->getTitle(), $book->getAuthor()) ?>" 
                                         class="card-img-top" 
                                         style="height: 200px; object-fit: cover;"
                                         onerror="this.src='<?= getDummyBookCover($book->getTitle(), $book->getAuthor(), 300, 400) ?>'">
                                    <div class="card-body p-2">
                                        <h6 class="card-title small"><?= e($book->getTitle()) ?></h6>
                                        <p class="card-text small text-muted"><?= e($book->getAuthor()) ?></p>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <div class="card mb-4">
            <div class="card-header">
                <h3>Top Authors (<?= count($topAuthors) ?>)</h3>
            </div>
            <div class="card-body">
                <?php if (empty($topAuthors)): ?>
                    <p class="text-danger">No authors found!</p>
                <?php else: ?>
                    <ul>
                        <?php foreach ($topAuthors as $author): ?>
                            <li><?= e($author['author']) ?> - <?= $author['book_count'] ?> books</li>
                        <?php endforeach; ?>
                    </ul>
                <?php endif; ?>
            </div>
        </div>

        <div class="card mb-4">
            <div class="card-header">
                <h3>Recent Reviews (<?= count($recentReviews) ?>)</h3>
            </div>
            <div class="card-body">
                <?php if (empty($recentReviews)): ?>
                    <p class="text-danger">No reviews found!</p>
                <?php else: ?>
                    <ul>
                        <?php foreach ($recentReviews as $review): ?>
                            <li><?= e($review['title']) ?> - <?= $review['rating'] ?> stars by <?= e($review['username']) ?></li>
                        <?php endforeach; ?>
                    </ul>
                <?php endif; ?>
            </div>
        </div>

        <div class="card mb-4">
            <div class="card-header">
                <h3>Recently Published (<?= count($recentlyPublishedBooks) ?>)</h3>
            </div>
            <div class="card-body">
                <?php if (empty($recentlyPublishedBooks)): ?>
                    <p class="text-danger">No recently published books found!</p>
                <?php else: ?>
                    <div class="row row-cols-5 g-3">
                        <?php foreach ($recentlyPublishedBooks as $book): ?>
                            <div class="col">
                                <div class="card">
                                    <img src="<?= getBookCoverUrl($book, $book->getTitle(), $book->getAuthor()) ?>" 
                                         class="card-img-top" 
                                         style="height: 200px; object-fit: cover;"
                                         onerror="this.src='<?= getDummyBookCover($book->getTitle(), $book->getAuthor(), 300, 400) ?>'">
                                    <div class="card-body p-2">
                                        <h6 class="card-title small"><?= e($book->getTitle()) ?></h6>
                                        <p class="card-text small text-muted"><?= e($book->getAuthor()) ?></p>
                                        <span class="badge bg-primary"><?= $book->getYear() ?></span>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</body>
</html>
