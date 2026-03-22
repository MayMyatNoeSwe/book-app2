<?php
$pageTitle = "Home";

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Set proper content type
header('Content-Type: text/html; charset=utf-8');

require_once 'includes/sessions.php';
require_once 'vendor/autoload.php';
require_once 'includes/env_loader.php';
require_once 'includes/functions.php';

use App\Auth;
use App\Library;

$library = new Library();

// Pagination Settings
$page = 1;
$limit = 12;
$offset = 0;

// Filter Parameters
$category = $_GET['category'] ?? null;
$search = $_GET['search'] ?? null;

// Clean and validate search parameter
if ($search !== null) {
    $search = trim($search);
    if ($search === '') {
        $search = null;
    }
}

// Clean and validate category parameter
if ($category !== null) {
    $category = trim($category);
    if ($category === '') {
        $category = null;
    }
}

// Validate Category
if ($category && !in_array($category, getCategories())) {
    $category = null;
}

$allCategories = getCategories();

// Fetch Initial Batch
$books = $library->getBooksPaginated($limit, $offset, $category, $search);
$totalBooks = $library->countBooks($category, $search);

// Fetch data for home page features (only when not searching/filtering)
$bestSellingBooks = [];
$topAuthors = [];
$recentReviews = [];
$recentlyPublishedBooks = [];
$bookStats = [];

if (!$category && !$search) {
    try {
        $bestSellingBooks = $library->getBestSellingBooks(10);
        $topAuthors = $library->getTopAuthors(6);
        $recentReviews = $library->getRecentReviews(6);
        $recentlyPublishedBooks = $library->getRecentlyPublishedBooks(10);
        $bookStats = $library->getBookStats();
    } catch (Exception $e) {
        // If there's an error (like missing authors table), show a message
        if (strpos($e->getMessage(), 'authors') !== false) {
            setFlashMessage('To see author photos and enhanced features, please run the setup script: <a href="setup_authors_table.php">Setup Authors Table</a>', 'info');
        }
        // Continue with basic functionality
        $bestSellingBooks = [];
        $topAuthors = [];
        $recentReviews = [];
        $recentlyPublishedBooks = [];
        $bookStats = ['total_books' => 0, 'total_authors' => 0, 'total_reviews' => 0, 'monthly_borrows' => 0];
    }
}

// Set Page Title
if ($search) {
    $pageTitle = "Search: " . $search;
} elseif ($category) {
    $pageTitle = $category . " Books";
}

include 'views/header.php';
?>
</div> <!-- Close default container from header.php -->

<?php 
// Fetch featured book for hero (fallback to a dummy one if empty)
$featuredBook = (!empty($bestSellingBooks)) ? $bestSellingBooks[0] : null;

// Get metadata for hero
$heroTitle = $featuredBook ? $featuredBook->getTitle() : "Design";
$heroAuthor = $featuredBook ? $featuredBook->getAuthor() : "Keith Granet";
$heroCategory = $featuredBook ? $featuredBook->getCategory() : "Design Thinking";
$heroPrice = "$32.78"; // Dummy price if not in DB
$heroRating = $featuredBook ? $featuredBook->getAverageRating() : 4.5;
$imageApiCover = function ($title, $author) {
    return getDummyBookCover($title, $author, 700, 1050);
};

$heroCover = $featuredBook
    ? $imageApiCover($featuredBook->getTitle(), $featuredBook->getAuthor())
    : getDummyBookCover('Design', 'Keith Granet', 700, 1050);
?>

