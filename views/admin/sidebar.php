<?php
// views/admin/sidebar.php
$currentScript = basename($_SERVER['SCRIPT_NAME']);
?>
<aside class="admin-sidebar d-flex flex-column p-4 flex-shrink-0 bg-white" style="width: 280px; min-height: 100vh;">
    <div class="sidebar-logo mb-5 px-3">
        <a href="<?= baseUrl() ?>/admin/index.php" class="text-decoration-none">
            <span class="fs-4 fw-bold text-dark"><i class="fas fa-shield-alt text-primary me-2"></i>Admin<span class="text-primary">Hub</span></span>
        </a>
    </div>

    <div class="sidebar-menu flex-grow-1 overflow-auto pe-2">
        <div class="menu-label text-muted small text-uppercase fw-bold mb-3 px-3">Main Menu</div>
        <ul class="nav nav-pills flex-column gap-2 mb-4">
            <li class="nav-item">
                <a href="<?= baseUrl() ?>/admin/index.php" class="nav-link <?= $currentScript === 'index.php' ? 'active shadow-primary' : 'text-dark' ?> py-3 px-4 rounded-4 transition-all d-flex align-items-center gap-3">
                    <i class="fas fa-th-large <?= $currentScript === 'index.php' ? 'text-white' : 'text-primary opacity-75' ?>"></i>
                    <span class="fw-semibold">Dashboard</span>
                </a>
            </li>
            <li class="nav-item">
                <a href="<?= baseUrl() ?>/admin/books.php" class="nav-link <?= $currentScript === 'books.php' ? 'active shadow-primary' : 'text-dark' ?> py-3 px-4 rounded-4 transition-all d-flex align-items-center gap-3">
                    <i class="fas fa-book <?= $currentScript === 'books.php' ? 'text-white' : 'text-success opacity-75' ?>"></i>
                    <span class="fw-semibold">Manage Books</span>
                </a>
            </li>
            <li class="nav-item">
                <a href="<?= baseUrl() ?>/admin/categories.php" class="nav-link <?= $currentScript === 'categories.php' ? 'active shadow-primary' : 'text-dark' ?> py-3 px-4 rounded-4 transition-all d-flex align-items-center gap-3">
                    <i class="fas fa-tags <?= $currentScript === 'categories.php' ? 'text-white' : 'text-warning opacity-75' ?>"></i>
                    <span class="fw-semibold">Categories</span>
                </a>
            </li>
            <li class="nav-item">
                <a href="<?= baseUrl() ?>/admin/users.php" class="nav-link <?= $currentScript === 'users.php' ? 'active shadow-primary' : 'text-dark' ?> py-3 px-4 rounded-4 transition-all d-flex align-items-center gap-3">
                    <i class="fas fa-users <?= $currentScript === 'users.php' ? 'text-white' : 'text-info opacity-75' ?>"></i>
                    <span class="fw-semibold">User Accounts</span>
                </a>
            </li>
        </ul>

        <div class="menu-label text-muted small text-uppercase fw-bold mb-3 px-3">Analytics & Settings</div>
        <ul class="nav nav-pills flex-column gap-2">
            <li class="nav-item">
                <a href="<?= baseUrl() ?>/admin/reports.php" class="nav-link <?= $currentScript === 'reports.php' ? 'active shadow-primary' : 'text-dark' ?> py-3 px-4 rounded-4 transition-all d-flex align-items-center gap-3">
                    <i class="fas fa-chart-line <?= $currentScript === 'reports.php' ? 'text-white' : 'text-danger opacity-75' ?>"></i>
                    <span class="fw-semibold">Reports</span>
                </a>
            </li>
            <li class="nav-item">
                <a href="<?= baseUrl() ?>/admin/settings.php" class="nav-link <?= $currentScript === 'settings.php' ? 'active shadow-primary' : 'text-dark' ?> py-3 px-4 rounded-4 transition-all d-flex align-items-center gap-3">
                    <i class="fas fa-cog <?= $currentScript === 'settings.php' ? 'text-white' : 'text-secondary opacity-75' ?>"></i>
                    <span class="fw-semibold">System Settings</span>
                </a>
            </li>
        </ul>
    </div>

    <div class="sidebar-footer mt-5 px-3">
        <div class="user-pill d-flex align-items-center gap-3 p-3 bg-light rounded-4 border">
            <div class="avatar-circle rounded-circle bg-primary text-white d-flex align-items-center justify-content-center fw-bold" style="width: 40px; height: 40px; font-size: 0.9rem;">
                <?= substr($_SESSION['user_name'] ?? 'A', 0, 1) ?>
            </div>
            <div class="user-info overflow-hidden">
                <p class="mb-0 fw-bold text-truncate" style="font-size: 0.9rem;"><?= e($_SESSION['user_name'] ?? 'Admin') ?></p>
                <p class="mb-0 text-muted small text-truncate"><?= e($_SESSION['email'] ?? 'admin@test.com') ?></p>
            </div>
        </div>
    </div>
</aside>
