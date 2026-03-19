<?php
// views/footer.php
use App\Auth;
?>
</div> <!-- End container -->

<!-- Bootstrap JS Bundle -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<!-- SweetAlert2 JS -->
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<!-- Custom Animations -->
<script src="<?= baseUrl() ?>/public/js/animations.js"></script>

<!-- Cart Count Loader -->
<?php if (Auth::check()): ?>
<script>
// Load cart count
function loadCartCount() {
    fetch('api/cart_count.php')
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                const badge = document.getElementById('cart-count');
                if (badge) {
                    badge.textContent = data.count;
                    badge.style.display = data.count > 0 ? 'inline-block' : 'none';
                }
            }
        })
        .catch(error => console.error('Error loading cart count:', error));
}

// Load on page load
document.addEventListener('DOMContentLoaded', loadCartCount);

// Add to cart function (global)
function addToCart(bookId, quantity = 1) {
    fetch('api/cart_add.php', {
        method: 'POST',
        headers: {'Content-Type': 'application/json'},
        body: JSON.stringify({ book_id: bookId, quantity: quantity })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            Swal.fire({
                icon: 'success',
                title: 'Added to Cart!',
                text: data.message,
                confirmButtonColor: '#2e8a40',
                timer: 2000,
                showConfirmButton: false
            });
            loadCartCount(); // Refresh cart count
        } else {
            Swal.fire({
                icon: 'error',
                title: 'Error',
                text: data.message,
                confirmButtonColor: '#2e8a40'
            });
        }
    })
    .catch(error => {
        console.error('Error:', error);
        Swal.fire({
            icon: 'error',
            title: 'Error',
            text: 'Failed to add to cart',
            confirmButtonColor: '#2e8a40'
        });
    });
}
</script>
<?php endif; ?>

