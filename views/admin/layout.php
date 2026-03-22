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
    <div class="admin-wrapper d-flex">
        <?php include APP_ROOT . '/views/admin/sidebar.php'; ?>
        
        <main class="admin-main flex-grow-1">
            <header class="admin-top-bar d-flex justify-content-between align-items-center p-3 mb-4 bg-white shadow-sm rounded-4">
                <div class="top-bar-left">
                    <h1 class="h4 mb-0 fw-bold text-dark"><?= e($pageTitle) ?></h1>
                    <nav aria-label="breadcrumb">
                        <ol class="breadcrumb mb-0" style="font-size: 0.85rem;">
                            <li class="breadcrumb-item"><a href="<?= baseUrl() ?>/admin/index.php" class="text-decoration-none text-muted">Admin</a></li>
                            <li class="breadcrumb-item active" aria-current="page"><?= e($pageTitle) ?></li>
                        </ol>
                    </nav>
                </div>
                <div class="top-bar-right d-flex align-items-center gap-3">
                    <div class="dropdown">
                        <button class="btn btn-light rounded-pill dropdown-toggle d-flex align-items-center gap-2 border shadow-sm px-3" type="button" data-bs-toggle="dropdown">
                            <i class="fas fa-user-circle text-primary"></i>
                            <span><?= e($_SESSION['user_name'] ?? 'Admin') ?></span>
                        </button>
                        <ul class="dropdown-menu dropdown-menu-end shadow border-0 rounded-3">
                            <li><a class="dropdown-item py-2" href="<?= baseUrl() ?>/index.php"><i class="fas fa-home me-2 text-muted"></i>View Website</a></li>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item py-2 text-danger" href="<?= baseUrl() ?>/logout.php"><i class="fas fa-sign-out-alt me-2"></i>Logout</a></li>
                        </ul>
                    </div>
                </div>
            </header>

            <div class="admin-content px-3 pb-4">
                <?php $contentRenderer(); ?>
            </div>
        </main>
    </div>
    <?php
    include APP_ROOT . '/views/admin/footer.php';
}
