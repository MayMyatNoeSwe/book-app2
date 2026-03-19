<?php
$pageTitle = "Authors Directory";
require_once 'includes/sessions.php';
require_once 'vendor/autoload.php';
require_once 'includes/env_loader.php';
require_once 'includes/functions.php';

use App\Auth;
use App\Library;

$library = new Library();

// Get parameters
$page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$limit = isset($_GET['limit']) ? max(12, min(48, (int)$_GET['limit'])) : 24;
$offset = ($page - 1) * $limit;

// Filters
$search = $_GET['search'] ?? null;
$sortBy = $_GET['sort'] ?? 'name';
$sortOrder = $_GET['order'] ?? 'asc';
$viewMode = $_GET['view'] ?? 'grid';
$minBooks = isset($_GET['min_books']) ? max(1, (int)$_GET['min_books']) : 1;

// Clean inputs
if ($search) {
    $search = trim($search);
    if ($search === '') $search = null;
}

// Validate sort options
$validSorts = ['name', 'book_count', 'avg_rating', 'total_borrows', 'latest_book'];
if (!in_array($sortBy, $validSorts)) $sortBy = 'name';
if (!in_array($sortOrder, ['asc', 'desc'])) $sortOrder = 'asc';
if (!in_array($viewMode, ['grid', 'list', 'compact'])) $viewMode = 'grid';

// Get authors with advanced filtering
$authors = $library->getAdvancedAuthorsPaginated($limit, $offset, $search, $sortBy, $sortOrder, $minBooks);
$totalAuthors = $library->countAdvancedAuthors($search, $minBooks);

// Calculate pagination
$totalPages = ceil($totalAuthors / $limit);
$hasNextPage = $page < $totalPages;
$hasPrevPage = $page > 1;

// Get stats
$authorStats = $library->getAuthorStats();

// Set dynamic page title
if ($search) {
    $pageTitle = "Search Authors: " . $search;
} elseif ($sortBy === 'book_count' && $sortOrder === 'desc') {
    $pageTitle = "Most Prolific Authors";
} elseif ($sortBy === 'avg_rating' && $sortOrder === 'desc') {
    $pageTitle = "Top Rated Authors";
} elseif ($sortBy === 'total_borrows' && $sortOrder === 'desc') {
    $pageTitle = "Most Popular Authors";
} else {
    $pageTitle = "Authors Directory";
}

include 'views/header.php';
?>

