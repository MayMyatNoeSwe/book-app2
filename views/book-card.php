<?php

use App\Auth;

$showBorrow = $showBorrow ?? Auth::check();
?>

<div class="col mb-4 animate-on-scroll stagger-item">
    <div class="card h-100 shadow-sm border-0 hover-shadow transition">
        <!-- Cover Image -->
        <div class="text-center pt-4 px-3">
            <?php 
            $coverUrl = getBookCoverUrl($book, $book->getTitle(), $book->getAuthor());
            ?>
            <img src="<?= $coverUrl ?>"
                alt="<?= e($book->getTitle()) ?> cover" class="card-img-top rounded shadow-sm"
                style="width: 160px; height: 240px; object-fit: cover;"
                onerror="this.src='<?= getDummyBookCover($book->getTitle(), $book->getAuthor(), 160, 240) ?>';">
        </div>

        <!-- Card Body -->
        <div class="card-body d-flex flex-column pb-4">
            <h5 class="card-title text-truncate fw-bold"><?= e($book->getTitle()) ?></h5>
            <p class="card-text text-muted small mb-2">
                by <?= e($book->getAuthor()) ?> (<?= $book->getYear() ?>)
            </p>

            <!-- Category Badge -->
            <p class="mb-3">
                <span class="badge bg-secondary"><?= e($book->getCategory()) ?></span>
            </p>

            <!-- Availability Info or EBook Info -->
            <div class="mt-auto">
                <p class="mb-3 text-center">
                    <?php if ($book instanceof \App\EBook): ?>
                    <span class="text-primary fw-bold fs-5">Digital E-Book</span><br>
                    <small class="text-muted">Size: <?= e($book->getFileSize()) ?></small>
                    <?php elseif ($book->isAvailable()): ?>
                    <span class="text-success fw-bold fs-5">Available</span><br>
                    <small class="text-muted"><?= $book->getAvailableCopies() ?> of <?= $book->getTotalCopies() ?>
                        copies</small>
                    <?php else: ?>
                    <span class="text-danger fw-bold fs-5">Out of Stock</span><br>
                    <small class="text-muted">Available for Pre-order</small>
                    <?php endif; ?>
                </p>
                
                <!-- Action Buttons -->
                <?php if (Auth::check()): ?>
                <?php if ($book instanceof \App\EBook): ?>
                    <?php if ($book->getDownloadLink()): ?>
                    <a href="<?= e($book->getDownloadLink()) ?>" target="_blank" class="btn btn-primary w-100 fw-bold">
                        📥 Download PDF
                    </a>
                    <?php else: ?>
                    <button class="btn btn-secondary w-100" disabled>No Link Available</button>
                    <?php endif; ?>
                <?php else: ?>
                    <?php 
                    if (!isset($library)) {
                        $library = new \App\Library();
                    }
                    $isBorrowing = $library->isCurrentlyBorrowing(Auth::id(), $book->getId()); 
                    ?>

                    <?php if ($isBorrowing): ?>
                    <button class="btn btn-secondary w-100 mb-2" disabled>
                        📖 Currently Reading
                    </button>
                    <form method="POST" action="book-details.php">
                        <input type="hidden" name="id" value="<?= e($book->getId()) ?>">
                        <input type="hidden" name="action" value="return">
                        <button type="submit" class="btn btn-sm btn-outline-danger w-100">
                            Return Now
                        </button>
                    </form>
                    <?php elseif ($book->isAvailable()): ?>
                    <!-- Borrow Form -->
                    <form method="POST" action="book-details.php">
                        <input type="hidden" name="id" value="<?= e($book->getId()) ?>">
                        <input type="hidden" name="action" value="borrow">
                        <button type="submit" class="btn btn-primary w-100 fw-bold mb-2">
                            Borrow Now
                        </button>
                    </form>
                    <button onclick="addToCart('<?= e($book->getId()) ?>')" class="btn btn-outline-primary w-100 btn-sm fw-bold">
                        <i class="fas fa-shopping-cart me-1"></i> Add to Cart
                    </button>
                    <?php else: ?>
                    <!-- Reserve & Pre-order -->
                    <form method="POST" action="book-details.php" class="mb-2">
                        <input type="hidden" name="id" value="<?= e($book->getId()) ?>">
                        <input type="hidden" name="action" value="reserve">
                        <button type="submit" class="btn btn-outline-info w-100 fw-bold">
                            Reserve Now
                        </button>
                    </form>
                    <button onclick="addToCart('<?= e($book->getId()) ?>')" class="btn btn-primary w-100 fw-bold" style="background:#524f7d; border-color:#524f7d;">
                        <i class="fas fa-calendar-check me-1"></i> Pre-order
                    </button>
                    <?php endif ?>
                <?php endif ?>
                <?php else: ?>
                <!-- Guest User -->
                <div class="guest-actions">
                    <?php if ($book instanceof \App\EBook): ?>
                        <button type="button" onclick="showLoginAlert('Please login to download this e-book.')" class="btn btn-primary w-100 fw-bold">
                            <i class="fas fa-sign-in-alt me-1"></i> Download Now
                        </button>
                    <?php elseif ($book->isAvailable()): ?>
                        <button type="button" onclick="showLoginAlert('Please login to borrow this book.')" class="btn btn-primary w-100 fw-bold mb-2">
                            <i class="fas fa-book me-1"></i> Borrow Now
                        </button>
                        <button onclick="addToCart('<?= e($book->getId()) ?>')" class="btn btn-outline-primary w-100 btn-sm fw-bold">
                            <i class="fas fa-shopping-cart me-1"></i> Add to Cart
                        </button>
                    <?php else: ?>
                        <button type="button" onclick="showLoginAlert('Please login to borrow this book.')" class="btn btn-outline-info w-100 fw-bold mb-2">
                            <i class="fas fa-sign-in-alt me-1"></i> Borrow Now
                        </button>
                        <button onclick="addToCart('<?= e($book->getId()) ?>')" class="btn btn-primary w-100 fw-bold" style="background:#524f7d; border-color:#524f7d;">
                            <i class="fas fa-calendar-check me-1"></i> Pre-order
                        </button>
                    <?php endif ?>
                </div>
                <?php endif ?>

                <!-- Always Show Details Link -->
                <div class="mt-2">
                    <a href="book-details.php?id=<?= e($book->getId()) ?>"
                        class="btn btn-outline-secondary w-100 btn-sm">
                        View Details
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>