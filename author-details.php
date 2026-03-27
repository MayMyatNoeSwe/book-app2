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
$authorName = urldecode($authorName);

// Get author details
$authorDetails = $library->getAuthorDetails($authorName);
if (!$authorDetails) {
    header('Location: author-list.php');
    exit;
}

// Get author's books
$sortBy = $_GET['sort'] ?? 'year';
$sortOrder = $_GET['order'] ?? 'desc';
$page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$limit = 12;
$offset = ($page - 1) * $limit;

$authorBooks = $library->getBooksByAuthor($authorName, $limit, $offset, $sortBy, $sortOrder);
$popularBooks = $library->getAuthorPopularBooks($authorName, 6);

$totalBooks = $authorDetails['book_count'];
$totalPages = ceil($totalBooks / $limit);
$hasNextPage = $page < $totalPages;
$hasPrevPage = $page > 1;

// User Borrowing Stats for the Modal
$hasBorrowedBefore = false;
$unreturnedBooksCount = 0;
if (Auth::check()) {
    $userId = Auth::id();
    $pdo = $library->getPdo();
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM borrowing_history WHERE user_id = ?");
    $stmt->execute([$userId]);
    $hasBorrowedBefore = $stmt->fetchColumn() > 0;
    
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM borrowing_history WHERE user_id = ? AND returned_at IS NULL AND `status` IN ('pending','approved')");
    $stmt->execute([$userId]);
    $unreturnedBooksCount = $stmt->fetchColumn();
}

$pageTitle = $authorName . " - Author Details";

// Author avatar
$authorPhoto = $authorDetails['author_photo'] ?? null;
$avatarUrl = getAuthorAvatarUrl($authorName, 300);
if ($authorPhoto && file_exists(__DIR__ . '/public/uploads/authors/' . $authorPhoto)) {
    $avatarSrc = baseUrl() . '/public/uploads/authors/' . e($authorPhoto);
} else {
    $avatarSrc = $avatarUrl;
}

include 'views/header.php';
?>

