<?php
// Dynamic page title will be set after processing parameters
$pageTitle = "Book Library";
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
$category = $_GET['category'] ?? null;
$search = $_GET['search'] ?? null;
$sortBy = $_GET['sort'] ?? 'title';
$sortOrder = $_GET['order'] ?? 'asc';
$viewMode = $_GET['view'] ?? 'grid';
$availability = $_GET['availability'] ?? 'all';

// Clean inputs
if ($search) {
    $search = trim($search);
    if ($search === '') $search = null;
}
if ($category === '') $category = null;

// Validate sort options
$validSorts = ['title', 'author', 'year', 'category', 'created_at', 'borrowed', 'recent'];
if (!in_array($sortBy, $validSorts)) $sortBy = 'title';
if (!in_array($sortOrder, ['asc', 'desc'])) $sortOrder = 'asc';
if (!in_array($viewMode, ['grid', 'list'])) $viewMode = 'grid';

// Get books with advanced filtering
$books = $library->getAdvancedBooksPaginated($limit, $offset, $category, $search, $sortBy, $sortOrder, $availability);
$totalBooks = $library->countAdvancedBooks($category, $search, $availability);

// Calculate pagination
$totalPages = ceil($totalBooks / $limit);
$hasNextPage = $page < $totalPages;
$hasPrevPage = $page > 1;

// Get filter data
$categories = getCategories();
$bookStats = $library->getBookStats();

// Set dynamic page title based on parameters
if ($search) {
    $pageTitle = "Search: " . $search;
} elseif ($category) {
    $pageTitle = $category . " Books";
} elseif ($sortBy === 'borrowed' && $sortOrder === 'desc') {
    $pageTitle = "Best Selling Books";
} elseif ($sortBy === 'year' && $sortOrder === 'desc') {
    $pageTitle = "Recently Published Books";
} elseif ($sortBy === 'recent' && $sortOrder === 'desc') {
    $pageTitle = "Recently Published Books";
} elseif ($sortBy === 'author') {
    $pageTitle = "Books by Author";
} else {
    $pageTitle = "Book Library";
}

include 'views/header.php';
?>

