<?php
// views/navbar.php
/**
 * RE-DESIGNED PREMIUM NAVBAR
 * Features: Floating Glassmorphism, Modern Layout, Sub-pixel Animations
 */
use App\Auth;

displayFlashMessage();
$navCats = getCategories();
?>

<!-- Navbar Styles -->
<style>
:root {
    --nav-height: 80px;
    --nav-glass: rgba(255, 246, 245, 0.9); /* Matching new light #fff6f5 shade */
    --nav-border: rgba(61, 64, 91, 0.12);
    --nav-shadow: 0 8px 32px 0 rgba(31, 38, 135, 0.07);
    --nav-accent: #E07A5F;
    --nav-text: #3D405B;
}

body {
    padding-top: 112px; /* Accounting for fixed navbar height */
}

[data-bs-theme="dark"] {
    --nav-glass: rgba(20, 27, 45, 0.9); /* Matching new midnight #141b2d shade */
    --nav-border: rgba(255, 255, 255, 0.08);
    --nav-text: #f1f5f9;
}

/* Floating Wrapper */
.navbar-wrapper {
    position: fixed;
    top: 0;
    left: 0;
    right: 0;
    width: 100%;
    z-index: 1050;
    padding: 1rem 0;
    pointer-events: none; /* Allow clicks through to background if needed */
}

/* Main Navbar Body */
.bh-navbar {
    pointer-events: auto;
    background: var(--nav-glass);
    backdrop-filter: blur(16px);
    -webkit-backdrop-filter: blur(16px);
    border: 1px solid var(--nav-border);
    box-shadow: var(--nav-shadow);
    border-radius: 24px;
    margin: 0 auto;
    width: 95%;
    max-width: 1400px;
    height: var(--nav-height);
    display: flex;
    align-items: center;
    transition: all 0.4s cubic-bezier(0.165, 0.84, 0.44, 1);
}

.bh-navbar.scrolled {
    width: 98%;
    border-radius: 12px;
    height: 70px;
    margin-top: -0.5rem;
}

/* Brand Area */
.bh-brand {
    font-size: 1.5rem;
    font-weight: 800;
    letter-spacing: -1px;
    color: var(--nav-text) !important;
    text-decoration: none;
    display: flex;
    align-items: center;
    gap: 8px;
    padding: 0 1.5rem;
}

.bh-brand .brand-icon {
    width: 38px;
    height: 38px;
    background: var(--nav-accent);
    color: white;
    border-radius: 10px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.2rem;
    box-shadow: 0 4px 12px rgba(224, 122, 95, 0.3);
}

/* Nav Links - Center */
.bh-nav-center {
    flex-grow: 1;
    display: flex;
    justify-content: center;
}

.bh-link-list {
    display: flex;
    list-style: none;
    margin: 0;
    padding: 0;
    gap: 1.5rem;
}

.bh-nav-link {
    color: var(--nav-text) !important;
    font-weight: 600;
    font-size: 0.95rem;
    text-decoration: none;
    padding: 0.5rem 0.75rem;
    border-radius: 12px;
    transition: all 0.3s ease;
    opacity: 0.8;
}

.bh-nav-link:hover {
    opacity: 1;
    background: rgba(224, 122, 95, 0.08);
    color: var(--nav-accent) !important;
}

/* Utils Area - Right */
.bh-utils {
    display: flex;
    align-items: center;
    gap: 12px;
    padding-right: 1.5rem;
}

.bh-btn-icon {
    width: 42px;
    height: 42px;
    border-radius: 14px;
    border: 1px solid transparent;
    background: transparent;
    color: var(--nav-text);
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.1rem;
    transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    cursor: pointer;
    position: relative;
}

.bh-btn-icon:hover {
    background: var(--nav-border);
    transform: translateY(-2px);
}

.bh-badge {
    position: absolute;
    top: 6px;
    right: 6px;
    background: var(--nav-accent);
    color: white;
    font-size: 0.7rem;
    font-weight: 700;
    min-width: 18px;
    height: 18px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    border: 2px solid white;
}

[data-bs-theme="dark"] .bh-badge {
    border-color: #0f172a;
}

