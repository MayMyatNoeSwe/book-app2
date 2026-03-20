<?php
// views/navbar.php
use App\Auth;

displayFlashMessage();

$navCats = getCategories();
//var_dump($navCats);
?>
<nav class="navbar navbar-expand-lg navbar-premium sticky-top py-3" style="z-index: 1050 !important;margin-bottom:500px">
    <div class="container-fluid px-3 px-lg-5">
        <div class="row w-100 align-items-center g-0">
            <!-- 1. Left Section (Nav Links) -->
            <div class="col-4 d-flex align-items-center" style="overflow: visible !important;">
                <!-- Mobile Hamburger -->
                <button class="navbar-toggler d-lg-none border-0 nav-hamburger me-2" type="button"
                        data-bs-toggle="collapse" data-bs-target="#navbarNav"
                        style="box-shadow:none;background:transparent;padding:6px;">
                    <span class="navbar-toggler-icon"></span>
                </button>

                <!-- Desktop Links -->
                <ul class="navbar-nav flex-row align-items-center d-none d-lg-flex mb-0">
                   
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle d-flex align-items-center" href="#" id="browseDropdown"
                           role="button" data-bs-toggle="dropdown" aria-expanded="false">
                           CATEGORIES <i class="fas fa-chevron-down ms-2 nav-chevron"></i>
                        </a>
                        <ul class="dropdown-menu dropdown-menu-start border-0 shadow" aria-labelledby="browseDropdown" style="z-index: 9999 !important; min-width: 250px;">
                            <?php  
                         $navCats = [ 'Fiction',
        'Non-Fiction',
        'Mystery',
        'Romance',
        'Sci-Fi',
        'Fantasy',
        'Biography',
        'History',
        'Self-Help',
        'Children',
        'Horror',
        'Thriller',
        'Poetry',
        'Uncategorized'];
        var_dump($navCats);
        //die();
                            ?>
                            <?php foreach ($navCats as $navCat): ?>
                            <li><a class="dropdown-item" href="<?= baseUrl() ?>/book-list.php?category=<?= urlencode($navCat) ?>"><?= e($navCat) ?></a></li>
                            <?php endforeach; ?>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item" href="<?= baseUrl() ?>/book-list.php">View All Books</a></li>
                        </ul>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link nav-text-only" href="<?= baseUrl() ?>/book-list.php?sort=borrowed&order=desc">TOP SELLERS</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link nav-icon-search" href="#" data-bs-toggle="modal" data-bs-target="#searchModal">
                            <i class="fas fa-search"></i>
                        </a>
                    </li>
                </ul>
            </div>

            <!-- 2. Logo (Absolute Center) -->
            <div class="col-4 d-flex justify-content-center px-0">
                <a class="navbar-brand fw-bold m-0" href="<?= baseUrl() ?>/index.php">
                    <span class="brand-book">Book</span><span class="brand-house">House</span>
                </a>
            </div>

            <!-- 3. Right Icons (End) -->
            <div class="col-4 d-flex align-items-center justify-content-end gap-1">
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
        </div>

        <!-- 4. Mobile Drawer (Collapse Only) -->
        <div class="collapse navbar-collapse nav-collapse-drawer" id="navbarNav">
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
                    <li><a class="mobile-nav-link" href="<?= baseUrl() ?>/book-list.php?sort=borrowed&order=desc"><i class="fas fa-fire"></i> Top Sellers</a></li>
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
    const updateTheme = (t) => {
        html.setAttribute('data-bs-theme', t);
        localStorage.setItem('theme', t);
        if (icon) icon.className = t === 'dark' ? 'fas fa-sun' : 'fas fa-moon';
    };
    updateTheme(localStorage.getItem('theme') || 'light');
    toggle?.addEventListener('click', () => updateTheme(html.getAttribute('data-bs-theme') === 'dark' ? 'light' : 'dark'));

    // Dropdown chevron rotation using Bootstrap events
    document.querySelectorAll('.dropdown').forEach(dropdown => {
        dropdown.addEventListener('show.bs.dropdown', () => dropdown.querySelector('.nav-chevron')?.classList.add('rotated'));
        dropdown.addEventListener('hide.bs.dropdown', () => dropdown.querySelector('.nav-chevron')?.classList.remove('rotated'));
    });
    
    // Mobile categories chevron
    const catCollapse = document.getElementById('mobileCatCollapse');
    const catBtn = document.querySelector('[data-bs-target="#mobileCatCollapse"]');
    catCollapse?.addEventListener('show.bs.collapse', () => catBtn?.querySelector('.mobile-chevron')?.classList.add('rotated'));
    catCollapse?.addEventListener('hide.bs.collapse', () => catBtn?.querySelector('.mobile-chevron')?.classList.remove('rotated'));
});
</script>

<div class="modal fade" id="searchModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content border-0 shadow-lg rounded-4">
            <div class="modal-body p-4">
                <form action="book-list.php" method="GET">
                    <div class="input-group input-group-lg">
                        <span class="input-group-text bg-transparent border-0"><i class="fas fa-search text-muted"></i></span>
                        <input type="text" name="search" class="form-control border-0 bg-transparent shadow-none" placeholder="Search books, authors, or categories..." autofocus>
                        <button type="submit" class="btn btn-primary rounded-pill px-4 ms-2">Search</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