<div class="book-list-container">
    <!-- Premium Header Section -->
    <div class="book-list-header py-5 mb-0">
        <div class="container position-relative z-index-1">
            <nav aria-label="breadcrumb" class="mb-4">
                <ol class="breadcrumb bg-transparent p-0 mb-0">
                    <li class="breadcrumb-item"><a href="index.php" class="text-white opacity-75 text-decoration-none">Home</a></li>
                    <li class="breadcrumb-item active text-white fw-600" aria-current="page">Library</li>
                </ol>
            </nav>
            
            <div class="row align-items-center">
                <div class="col-lg-8">
                    <h1 class="display-3 fw-800 text-white mb-3">
                        <?php if ($search): ?>
                            <span class="opacity-75 fs-4 d-block text-uppercase letter-spacing-2 mb-2">Search Results</span>
                            "<?= e($search) ?>"
                        <?php elseif ($category): ?>
                            <span class="opacity-75 fs-4 d-block text-uppercase letter-spacing-2 mb-2"><?= e($category) ?></span>
                            Collection
                        <?php else: ?>
                            <span class="opacity-75 fs-4 d-block text-uppercase letter-spacing-2 mb-2">Discover</span>
                            Explore Our Library
                        <?php endif; ?>
                    </h1>
                    <p class="lead text-white opacity-75 mb-0 fs-5">
                        Discover your next favorite story among our curated collection of <?= number_format($totalBooks) ?> books.
                    </p>
                </div>
                <div class="col-lg-4 text-lg-end mt-4 mt-lg-0">
                    <div class="stats-pill d-inline-flex align-items-center p-3 px-4 bg-white bg-opacity-10 backdrop-blur rounded-pill border border-white border-opacity-20 shadow-lg">
                        <div class="me-3">
                            <div class="stats-icon bg-white text-primary rounded-circle d-flex align-items-center justify-content-center shadow-sm" style="width: 48px; height: 48px;">
                                <i class="fas fa-book-open fs-5"></i>
                            </div>
                        </div>
                        <div class="text-start">
                            <div class="fw-800 fs-4 text-white lh-1"><?= number_format($totalBooks) ?></div>
                            <div class="text-white opacity-75 small text-uppercase fw-700 letter-spacing-1">Books Found</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Abstract Shapes for Header -->
        <div class="header-shape-1"></div>
        <div class="header-shape-2"></div>
    </div>

    <!-- Integrated Filter & Control Bar -->
    <div class="filters-bar-wrapper sticky-top">
        <div class="container">
            <div class="filters-bar bg-white rounded-4 shadow-sm border p-3 mt-n4">
                <form method="GET" id="filterForm">
                    <div class="row g-3 align-items-center">
                        <!-- Search Box -->
                        <div class="col-xl-4 col-lg-4">
                            <div class="search-input-wrapper position-relative">
                                <i class="fas fa-search position-absolute top-50 start-0 translate-middle-y ms-3 text-muted"></i>
                                <input type="text" name="search" value="<?= e($search) ?>" 
                                       class="form-control form-control-lg ps-5 border-0 bg-light rounded-3 fs-6" 
                                       placeholder="Search titles, authors, ISBN...">
                                <?php if ($search): ?>
                                <button type="button" class="btn btn-link position-absolute top-50 end-0 translate-middle-y me-2 text-muted text-decoration-none" onclick="clearSearch()">
                                    <i class="fas fa-times-circle"></i>
                                </button>
                                <?php endif; ?>
                            </div>
                        </div>
                        
                        <!-- Filters Group -->
                        <div class="col-xl-5 col-lg-5">
                            <div class="row g-2">
                                <div class="col-4">
                                    <select name="category" class="form-select border-0 bg-light rounded-3 fs-7 py-2 h-100">
                                        <option value="">Categories</option>
                                        <?php foreach ($categories as $cat): ?>
                                            <?php if ($cat === 'Uncategorized') continue; ?>
                                            <option value="<?= e($cat) ?>" <?= $category === $cat ? 'selected' : '' ?>>
                                                <?= e($cat) ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="col-4">
                                    <select name="sort" class="form-select border-0 bg-light rounded-3 fs-7 py-2 h-100">
                                        <option value="title" <?= $sortBy === 'title' ? 'selected' : '' ?>>Sort: Title</option>
                                        <option value="author" <?= $sortBy === 'author' ? 'selected' : '' ?>>Sort: Author</option>
                                        <option value="recent" <?= $sortBy === 'recent' ? 'selected' : '' ?>>Sort: Newest</option>
                                        <option value="borrowed" <?= $sortBy === 'borrowed' ? 'selected' : '' ?>>Sort: Popular</option>
                                    </select>
                                </div>
                                <div class="col-4">
                                    <select name="availability" class="form-select border-0 bg-light rounded-3 fs-7 py-2 h-100">
                                        <option value="all" <?= $availability === 'all' ? 'selected' : '' ?>>All Status</option>
                                        <option value="available" <?= $availability === 'available' ? 'selected' : '' ?>>Available</option>
                                        <option value="borrowed" <?= $availability === 'borrowed' ? 'selected' : '' ?>>Borrowed</option>
                                    </select>
                                </div>
                            </div>
                        </div>

                        <!-- Actions & View Controls -->
                        <div class="col-xl-3 col-lg-3">
                            <div class="d-flex align-items-center justify-content-lg-end gap-3 h-100">
                                <button type="button" class="btn btn-outline-light text-muted border bg-light rounded-3 px-3 h-100 d-flex align-items-center" 
                                        onclick="toggleSortOrder()" title="Toggle Sort Order">
                                    <i class="fas fa-sort-<?= $sortOrder === 'asc' ? 'up' : 'down' ?> fs-5"></i>
                                </button>
                                
                                <div class="divider d-none d-xl-block"></div>
                                
                                <div class="btn-group rounded-3 overflow-hidden border shadow-none h-100">
                                    <button type="button" class="btn border-0 py-2 px-3 <?= $viewMode === 'grid' ? 'btn-primary' : 'bg-light text-muted' ?>" onclick="changeView('grid')">
                                        <i class="fas fa-th-large"></i>
                                    </button>
                                    <button type="button" class="btn border-0 py-2 px-3 <?= $viewMode === 'list' ? 'btn-primary' : 'bg-light text-muted' ?>" onclick="changeView('list')">
                                        <i class="fas fa-list"></i>
                                    </button>
                                </div>
                                
                                <button type="submit" class="btn btn-primary rounded-3 px-4 h-100">Apply</button>
                            </div>
                        </div>
                    </div>
                    
                    <input type="hidden" name="order" value="<?= $sortOrder ?>" id="sortOrder">
                    <input type="hidden" name="view" value="<?= $viewMode ?>" id="viewMode">
                    <input type="hidden" name="limit" value="<?= $limit ?>" id="limitInput">

                    <?php if ($search || $category || $availability !== 'all'): ?>
                        <div class="mt-3 d-flex align-items-center flex-wrap gap-2">
                            <span class="text-muted small me-2"><i class="fas fa-sliders-h me-1"></i> Active:</span>
                            <?php if ($search): ?>
                                <span class="badge bg-primary bg-opacity-10 text-primary border border-primary border-opacity-10 p-2 px-3 rounded-pill fs-7">
                                    "<?= e($search) ?>"
                                </span>
                            <?php endif; ?>
                            <?php if ($category): ?>
                                <span class="badge bg-primary bg-opacity-10 text-primary border border-primary border-opacity-10 p-2 px-3 rounded-pill fs-7">
                                    <?= e($category) ?>
                                </span>
                            <?php endif; ?>
                            <?php if ($availability !== 'all'): ?>
                                <span class="badge bg-primary bg-opacity-10 text-primary border border-primary border-opacity-10 p-2 px-3 rounded-pill fs-7">
                                    <?= ucfirst($availability) ?>
                                </span>
                            <?php endif; ?>
                            <a href="book-list.php" class="small text-danger text-decoration-none ms-2 fw-600"><i class="fas fa-times me-1"></i>Reset</a>
                        </div>
                    <?php endif; ?>
                </form>
            </div>
        </div>
    </div>

    <!-- Results Info Bar -->
    <div class="container pt-4 pt-lg-5">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h5 class="mb-0 fw-700 text-muted fs-6">
                Showing 1-<?= count($books) ?> of <?= number_format($totalBooks) ?> books
            </h5>
            <div class="d-flex align-items-center gap-2">
                <span class="small text-muted fw-600">Per Page:</span>
                <select class="form-select form-select-sm border-0 bg-transparent fw-bold text-primary p-0 ps-1 pb-1" style="width: auto;" onchange="changeLimit(this.value)">
                    <option value="12" <?= $limit === 12 ? 'selected' : '' ?>>12</option>
                    <option value="24" <?= $limit === 24 ? 'selected' : '' ?>>24</option>
                    <option value="48" <?= $limit === 48 ? 'selected' : '' ?>>48</option>
                </select>
            </div>
        </div>
    </div>

    <!-- Books Display -->
    <div class="books-section pb-5">
        <div class="container">
            <?php if (!empty($books)): ?>
                
                <!-- Grid View -->
                <?php if ($viewMode === 'grid'): ?>
                <div class="row row-cols-1 row-cols-sm-2 row-cols-md-3 row-cols-lg-4 g-4 g-lg-5" id="booksGrid">
                    <?php foreach ($books as $index => $book): ?>
                    <div class="col animate-on-scroll" style="animation-delay: <?= $index * 0.05 ?>s">
                        <div class="book-card-ultra">
                            <div class="book-cover-container">
                                <?php $coverUrl = getBookCoverUrl($book, $book->getTitle(), $book->getAuthor()); ?>
                                <img src="<?= $coverUrl ?>"
                                     alt="<?= e($book->getTitle()) ?>" class="book-cover-img"
                                     onerror="this.src='<?= getDummyBookCover($book->getTitle(), $book->getAuthor(), 400, 600) ?>';">
                                
                                <div class="badge-overlay">
                                    <?php if ($book instanceof \App\EBook): ?>
                                        <span class="badge bg-info shadow-sm"><i class="fas fa-tablet-alt me-1"></i>E-Book</span>
                                    <?php elseif ($book->isAvailable()): ?>
                                        <span class="badge bg-success shadow-sm"><i class="fas fa-check me-1"></i>Available</span>
                                    <?php else: ?>
                                        <span class="badge bg-danger shadow-sm"><i class="fas fa-clock me-1"></i>Out</span>
                                    <?php endif; ?>
                                </div>

                                <div class="card-actions-glass">
                                    <div class="d-flex flex-column gap-2 p-3">
                                        <a href="book-details.php?id=<?= e($book->getId()) ?>" 
                                           class="btn btn-white blur-btn text-dark fw-700 rounded-pill py-2">
                                            <i class="fas fa-arrow-right me-2"></i>Details
                                        </a>
                                        <?php if (Auth::check() && $book->isAvailable()): ?>
                                        <button class="btn btn-primary rounded-pill py-2 fw-700" onclick="quickBorrow('<?= e($book->getId()) ?>')">
                                            <i class="fas fa-plus me-2"></i>Borrow
                                        </button>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="book-card-footer">
                                <span class="text-uppercase letter-spacing-1 text-primary fw-700 fs-xs mb-1 d-block"><?= e($book->getCategory()) ?></span>
                                <h6 class="book-name-title" title="<?= e($book->getTitle()) ?>">
                                    <?= e($book->getTitle()) ?>
                                </h6>
                                <div class="d-flex justify-content-between align-items-center mt-2">
                                    <span class="text-muted fs-7 fw-500">by <span class="text-dark fw-600"><?= e($book->getAuthor()) ?></span></span>
                                    <span class="text-muted fs-7 fw-700"><?= $book->getYear() ?></span>
                                </div>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>

                <!-- List View -->
                <?php if ($viewMode === 'list'): ?>
                <div class="d-flex flex-column gap-4">
                    <?php foreach ($books as $index => $book): ?>
                    <div class="list-item-premium animate-on-scroll" style="animation-delay: <?= $index * 0.05 ?>s">
                        <div class="row align-items-center">
                            <div class="col-lg-2 col-md-3 col-4">
                                <div class="list-cover-wrapper rounded-4 overflow-hidden shadow-sm">
                                    <?php $coverUrl = getBookCoverUrl($book, $book->getTitle(), $book->getAuthor()); ?>
                                    <img src="<?= $coverUrl ?>"
                                         alt="<?= e($book->getTitle()) ?>" class="w-100 h-100 object-fit-cover"
                                         onerror="this.src='<?= getDummyBookCover($book->getTitle(), $book->getAuthor(), 200, 300) ?>';">
                                </div>
                            </div>
                            <div class="col-lg-6 col-md-6 col-8 ps-lg-4">
                                <div class="d-flex align-items-center gap-2 mb-2">
                                    <span class="badge bg-primary bg-opacity-10 text-primary border-0 rounded-pill px-3 py-1 fs-xs fw-700"><?= e($book->getCategory()) ?></span>
                                    <?php if ($book instanceof \App\EBook): ?>
                                        <span class="badge bg-info bg-opacity-10 text-info border-0 rounded-pill px-3 py-1 fs-xs fw-700">Digital</span>
                                    <?php endif; ?>
                                </div>
                                <h4 class="mb-2 fw-800 text-dark"><?= e($book->getTitle()) ?></h4>
                                <p class="text-muted mb-3 fs-6">Written by <span class="text-dark fw-700"><?= e($book->getAuthor()) ?></span> • Published in <span class="fw-600"><?= $book->getYear() ?></span></p>
                                
                                <div class="d-flex align-items-center gap-4">
                                    <div class="d-flex align-items-center text-muted fs-7">
                                        <i class="fas fa-star text-warning me-1"></i>
                                        <span class="fw-700">4.8</span>
                                        <span class="ms-1">(120 reviews)</span>
                                    </div>
                                    <div class="status-indicator d-flex align-items-center gap-2">
                                        <?php if ($book->isAvailable()): ?>
                                            <span class="status-dot pulse-success"></span>
                                            <span class="text-success fw-700 fs-7">Available</span>
                                        <?php else: ?>
                                            <span class="status-dot bg-danger"></span>
                                            <span class="text-danger fw-700 fs-7">Currently Borrowed</span>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                            <div class="col-lg-4 col-md-3 mt-4 mt-md-0 text-md-end">
                                <div class="d-flex flex-row flex-md-column gap-2 justify-content-end align-items-md-end">
                                    <a href="book-details.php?id=<?= e($book->getId()) ?>" 
                                       class="btn btn-outline-dark border-2 rounded-pill px-4 fw-700 fs-7 transition-all hover-translate-x">
                                        View Details <i class="fas fa-arrow-right ms-2 opacity-50"></i>
                                    </a>
                                    <?php if (Auth::check() && $book->isAvailable()): ?>
                                    <button class="btn btn-primary rounded-pill px-4 fw-700 fs-7 shadow-sm" onclick="quickBorrow('<?= e($book->getId()) ?>')">
                                        Borrow Now
                                    </button>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>

            <?php else: ?>
                <!-- Ultra Modern Empty State -->
                <div class="empty-state-card text-center py-5 px-4 rounded-5 bg-white shadow-soft border mt-4">
                    <div class="empty-illustration mb-4">
                        <img src="<?= baseUrl() ?>/public/img/empty_book_search.png" alt="No results" class="img-fluid" style="max-height: 280px;">
                    </div>
                    <h2 class="fw-900 text-dark mb-3 fs-1">Oops! Nothing found.</h2>
                    <p class="text-muted mb-5 mx-auto fs-5" style="max-width: 500px;">
                        <?php if ($search): ?>
                            We couldn't find any books matching "<strong><?= e($search) ?></strong>". Perhaps try different keywords or browse our categories?
                        <?php else: ?>
                            This section looks a bit empty. Check back later or try exploring our other amazing collections!
                        <?php endif; ?>
                    </p>
                    <div class="d-flex flex-column flex-sm-row justify-content-center gap-3">
                        <a href="book-list.php" class="btn btn-primary btn-lg rounded-pill px-5 shadow-lg fw-700">
                            <i class="fas fa-sync-alt me-2"></i>Reset All Filters
                        </a>
                        <a href="index.php" class="btn btn-outline-dark border-2 btn-lg rounded-pill px-5 fw-700">
                            Back to Home
                        </a>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Modern Enhanced Pagination -->
    <?php if ($totalPages > 1): ?>
    <div class="pagination-section py-5 mt-4">
        <div class="container">
            <div class="d-flex flex-column flex-md-row justify-content-between align-items-center gap-4">
                <div class="text-muted fw-600 order-2 order-md-1">
                    Viewing Page <span class="text-dark fw-800"><?= $page ?></span> of <span class="text-dark fw-800"><?= $totalPages ?></span>
                </div>
                
                <nav aria-label="Pagination" class="order-1 order-md-2">
                    <ul class="pagination pagination-luxury mb-0 gap-2">
                        <?php if ($hasPrevPage): ?>
                        <li class="page-item">
                            <a class="page-link rounded-circle d-flex align-items-center justify-content-center" href="<?= buildUrl(['page' => $page - 1]) ?>" style="width: 45px; height: 45px;">
                                <i class="fas fa-chevron-left"></i>
                            </a>
                        </li>
                        <?php endif; ?>

                        <?php
                        $startPage = max(1, $page - 2);
                        $endPage = min($totalPages, $page + 2);
                        
                        if ($page <= 3) $endPage = min($totalPages, 5);
                        if ($page > $totalPages - 3) $startPage = max(1, $totalPages - 4);

                        for ($i = $startPage; $i <= $endPage; $i++): ?>
                        <li class="page-item <?= $i === $page ? 'active' : '' ?>">
                            <a class="page-link rounded-circle d-flex align-items-center justify-content-center fw-800" href="<?= buildUrl(['page' => $i]) ?>" style="width: 45px; height: 45px;"><?= $i ?></a>
                        </li>
                        <?php endfor; ?>

                        <?php if ($hasNextPage): ?>
                        <li class="page-item">
                            <a class="page-link rounded-circle d-flex align-items-center justify-content-center" href="<?= buildUrl(['page' => $page + 1]) ?>" style="width: 45px; height: 45px;">
                                <i class="fas fa-chevron-right"></i>
                            </a>
                        </li>
                        <?php endif; ?>
                    </ul>
                </nav>
            </div>
        </div>
    </div>
    <?php endif; ?>
