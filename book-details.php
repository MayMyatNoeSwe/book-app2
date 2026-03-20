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

// Get book ID from URL
$bookId = $_GET['id'] ?? null;
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
            if ($library->borrowBook($bookId, $userId)) {
                $message = 'Book borrowed successfully! Due date: ' . date('M j, Y', strtotime('+14 days'));
                $messageType = 'success';
                // Refresh book data
                $book = $library->getBookById($bookId);
            } else {
                $message = 'Unable to borrow this book. Please try again.';
                $messageType = 'error';
            }
            break;
            
        case 'return':
            if ($library->returnBook($bookId, $userId)) {
                $message = 'Book returned successfully! Thank you.';
                $messageType = 'success';
                // Refresh book data
                $book = $library->getBookById($bookId);
            } else {
                $message = 'Unable to return this book. Please contact support.';
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
                    // Update existing review
                    $library->updateReview($reviewId, $userId, $rating, $reviewText);
                    $message = 'Review updated successfully!';
                } else {
                    // Add new review
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

// Get related books (same author or category)
$relatedBooks = [];
try {
    $relatedByAuthor = $library->getBooksPaginated(6, 0, null, $book->getAuthor());
    $relatedByCategory = $library->getBooksPaginated(6, 0, $book->getCategory(), null);
    
    // Combine and filter out current book
    $allRelated = array_merge($relatedByAuthor, $relatedByCategory);
    foreach ($allRelated as $relatedBook) {
        if ($relatedBook->getId() !== $bookId && count($relatedBooks) < 4) {
            $relatedBooks[$relatedBook->getId()] = $relatedBook;
        }
    }
    $relatedBooks = array_values($relatedBooks);
} catch (Exception $e) {
    // Fallback to empty array if query fails
    $relatedBooks = [];
}

// Check if user is currently borrowing this book
$isCurrentlyBorrowing = false;
if (Auth::check()) {
    $isCurrentlyBorrowing = $library->isCurrentlyBorrowing(Auth::id(), $bookId);
}

// Calculate average rating
$averageRating = 0;
$totalRatings = count($reviews);
if ($totalRatings > 0) {
    $totalScore = array_sum(array_column($reviews, 'rating'));
    $averageRating = $totalScore / $totalRatings;
}

// Set dynamic page title
$pageTitle = $book->getTitle() . " - Book Details";

include 'views/header.php';
?>

<div class="book-details-container">
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
                    <a href="book-list.php" class="text-decoration-none">Books</a>
                </li>
                <li class="breadcrumb-item">
                    <a href="book-list.php?category=<?= urlencode($book->getCategory()) ?>" class="text-decoration-none">
                        <?= e($book->getCategory()) ?>
                    </a>
                </li>
                <li class="breadcrumb-item active" aria-current="page">
                    <?= e($book->getTitle()) ?>
                </li>
            </ol>
        </nav>
    </div>

    <!-- Message Display -->
    <?php if ($message): ?>
    <div class="container-fluid">
        <div class="alert alert-<?= $messageType === 'success' ? 'success' : 'danger' ?> alert-dismissible fade show" role="alert">
            <i class="fas fa-<?= $messageType === 'success' ? 'check-circle' : 'exclamation-triangle' ?> me-2"></i>
            <?= e($message) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    </div>
    <?php endif; ?>

    <!-- Main Book Details -->
    <div class="book-hero-section">
        <div class="container-fluid">
            <div class="row g-5 align-items-start">
                <!-- Book Cover & Information (LEFT SIDE - 67%) -->
                <div class="col-lg-8 order-lg-1">
                    <!-- Book Cover -->
                    <div class="book-cover-section animate-on-scroll mb-4">
                        <div class="book-cover-wrapper">
                            <img src="<?= getBookCoverUrl($book, $book->getTitle(), $book->getAuthor()) ?>"
                                 alt="<?= e($book->getTitle()) ?> cover" 
                                 class="book-cover-large"
                                 onerror="this.src='<?= getDummyBookCover($book->getTitle(), $book->getAuthor()) ?>'">
                            
                            <!-- Availability Badge -->
                            <div class="availability-badge">
                                <?php if ($book instanceof EBook): ?>
                                    <span class="badge bg-primary">
                                        <i class="fas fa-download me-1"></i>Digital E-Book
                                    </span>
                                <?php elseif ($book->isAvailable()): ?>
                                    <span class="badge bg-success">
                                        <i class="fas fa-check-circle me-1"></i>Available
                                    </span>
                                <?php else: ?>
                                    <span class="badge bg-danger">
                                        <i class="fas fa-times-circle me-1"></i>Not Available
                                    </span>
                                <?php endif; ?>
                            </div>
                        </div>

                        <!-- Quick Action Buttons -->
                        <div class="quick-actions mt-4">
                            <?php if (Auth::check()): ?>
                                <?php if ($book instanceof EBook): ?>
                                    <?php if ($book->getDownloadLink()): ?>
                                        <a href="<?= e($book->getDownloadLink()) ?>" target="_blank" 
                                           class="btn btn-primary btn-lg w-100 mb-3">
                                            <i class="fas fa-download me-2"></i>Download PDF
                                        </a>
                                    <?php else: ?>
                                        <button class="btn btn-secondary btn-lg w-100 mb-3" disabled>
                                            <i class="fas fa-times me-2"></i>No Download Link
                                        </button>
                                    <?php endif; ?>
                                <?php else: ?>
                                    <?php if ($isCurrentlyBorrowing): ?>
                                        <button class="btn btn-success btn-lg w-100 mb-3" disabled>
                                            <i class="fas fa-book-reader me-2"></i>Currently Borrowed
                                        </button>
                                        <form method="POST" class="mb-3">
                                            <input type="hidden" name="action" value="return">
                                            <button type="submit" class="btn btn-outline-danger btn-lg w-100">
                                                <i class="fas fa-undo me-2"></i>Return Book
                                            </button>
                                        </form>
                                    <?php elseif ($book->isAvailable()): ?>
                                        <form method="POST" class="mb-3">
                                            <input type="hidden" name="action" value="borrow">
                                            <button type="submit" class="btn btn-primary btn-lg w-100">
                                                <i class="fas fa-book me-2"></i>Borrow Now
                                            </button>
                                        </form>
                                        <!-- Add to Cart Button -->
                                        <button onclick="addToCart(<?= $bookId ?>)" class="btn btn-outline-primary btn-lg w-100 mb-3">
                                            <i class="fas fa-shopping-cart me-2"></i>Add to Cart
                                        </button>
                                    <?php else: ?>
                                        <form method="POST" class="mb-3">
                                            <input type="hidden" name="action" value="reserve">
                                            <button type="submit" class="btn btn-outline-info btn-lg w-100">
                                                <i class="fas fa-bookmark me-2"></i>Reserve Book
                                            </button>
                                        </form>
                                    <?php endif; ?>
                                <?php endif; ?>
                            <?php else: ?>
                                <a href="login.php" class="btn btn-primary btn-lg w-100 mb-3">
                                    <i class="fas fa-sign-in-alt me-2"></i>
                                    <?= ($book instanceof EBook) ? 'Login to Download' : 'Login to Borrow' ?>
                                </a>
                            <?php endif; ?>

                            <!-- Share Buttons -->
                            <div class="share-buttons">
                                <h6 class="text-muted mb-2">Share this book:</h6>
                                <div class="btn-group w-100" role="group">
                                    <button class="btn btn-outline-primary" onclick="shareBook('facebook')" title="Share on Facebook">
                                        <i class="fab fa-facebook-f"></i>
                                    </button>
                                    <button class="btn btn-outline-info" onclick="shareBook('twitter')" title="Share on Twitter">
                                        <i class="fab fa-twitter"></i>
                                    </button>
                                    <button class="btn btn-outline-success" onclick="shareBook('whatsapp')" title="Share on WhatsApp">
                                        <i class="fab fa-whatsapp"></i>
                                    </button>
                                    <button class="btn btn-outline-secondary" onclick="copyBookLink()" title="Copy Link">
                                        <i class="fas fa-link"></i>
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Book Information -->
                    <div class="book-info-section animate-on-scroll">
                        <!-- Title & Author -->
                        <div class="book-header mb-4">
                            <h1 class="book-title"><?= e($book->getTitle()) ?></h1>
                            <div class="book-author">
                                <span class="text-muted">by</span>
                                <a href="author-details.php?author=<?= urlencode($book->getAuthor()) ?>" 
                                   class="author-link">
                                    <?= e($book->getAuthor()) ?>
                                </a>
                                <span class="text-muted">• <?= $book->getYear() ?></span>
                            </div>
                        </div>

                        <!-- Rating & Reviews Summary -->
                        <div class="rating-summary mb-4">
                            <div class="row align-items-center">
                                <div class="col-md-6">
                                    <div class="rating-display">
                                        <?php if ($totalRatings > 0): ?>
                                            <div class="stars-large">
                                                <?php for ($i = 1; $i <= 5; $i++): ?>
                                                    <i class="fas fa-star <?= $i <= round($averageRating) ? 'text-warning' : 'text-muted' ?>"></i>
                                                <?php endfor; ?>
                                            </div>
                                            <span class="rating-text">
                                                <?= number_format($averageRating, 1) ?> out of 5 
                                                (<?= $totalRatings ?> <?= $totalRatings === 1 ? 'review' : 'reviews' ?>)
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
                                    <?php if (Auth::check()): ?>
                                        <button class="btn btn-outline-primary" data-bs-toggle="modal" data-bs-target="#reviewModal">
                                            <i class="fas fa-star me-1"></i>Write a Review
                                        </button>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>

                        <!-- Book Details Grid -->
                        <div class="book-details-grid mb-4">
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <div class="detail-card">
                                        <div class="detail-icon">
                                            <i class="fas fa-tag text-primary"></i>
                                        </div>
                                        <div class="detail-content">
                                            <h6>Category</h6>
                                            <a href="book-list.php?category=<?= urlencode($book->getCategory()) ?>" 
                                               class="category-link">
                                                <?= e($book->getCategory()) ?>
                                            </a>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="col-md-6">
                                    <div class="detail-card">
                                        <div class="detail-icon">
                                            <i class="fas fa-calendar text-success"></i>
                                        </div>
                                        <div class="detail-content">
                                            <h6>Publication Year</h6>
                                            <p><?= $book->getYear() ?></p>
                                        </div>
                                    </div>
                                </div>

                                <?php if (!($book instanceof EBook)): ?>
                                <div class="col-md-6">
                                    <div class="detail-card">
                                        <div class="detail-icon">
                                            <i class="fas fa-books text-info"></i>
                                        </div>
                                        <div class="detail-content">
                                            <h6>Copies Available</h6>
                                            <p><?= $book->getAvailableCopies() ?> of <?= $book->getTotalCopies() ?></p>
                                        </div>
                                    </div>
                                </div>
                                <?php else: ?>
                                <div class="col-md-6">
                                    <div class="detail-card">
                                        <div class="detail-icon">
                                            <i class="fas fa-file-pdf text-danger"></i>
                                        </div>
                                        <div class="detail-content">
                                            <h6>File Size</h6>
                                            <p><?= e($book->getFileSize() ?? 'Unknown') ?></p>
                                        </div>
                                    </div>
                                </div>
                                <?php endif; ?>

                                <div class="col-md-6">
                                    <div class="detail-card">
                                        <div class="detail-icon">
                                            <i class="fas fa-bookmark text-warning"></i>
                                        </div>
                                        <div class="detail-content">
                                            <h6>Book ID</h6>
                                            <p class="font-monospace"><?= e($book->getId()) ?></p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Book Description (Placeholder) -->
                        <div class="book-description mb-4">
                            <h5 class="section-title">
                                <i class="fas fa-align-left me-2"></i>Description
                            </h5>
                            <div class="description-content">
                                <p class="text-muted">
                                    Discover the captivating world of "<?= e($book->getTitle()) ?>" by <?= e($book->getAuthor()) ?>. 
                                    This remarkable <?= strtolower($book->getCategory()) ?> work, published in <?= $book->getYear() ?>, 
                                    offers readers an engaging journey through expertly crafted storytelling and profound insights.
                                </p>
                                <p class="text-muted">
                                    Whether you're a longtime fan of <?= e($book->getAuthor()) ?> or discovering their work for the first time, 
                                    this book promises to deliver an unforgettable reading experience that will leave you thinking long after 
                                    you've turned the final page.
                                </p>
                                <div class="description-tags">
                                    <span class="badge bg-light text-dark me-2"><?= e($book->getCategory()) ?></span>
                                    <span class="badge bg-light text-dark me-2"><?= $book->getYear() ?>s</span>
                                    <span class="badge bg-light text-dark"><?= e($book->getAuthor()) ?></span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Related Books Sidebar (RIGHT SIDE - 33%) -->
                <div class="col-lg-4 order-lg-2">
                    <?php if (!empty($relatedBooks)): ?>
                    <div class="related-books-sidebar animate-on-scroll">
                        <div class="section-header mb-4">
                            <h4 class="section-title">
                                <i class="fas fa-book-open me-2"></i>Related Books
                            </h4>
                        </div>
                        
                        <div class="related-books-list">
                            <?php foreach ($relatedBooks as $relatedBook): ?>
                                <div class="related-book-item mb-3">
                                    <a href="book-details.php?id=<?= $relatedBook->getId() ?>" class="text-decoration-none">
                                        <div class="card border-0 shadow-sm hover-shadow transition">
                                            <div class="row g-0">
                                                <div class="col-4">
                                                    <img src="<?= getBookCoverUrl($relatedBook, $relatedBook->getTitle(), $relatedBook->getAuthor()) ?>" 
                                                         class="img-fluid rounded-start" 
                                                         alt="<?= e($relatedBook->getTitle()) ?>"
                                                         style="height: 120px; object-fit: cover; width: 100%;"
                                                         onerror="this.src='<?= getDummyBookCover($relatedBook->getTitle(), $relatedBook->getAuthor(), 200, 300) ?>'">
                                                </div>
                                                <div class="col-8">
                                                    <div class="card-body p-2">
                                                        <h6 class="card-title mb-1 small" style="font-size: 0.85rem;">
                                                            <?= e(strlen($relatedBook->getTitle()) > 40 ? substr($relatedBook->getTitle(), 0, 40) . '...' : $relatedBook->getTitle()) ?>
                                                        </h6>
                                                        <p class="card-text text-muted mb-1" style="font-size: 0.75rem;">
                                                            <?= e($relatedBook->getAuthor()) ?>
                                                        </p>
                                                        <p class="card-text mb-0" style="font-size: 0.7rem;">
                                                            <span class="badge bg-primary"><?= e($relatedBook->getCategory()) ?></span>
                                                        </p>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </a>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Reviews Section -->
    <div class="reviews-section">
        <div class="container-fluid">
            <div class="section-header">
                <h3 class="section-title">
                    <i class="fas fa-comments me-2"></i>
                    Reader Reviews
                    <?php if ($totalRatings > 0): ?>
                        <span class="badge bg-primary ms-2"><?= $totalRatings ?></span>
                    <?php endif; ?>
                </h3>
            </div>

            <?php if (!empty($reviews)): ?>
                <div class="reviews-grid">
                    <?php foreach ($reviews as $review): ?>
                    <div class="review-card animate-on-scroll" id="review-<?= $review['id'] ?>">
                        <div class="review-header">
                            <div class="reviewer-info">
                                <div class="reviewer-avatar">
                                    <i class="fas fa-user-circle"></i>
                                </div>
                                <div class="reviewer-details">
                                    <h6 class="reviewer-name"><?= e($review['username']) ?></h6>
                                    <div class="review-stars">
                                        <?php for ($i = 1; $i <= 5; $i++): ?>
                                            <i class="fas fa-star <?= $i <= $review['rating'] ? 'text-warning' : 'text-muted' ?>"></i>
                                        <?php endfor; ?>
                                    </div>
                                </div>
                            </div>
                            <div class="review-actions">
                                <small class="text-muted me-3">
                                    <?= date('M j, Y', strtotime($review['created_at'])) ?>
                                </small>
                                <?php if (Auth::check() && Auth::id() == $review['user_id']): ?>
                                    <button class="btn btn-sm btn-outline-primary me-1" onclick="editReview(<?= $review['id'] ?>, <?= $review['rating'] ?>, '<?= addslashes($review['review_text']) ?>')">
                                        <i class="fas fa-edit"></i>
                                    </button>
                                    <button class="btn btn-sm btn-outline-danger" onclick="deleteReview(<?= $review['id'] ?>)">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                <?php endif; ?>
                            </div>
                        </div>
                        <?php if (!empty($review['review_text'])): ?>
                        <div class="review-content">
                            <p><?= e($review['review_text']) ?></p>
                        </div>
                        <?php endif; ?>
                    </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <div class="empty-reviews">
                    <div class="text-center py-5">
                        <i class="fas fa-comments fa-3x text-muted mb-3"></i>
                        <h5 class="text-muted">No reviews yet</h5>
                        <p class="text-muted">Be the first to share your thoughts about this book!</p>
                        <?php if (Auth::check()): ?>
                            <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#reviewModal">
                                <i class="fas fa-star me-1"></i>Write the First Review
                            </button>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Related Books Section -->
    <?php if (!empty($relatedBooks)): ?>
    <div class="related-books-section">
        <div class="container-fluid">
            <div class="section-header">
                <h3 class="section-title">
                    <i class="fas fa-book-open me-2"></i>You Might Also Like
                </h3>
            </div>
            
            <div class="row row-cols-1 row-cols-sm-2 row-cols-md-3 row-cols-lg-4 g-4">
                <?php foreach ($relatedBooks as $relatedBook): ?>
                    <?php 
                    $showBorrow = Auth::check();
                    $book = $relatedBook; // For compatibility with book-card.php
                    include 'views/book-card.php'; 
                    ?>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
    <?php endif; ?>
</div>

<?php $book = $currentBook; ?>

<!-- Review Modal -->
<?php if (Auth::check()): ?>
<div class="modal fade" id="reviewModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="fas fa-star me-2"></i>Write a Review
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" id="reviewForm">
                <div class="modal-body">
                    <input type="hidden" name="action" value="review">
                    <input type="hidden" name="review_id" id="reviewId" value="">
                    
                    <div class="mb-4">
                        <h6 class="mb-2">Rating *</h6>
                        <div class="rating-input">
                            <?php for ($i = 1; $i <= 5; $i++): ?>
                                <input type="radio" name="rating" value="<?= $i ?>" id="star<?= $i ?>" required>
                                <label for="star<?= $i ?>" class="star-label">
                                    <i class="fas fa-star"></i>
                                </label>
                            <?php endfor; ?>
                        </div>
                        <small class="text-muted">Click on a star to rate this book</small>
                    </div>
                    
                    <div class="mb-3">
                        <label for="reviewText" class="form-label">Your Review (Optional)</label>
                        <textarea class="form-control" id="reviewText" name="review_text" rows="4" 
                                  placeholder="Share your thoughts about this book..."></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-paper-plane me-1"></i>Submit Review
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
<?php endif; ?>

<script>
// Share functionality
function shareBook(platform) {
    const url = encodeURIComponent(window.location.href);
    const title = encodeURIComponent('<?= addslashes($book->getTitle()) ?> by <?= addslashes($book->getAuthor()) ?>');
    
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

function copyBookLink() {
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

// Edit review function
function editReview(reviewId, rating, reviewText) {
    // Set the review ID in a hidden field
    document.getElementById('reviewId').value = reviewId;
    
    // Set the rating
    const ratingInput = document.querySelector(`input[name="rating"][value="${rating}"]`);
    if (ratingInput) {
        ratingInput.checked = true;
        // Update star labels
        const starLabels = document.querySelectorAll('.rating-input .star-label');
        starLabels.forEach((l, i) => {
            l.classList.toggle('selected', i < rating);
        });
    }
    
    // Set the review text
    document.getElementById('reviewText').value = reviewText;
    
    // Change modal title
    document.querySelector('#reviewModal .modal-title').textContent = 'Edit Your Review';
    
    // Change submit button text
    document.querySelector('#reviewModal button[type="submit"]').innerHTML = '<i class="fas fa-save me-1"></i>Update Review';
    
    // Show the modal
    const modal = new bootstrap.Modal(document.getElementById('reviewModal'));
    modal.show();
}

// Delete review function
function deleteReview(reviewId) {
    console.log('deleteReview function called with reviewId:', reviewId);
    console.log('Swal object:', typeof Swal);
    
    if (typeof Swal === 'undefined') {
        alert('ERROR: SweetAlert2 is not loaded!');
        return;
    }
    
    Swal.fire({
        title: 'Delete Review?',
        text: 'Are you sure you want to delete this review? This action cannot be undone.',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#d33',
        cancelButtonColor: '#6c757d',
        confirmButtonText: 'Yes, delete it!',
        cancelButtonText: 'Cancel'
    }).then((result) => {
        console.log('Swal result:', result);
        
        if (result.isConfirmed) {
            console.log('User confirmed deletion, sending request...');
            
            // Send delete request
            fetch('api/delete_review.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({ review_id: reviewId })
            })
            .then(response => {
                console.log('Response status:', response.status);
                return response.json();
            })
            .then(data => {
                console.log('Response data:', data);
                
                if (data.success) {
                    Swal.fire({
                        icon: 'success',
                        title: 'Deleted!',
                        text: 'Your review has been deleted.',
                        confirmButtonColor: '#2e8a40',
                        timer: 1500,
                        showConfirmButton: false
                    }).then(() => {
                        console.log('Reloading page...');
                        location.reload();
                    });
                } else {
                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: data.message || 'Failed to delete review',
                        confirmButtonColor: '#2e8a40'
                    });
                }
            })
            .catch(error => {
                console.error('Fetch error:', error);
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: 'An error occurred while deleting the review',
                    confirmButtonColor: '#2e8a40'
                });
            });
        } else {
            console.log('User cancelled deletion');
        }
    });
}