/* User Account Pill */
.bh-user-pill {
    display: flex;
    align-items: center;
    gap: 10px;
    background: var(--nav-border);
    padding: 6px 16px 6px 6px;
    border-radius: 100px;
    text-decoration: none !important;
    color: var(--nav-text) !important;
    transition: all 0.3s ease;
}

.bh-user-pill:hover {
    background: rgba(224, 122, 95, 0.1);
    box-shadow: 0 4px 12px rgba(0,0,0,0.05);
}

.bh-avatar {
    width: 32px;
    height: 32px;
    border-radius: 50%;
    background: var(--nav-accent);
    color: white;
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: 700;
    font-size: 0.85rem;
}

/* Mobile Toggler */
.bh-mobile-toggle {
    display: none;
    border: none;
    background: transparent;
    color: var(--nav-text);
    font-size: 1.5rem;
    padding: 0 1rem;
}

@media (max-width: 991px) {
    .bh-nav-center { display: none; }
    .bh-mobile-toggle { display: block; }
    .bh-navbar { height: 70px; width: 92%; }
    .bh-utils .d-none-mobile { display: none; }
}

/* Popover Dropdowns */
.bh-dropdown {
    border: none !important;
    border-radius: 20px !important;
    padding: 12px !important;
    margin-top: 15px !important;
    min-width: 260px !important; /* Ensure enough width for labels */
    background: var(--nav-glass) !important;
    backdrop-filter: blur(24px);
    box-shadow: 0 20px 50px rgba(0,0,0,0.1) !important;
    animation: bh-fade-up 0.3s ease;
    right: 0 !important; /* Force right alignment for end-drop */
    left: auto !important;
    margin-right: 15px !important; /* Pull back from screen edge */
}

@keyframes bh-fade-up {
    from { opacity: 0; transform: translateY(10px); }
    to { opacity: 1; transform: translateY(0); }
}

.bh-dropdown-item {
    border-radius: 12px !important;
    padding: 10px 15px !important;
    font-weight: 500;
    transition: all 0.2s ease;
}

.bh-dropdown-item:hover {
    background: rgba(224, 122, 95, 0.08) !important;
    color: var(--nav-accent) !important;
}

/* Mobile Drawer Sidebar */
.bh-drawer {
    position: fixed;
    top: 0;
    left: -100%;
    width: 300px;
    height: 100vh;
    background: var(--nav-glass);
    backdrop-filter: blur(20px);
    z-index: 2000;
    transition: all 0.4s cubic-bezier(0.165, 0.84, 0.44, 1);
    padding: 2rem;
    border-right: 1px solid var(--nav-border);
}

.bh-drawer.active {
    left: 0;
}

.bh-drawer-overlay {
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100vh;
    background: rgba(0,0,0,0.4);
    backdrop-filter: blur(4px);
    z-index: 1999;
    display: none;
}

.bh-drawer-overlay.active {
    display: block;
}
.dropdown-divider {
    border-top: 1px solid rgba(61, 64, 91, 0.1) !important;
    opacity: 0.5;
}

[data-bs-theme="dark"] .dropdown-divider {
    border-top: 1px solid rgba(255, 255, 255, 0.08) !important;
}

/* Custom Join Button Style */
.button {
    background-color: var(--nav-accent) !important;
    color: white !important;
    border: none !important;
    transition: all 0.3s cubic-bezier(0.175, 0.885, 0.32, 1.275) !important;
}

.button:hover {
    background-color: #cf6a50 !important;
    transform: translateY(-2px);
    box-shadow: 0 8px 20px rgba(224, 122, 95, 0.25) !important;
}
</style>