</div>

<!-- Premium Quick Borrow Modal -->
<div class="modal fade" id="quickBorrowModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-2xl rounded-5 overflow-hidden">
            <div class="modal-header border-0 bg-primary p-4 text-white">
                <div class="d-flex align-items-center gap-3">
                    <div class="modal-icon-bg bg-white bg-opacity-20 rounded-circle d-flex align-items-center justify-content-center" style="width: 45px; height: 45px;">
                        <i class="fas fa-bookmark fs-5"></i>
                    </div>
                    <div>
                        <h5 class="modal-title fw-800 mb-0">Borrowing Confirmation</h5>
                        <p class="mb-0 small opacity-75">You're one step away from your next read.</p>
                    </div>
                </div>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-4 bg-light">
                <div class="notice-card p-4 rounded-4 bg-white border shadow-sm mb-4">
                    <div class="d-flex align-items-start gap-3">
                        <div class="text-primary mt-1">
                            <i class="fas fa-info-circle fs-5"></i>
                        </div>
                        <div>
                            <h6 class="fw-800 text-dark mb-1">Standard Policy</h6>
                            <p class="mb-0 text-muted small lh-base">By borrowing, you agree to return this item within 14 days. Late returns may incur a small fee or membership points deduction.</p>
                        </div>
                    </div>
                </div>
                <p class="mb-0 fw-700 text-dark text-center fs-5">Ready to proceed with this borrow?</p>
            </div>
            <div class="modal-footer border-0 p-4 bg-light pt-0 mt-n1">
                <div class="d-grid w-100 gap-2">
                    <button type="button" class="btn btn-primary btn-lg rounded-pill fw-800 py-3 shadow-lg" id="confirmBorrow">
                        Confirm & Borrow
                    </button>
                    <button type="button" class="btn btn-link text-muted fw-600 text-decoration-none" data-bs-dismiss="modal">
                        Maybe later
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<?php
function buildUrl($newParams = []) {
    global $category, $search, $sortBy, $sortOrder, $viewMode, $limit;
    $params = array_filter([
        'category' => $category,
        'search' => $search,
        'sort' => $sortBy,
        'order' => $sortOrder,
        'view' => $viewMode,
        'limit' => $limit
    ]);
    return 'book-list.php?' . http_build_query(array_merge($params, $newParams));
}
?>

