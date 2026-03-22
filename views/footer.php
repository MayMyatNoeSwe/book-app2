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
                confirmButtonColor: '#E07A5F',
                timer: 2000,
                showConfirmButton: false
            });
            loadCartCount();
        } else {
            Swal.fire({
                icon: 'error',
                title: 'Error',
                text: data.message,
                confirmButtonColor: '#E07A5F'
            });
        }
    })
    .catch(error => {
        console.error('Error:', error);
        Swal.fire({
            icon: 'error',
            title: 'Error',
            text: 'Failed to add to cart',
            confirmButtonColor: '#E07A5F'
        });
    });
}
</script>
<?php endif; ?>

<!-- ═══════ PREMIUM FOOTER ═══════ -->
<style>
.ft {
    position: relative;
    background: #1a1f2e;
    color: rgba(255,255,255,0.7);
    font-size: 14px;
    overflow: hidden;
}
.ft::before {
    content: ''; position: absolute; inset: 0;
    background-image:
        linear-gradient(rgba(255,255,255,0.015) 1px, transparent 1px),
        linear-gradient(90deg, rgba(255,255,255,0.015) 1px, transparent 1px);
    background-size: 60px 60px; pointer-events: none;
}
.ft-glow-1 {
    position: absolute; width: 400px; height: 400px;
    background: radial-gradient(circle, rgba(224,122,95,0.08), transparent 70%);
    top: -100px; left: -100px; pointer-events: none;
}
.ft-glow-2 {
    position: absolute; width: 350px; height: 350px;
    background: radial-gradient(circle, rgba(129,178,154,0.06), transparent 70%);
    bottom: -80px; right: -80px; pointer-events: none;
}