<div class="navbar-wrapper">
    <nav class="bh-navbar" id="main-nav">
        <!-- Logo -->
        <a href="<?= baseUrl() ?>/index.php" class="bh-brand">
            <div class="brand-icon"><i class="fas fa-book"></i></div>
            <span class="d-none d-sm-inline"><?= e(getSetting('site_name', 'BookHouse')) ?></span>
        </a>

        <!-- Desktop Navigation -->
        <div class="bh-nav-center">
            <ul class="bh-link-list">
                <li><a href="<?= baseUrl() ?>/index.php" class="bh-nav-link">Home</a></li>
                <li><a href="<?= baseUrl() ?>/book-list.php" class="bh-nav-link">Collection</a></li>
                <li class="nav-item dropdown">
                    <a class="bh-nav-link dropdown-toggle" href="#" id="catsDropdown" data-bs-toggle="dropdown">Categories</a>
                    <ul class="dropdown-menu bh-dropdown shadow border-0">
                        <?php foreach (array_slice($navCats, 0, 8) as $cat): ?>
                            <li><a class="dropdown-item bh-dropdown-item" href="<?= e(baseUrl()) ?>/book-list.php?category=<?= urlencode($cat) ?>"><?= e($cat) ?></a></li>
                        <?php endforeach; ?>
                        <li><hr class="dropdown-divider"></li>
                        <li><a class="dropdown-item bh-dropdown-item fw-bold text-primary" href="<?= baseUrl() ?>/book-list.php">Explore All</a></li>
                    </ul>
                </li>
                <li><a href="<?= baseUrl() ?>/author-list.php" class="bh-nav-link">Authors</a></li>
            </ul>
        </div>

        <!-- Utils & Mobile Toggle -->
        <div class="bh-utils">
            <!-- Search -->
            <button class="bh-btn-icon" data-bs-toggle="modal" data-bs-target="#searchModal" title="Search">
                <i class="fas fa-search"></i>
            </button>

            <!-- Theme -->
            <button id="themeToggle" class="bh-btn-icon" title="Toggle Theme">
                <i class="fas fa-moon"></i>
            </button>

            <!-- Cart -->
            <a href="<?= baseUrl() ?>/cart.php" class="bh-btn-icon" title="Cart">
                <i class="fas fa-shopping-cart"></i>
                <span class="bh-badge" id="cart-count">0</span>
            </a>

            <!-- User -->
            <?php if (Auth::check()): ?>
            <div class="dropdown">
                <a href="#" class="bh-user-pill dropdown-toggle" data-bs-toggle="dropdown">
                    <div class="bh-avatar"><?= substr(Auth::user() ?? 'U', 0, 1) ?></div>
                    <span class="d-none d-lg-inline fw-bold"><?= e(Auth::user()) ?></span>
                </a>
                <ul class="dropdown-menu dropdown-menu-end bh-dropdown shadow border-0">
                    <li><a class="dropdown-item bh-dropdown-item" href="<?= baseUrl() ?>/user-details.php"><i class="far fa-user me-2"></i>My Profile</a></li>
                    <li><a class="dropdown-item bh-dropdown-item" href="<?= baseUrl() ?>/borrow.php"><i class="fas fa-book-reader me-2"></i>Borrowing</a></li>
                    <li><a class="dropdown-item bh-dropdown-item" href="<?= baseUrl() ?>/user-details.php#orders-section"><i class="fas fa-shopping-bag me-2"></i>Orders</a></li>
                    <?php if (isAdmin()): ?>
                        <li><hr class="dropdown-divider"></li>
                        <li><a class="dropdown-item bh-dropdown-item text-primary" href="<?= baseUrl() ?>/admin/index.php"><i class="fas fa-shield-alt me-2"></i>Admin Dashboard</a></li>
                    <?php endif; ?>
                    <li><hr class="dropdown-divider"></li>
                    <li><a class="dropdown-item bh-dropdown-item text-danger" href="<?= baseUrl() ?>/logout.php"><i class="fas fa-sign-out-alt me-2"></i>Sign Out</a></li>
                </ul>
            </div>
            <?php else: ?>
            <a href="<?= baseUrl() ?>/login.php" class="bh-nav-link d-none d-lg-block">Log In</a>
            <?php if (getSetting('allow_registration', '1') === '1'): ?>
                <a href="<?= baseUrl() ?>/register.php" class="button btn rounded-pill px-4 d-none d-md-block shadow-sm">Join</a>
            <?php endif; ?>
            <?php endif; ?>

            <!-- Mobile Hamburger -->
            <button class="bh-mobile-toggle" id="drawer-toggle">
                <i class="fas fa-bars-staggered"></i>
            </button>
        </div>
    </nav>
</div>