<style>
/* ─── Author Details Premium ─── */
.ad-hero {
    position: relative; overflow: hidden;
    padding: 50px 0 60px;
    background:
        radial-gradient(ellipse at 10% 65%, rgba(224,122,95,0.12) 0%, transparent 55%),
        radial-gradient(ellipse at 85% 20%, rgba(129,178,154,0.09) 0%, transparent 50%),
        radial-gradient(ellipse at 50% 90%, rgba(242,204,143,0.06) 0%, transparent 40%),
        var(--bookhouse-bg, #FFF3F0);
}
[data-bs-theme="dark"] .ad-hero {
    background:
        radial-gradient(ellipse at 10% 65%, rgba(224,122,95,0.15) 0%, transparent 55%),
        radial-gradient(ellipse at 85% 20%, rgba(129,178,154,0.12) 0%, transparent 50%),
        #0f172a;
}
.ad-hero::before {
    content: ''; position: absolute; inset: 0;
    background-image:
        linear-gradient(rgba(0,0,0,0.02) 1px, transparent 1px),
        linear-gradient(90deg, rgba(0,0,0,0.02) 1px, transparent 1px);
    background-size: 60px 60px; pointer-events: none;
}
[data-bs-theme="dark"] .ad-hero::before {
    background-image:
        linear-gradient(rgba(255,255,255,0.025) 1px, transparent 1px),
        linear-gradient(90deg, rgba(255,255,255,0.025) 1px, transparent 1px);
}

/* Avatar */
.ad-avatar-wrap {
    position: relative; width: 200px; flex-shrink: 0;
}
.ad-avatar {
    width: 200px; height: 200px; border-radius: 50%;
    overflow: hidden;
    border: 5px solid rgba(255,255,255,0.8);
    box-shadow: 0 20px 50px rgba(61,64,91,0.18);
}
[data-bs-theme="dark"] .ad-avatar { border-color: rgba(30,41,59,0.8); }
.ad-avatar img { width: 100%; height: 100%; object-fit: cover; }
.ad-avatar-badge {
    position: absolute; bottom: 8px; right: 8px;
    padding: 6px 14px; border-radius: 999px;
    font-size: 11px; font-weight: 800;
    background: var(--bookhouse-orange, #E07A5F); color: #fff;
    box-shadow: 0 4px 12px rgba(224,122,95,0.35);
}

/* Info */
.ad-cat-pill {
    display: inline-block; font-size: 11px; font-weight: 800;
    letter-spacing: 1.5px; text-transform: uppercase;
    color: var(--bookhouse-orange); margin-bottom: 10px;
    background: rgba(224,122,95,0.08);
    padding: 5px 14px; border-radius: 999px;
}
.ad-name {
    font-family: 'Playfair Display', serif;
    font-size: clamp(1.8rem, 4.5vw, 2.8rem);
    font-weight: 800; line-height: 1.15;
    color: var(--bookhouse-text); margin-bottom: 6px;
}
.ad-subtitle {
    font-size: 15px; color: var(--bookhouse-text-muted); margin-bottom: 20px;
}

/* Rating inline */
.ad-rating {
    display: flex; align-items: center; gap: 12px; margin-bottom: 24px;
}
.ad-rating .stars { display: flex; gap: 3px; }
.ad-rating .stars i { color: #f59e0b; font-size: 16px; }
.ad-rating .stars i.empty { color: #d1d5db; }
.ad-rating .score { font-weight: 800; font-size: 18px; color: var(--bookhouse-text); }
.ad-rating .count { font-size: 13px; color: var(--bookhouse-text-muted); }

/* Stats row */
.ad-stats {
    display: flex; flex-wrap: wrap; gap: 12px; margin-bottom: 24px;
}
.ad-stat {
    display: flex; align-items: center; gap: 10px;
    background: rgba(255,255,255,0.7);
    backdrop-filter: blur(8px);
    border: 1px solid rgba(0,0,0,0.06);
    border-radius: 14px; padding: 12px 18px;
    transition: transform 0.2s;
}
.ad-stat:hover { transform: translateY(-2px); }
[data-bs-theme="dark"] .ad-stat {
    background: rgba(30,41,59,0.7); border-color: rgba(255,255,255,0.08);
}
.ad-stat-icon {
    width: 36px; height: 36px; display: flex; align-items: center; justify-content: center;
    border-radius: 10px; font-size: 14px;
}
.ad-stat-icon.orange { background: rgba(224,122,95,0.12); color: #E07A5F; }
.ad-stat-icon.gold   { background: rgba(242,204,143,0.15); color: #d4a646; }
.ad-stat-icon.mint   { background: rgba(129,178,154,0.12); color: #81B29A; }
.ad-stat-icon.blue   { background: rgba(59,130,246,0.1); color: #3b82f6; }
.ad-stat .val { font-weight: 800; font-size: 16px; color: var(--bookhouse-text); }
.ad-stat .lbl { font-size: 11px; text-transform: uppercase; letter-spacing: 0.6px; color: var(--bookhouse-text-muted); font-weight: 600; }

/* Actions */
.ad-actions { display: flex; flex-wrap: wrap; gap: 10px; }
.ad-btn {
    padding: 11px 24px; border-radius: 14px;
    font-size: 14px; font-weight: 700; border: none;
    cursor: pointer; transition: all 0.25s;
    text-decoration: none; display: inline-flex;
    align-items: center; gap: 8px;
}
.ad-btn-primary {
    background: var(--bookhouse-orange); color: #fff;
    box-shadow: 0 8px 20px rgba(224,122,95,0.3);
}
.ad-btn-primary:hover { filter: brightness(1.1); transform: translateY(-2px); color: #fff; }
.ad-btn-outline {
    background: transparent; border: 2px solid rgba(0,0,0,0.1) !important;
    color: var(--bookhouse-text);
}
[data-bs-theme="dark"] .ad-btn-outline { border-color: rgba(255,255,255,0.12) !important; }
.ad-btn-outline:hover { border-color: var(--bookhouse-orange) !important; color: var(--bookhouse-orange); }

/* Share */
.ad-share { display: flex; gap: 8px; margin-top: 16px; }
.ad-share button {
    width: 38px; height: 38px; border-radius: 10px;
    border: 1px solid rgba(0,0,0,0.08); background: rgba(0,0,0,0.02);
    color: var(--bookhouse-text-muted); cursor: pointer;
    transition: all 0.2s; font-size: 14px;
    display: flex; align-items: center; justify-content: center;
}
[data-bs-theme="dark"] .ad-share button { background: rgba(255,255,255,0.05); border-color: rgba(255,255,255,0.08); }
.ad-share button:hover { background: var(--bookhouse-orange); color: #fff; border-color: var(--bookhouse-orange); }

/* ─── Sections ─── */
.ad-section { padding: 50px 0; }
.ad-section-title {
    font-family: 'Manrope', sans-serif;
    font-size: 22px; font-weight: 800;
    color: var(--bookhouse-text); margin-bottom: 8px;
    display: flex; align-items: center; gap: 10px;
}
.ad-section-title i { color: var(--bookhouse-orange); }
.ad-section-sub { font-size: 14px; color: var(--bookhouse-text-muted); margin-bottom: 28px; }

/* Bio */
.ad-bio {
    font-size: 15px; line-height: 1.8;
    color: var(--bookhouse-text-muted);
    max-width: 700px;
}
.ad-genre-tag {
    display: inline-block; padding: 5px 14px;
    border-radius: 999px; font-size: 12px; font-weight: 700;
    background: rgba(224,122,95,0.06); color: var(--bookhouse-orange);
    border: 1px solid rgba(224,122,95,0.12);
    margin: 3px 3px 3px 0; text-decoration: none;
    transition: all 0.2s;
}
.ad-genre-tag:hover { background: var(--bookhouse-orange); color: #fff; }

/* Detail cards */
.ad-detail {
    background: #fff; border-radius: 16px;
    border: 1px solid rgba(0,0,0,0.06);
    padding: 20px; transition: transform 0.2s;
}
.ad-detail:hover { transform: translateY(-2px); }
[data-bs-theme="dark"] .ad-detail { background: #1e293b; border-color: rgba(255,255,255,0.06); }
.ad-detail .label { font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.8px; color: var(--bookhouse-text-muted); margin-bottom: 6px; }
.ad-detail .value { font-size: 15px; font-weight: 700; color: var(--bookhouse-text); word-break: break-word; }

/* ─── Book Cards (inline) ─── */
.ad-book-card {
    background: #fff; border-radius: 20px;
    border: 1px solid rgba(0,0,0,0.06);
    overflow: hidden; height: 100%;
    display: flex; flex-direction: column;
    transition: transform 0.35s ease, box-shadow 0.35s ease;
}
.ad-book-card:hover {
    transform: translateY(-8px);
    box-shadow: 0 24px 56px rgba(61,64,91,0.14);
}
[data-bs-theme="dark"] .ad-book-card { background: #1e293b; border-color: rgba(255,255,255,0.06); }
.ad-book-cover {
    position: relative; aspect-ratio: 2/3; overflow: hidden;
    background: linear-gradient(135deg, #f0e6e0, #e8ddd5);
}
[data-bs-theme="dark"] .ad-book-cover { background: linear-gradient(135deg, #1a2332, #253141); }
.ad-book-cover img { width: 100%; height: 100%; object-fit: cover; transition: transform 0.5s ease; }
.ad-book-card:hover .ad-book-cover img { transform: scale(1.06); }
.ad-book-overlay {
    position: absolute; inset: 0;
    background: linear-gradient(to top, rgba(0,0,0,0.7) 0%, transparent 50%);
    display: flex; flex-direction: column; justify-content: flex-end;
    padding: 14px; opacity: 0; transition: opacity 0.3s;
}
.ad-book-card:hover .ad-book-overlay { opacity: 1; }
.ad-book-overlay a, .ad-book-overlay button {
    display: block; text-align: center; padding: 9px; border-radius: 10px;
    font-weight: 700; font-size: 12px; text-decoration: none;
    border: none; cursor: pointer; width: 100%; transition: all 0.2s;
}
.ad-book-overlay .ad-ol-details { background: rgba(255,255,255,0.92); color: #3D405B; }
.ad-book-overlay .ad-ol-borrow-btn { 
    background: var(--bookhouse-orange); 
    color: #fff; 
    margin-top: 6px; 
}
.ad-book-overlay .ad-ol-borrow { 
    background: transparent; 
    color: #fff; 
    border: 1px solid rgba(255,255,255,0.6) !important;
    margin-top: 6px; 
}
.ad-book-overlay .ad-ol-borrow:hover {
    background: rgba(255,255,255,0.1);
    border-color: #fff !important;
}
.ad-book-body {
    padding: 14px 14px 16px; flex: 1;
    display: flex; flex-direction: column;
}
.ad-book-body .cat {
    font-size: 10px; font-weight: 800; letter-spacing: 1px;
    text-transform: uppercase; color: var(--bookhouse-orange); margin-bottom: 4px;
}
.ad-book-body .title {
    font-weight: 800; font-size: 14px; color: var(--bookhouse-text);
    display: -webkit-box; -webkit-line-clamp: 2; line-clamp: 2;
    -webkit-box-orient: vertical; overflow: hidden; line-height: 1.35; margin-bottom: 0;
}
.ad-book-body .year-row {
    margin-top: auto; padding-top: 8px;
    border-top: 1px solid rgba(0,0,0,0.04);
    font-size: 12px; font-weight: 700;
    color: var(--bookhouse-orange);
    background: rgba(224,122,95,0.06);
    display: inline-block; padding: 2px 8px; border-radius: 6px;
    align-self: flex-start; margin-top: 8px;
}

/* Sort pill */
.ad-sort-pill {
    appearance: none; border: 1px solid rgba(0,0,0,0.08);
    background: rgba(0,0,0,0.02); border-radius: 12px;
    padding: 9px 34px 9px 14px; font-size: 13px; font-weight: 600;
    color: var(--bookhouse-text); cursor: pointer;
    background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='12' viewBox='0 0 12 12'%3E%3Cpath fill='%23636e72' d='M6 8L1 3h10z'/%3E%3C/svg%3E");
    background-repeat: no-repeat; background-position: right 12px center;
}
[data-bs-theme="dark"] .ad-sort-pill {
    background-color: rgba(255,255,255,0.05); border-color: rgba(255,255,255,0.1);
}

/* Pagination */
.ad-pagination {
    padding: 30px 0; display: flex;
    justify-content: center; align-items: center; gap: 6px; flex-wrap: wrap;
}
.ad-pagination a, .ad-pagination span {
    width: 42px; height: 42px; display: inline-flex;
    align-items: center; justify-content: center;
    border-radius: 12px; font-weight: 700; font-size: 14px;
    text-decoration: none; transition: all 0.2s;
    color: var(--bookhouse-text-muted);
    border: 1px solid rgba(0,0,0,0.06);
}
[data-bs-theme="dark"] .ad-pagination a, [data-bs-theme="dark"] .ad-pagination span { border-color: rgba(255,255,255,0.08); }
.ad-pagination a:hover { background: rgba(224,122,95,0.08); color: var(--bookhouse-orange); border-color: rgba(224,122,95,0.2); }
.ad-pagination .active {
    background: var(--bookhouse-orange) !important; color: #fff !important;
    border-color: var(--bookhouse-orange) !important;
    box-shadow: 0 4px 14px rgba(224,122,95,0.35);
}

/* Empty */
.ad-empty { text-align: center; padding: 60px 20px; background: rgba(0,0,0,0.02); border-radius: 20px; }
[data-bs-theme="dark"] .ad-empty { background: rgba(255,255,255,0.02); }

/* Responsive */
@media (max-width: 991px) {
    .ad-hero-row { flex-direction: column !important; align-items: center !important; text-align: center; }
    .ad-avatar-wrap { margin-bottom: 24px; }
    .ad-stats { justify-content: center; }
    .ad-actions { justify-content: center; }
    .ad-share { justify-content: center; }
}
@media (max-width: 576px) {
    .ad-avatar-wrap { width: 160px; }
    .ad-avatar { width: 160px; height: 160px; }
    .ad-stats { flex-direction: column; }
}
</style>

<!-- ═══════  HERO  ═══════ -->
<section class="ad-hero">
    <div class="container position-relative" style="z-index:2;">
        <!-- Breadcrumb -->
        <nav aria-label="breadcrumb" class="mb-4">
            <ol class="breadcrumb" style="font-size:13px;">
                <li class="breadcrumb-item"><a href="index.php" class="text-decoration-none" style="color:var(--bookhouse-text-muted);">Home</a></li>
                <li class="breadcrumb-item"><a href="author-list.php" class="text-decoration-none" style="color:var(--bookhouse-text-muted);">Authors</a></li>
                <li class="breadcrumb-item active fw-bold" style="color:var(--bookhouse-orange);"><?= e($authorName) ?></li>
            </ol>
        </nav>

        <!-- Main Layout -->
        <div class="d-flex gap-5 ad-hero-row">
            <!-- Avatar -->
            <div class="ad-avatar-wrap">
                <div class="ad-avatar">
                    <img src="<?= $avatarSrc ?>" alt="<?= e($authorName) ?>">
                </div>
                <span class="ad-avatar-badge"><i class="fas fa-pen-fancy me-1"></i>Author</span>
            </div>

            <!-- Info -->
            <div style="min-width:0; flex:1;">
                <span class="ad-cat-pill">Author Profile</span>
                <h1 class="ad-name"><?= e($authorName) ?></h1>
                <div class="ad-subtitle">
                    <?php if ($authorDetails['first_book_year'] && $authorDetails['latest_book_year']): ?>
                        Active <?= $authorDetails['first_book_year'] ?> – <?= $authorDetails['latest_book_year'] ?>
                    <?php else: ?>
                        Author
                    <?php endif; ?>
                </div>

                <!-- Rating -->
                <div class="ad-rating">
                    <div class="stars">
                        <?php for ($i = 1; $i <= 5; $i++): ?>
                            <i class="fas fa-star <?= $i <= round($authorDetails['avg_rating']) ? '' : 'empty' ?>"></i>
                        <?php endfor; ?>
                    </div>
                    <span class="score"><?= number_format($authorDetails['avg_rating'], 1) ?></span>
                    <span class="count">(<?= $authorDetails['review_count'] ?> <?= $authorDetails['review_count'] == 1 ? 'review' : 'reviews' ?>)</span>
                </div>

                <!-- Stats -->
                <div class="ad-stats">
                    <div class="ad-stat">
                        <div class="ad-stat-icon orange"><i class="fas fa-book"></i></div>
                        <div><div class="val"><?= $authorDetails['book_count'] ?></div><div class="lbl">Books</div></div>
                    </div>
                    <div class="ad-stat">
                        <div class="ad-stat-icon gold"><i class="fas fa-star"></i></div>
                        <div><div class="val"><?= number_format($authorDetails['avg_rating'], 1) ?></div><div class="lbl">Rating</div></div>
                    </div>
                    <div class="ad-stat">
                        <div class="ad-stat-icon mint"><i class="fas fa-arrow-trend-up"></i></div>
                        <div><div class="val"><?= $authorDetails['total_borrows'] ?></div><div class="lbl">Borrows</div></div>
                    </div>
                    <div class="ad-stat">
                        <div class="ad-stat-icon blue"><i class="fas fa-comments"></i></div>
                        <div><div class="val"><?= $authorDetails['review_count'] ?></div><div class="lbl">Reviews</div></div>
                    </div>
                </div>

                <!-- Actions -->
                <div class="ad-actions">
                    <a href="book-list.php?search=<?= urlencode($authorName) ?>" class="ad-btn ad-btn-primary">
                        <i class="fas fa-book"></i> View All Books
                    </a>
                </div>

                <!-- Share -->
                <div class="ad-share">
                    <button onclick="shareAuthor('facebook')" title="Facebook"><i class="fab fa-facebook-f"></i></button>
                    <button onclick="shareAuthor('twitter')" title="Twitter"><i class="fab fa-twitter"></i></button>
                    <button onclick="shareAuthor('whatsapp')" title="WhatsApp"><i class="fab fa-whatsapp"></i></button>
                    <button onclick="copyAuthorLink()" title="Copy Link"><i class="fas fa-link"></i></button>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- ═══════  BIO & DETAILS  ═══════ -->
<div class="ad-section" style="border-top: 1px solid rgba(0,0,0,0.04);">
    <div class="container">
        <div class="row g-5">
            <div class="col-lg-8">
                <h3 class="ad-section-title"><i class="fas fa-user"></i> About the Author</h3>
                <div class="ad-bio">
                    <?php if (!empty($authorDetails['author_bio'])): ?>
                        <p><?= nl2br(e($authorDetails['author_bio'])) ?></p>
                    <?php else: ?>
                        <p><?= e($authorName) ?> is a talented author who has contributed <?= $authorDetails['book_count'] ?> 
                        <?= $authorDetails['book_count'] === 1 ? 'book' : 'books' ?> to our collection.
                        <?php if ($authorDetails['first_book_year']): ?>
                            Their literary journey began in <?= $authorDetails['first_book_year'] ?><?= $authorDetails['latest_book_year'] !== $authorDetails['first_book_year'] ? ' and continues to ' . $authorDetails['latest_book_year'] : '' ?>.
                        <?php endif; ?>
                        <?php if ($authorDetails['avg_rating'] > 0): ?>
                            With an average rating of <?= number_format($authorDetails['avg_rating'], 1) ?> stars, their work has been well-received by readers.
                        <?php endif; ?></p>
                    <?php endif; ?>
                </div>

                <!-- Genre tags -->
                <?php if (!empty($authorDetails['categories_array'])): ?>
                <div class="mt-3">
                    <?php foreach ($authorDetails['categories_array'] as $cat): ?>
                        <a href="book-list.php?category=<?= urlencode($cat) ?>&search=<?= urlencode($authorName) ?>" class="ad-genre-tag"><?= e($cat) ?></a>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>
            </div>

            <!-- Detail cards sidebar -->
            <div class="col-lg-4">
                <div class="d-flex flex-column gap-12" style="gap:12px;">
                    <div class="ad-detail">
                        <div class="label">Career Span</div>
                        <div class="value">
                            <?php if ($authorDetails['first_book_year'] && $authorDetails['latest_book_year']): ?>
                                <?= $authorDetails['first_book_year'] ?> – <?= $authorDetails['latest_book_year'] ?>
                                (<?= $authorDetails['latest_book_year'] - $authorDetails['first_book_year'] + 1 ?> years)
                            <?php else: ?>
                                Not available
                            <?php endif; ?>
                        </div>
                    </div>
                    <div class="ad-detail">
                        <div class="label">First Published</div>
                        <div class="value">
                            <?php if ($authorDetails['first_book_title']): ?>
                                "<?= e($authorDetails['first_book_title']) ?>" (<?= $authorDetails['first_book_year'] ?>)
                            <?php else: ?>
                                Not available
                            <?php endif; ?>
                        </div>
                    </div>
                    <div class="ad-detail">
                        <div class="label">Latest Work</div>
                        <div class="value">
                            <?php if ($authorDetails['latest_book_title']): ?>
                                "<?= e($authorDetails['latest_book_title']) ?>" (<?= $authorDetails['latest_book_year'] ?>)
                            <?php else: ?>
                                Not available
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- ═══════  POPULAR BOOKS  ═══════ -->
<?php if (!empty($popularBooks)): ?>
<div class="ad-section" style="background: rgba(0,0,0,0.015); border-top: 1px solid rgba(0,0,0,0.04);">
    <div class="container">
        <h3 class="ad-section-title"><i class="fas fa-fire"></i> Most Popular</h3>
        <p class="ad-section-sub">The most borrowed and highest-rated books by <?= e($authorName) ?></p>

        <div class="row row-cols-2 row-cols-sm-3 row-cols-md-4 row-cols-lg-6 g-3">
            <?php foreach ($popularBooks as $book):
                $coverUrl = getBookCoverUrl($book, $book->getTitle(), $book->getAuthor());
                $fallback = getDummyBookCover($book->getTitle(), $book->getAuthor(), 400, 600);
            ?>
            <div class="col">
                <div class="ad-book-card">
                    <div class="ad-book-cover">
                        <img src="<?= $coverUrl ?>" alt="<?= e($book->getTitle()) ?>" loading="lazy" onerror="this.src='<?= $fallback ?>'">
                        <div class="ad-book-overlay">
                            <a href="book-details.php?id=<?= e($book->getId()) ?>" class="ad-ol-details">View Details</a>
                            <?php if (!($book instanceof \App\EBook) && $book->isAvailable()): ?>
                                <?php if (!Auth::check() || !$library->isCurrentlyBorrowing(Auth::id(), $book->getId())): ?>
                                <button class="ad-ol-borrow-btn" onclick="quickBorrow('<?= e($book->getId()) ?>')">
                                    <i class="fas fa-plus me-1"></i> Borrow
                                </button>
                                <?php endif; ?>
                            <?php endif; ?>
                            <button class="ad-ol-borrow" onclick="addToCart('<?= e($book->getId()) ?>')" style="<?= !$book->isAvailable() ? 'background:#524f7d; border-color:#524f7d!important;' : '' ?>">
                                <i class="fas <?= $book->isAvailable() ? 'fa-shopping-cart' : 'fa-calendar-check' ?> me-1"></i>
                                <?= $book->isAvailable() ? 'Add to Cart' : 'Pre-order' ?>
                            </button>
                        </div>
                    </div>
                    <div class="ad-book-body">
                        <div class="cat"><?= e($book->getCategory()) ?></div>
                        <div class="title"><?= e($book->getTitle()) ?></div>
                        <div class="year-row"><?= $book->getYear() ?></div>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</div>
<?php endif; ?>

<!-- ═══════  ALL BOOKS  ═══════ -->
<div class="ad-section" style="border-top: 1px solid rgba(0,0,0,0.04);">
    <div class="container">
        <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-4">
            <div>
                <h3 class="ad-section-title mb-1">
                    <i class="fas fa-layer-group"></i> All Books
                    <span style="background:rgba(224,122,95,0.1); color:var(--bookhouse-orange); padding:2px 10px; border-radius:999px; font-size:13px;"><?= $totalBooks ?></span>
                </h3>
                <p class="ad-section-sub mb-0">Complete collection by <?= e($authorName) ?></p>
            </div>
            <select class="ad-sort-pill" onchange="changeBooksSort(this.value)">
                <option value="year-desc" <?= $sortBy === 'year' && $sortOrder === 'desc' ? 'selected' : '' ?>>Newest First</option>
                <option value="year-asc" <?= $sortBy === 'year' && $sortOrder === 'asc' ? 'selected' : '' ?>>Oldest First</option>
                <option value="title-asc" <?= $sortBy === 'title' && $sortOrder === 'asc' ? 'selected' : '' ?>>Title A-Z</option>
                <option value="title-desc" <?= $sortBy === 'title' && $sortOrder === 'desc' ? 'selected' : '' ?>>Title Z-A</option>
                <option value="category-asc" <?= $sortBy === 'category' && $sortOrder === 'asc' ? 'selected' : '' ?>>Category</option>
            </select>
        </div>

        <?php if (!empty($authorBooks)): ?>
        <div class="row row-cols-2 row-cols-sm-3 row-cols-md-4 row-cols-lg-5 row-cols-xl-6 g-3">
            <?php foreach ($authorBooks as $book):
                $coverUrl = getBookCoverUrl($book, $book->getTitle(), $book->getAuthor());
                $fallback = getDummyBookCover($book->getTitle(), $book->getAuthor(), 400, 600);
            ?>
            <div class="col">
                <div class="ad-book-card">
                    <div class="ad-book-cover">
                        <img src="<?= $coverUrl ?>" alt="<?= e($book->getTitle()) ?>" loading="lazy" onerror="this.src='<?= $fallback ?>'">
                        <div class="ad-book-overlay">
                            <a href="book-details.php?id=<?= e($book->getId()) ?>" class="ad-ol-details">View Details</a>
                            <?php if (!($book instanceof \App\EBook) && $book->isAvailable()): ?>
                                <?php if (!Auth::check() || !$library->isCurrentlyBorrowing(Auth::id(), $book->getId())): ?>
                                <button class="ad-ol-borrow-btn" onclick="quickBorrow('<?= e($book->getId()) ?>')">
                                    <i class="fas fa-plus me-1"></i> Borrow
                                </button>
                                <?php endif; ?>
                            <?php endif; ?>
                            <button class="ad-ol-borrow" onclick="addToCart('<?= e($book->getId()) ?>')" style="<?= !$book->isAvailable() ? 'background:#524f7d; border-color:#524f7d!important;' : '' ?>">
                                <i class="fas <?= $book->isAvailable() ? 'fa-shopping-cart' : 'fa-calendar-check' ?> me-1"></i>
                                <?= $book->isAvailable() ? 'Add to Cart' : 'Pre-order' ?>
                            </button>
                        </div>
                    </div>
                    <div class="ad-book-body">
                        <div class="cat"><?= e($book->getCategory()) ?></div>
                        <div class="title"><?= e($book->getTitle()) ?></div>
                        <div class="year-row"><?= $book->getYear() ?></div>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>

        <!-- Pagination -->
        <?php if ($totalPages > 1): ?>
        <div class="ad-pagination">
            <?php if ($page > 2): ?>
                <a href="<?= buildAuthorUrl(['page' => 1]) ?>"><i class="fas fa-angles-left"></i></a>
            <?php endif; ?>
            <?php if ($hasPrevPage): ?>
                <a href="<?= buildAuthorUrl(['page' => $page - 1]) ?>"><i class="fas fa-chevron-left"></i></a>
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

        <?php else: ?>
        <div class="ad-empty">
            <div style="width:72px;height:72px;margin:0 auto 20px;background:rgba(224,122,95,0.08);border-radius:50%;display:flex;align-items:center;justify-content:center;">
                <i class="fas fa-book" style="font-size:28px;color:var(--bookhouse-orange);opacity:0.4;"></i>
            </div>
            <h5 style="font-weight:700; color:var(--bookhouse-text);">No books found</h5>
            <p style="color:var(--bookhouse-text-muted); font-size:14px;">This author doesn't have any books in our current collection.</p>
        </div>
        <?php endif; ?>
    </div>
</div>

<?php
function buildAuthorUrl($newParams = []) {
    global $authorName, $sortBy, $sortOrder;
    $params = array_filter([
        'author' => $authorName,
        'sort' => $sortBy,
        'order' => $sortOrder
    ]);
    return 'author-details.php?' . http_build_query(array_merge($params, $newParams));
}
?>

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
                        <li><i class="fas fa-book text-primary me-2"></i><strong>Books:</strong> 1 Book</li>
                        <li><i class="fas fa-user-clock text-warning me-2"></i><strong>Status:</strong> <?= $hasBorrowedBefore ? 'Existing Borrower' : 'First-time Borrower' ?></li>
                        <li>
                            <i class="fas <?= $unreturnedBooksCount > 0 ? 'fa-exclamation-circle text-danger' : 'fa-check-circle text-success' ?> me-2"></i>
                            <strong>Unreturned Books:</strong> <?= $unreturnedBooksCount ?> / 3 
                            <?php if ($unreturnedBooksCount >= 3): ?>
                                <br><span class="text-danger small mt-1 d-block"><i class="fas fa-ban me-1"></i>You have reached the maximum borrow limit. Please return a book first.</span>
                            <?php elseif ($unreturnedBooksCount > 0): ?>
                                <span class="text-danger small">(Please return on time)</span>
                            <?php endif; ?>
                        </li>
                    </ul>
                </div>
                <?php endif; ?>

                <?php if (Auth::check() && $unreturnedBooksCount >= 3): ?>
                    <p class="fw-700 text-center mb-0 text-danger" style="font-size: 16px;"><i class="fas fa-times-circle me-1"></i>Cannot Proceed</p>
                <?php else: ?>
                    <p class="fw-700 text-center mb-0" style="color: var(--bookhouse-text); font-size: 16px;">Ready to proceed?</p>
                <?php endif; ?>
            </div>
            <div class="modal-footer flex-column gap-2 text-center">
                <button type="button" class="btn w-100 py-3 fw-800 text-white rounded-pill" id="confirmBorrow" style="background: var(--bookhouse-orange); font-size: 15px;" <?= (Auth::check() && $unreturnedBooksCount >= 3) ? 'disabled' : '' ?>>
                    <i class="fas fa-check me-2"></i>Confirm & Borrow
                </button>
                <button type="button" class="btn btn-link text-muted fw-600 text-decoration-none" data-bs-dismiss="modal"><?= (Auth::check() && $unreturnedBooksCount >= 3) ? 'Close' : 'Maybe later' ?></button>
            </div>
        </div>
    </div>
</div>

<script>
let currentBookId = null;
function quickBorrow(bookId) {
    <?php if (!Auth::check()): ?>
        Swal.fire({
            icon: 'info',
            title: 'Login Required',
            text: 'Please login to borrow this book.',
            showCancelButton: true,
            confirmButtonText: 'Login Now',
            confirmButtonColor: '#E07A5F',
            cancelButtonText: 'Later'
        }).then((result) => {
            if (result.isConfirmed) {
                window.location.href = 'login.php?redirect=' + encodeURIComponent(window.location.href);
            }
        });
        return;
    <?php endif; ?>
    currentBookId = bookId;
    new bootstrap.Modal(document.getElementById('quickBorrowModal')).show();
}

document.getElementById('confirmBorrow')?.addEventListener('click', function() {
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

function shareAuthor(platform) {
    const url = encodeURIComponent(window.location.href);
    const title = encodeURIComponent('<?= addslashes($authorName) ?> - Author Profile');
    let shareUrl = '';
    switch(platform) {
        case 'facebook': shareUrl = `https://www.facebook.com/sharer/sharer.php?u=${url}`; break;
        case 'twitter': shareUrl = `https://twitter.com/intent/tweet?url=${url}&text=${title}`; break;
        case 'whatsapp': shareUrl = `https://wa.me/?text=${title} ${url}`; break;
    }
    if (shareUrl) window.open(shareUrl, '_blank', 'width=600,height=400');
}

function copyAuthorLink() {
    navigator.clipboard.writeText(window.location.href).then(() => {
        const btn = event.target.closest('button');
        const orig = btn.innerHTML;
        btn.innerHTML = '<i class="fas fa-check"></i>';
        btn.style.background = '#10b981'; btn.style.color = '#fff'; btn.style.borderColor = '#10b981';
        setTimeout(() => { btn.innerHTML = orig; btn.style = ''; }, 2000);
    });
}

function changeBooksSort(value) {
    const [sort, order] = value.split('-');
    const url = new URL(window.location);
    url.searchParams.set('sort', sort);
    url.searchParams.set('order', order);
    url.searchParams.delete('page');
    window.location.href = url.toString();
}
</script>

<?php include 'views/footer.php'; ?>