<script>
function changeView(mode) {
    document.getElementById('viewMode').value = mode;
    document.getElementById('filterForm').submit();
}

function changeLimit(limit) {
    document.getElementById('limitInput').value = limit;
    document.getElementById('filterForm').submit();
}

function toggleSortOrder() {
    const orderInput = document.getElementById('sortOrder');
    orderInput.value = orderInput.value === 'asc' ? 'desc' : 'asc';
    document.getElementById('filterForm').submit();
}

function clearSearch() {
    document.querySelector('input[name="search"]').value = '';
    document.getElementById('filterForm').submit();
}

let currentBookId = null;
function quickBorrow(bookId) {
    <?php if (!Auth::check()): ?>
        window.location.href = 'login.php?redirect=' + encodeURIComponent(window.location.href);
        return;
    <?php endif; ?>
    currentBookId = bookId;
    new bootstrap.Modal(document.getElementById('quickBorrowModal')).show();
}

document.getElementById('confirmBorrow').addEventListener('click', function() {
    if (currentBookId) {
        const form = document.createElement('form');
        form.method = 'POST';
        form.action = 'book-details.php';
        
        [['id', currentBookId], ['action', 'borrow']].forEach(([name, value]) => {
            const input = document.createElement('input');
            input.type = 'hidden';
            input.name = name;
            input.value = value;
            form.appendChild(input);
        });
        
        document.body.appendChild(form);
        form.submit();
    }
});

document.addEventListener('DOMContentLoaded', () => {
    const submits = document.querySelectorAll('select[name="category"], select[name="availability"], select[name="sort"]');
    submits.forEach(s => s.addEventListener('change', () => document.getElementById('filterForm').submit()));
});
</script>

<?php include 'views/footer.php'; ?>