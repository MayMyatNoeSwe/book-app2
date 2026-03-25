<?php
// views/admin/layout.php
/**
 * Admin Layout Wrapper
 * @param string $pageTitle
 * @param callable $contentRenderer
 */
function renderAdminLayout(string $pageTitle, callable $contentRenderer): void
{
    requireAdmin();
    include APP_ROOT . '/views/admin/header.php';
    ?>
    <div class="admin-wrapper">
        <?php include APP_ROOT . '/views/admin/sidebar.php'; ?>
        
        <main class="admin-main">
            <header class="admin-top-bar mt-2">
                <div class="top-bar-left">
                    <nav aria-label="breadcrumb" class="mb-1">
                        <ol class="breadcrumb smallest text-uppercase letter-spacing-1 mb-0">
                            <li class="breadcrumb-item"><a href="<?= baseUrl() ?>/admin/index.php" class="text-decoration-none text-muted">Admin System</a></li>
                            <li class="breadcrumb-item active fw-bold text-primary" aria-current="page"><?= e($pageTitle) ?></li>
                        </ol>
                    </nav>
                    <h1 class="h3 mb-0 fw-800 text-dark"><?= e($pageTitle) ?></h1>
                </div>
                <div class="top-bar-right d-flex align-items-center gap-4">
                    <div class="search-box d-none d-md-block">
                        <div class="input-group input-group-sm bg-white rounded-pill border px-2">
                            <span class="input-group-text bg-transparent border-0"><i class="fas fa-search text-muted small"></i></span>
                            <input type="text" class="form-control bg-transparent border-0 shadow-none ps-0" placeholder="Fast search...">
                        </div>
                    </div>
                    <div class="notifications me-2 position-relative cursor-pointer">
                        <i class="fas fa-bell text-muted"></i>
                        <span class="position-absolute top-0 start-100 translate-middle p-1 bg-danger border border-light rounded-circle" style="width: 8px; height: 8px;"></span>
                    </div>
                    <div class="dropdown">
                        <button class="btn btn-white border-0 p-0 dropdown-toggle d-flex align-items-center gap-2 shadow-none" type="button" data-bs-toggle="dropdown">
                            <div class="user-avatar-mini rounded-pill bg-primary-soft text-primary d-flex align-items-center justify-content-center fw-bold" style="width: 34px; height: 34px; font-size: 0.8rem;">
                                <?= substr($_SESSION['user_name'] ?? 'A', 0, 1) ?>
                            </div>
                            <span class="d-none d-lg-inline fw-semibold text-dark small ms-1"><?= e($_SESSION['user_name'] ?? 'Admin') ?></span>
                        </button>
                        <ul class="dropdown-menu dropdown-menu-end shadow-lg border-0 rounded-4 p-2 mt-2 animate__animated animate__fadeIn">
                            <li><a class="dropdown-item py-2 px-3 rounded-3" href="<?= baseUrl() ?>/admin/index.php"><i class="fas fa-chart-pie me-2 opacity-50 small"></i>Overview</a></li>
                            <li><a class="dropdown-item py-2 px-3 rounded-3" href="<?= baseUrl() ?>/admin/settings.php"><i class="fas fa-user-gear me-2 opacity-50 small"></i>Account Setup</a></li>
                            <li><hr class="dropdown-divider opacity-50"></li>
                            <li><a class="dropdown-item py-2 px-3 rounded-3 text-danger" href="<?= baseUrl() ?>/logout.php"><i class="fas fa-door-open me-2 small"></i>Log Out</a></li>
                        </ul>
                    </div>
                </div>
            </header>

            <div class="admin-content mt-2">
                <?php $contentRenderer(); ?>
            </div>
        </main>
    </div>
    <?php
    include APP_ROOT . '/views/admin/footer.php';
}
