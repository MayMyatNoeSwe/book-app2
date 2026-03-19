<?php
$pageTitle = "Author Details";
require_once 'includes/sessions.php';
require_once 'vendor/autoload.php';
require_once 'includes/env_loader.php';
require_once 'includes/functions.php';

use App\Auth;
use App\Library;
use App\EBook;

$library = new Library();

// Get author name from URL
$authorName = $_GET['author'] ?? null;
if (!$authorName) {
    header('Location: author-list.php');
    exit;
}

// Decode URL-encoded author name
$authorName = urldecode($authorName);

// Get author details
$authorDetails = $library->getAuthorDetails($authorName);
if (!$authorDetails) {
    header('Location: author-list.php');
    exit;
}

// Get author's books with different sorting options
$sortBy = $_GET['sort'] ?? 'year';
$sortOrder = $_GET['order'] ?? 'desc';
$page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$limit = 12;
$offset = ($page - 1) * $limit;

$authorBooks = $library->getBooksByAuthor($authorName, $limit, $offset, $sortBy, $sortOrder);
$popularBooks = $library->getAuthorPopularBooks($authorName, 6);

// Get total books count for pagination
$totalBooks = $authorDetails['book_count'];
$totalPages = ceil($totalBooks / $limit);
$hasNextPage = $page < $totalPages;
$hasPrevPage = $page > 1;

// Set dynamic page title
$pageTitle = $authorName . " - Author Details";

include 'views/header.php';
?>

