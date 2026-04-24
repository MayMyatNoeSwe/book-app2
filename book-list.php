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

// User Borrowing Stats for the Modal (Multi-Card & Group Aware)
$hasBorrowedBefore = false;
$unreturnedBooksCount = 0;
$borrowLimit = 3; // Default
if (Auth::check()) {
    $userId = Auth::id();
    $pdo = $library->getPdo();

    // Membership Rules for Active Card
    $msRules = $library->getMembershipRules($userId);
    $activeSubId = $msRules['sub_id'];
    $borrowLimit = (int)$msRules['personal_limit'];
    $groupLimit = (int)$msRules['group_limit'];

    $personalActiveUsage = $library->getGroupUsageCount($activeSubId, $userId);
    $groupPoolUsage = $library->getGroupUsageCount($activeSubId);
}

include 'views/header.php';
?>

<style>
/* ─── Book Collection Premium Styles ─── */
.bl-hero {
    position: relative;
    padding: 70px 0 55px;
    overflow: hidden;
    background:
        radial-gradient(ellipse at 15% 55%, rgba(224,122,95,0.14) 0%, transparent 55%),
        radial-gradient(ellipse at 85% 25%, rgba(129,178,154,0.10) 0%, transparent 50%),
        radial-gradient(ellipse at 50% 80%, rgba(242,204,143,0.08) 0%, transparent 45%),
        var(--bookhouse-bg, #FFF3F0);
}
[data-bs-theme="dark"] .bl-hero {
    background:
        radial-gradient(ellipse at 15% 55%, rgba(224,122,95,0.16) 0%, transparent 55%),
        radial-gradient(ellipse at 85% 25%, rgba(129,178,154,0.12) 0%, transparent 50%),
        #0f172a;
}
.bl-hero::before {
    content: '';
    position: absolute; inset: 0;
    background-image:
        linear-gradient(rgba(0,0,0,0.02) 1px, transparent 1px),
        linear-gradient(90deg, rgba(0,0,0,0.02) 1px, transparent 1px);
    background-size: 55px 55px;
    pointer-events: none;
}
[data-bs-theme="dark"] .bl-hero::before {
    background-image:
        linear-gradient(rgba(255,255,255,0.025) 1px, transparent 1px),
        linear-gradient(90deg, rgba(255,255,255,0.025) 1px, transparent 1px);
}
.bl-badge {
    display: inline-flex; align-items: center; gap: 6px;
    background: rgba(224,122,95,0.1);
    color: var(--bookhouse-orange, #E07A5F);
    font-size: 11px; font-weight: 700;
    letter-spacing: 1.5px; text-transform: uppercase;
    padding: 6px 16px; border-radius: 999px;
    border: 1px solid rgba(224,122,95,0.2);
    margin-bottom: 16px;
}
.bl-hero h1 {
    font-family: 'Playfair Display', serif;
    font-size: clamp(2rem, 5vw, 3.2rem);
    font-weight: 800; line-height: 1.15;
    color: var(--bookhouse-text);
    margin-bottom: 16px;
}
.bl-hero h1 span {
    background: linear-gradient(135deg, var(--bookhouse-orange), #c2664e);
    -webkit-background-clip: text; -webkit-text-fill-color: transparent;
    background-clip: text;
}
.bl-hero-desc {
    font-size: 1.05rem; color: var(--bookhouse-text-muted);
    max-width: 560px; line-height: 1.7;
}
.bl-hero-stats {
    display: flex; flex-wrap: wrap; gap: 14px; margin-top: 26px;
}
.bl-stat {
    display: flex; align-items: center; gap: 12px;
    background: rgba(255,255,255,0.75);
    backdrop-filter: blur(10px);
    border: 1px solid rgba(0,0,0,0.06);
    border-radius: 16px; padding: 12px 20px;
    transition: transform 0.2s;
}
.bl-stat:hover { transform: translateY(-2px); }
[data-bs-theme="dark"] .bl-stat {
    background: rgba(30,41,59,0.7);
    border-color: rgba(255,255,255,0.08);
}
.bl-stat-icon {
    width: 40px; height: 40px;
    display: flex; align-items: center; justify-content: center;
    border-radius: 12px; font-size: 16px;
}
.bl-stat-icon.orange { background: rgba(224,122,95,0.12); color: #E07A5F; }
.bl-stat-icon.mint   { background: rgba(129,178,154,0.12); color: #81B29A; }
.bl-stat-icon.gold   { background: rgba(242,204,143,0.15); color: #d4a646; }
.bl-stat .val { font-weight: 800; font-size: 18px; color: var(--bookhouse-text); }
.bl-stat .lbl { font-size: 11px; text-transform: uppercase; letter-spacing: 0.8px; color: var(--bookhouse-text-muted); font-weight: 600; }

/* ─── Filter Bar ─── */
.bl-filter-bar {
    background: var(--bookhouse-bg, #FFF3F0);
    border-bottom: 1px solid rgba(0,0,0,0.06);
    padding: 14px 0;
    position: sticky; top: 68px; z-index: 90;
    transition: box-shadow 0.2s;
}
[data-bs-theme="dark"] .bl-filter-bar {
    background: #0f172a; border-color: rgba(255,255,255,0.06);
}
.bl-filter-bar.scrolled { box-shadow: 0 4px 24px rgba(0,0,0,0.08); }
.bl-search-wrap {
    display: flex; align-items: center; gap: 12px;
    background: rgba(255,255,255,0.8);
    backdrop-filter: blur(8px);
    border-radius: 16px; padding: 6px 18px;
    border: 1px solid rgba(61, 64, 91, 0.1);
    transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    box-shadow: 0 2px 10px rgba(0,0,0,0.02);
}
.bl-search-wrap:focus-within {
    border-color: var(--bookhouse-orange);
    background: #fff;
    box-shadow: 0 8px 24px rgba(224, 122, 95, 0.12);
    transform: translateY(-1px);
}
[data-bs-theme="dark"] .bl-search-wrap {
    background: rgba(255,255,255,0.05);
    border-color: rgba(255,255,255,0.08);
}
.bl-search-wrap input {
    border: none; background: none; outline: none;
    padding: 10px 0; flex: 1; font-size: 14px;
    color: var(--bookhouse-text);
}
.bl-search-wrap input::placeholder { color: var(--bookhouse-text-muted); }
.bl-pill-select {
    appearance: none;
    border: 1px solid rgba(61, 64, 91, 0.1);
    background: rgba(255,255,255,0.8);
    backdrop-filter: blur(8px);
    border-radius: 14px;
    padding: 10px 40px 10px 16px;
    font-size: 13px; font-weight: 700;
    color: var(--bookhouse-dark); cursor: pointer;
    background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='12' viewBox='0 0 12 12'%3E%3Cpath fill='%23E07A5F' d='M6 8.5L2 4h8z'/%3E%3C/svg%3E");
    background-repeat: no-repeat;
    background-position: right 14px center;
    transition: all 0.3s ease;
    box-shadow: 0 2px 8px rgba(0,0,0,0.02);
}
[data-bs-theme="dark"] .bl-pill-select {
    background-color: rgba(30, 41, 59, 0.5);
    border-color: rgba(255, 255, 255, 0.1);
    color: #f1f5f9;
    background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='12' viewBox='0 0 12 12'%3E%3Cpath fill='%2394a3b8' d='M6 8L1 3h10z'/%3E%3C/svg%3E");
}
.bl-pill-select:hover {
    border-color: var(--bookhouse-orange);
    background-color: #fff;
    transform: translateY(-1px);
    box-shadow: 0 4px 12px rgba(0,0,0,0.05);
}
.bl-pill-select:focus { 
    border-color: var(--bookhouse-orange); 
    outline: none;
    box-shadow: 0 0 0 4px rgba(224, 122, 95, 0.15);
}
.bl-view-toggle {
    display: inline-flex;
    border: 1px solid rgba(0,0,0,0.08);
    border-radius: 12px; overflow: hidden;
}
[data-bs-theme="dark"] .bl-view-toggle { border-color: rgba(255,255,255,0.1); }
.bl-view-toggle button {
    border: none; background: transparent;
    padding: 9px 14px; color: var(--bookhouse-text-muted);
    cursor: pointer; transition: all 0.2s; font-size: 14px;
}
.bl-view-toggle button:hover { color: var(--bookhouse-orange); }
.bl-view-toggle button.active { background: var(--bookhouse-orange); color: #fff; }
.bl-active-filters {
    display: flex; flex-wrap: wrap; align-items: center; gap: 8px;
    margin-top: 12px; padding-top: 12px;
    border-top: 1px solid rgba(0,0,0,0.04);
}
[data-bs-theme="dark"] .bl-active-filters { border-color: rgba(255,255,255,0.06); }

.bl-btn-clear {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    padding: 6px 14px;
    border-radius: 999px;
    background: rgba(239, 68, 68, 0.08); /* Pale red */
    color: #ef4444 !important;
    font-size: 11px;
    font-weight: 800;
    text-transform: uppercase;
    letter-spacing: 0.8px;
    text-decoration: none !important;
    border: 1.5px solid rgba(239, 68, 68, 0.12);
    transition: all 0.3s cubic-bezier(0.175, 0.885, 0.32, 1.275);
    margin-left: 8px;
}

.bl-btn-clear:hover {
    background: #ef4444;
    color: #fff !important;
    border-color: #ef4444;
    transform: translateY(-1px);
    box-shadow: 0 6px 15px rgba(239, 68, 68, 0.25);
}

.bl-filter-chip {
    display: inline-flex; align-items: center; gap: 6px;
    background: rgba(224,122,95,0.08); color: var(--bookhouse-orange);
    border: 1px solid rgba(224,122,95,0.15);
    border-radius: 999px; padding: 4px 14px;
    font-size: 12px; font-weight: 700;
}

/* ─── Book Cards — Grid ─── */
.bl-content { padding: 40px 0 60px; }
.bl-card {
    background: #fff;
    border-radius: 20px;
    border: 1px solid rgba(0,0,0,0.06);
    overflow: hidden;
    transition: transform 0.35s ease, box-shadow 0.35s ease;
    height: 100%;
    display: flex; flex-direction: column;
}
.bl-card:hover {
    transform: translateY(-8px);
    box-shadow: 0 24px 56px rgba(61,64,91,0.14);
}
[data-bs-theme="dark"] .bl-card {
    background: #1e293b; border-color: rgba(255,255,255,0.06);
}
[data-bs-theme="dark"] .bl-card:hover { box-shadow: 0 24px 56px rgba(0,0,0,0.35); }

.bl-card-cover {
    position: relative;
    aspect-ratio: 2/3;
    overflow: hidden;
    background: linear-gradient(135deg, #f0e6e0, #e8ddd5);
}
[data-bs-theme="dark"] .bl-card-cover {
    background: linear-gradient(135deg, #1a2332, #253141);
}
.bl-card-cover img {
    width: 100%; height: 100%; object-fit: cover;
    transition: transform 0.5s ease;
}
.bl-card:hover .bl-card-cover img { transform: scale(1.06); }
.bl-card-badge {
    position: absolute; top: 12px; left: 12px;
    font-size: 10px; font-weight: 800;
    letter-spacing: 0.8px; text-transform: uppercase;
    padding: 5px 12px; border-radius: 999px;
    backdrop-filter: blur(8px);
}
.bl-card-badge.available { background: rgba(16,185,129,0.9); color: #fff; }
.bl-card-badge.borrowed  { background: rgba(239,68,68,0.85); color: #fff; }
.bl-card-badge.ebook     { background: rgba(59,130,246,0.85); color: #fff; }

.bl-card-overlay {
    position: absolute; inset: 0;
    background: linear-gradient(to top, rgba(0,0,0,0.7) 0%, transparent 50%);
    display: flex; flex-direction: column;
    justify-content: flex-end; padding: 16px;
    opacity: 0; transition: opacity 0.3s ease;
}
.bl-card:hover .bl-card-overlay { opacity: 1; }
.bl-card-overlay .btn-details {
    display: block; text-align: center;
    padding: 10px; border-radius: 12px;
    background: rgba(255,255,255,0.92);
    color: var(--bookhouse-dark, #3D405B);
    font-weight: 700; font-size: 13px;
    text-decoration: none;
    transition: background 0.2s;
    backdrop-filter: blur(4px);
}
.bl-card-overlay .btn-details:hover {
    background: #fff;
}
.bl-card-overlay .btn-cart {
    display: block; text-align: center;
    padding: 10px; border-radius: 12px;
    background: transparent;
    color: #fff; border: 1px solid rgba(255,255,255,0.6);
    font-weight: 700; font-size: 13px;
    cursor: pointer; margin-top: 8px;
    transition: all 0.2s;
    width: 100%;
}
.bl-card-overlay .btn-cart:hover {
    background: rgba(255,255,255,0.1);
    border-color: #fff;
}
.bl-card-overlay .btn-borrow {
    display: block; text-align: center;
    padding: 10px; border-radius: 12px;
    background: var(--bookhouse-orange);
    color: #fff; border: none;
    font-weight: 700; font-size: 13px;
    cursor: pointer; margin-top: 8px;
    transition: filter 0.2s;
    width: 100%;
}
.bl-card-overlay .btn-borrow:hover { filter: brightness(1.1); }

.bl-card-body {
    padding: 16px 16px 18px;
    flex: 1; display: flex; flex-direction: column;
}
.bl-card-cat {
    font-size: 10px; font-weight: 800;
    letter-spacing: 1.2px; text-transform: uppercase;
    color: var(--bookhouse-orange);
    margin-bottom: 6px;
}
.bl-card-title {
    font-family: 'Manrope', sans-serif;
    font-weight: 800; font-size: 15px;
    color: var(--bookhouse-text);
    margin-bottom: 6px;
    display: -webkit-box; -webkit-line-clamp: 2;
    line-clamp: 2; -webkit-box-orient: vertical;
    overflow: hidden; line-height: 1.35;
}
.bl-card-author {
    font-size: 13px; color: var(--bookhouse-text-muted);
    margin-top: auto; padding-top: 8px;
    border-top: 1px solid rgba(0,0,0,0.04);
    display: flex; justify-content: space-between; align-items: center;
}
[data-bs-theme="dark"] .bl-card-author { border-color: rgba(255,255,255,0.06); }
.bl-card-author .year {
    font-weight: 700; font-size: 12px;
    color: var(--bookhouse-orange);
    background: rgba(224,122,95,0.08);
    padding: 2px 8px; border-radius: 6px;
}

/* ─── List View ─── */
.bl-list-item {
    display: flex; gap: 20px; align-items: center;
    background: #fff; border-radius: 18px;
    border: 1px solid rgba(0,0,0,0.06);
    padding: 16px 20px; margin-bottom: 14px;
    transition: transform 0.25s ease, box-shadow 0.25s ease;
}
.bl-list-item:hover {
    transform: translateX(4px);
    box-shadow: 0 10px 35px rgba(61,64,91,0.08);
}
[data-bs-theme="dark"] .bl-list-item {
    background: #1e293b; border-color: rgba(255,255,255,0.06);
}
.bl-list-cover {
    width: 80px; min-width: 80px; height: 110px;
    border-radius: 12px; overflow: hidden; flex-shrink: 0;
    background: #f0e6e0;
}
.bl-list-cover img { width: 100%; height: 100%; object-fit: cover; }
.bl-list-info { flex: 1; min-width: 0; }
.bl-list-info .cat {
    font-size: 10px; font-weight: 800;
    letter-spacing: 1px; text-transform: uppercase;
    color: var(--bookhouse-orange); margin-bottom: 4px;
}
.bl-list-info h5 {
    font-weight: 800; font-size: 16px; margin: 0 0 4px;
    color: var(--bookhouse-text);
    overflow: hidden; text-overflow: ellipsis; white-space: nowrap;
}
.bl-list-info .meta {
    font-size: 13px; color: var(--bookhouse-text-muted);
}
.bl-list-info .meta strong { color: var(--bookhouse-text); }
.bl-list-status {
    flex-shrink: 0; display: flex; align-items: center; gap: 6px;
    font-size: 12px; font-weight: 700;
}
.bl-list-status .dot {
    width: 8px; height: 8px; border-radius: 50%;
}
.bl-list-status .dot.green { background: #10b981; box-shadow: 0 0 6px rgba(16,185,129,0.5); }
.bl-list-status .dot.red   { background: #ef4444; }
.bl-list-actions { flex-shrink: 0; display: flex; gap: 8px; }
.bl-list-actions a, .bl-list-actions button {
    padding: 8px 18px; border-radius: 12px; font-size: 13px; font-weight: 700;
    text-decoration: none; border: none; cursor: pointer; transition: all 0.2s;
}
.bl-btn-outline {
    color: var(--bookhouse-orange);
    border: 1.5px solid rgba(224,122,95,0.25) !important;
    background: rgba(224,122,95,0.04);
}
.bl-btn-outline:hover {
    background: var(--bookhouse-orange); color: #fff;
    border-color: var(--bookhouse-orange) !important;
}
.bl-btn-fill {
    background: var(--bookhouse-orange); color: #fff;
}
.bl-btn-fill:hover { filter: brightness(1.1); }

/* ─── Empty State ─── */
.bl-empty {
    text-align: center; padding: 80px 20px;
}
.bl-empty-icon {
    width: 110px; height: 110px; margin: 0 auto 28px;
    background: rgba(224,122,95,0.08);
    border-radius: 50%; display: flex;
    align-items: center; justify-content: center;
    font-size: 44px; color: var(--bookhouse-orange);
}
.bl-empty h3 { font-weight: 800; margin-bottom: 10px; color: var(--bookhouse-text); font-size: 1.6rem; }
.bl-empty p { color: var(--bookhouse-text-muted); margin-bottom: 28px; max-width: 480px; margin-left: auto; margin-right: auto; }

/* ─── Pagination ─── */
.bl-pagination {
    padding: 30px 0 50px; display: flex;
    justify-content: center; align-items: center; gap: 6px; flex-wrap: wrap;
}
.bl-pagination a, .bl-pagination span {
    width: 42px; height: 42px; display: inline-flex;
    align-items: center; justify-content: center;
    border-radius: 12px; font-weight: 700; font-size: 14px;
    text-decoration: none; transition: all 0.2s;
    color: var(--bookhouse-text-muted);
    border: 1px solid rgba(0,0,0,0.06);
}
[data-bs-theme="dark"] .bl-pagination a, [data-bs-theme="dark"] .bl-pagination span {
    border-color: rgba(255,255,255,0.08);
}
.bl-pagination a:hover {
    background: rgba(224,122,95,0.08); color: var(--bookhouse-orange);
    border-color: rgba(224,122,95,0.2);
}
.bl-pagination .active {
    background: var(--bookhouse-orange) !important;
    color: #fff !important; border-color: var(--bookhouse-orange) !important;
    box-shadow: 0 4px 14px rgba(224,122,95,0.35);
}

/* ─── Quick Borrow Modal ─── */
.bl-modal .modal-content {
    border: none; border-radius: 24px; overflow: hidden;
}
.bl-modal .modal-header {
    background: linear-gradient(135deg, var(--bookhouse-orange), #c2664e);
    border: none; padding: 24px 28px;
}
.bl-modal .modal-body { padding: 28px; }
.bl-modal .modal-footer { border: none; padding: 0 28px 28px; }

/* ─── Responsive ─── */
@media (max-width: 767px) {
    .bl-hero { padding: 50px 0 30px; text-align: center; }
    .bl-hero-desc { margin: 0 auto; }
    .bl-hero-stats { justify-content: center; }
    .bl-filter-row { flex-direction: column; gap: 12px !important; }
    .bl-filter-row > * { width: 100% !important; }
    .bl-list-item { flex-direction: column; text-align: center; gap: 14px; }
    .bl-list-actions { justify-content: center; }
    .bl-list-status { justify-content: center; }
}
</style>

<!-- ═══════  HERO  ═══════ -->
<section class="bl-hero">
    <div class="container position-relative" style="z-index: 2;">
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-3" style="font-size: 13px;">
                <li class="breadcrumb-item"><a href="index.php" class="text-decoration-none" style="color: var(--bookhouse-text-muted);">Home</a></li>
                <li class="breadcrumb-item active fw-bold" style="color: var(--bookhouse-orange);">
                    <?= $category ? e($category) : 'Library' ?>
                </li>
            </ol>
        </nav>

        <div class="bl-badge"><i class="fas fa-book-open"></i> Book Collection</div>

        <h1>
            <?php if ($search): ?>
                Results for "<span><?= e($search) ?></span>"
            <?php elseif ($category): ?>
                <span><?= e($category) ?></span> Collection
            <?php else: ?>
                Explore Our <span>Library</span>
            <?php endif; ?>
        </h1>

        <p class="bl-hero-desc">
            Discover your next favorite story among our curated collection of <?= number_format($totalBooks) ?> books.
        </p>

        <div class="bl-hero-stats">
            <div class="bl-stat">
                <div class="bl-stat-icon orange"><i class="fas fa-book"></i></div>
                <div>
                    <div class="val"><?= number_format($totalBooks) ?></div>
                    <div class="lbl">Books Found</div>
                </div>
            </div>
            <div class="bl-stat">
                <div class="bl-stat-icon mint"><i class="fas fa-layer-group"></i></div>
                <div>
                    <div class="val"><?= count($categories) ?></div>
                    <div class="lbl">Categories</div>
                </div>
            </div>
            <div class="bl-stat">
                <div class="bl-stat-icon gold"><i class="fas fa-star"></i></div>
                <div>
                    <div class="val"><?= number_format($bookStats['avg_rating'] ?? 4.5, 1) ?></div>
                    <div class="lbl">Avg Rating</div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- ═══════  FILTER BAR  ═══════ -->
<div class="bl-filter-bar" id="filterBar">
    <div class="container">
        <form method="GET" id="filterForm">
            <div class="d-flex flex-wrap align-items-center gap-3 bl-filter-row">
                <!-- Search -->
                <div class="bl-search-wrap" style="flex: 1; min-width: 200px;">
                    <i class="fas fa-search" style="color: var(--bookhouse-text-muted); font-size: 14px;"></i>
                    <input type="text" name="search" value="<?= e($search) ?>" placeholder="Search titles, authors...">
                    <?php if ($search): ?>
                        <button type="button" onclick="clearSearch()" style="border:none;background:none;cursor:pointer;color:var(--bookhouse-text-muted);"><i class="fas fa-times"></i></button>
                    <?php endif; ?>
                </div>

                <!-- Category -->
                <select name="category" class="bl-pill-select">
                    <option value="">All Categories</option>
                    <?php foreach ($categories as $cat): ?>
                        <?php if ($cat === 'Uncategorized') continue; ?>
                        <option value="<?= e($cat) ?>" <?= $category === $cat ? 'selected' : '' ?>><?= e($cat) ?></option>
                    <?php endforeach; ?>
                </select>

                <!-- Sort -->
                <select name="sort" class="bl-pill-select">
                    <option value="title" <?= $sortBy === 'title' ? 'selected' : '' ?>>Title</option>
                    <option value="author" <?= $sortBy === 'author' ? 'selected' : '' ?>>Author</option>
                    <option value="recent" <?= $sortBy === 'recent' ? 'selected' : '' ?>>Newest</option>
                    <option value="borrowed" <?= $sortBy === 'borrowed' ? 'selected' : '' ?>>Popular</option>
                </select>

                <!-- Availability -->
                <select name="availability" class="bl-pill-select">
                    <option value="all" <?= $availability === 'all' ? 'selected' : '' ?>>All Status</option>
                    <option value="available" <?= $availability === 'available' ? 'selected' : '' ?>>Available</option>
                    <option value="borrowed" <?= $availability === 'borrowed' ? 'selected' : '' ?>>Borrowed</option>
                </select>

                <!-- Order Toggle -->
                <button type="button" class="bl-pill-select" onclick="toggleSortOrder()" style="cursor:pointer; display:inline-flex; align-items:center; gap:6px;">
                    <i class="fas fa-arrow-<?= $sortOrder === 'asc' ? 'up' : 'down' ?>-short-wide"></i>
                    <?= $sortOrder === 'asc' ? 'A→Z' : 'Z→A' ?>
                </button>

                <!-- View Toggle -->
                <div class="bl-view-toggle">
                    <button type="button" class="<?= $viewMode === 'grid' ? 'active' : '' ?>" onclick="changeView('grid')"><i class="fas fa-th-large"></i></button>
                    <button type="button" class="<?= $viewMode === 'list' ? 'active' : '' ?>" onclick="changeView('list')"><i class="fas fa-bars"></i></button>
                </div>

                <!-- Results -->
                <div class="ms-auto" style="font-size:13px; color:var(--bookhouse-text-muted);">
                    <strong style="color:var(--bookhouse-text);"><?= count($books) ?></strong> of <?= number_format($totalBooks) ?>
                </div>
            </div>

            <input type="hidden" name="order" value="<?= $sortOrder ?>" id="sortOrder">
            <input type="hidden" name="view" value="<?= $viewMode ?>" id="viewMode">
            <input type="hidden" name="limit" value="<?= $limit ?>" id="limitInput">

            <?php if ($search || $category || $availability !== 'all'): ?>
            <div class="bl-active-filters">
                <span style="font-size:12px; color:var(--bookhouse-text-muted); font-weight:600;"><i class="fas fa-filter me-1"></i>Active:</span>
                <?php if ($search): ?>
                    <span class="bl-filter-chip">"<?= e($search) ?>"</span>
                <?php endif; ?>
                <?php if ($category): ?>
                    <span class="bl-filter-chip"><i class="fas fa-tag me-1"></i><?= e($category) ?></span>
                <?php endif; ?>
                <?php if ($availability !== 'all'): ?>
                    <span class="bl-filter-chip"><?= ucfirst($availability) ?></span>
                <?php endif; ?>
                <a href="book-list.php" class="bl-btn-clear"><i class="fas fa-times me-1"></i>Clear All</a>
            </div>
            <?php endif; ?>
        </form>
    </div>
</div>

<!-- ═══════  BOOKS CONTENT  ═══════ -->
<div class="bl-content">
    <div class="container">
        <?php if (!empty($books)): ?>

            <!-- ── Grid View ── -->
            <?php if ($viewMode === 'grid'): ?>
            <div class="row row-cols-2 row-cols-sm-2 row-cols-md-3 row-cols-lg-4 row-cols-xl-5 g-4">
                <?php foreach ($books as $book):
                    $coverUrl = getBookCoverUrl($book, $book->getTitle(), $book->getAuthor());
                    $fallback = getDummyBookCover($book->getTitle(), $book->getAuthor(), 400, 600);
                    $isEbook = $book instanceof \App\EBook;
                ?>
                <div class="col">
                    <div class="bl-card">
                        <div class="bl-card-cover">
                            <img src="<?= $coverUrl ?>" alt="<?= e($book->getTitle()) ?>" loading="lazy"
                                 onerror="this.src='<?= $fallback ?>';">

                            <?php if ($isEbook): ?>
                                <span class="bl-card-badge ebook"><i class="fas fa-tablet-alt me-1"></i>E-Book</span>
                            <?php elseif ($book->isAvailable()): ?>
                                <span class="bl-card-badge available"><i class="fas fa-check me-1"></i>Available</span>
                            <?php else: ?>
                                <span class="bl-card-badge borrowed" style="background:#ef4444;"><i class="fas fa-exclamation-circle me-1"></i>Out of Stock</span>
                            <?php endif; ?>

                            <div class="bl-card-overlay">
                                <a href="book-details.php?id=<?= e($book->getId()) ?>" class="btn-details">
                                    <i class="fas fa-arrow-right me-1"></i> View Details
                                </a>
                                <?php if ($book->isAvailable()): ?>
                                    <?php if (!Auth::check() || !$library->isCurrentlyBorrowing(Auth::id(), $book->getId())): ?>
                                    <button class="btn-borrow" onclick="quickBorrow('<?= e($book->getId()) ?>')">
                                        <i class="fas fa-plus me-1"></i> Borrow Now
                                    </button>
                                    <?php endif; ?>
                                <?php endif; ?>
                                
                                <button class="btn-cart" onclick="addToCart('<?= e($book->getId()) ?>')" style="<?= !$book->isAvailable() ? 'background: #524f7d; border-color: #524f7d; color: #fff;' : '' ?>">
                                    <i class="fas <?= $book->isAvailable() ? 'fa-shopping-cart' : 'fa-calendar-check' ?> me-1"></i> 
                                    <?= $book->isAvailable() ? 'Add to Cart' : 'Pre-order' ?>
                                </button>
                            </div>
                        </div>

                        <div class="bl-card-body">
                            <div class="bl-card-cat"><?= e($book->getCategory()) ?></div>
                            <div class="bl-card-title" title="<?= e($book->getTitle()) ?>"><?= e($book->getTitle()) ?></div>
                            <div class="bl-card-author" style="font-size: 12px; color: var(--bookhouse-text-muted); margin-bottom: 4px;">
                                by <strong style="color:var(--bookhouse-text);"><?= e($book->getAuthor()) ?></strong> · <?= $book->getYear() ?>
                            </div>
                            <div class="bl-card-price" style="font-size: 13px; margin-top: 6px;">
                                <div style="color: var(--bookhouse-orange); font-weight: 800;">Buy: <?= number_format($book->getPrice()) ?> Ks</div>
                                <div style="color: var(--bookhouse-text-muted); font-size: 11px; font-weight: 600;">Borrow: <?= number_format($book->getBorrowPrice()) ?> Ks</div>
                            </div>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>

            <!-- ── List View ── -->
            <?php if ($viewMode === 'list'): ?>
            <div class="py-2">
                <?php foreach ($books as $book):
                    $coverUrl = getBookCoverUrl($book, $book->getTitle(), $book->getAuthor());
                    $fallback = getDummyBookCover($book->getTitle(), $book->getAuthor(), 200, 300);
                    $isEbook = $book instanceof \App\EBook;
                ?>
                <div class="bl-list-item">
                    <div class="bl-list-cover">
                        <img src="<?= $coverUrl ?>" alt="<?= e($book->getTitle()) ?>" loading="lazy"
                             onerror="this.src='<?= $fallback ?>';">
                    </div>
                    <div class="bl-list-info">
                        <div class="cat"><?= e($book->getCategory()) ?></div>
                        <h5><?= e($book->getTitle()) ?></h5>
                        <div class="meta">by <strong><?= e($book->getAuthor()) ?></strong> · <?= $book->getYear() ?></div>
                        <div class="bl-list-price" style="font-size: 13px; margin-top: 4px;">
                            <span style="color: var(--bookhouse-orange); font-weight: 800;">Buy: <?= number_format($book->getPrice()) ?> Ks</span>
                            <span style="margin: 0 8px; color: #ddd;">|</span>
                            <span style="color: var(--bookhouse-text-muted); font-weight: 600;">Borrow: <?= number_format($book->getBorrowPrice()) ?> Ks</span>
                        </div>
                    </div>
                    <div class="bl-list-status">
                        <?php if ($book->isAvailable()): ?>
                            <span class="dot green"></span>
                            <span style="color:#10b981;">Available</span>
                        <?php else: ?>
                            <span class="dot red" style="background:#ef4444;"></span>
                            <span style="color:#ef4444;">Out of Stock</span>
                        <?php endif; ?>
                    </div>
                    <div class="bl-list-actions">
                        <a href="book-details.php?id=<?= e($book->getId()) ?>" class="bl-btn-outline">Details</a>
                        <?php if ($book->isAvailable()): ?>
                            <?php if (!Auth::check() || !$library->isCurrentlyBorrowing(Auth::id(), $book->getId())): ?>
                            <button class="bl-btn-fill" onclick="quickBorrow('<?= e($book->getId()) ?>')">Borrow</button>
                            <?php endif; ?>
                        <?php endif; ?>
                        <button class="<?= $book->isAvailable() ? 'bl-btn-outline' : 'bl-btn-fill' ?>" onclick="addToCart('<?= e($book->getId()) ?>')" style="<?= !$book->isAvailable() ? 'background: #524f7d; border-color: #524f7d;' : '' ?>">
                            <i class="fas <?= $book->isAvailable() ? 'fa-shopping-cart' : 'fa-calendar-check' ?> me-1"></i> <?= $book->isAvailable() ? 'Cart' : 'Pre-order' ?>
                        </button>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>

        <?php else: ?>
            <!-- Empty State -->
            <div class="bl-empty">
                <div class="bl-empty-icon"><i class="fas fa-book-open"></i></div>
                <h3>No Books Found</h3>
                <p>
                    <?php if ($search): ?>
                        No books match "<strong><?= e($search) ?></strong>". Try different keywords or browse categories.
                    <?php else: ?>
                        This section appears empty. Check back later or explore other categories!
                    <?php endif; ?>
                </p>
                <div class="d-flex justify-content-center gap-3 flex-wrap">
                    <a href="book-list.php" class="bl-btn-outline" style="padding: 12px 28px; border-radius: 14px;">
                        <i class="fas fa-rotate-right me-1"></i> Reset Filters
                    </a>
                    <a href="index.php" class="bl-btn-fill" style="padding: 12px 28px; border-radius: 14px; text-decoration: none;">
                        <i class="fas fa-house me-1"></i> Home
                    </a>
                </div>
            </div>
        <?php endif; ?>

        <!-- ═══════  PAGINATION  ═══════ -->
        <?php if ($totalPages > 1): ?>
        <div class="bl-pagination">
            <?php if ($page > 2): ?>
                <a href="<?= buildUrl(['page' => 1]) ?>"><i class="fas fa-angles-left"></i></a>
            <?php endif; ?>
            <?php if ($hasPrevPage): ?>
                <a href="<?= buildUrl(['page' => $page - 1]) ?>"><i class="fas fa-chevron-left"></i></a>
            <?php endif; ?>

            <?php
            $startPage = max(1, $page - 2);
            $endPage = min($totalPages, $page + 2);
            if ($page <= 3) $endPage = min($totalPages, 5);
            if ($page > $totalPages - 3) $startPage = max(1, $totalPages - 4);
            for ($i = $startPage; $i <= $endPage; $i++): ?>
                <?php if ($i === $page): ?>
                    <span class="active"><?= $i ?></span>
                <?php else: ?>
                    <a href="<?= buildUrl(['page' => $i]) ?>"><?= $i ?></a>
                <?php endif; ?>
            <?php endfor; ?>

            <?php if ($hasNextPage): ?>
                <a href="<?= buildUrl(['page' => $page + 1]) ?>"><i class="fas fa-chevron-right"></i></a>
            <?php endif; ?>
            <?php if ($page < $totalPages - 1): ?>
                <a href="<?= buildUrl(['page' => $totalPages]) ?>"><i class="fas fa-angles-right"></i></a>
            <?php endif; ?>
        </div>
        <?php endif; ?>
    </div>
</div>

<!-- Quick Borrow Modal -->
<div class="modal fade bl-modal" id="quickBorrowModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content text-center shadow-lg">
            <div class="modal-header text-white" style="background: linear-gradient(135deg, var(--bookhouse-orange), #c2664e);">
                <div class="d-flex align-items-center gap-3">
                    <div style="width:44px;height:44px;background:rgba(255,255,255,0.2);border-radius:50%;display:flex;align-items:center;justify-content:center;">
                        <i class="fas fa-bookmark fs-5"></i>
                    </div>
                    <div class="text-start">
                        <h5 class="modal-title fw-800 mb-0">Borrow Confirmation</h5>
                        <p class="mb-0 small opacity-75">You're one step away from your next read.</p>
                    </div>
                </div>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body text-start">
                <!-- Plan Selector -->
                <?php if (Auth::check()): ?>
                <?php
                    $currentTier = $msRules['tier'] ?? 'bronze';
                    $isMemberPlan = ($currentTier !== 'bronze');
                    $freeLimitVal = (int)getSetting('borrow_limit', 3);
                ?>
                <div class="d-flex gap-3 mb-3" id="blPlanSelector">
                    <!-- Free Plan -->
                    <div class="plan-option flex-fill text-center p-3 rounded-4 <?= !$isMemberPlan ? 'active' : '' ?>" data-plan="free" onclick="blSelectPlan(this)" style="cursor:pointer;position:relative;">
                        <span class="plan-badge" style="<?= !$isMemberPlan ? '' : 'display:none;' ?>">Selected</span>
                        <div class="plan-check"><i class="fas fa-check"></i></div>
                        <div style="width:36px;height:36px;border-radius:50%;background:rgba(107,114,128,0.1);display:inline-flex;align-items:center;justify-content:center;margin-bottom:8px;">
                            <i class="fas fa-book" style="color:#6b7280;"></i>
                        </div>
                        <div class="fw-800" style="font-size:14px;">Free</div>
                        <div class="small text-muted mb-1">Pay per borrow</div>
                        <div class="fw-800" style="color:#d48b71;font-size:16px;">Fee Applies</div>
                        <div class="smallest text-muted mt-1"><i class="fas fa-layer-group me-1"></i>Limit: <?= $freeLimitVal ?></div>
                    </div>
                    <!-- Plan -->
                    <div class="plan-option flex-fill text-center p-3 rounded-4 <?= $isMemberPlan ? 'active' : '' ?>" data-plan="plan" onclick="blSelectPlan(this)" style="cursor:pointer;position:relative;">
                        <span class="plan-badge" style="<?= $isMemberPlan ? '' : 'display:none;' ?>">Selected</span>
                        <div class="plan-check"><i class="fas fa-check"></i></div>
                        <div style="width:36px;height:36px;border-radius:50%;background:rgba(212,139,113,0.12);display:inline-flex;align-items:center;justify-content:center;margin-bottom:8px;">
                            <i class="fas fa-crown" style="color:#d48b71;"></i>
                        </div>
                        <div class="fw-800" style="font-size:14px;"><?= ucfirst($currentTier) ?></div>
                        <div class="small text-muted mb-1">Membership card</div>
                        <div class="fw-800" style="color:#10b981;font-size:16px;">FREE</div>
                        <div class="smallest text-muted mt-1"><i class="fas fa-layer-group me-1"></i>Limit: <?= $borrowLimit ?></div>
                    </div>
                </div>
                <input type="hidden" id="blBorrowPlan" value="<?= $isMemberPlan ? 'plan' : 'free' ?>">
                <style>
                    .plan-option{border:2px solid rgba(0,0,0,0.08);background:#fff;transition:all 0.3s ease;border-radius:16px!important;}
                    .plan-option:hover{border-color:#d48b71;background:rgba(212,139,113,0.02);}
                    .plan-option.active{border-color:#d48b71;background:rgba(212,139,113,0.04);box-shadow:0 4px 20px rgba(212,139,113,0.15);}
                    .plan-option .plan-badge{position:absolute;top:-10px;left:50%;transform:translateX(-50%);background:linear-gradient(135deg,#d48b71,#c2664e);color:#fff;font-size:10px;font-weight:800;padding:3px 14px;border-radius:999px;text-transform:uppercase;letter-spacing:.5px;white-space:nowrap;z-index:2;}
                    .plan-option .plan-check{position:absolute;top:10px;right:10px;width:22px;height:22px;border-radius:50%;border:2px solid rgba(0,0,0,0.1);display:flex;align-items:center;justify-content:center;font-size:10px;color:transparent;transition:all 0.25s;}
                    .plan-option.active .plan-check{background:#d48b71;border-color:#d48b71;color:#fff;}
                </style>
                <?php endif; ?>

                <div class="p-3 rounded-4 border mb-3" style="background: rgba(224,122,95,0.04);">
                    <div class="d-flex gap-3">
                        <i class="fas fa-info-circle mt-1" style="color: var(--bookhouse-orange);"></i>
                        <div>
                            <h6 class="fw-800 mb-1" style="font-size:14px;">Borrowing Policy</h6>
                            <p class="mb-0 small" style="color: var(--bookhouse-text-muted);">Return within 14 days. Late returns may incur a small fee.</p>
                        </div>
                    </div>
                </div>

                <!-- Borrowing Details Section -->
                <?php if (Auth::check()): ?>
                <div class="p-3 rounded-4 border mb-4" style="background: #ffffff;">
                    <h6 class="fw-800 mb-3" style="font-size:14px; border-bottom: 1px solid rgba(0,0,0,0.06); padding-bottom: 8px;">Your Borrowing Summary</h6>
                    <ul class="list-unstyled mb-0" style="font-size: 14px; color: var(--bookhouse-text); line-height: 1.8;">
                        <li><i class="fas fa-calendar-day text-success me-2"></i><strong>Duration:</strong> 14 Days</li>
                        <li><i class="fas fa-user-check text-primary me-2"></i><strong>Your Active Books:</strong> <?= $personalActiveUsage ?> / <?= $borrowLimit ?></li>
                        <?php if ($personalActiveUsage >= $borrowLimit && $groupPoolUsage < $groupLimit): ?>
                            <li class="text-danger small mt-1 animate__animated animate__headShake">
                                <i class="fas fa-info-circle me-1"></i> You have reached your individual limit.
                            </li>
                        <?php endif; ?>
                    </ul>
                </div>
                <?php endif; ?>

                <?php if (Auth::check() && ($personalActiveUsage >= $borrowLimit || $groupPoolUsage >= $groupLimit)): ?>
                    <p class="fw-700 text-center mb-0 text-danger" style="font-size: 16px;"><i class="fas fa-times-circle me-1"></i>Limit Reached</p>
                <?php else: ?>
                    <p class="fw-700 text-center mb-0" style="color: var(--bookhouse-text); font-size: 16px;">Ready to proceed?</p>
                <?php endif; ?>
            </div>
            <div class="modal-footer flex-column gap-2 text-center">
                <button type="button" class="btn w-100 py-3 fw-800 text-white rounded-pill" id="confirmBorrow" style="background: var(--bookhouse-orange); font-size: 15px;" <?= (Auth::check() && ($personalActiveUsage >= $borrowLimit || $groupPoolUsage >= $groupLimit)) ? 'disabled' : '' ?>>
                    <i class="fas fa-check me-2"></i>Confirm & Borrow
                </button>
                <button type="button" class="btn btn-link text-muted fw-600 text-decoration-none" data-bs-dismiss="modal"><?= (Auth::check() && ($personalActiveUsage >= $borrowLimit || $groupPoolUsage >= $groupLimit)) ? 'Close' : 'Maybe later' ?></button>
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
        showLoginAlert('Please login to borrow this book.');
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
        
        const planVal = document.getElementById('blBorrowPlan')?.value || 'plan';
        [['id', currentBookId], ['action', 'borrow'], ['borrow_plan', planVal]].forEach(([name, value]) => {
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

function blSelectPlan(el) {
    document.querySelectorAll('#blPlanSelector .plan-option').forEach(c => c.classList.remove('active'));
    el.classList.add('active');
    document.getElementById('blBorrowPlan').value = el.dataset.plan;
}

document.addEventListener('DOMContentLoaded', () => {
    // Auto-submit on filter change
    document.querySelectorAll('select[name="category"], select[name="availability"], select[name="sort"]')
        .forEach(s => s.addEventListener('change', () => document.getElementById('filterForm').submit()));

    // Sticky shadow
    const bar = document.getElementById('filterBar');
    if (bar) {
        window.addEventListener('scroll', () => bar.classList.toggle('scrolled', window.scrollY > 200));
    }

    // Fade-in cards
    const cards = document.querySelectorAll('.bl-card, .bl-list-item');
    const obs = new IntersectionObserver((entries) => {
        entries.forEach((entry, i) => {
            if (entry.isIntersecting) {
                entry.target.style.transition = `opacity 0.5s ${i * 0.04}s ease, transform 0.5s ${i * 0.04}s ease`;
                entry.target.style.opacity = '1';
                entry.target.style.transform = 'translateY(0)';
                obs.unobserve(entry.target);
            }
        });
    }, { threshold: 0.05 });
    cards.forEach(c => { c.style.opacity = '0'; c.style.transform = 'translateY(20px)'; obs.observe(c); });
});
</script>

<?php include 'views/footer.php'; ?>