<section class="home-hero">
    <div class="hero-orb orb-1"></div>
    <div class="hero-orb orb-2"></div>
    <div class="hero-orb orb-3"></div>
    <div class="hero-gridline"></div>
    <div class="container">
        <div class="row align-items-center g-5">
            <div class="col-lg-6">
                <div class="hero-eyebrow reveal">Curated Spotlight</div>
                <h1 class="hero-title reveal" data-delay="0.08"><?= e($heroTitle) ?></h1>
                <p class="hero-lead reveal" data-delay="0.16">
                    A premium reading experience designed around clarity, craft, and the books that shape your next idea.
                </p>
                <div class="hero-cta-group reveal" data-delay="0.24">
                    <a href="<?= baseUrl() ?>/cart.php?add=<?= $featuredBook ? $featuredBook->getId() : 1 ?>" class="btn btn-primary hero-btn">
                        <i class="fas fa-shopping-cart me-2"></i>Add to Cart
                    </a>
                    <a href="book-details.php?id=<?= $featuredBook ? $featuredBook->getId() : 1 ?>" class="btn btn-outline-dark hero-btn-ghost">
                        Read Details
                    </a>
                </div>
                <form class="hero-search reveal" data-delay="0.28" action="index.php" method="get">
                    <div class="hero-search-field">
                        <i class="fas fa-search"></i>
                        <input type="text" name="search" placeholder="Search titles, authors, or keywords" value="<?= e($search ?? '') ?>">
                    </div>
                    <div class="hero-search-field">
                        <i class="fas fa-tags"></i>
                        <select name="category">
                            <option value="">All Categories</option>
                            <?php foreach ($allCategories as $cat): ?>
                                <option value="<?= e($cat) ?>" <?= $category === $cat ? 'selected' : '' ?>><?= e($cat) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <button type="submit" class="btn hero-search-btn">Explore</button>
                </form>
                <div class="hero-meta-grid reveal" data-delay="0.32">
                    <div class="hero-meta-card">
                        <span class="meta-label">Author</span>
                        <span class="meta-value"><?= e($heroAuthor) ?></span>
                    </div>
                    <div class="hero-meta-card">
                        <span class="meta-label">Category</span>
                        <span class="meta-value"><?= e($heroCategory) ?></span>
                    </div>
                    <div class="hero-meta-card">
                        <span class="meta-label">Format</span>
                        <span class="meta-value">Print + Digital</span>
                    </div>
                </div>
            </div>
            <div class="col-lg-6">
                <div class="hero-media">
                    <div class="hero-cover-stack hero-parallax" data-depth="18">
                        <img src="<?= $heroCover ?>" 
                             alt="<?= e($heroTitle) ?>" 
                             class="hero-cover"
                             onerror="this.src='<?= getDummyBookCover($heroTitle, $heroAuthor, 700, 1050) ?>'">
                        <span class="hero-price-tag"><?= $heroPrice ?></span>
                    </div>
                    <div class="hero-float-card hero-parallax" data-depth="28">
                        <div class="float-title">Readers' Rating</div>
                        <div class="float-rating">
                            <div class="stars-new">
                                <?php 
                                $ratingInt = floor($heroRating);
                                for ($i = 1; $i <= 5; $i++): 
                                    if ($i <= $ratingInt): ?>
                                        <i class="fas fa-star"></i>
                                    <?php elseif ($i == $ratingInt + 1 && $heroRating > $ratingInt): ?>
                                        <i class="fas fa-star-half-alt"></i>
                                    <?php else: ?>
                                        <i class="far fa-star"></i>
                                    <?php endif; 
                                endfor; ?>
                            </div>
                            <span class="float-score"><?= number_format($heroRating, 1) ?></span>
                        </div>
                        <div class="float-subtext">1.4k verified reviews</div>
                    </div>
                    <div class="hero-float-chip hero-parallax" data-depth="12">
                        <i class="fas fa-award me-2"></i>Top pick this week
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<?php if (!empty($allCategories)): ?>
<section class="category-ribbon">
    <div class="ribbon-track">
        <?php foreach (array_merge($allCategories, $allCategories) as $cat): ?>
            <a href="book-list.php?category=<?= urlencode($cat) ?>" class="ribbon-chip text-decoration-none">
                <i class="fas fa-book-open me-2"></i><?= e($cat) ?>
            </a>
        <?php endforeach; ?>
    </div>
</section>
<?php endif; ?>