<div class="author-list-container">
    <!-- Breadcrumb -->
    <div class="container-fluid">
        <nav aria-label="breadcrumb" class="pt-3">
            <ol class="breadcrumb">
                <li class="breadcrumb-item">
                    <a href="index.php" class="text-decoration-none">
                        <i class="fas fa-home me-1"></i>Home
                    </a>
                </li>
                <?php if ($search): ?>
                    <li class="breadcrumb-item active" aria-current="page">Search Authors</li>
                <?php elseif ($sortBy === 'book_count' && $sortOrder === 'desc'): ?>
                    <li class="breadcrumb-item active" aria-current="page">Most Prolific Authors</li>
                <?php elseif ($sortBy === 'avg_rating' && $sortOrder === 'desc'): ?>
                    <li class="breadcrumb-item active" aria-current="page">Top Rated Authors</li>
                <?php elseif ($sortBy === 'total_borrows' && $sortOrder === 'desc'): ?>
                    <li class="breadcrumb-item active" aria-current="page">Most Popular Authors</li>
                <?php else: ?>
                    <li class="breadcrumb-item active" aria-current="page">All Authors</li>
                <?php endif; ?>
            </ol>
        </nav>
    </div>

    <!-- Advanced Header -->
    <div class="author-list-header">
        <div class="container-fluid px-5 py-5">
            <div class="row align-items-center py-4">
                <div class="col-md-6">
                    <h1 class="display-5 fw-bold mb-2">
                        <i class="fas fa-user-edit me-3 text-primary"></i>
                        <?php if ($search): ?>
                            Author Search Results
                        <?php elseif ($sortBy === 'book_count' && $sortOrder === 'desc'): ?>
                            Most Prolific Authors
                        <?php elseif ($sortBy === 'avg_rating' && $sortOrder === 'desc'): ?>
                            Top Rated Authors
                        <?php elseif ($sortBy === 'total_borrows' && $sortOrder === 'desc'): ?>
                            Most Popular Authors
                        <?php else: ?>
                            Authors Directory
                        <?php endif; ?>
                    </h1>
                    <p class="text-muted mb-0">
                        <?php if ($search): ?>
                            Results for "<?= e($search) ?>" - <?= number_format($totalAuthors) ?> authors found
                        <?php elseif ($sortBy === 'book_count' && $sortOrder === 'desc'): ?>
                            Authors with the most books in our collection
                        <?php elseif ($sortBy === 'avg_rating' && $sortOrder === 'desc'): ?>
                            Authors with the highest average ratings
                        <?php elseif ($sortBy === 'total_borrows' && $sortOrder === 'desc'): ?>
                            Most borrowed authors in our library
                        <?php else: ?>
                            Discover our collection of <?= number_format($authorStats['total_authors']) ?> talented authors
                        <?php endif; ?>
                    </p>
                </div>
                <div class="col-md-6 text-md-end">
                    <div class="d-flex flex-wrap justify-content-md-end gap-2">
                        <span class="badge bg-primary fs-6 px-3 py-2">
                            <i class="fas fa-user-edit me-1"></i><?= number_format($totalAuthors) ?> Authors
                        </span>
                        <span class="badge bg-success fs-6 px-3 py-2">
                            <i class="fas fa-book me-1"></i><?= number_format($authorStats['total_books']) ?> Books
                        </span>
                        <span class="badge bg-warning fs-6 px-3 py-2">
                            <i class="fas fa-star me-1"></i><?= number_format($authorStats['avg_rating'], 1) ?> Avg Rating
                        </span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Advanced Search & Filters -->
    <div class="filters-section bg-light border-bottom">
        <div class="container-fluid">
            <form method="GET" id="filterForm" class="py-4">
                <div class="row g-3">
                    <!-- Search -->
                    <div class="col-lg-4">
                        <div class="search-wrapper">
                            <div class="input-group input-group-lg">
                                <span class="input-group-text bg-white border-end-0">
                                    <i class="fas fa-search text-muted"></i>
                                </span>
                                <input type="text" name="search" value="<?= e($search) ?>" 
                                       class="form-control border-start-0 ps-0" 
                                       placeholder="Search authors by name...">
                                <?php if ($search): ?>
                                <button type="button" class="btn btn-outline-secondary" onclick="clearSearch()">
                                    <i class="fas fa-times"></i>
                                </button>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>

                    <!-- Minimum Books Filter -->
                    <div class="col-lg-2">
                        <select name="min_books" class="form-select form-select-lg">
                            <option value="1" <?= $minBooks === 1 ? 'selected' : '' ?>>Any Books</option>
                            <option value="2" <?= $minBooks === 2 ? 'selected' : '' ?>>2+ Books</option>
                            <option value="3" <?= $minBooks === 3 ? 'selected' : '' ?>>3+ Books</option>
                            <option value="5" <?= $minBooks === 5 ? 'selected' : '' ?>>5+ Books</option>
                            <option value="10" <?= $minBooks === 10 ? 'selected' : '' ?>>10+ Books</option>
                        </select>
                    </div>

                    <!-- Sort Options -->
                    <div class="col-lg-3">
                        <select name="sort" class="form-select form-select-lg">
                            <option value="name" <?= $sortBy === 'name' ? 'selected' : '' ?>>Author Name</option>
                            <option value="book_count" <?= $sortBy === 'book_count' ? 'selected' : '' ?>>Number of Books</option>
                            <option value="avg_rating" <?= $sortBy === 'avg_rating' ? 'selected' : '' ?>>Average Rating</option>
                            <option value="total_borrows" <?= $sortBy === 'total_borrows' ? 'selected' : '' ?>>Popularity</option>
                            <option value="latest_book" <?= $sortBy === 'latest_book' ? 'selected' : '' ?>>Latest Book</option>
                        </select>
                    </div>

                    <!-- Sort Order & Actions -->
                    <div class="col-lg-3">
                        <div class="d-flex gap-2">
                            <button type="button" class="btn btn-lg btn-outline-secondary flex-fill" 
                                    onclick="toggleSortOrder()" title="Sort Order">
                                <i class="fas fa-sort-<?= $sortOrder === 'asc' ? 'up' : 'down' ?>"></i>
                                <?= $sortOrder === 'asc' ? 'A-Z' : 'Z-A' ?>
                            </button>
                            <button type="submit" class="btn btn-lg btn-primary flex-fill">
                                <i class="fas fa-filter me-1"></i>Filter
                            </button>
                        </div>
                        <input type="hidden" name="order" value="<?= $sortOrder ?>" id="sortOrder">
                        <input type="hidden" name="view" value="<?= $viewMode ?>" id="viewMode">
                        <input type="hidden" name="limit" value="<?= $limit ?>" id="limitInput">
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- View Controls & Results Info -->
    <div class="view-controls-section bg-white border-bottom">
        <div class="container-fluid">
            <div class="row align-items-center py-3">
                <div class="col-md-6">
                    <div class="results-info">
                        <span class="text-muted">
                            Showing <?= count($authors) ?> of <?= number_format($totalAuthors) ?> authors
                            <?php if ($search): ?>
                                for "<strong><?= e($search) ?></strong>"
                            <?php endif; ?>
                            <?php if ($minBooks > 1): ?>
                                with <strong><?= $minBooks ?>+</strong> books
                            <?php endif; ?>
                        </span>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="d-flex justify-content-md-end align-items-center gap-3">
                        <!-- Items per page -->
                        <div class="d-flex align-items-center">
                            <label class="form-label mb-0 me-2 text-muted">Show:</label>
                            <select class="form-select form-select-sm" style="width: auto;" onchange="changeLimit(this.value)">
                                <option value="12" <?= $limit === 12 ? 'selected' : '' ?>>12</option>
                                <option value="24" <?= $limit === 24 ? 'selected' : '' ?>>24</option>
                                <option value="36" <?= $limit === 36 ? 'selected' : '' ?>>36</option>
                                <option value="48" <?= $limit === 48 ? 'selected' : '' ?>>48</option>
                            </select>
                        </div>

                        <!-- View Mode Toggle -->
                        <div class="btn-group" role="group">
                            <button type="button" class="btn btn-outline-secondary <?= $viewMode === 'grid' ? 'active' : '' ?>" 
                                    onclick="changeView('grid')" title="Grid View">
                                <i class="fas fa-th"></i>
                            </button>
                            <button type="button" class="btn btn-outline-secondary <?= $viewMode === 'list' ? 'active' : '' ?>" 
                                    onclick="changeView('list')" title="List View">
                                <i class="fas fa-list"></i>
                            </button>
                            <button type="button" class="btn btn-outline-secondary <?= $viewMode === 'compact' ? 'active' : '' ?>" 
                                    onclick="changeView('compact')" title="Compact View">
                                <i class="fas fa-th-list"></i>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Authors Display -->
    <div class="authors-section">
        <div class="container-fluid">
            <?php if (!empty($authors)): ?>
                
                <!-- Grid View -->
                <?php if ($viewMode === 'grid'): ?>
                <div class="row row-cols-1 row-cols-sm-2 row-cols-md-3 row-cols-lg-4 g-4 py-4" id="authorsGrid">
                    <?php foreach ($authors as $author): ?>
                    <div class="col animate-on-scroll">
                        <div class="author-card-premium h-100">
                            <div class="author-photo-wrapper">
                                <?php 
                                $authorPhoto = $author['author_photo'] ?? null;
                                $authorName = $author['author'];
                                $avatarUrl = getAuthorAvatarUrl($authorName, 150);
                                
                                if ($authorPhoto && file_exists(__DIR__ . '/public/uploads/authors/' . $authorPhoto)): ?>
                                    <img src="<?= baseUrl() ?>/public/uploads/authors/<?= e($authorPhoto) ?>" 
                                         alt="<?= e($authorName) ?>" class="author-photo">
                                <?php else: ?>
                                    <img src="<?= $avatarUrl ?>" 
                                         alt="<?= e($authorName) ?>" class="author-photo">
                                <?php endif; ?>
                                
                                <!-- Stats Badge -->
                                <div class="stats-badge">
                                    <span class="badge bg-primary"><?= $author['book_count'] ?> Books</span>
                                </div>

                                <!-- Quick Actions -->
                                <div class="quick-actions">
                                    <a href="author-details.php?author=<?= urlencode($authorName) ?>" 
                                       class="btn btn-sm btn-info" title="View Profile">
                                        <i class="fas fa-user"></i>
                                    </a>
                                    <a href="book-list.php?search=<?= urlencode($authorName) ?>" 
                                       class="btn btn-sm btn-primary" title="View Books">
                                        <i class="fas fa-book"></i>
                                    </a>
                                    <?php if (!empty($author['author_bio'])): ?>
                                    <button class="btn btn-sm btn-info" onclick="showAuthorBio('<?= e($authorName) ?>', '<?= e($author['author_bio']) ?>')" title="View Bio">
                                        <i class="fas fa-info"></i>
                                    </button>
                                    <?php endif; ?>
                                </div>
                            </div>
                            
                            <div class="author-info">
                                <h6 class="author-name" title="<?= e($authorName) ?>">
                                    <?= e($authorName) ?>
                                </h6>
                                
                                <div class="author-stats">
                                    <div class="stat-item">
                                        <span class="stat-value"><?= $author['book_count'] ?></span>
                                        <span class="stat-label">Books</span>
                                    </div>
                                    <div class="stat-item">
                                        <span class="stat-value"><?= number_format($author['avg_rating'], 1) ?></span>
                                        <span class="stat-label">Rating</span>
                                    </div>
                                    <div class="stat-item">
                                        <span class="stat-value"><?= $author['total_borrows'] ?></span>
                                        <span class="stat-label">Borrows</span>
                                    </div>
                                </div>

                                <?php if (!empty($author['latest_book_title'])): ?>
                                <div class="latest-book">
                                    <small class="text-muted">Latest: <?= e($author['latest_book_title']) ?></small>
                                </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>

                <!-- List View -->
                <?php if ($viewMode === 'list'): ?>
                <div class="py-4">
                    <?php foreach ($authors as $author): ?>
                    <div class="author-list-item animate-on-scroll">
                        <div class="row align-items-center">
                            <div class="col-md-2">
                                <div class="author-photo-medium">
                                    <?php 
                                    $authorPhoto = $author['author_photo'] ?? null;
                                    $authorName = $author['author'];
                                    $avatarUrl = getAuthorAvatarUrl($authorName, 100);
                                    
                                    if ($authorPhoto && file_exists(__DIR__ . '/public/uploads/authors/' . $authorPhoto)): ?>
                                        <img src="<?= baseUrl() ?>/public/uploads/authors/<?= e($authorPhoto) ?>" 
                                             alt="<?= e($authorName) ?>" class="img-fluid rounded-circle">
                                    <?php else: ?>
                                        <img src="<?= $avatarUrl ?>" 
                                             alt="<?= e($authorName) ?>" class="img-fluid rounded-circle">
                                    <?php endif; ?>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <h5 class="mb-1"><?= e($authorName) ?></h5>
                                <?php if (!empty($author['author_bio'])): ?>
                                <p class="text-muted mb-2"><?= e(substr($author['author_bio'], 0, 120)) ?><?= strlen($author['author_bio']) > 120 ? '...' : '' ?></p>
                                <?php endif; ?>
                                <?php if (!empty($author['latest_book_title'])): ?>
                                <div class="latest-book-info">
                                    <small class="text-primary">Latest: <?= e($author['latest_book_title']) ?> (<?= $author['latest_book_year'] ?>)</small>
                                </div>
                                <?php endif; ?>
                            </div>
                            <div class="col-md-2 text-center">
                                <div class="author-stats-list">
                                    <div class="stat-row">
                                        <span class="badge bg-primary"><?= $author['book_count'] ?> Books</span>
                                    </div>
                                    <div class="stat-row">
                                        <span class="badge bg-warning"><?= number_format($author['avg_rating'], 1) ?> ★</span>
                                    </div>
                                    <div class="stat-row">
                                        <span class="badge bg-success"><?= $author['total_borrows'] ?> Borrows</span>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-2 text-end">
                                <div class="btn-group-vertical">
                                    <a href="author-details.php?author=<?= urlencode($authorName) ?>" 
                                       class="btn btn-outline-info btn-sm">
                                        <i class="fas fa-user me-1"></i>View Profile
                                    </a>
                                    <a href="book-list.php?search=<?= urlencode($authorName) ?>" 
                                       class="btn btn-outline-primary btn-sm">
                                        <i class="fas fa-book me-1"></i>View Books
                                    </a>
                                    <?php if (!empty($author['author_bio'])): ?>
                                    <button class="btn btn-outline-info btn-sm" onclick="showAuthorBio('<?= e($authorName) ?>', '<?= e($author['author_bio']) ?>')">
                                        <i class="fas fa-info me-1"></i>Biography
                                    </button>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>

                <!-- Compact View -->
                <?php if ($viewMode === 'compact'): ?>
                <div class="py-4">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle">
                            <thead class="table-dark">
                                <tr>
                                    <th>Photo</th>
                                    <th>Author</th>
                                    <th>Books</th>
                                    <th>Rating</th>
                                    <th>Borrows</th>
                                    <th>Latest Book</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($authors as $author): ?>
                                <tr class="animate-on-scroll">
                                    <td>
                                        <div class="author-photo-tiny">
                                            <?php 
                                            $authorPhoto = $author['author_photo'] ?? null;
                                            $authorName = $author['author'];
                                            $avatarUrl = getAuthorAvatarUrl($authorName, 50);
                                            
                                            if ($authorPhoto && file_exists(__DIR__ . '/public/uploads/authors/' . $authorPhoto)): ?>
                                                <img src="<?= baseUrl() ?>/public/uploads/authors/<?= e($authorPhoto) ?>" 
                                                     alt="<?= e($authorName) ?>" class="img-fluid rounded-circle">
                                            <?php else: ?>
                                                <img src="<?= $avatarUrl ?>" 
                                                     alt="<?= e($authorName) ?>" class="img-fluid rounded-circle">
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                    <td>
                                        <strong><?= e($authorName) ?></strong>
                                    </td>
                                    <td>
                                        <span class="badge bg-primary"><?= $author['book_count'] ?></span>
                                    </td>
                                    <td>
                                        <span class="badge bg-warning"><?= number_format($author['avg_rating'], 1) ?> ★</span>
                                    </td>
                                    <td>
                                        <span class="badge bg-success"><?= $author['total_borrows'] ?></span>
                                    </td>
                                    <td>
                                        <?php if (!empty($author['latest_book_title'])): ?>
                                            <small><?= e($author['latest_book_title']) ?> (<?= $author['latest_book_year'] ?>)</small>
                                        <?php else: ?>
                                            <small class="text-muted">-</small>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <div class="btn-group btn-group-sm">
                                            <a href="author-details.php?author=<?= urlencode($authorName) ?>" 
                                               class="btn btn-outline-info" title="View Profile">
                                                <i class="fas fa-user"></i>
                                            </a>
                                            <a href="book-list.php?search=<?= urlencode($authorName) ?>" 
                                               class="btn btn-outline-primary" title="View Books">
                                                <i class="fas fa-book"></i>
                                            </a>
                                            <?php if (!empty($author['author_bio'])): ?>
                                            <button class="btn btn-outline-info" onclick="showAuthorBio('<?= e($authorName) ?>', '<?= e($author['author_bio']) ?>')" title="Biography">
                                                <i class="fas fa-info"></i>
                                            </button>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
                <?php endif; ?>

            <?php else: ?>
                <!-- Empty State -->
                <div class="empty-state text-center py-5">
                    <div class="empty-icon mb-4">
                        <i class="fas fa-user-edit fa-4x text-muted opacity-50"></i>
                    </div>
                    <h3 class="text-muted">No authors found</h3>
                    <p class="text-muted mb-4">
                        <?php if ($search): ?>
                            No authors match your search for "<strong><?= e($search) ?></strong>".
                        <?php elseif ($minBooks > 1): ?>
                            No authors found with <?= $minBooks ?>+ books.
                        <?php else: ?>
                            No authors are currently available.
                        <?php endif; ?>
                    </p>
                    <div class="d-flex justify-content-center gap-2">
                        <a href="author-list.php" class="btn btn-outline-primary">
                            <i class="fas fa-refresh me-1"></i>Clear Filters
                        </a>
                        <a href="index.php" class="btn btn-primary">
                            <i class="fas fa-home me-1"></i>Back to Home
                        </a>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Advanced Pagination -->
    <?php if ($totalPages > 1): ?>
    <div class="pagination-section bg-light border-top">
        <div class="container-fluid">
            <div class="row align-items-center py-4">
                <div class="col-md-6">
                    <div class="pagination-info">
                        <span class="text-muted">
                            Page <?= $page ?> of <?= $totalPages ?> 
                            (<?= number_format($totalAuthors) ?> total authors)
                        </span>
                    </div>
                </div>
                <div class="col-md-6">
                    <nav aria-label="Author pagination">
                        <ul class="pagination justify-content-md-end mb-0">
                            <!-- First Page -->
                            <?php if ($page > 2): ?>
                            <li class="page-item">
                                <a class="page-link" href="<?= buildAuthorUrl(['page' => 1]) ?>">
                                    <i class="fas fa-angle-double-left"></i>
                                </a>
                            </li>
                            <?php endif; ?>

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

                            <!-- Last Page -->
                            <?php if ($page < $totalPages - 1): ?>
                            <li class="page-item">
                                <a class="page-link" href="<?= buildAuthorUrl(['page' => $totalPages]) ?>">
                                    <i class="fas fa-angle-double-right"></i>
                                </a>
                            </li>
                            <?php endif; ?>
                        </ul>
                    </nav>
                </div>
            </div>
        </div>
    </div>
    <?php endif; ?>
