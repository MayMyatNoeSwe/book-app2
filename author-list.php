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

<style>
/* ── Author Directory Premium Styles ── */
.author-dir-hero {
    position: relative;
    padding: 70px 0 50px;
    overflow: hidden;
    background: 
        radial-gradient(ellipse at 20% 50%, rgba(224,122,95,0.12) 0%, transparent 60%),
        radial-gradient(ellipse at 80% 30%, rgba(129,178,154,0.10) 0%, transparent 50%),
        var(--bookhouse-bg, #FFF3F0);
}
[data-bs-theme="dark"] .author-dir-hero {
    background:
        radial-gradient(ellipse at 20% 50%, rgba(224,122,95,0.15) 0%, transparent 55%),
        radial-gradient(ellipse at 80% 30%, rgba(129,178,154,0.12) 0%, transparent 50%),
        #0f172a;
}
.author-dir-hero::before {
    content: '';
    position: absolute;
    inset: 0;
    background-image:
        linear-gradient(rgba(0,0,0,0.025) 1px, transparent 1px),
        linear-gradient(90deg, rgba(0,0,0,0.025) 1px, transparent 1px);
    background-size: 60px 60px;
    pointer-events: none;
}
[data-bs-theme="dark"] .author-dir-hero::before {
    background-image:
        linear-gradient(rgba(255,255,255,0.03) 1px, transparent 1px),
        linear-gradient(90deg, rgba(255,255,255,0.03) 1px, transparent 1px);
}
.author-dir-hero .hero-badge {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    background: rgba(224,122,95,0.1);
    color: var(--bookhouse-orange, #E07A5F);
    font-size: 11px;
    font-weight: 700;
    letter-spacing: 1.5px;
    text-transform: uppercase;
    padding: 6px 16px;
    border-radius: 999px;
    border: 1px solid rgba(224,122,95,0.2);
    margin-bottom: 16px;
}
.author-dir-hero h1 {
    font-family: 'Playfair Display', serif;
    font-size: clamp(2rem, 5vw, 3.2rem);
    font-weight: 800;
    color: var(--bookhouse-text, #2D3436);
    line-height: 1.15;
    margin-bottom: 16px;
}
.author-dir-hero h1 span {
    background: linear-gradient(135deg, var(--bookhouse-orange, #E07A5F), #c2664e);
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
    background-clip: text;
}
.author-dir-hero .hero-desc {
    font-size: 1.05rem;
    color: var(--bookhouse-text-muted, #636E72);
    max-width: 540px;
    line-height: 1.7;
}
.author-hero-stats {
    display: flex;
    flex-wrap: wrap;
    gap: 14px;
    margin-top: 28px;
}
.author-hero-stat {
    display: flex;
    align-items: center;
    gap: 12px;
    background: rgba(255,255,255,0.75);
    backdrop-filter: blur(10px);
    border: 1px solid rgba(0,0,0,0.06);
    border-radius: 16px;
    padding: 12px 20px;
    min-width: 140px;
    transition: transform 0.2s ease;
}
.author-hero-stat:hover { transform: translateY(-2px); }
[data-bs-theme="dark"] .author-hero-stat {
    background: rgba(30,41,59,0.7);
    border-color: rgba(255,255,255,0.08);
}
.author-hero-stat .stat-icon-wrap {
    width: 40px; height: 40px;
    display: flex; align-items: center; justify-content: center;
    border-radius: 12px;
    font-size: 16px;
}
.stat-icon-wrap.blue   { background: rgba(59,130,246,0.12); color: #3b82f6; }
.stat-icon-wrap.green  { background: rgba(16,185,129,0.12); color: #10b981; }
.stat-icon-wrap.yellow { background: rgba(245,158,11,0.12); color: #f59e0b; }
.author-hero-stat .stat-text .val { font-weight: 800; font-size: 18px; color: var(--bookhouse-text); }
.author-hero-stat .stat-text .lbl { font-size: 11px; text-transform: uppercase; letter-spacing: 0.8px; color: var(--bookhouse-text-muted); font-weight: 600; }

/* ── Filter Bar ── */
.author-filter-bar {
    background: var(--bookhouse-bg, #FFF3F0);
    border-bottom: 1px solid rgba(0,0,0,0.06);
    padding: 16px 0;
    position: sticky;
    top: 68px;
    z-index: 90;
    transition: box-shadow 0.2s;
}
[data-bs-theme="dark"] .author-filter-bar {
    background: #0f172a;
    border-color: rgba(255,255,255,0.06);
}
.author-filter-bar.scrolled {
    box-shadow: 0 4px 20px rgba(0,0,0,0.08);
}
.filter-search-wrap {
    display: flex;
    align-items: center;
    gap: 10px;
    background: rgba(0,0,0,0.03);
    border-radius: 14px;
    padding: 4px 16px;
    border: 1px solid rgba(0,0,0,0.06);
    transition: border-color 0.2s, box-shadow 0.2s;
}
.filter-search-wrap:focus-within {
    border-color: var(--bookhouse-orange);
    box-shadow: 0 0 0 3px rgba(224,122,95,0.1);
}
[data-bs-theme="dark"] .filter-search-wrap {
    background: rgba(255,255,255,0.05);
    border-color: rgba(255,255,255,0.08);
}
.filter-search-wrap input {
    border: none; background: none; outline: none;
    padding: 10px 0; flex: 1; font-size: 14px;
    color: var(--bookhouse-text);
}
.filter-search-wrap input::placeholder { color: var(--bookhouse-text-muted); }
.filter-pill-select {
    appearance: none;
    border: 1px solid rgba(0,0,0,0.08);
    background: rgba(0,0,0,0.02);
    border-radius: 12px;
    padding: 10px 36px 10px 14px;
    font-size: 13px; font-weight: 600;
    color: var(--bookhouse-text);
    cursor: pointer;
    background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='12' viewBox='0 0 12 12'%3E%3Cpath fill='%23636e72' d='M6 8L1 3h10z'/%3E%3C/svg%3E");
    background-repeat: no-repeat;
    background-position: right 12px center;
    transition: border-color 0.2s;
}
[data-bs-theme="dark"] .filter-pill-select {
    background-color: rgba(255,255,255,0.05);
    border-color: rgba(255,255,255,0.1);
    background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='12' viewBox='0 0 12 12'%3E%3Cpath fill='%2394a3b8' d='M6 8L1 3h10z'/%3E%3C/svg%3E");
}
.filter-pill-select:focus { border-color: var(--bookhouse-orange); outline: none; }
.view-toggle-group {
    display: inline-flex;
    border: 1px solid rgba(0,0,0,0.08);
    border-radius: 12px;
    overflow: hidden;
}
[data-bs-theme="dark"] .view-toggle-group { border-color: rgba(255,255,255,0.1); }
.view-toggle-group button {
    border: none; background: transparent;
    padding: 9px 14px; color: var(--bookhouse-text-muted);
    cursor: pointer; transition: all 0.2s;
    font-size: 14px;
}
.view-toggle-group button:hover { color: var(--bookhouse-orange); }
.view-toggle-group button.active {
    background: var(--bookhouse-orange);
    color: #fff;
}
.filter-results-text {
    font-size: 13px; color: var(--bookhouse-text-muted);
    display: flex; align-items: center; gap: 6px;
}

/* ── Author Cards ── */
.author-grid { padding: 40px 0 60px; }
.al-card {
    background: #fff;
    border-radius: 20px;
    border: 1px solid rgba(0,0,0,0.06);
    padding: 28px 20px 24px;
    text-align: center;
    transition: transform 0.3s ease, box-shadow 0.3s ease;
    height: 100%;
    display: flex;
    flex-direction: column;
    align-items: center;
}
.al-card:hover {
    transform: translateY(-6px);
    box-shadow: 0 20px 50px rgba(61,64,91,0.12);
}
[data-bs-theme="dark"] .al-card {
    background: #1e293b;
    border-color: rgba(255,255,255,0.06);
}
[data-bs-theme="dark"] .al-card:hover {
    box-shadow: 0 20px 50px rgba(0,0,0,0.3);
}
.al-avatar {
    width: 100px; height: 100px;
    border-radius: 50%;
    overflow: hidden;
    margin-bottom: 16px;
    border: 3px solid rgba(224,122,95,0.15);
    box-shadow: 0 8px 20px rgba(224,122,95,0.1);
    flex-shrink: 0;
}
.al-avatar img { width: 100%; height: 100%; object-fit: cover; }
.al-card h5 {
    font-family: 'Manrope', sans-serif;
    font-weight: 700; font-size: 15px;
    color: var(--bookhouse-text);
    margin-bottom: 8px;
    overflow: hidden; text-overflow: ellipsis; white-space: nowrap;
    max-width: 100%;
}
.al-card .al-meta {
    display: flex; gap: 8px; justify-content: center;
    margin-bottom: 16px; flex-wrap: wrap;
}
.al-tag {
    font-size: 11px; font-weight: 700;
    padding: 4px 12px; border-radius: 999px;
    letter-spacing: 0.3px;
}
.al-tag.books { background: rgba(59,130,246,0.1); color: #3b82f6; }
.al-tag.rating { background: rgba(245,158,11,0.1); color: #f59e0b; }
.al-tag.borrows { background: rgba(16,185,129,0.1); color: #10b981; }
[data-bs-theme="dark"] .al-tag.books { background: rgba(59,130,246,0.2); }
[data-bs-theme="dark"] .al-tag.rating { background: rgba(245,158,11,0.2); }
[data-bs-theme="dark"] .al-tag.borrows { background: rgba(16,185,129,0.2); }

.al-card .al-actions { margin-top: auto; width: 100%; }
.al-btn-profile {
    display: block; width: 100%;
    padding: 10px; border-radius: 12px;
    font-size: 13px; font-weight: 700;
    text-align: center; text-decoration: none;
    color: var(--bookhouse-orange);
    border: 1.5px solid rgba(224,122,95,0.25);
    background: rgba(224,122,95,0.04);
    transition: all 0.2s;
}
.al-btn-profile:hover {
    background: var(--bookhouse-orange);
    color: #fff; border-color: var(--bookhouse-orange);
    transform: translateY(-1px);
    box-shadow: 0 6px 16px rgba(224,122,95,0.25);
}

/* ── List View ── */
.al-list-item {
    display: flex; align-items: center; gap: 20px;
    background: #fff; border-radius: 16px;
    border: 1px solid rgba(0,0,0,0.06);
    padding: 20px 24px; margin-bottom: 12px;
    transition: transform 0.2s ease, box-shadow 0.2s ease;
}
.al-list-item:hover {
    transform: translateX(4px);
    box-shadow: 0 8px 30px rgba(61,64,91,0.08);
}
[data-bs-theme="dark"] .al-list-item {
    background: #1e293b;
    border-color: rgba(255,255,255,0.06);
}
.al-list-avatar { width: 64px; height: 64px; border-radius: 50%; overflow: hidden; flex-shrink: 0; border: 2px solid rgba(224,122,95,0.15); }
.al-list-avatar img { width: 100%; height: 100%; object-fit: cover; }
.al-list-info { flex: 1; min-width: 0; }
.al-list-info h5 { font-weight: 700; font-size: 16px; margin: 0 0 4px; color: var(--bookhouse-text); }
.al-list-info .al-list-bio { font-size: 13px; color: var(--bookhouse-text-muted); margin: 0; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
.al-list-tags { display: flex; gap: 6px; flex-shrink: 0; flex-wrap: wrap; }
.al-list-action { flex-shrink: 0; }

/* ── Empty State ── */
.al-empty {
    text-align: center; padding: 80px 20px;
}
.al-empty-icon {
    width: 100px; height: 100px; margin: 0 auto 24px;
    background: rgba(224,122,95,0.08);
    border-radius: 50%; display: flex;
    align-items: center; justify-content: center;
    font-size: 40px; color: var(--bookhouse-orange);
}
.al-empty h3 { font-weight: 700; margin-bottom: 8px; color: var(--bookhouse-text); }
.al-empty p { color: var(--bookhouse-text-muted); margin-bottom: 24px; }

/* ── Pagination ── */
.al-pagination {
    padding: 30px 0 50px; display: flex;
    justify-content: center; align-items: center; gap: 6px;
}
.al-pagination a, .al-pagination span {
    width: 40px; height: 40px; display: inline-flex;
    align-items: center; justify-content: center;
    border-radius: 12px; font-weight: 700; font-size: 14px;
    text-decoration: none; transition: all 0.2s;
    color: var(--bookhouse-text-muted);
    border: 1px solid rgba(0,0,0,0.06);
}
[data-bs-theme="dark"] .al-pagination a, [data-bs-theme="dark"] .al-pagination span {
    border-color: rgba(255,255,255,0.08);
}
.al-pagination a:hover {
    background: rgba(224,122,95,0.08);
    color: var(--bookhouse-orange);
    border-color: rgba(224,122,95,0.2);
}
.al-pagination .active {
    background: var(--bookhouse-orange) !important;
    color: #fff !important;
    border-color: var(--bookhouse-orange) !important;
}

/* ── Responsive ── */
@media (max-width: 767px) {
    .author-dir-hero { padding: 50px 0 30px; text-align: center; }
    .author-dir-hero .hero-desc { margin: 0 auto; }
    .author-hero-stats { justify-content: center; }
    .filter-row-top { flex-direction: column; gap: 12px !important; }
    .filter-row-top > * { width: 100% !important; }
    .al-list-item { flex-direction: column; text-align: center; gap: 12px; }
    .al-list-tags { justify-content: center; }
}
</style>

<!-- ═══════  HERO  ═══════ -->
<section class="author-dir-hero">
    <div class="container position-relative" style="z-index: 2;">
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-3" style="font-size: 13px;">
                <li class="breadcrumb-item"><a href="index.php" class="text-decoration-none" style="color: var(--bookhouse-text-muted);">Home</a></li>
                <li class="breadcrumb-item active fw-bold" style="color: var(--bookhouse-orange);">Authors</li>
            </ol>
        </nav>

        <div class="hero-badge"><i class="fas fa-feather-alt"></i> Explore Creators</div>

        <h1>Discover World-Class <span>Authors</span></h1>

        <p class="hero-desc">
            Browse our curated directory of <?= number_format($authorStats['total_authors']) ?> talented authors.
            <?php if ($search): ?>
                Showing results for "<strong><?= e($search) ?></strong>".
            <?php endif; ?>
        </p>

        <div class="author-hero-stats">
            <div class="author-hero-stat">
                <div class="stat-icon-wrap blue"><i class="fas fa-user-pen"></i></div>
                <div class="stat-text">
                    <div class="val"><?= number_format($totalAuthors) ?></div>
                    <div class="lbl">Authors</div>
                </div>
            </div>
            <div class="author-hero-stat">
                <div class="stat-icon-wrap green"><i class="fas fa-book-open"></i></div>
                <div class="stat-text">
                    <div class="val"><?= number_format($authorStats['total_books']) ?></div>
                    <div class="lbl">Total Books</div>
                </div>
            </div>
            <div class="author-hero-stat">
                <div class="stat-icon-wrap yellow"><i class="fas fa-star"></i></div>
                <div class="stat-text">
                    <div class="val"><?= number_format($authorStats['avg_rating'], 1) ?></div>
                    <div class="lbl">Avg Rating</div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- ═══════  FILTER BAR  ═══════ -->
<div class="author-filter-bar" id="filterBar">
    <div class="container">
        <form method="GET" id="filterForm">
            <div class="d-flex flex-wrap align-items-center gap-3 filter-row-top">
                <!-- Search -->
                <div class="filter-search-wrap" style="flex: 1; min-width: 200px;">
                    <i class="fas fa-search" style="color: var(--bookhouse-text-muted); font-size: 14px;"></i>
                    <input type="text" name="search" value="<?= e($search) ?>" placeholder="Search by name...">
                    <?php if ($search): ?>
                        <button type="button" onclick="clearSearch()" style="border:none;background:none;cursor:pointer;color:var(--bookhouse-text-muted);"><i class="fas fa-times"></i></button>
                    <?php endif; ?>
                </div>

                <!-- Sort -->
                <select name="sort" class="filter-pill-select">
                    <option value="name" <?= $sortBy === 'name' ? 'selected' : '' ?>>Name</option>
                    <option value="book_count" <?= $sortBy === 'book_count' ? 'selected' : '' ?>>Books</option>
                    <option value="avg_rating" <?= $sortBy === 'avg_rating' ? 'selected' : '' ?>>Rating</option>
                    <option value="total_borrows" <?= $sortBy === 'total_borrows' ? 'selected' : '' ?>>Popularity</option>
                </select>

                <!-- Order Toggle -->
                <button type="button" class="filter-pill-select" onclick="toggleSortOrder()" style="cursor:pointer; display:inline-flex; align-items:center; gap:6px;">
                    <i class="fas fa-arrow-<?= $sortOrder === 'asc' ? 'up' : 'down' ?>-short-wide"></i>
                    <?= $sortOrder === 'asc' ? 'A→Z' : 'Z→A' ?>
                </button>

                <!-- View Toggle -->
                <div class="view-toggle-group">
                    <button type="button" class="<?= $viewMode === 'grid' ? 'active' : '' ?>" onclick="changeView('grid')" title="Grid"><i class="fas fa-th-large"></i></button>
                    <button type="button" class="<?= $viewMode === 'list' ? 'active' : '' ?>" onclick="changeView('list')" title="List"><i class="fas fa-bars"></i></button>
                </div>

                <!-- Results count -->
                <div class="filter-results-text ms-auto">
                    <strong style="color: var(--bookhouse-text);"><?= count($authors) ?></strong> of <?= number_format($totalAuthors) ?>
                </div>
            </div>

            <input type="hidden" name="order" value="<?= $sortOrder ?>" id="sortOrder">
            <input type="hidden" name="view" value="<?= $viewMode ?>" id="viewMode">
            <input type="hidden" name="limit" value="<?= $limit ?>" id="limitInput">
        </form>
    </div>
</div>

<!-- ═══════  AUTHORS GRID / LIST  ═══════ -->
<div class="author-grid">
    <div class="container">
        <?php if (!empty($authors)): ?>

            <?php if ($viewMode === 'grid'): ?>
            <div class="row row-cols-2 row-cols-sm-2 row-cols-md-3 row-cols-lg-4 row-cols-xl-5 g-4">
                <?php foreach ($authors as $author):
                    $authorPhoto = $author['author_photo'] ?? null;
                    $authorName = $author['author'];
                    $avatarUrl = getAuthorAvatarUrl($authorName, 150);
                    $imgSrc = ($authorPhoto && file_exists(__DIR__ . '/public/uploads/authors/' . $authorPhoto))
                        ? e(baseUrl()) . '/public/uploads/authors/' . e($authorPhoto)
                        : $avatarUrl;
                ?>
                <div class="col">
                    <div class="al-card">
                        <div class="al-avatar">
                            <img src="<?= $imgSrc ?>" alt="<?= e($authorName) ?>" loading="lazy">
                        </div>
                        <h5 title="<?= e($authorName) ?>"><?= e($authorName) ?></h5>
                        <div class="al-meta">
                            <span class="al-tag books"><?= $author['book_count'] ?> Books</span>
                            <span class="al-tag rating"><?= number_format($author['avg_rating'], 1) ?> ★</span>
                        </div>
                        <div class="al-actions">
                            <a href="author-details.php?author=<?= urlencode($authorName) ?>" class="al-btn-profile">
                                <i class="fas fa-arrow-right me-1"></i> View Profile
                            </a>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>

            <?php if ($viewMode === 'list'): ?>
            <div class="py-3">
                <?php foreach ($authors as $author):
                    $authorPhoto = $author['author_photo'] ?? null;
                    $authorName = $author['author'];
                    $avatarUrl = getAuthorAvatarUrl($authorName, 100);
                    $imgSrc = ($authorPhoto && file_exists(__DIR__ . '/public/uploads/authors/' . $authorPhoto))
                        ? e(baseUrl()) . '/public/uploads/authors/' . e($authorPhoto)
                        : $avatarUrl;
                ?>
                <div class="al-list-item">
                    <div class="al-list-avatar">
                        <img src="<?= $imgSrc ?>" alt="<?= e($authorName) ?>" loading="lazy">
                    </div>
                    <div class="al-list-info">
                        <h5><?= e($authorName) ?></h5>
                        <?php if (!empty($author['author_bio'])): ?>
                            <p class="al-list-bio"><?= e(substr($author['author_bio'], 0, 120)) ?><?= strlen($author['author_bio']) > 120 ? '...' : '' ?></p>
                        <?php endif; ?>
                    </div>
                    <div class="al-list-tags">
                        <span class="al-tag books"><?= $author['book_count'] ?> Books</span>
                        <span class="al-tag rating"><?= number_format($author['avg_rating'], 1) ?> ★</span>
                        <span class="al-tag borrows"><?= $author['total_borrows'] ?> Borrows</span>
                    </div>
                    <div class="al-list-action">
                        <a href="author-details.php?author=<?= urlencode($authorName) ?>" class="al-btn-profile" style="padding: 8px 20px; white-space: nowrap;">
                            View Profile
                        </a>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>

            <?php if ($viewMode === 'compact'): ?>
            <div class="py-4">
                <div class="table-responsive" style="border-radius: 16px; overflow: hidden; border: 1px solid rgba(0,0,0,0.06);">
                    <table class="table table-hover align-middle mb-0">
                        <thead style="background: var(--bookhouse-orange); color: #fff;">
                            <tr>
                                <th class="py-3 ps-4">Author</th>
                                <th class="py-3 text-center">Books</th>
                                <th class="py-3 text-center">Rating</th>
                                <th class="py-3 text-center">Borrows</th>
                                <th class="py-3 pe-4 text-end">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($authors as $author):
                                $authorPhoto = $author['author_photo'] ?? null;
                                $authorName = $author['author'];
                                $avatarUrl = getAuthorAvatarUrl($authorName, 50);
                                $imgSrc = ($authorPhoto && file_exists(__DIR__ . '/public/uploads/authors/' . $authorPhoto))
                                    ? e(baseUrl()) . '/public/uploads/authors/' . e($authorPhoto)
                                    : $avatarUrl;
                            ?>
                            <tr>
                                <td class="ps-4">
                                    <div class="d-flex align-items-center gap-3">
                                        <img src="<?= $imgSrc ?>" alt="<?= e($authorName) ?>" 
                                             style="width: 40px; height: 40px; border-radius: 50%; object-fit: cover; border: 2px solid rgba(224,122,95,0.15);" loading="lazy">
                                        <strong style="color: var(--bookhouse-text);"><?= e($authorName) ?></strong>
                                    </div>
                                </td>
                                <td class="text-center"><span class="al-tag books"><?= $author['book_count'] ?></span></td>
                                <td class="text-center"><span class="al-tag rating"><?= number_format($author['avg_rating'], 1) ?> ★</span></td>
                                <td class="text-center"><span class="al-tag borrows"><?= $author['total_borrows'] ?></span></td>
                                <td class="pe-4 text-end">
                                    <a href="author-details.php?author=<?= urlencode($authorName) ?>" class="al-btn-profile" style="display:inline-block; width:auto; padding: 6px 16px;">
                                        Profile
                                    </a>
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
            <div class="al-empty">
                <div class="al-empty-icon"><i class="fas fa-user-pen"></i></div>
                <h3>No Authors Found</h3>
                <p>
                    <?php if ($search): ?>
                        No authors match "<strong><?= e($search) ?></strong>".
                    <?php else: ?>
                        No authors are currently available.
                    <?php endif; ?>
                </p>
                <div class="d-flex justify-content-center gap-3">
                    <a href="author-list.php" class="al-btn-profile" style="display:inline-block; width:auto; padding: 10px 28px;">
                        <i class="fas fa-rotate-right me-1"></i> Clear Filters
                    </a>
                    <a href="index.php" class="al-btn-profile" style="display:inline-block; width:auto; padding: 10px 28px; background: var(--bookhouse-orange); color: #fff; border-color: var(--bookhouse-orange);">
                        <i class="fas fa-house me-1"></i> Home
                    </a>
                </div>
            </div>
        <?php endif; ?>

        <!-- ═══════  PAGINATION  ═══════ -->
        <?php if ($totalPages > 1): ?>
        <div class="al-pagination">
            <?php if ($page > 2): ?>
                <a href="<?= buildAuthorUrl(['page' => 1]) ?>"><i class="fas fa-angles-left"></i></a>
            <?php endif; ?>
            <?php if ($hasPrevPage): ?>
                <a href="<?= buildAuthorUrl(['page' => $page - 1]) ?>"><i class="fas fa-chevron-left"></i></a>
            <?php endif; ?>

            <?php
            $startPage = max(1, $page - 2);
            $endPage = min($totalPages, $page + 2);
            for ($i = $startPage; $i <= $endPage; $i++): ?>
                <?php if ($i === $page): ?>
                    <span class="active"><?= $i ?></span>
                <?php else: ?>
                    <a href="<?= buildAuthorUrl(['page' => $i]) ?>"><?= $i ?></a>
                <?php endif; ?>
            <?php endfor; ?>

            <?php if ($hasNextPage): ?>
                <a href="<?= buildAuthorUrl(['page' => $page + 1]) ?>"><i class="fas fa-chevron-right"></i></a>
            <?php endif; ?>
            <?php if ($page < $totalPages - 1): ?>
                <a href="<?= buildAuthorUrl(['page' => $totalPages]) ?>"><i class="fas fa-angles-right"></i></a>
            <?php endif; ?>
        </div>
        <?php endif; ?>
    </div>
</div>

<!-- Author Biography Modal -->
<div class="modal fade" id="authorBioModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content" style="border-radius: 20px; border: none;">
            <div class="modal-header border-0 pb-0">
                <h5 class="modal-title fw-bold" id="authorBioTitle">Author Biography</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div id="authorBioContent"></div>
            </div>
            <div class="modal-footer border-0">
                <button type="button" class="btn btn-secondary rounded-pill px-4" data-bs-dismiss="modal">Close</button>
                <button type="button" class="btn rounded-pill px-4" id="viewAuthorBooks" style="background: var(--bookhouse-orange); color: #fff;">View Books</button>
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
    document.getElementById('authorBioTitle').textContent = authorName + ' — Biography';
    document.getElementById('authorBioContent').innerHTML = '<p>' + bio + '</p>';
    
    const modal = new bootstrap.Modal(document.getElementById('authorBioModal'));
    modal.show();
}

document.getElementById('viewAuthorBooks').addEventListener('click', function() {
    if (currentAuthorName) {
        window.location.href = 'book-list.php?search=' + encodeURIComponent(currentAuthorName);
    }
});

// Auto-submit on sort change
document.addEventListener('DOMContentLoaded', function() {
    document.querySelectorAll('select[name="sort"]').forEach(s => {
        s.addEventListener('change', () => document.getElementById('filterForm').submit());
    });

    // Sticky filter bar shadow
    const bar = document.getElementById('filterBar');
    if (bar) {
        window.addEventListener('scroll', () => {
            bar.classList.toggle('scrolled', window.scrollY > 200);
        });
    }

    // Fade-in animation
    const cards = document.querySelectorAll('.al-card, .al-list-item');
    const obs = new IntersectionObserver((entries) => {
        entries.forEach((entry, i) => {
            if (entry.isIntersecting) {
                entry.target.style.transition = `opacity 0.5s ${i * 0.05}s ease, transform 0.5s ${i * 0.05}s ease`;
                entry.target.style.opacity = '1';
                entry.target.style.transform = 'translateY(0)';
                obs.unobserve(entry.target);
            }
        });
    }, { threshold: 0.1 });

    cards.forEach(c => {
        c.style.opacity = '0';
        c.style.transform = 'translateY(20px)';
        obs.observe(c);
    });
});
</script>

<?php include 'views/footer.php'; ?>