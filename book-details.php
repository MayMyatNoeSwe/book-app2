<?php
$pageTitle = "Book Details";
require_once 'includes/sessions.php';
require_once 'vendor/autoload.php';
require_once 'includes/env_loader.php';
require_once 'includes/functions.php';

use App\Auth;
use App\Library;
use App\EBook;

$library = new Library();

// Get book ID from URL or POST data
$bookId = $_GET['id'] ?? $_POST['id'] ?? null;
if (!$bookId) {
    header('Location: index.php');
    exit;
}

// Get book details
$book = $library->getBookById($bookId);
if (!$book) {
    header('Location: index.php');
    exit;
}
$currentBook = $book;

// Handle form submissions
$message = '';
$messageType = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && Auth::check()) {
    $action = $_POST['action'] ?? '';
    $userId = Auth::id();
    
    switch ($action) {
        case 'borrow':
            // The library class now handles the multi-card limit check internally in borrowBook
            if ($library->borrowBook($bookId, $userId)) {
                $message = 'Borrow request submitted! Waiting for admin approval.';
                $messageType = 'success';
                $book = $library->getBookById($bookId);
            } else {
                // Determine why it failed
                $rules = $library->getMembershipRules($userId);
                $stmt = $library->getPdo()->prepare("SELECT COUNT(*) FROM borrowing_history WHERE user_id = ? AND subscription_id = ? AND returned_at IS NULL AND status IN ('pending','approved')");
                $stmt->execute([$userId, $rules['sub_id']]);
                $count = (int)$stmt->fetchColumn();
                
                if ($count >= $rules['limit']) {
                    $message = "You have reached the maximum borrow limit of {$rules['limit']} books for your active card. Please return a book first or switch to another member card.";
                } else {
                    $message = 'Unable to borrow this book. You may already have a pending request or it might be unavailable.';
                }
                $messageType = 'error';
            }
            break;
            
        case 'return':
            if ($library->returnBook($bookId, $userId)) {
                $message = 'Return request submitted! Waiting for admin approval.';
                $messageType = 'success';
                $book = $library->getBookById($bookId);
            } else {
                $message = 'Unable to submit return request. Please contact support.';
                $messageType = 'error';
            }
            break;
            
        case 'reserve':
            if ($library->reserveBook($userId, $bookId)) {
                $message = 'Book reserved successfully! You will be notified when it becomes available.';
                $messageType = 'success';
            } else {
                $message = 'Unable to reserve this book. Please try again.';
                $messageType = 'error';
            }
            break;
            
        case 'review':
            $rating = (int)($_POST['rating'] ?? 0);
            $reviewText = trim($_POST['review_text'] ?? '');
            $reviewId = (int)($_POST['review_id'] ?? 0);
            
            if ($rating >= 1 && $rating <= 5) {
                if ($reviewId > 0) {
                    $library->updateReview($reviewId, $userId, $rating, $reviewText);
                    $message = 'Review updated successfully!';
                } else {
                    $library->addReview($userId, $bookId, $rating, $reviewText);
                    $message = 'Review submitted successfully!';
                }
                $messageType = 'success';
            } else {
                $message = 'Please provide a valid rating (1-5 stars).';
                $messageType = 'error';
            }
            break;
    }
}

// Get book reviews
$reviews = $library->getReviews($bookId);

// Get related books
$relatedBooks = [];
try {
    $relatedByAuthor = $library->getBooksPaginated(6, 0, null, $book->getAuthor());
    $relatedByCategory = $library->getBooksPaginated(6, 0, $book->getCategory(), null);
    $allRelated = array_merge($relatedByAuthor, $relatedByCategory);
    foreach ($allRelated as $relatedBook) {
        if ($relatedBook->getId() !== $bookId && count($relatedBooks) < 4) {
            $relatedBooks[$relatedBook->getId()] = $relatedBook;
        }
    }
    $relatedBooks = array_values($relatedBooks);
} catch (Exception $e) {
    $relatedBooks = [];
}

// Check if user is currently borrowing
$isCurrentlyBorrowing = false;
$hasBorrowedBefore = false;
$unreturnedBooksCount = 0;
$hasPendingBorrow = false;
$isReturnPending = false;
if (Auth::check()) {
    $userId = Auth::id();
    $isCurrentlyBorrowing = $library->isCurrentlyBorrowing($userId, $bookId);
    $hasPendingBorrow = $library->hasPendingBorrow($userId, $bookId);
    $pdo = $library->getPdo();
    
    // Fetch membership rules for the active card
    $msRules = $library->getMembershipRules($userId);
    $individualLimit = $msRules['limit'];
    $groupSize = $library->getGroupMemberCount($activeSubId);
    $borrowLimit = $individualLimit * $groupSize; // Total capacity for the whole group
    
    $borrowDuration = $msRules['days'];
    $borrowFine = $msRules['fine'];
    $activeSubId = $msRules['sub_id'];

    $stmt = $pdo->prepare("SELECT COUNT(*) FROM borrowing_history WHERE user_id = ?");
    $stmt->execute([$userId]);
    $hasBorrowedBefore = $stmt->fetchColumn() > 0;
    
    // Count unreturned books FOR THE ACTIVE GROUP
    $unreturnedBooksCount = $library->getGroupUsageCount($activeSubId);

    // Check if return is pending for this book
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM borrowing_history WHERE user_id = ? AND book_id = ? AND `status` = 'return_pending'");
    $stmt->execute([$userId, $bookId]);
    $isReturnPending = (int)$stmt->fetchColumn() > 0;
}