// Rating input functionality
document.addEventListener('DOMContentLoaded', function() {
    const ratingInputs = document.querySelectorAll('.rating-input input[type="radio"]');
    const starLabels = document.querySelectorAll('.rating-input .star-label');
    
    starLabels.forEach((label, index) => {
        label.addEventListener('mouseenter', () => {
            starLabels.forEach((l, i) => {
                l.classList.toggle('hover', i <= index);
            });
        });
        
        label.addEventListener('mouseleave', () => {
            starLabels.forEach(l => l.classList.remove('hover'));
        });
        
        label.addEventListener('click', () => {
            starLabels.forEach((l, i) => {
                l.classList.toggle('selected', i <= index);
            });
        });
    });
    
    // Reset modal when closed (only if modal exists)
    const reviewModal = document.getElementById('reviewModal');
    if (reviewModal) {
        reviewModal.addEventListener('hidden.bs.modal', function () {
            document.getElementById('reviewId').value = '';
            document.getElementById('reviewForm').reset();
            document.querySelector('#reviewModal .modal-title').textContent = 'Write a Review';
            document.querySelector('#reviewModal button[type="submit"]').innerHTML = '<i class="fas fa-paper-plane me-1"></i>Submit Review';
            starLabels.forEach(l => l.classList.remove('selected'));
        });
    }
});
</script>

<?php include 'views/footer.php'; ?>