/* Main */
.ft-main { padding: 60px 0 40px; position: relative; z-index: 1; }
.ft-brand-name {
    font-family: 'Playfair Display', serif;
    font-size: 22px; font-weight: 800; color: #fff;
    display: flex; align-items: center; gap: 10px;
    margin-bottom: 14px;
}
.ft-brand-icon {
    width: 36px; height: 36px; border-radius: 10px;
    background: linear-gradient(135deg, var(--bookhouse-orange, #E07A5F), #c2664e);
    display: flex; align-items: center; justify-content: center;
    color: #fff; font-size: 16px;
}
.ft-brand-desc {
    line-height: 1.7; margin-bottom: 20px; max-width: 300px; font-size: 13px;
    color: rgba(255,255,255,0.5);
}
.ft-mini-stats {
    display: flex; gap: 20px; flex-wrap: wrap;
}
.ft-mini-stat {
    display: flex; align-items: center; gap: 6px;
    font-size: 12px; font-weight: 600; color: rgba(255,255,255,0.4);
}
.ft-mini-stat i { color: var(--bookhouse-orange, #E07A5F); font-size: 11px; }

/* Links */
.ft-heading {
    font-size: 13px; font-weight: 800; letter-spacing: 1.5px;
    text-transform: uppercase; color: #fff; margin-bottom: 20px;
}
.ft-links { list-style: none; padding: 0; margin: 0; }
.ft-links li { margin-bottom: 10px; }
.ft-links a {
    color: rgba(255,255,255,0.45);
    text-decoration: none; font-size: 13px; font-weight: 500;
    transition: color 0.2s, padding-left 0.2s;
    display: inline-block;
}
.ft-links a:hover { color: var(--bookhouse-orange, #E07A5F); padding-left: 4px; }

/* Contact */
.ft-contact-item {
    display: flex; align-items: flex-start; gap: 10px;
    margin-bottom: 12px; font-size: 13px;
    color: rgba(255,255,255,0.45);
}
.ft-contact-item i {
    color: var(--bookhouse-orange, #E07A5F); margin-top: 2px; font-size: 12px; flex-shrink: 0;
}
.ft-social {
    display: flex; gap: 8px; margin-top: 18px;
}
.ft-social a {
    width: 36px; height: 36px; border-radius: 10px;
    border: 1px solid rgba(255,255,255,0.08);
    display: flex; align-items: center; justify-content: center;
    color: rgba(255,255,255,0.4);
    text-decoration: none; font-size: 14px;
    transition: all 0.25s;
}
.ft-social a:hover {
    background: var(--bookhouse-orange, #E07A5F);
    border-color: var(--bookhouse-orange, #E07A5F);
    color: #fff; transform: translateY(-2px);
}

/* Newsletter */
.ft-newsletter {
    position: relative; z-index: 1;
    border-top: 1px solid rgba(255,255,255,0.06);
    padding: 32px 0;
}
.ft-nl-title {
    font-weight: 800; font-size: 16px; color: #fff; margin-bottom: 4px;
}
.ft-nl-desc { font-size: 13px; color: rgba(255,255,255,0.4); margin: 0; }
.ft-nl-form {
    display: flex; gap: 8px; max-width: 420px;
}
.ft-nl-form input {
    flex: 1; border: 1px solid rgba(255,255,255,0.1);
    background: rgba(255,255,255,0.05);
    border-radius: 12px; padding: 12px 18px;
    color: #fff; font-size: 13px; outline: none;
    transition: border-color 0.2s;
}
.ft-nl-form input::placeholder { color: rgba(255,255,255,0.3); }
.ft-nl-form input:focus { border-color: var(--bookhouse-orange); }
.ft-nl-btn {
    padding: 12px 24px; border: none; border-radius: 12px;
    background: var(--bookhouse-orange, #E07A5F); color: #fff;
    font-weight: 700; font-size: 13px; cursor: pointer;
    transition: all 0.2s; white-space: nowrap;
}
.ft-nl-btn:hover { filter: brightness(1.1); transform: translateY(-1px); }

/* Bottom */
.ft-bottom {
    position: relative; z-index: 1;
    border-top: 1px solid rgba(255,255,255,0.06);
    padding: 20px 0;
}
.ft-copyright { font-size: 12px; color: rgba(255,255,255,0.3); }
.ft-copyright strong { color: rgba(255,255,255,0.6); }
.ft-bottom-links {
    display: flex; gap: 20px; justify-content: flex-end; flex-wrap: wrap;
}
.ft-bottom-links a {
    font-size: 12px; color: rgba(255,255,255,0.3);
    text-decoration: none; transition: color 0.2s;
}
.ft-bottom-links a:hover { color: var(--bookhouse-orange, #E07A5F); }
.ft-admin-link {
    background: rgba(224,122,95,0.15) !important;
    padding: 4px 12px; border-radius: 8px;
    color: var(--bookhouse-orange, #E07A5F) !important;
    font-weight: 700;
}

/* Back to top */
.ft-top-btn {
    position: fixed; bottom: 30px; right: 30px;
    width: 44px; height: 44px; border-radius: 14px;
    background: var(--bookhouse-orange, #E07A5F);
    color: #fff; border: none; cursor: pointer;
    display: flex; align-items: center; justify-content: center;
    font-size: 16px; z-index: 999;
    box-shadow: 0 8px 24px rgba(224,122,95,0.35);
    opacity: 0; visibility: hidden;
    transform: translateY(10px);
    transition: all 0.3s;
}
.ft-top-btn.show { opacity: 1; visibility: visible; transform: translateY(0); }
.ft-top-btn:hover { transform: translateY(-3px); }

@media (max-width: 767px) {
    .ft-main { padding: 40px 0 30px; text-align: center; }
    .ft-brand-name { justify-content: center; }
    .ft-brand-desc { margin: 0 auto 20px; }
    .ft-mini-stats { justify-content: center; }
    .ft-newsletter .row { text-align: center; }
    .ft-nl-form { margin: 16px auto 0; }
    .ft-bottom-links { justify-content: center; }
    .ft-copyright { text-align: center; margin-bottom: 12px; }
}
</style>

<footer class="ft">
    <div class="ft-glow-1"></div>
    <div class="ft-glow-2"></div>

    <!-- Main Footer -->
    <div class="ft-main">
        <div class="container">
            <div class="row g-4 g-lg-5">
                <!-- Brand -->
                <div class="col-lg-3 col-md-6">
                    <div class="ft-brand-name">
                        <div class="ft-brand-icon"><i class="fas fa-book-open"></i></div>
                        My Library
                    </div>
                    <p class="ft-brand-desc">
                        Your digital gateway to knowledge and literature. Discover, borrow, and explore thousands of books.
                    </p>
                    <div class="ft-mini-stats">
                        <div class="ft-mini-stat"><i class="fas fa-book"></i> 1000+ Books</div>
                        <div class="ft-mini-stat"><i class="fas fa-users"></i> 500+ Members</div>
                    </div>
                </div>

                <!-- Browse -->
                <div class="col-lg-2 col-md-6 col-6">
                    <div class="ft-heading">Browse</div>
                    <ul class="ft-links">
                        <li><a href="<?= baseUrl() ?>/index.php">Home</a></li>
                        <li><a href="<?= baseUrl() ?>/book-list.php">All Books</a></li>
                        <li><a href="<?= baseUrl() ?>/author-list.php">Authors</a></li>
                        <li><a href="<?= baseUrl() ?>/recently-published.php">New Releases</a></li>
                        <li><a href="<?= baseUrl() ?>/ebooks.php">E-Books</a></li>
                    </ul>
                </div>

                <!-- Categories -->
                <div class="col-lg-2 col-md-6 col-6">
                    <div class="ft-heading">Categories</div>
                    <ul class="ft-links">
                        <li><a href="<?= baseUrl() ?>/book-list.php?category=fiction">Fiction</a></li>
                        <li><a href="<?= baseUrl() ?>/book-list.php?category=non-fiction">Non-Fiction</a></li>
                        <li><a href="<?= baseUrl() ?>/book-list.php?category=science">Science</a></li>
                        <li><a href="<?= baseUrl() ?>/book-list.php?category=history">History</a></li>
                        <li><a href="<?= baseUrl() ?>/book-list.php?category=technology">Technology</a></li>
                    </ul>
                </div>

                <!-- Account -->
                <div class="col-lg-2 col-md-6 col-6">
                    <div class="ft-heading">Account</div>
                    <ul class="ft-links">
                        <?php if (Auth::check()): ?>
                            <li><a href="<?= baseUrl() ?>/profile.php">My Profile</a></li>
                            <li><a href="<?= baseUrl() ?>/history.php">My History</a></li>
                            <li><a href="<?= baseUrl() ?>/reservations.php">Reservations</a></li>
                            <li><a href="<?= baseUrl() ?>/logout.php">Logout</a></li>
                        <?php else: ?>
                            <li><a href="<?= baseUrl() ?>/login.php">Login</a></li>
                            <li><a href="<?= baseUrl() ?>/register.php">Sign Up</a></li>
                        <?php endif; ?>
                        <li><a href="<?= baseUrl() ?>/help_center.php">Help Center</a></li>
                        <li><a href="<?= baseUrl() ?>/contact_us.php">Contact Us</a></li>
                    </ul>
                </div>

                <!-- Connect -->
                <div class="col-lg-3 col-md-6 col-6">
                    <div class="ft-heading">Connect</div>
                    <div class="ft-contact-item">
                        <i class="fas fa-map-marker-alt"></i>
                        <span>123 Library Street<br>Knowledge City, KC 12345</span>
                    </div>
                    <div class="ft-contact-item">
                        <i class="fas fa-phone"></i>
                        <span>+1 (555) 123-4567</span>
                    </div>
                    <div class="ft-contact-item">
                        <i class="fas fa-envelope"></i>
                        <span>info@mylibrary.com</span>
                    </div>
                    <div class="ft-social">
                        <a href="#" title="Facebook"><i class="fab fa-facebook-f"></i></a>
                        <a href="#" title="Twitter"><i class="fab fa-twitter"></i></a>
                        <a href="#" title="Instagram"><i class="fab fa-instagram"></i></a>
                        <a href="#" title="LinkedIn"><i class="fab fa-linkedin-in"></i></a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Newsletter -->
    <div class="ft-newsletter">
        <div class="container">
            <div class="row align-items-center g-3">
                <div class="col-lg-6">
                    <div class="ft-nl-title">Stay in the Loop</div>
                    <p class="ft-nl-desc">Get notified about new arrivals, events, and exclusive offers.</p>
                </div>
                <div class="col-lg-6 d-flex justify-content-lg-end">
                    <form class="ft-nl-form" onsubmit="subscribeNewsletter(event)">
                        <input type="email" placeholder="Your email address" required>
                        <button type="submit" class="ft-nl-btn">Subscribe</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Bottom -->
    <div class="ft-bottom">
        <div class="container">
            <div class="row align-items-center g-2">
                <div class="col-lg-6">
                    <div class="ft-copyright">
                        &copy; <?= date('Y') ?> <strong>My Book Library System</strong>. All rights reserved.
                    </div>
                </div>
                <div class="col-lg-6">
                    <div class="ft-bottom-links">
                        <a href="<?= baseUrl() ?>/privacy.php">Privacy</a>
                        <a href="<?= baseUrl() ?>/terms.php">Terms</a>
                        <a href="<?= baseUrl() ?>/cookies.php">Cookies</a>
                        <?php if (Auth::isAdmin()): ?>
                            <a href="<?= baseUrl() ?>/admin/index.php" class="ft-admin-link">
                                <i class="fas fa-cog me-1"></i>Admin
                            </a>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Back to Top -->
    <button class="ft-top-btn" id="ftTopBtn" onclick="scrollToTop()" title="Back to Top">
        <i class="fas fa-chevron-up"></i>
    </button>
</footer>

<script>
// Newsletter subscription
function subscribeNewsletter(event) {
    event.preventDefault();
    const button = event.target.querySelector('button');
    const originalText = button.innerHTML;
    
    button.innerHTML = '<i class="fas fa-check me-1"></i>Done!';
    button.style.background = '#10b981';
    button.disabled = true;
    
    setTimeout(() => {
        button.innerHTML = originalText;
        button.style.background = '';
        button.disabled = false;
        event.target.reset();
    }, 3000);
}

// Back to top
function scrollToTop() {
    window.scrollTo({ top: 0, behavior: 'smooth' });
}

window.addEventListener('scroll', function() {
    const btn = document.getElementById('ftTopBtn');
    if (btn) btn.classList.toggle('show', window.pageYOffset > 300);
});
</script>

</body>
</html>