<div class="author-details-container">
    <!-- Breadcrumb -->
    <div class="container-fluid">
        <nav aria-label="breadcrumb" class="pt-3">
            <ol class="breadcrumb">
                <li class="breadcrumb-item">
                    <a href="index.php" class="text-decoration-none">
                        <i class="fas fa-home me-1"></i>Home
                    </a>
                </li>
                <li class="breadcrumb-item">
                    <a href="author-list.php" class="text-decoration-none">Authors</a>
                </li>
                <li class="breadcrumb-item active" aria-current="page">
                    <?= e($authorName) ?>
                </li>
            </ol>
        </nav>
    </div>

    <!-- Author Hero Section -->
    <div class="author-hero-section">
        <div class="container-fluid">
            <div class="row g-5 align-items-start">
                <!-- Author Photo & Quick Stats -->
                <div class="col-lg-4">
                    <div class="author-profile-section animate-on-scroll">
                        <div class="author-photo-wrapper">
                            <?php 
                            $authorPhoto = $authorDetails['author_photo'] ?? null;
                            $avatarUrl = getAuthorAvatarUrl($authorName, 300);
                            
                            if ($authorPhoto && file_exists(__DIR__ . '/public/uploads/authors/' . $authorPhoto)): ?>
                                <img src="<?= baseUrl() ?>/public/uploads/authors/<?= e($authorPhoto) ?>" 
                                     alt="<?= e($authorName) ?>" class="author-photo-large">
                            <?php else: ?>
                                <img src="<?= $avatarUrl ?>" 
                                     alt="<?= e($authorName) ?>" class="author-photo-large">
                            <?php endif; ?>
                            
                            <!-- Author Badge -->
                            <div class="author-badge">
                                <span class="badge bg-primary">
                                    <i class="fas fa-user-edit me-1"></i>Author
                                </span>
                            </div>
                        </div>

                        <!-- Quick Stats Cards -->
                        <div class="author-stats-cards mt-4">
                            <div class="stat-card">
                                <div class="stat-icon">
                                    <i class="fas fa-book text-primary"></i>
                                </div>
                                <div class="stat-content">
                                    <h3><?= $authorDetails['book_count'] ?></h3>
                                    <p>Books Published</p>
                                </div>
                            </div>
                            
                            <div class="stat-card">
                                <div class="stat-icon">
                                    <i class="fas fa-star text-warning"></i>
                                </div>
                                <div class="stat-content">
                                    <h3><?= number_format($authorDetails['avg_rating'], 1) ?></h3>
                                    <p>Average Rating</p>
                                </div>
                            </div>
                            
                            <div class="stat-card">
                                <div class="stat-icon">
                                    <i class="fas fa-download text-success"></i>
                                </div>
                                <div class="stat-content">
                                    <h3><?= $authorDetails['total_borrows'] ?></h3>
                                    <p>Total Borrows</p>
                                </div>
                            </div>
                            
                            <div class="stat-card">
                                <div class="stat-icon">
                                    <i class="fas fa-comments text-info"></i>
                                </div>
                                <div class="stat-content">
                                    <h3><?= $authorDetails['review_count'] ?></h3>
                                    <p>Reviews</p>
                                </div>
                            </div>
                        </div>

                        <!-- Quick Actions -->
                        <div class="author-actions mt-4">
                            <a href="book-list.php?search=<?= urlencode($authorName) ?>" 
                               class="btn btn-primary btn-lg w-100 mb-3">
                                <i class="fas fa-book me-2"></i>View All Books
                            </a>
                            
                            <div class="share-buttons">
                                <h6 class="text-muted mb-2">Share this author:</h6>
                                <div class="btn-group w-100" role="group">
                                    <button class="btn btn-outline-primary" onclick="shareAuthor('facebook')" title="Share on Facebook">
                                        <i class="fab fa-facebook-f"></i>
                                    </button>
                                    <button class="btn btn-outline-info" onclick="shareAuthor('twitter')" title="Share on Twitter">
                                        <i class="fab fa-twitter"></i>
                                    </button>
                                    <button class="btn btn-outline-success" onclick="shareAuthor('whatsapp')" title="Share on WhatsApp">
                                        <i class="fab fa-whatsapp"></i>
                                    </button>
                                    <button class="btn btn-outline-secondary" onclick="copyAuthorLink()" title="Copy Link">
                                        <i class="fas fa-link"></i>
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Author Information -->
                <div class="col-lg-8">
                    <div class="author-info-section animate-on-scroll">
                        <!-- Author Name & Title -->
                        <div class="author-header mb-4">
                            <h1 class="author-name"><?= e($authorName) ?></h1>
                            <div class="author-subtitle">
                                <span class="text-muted">Author</span>
                                <?php if ($authorDetails['first_book_year'] && $authorDetails['latest_book_year']): ?>
                                    <span class="text-muted">• Active <?= $authorDetails['first_book_year'] ?> - <?= $authorDetails['latest_book_year'] ?></span>
                                <?php endif; ?>
                            </div>
                        </div>

                        <!-- Author Bio -->
                        <div class="author-bio mb-4">
                            <h5 class="section-title">
                                <i class="fas fa-user me-2"></i>About the Author
                            </h5>
                            <div class="bio-content">
                                <?php if (!empty($authorDetails['author_bio'])): ?>
                                    <p><?= nl2br(e($authorDetails['author_bio'])) ?></p>
                                <?php else: ?>
                                    <p class="text-muted">
                                        <?= e($authorName) ?> is a talented author who has contributed <?= $authorDetails['book_count'] ?> 
                                        <?= $authorDetails['book_count'] === 1 ? 'book' : 'books' ?> to our collection. 
                                        <?php if ($authorDetails['first_book_year']): ?>
                                            Their literary journey began in <?= $authorDetails['first_book_year'] ?><?= $authorDetails['latest_book_year'] !== $authorDetails['first_book_year'] ? ' and continues to ' . $authorDetails['latest_book_year'] : '' ?>.
                                        <?php endif; ?>
                                        <?php if ($authorDetails['avg_rating'] > 0): ?>
                                            With an average rating of <?= number_format($authorDetails['avg_rating'], 1) ?> stars, 
                                            their work has been well-received by readers.
                                        <?php endif; ?>
                                    </p>
                                <?php endif; ?>
                            </div>
                        </div>

                        <!-- Author Details Grid -->
                        <div class="author-details-grid mb-4">
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <div class="detail-card">
                                        <div class="detail-icon">
                                            <i class="fas fa-calendar-alt text-primary"></i>
                                        </div>
                                        <div class="detail-content">
                                            <h6>Career Span</h6>
                                            <p>
                                                <?php if ($authorDetails['first_book_year'] && $authorDetails['latest_book_year']): ?>
                                                    <?= $authorDetails['first_book_year'] ?> - <?= $authorDetails['latest_book_year'] ?>
                                                    (<?= $authorDetails['latest_book_year'] - $authorDetails['first_book_year'] + 1 ?> years)
                                                <?php else: ?>
                                                    Not available
                                                <?php endif; ?>
                                            </p>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="col-md-6">
                                    <div class="detail-card">
                                        <div class="detail-icon">
                                            <i class="fas fa-tags text-success"></i>
                                        </div>
                                        <div class="detail-content">
                                            <h6>Genres</h6>
                                            <div class="genre-tags">
                                                <?php if (!empty($authorDetails['categories_array'])): ?>
                                                    <?php foreach ($authorDetails['categories_array'] as $category): ?>
                                                        <a href="book-list.php?category=<?= urlencode($category) ?>&search=<?= urlencode($authorName) ?>" 
                                                           class="badge bg-light text-dark me-1 mb-1"><?= e($category) ?></a>
                                                    <?php endforeach; ?>
                                                <?php else: ?>
                                                    <span class="text-muted">Various</span>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <div class="col-md-6">
                                    <div class="detail-card">
                                        <div class="detail-icon">
                                            <i class="fas fa-book-open text-info"></i>
                                        </div>
                                        <div class="detail-content">
                                            <h6>First Published</h6>
                                            <p>
                                                <?php if ($authorDetails['first_book_title']): ?>
                                                    "<?= e($authorDetails['first_book_title']) ?>" (<?= $authorDetails['first_book_year'] ?>)
                                                <?php else: ?>
                                                    Not available
                                                <?php endif; ?>
                                            </p>
                                        </div>
                                    </div>
                                </div>

                                <div class="col-md-6">
                                    <div class="detail-card">
                                        <div class="detail-icon">
                                            <i class="fas fa-clock text-warning"></i>
                                        </div>
                                        <div class="detail-content">
                                            <h6>Latest Work</h6>
                                            <p>
                                                <?php if ($authorDetails['latest_book_title']): ?>
                                                    "<?= e($authorDetails['latest_book_title']) ?>" (<?= $authorDetails['latest_book_year'] ?>)
                                                <?php else: ?>
                                                    Not available
                                                <?php endif; ?>
                                            </p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Rating Summary -->
                        <div class="rating-summary mb-4">
                            <div class="row align-items-center">
                                <div class="col-md-6">
                                    <div class="rating-display">
                                        <?php if ($authorDetails['review_count'] > 0): ?>
                                            <div class="stars-large">
                                                <?php for ($i = 1; $i <= 5; $i++): ?>
                                                    <i class="fas fa-star <?= $i <= round($authorDetails['avg_rating']) ? 'text-warning' : 'text-muted' ?>"></i>
                                                <?php endfor; ?>
                                            </div>
                                            <span class="rating-text">
                                                <?= number_format($authorDetails['avg_rating'], 1) ?> out of 5 
                                                (<?= $authorDetails['review_count'] ?> <?= $authorDetails['review_count'] === 1 ? 'review' : 'reviews' ?>)
                                            </span>
                                        <?php else: ?>
                                            <div class="stars-large">
                                                <?php for ($i = 1; $i <= 5; $i++): ?>
                                                    <i class="fas fa-star text-muted"></i>
                                                <?php endfor; ?>
                                            </div>
                                            <span class="rating-text text-muted">No reviews yet</span>
                                        <?php endif; ?>
                                    </div>
                                </div>
                                <div class="col-md-6 text-md-end">
                                    <div class="popularity-metrics">
                                        <div class="metric">
                                            <span class="metric-value"><?= $authorDetails['total_borrows'] ?></span>
                                            <span class="metric-label">Total Borrows</span>
                                        </div>
                                        <div class="metric">
                                            <span class="metric-value"><?= $authorDetails['book_count'] ?></span>
                                            <span class="metric-label">Books</span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Popular Books Section -->
    <?php if (!empty($popularBooks)): ?>
    <div class="popular-books-section">
        <div class="container-fluid">
            <div class="section-header">
                <h3 class="section-title">
                    <i class="fas fa-fire me-2"></i>Most Popular Books
                </h3>
                <p class="section-subtitle">The most borrowed and highest-rated books by <?= e($authorName) ?></p>
            </div>
            
            <div class="row row-cols-1 row-cols-sm-2 row-cols-md-3 row-cols-lg-4 g-4">
                <?php foreach ($popularBooks as $book): ?>
                    <?php 
                    $showBorrow = Auth::check();
                    include 'views/book-card.php'; 
                    ?>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <!-- All Books Section -->
    <div class="all-books-section">
        <div class="container-fluid">
            <div class="section-header">
                <div class="row align-items-center">
                    <div class="col-md-6">
                        <h3 class="section-title">
                            <i class="fas fa-books me-2"></i>All Books by <?= e($authorName) ?>
                            <span class="badge bg-primary ms-2"><?= $totalBooks ?></span>
                        </h3>
                    </div>
                    <div class="col-md-6">
                        <div class="books-controls">
                            <div class="d-flex justify-content-md-end align-items-center gap-3">
                                <!-- Sort Options -->
                                <div class="sort-controls">
                                    <select class="form-select form-select-sm" onchange="changeBooksSort(this.value)" style="width: auto;">
                                        <option value="year-desc" <?= $sortBy === 'year' && $sortOrder === 'desc' ? 'selected' : '' ?>>Newest First</option>
                                        <option value="year-asc" <?= $sortBy === 'year' && $sortOrder === 'asc' ? 'selected' : '' ?>>Oldest First</option>
                                        <option value="title-asc" <?= $sortBy === 'title' && $sortOrder === 'asc' ? 'selected' : '' ?>>Title A-Z</option>
                                        <option value="title-desc" <?= $sortBy === 'title' && $sortOrder === 'desc' ? 'selected' : '' ?>>Title Z-A</option>
                                        <option value="category-asc" <?= $sortBy === 'category' && $sortOrder === 'asc' ? 'selected' : '' ?>>Category</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <?php if (!empty($authorBooks)): ?>
                <div class="row row-cols-1 row-cols-sm-2 row-cols-md-3 row-cols-lg-4 g-4">
                    <?php foreach ($authorBooks as $book): ?>
                        <?php 
                        $showBorrow = Auth::check();
                        include 'views/book-card.php'; 
                        ?>
                    <?php endforeach; ?>
                </div>

                <!-- Pagination -->
                <?php if ($totalPages > 1): ?>
                <div class="pagination-section mt-5">
                    <div class="row align-items-center">
                        <div class="col-md-6">
                            <div class="pagination-info">
                                <span class="text-muted">
                                    Showing <?= count($authorBooks) ?> of <?= $totalBooks ?> books
                                    (Page <?= $page ?> of <?= $totalPages ?>)
                                </span>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <nav aria-label="Author books pagination">
                                <ul class="pagination justify-content-md-end">
                                    <!-- Previous Page -->
                                    <?php if ($hasPrevPage): ?>
                                    <li class="page-item">
                                        <a class="page-link" href="<?= buildAuthorUrl(['page' => $page - 1]) ?>">
                                            <i class="fas fa-angle-left"></i> Previous
                                        </a>
                                    </li>
                                    <?php endif; ?>

                                    <!-- Page Numbers -->
                                    <?php
                                    $startPage = max(1, $page - 2);
                                    $endPage = min($totalPages, $page + 2);
                                    
                                    for ($i = $startPage; $i <= $endPage; $i++): ?>
                                    <li class="page-item <?= $i === $page ? 'active' : '' ?>">
                                        <a class="page-link" href="<?= buildAuthorUrl(['page' => $i]) ?>"><?= $i ?></a>
                                    </li>
                                    <?php endfor; ?>

                                    <!-- Next Page -->
                                    <?php if ($hasNextPage): ?>
                                    <li class="page-item">
                                        <a class="page-link" href="<?= buildAuthorUrl(['page' => $page + 1]) ?>">
                                            Next <i class="fas fa-angle-right"></i>
                                        </a>
                                    </li>
                                    <?php endif; ?>
                                </ul>
                            </nav>
                        </div>
                    </div>
                </div>
                <?php endif; ?>
            <?php else: ?>
                <div class="empty-books">
                    <div class="text-center py-5">
                        <i class="fas fa-book fa-3x text-muted mb-3"></i>
                        <h5 class="text-muted">No books found</h5>
                        <p class="text-muted">This author doesn't have any books in our current collection.</p>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php
