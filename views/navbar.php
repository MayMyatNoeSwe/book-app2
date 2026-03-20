<?php
// views/navbar.php
use App\Auth;

displayFlashMessage();

$navCats = getCategories();
?>
<nav class="navbar navbar-expand-lg navbar-premium sticky-top py-3" style="z-index: 1050 !important;">
    <div class="container-fluid px-3 px-lg-5 d-flex align-items-center flex-wrap">

        <!-- 1. Left Section (Nav Links) -->
        <div class="nav-section-left d-flex align-items-center" style="order:1; flex: 1 1 33%;">
            <!-- Mobile Hamburger -->
            <button class="navbar-toggler d-lg-none border-0 nav-hamburger" type="button"
                    data-bs-toggle="collapse" data-bs-target="#navbarNav"
                    style="box-shadow:none;background:transparent;padding:6px;">
                <span class="navbar-toggler-icon"></span>
            </button>

            <!-- Desktop Links -->
            <ul class="navbar-nav flex-row align-items-center d-none d-lg-flex mb-0">
                <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle d-flex align-items-center" href="#" id="collectionDropdown"
                       role="button" data-bs-toggle="dropdown" aria-expanded="false">
                       COLLECTION <i class="fas fa-chevron-down ms-2 nav-chevron"></i>
                    </a>
                    <ul class="dropdown-menu border-0 shadow" aria-labelledby="collectionDropdown">
                        <li><a class="dropdown-item" href="<?= baseUrl() ?>/book-list.php?sort=latest">Newest Arrivals</a></li>
                        <li><a class="dropdown-item" href="<?= baseUrl() ?>/book-list.php?sort=oldest">Oldest Classics</a></li>
                        <li><a class="dropdown-item" href="<?= baseUrl() ?>/book-list.php?sort=alphabetical">A - Z</a></li>
                    </ul>
                </li>
                <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle d-flex align-items-center" href="#" id="browseDropdown"
                       role="button" data-bs-toggle="dropdown" aria-expanded="false">
                       CATEGORIES <i class="fas fa-chevron-down ms-2 nav-chevron"></i>
                    </a>
                    <ul class="dropdown-menu dropdown-menu-start border-0 shadow" aria-labelledby="browseDropdown">
                        <?php foreach ($navCats as $navCat): ?>
                        <li><a class="dropdown-item" href="<?= baseUrl() ?>/book-list.php?category=<?= urlencode($navCat) ?>"><?= e($navCat) ?></a></li>
                        <?php endforeach; ?>
                        <li><hr class="dropdown-divider"></li>
                        <li><a class="dropdown-item" href="<?= baseUrl() ?>/book-list.php">View All Books</a></li>
                    </ul>
                </li>
                <li class="nav-item">
                    <a class="nav-link nav-text-only" href="<?= baseUrl() ?>/book-list.php?sort=borrowed">TOP SELLERS</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link nav-icon-search" href="#" data-bs-toggle="modal" data-bs-target="#searchModal">
                        <i class="fas fa-search"></i>
                    </a>
                </li>
            </ul>
        </div>

        <!-- 2. Logo (Absolute Center) -->
        <div class="nav-section-center d-flex justify-content-center" style="order:2; flex: 1 1 33%;">
            <a class="navbar-brand fw-bold m-0" href="<?= baseUrl() ?>/index.php">
                <span class="brand-book">Book</span><span class="brand-house">House</span>
            </a>
        </div>

        <!-- 3. Right Icons (End) -->
        <div class="nav-section-right d-flex align-items-center justify-content-end gap-1" style="order:3; flex: 1 1 33%;">
            <button id="themeToggle" class="nav-icon-link border-0 bg-transparent" title="Toggle Dark Mode">
                <i class="fas fa-moon"></i>
            </button>

            <?php if (Auth::check()): ?>
            <div class="dropdown" style="position:relative;">
                <a class="nav-user-pill dropdown-toggle d-flex align-items-center" href="#"
                   id="userDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                    <div class="user-avatar-circle"><i class="far fa-user"></i></div>
                    <span class="user-name-label d-none d-lg-block"><?= e(Auth::user()) ?></span>
                </a>
                <ul class="dropdown-menu dropdown-menu-end shadow" aria-labelledby="userDropdown">
                    <li><a class="dropdown-item" href="<?= baseUrl() ?>/book-list.php?availability=available"><i class="fas fa-book-reader me-2"></i>Borrow Books</a></li>
                    <li><a class="dropdown-item" href="<?= baseUrl() ?>/cart.php"><i class="fas fa-shopping-bag me-2"></i>Buy Books</a></li>
                    <li><hr class="dropdown-divider"></li>
                    <li><a class="dropdown-item logout-item text-danger" href="<?= baseUrl() ?>/logout.php"><i class="fas fa-sign-out-alt me-2"></i>Logout</a></li>
                </ul>
            </div>
            <?php else: ?>
            <a class="nav-icon-link" href="<?= baseUrl() ?>/login.php" title="Login"><i class="far fa-user"></i></a>
            <?php endif; ?>

            <?php if (!Auth::check()): ?>
            <a class="btn-create-account d-none d-lg-inline-flex" href="<?= baseUrl() ?>/register.php">Create Account</a>
            <?php endif; ?>

            <a href="<?= baseUrl() ?>/cart.php" class="cart-icon-wrapper text-decoration-none position-relative ms-1">
                <i class="fas fa-shopping-cart" style="font-size:18px;"></i>
                <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger" id="cart-count">0</span>
            </a>
        </div>

        <!-- 4. Mobile Drawer (Collapse Only) -->
        <div class="collapse navbar-collapse nav-collapse-drawer" id="navbarNav" style="order:4; flex-basis: 100%;">
            <div class="d-lg-none mobile-nav-drawer mt-3">
                <ul class="mobile-nav-list">
                    <li><a class="mobile-nav-link" href="<?= baseUrl() ?>/book-list.php"><i class="fas fa-layer-group"></i> Collection</a></li>
                    <li>
                        <button class="mobile-nav-link w-100 text-start border-0 bg-transparent"
                                data-bs-toggle="collapse" data-bs-target="#mobileCatCollapse">
                            <i class="fas fa-tags"></i> Categories
                            <i class="fas fa-chevron-down ms-auto mobile-chevron"></i>
                        </button>
                        <div class="collapse" id="mobileCatCollapse">
                            <ul class="mobile-subnav-list">
                                <?php foreach ($navCats as $navCat): ?>
                                <li><a href="<?= baseUrl() ?>/book-list.php?category=<?= urlencode($navCat) ?>"><?= e($navCat) ?></a></li>
                                <?php endforeach; ?>
                                <li><a href="<?= baseUrl() ?>/book-list.php">View All Books</a></li>
                            </ul>
                        </div>
                    </li>
                    <li><a class="mobile-nav-link" href="<?= baseUrl() ?>/book-list.php?sort=borrowed"><i class="fas fa-fire"></i> Top Sellers</a></li>
                    <li><a class="mobile-nav-link" href="#" data-bs-toggle="modal" data-bs-target="#searchModal"><i class="fas fa-search"></i> Search Books</a></li>
                    <?php if (!Auth::check()): ?>
                    <li class="mobile-nav-cta">
                        <a class="btn-mobile-register" href="<?= baseUrl() ?>/register.php">
                            <i class="fas fa-user-plus me-2"></i> Create Account
                        </a>
                    </li>
                    <?php endif; ?>
                </ul>
            </div>
        </div>



    </div>