<div class="container pb-5">
    <!-- Re-open container -->

    <?php displayFlashMessage(); ?>

    <?php if (!$category && !$search): ?>
    <!-- Home Page Features -->
    
    <!-- Statistics Cards -->
    <section class="home-stats">
        <div class="container">
            <div class="stats-grid">
                <div class="stat-card reveal" data-delay="0.05">
                    <div class="stat-icon"><i class="fas fa-book-open"></i></div>
                    <div class="stat-info">
                        <div class="stat-number" data-count="<?= (int)($bookStats['total_books'] ?? 0) ?>">0</div>
                        <p class="stat-label">BOOKS IN OUR<br>CATALOG</p>
                    </div>
                </div>
                <div class="stat-card reveal" data-delay="0.12">
                    <div class="stat-icon accent-mint"><i class="fas fa-pen-nib"></i></div>
                    <div class="stat-info">
                        <div class="stat-number" data-count="<?= (int)($bookStats['total_authors'] ?? 0) ?>">0</div>
                        <p class="stat-label">AUTHORS<br>REPRESENTED</p>
                    </div>
                </div>
                <div class="stat-card reveal" data-delay="0.19">
                    <div class="stat-icon accent-gold"><i class="fas fa-star"></i></div>
                    <div class="stat-info">
                        <div class="stat-number" data-count="<?= (int)($bookStats['total_reviews'] ?? 0) ?>">0</div>
                        <p class="stat-label">READER REVIEWS</p>
                    </div>
                </div>
                <div class="stat-card reveal" data-delay="0.26">
                    <div class="stat-icon accent-slate"><i class="fas fa-arrow-trend-up"></i></div>
                    <div class="stat-info">
                        <div class="stat-number" data-count="<?= (int)($bookStats['monthly_borrows'] ?? 0) ?>">0</div>
                        <p class="stat-label">MONTHLY BORROWS</p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Best Selling Books -->
    <?php if (!empty($bestSellingBooks)): ?>
    <section class="home-section books-section">
        <div class="container">
            <div class="section-header reveal">
                <div>
                    <h2 class="section-title">Popular Books</h2>
                    <p class="section-subtitle">Discover our most borrowed titles</p>
                </div>
                <a href="book-list.php?sort=borrowed&order=desc" class="btn btn-outline-dark">View All</a>
            </div>
            
            <div class="books-strip-wrap reveal">
                <button class="strip-nav strip-prev" type="button" aria-label="Scroll left">
                    <i class="fas fa-chevron-left"></i>
                </button>
                <div class="books-strip" data-strip="popular">
                <?php foreach (array_slice($bestSellingBooks, 0, 12) as $index => $book): ?>
                <article class="strip-card">
                    <a class="strip-cover" href="book-details.php?id=<?= e($book->getId()) ?>">
                        <img src="<?= $imageApiCover($book->getTitle(), $book->getAuthor()) ?>"
                            alt="<?= e($book->getTitle()) ?>"
                            loading="lazy"
                            onerror="this.src='<?= getDummyBookCover($book->getTitle(), $book->getAuthor(), 220, 320) ?>'">
                    </a>
                    <div class="strip-meta">
                        <?php if ($book->getAverageRating() > 0): ?>
                        <div class="strip-rating">
                            <?php for ($i = 1; $i <= 5; $i++): ?>
                                <i class="fas fa-star <?= $i <= round($book->getAverageRating()) ? 'text-warning' : 'text-muted' ?>"></i>
                            <?php endfor; ?>
                        </div>
                        <?php endif; ?>
                        <h6 class="strip-title" title="<?= e($book->getTitle()) ?>"><?= e($book->getTitle()) ?></h6>
                        <p class="strip-author">By <?= e($book->getAuthor()) ?></p>
                        <a href="book-details.php?id=<?= e($book->getId()) ?>" class="btn strip-cta">Buy Now</a>
                    </div>
                </article>
                <?php endforeach; ?>
                </div>
                <button class="strip-nav strip-next" type="button" aria-label="Scroll right">
                    <i class="fas fa-chevron-right"></i>
                </button>
            </div>
        </div>
    </section>
    <?php endif; ?>

    <!-- Top Authors -->
    <?php if (!empty($topAuthors)): ?>
    <section class="home-section authors-section">
        <div class="container">
            <div class="section-header reveal">
                <div>
                    <h2 class="section-title">Featured Authors</h2>
                    <p class="section-subtitle">Meet our most popular writers</p>
                </div>
                <a href="author-list.php" class="btn btn-outline-dark">View All</a>
            </div>
            
            <div class="row row-cols-2 row-cols-md-3 row-cols-lg-6 g-4">
                <?php foreach (array_slice($topAuthors, 0, 6) as $index => $author): ?>
                <div class="col reveal" data-delay="<?= 0.05 + ($index % 6) * 0.05 ?>">
                    <div class="author-card-modern">
                        <a href="author-details.php?author=<?= urlencode($author['author']) ?>" class="text-decoration-none">
                            <div class="author-avatar-wrapper">
                                <?php 
                                $authorPhoto = $author['author_photo'] ?? null;
                                $authorName = $author['author'];
                                $avatarUrl = getAuthorAvatarUrl($authorName, 150);
                                
                                if ($authorPhoto && file_exists(__DIR__ . '/public/uploads/authors/' . $authorPhoto)): ?>
                                    <img src="<?= baseUrl() ?>/public/uploads/authors/<?= e($authorPhoto) ?>" 
                                         alt="<?= e($authorName) ?>" 
                                         loading="lazy"
                                         class="author-avatar">
                                <?php else: ?>
                                    <img src="<?= $avatarUrl ?>" 
                                         alt="<?= e($authorName) ?>" 
                                         loading="lazy"
                                         class="author-avatar">
                                <?php endif; ?>
                            </div>
                            <h6 class="author-name"><?= e($authorName) ?></h6>
                            <p class="author-stats"><?= $author['book_count'] ?> Books</p>
                        </a>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </section>
    <?php endif; ?>

    <!-- Recent Reviews -->
    <?php if (!empty($recentReviews)): ?>
    <section class="home-section reviews-section">
        <div class="container">
            <div class="section-header reveal">
                <div>
                    <h2 class="section-title">What Readers Say</h2>
                    <p class="section-subtitle">Recent reviews from our community</p>
                </div>
                <a href="book-list.php" class="btn btn-outline-dark">View All</a>
            </div>
            
            <div class="row row-cols-1 row-cols-md-2 row-cols-lg-3 g-4">
                <?php foreach (array_slice($recentReviews, 0, 6) as $review): ?>
                <div class="col reveal" data-delay="0.08">
                    <div class="review-card-modern">
                        <div class="review-rating">
                            <?php for ($i = 1; $i <= 5; $i++): ?>
                                <i class="fas fa-star <?= $i <= $review['rating'] ? 'text-warning' : 'text-muted' ?>"></i>
                            <?php endfor; ?>
                        </div>
                        <p class="review-text">
                            "<?= e(substr($review['review_text'], 0, 120)) ?><?= strlen($review['review_text']) > 120 ? '...' : '' ?>"
                        </p>
                        <div class="review-footer">
                            <div class="reviewer-info">
                                <div class="reviewer-avatar">
                                    <?= strtoupper(substr($review['username'], 0, 1)) ?>
                                </div>
                                <div>
                                    <strong class="reviewer-name"><?= e($review['username']) ?></strong>
                                    <p class="review-date"><?= date('M j, Y', strtotime($review['created_at'])) ?></p>
                                </div>
                            </div>
                            <a href="book-details.php?id=<?= e($review['book_id']) ?>" class="book-link">
                                <?= e(substr($review['title'], 0, 30)) ?><?= strlen($review['title']) > 30 ? '...' : '' ?>
                            </a>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </section>
    <?php endif; ?>

    <!-- Recently Published Books -->
    <?php if (!empty($recentlyPublishedBooks)): ?>
    <section class="home-section books-section alt-surface">
        <div class="container">
            <div class="section-header reveal">
                <div>
                    <h2 class="section-title">New Arrivals</h2>
                    <p class="section-subtitle">Fresh off the press</p>
                </div>
                <a href="book-list.php?sort=recent&order=desc" class="btn btn-outline-dark">View All</a>
            </div>
            
            <div class="books-strip-wrap reveal">
                <button class="strip-nav strip-prev" type="button" aria-label="Scroll left">
                    <i class="fas fa-chevron-left"></i>
                </button>
                <div class="books-strip" data-strip="new">
                <?php foreach (array_slice($recentlyPublishedBooks, 0, 12) as $index => $book): ?>
                <article class="strip-card">
                    <a class="strip-cover" href="book-details.php?id=<?= e($book->getId()) ?>">
                        <img src="<?= $imageApiCover($book->getTitle(), $book->getAuthor()) ?>"
                            alt="<?= e($book->getTitle()) ?>"
                            loading="lazy"
                            onerror="this.src='<?= getDummyBookCover($book->getTitle(), $book->getAuthor(), 220, 320) ?>'">
                    </a>
                    <div class="strip-meta">
                        <span class="new-badge">New</span>
                        <h6 class="strip-title" title="<?= e($book->getTitle()) ?>"><?= e($book->getTitle()) ?></h6>
                        <p class="strip-author">By <?= e($book->getAuthor()) ?></p>
                        <a href="book-details.php?id=<?= e($book->getId()) ?>" class="btn strip-cta">Buy Now</a>
                    </div>
                </article>
                <?php endforeach; ?>
                </div>
                <button class="strip-nav strip-next" type="button" aria-label="Scroll right">
                    <i class="fas fa-chevron-right"></i>
                </button>
            </div>
        </div>
    </section>
    <?php endif; ?>

    <?php endif; ?>

    <!-- Search/Filter Results -->
    <?php if ($search || $category): ?>
    <section class="search-results-section">
        <div class="container">
            <div class="search-results-header">
                <div>
                    <h2 class="results-title">
                        <?php if ($search): ?>
                            <i class="fas fa-search me-2"></i>Search Results
                        <?php elseif ($category): ?>
                            <i class="fas fa-filter me-2"></i><?= e($category) ?> Books
                        <?php endif; ?>
                    </h2>
                    <p class="results-subtitle">
                        <?php if ($search): ?>
                            Showing results for "<strong><?= e($search) ?></strong>"
                        <?php endif; ?>
                        - Found <strong><?= $totalBooks ?></strong> book<?= $totalBooks != 1 ? 's' : '' ?>
                    </p>
                </div>
                <a href="index.php" class="btn btn-outline-danger">
                    <i class="fas fa-times me-2"></i>Clear Filters
                </a>
            </div>

            <?php if (empty($books)): ?>
                <div class="no-results">
                    <i class="fas fa-search fa-4x text-muted mb-4"></i>
                    <h3>No books found</h3>
                    <p class="text-muted">Try adjusting your search terms or browse all books</p>
                    <a href="index.php" class="btn btn-primary mt-3">
                        <i class="fas fa-home me-2"></i>Back to Home
                    </a>
                </div>
            <?php else: ?>
                <div class="row row-cols-2 row-cols-md-3 row-cols-lg-4 g-4">
                    <?php foreach ($books as $book): ?>
                        <div class="col">
                            <?php $isPremium = $book->getAverageRating() >= 4.7; ?>
                            <div class="book-card <?= $isPremium ? 'premium' : '' ?>">
                                <div class="book-cover-wrapper">
                                    <?php if ($book->getCategory()): ?>
                                        <span class="category-badge"><?= e($book->getCategory()) ?></span>
                                    <?php endif; ?>
                                    <img src="<?= $imageApiCover($book->getTitle(), $book->getAuthor()) ?>" 
                                         class="book-cover" 
                                         alt="<?= e($book->getTitle()) ?>"
                                         loading="lazy"
                                         onerror="this.src='<?= getDummyBookCover($book->getTitle(), $book->getAuthor(), 300, 400) ?>'">
                                    <div class="book-overlay">
                                        <a href="book-details.php?id=<?= $book->getId() ?>" class="btn btn-light btn-sm">
                                            View Details
                                        </a>
                                    </div>
                                </div>
                                <div class="book-info">
                                    <h6 class="book-title" title="<?= e($book->getTitle()) ?>">
                                        <?= e($book->getTitle()) ?>
                                    </h6>
                                    <p class="book-author">
                                        <a href="author-details.php?author=<?= urlencode($book->getAuthor()) ?>">
                                            By <?= e($book->getAuthor()) ?>
                                        </a>
                                    </p>
                                    <?php if ($book->getYear()): ?>
                                        <span class="badge bg-secondary"><?= e($book->getYear()) ?></span>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </section>
    <?php endif; ?>

   

    <?php include 'views/footer.php'; ?>

    <!-- Premium Motion Script -->
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const revealTargets = new Set();
            const revealObserver = new IntersectionObserver((entries) => {
                entries.forEach((entry) => {
                    if (entry.isIntersecting) {
                        entry.target.classList.add('is-visible');
                        revealObserver.unobserve(entry.target);
                    }
                });
            }, { threshold: 0.12, rootMargin: '0px 0px -40px 0px' });

            const registerReveal = (elements) => {
                elements.forEach((el) => {
                    if (!revealTargets.has(el)) {
                        revealTargets.add(el);
                        const delay = parseFloat(el.dataset.delay || '0');
                        el.style.setProperty('--reveal-delay', `${delay}s`);
                        revealObserver.observe(el);
                    }
                });
            };

            registerReveal(document.querySelectorAll('.reveal'));

            // Count-up animation for stats
            const countObserver = new IntersectionObserver((entries) => {
                entries.forEach((entry) => {
                    if (!entry.isIntersecting) return;
                    const el = entry.target;
                    const target = parseInt(el.dataset.count || '0', 10);
                    const duration = 1400;
                    const start = performance.now();

                    const tick = (now) => {
                        const progress = Math.min((now - start) / duration, 1);
                        const value = Math.floor(progress * target);
                        el.textContent = value.toLocaleString();
                        if (progress < 1) requestAnimationFrame(tick);
                    };
                    requestAnimationFrame(tick);
                    countObserver.unobserve(el);
                });
            }, { threshold: 0.5 });

            document.querySelectorAll('.stat-number[data-count]').forEach((el) => countObserver.observe(el));

            // Hero parallax
            const hero = document.querySelector('.home-hero');
            if (hero) {
                const layers = hero.querySelectorAll('.hero-parallax');
                hero.addEventListener('mousemove', (event) => {
                    const rect = hero.getBoundingClientRect();
                    const x = (event.clientX - rect.left) / rect.width - 0.5;
                    const y = (event.clientY - rect.top) / rect.height - 0.5;
                    layers.forEach((layer) => {
                        const depth = parseFloat(layer.dataset.depth || '10');
                        layer.style.transform = `translate(${x * depth}px, ${y * depth}px)`;
                    });
                });

                hero.addEventListener('mouseleave', () => {
                    layers.forEach((layer) => {
                        layer.style.transform = 'translate(0, 0)';
                    });
                });
            }

            window.BookLibraryAnimations = {
                observeNewElements: (elements) => {
                    if (!elements) return;
                    const list = Array.from(elements).flatMap((el) =>
                        el.classList.contains('reveal') ? [el] : Array.from(el.querySelectorAll?.('.reveal') || [])
                    );
                    registerReveal(list);
                }
            };

            // Strip slider controls
            document.querySelectorAll('.books-strip-wrap').forEach((wrap) => {
                const strip = wrap.querySelector('.books-strip');
                const prev = wrap.querySelector('.strip-prev');
                const next = wrap.querySelector('.strip-next');
                if (!strip || !prev || !next) return;

                // Ensure horizontal layout even if grid is unsupported
                const computedDisplay = getComputedStyle(strip).display;
                if (computedDisplay !== 'grid') {
                    strip.classList.add('is-flex');
                }

                const scrollByCard = () => {
                    const card = strip.querySelector('.strip-card');
                    const styles = getComputedStyle(strip);
                    const gap = parseFloat(styles.columnGap || styles.gap || '0');
                    return card ? card.getBoundingClientRect().width + gap : 360;
                };

                prev.addEventListener('click', () => {
                    strip.scrollBy({ left: -scrollByCard(), behavior: 'smooth' });
                });

                next.addEventListener('click', () => {
                    strip.scrollBy({ left: scrollByCard(), behavior: 'smooth' });
                });
            });
        });
    </script>