// Helper function to build URLs with current parameters
function buildAuthorUrl($newParams = []) {
    global $authorName, $sortBy, $sortOrder;
    
    $params = array_filter([
        'author' => $authorName,
        'sort' => $sortBy,
        'order' => $sortOrder
    ]);
    
    $params = array_merge($params, $newParams);
    
    return 'author-details.php?' . http_build_query($params);
}
?>

<script>
// Share functionality
function shareAuthor(platform) {
    const url = encodeURIComponent(window.location.href);
    const title = encodeURIComponent('<?= addslashes($authorName) ?> - Author Profile');
    
    let shareUrl = '';
    switch(platform) {
        case 'facebook':
            shareUrl = `https://www.facebook.com/sharer/sharer.php?u=${url}`;
            break;
        case 'twitter':
            shareUrl = `https://twitter.com/intent/tweet?url=${url}&text=${title}`;
            break;
        case 'whatsapp':
            shareUrl = `https://wa.me/?text=${title} ${url}`;
            break;
    }
    
    if (shareUrl) {
        window.open(shareUrl, '_blank', 'width=600,height=400');
    }
}

function copyAuthorLink() {
    navigator.clipboard.writeText(window.location.href).then(() => {
        // Show success feedback
        const btn = event.target.closest('button');
        const originalHTML = btn.innerHTML;
        btn.innerHTML = '<i class="fas fa-check"></i>';
        btn.classList.add('btn-success');
        btn.classList.remove('btn-outline-secondary');
        
        setTimeout(() => {
            btn.innerHTML = originalHTML;
            btn.classList.remove('btn-success');
            btn.classList.add('btn-outline-secondary');
        }, 2000);
    });
}

// Sort functionality
function changeBooksSort(value) {
    const [sort, order] = value.split('-');
    const url = new URL(window.location);
    url.searchParams.set('sort', sort);
    url.searchParams.set('order', order);
    url.searchParams.delete('page'); // Reset to first page
    window.location.href = url.toString();
}
</script>

<?php include 'views/footer.php'; ?>