</nav>

<script>
document.addEventListener('DOMContentLoaded', () => {
    // Theme toggle
    const toggle = document.getElementById('themeToggle');
    const icon = toggle?.querySelector('i');
    const html = document.documentElement;
    const savedTheme = localStorage.getItem('theme') || 'light';
    html.setAttribute('data-bs-theme', savedTheme);
    if (icon) icon.className = savedTheme === 'dark' ? 'fas fa-sun' : 'fas fa-moon';
    toggle?.addEventListener('click', () => {
        const newTheme = html.getAttribute('data-bs-theme') === 'dark' ? 'light' : 'dark';
        html.setAttribute('data-bs-theme', newTheme);
        localStorage.setItem('theme', newTheme);
        if (icon) icon.className = newTheme === 'dark' ? 'fas fa-sun' : 'fas fa-moon';
    });

    // Dropdown chevron rotation - using events for better reliability
    document.querySelectorAll('.dropdown').forEach(dropdown => {
        dropdown.addEventListener('show.bs.dropdown', function() {
            const chevron = this.querySelector('.nav-chevron');
            if (chevron) chevron.classList.add('rotated');
        });
        dropdown.addEventListener('hide.bs.dropdown', function() {
            const chevron = this.querySelector('.nav-chevron');
            if (chevron) chevron.classList.remove('rotated');
        });
    });

    // Fallback dropdown toggle for nav menus in case Bootstrap dropdowns
    // do not initialize correctly on the current page.
    const navDropdowns = Array.from(document.querySelectorAll('.navbar-premium .nav-item.dropdown'));
    const closeNavDropdowns = (except = null) => {
        navDropdowns.forEach((dropdown) => {
            if (dropdown === except) return;
            dropdown.classList.remove('show');
            const toggle = dropdown.querySelector('.dropdown-toggle');
            const menu = dropdown.querySelector('.dropdown-menu');
            const chevron = dropdown.querySelector('.nav-chevron');
            toggle?.setAttribute('aria-expanded', 'false');
            menu?.classList.remove('show');
            chevron?.classList.remove('rotated');
        });
    };

    navDropdowns.forEach((dropdown) => {
        const toggle = dropdown.querySelector('.dropdown-toggle');
        const menu = dropdown.querySelector('.dropdown-menu');
        const chevron = dropdown.querySelector('.nav-chevron');
        if (!toggle || !menu) return;

        toggle.addEventListener('click', (e) => {
            e.preventDefault();
            e.stopPropagation();

            const willOpen = !menu.classList.contains('show');
            closeNavDropdowns(dropdown);

            dropdown.classList.toggle('show', willOpen);
            menu.classList.toggle('show', willOpen);
            toggle.setAttribute('aria-expanded', willOpen ? 'true' : 'false');
            chevron?.classList.toggle('rotated', willOpen);
        });
    });

    // Mobile categories chevron
    const catCollapse = document.getElementById('mobileCatCollapse');
    const catBtn = document.querySelector('[data-bs-target="#mobileCatCollapse"]');
    catCollapse?.addEventListener('show.bs.collapse', () => catBtn?.querySelector('.mobile-chevron')?.classList.add('rotated'));
    catCollapse?.addEventListener('hide.bs.collapse', () => catBtn?.querySelector('.mobile-chevron')?.classList.remove('rotated'));

    // Fallback dropdown toggle for user pill in case Bootstrap events are prevented
    const userToggle = document.getElementById('userDropdown');
    const userMenu = userToggle?.parentElement?.querySelector('.dropdown-menu');
    if (userToggle && userMenu) {
        userToggle.addEventListener('click', (e) => {
            e.preventDefault();
            e.stopPropagation();
            userMenu.classList.toggle('show');
            userToggle.parentElement.classList.toggle('show');
        });
        document.addEventListener('click', (e) => {
            closeNavDropdowns();
            if (!userMenu.contains(e.target) && !userToggle.contains(e.target)) {
                userMenu.classList.remove('show');
                userToggle.parentElement.classList.remove('show');
            }
        });
    } else {
        document.addEventListener('click', () => {
            closeNavDropdowns();
        });
    }
});
</script>

<div class="modal fade" id="searchModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content border-0 shadow-lg rounded-4">
            <div class="modal-body p-4">
                <form action="book-list.php" method="GET">
                    <div class="input-group input-group-lg">
                        <span class="input-group-text bg-transparent border-0"><i class="fas fa-search text-muted"></i></span>
                        <input type="text" name="q" class="form-control border-0 bg-transparent shadow-none" placeholder="Search books, authors, or categories..." autofocus>
                        <button type="submit" class="btn btn-primary rounded-pill px-4 ms-2">Search</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