<!-- Premium Footer -->
<footer class="premium-footer">
    <!-- Main Footer Content -->
    <div class="footer-main">
        <div class="container">
            <div class="row g-4">
                <!-- Brand Section -->
                <div class="col-lg-4 col-md-6">
                    <div class="footer-brand">
                        <div class="brand-logo mb-3">
                            <i class="fas fa-book-open text-primary me-2"></i>
                            <span class="brand-name">My Library</span>
                        </div>
                        <p class="brand-description">
                            Your digital gateway to knowledge and literature. Discover, borrow, and explore 
                            thousands of books from our comprehensive collection.
                        </p>
                        <div class="footer-stats">
                            <div class="stat-item">
                                <i class="fas fa-book text-primary"></i>
                                <span>1000+ Books</span>
                            </div>
                            <div class="stat-item">
                                <i class="fas fa-users text-primary"></i>
                                <span>500+ Members</span>
                            </div>
                            <div class="stat-item">
                                <i class="fas fa-star text-primary"></i>
                                <span>4.8 Rating</span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Quick Links -->
                <div class="col-lg-2 col-md-6">
                    <div class="footer-section">
                        <h5 class="footer-title">Browse</h5>
                        <ul class="footer-links">
                            <li><a href="<?= baseUrl() ?>/index.php"><i class="fas fa-home"></i> Home</a></li>
                            <li><a href="<?= baseUrl() ?>/book-list.php"><i class="fas fa-book"></i> All Books</a></li>
                            <li><a href="<?= baseUrl() ?>/author-list.php"><i class="fas fa-user-edit"></i> Authors</a></li>
                            <li><a href="<?= baseUrl() ?>/recently-published.php"><i class="fas fa-clock"></i> New Releases</a></li>
                            <li><a href="<?= baseUrl() ?>/ebooks.php"><i class="fas fa-tablet-alt"></i> E-Books</a></li>
                        </ul>
                    </div>
                </div>

                <!-- Categories -->
                <div class="col-lg-2 col-md-6">
                    <div class="footer-section">
                        <h5 class="footer-title">Categories</h5>
                        <ul class="footer-links">
                            <li><a href="<?= baseUrl() ?>/book-list.php?category=fiction"><i class="fas fa-magic"></i> Fiction</a></li>
                            <li><a href="<?= baseUrl() ?>/book-list.php?category=non-fiction"><i class="fas fa-lightbulb"></i> Non-Fiction</a></li>
                            <li><a href="<?= baseUrl() ?>/book-list.php?category=science"><i class="fas fa-flask"></i> Science</a></li>
                            <li><a href="<?= baseUrl() ?>/book-list.php?category=history"><i class="fas fa-landmark"></i> History</a></li>
                            <li><a href="<?= baseUrl() ?>/book-list.php?category=technology"><i class="fas fa-laptop-code"></i> Technology</a></li>
                        </ul>
                    </div>
                </div>

                <!-- Account & Support -->
                <div class="col-lg-2 col-md-6">
                    <div class="footer-section">
                        <h5 class="footer-title">Account</h5>
                        <ul class="footer-links">
                            <?php if (Auth::check()): ?>
                                <li><a href="<?= baseUrl() ?>/profile.php"><i class="fas fa-user-circle"></i> My Profile</a></li>
                                <li><a href="<?= baseUrl() ?>/history.php"><i class="fas fa-history"></i> My History</a></li>
                                <li><a href="<?= baseUrl() ?>/reservations.php"><i class="fas fa-bookmark"></i> Reservations</a></li>
                                <li><a href="<?= baseUrl() ?>/logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
                            <?php else: ?>
                                <li><a href="<?= baseUrl() ?>/login.php"><i class="fas fa-sign-in-alt"></i> Login</a></li>
                                <li><a href="<?= baseUrl() ?>/register.php"><i class="fas fa-user-plus"></i> Sign Up</a></li>
                            <?php endif; ?>
                            <li><a href="<?= baseUrl() ?>/help_center.php"><i class="fas fa-question-circle"></i> Help Center</a></li>
                            <li><a href="<?= baseUrl() ?>/contact_us.php"><i class="fas fa-envelope"></i> Contact Us</a></li>
                        </ul>
                    </div>
                </div>

                <!-- Contact & Social -->
                <div class="col-lg-2 col-md-6">
                    <div class="footer-section">
                        <h5 class="footer-title">Connect</h5>
                        <div class="contact-info mb-3">
                            <div class="contact-item">
                                <i class="fas fa-map-marker-alt text-primary"></i>
                                <span>123 Library Street<br>Knowledge City, KC 12345</span>
                            </div>
                            <div class="contact-item">
                                <i class="fas fa-phone text-primary"></i>
                                <span>+1 (555) 123-4567</span>
                            </div>
                            <div class="contact-item">
                                <i class="fas fa-envelope text-primary"></i>
                                <span>info@mylibrary.com</span>
                            </div>
                        </div>
                        
                        <div class="social-links">
                            <a href="#" class="social-link" title="Facebook">
                                <i class="fab fa-facebook-f"></i>
                            </a>
                            <a href="#" class="social-link" title="Twitter">
                                <i class="fab fa-twitter"></i>
                            </a>
                            <a href="#" class="social-link" title="Instagram">
                                <i class="fab fa-instagram"></i>
                            </a>
                            <a href="#" class="social-link" title="LinkedIn">
                                <i class="fab fa-linkedin-in"></i>
                            </a>
                            <a href="#" class="social-link" title="YouTube">
                                <i class="fab fa-youtube"></i>
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Newsletter Section -->
    <div class="footer-newsletter">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-lg-6">
                    <div class="newsletter-content">
                        <h4 class="newsletter-title">
                            <i class="fas fa-paper-plane text-primary me-2"></i>
                            Stay Updated
                        </h4>
                        <p class="newsletter-description">
                            Get notified about new book arrivals, special events, and exclusive offers.
                        </p>
                    </div>
                </div>
                <div class="col-lg-6">
                    <form class="newsletter-form" onsubmit="subscribeNewsletter(event)">
                        <div class="input-group">
                            <input type="email" class="form-control" placeholder="Enter your email address" required>
                            <button class="btn btn-primary" type="submit">
                                <i class="fas fa-paper-plane me-1"></i>
                                Subscribe
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Footer Bottom -->
    <div class="footer-bottom">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-lg-6">
                    <div class="copyright">
                        <p class="mb-0">
                            &copy; <?= date('Y') ?> <strong>My Book Library System</strong>. All rights reserved.
                        </p>
                        <p class="mb-0 small">
                            Built with <i class="fas fa-heart text-danger"></i> using Pure PHP OOP
                        </p>
                    </div>
                </div>
                <div class="col-lg-6">
                    <div class="footer-bottom-links">
                        <a href="<?= baseUrl() ?>/privacy.php">Privacy Policy</a>
                        <a href="<?= baseUrl() ?>/terms.php">Terms of Service</a>
                        <a href="<?= baseUrl() ?>/cookies.php">Cookie Policy</a>
                        <?php if (Auth::isAdmin()): ?>
                            <a href="<?= baseUrl() ?>/admin/index.php" class="admin-link">
                                <i class="fas fa-cog"></i> Admin Panel
                            </a>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Back to Top Button -->
    <button class="back-to-top" onclick="scrollToTop()" title="Back to Top">
        <i class="fas fa-chevron-up"></i>
    </button>
</footer>

<script>
// Newsletter subscription
function subscribeNewsletter(event) {
    event.preventDefault();
    const email = event.target.querySelector('input[type="email"]').value;
    
    // Show success message (you can implement actual subscription logic)
    const button = event.target.querySelector('button');
    const originalText = button.innerHTML;
    
    button.innerHTML = '<i class="fas fa-check me-1"></i>Subscribed!';
    button.classList.remove('btn-primary');
    button.classList.add('btn-success');
    button.disabled = true;
    
    setTimeout(() => {
        button.innerHTML = originalText;
        button.classList.remove('btn-success');
        button.classList.add('btn-primary');
        button.disabled = false;
        event.target.reset();
    }, 3000);
}

// Back to top functionality
function scrollToTop() {
    window.scrollTo({
        top: 0,
        behavior: 'smooth'
    });
}

// Show/hide back to top button
window.addEventListener('scroll', function() {
    const backToTop = document.querySelector('.back-to-top');
    if (window.pageYOffset > 300) {
        backToTop.classList.add('show');
    } else {
        backToTop.classList.remove('show');
    }
});
</script>

</body>
</html>