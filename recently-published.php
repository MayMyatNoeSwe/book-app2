<?php
$pageTitle = "Recently Published Books";
require_once 'includes/sessions.php';
require_once 'vendor/autoload.php';
require_once 'includes/env_loader.php';
require_once 'includes/functions.php';

use App\Auth;
use App\Library;

$library = new Library();

// Pagination Settings
$page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$limit = 20;
$offset = ($page - 1) * $limit;

// Get recently published books with pagination
$recentBooks = $library->getRecentlyPublishedBooks($limit, $offset);
$totalBooks = $library->countRecentlyPublishedBooks();

// Calculate pagination
$totalPages = ceil($totalBooks / $limit);
$hasNextPage = $page < $totalPages;
$hasPrevPage = $page > 1;

include 'views/header.php';
?>

<div class="container-fluid">
    <?php displayFlashMessage(); ?>

    <!-- Breadcrumb -->
    <nav aria-label="breadcrumb" class="mb-3">
        <ol class="breadcrumb">
            <li class="breadcrumb-item">
                <a href="index.php" class="text-decoration-none">
                    <i class="fas fa-home me-1"></i>Home
                </a>
            </li>
            <li class="breadcrumb-item active" aria-current="page">Recently Published</li>
        </ol>
    </nav>

    <!-- Page Header -->
    <div class="row mb-4">
        <div class="col">
            <div class="d-flex align-items-center justify-content-between">
                <div>
                    <h2 class="fw-bold mb-2">🆕 Recently Published Books</h2>
                    <p class="text-muted mb-0">Discover the latest additions to our collection</p>
                </div>
                <div>
                    <a href="index.php" class="btn btn-outline-secondary">
                        <i class="fas fa-arrow-left me-2"></i>Back to Home
                    </a>
                </div>
            </div>
        </div>
    </div>

    <!-- Stats Bar -->
    <div class="row mb-4">
        <div class="col">
            <div class="card border-0 shadow-sm">
                <div class="card-body py-3">
                    <div class="row text-center">
                        <div class="col-md-3">
                            <strong class="text-primary"><?= $totalBooks ?></strong>
                            <br><small class="text-muted">Total Recently Published</small>
                        </div>
                        <div class="col-md-3">
                            <strong class="text-success"><?= count($recentBooks) ?></strong>
                            <br><small class="text-muted">Showing on This Page</small>
                        </div>
                        <div class="col-md-3">
                            <strong class="text-info"><?= $page ?></strong>
                            <br><small class="text-muted">Current Page</small>
                        </div>
                        <div class="col-md-3">
                            <strong class="text-warning"><?= $totalPages ?></strong>
                            <br><small class="text-muted">Total Pages</small>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Books Grid -->
    <?php if (!empty($recentBooks)): ?>
    <div class="row row-cols-1 row-cols-sm-2 row-cols-md-3 row-cols-lg-4 g-4 mb-5">
        <?php foreach ($recentBooks as $book): ?>
        <div class="col animate-on-scroll stagger-item">
            <?php include 'views/book-card.php'; ?>
        </div>
        <?php endforeach; ?>
    </div>

    <!-- Pagination -->
    <?php if ($totalPages > 1): ?>
    <nav aria-label="Recently Published Books Pagination" class="mb-5">
        <ul class="pagination justify-content-center">
            <!-- Previous Page -->
            <?php if ($hasPrevPage): ?>
            <li class="page-item">
                <a class="page-link" href="?page=<?= $page - 1 ?>">
                    <i class="fas fa-chevron-left"></i> Previous
                </a>
            </li>
            <?php else: ?>
            <li class="page-item disabled">
                <span class="page-link">
                    <i class="fas fa-chevron-left"></i> Previous
                </span>
            </li>
            <?php endif; ?>

            <!-- Page Numbers -->
            <?php
            $startPage = max(1, $page - 2);
            $endPage = min($totalPages, $page + 2);
            
            if ($startPage > 1): ?>
                <li class="page-item">
                    <a class="page-link" href="?page=1">1</a>
                </li>
                <?php if ($startPage > 2): ?>
                <li class="page-item disabled">
                    <span class="page-link">...</span>
                </li>
                <?php endif;
            endif;

            for ($i = $startPage; $i <= $endPage; $i++): ?>
            <li class="page-item <?= $i === $page ? 'active' : '' ?>">
                <a class="page-link" href="?page=<?= $i ?>"><?= $i ?></a>
            </li>
            <?php endfor;

            if ($endPage < $totalPages): 
                if ($endPage < $totalPages - 1): ?>
                <li class="page-item disabled">
                    <span class="page-link">...</span>
                </li>
                <?php endif; ?>
                <li class="page-item">
                    <a class="page-link" href="?page=<?= $totalPages ?>"><?= $totalPages ?></a>
                </li>
            <?php endif; ?>

            <!-- Next Page -->
            <?php if ($hasNextPage): ?>
            <li class="page-item">
                <a class="page-link" href="?page=<?= $page + 1 ?>">
                    Next <i class="fas fa-chevron-right"></i>
                </a>
            </li>
            <?php else: ?>
            <li class="page-item disabled">
                <span class="page-link">
                    Next <i class="fas fa-chevron-right"></i>
                </span>
            </li>
            <?php endif; ?>
        </ul>
    </nav>
    <?php endif; ?>

    <?php else: ?>
    <!-- No Books Found -->
    <div class="text-center py-5">
        <div class="mb-4">
            <i class="fas fa-book-open fa-4x text-muted opacity-50"></i>
        </div>
        <h4 class="text-muted">No Recently Published Books Found</h4>
        <p class="text-muted mb-4">It looks like there are no recently published books in the system yet.</p>
        
        <?php if (Auth::isAdmin()): ?>
        <a href="admin/add-book.php" class="btn btn-primary">
            <i class="fas fa-plus me-2"></i>Add New Book
        </a>
        <?php else: ?>
        <a href="index.php" class="btn btn-outline-primary">
            <i class="fas fa-home me-2"></i>Back to Home
        </a>
        <?php endif; ?>
    </div>
    <?php endif; ?>

</div>

<?php include 'views/footer.php'; ?>