// Calculate average rating
$averageRating = 0;
$totalRatings = count($reviews);
if ($totalRatings > 0) {
    $totalScore = array_sum(array_column($reviews, 'rating'));
    $averageRating = $totalScore / $totalRatings;
}

$pageTitle = $book->getTitle() . " - Book Details";
$isEbook = $book instanceof EBook;
$coverUrl = getBookCoverUrl($book, $book->getTitle(), $book->getAuthor());
$fallbackCover = getDummyBookCover($book->getTitle(), $book->getAuthor());

include 'views/header.php';
?>

<style>
/* ─── Book Details Premium ─── */
.bd-hero {
    position: relative; overflow: hidden;
    padding: 50px 0 60px;
    background:
        radial-gradient(ellipse at 10% 60%, rgba(224,122,95,0.12) 0%, transparent 55%),
        radial-gradient(ellipse at 90% 20%, rgba(129,178,154,0.09) 0%, transparent 50%),
        var(--bookhouse-bg, #FFF3F0);
}
[data-bs-theme="dark"] .bd-hero {
    background:
        radial-gradient(ellipse at 10% 60%, rgba(224,122,95,0.15) 0%, transparent 55%),
        radial-gradient(ellipse at 90% 20%, rgba(129,178,154,0.12) 0%, transparent 50%),
        #0f172a;
}
.bd-hero::before {
    content: ''; position: absolute; inset: 0;
    background-image:
        linear-gradient(rgba(0,0,0,0.02) 1px, transparent 1px),
        linear-gradient(90deg, rgba(0,0,0,0.02) 1px, transparent 1px);
    background-size: 60px 60px; pointer-events: none;
}
[data-bs-theme="dark"] .bd-hero::before {
    background-image:
        linear-gradient(rgba(255,255,255,0.025) 1px, transparent 1px),
        linear-gradient(90deg, rgba(255,255,255,0.025) 1px, transparent 1px);
}

/* Cover */
.bd-cover-wrap {
    position: relative; width: 280px; max-width: 100%; flex-shrink: 0;
}
.bd-cover {
    width: 100%; border-radius: 20px; overflow: hidden;
    box-shadow: 0 30px 60px rgba(61,64,91,0.2);
    aspect-ratio: 2/3;
}
.bd-cover img { width: 100%; height: 100%; object-fit: cover; }
.bd-cover-badge {
    position: absolute; top: 14px; left: 14px;
    padding: 6px 14px; border-radius: 999px;
    font-size: 11px; font-weight: 800; letter-spacing: 0.6px;
    text-transform: uppercase; backdrop-filter: blur(8px);
}
.bd-cover-badge.available { background: rgba(16,185,129,0.9); color: #fff; }
.bd-cover-badge.borrowed  { background: rgba(239,68,68,0.85); color: #fff; }
.bd-cover-badge.ebook     { background: rgba(59,130,246,0.85); color: #fff; }

/* Info */
.bd-cat {
    display: inline-block; font-size: 11px; font-weight: 800;
    letter-spacing: 1.5px; text-transform: uppercase;
    color: var(--bookhouse-orange); margin-bottom: 10px;
    background: rgba(224,122,95,0.08);
    padding: 5px 14px; border-radius: 999px;
}
.bd-title {
    font-family: 'Playfair Display', serif;
    font-size: clamp(1.6rem, 4vw, 2.6rem);
    font-weight: 800; line-height: 1.2;
    color: var(--bookhouse-text); margin-bottom: 10px;
}
.bd-author-line {
    font-size: 1rem; color: var(--bookhouse-text-muted); margin-bottom: 20px;
}
.bd-author-line a {
    color: var(--bookhouse-orange); text-decoration: none; font-weight: 700;
    transition: opacity 0.2s;
}
.bd-author-line a:hover { opacity: 0.8; }

/* Rating */
.bd-rating {
    display: flex; align-items: center; gap: 12px;
    margin-bottom: 24px;
}
.bd-rating .stars { display: flex; gap: 3px; }
.bd-rating .stars i { color: #f59e0b; font-size: 16px; }
.bd-rating .stars i.empty { color: #d1d5db; }
.bd-rating .score { font-weight: 800; font-size: 18px; color: var(--bookhouse-text); }
.bd-rating .count { font-size: 13px; color: var(--bookhouse-text-muted); }

/* Meta grid */
.bd-meta-grid {
    display: grid; grid-template-columns: repeat(auto-fill, minmax(140px, 1fr));
    gap: 12px; margin-bottom: 28px;
}
.bd-meta-item {
    background: rgba(255,255,255,0.7); backdrop-filter: blur(8px);
    border: 1px solid rgba(0,0,0,0.06);
    border-radius: 14px; padding: 14px 16px;
    transition: transform 0.2s;
    overflow: hidden; min-width: 0;
}
.bd-meta-item:hover { transform: translateY(-2px); }
[data-bs-theme="dark"] .bd-meta-item {
    background: rgba(30,41,59,0.7); border-color: rgba(255,255,255,0.08);
}
.bd-meta-item .label {
    font-size: 10px; font-weight: 700; letter-spacing: 0.8px;
    text-transform: uppercase; color: var(--bookhouse-text-muted); margin-bottom: 4px;
}
.bd-meta-item .value {
    font-size: 15px; font-weight: 800; color: var(--bookhouse-text);
    word-break: break-all; overflow-wrap: break-word;
}

/* Actions */
.bd-actions { display: flex; flex-wrap: wrap; gap: 10px; }
.bd-btn {
    padding: 12px 28px; border-radius: 14px;
    font-size: 14px; font-weight: 700;
    border: none; cursor: pointer;
    transition: all 0.25s; text-decoration: none;
    display: inline-flex; align-items: center; gap: 8px;
}
.bd-btn-primary {
    background: var(--bookhouse-orange); color: #fff;
    box-shadow: 0 8px 20px rgba(224,122,95,0.3);
}
.bd-btn-primary:hover { filter: brightness(1.1); transform: translateY(-2px); color: #fff; }
.bd-btn-outline {
    background: transparent;
    border: 2px solid rgba(0,0,0,0.1) !important;
    color: var(--bookhouse-text);
}
[data-bs-theme="dark"] .bd-btn-outline { border-color: rgba(255,255,255,0.12) !important; }
.bd-btn-outline:hover {
    border-color: var(--bookhouse-orange) !important;
    color: var(--bookhouse-orange);
}
.bd-btn-success { background: #10b981; color: #fff; }
.bd-btn-danger-outline {
    background: transparent; border: 2px solid rgba(239,68,68,0.3) !important;
    color: #ef4444;
}
.bd-btn-danger-outline:hover { background: #ef4444; color: #fff; }

/* Share */
.bd-share { display: flex; gap: 8px; margin-top: 20px; }
.bd-share button {
    width: 40px; height: 40px; border-radius: 12px;
    border: 1px solid rgba(0,0,0,0.08); background: rgba(0,0,0,0.02);
    color: var(--bookhouse-text-muted); cursor: pointer;
    transition: all 0.2s; font-size: 14px;
    display: flex; align-items: center; justify-content: center;
}
[data-bs-theme="dark"] .bd-share button {
    background: rgba(255,255,255,0.05); border-color: rgba(255,255,255,0.08);
}
.bd-share button:hover {
    background: var(--bookhouse-orange); color: #fff;
    border-color: var(--bookhouse-orange);
}

/* ─── Description ─── */
.bd-section {
    padding: 50px 0;
}
.bd-section-title {
    font-family: 'Manrope', sans-serif;
    font-size: 22px; font-weight: 800;
    color: var(--bookhouse-text); margin-bottom: 20px;
    display: flex; align-items: center; gap: 10px;
}
.bd-section-title i { color: var(--bookhouse-orange); }
.bd-desc-text {
    font-size: 15px; line-height: 1.8;
    color: var(--bookhouse-text-muted);
    max-width: 700px;
}
.bd-tag {
    display: inline-block; padding: 5px 14px;
    border-radius: 999px; font-size: 12px; font-weight: 700;
    background: rgba(0,0,0,0.04); color: var(--bookhouse-text-muted);
    margin: 4px 4px 4px 0;
}
[data-bs-theme="dark"] .bd-tag { background: rgba(255,255,255,0.06); }

/* ─── Reviews ─── */
.bd-review-card {
    background: #fff; border-radius: 18px;
    border: 1px solid rgba(0,0,0,0.06);
    padding: 24px; margin-bottom: 16px;
    transition: transform 0.2s;
}
.bd-review-card:hover { transform: translateY(-2px); }
[data-bs-theme="dark"] .bd-review-card {
    background: #1e293b; border-color: rgba(255,255,255,0.06);
}
.bd-reviewer {
    display: flex; align-items: center; gap: 12px; margin-bottom: 12px;
}
.bd-reviewer-avatar {
    width: 44px; height: 44px; border-radius: 50%;
    background: rgba(224,122,95,0.1);
    display: flex; align-items: center; justify-content: center;
    color: var(--bookhouse-orange); font-size: 18px;
}
.bd-reviewer-name { font-weight: 700; font-size: 15px; color: var(--bookhouse-text); }
.bd-reviewer-date { font-size: 12px; color: var(--bookhouse-text-muted); }
.bd-review-stars { display: flex; gap: 2px; margin-bottom: 8px; }
.bd-review-stars i { font-size: 13px; color: #f59e0b; }
.bd-review-stars i.empty { color: #d1d5db; }
.bd-review-text { font-size: 14px; line-height: 1.7; color: var(--bookhouse-text-muted); }
.bd-review-actions {
    display: flex; gap: 6px; margin-left: auto;
}
.bd-review-actions button {
    width: 32px; height: 32px; border-radius: 8px;
    border: 1px solid rgba(0,0,0,0.08); background: transparent;
    cursor: pointer; font-size: 12px; color: var(--bookhouse-text-muted);
    transition: all 0.2s;
}
.bd-review-actions button:hover { color: var(--bookhouse-orange); border-color: var(--bookhouse-orange); }

/* ─── Related Books ─── */
.bd-related-card {
    display: flex; gap: 14px; align-items: center;
    padding: 12px; border-radius: 14px;
    border: 1px solid rgba(0,0,0,0.06);
    background: #fff; text-decoration: none;
    transition: all 0.2s; margin-bottom: 10px;
}
.bd-related-card:hover { transform: translateX(4px); box-shadow: 0 8px 24px rgba(0,0,0,0.06); }
[data-bs-theme="dark"] .bd-related-card { background: #1e293b; border-color: rgba(255,255,255,0.06); }
.bd-related-cover {
    width: 60px; min-width: 60px; height: 80px;
    border-radius: 10px; overflow: hidden; flex-shrink: 0;
}
.bd-related-cover img { width: 100%; height: 100%; object-fit: cover; }
.bd-related-info h6 {
    font-weight: 700; font-size: 13px; margin: 0 0 2px;
    color: var(--bookhouse-text);
    display: -webkit-box; -webkit-line-clamp: 2; line-clamp: 2;
    -webkit-box-orient: vertical; overflow: hidden;
}
.bd-related-info small { color: var(--bookhouse-text-muted); font-size: 12px; }

/* ─── Empty reviews ─── */
.bd-empty-reviews {
    text-align: center; padding: 60px 20px;
    background: rgba(0,0,0,0.02); border-radius: 20px;
}
[data-bs-theme="dark"] .bd-empty-reviews { background: rgba(255,255,255,0.02); }
.bd-empty-reviews i { font-size: 48px; color: var(--bookhouse-orange); opacity: 0.3; margin-bottom: 16px; }

/* ─── Modal ─── */
.bd-modal .modal-content { border: none; border-radius: 24px; overflow: hidden; }
.bd-modal .modal-header {
    background: linear-gradient(135deg, var(--bookhouse-orange), #c2664e);
    border: none; padding: 24px 28px; color: #fff;
}
.bd-modal .modal-body { padding: 28px; }
.bd-modal .modal-footer { border: none; padding: 0 28px 28px; }

/* Star input */
.bd-star-input { display: flex; flex-direction: row-reverse; justify-content: flex-end; gap: 4px; }
.bd-star-input input { display: none; }
.bd-star-input label {
    font-size: 28px; color: #d1d5db; cursor: pointer;
    transition: color 0.15s;
}
.bd-star-input label:hover, .bd-star-input label:hover ~ label,
.bd-star-input input:checked ~ label { color: #f59e0b; }

/* Alert */
.bd-alert {
    border: none; border-radius: 14px; padding: 16px 20px;
    display: flex; align-items: center; gap: 12px;
    font-weight: 600; font-size: 14px;
}
.bd-alert-success { background: rgba(16,185,129,0.1); color: #059669; }
.bd-alert-error   { background: rgba(239,68,68,0.1); color: #dc2626; }

/* ─── Responsive ─── */
@media (max-width: 991px) {
    .bd-hero-row { flex-direction: column !important; align-items: center !important; text-align: center; }
    .bd-cover-wrap { width: 220px; margin-bottom: 30px; }
    .bd-meta-grid { grid-template-columns: repeat(2, 1fr); }
    .bd-actions { justify-content: center; }
    .bd-share { justify-content: center; }
    .bd-author-line { justify-content: center; }
}
@media (max-width: 576px) {
    .bd-meta-grid { grid-template-columns: 1fr; }
}
</style>

<!-- ═══════  HERO  ═══════ -->
<section class="bd-hero">
    <div class="container position-relative" style="z-index:2;">
        <!-- Breadcrumb -->
        <nav aria-label="breadcrumb" class="mb-4">
            <ol class="breadcrumb" style="font-size:13px;">
                <li class="breadcrumb-item"><a href="index.php" class="text-decoration-none" style="color:var(--bookhouse-text-muted);">Home</a></li>
                <li class="breadcrumb-item"><a href="book-list.php" class="text-decoration-none" style="color:var(--bookhouse-text-muted);">Library</a></li>
                <li class="breadcrumb-item"><a href="book-list.php?category=<?= urlencode($book->getCategory()) ?>" class="text-decoration-none" style="color:var(--bookhouse-text-muted);"><?= e($book->getCategory()) ?></a></li>
                <li class="breadcrumb-item active fw-bold" style="color:var(--bookhouse-orange);"><?= e(strlen($book->getTitle()) > 30 ? substr($book->getTitle(), 0, 30) . '...' : $book->getTitle()) ?></li>
            </ol>
        </nav>

        <!-- Alert -->
        <?php if ($message): ?>
        <div class="bd-alert <?= $messageType === 'success' ? 'bd-alert-success' : 'bd-alert-error' ?> mb-4">
            <i class="fas fa-<?= $messageType === 'success' ? 'check-circle' : 'exclamation-triangle' ?>"></i>
            <?= e($message) ?>
        </div>
        <?php endif; ?>

        <!-- Main Layout -->
        <div class="d-flex gap-5 bd-hero-row">
            <!-- Cover -->
            <div class="bd-cover-wrap">
                <div class="bd-cover">
                    <img src="<?= $coverUrl ?>" alt="<?= e($book->getTitle()) ?>" onerror="this.src='<?= $fallbackCover ?>'">
                </div>
                <?php if ($isEbook): ?>
                    <span class="bd-cover-badge ebook"><i class="fas fa-tablet-alt me-1"></i>E-Book</span>
                <?php elseif ($book->isAvailable()): ?>
                    <span class="bd-cover-badge available"><i class="fas fa-check me-1"></i>Available</span>
                <?php else: ?>
                    <span class="bd-cover-badge borrowed" style="background:rgba(239,68,68,0.95);"><i class="fas fa-exclamation-circle me-1"></i>Out of Stock</span>
                <?php endif; ?>
            </div>

            <!-- Info -->
            <div class="flex-1" style="min-width:0;">
                <span class="bd-cat"><?= e($book->getCategory()) ?></span>
                <h1 class="bd-title"><?= e($book->getTitle()) ?></h1>
                <div class="bd-author-line">
                    by <a href="author-details.php?author=<?= urlencode($book->getAuthor()) ?>"><?= e($book->getAuthor()) ?></a>
                    <span style="margin: 0 8px; opacity:0.3;">·</span> <?= $book->getYear() ?>
                </div>

                <!-- Rating -->
                <div class="bd-rating">
                    <div class="stars">
                        <?php for ($i = 1; $i <= 5; $i++): ?>
                            <i class="fas fa-star <?= $i <= round($averageRating) ? '' : 'empty' ?>"></i>
                        <?php endfor; ?>
                    </div>
                    <span class="score"><?= number_format($averageRating, 1) ?></span>
                    <span class="count">(<?= $totalRatings ?> <?= $totalRatings === 1 ? 'review' : 'reviews' ?>)</span>
                </div>

                <!-- Meta Grid -->
                <div class="bd-meta-grid">
                    <div class="bd-meta-item">
                        <div class="label">Category</div>
                        <div class="value"><?= e($book->getCategory()) ?></div>
                    </div>
                    <div class="bd-meta-item">
                        <div class="label">Published</div>
                        <div class="value"><?= $book->getYear() ?></div>
                    </div>
                    <?php if (!$isEbook): ?>
                    <div class="bd-meta-item">
                        <div class="label">Copies</div>
                        <div class="value"><?= $book->getAvailableCopies() ?> / <?= $book->getTotalCopies() ?></div>
                    </div>
                    <?php else: ?>
                    <div class="bd-meta-item">
                        <div class="label">Format</div>
                        <div class="value">Digital PDF</div>
                    </div>
                    <?php endif; ?>
                    <div class="bd-meta-item">
                        <div class="label">Buy Price</div>
                        <div class="value"><?= number_format($book->getPrice()) ?> Ks</div>
                    </div>
                    <?php if (!$isEbook): ?>
                    <div class="bd-meta-item">
                        <div class="label">Borrow Fee</div>
                        <div class="value"><?= number_format($book->getBorrowPrice()) ?> Ks</div>
                    </div>
                    <?php endif; ?>
                    <div class="bd-meta-item">
                        <div class="label">Book ID</div>
                        <div class="value" style="font-family:monospace; font-size:13px;"><?= e($book->getId()) ?></div>
                    </div>
                </div>

                <!-- Action Buttons -->
                <div class="bd-actions">
                    <?php if ($isEbook): ?>
                        <?php if (Auth::check()): ?>
                            <?php if ($book->getDownloadLink()): ?>
                                <a href="<?= e($book->getDownloadLink()) ?>" target="_blank" class="bd-btn bd-btn-primary">
                                    <i class="fas fa-download"></i> Download PDF
                                </a>
                            <?php else: ?>
                                <button class="bd-btn bd-btn-outline" disabled><i class="fas fa-times"></i> No Download</button>
                            <?php endif; ?>
                        <?php else: ?>
                            <button type="button" onclick="showLoginAlert('Please login to download this e-book.')" class="bd-btn bd-btn-primary">
                                <i class="fas fa-sign-in-alt"></i> Download Now
                            </button>
                        <?php endif; ?>
                    <?php else: ?>
                        <div class="d-flex flex-wrap gap-2 align-items-center mb-3">
                            <?php if (Auth::check() && $hasPendingBorrow): ?>
                                <button class="bd-btn bd-btn-outline" disabled style="opacity:0.7;cursor:not-allowed;">
                                    <i class="fas fa-hourglass-half"></i> Pending Approval
                                </button>
                            <?php elseif (Auth::check() && $isReturnPending): ?>
                                <button class="bd-btn bd-btn-outline" disabled style="opacity:0.7;cursor:not-allowed;background:rgba(139,92,246,0.1);border-color:rgba(139,92,246,0.3)!important;color:#7c3aed;">
                                    <i class="fas fa-hourglass-half"></i> Return Pending
                                </button>
                            <?php elseif (Auth::check() && $isCurrentlyBorrowing): ?>
                                <button class="bd-btn bd-btn-success" disabled><i class="fas fa-book-reader"></i> Currently Reading</button>
                                <form method="POST" style="display:inline;">
                                    <input type="hidden" name="action" value="return">
                                    <button type="submit" class="bd-btn bd-btn-danger-outline"><i class="fas fa-undo"></i> Return</button>
                                </form>
                            <?php elseif ($book->isAvailable()): ?>
                                <?php if (Auth::check()): ?>
                                    <button type="button" class="bd-btn bd-btn-primary" onclick="confirmBorrow()">
                                        <i class="fas fa-book"></i> Borrow
                                    </button>
                                <?php else: ?>
                                    <button type="button" class="bd-btn bd-btn-primary" onclick="showLoginAlert('Please login to borrow this book.')">
                                        <i class="fas fa-sign-in-alt"></i> Borrow Now
                                    </button>
                                <?php endif; ?>
                            <?php else: ?>
                                <?php if (Auth::check()): ?>
                                    <form method="POST" style="display:inline;">
                                        <input type="hidden" name="action" value="reserve">
                                        <button type="submit" class="bd-btn bd-btn-outline" title="Notify me when available">
                                            <i class="fas fa-bookmark"></i> Reserve
                                        </button>
                                    </form>
                                <?php else: ?>
                                    <button type="button" class="bd-btn bd-btn-outline" onclick="showLoginAlert('Please login to borrow this book.')">
                                        <i class="fas fa-sign-in-alt"></i> Borrow Now
                                    </button>
                                <?php endif; ?>
                            <?php endif; ?>

                            <?php if ($book->isAvailable()): ?>
                                <button onclick="addToCart('<?= e($bookId) ?>')" class="bd-btn bd-btn-outline">
                                    <i class="fas fa-shopping-cart"></i> Add to Cart
                                </button>
                            <?php else: ?>
                                <button onclick="addToCart('<?= e($bookId) ?>')" class="bd-btn bd-btn-primary" style="background:#524f7d; border:none; box-shadow:0 8px 15px rgba(82,79,125,0.2);">
                                    <i class="fas fa-calendar-check"></i> Pre-order
                                </button>
                            <?php endif; ?>

                            <?php if (Auth::check()): ?>
                                <button class="bd-btn bd-btn-outline" data-bs-toggle="modal" data-bs-target="#reviewModal">
                                    <i class="fas fa-star"></i> Review
                                </button>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- Share -->
                <div class="bd-share">
                    <button onclick="shareBook('facebook')" title="Facebook"><i class="fab fa-facebook-f"></i></button>
                    <button onclick="shareBook('twitter')" title="Twitter"><i class="fab fa-twitter"></i></button>
                    <button onclick="shareBook('whatsapp')" title="WhatsApp"><i class="fab fa-whatsapp"></i></button>
                    <button onclick="copyBookLink()" title="Copy Link"><i class="fas fa-link"></i></button>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- ═══════  DESCRIPTION & SIDEBAR  ═══════ -->
<div class="bd-section" style="border-top: 1px solid rgba(0,0,0,0.04);">
    <div class="container">
        <div class="row g-5">
            <!-- Description -->
            <div class="col-lg-8">
                <h3 class="bd-section-title"><i class="fas fa-align-left"></i> About This Book</h3>
                <div class="bd-desc-text">
                    <p>Discover the captivating world of "<?= e($book->getTitle()) ?>" by <?= e($book->getAuthor()) ?>. 
                    This remarkable <?= strtolower($book->getCategory()) ?> work, published in <?= $book->getYear() ?>, 
                    offers readers an engaging journey through expertly crafted storytelling and profound insights.</p>
                    <p>Whether you're a longtime fan of <?= e($book->getAuthor()) ?> or discovering their work for the first time, 
                    this book promises to deliver an unforgettable reading experience that will leave you thinking long after 
                    you've turned the final page.</p>
                </div>
                <div class="mt-3">
                    <span class="bd-tag"><?= e($book->getCategory()) ?></span>
                    <span class="bd-tag"><?= $book->getYear() ?>s</span>
                    <span class="bd-tag"><?= e($book->getAuthor()) ?></span>
                </div>

                <!-- Reviews -->
                <div class="mt-5 pt-4" style="border-top: 1px solid rgba(0,0,0,0.04);">
                    <h3 class="bd-section-title">
                        <i class="fas fa-comments"></i> Reader Reviews
                        <?php if ($totalRatings > 0): ?>
                            <span style="background:rgba(224,122,95,0.1);color:var(--bookhouse-orange);padding:2px 10px;border-radius:999px;font-size:13px;margin-left:4px;"><?= $totalRatings ?></span>
                        <?php endif; ?>
                    </h3>

                    <?php if (!empty($reviews)): ?>
                        <?php foreach ($reviews as $review): ?>
                        <div class="bd-review-card" id="review-<?= $review['id'] ?>">
                            <div class="d-flex align-items-start">
                                <div class="bd-reviewer">
                                    <div class="bd-reviewer-avatar"><i class="fas fa-user"></i></div>
                                    <div>
                                        <div class="bd-reviewer-name"><?= e($review['username']) ?></div>
                                        <div class="bd-reviewer-date"><?= date('M j, Y', strtotime($review['created_at'])) ?></div>
                                    </div>
                                </div>
                                <?php if (Auth::check() && Auth::id() == $review['user_id']): ?>
                                <div class="bd-review-actions">
                                    <button onclick="editReview(<?= $review['id'] ?>, <?= $review['rating'] ?>, '<?= addslashes($review['review_text']) ?>')" title="Edit"><i class="fas fa-pen"></i></button>
                                    <button onclick="deleteReview(<?= $review['id'] ?>)" title="Delete"><i class="fas fa-trash"></i></button>
                                </div>
                                <?php endif; ?>
                            </div>
                            <div class="bd-review-stars">
                                <?php for ($i = 1; $i <= 5; $i++): ?>
                                    <i class="fas fa-star <?= $i <= $review['rating'] ? '' : 'empty' ?>"></i>
                                <?php endfor; ?>
                            </div>
                            <?php if (!empty($review['review_text'])): ?>
                                <div class="bd-review-text"><?= e($review['review_text']) ?></div>
                            <?php endif; ?>
                        </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div class="bd-empty-reviews">
                            <div style="width:72px;height:72px;margin:0 auto 20px;background:rgba(224,122,95,0.08);border-radius:50%;display:flex;align-items:center;justify-content:center;">
                                <i class="fas fa-comments" style="font-size:28px;color:var(--bookhouse-orange);opacity:0.5;"></i>
                            </div>
                            <h5 style="font-weight:700; color:var(--bookhouse-text); margin-bottom:8px; font-size:18px;">No reviews yet</h5>
                            <p style="color:var(--bookhouse-text-muted); margin-bottom:24px; font-size:14px;">Be the first to share your thoughts about this book!</p>
                            <?php if (Auth::check()): ?>
                                <button class="bd-btn bd-btn-outline" data-bs-toggle="modal" data-bs-target="#reviewModal" style="padding:10px 24px; font-size:13px; margin:0 auto; justify-content:center;">
                                    Write a Review
                                </button>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Sidebar: Related Books -->
            <div class="col-lg-4">
                <?php if (!empty($relatedBooks)): ?>
                <h3 class="bd-section-title"><i class="fas fa-book-open"></i> Related Books</h3>
                <?php foreach ($relatedBooks as $rb):
                    $rbCover = getBookCoverUrl($rb, $rb->getTitle(), $rb->getAuthor());
                    $rbFallback = getDummyBookCover($rb->getTitle(), $rb->getAuthor(), 200, 300);
                ?>
                <a href="book-details.php?id=<?= $rb->getId() ?>" class="bd-related-card">
                    <div class="bd-related-cover">
                        <img src="<?= $rbCover ?>" alt="<?= e($rb->getTitle()) ?>" loading="lazy" onerror="this.src='<?= $rbFallback ?>'">
                    </div>
                    <div class="bd-related-info">
                        <h6><?= e($rb->getTitle()) ?></h6>
                        <small><?= e($rb->getAuthor()) ?> · <?= e($rb->getCategory()) ?></small>
                    </div>
                </a>
                <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php $book = $currentBook; ?>

<!-- Review Modal -->
<?php if (Auth::check()): ?>
<div class="modal fade bd-modal" id="reviewModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <div>
                    <h5 class="modal-title fw-800 mb-0"><i class="fas fa-star me-2"></i>Write a Review</h5>
                    <p class="mb-0 small opacity-75">Share your thoughts about "<?= e($book->getTitle()) ?>"</p>
                </div>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" id="reviewForm">
                <div class="modal-body">
                    <input type="hidden" name="action" value="review">
                    <input type="hidden" name="review_id" id="reviewId" value="">
                    
                    <div class="mb-4">
                        <label class="form-label fw-700 mb-2">Your Rating *</label>
                        <div class="bd-star-input">
                            <?php for ($i = 5; $i >= 1; $i--): ?>
                                <input type="radio" name="rating" value="<?= $i ?>" id="bdStar<?= $i ?>" required>
                                <label for="bdStar<?= $i ?>" title="<?= $i ?> stars"><i class="fas fa-star"></i></label>
                            <?php endfor; ?>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="reviewText" class="form-label fw-700">Your Review <span class="fw-normal text-muted">(Optional)</span></label>
                        <textarea class="form-control" id="reviewText" name="review_text" rows="4" 
                                  placeholder="What did you think of this book?" style="border-radius:14px; border-color: rgba(0,0,0,0.1);"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="bd-btn bd-btn-outline" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="bd-btn bd-btn-primary">
                        <i class="fas fa-paper-plane"></i> Submit Review
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
<?php endif; ?>

<!-- Borrow via SweetAlert2 -->
<form method="POST" id="borrowForm" style="display:none;">
    <input type="hidden" name="action" value="borrow">
</form>

<script>
// ─── Login Required Alert (for non-logged-in users) ───
function borrowLoginAlert() {
    showLoginAlert('Please login to borrow this book.');
}

// ─── Borrow Confirmation Alert ───
function confirmBorrow() {
    <?php if (Auth::check() && $unreturnedBooksCount >= $borrowLimit): ?>
    Swal.fire({
        icon: 'error',
        title: 'Borrow Limit Reached',
        html: '<div style="font-size:15px;line-height:1.7;">' +
              'You currently have <strong><?= $unreturnedBooksCount ?></strong> unreturned books on this card (max <?= $borrowLimit ?>).<br>' +
              'Please return a book or switch to another member card before borrowing.' +
              '</div>',
        confirmButtonText: 'Got it',
        confirmButtonColor: '#d48b71',
    });
    <?php else: ?>
    Swal.fire({
        title: 'Borrow This Book?',
        html: '<div style="text-align:left;font-size:14px;line-height:1.8;padding:4px 0;">' +
              '<div style="background:rgba(212,139,113,0.08);border-radius:12px;padding:14px 16px;margin-bottom:16px;">' +
              '<div style="font-weight:700;margin-bottom:4px;color:#d48b71;"><i class="fas fa-info-circle me-1"></i> Borrowing Policy</div>' +
              '<div style="color:#6b7280;">Return within <strong><?= $borrowDuration ?> days</strong>. Late returns incur <strong><?= number_format($borrowFine) ?> Ks/day</strong> penalty.</div>' +
              '</div>' +
              '<div style="display:flex;flex-direction:column;gap:6px;">' +
              '<div><i class="fas fa-book me-2" style="color:#d48b71;width:18px;"></i><strong>Book:</strong> <?= addslashes($book->getTitle()) ?></div>' +
              '<div><i class="fas fa-calendar-day me-2" style="color:#10b981;width:18px;"></i><strong>Duration:</strong> <?= $borrowDuration ?> Days</div>' +
              '<div><i class="fas fa-clock me-2" style="color:#f59e0b;width:18px;"></i><strong>Due Date:</strong> <?= date("M j, Y", strtotime("+".$borrowDuration." days")) ?></div>' +
              '<div><i class="fas fa-layer-group me-2" style="color:#6366f1;width:18px;"></i><strong>Current card usage:</strong> <?= $unreturnedBooksCount ?> / <?= $borrowLimit ?></div>' +
              '</div>' +
              '<div style="margin-top:12px;background:#fef3c7;border-radius:8px;padding:10px 12px;font-size:12px;color:#92400e;">' +
              '<i class="fas fa-shield-alt me-1"></i> Card used: <strong>' + <?= json_encode(ucfirst($msRules['tier'] ?: 'Bronze')) ?> + ' Member</strong>' +
              '</div>' +
              '<div style="margin-top:8px;background:#e0f2fe;border-radius:8px;padding:10px 12px;font-size:12px;color:#0369a1;">' +
              '<i class="fas fa-info-circle me-1"></i> Your request will be reviewed and approved by our admin team.' +
              '</div>' +
              '</div>',
        icon: 'question',
        iconColor: '#d48b71',
        showCancelButton: true,
        confirmButtonText: '<i class="fas fa-check me-1"></i> Confirm & Borrow',
        cancelButtonText: 'Maybe Later',
        confirmButtonColor: '#d48b71',
        cancelButtonColor: '#9ca3af',
        reverseButtons: true,
        customClass: { popup: 'swal-borrow-popup', confirmButton: 'swal-confirm-btn' },
        showClass: { popup: 'animate__animated animate__fadeInDown animate__faster' },
        hideClass: { popup: 'animate__animated animate__fadeOutUp animate__faster' }
    }).then((result) => {
        if (result.isConfirmed) {
            document.getElementById('borrowForm').submit();
        }
    });
    <?php endif; ?>
}

// ─── Show borrow result alert on page load ───
<?php if ($message && ($action ?? '') === 'borrow'): ?>
document.addEventListener('DOMContentLoaded', function() {
    Swal.fire({
        icon: '<?= $messageType === "success" ? "success" : "error" ?>',
        title: '<?= $messageType === "success" ? "Request Submitted!" : "Borrow Failed" ?>',
        html: '<div style="font-size:15px;"><?= addslashes($message) ?></div>',
        confirmButtonColor: '#d48b71',
        confirmButtonText: 'OK',
        showClass: { popup: 'animate__animated animate__fadeInDown animate__faster' },
        hideClass: { popup: 'animate__animated animate__fadeOutUp animate__faster' }
    });
});
<?php endif; ?>

// Share
function shareBook(platform) {
    const url = encodeURIComponent(window.location.href);
    const title = encodeURIComponent('<?= addslashes($book->getTitle()) ?> by <?= addslashes($book->getAuthor()) ?>');
    let shareUrl = '';
    switch(platform) {
        case 'facebook': shareUrl = `https://www.facebook.com/sharer/sharer.php?u=${url}`; break;
        case 'twitter': shareUrl = `https://twitter.com/intent/tweet?url=${url}&text=${title}`; break;
        case 'whatsapp': shareUrl = `https://wa.me/?text=${title} ${url}`; break;
    }
    if (shareUrl) window.open(shareUrl, '_blank', 'width=600,height=400');
}

function copyBookLink() {
    navigator.clipboard.writeText(window.location.href).then(() => {
        const btn = event.target.closest('button');
        const orig = btn.innerHTML;
        btn.innerHTML = '<i class="fas fa-check"></i>';
        btn.style.background = '#10b981'; btn.style.color = '#fff'; btn.style.borderColor = '#10b981';
        setTimeout(() => { btn.innerHTML = orig; btn.style = ''; }, 2000);
    });
}

// Reviews
function editReview(reviewId, rating, reviewText) {
    document.getElementById('reviewId').value = reviewId;
    const radio = document.querySelector(`input[name="rating"][value="${rating}"]`);
    if (radio) radio.checked = true;
    document.getElementById('reviewText').value = reviewText;
    document.querySelector('#reviewModal .modal-title').innerHTML = '<i class="fas fa-pen me-2"></i>Edit Your Review';
    document.querySelector('#reviewModal button[type="submit"]').innerHTML = '<i class="fas fa-save me-1"></i> Update Review';
    new bootstrap.Modal(document.getElementById('reviewModal')).show();
}

function deleteReview(reviewId) {
    if (typeof Swal === 'undefined') { alert('SweetAlert2 not loaded'); return; }
    Swal.fire({
        title: 'Delete Review?',
        text: 'This action cannot be undone.',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#d33',
        cancelButtonColor: '#6c757d',
        confirmButtonText: 'Yes, delete it!'
    }).then((result) => {
        if (result.isConfirmed) {
            fetch('api/delete_review.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ review_id: reviewId })
            })
            .then(r => r.json())
            .then(data => {
                if (data.success) {
                    Swal.fire({ icon:'success', title:'Deleted!', timer:1500, showConfirmButton:false }).then(() => location.reload());
                } else {
                    Swal.fire({ icon:'error', title:'Error', text: data.message || 'Failed to delete' });
                }
            })
            .catch(() => Swal.fire({ icon:'error', title:'Error', text:'Network error' }));
        }
    });
}

document.addEventListener('DOMContentLoaded', function() {
    // Reset modal
    const reviewModal = document.getElementById('reviewModal');
    if (reviewModal) {
        reviewModal.addEventListener('hidden.bs.modal', function() {
            document.getElementById('reviewId').value = '';
            document.getElementById('reviewForm').reset();
            document.querySelector('#reviewModal .modal-title').innerHTML = '<i class="fas fa-star me-2"></i>Write a Review';
            document.querySelector('#reviewModal button[type="submit"]').innerHTML = '<i class="fas fa-paper-plane me-1"></i> Submit Review';
        });
    }
});
</script>

<?php include 'views/footer.php'; ?>