</div>

<!-- Author Biography Modal -->
<div class="modal fade" id="authorBioModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="authorBioTitle">Author Biography</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div id="authorBioContent"></div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                <button type="button" class="btn btn-primary" id="viewAuthorBooks">View Books</button>
            </div>
        </div>
    </div>
</div>

<?php
// Helper function to build URLs with current parameters
function buildAuthorUrl($newParams = []) {
    global $search, $sortBy, $sortOrder, $viewMode, $limit, $minBooks;
    
    $params = array_filter([
        'search' => $search,
        'sort' => $sortBy,
        'order' => $sortOrder,
        'view' => $viewMode,
        'limit' => $limit,
        'min_books' => $minBooks
    ]);
    
    $params = array_merge($params, $newParams);
    
    return 'author-list.php?' . http_build_query($params);
}
?>

<script>
// View mode functions
function changeView(mode) {
    document.getElementById('viewMode').value = mode;
    document.getElementById('filterForm').submit();
}

function changeLimit(limit) {
    document.getElementById('limitInput').value = limit;
    document.getElementById('filterForm').submit();
}

function toggleSortOrder() {
    const currentOrder = document.getElementById('sortOrder').value;
    document.getElementById('sortOrder').value = currentOrder === 'asc' ? 'desc' : 'asc';
    document.getElementById('filterForm').submit();
}

function clearSearch() {
    document.querySelector('input[name="search"]').value = '';
    document.getElementById('filterForm').submit();
}

// Author biography modal
let currentAuthorName = '';

function showAuthorBio(authorName, bio) {
    currentAuthorName = authorName;
    document.getElementById('authorBioTitle').textContent = authorName + ' - Biography';
    document.getElementById('authorBioContent').innerHTML = '<p>' + bio + '</p>';
    
    const modal = new bootstrap.Modal(document.getElementById('authorBioModal'));
    modal.show();
}

document.getElementById('viewAuthorBooks').addEventListener('click', function() {
    if (currentAuthorName) {
        window.location.href = 'book-list.php?search=' + encodeURIComponent(currentAuthorName);
    }
});

// Auto-submit form on filter changes
document.addEventListener('DOMContentLoaded', function() {
    const selects = document.querySelectorAll('select[name="min_books"], select[name="sort"]');
    selects.forEach(select => {
        select.addEventListener('change', function() {
            document.getElementById('filterForm').submit();
        });
    });
});
</script>

<?php include 'views/footer.php'; ?>