<!-- Mobile Drawer -->
<div class="bh-drawer-overlay" id="nav-overlay"></div>
<div class="bh-drawer" id="nav-drawer">
    <div class="d-flex justify-content-between align-items-center mb-5">
        <a href="<?= baseUrl() ?>/index.php" class="bh-brand p-0">
            <div class="brand-icon"><i class="fas fa-book"></i></div>
            <span><?= e(getSetting('site_name', 'BookHouse')) ?></span>
        </a>
        <button class="btn-close shadow-none" id="drawer-close"></button>
    </div>

    <ul class="bh-link-list flex-column w-100 gap-2">
        <li><a href="<?= baseUrl() ?>/index.php" class="bh-nav-link p-3 d-flex w-100">Home</a></li>
        <li><a href="<?= baseUrl() ?>/book-list.php" class="bh-nav-link p-3 d-flex w-100">Collection</a></li>
        <li><a href="<?= baseUrl() ?>/author-list.php" class="bh-nav-link p-3 d-flex w-100">Authors</a></li>
        <?php if (!Auth::check()): ?>
            <li class="mt-4">
                <a href="<?= baseUrl() ?>/login.php" class="btn btn-outline-primary rounded-pill w-100 mb-2">Log In</a>
                <?php if (getSetting('allow_registration', '1') === '1'): ?>
                    <a href="<?= baseUrl() ?>/register.php" class="btn btn-primary rounded-pill w-100">Create Account</a>
                <?php endif; ?>
            </li>
        <?php endif; ?>
    </ul>
</div>

<!-- Search Modal -->
<div class="modal fade" id="searchModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content border-0 shadow-lg rounded-4 overflow-hidden">
            <div class="modal-body p-0">
                <div class="p-4 bg-light border-bottom">
                    <form action="<?= baseUrl() ?>/book-list.php" method="GET" class="d-flex gap-2">
                        <div class="input-group input-group-lg bg-white rounded-3 shadow-sm border overflow-hidden">
                            <span class="input-group-text bg-transparent border-0"><i class="fas fa-search text-muted"></i></span>
                            <input type="text" name="search" class="form-control border-0 bg-transparent shadow-none" placeholder="Search books, authors, or genres..." autofocus>
                        </div>
                        <button type="submit" class="btn btn-primary rounded-3 px-4 fw-bold">SEARCH</button>
                    </form>
                </div>
                <div class="p-4 bg-white">
                    <h6 class="text-muted small fw-bold text-uppercase mb-3" style="letter-spacing: 1px;">Suggested Categories</h6>
                    <div class="d-flex flex-wrap gap-2">
                        <?php foreach (array_slice($navCats, 0, 10) as $cat): ?>
                            <a href="<?= baseUrl() ?>/book-list.php?category=<?= urlencode($cat) ?>" class="btn btn-sm btn-outline-secondary rounded-pill px-3"><?= e($cat) ?></a>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', () => {
    const nav = document.getElementById('main-nav');
    const themeBtn = document.getElementById('themeToggle');
    const themeIcon = themeBtn?.querySelector('i');
    const html = document.documentElement;
    
    // Scroll effect
    window.addEventListener('scroll', () => {
        if (window.scrollY > 20) {
            nav?.classList.add('scrolled');
        } else {
            nav?.classList.remove('scrolled');
        }
    });

    // Theme toggle
    const updateTheme = (t) => {
        html.setAttribute('data-bs-theme', t);
        localStorage.setItem('theme', t);
        if (themeIcon) {
            themeIcon.className = t === 'dark' ? 'fas fa-sun' : 'fas fa-moon';
        }
    };
    updateTheme(localStorage.getItem('theme') || 'light');
    themeBtn?.addEventListener('click', () => {
        updateTheme(html.getAttribute('data-bs-theme') === 'dark' ? 'light' : 'dark');
    });

    // Mobile Drawer Logic
    const drawer = document.getElementById('nav-drawer');
    const overlay = document.getElementById('nav-overlay');
    const openBtn = document.getElementById('drawer-toggle');
    const closeBtn = document.getElementById('drawer-close');

    const toggleDrawer = (open) => {
        drawer?.classList.toggle('active', open);
        overlay?.classList.toggle('active', open);
        document.body.style.overflow = open ? 'hidden' : '';
    };

    openBtn?.addEventListener('click', () => toggleDrawer(true));
    closeBtn?.addEventListener('click', () => toggleDrawer(false));
    overlay?.addEventListener('click', () => toggleDrawer(false));
});
</script>
