<?php
// admin/categories.php
require_once dirname(__DIR__) . '/vendor/autoload.php';
require_once dirname(__DIR__) . '/includes/sessions.php';
require_once dirname(__DIR__) . '/includes/env_loader.php';
require_once dirname(__DIR__) . '/includes/functions.php';
require_once dirname(__DIR__) . '/views/admin/layout.php';

use App\Library;
$library = new Library();
$pdo = $library->getPdo();

$stmt = $pdo->query("SELECT category, COUNT(*) as count FROM books GROUP BY category ORDER BY count DESC");
$categories = $stmt->fetchAll();

renderAdminLayout('Classification Management', function() use ($categories) {
    ?>
    <section class="premium-hero mb-5 rounded-5 overflow-hidden position-relative p-5 border border-light-subtle shadow-sm bg-lightest">
        <div class="hero-pattern position-absolute top-0 start-0 w-100 h-100 opacity-5" style="background-image: radial-gradient(#4e73df 1px, transparent 1px); background-size: 30px 30px; z-index: 2;"></div>
        <div class="position-relative" style="z-index: 3;">
            <div class="row align-items-center">
                <div class="col-lg-8">
                    <h3 class="text-dark fw-800 mb-2">Library Classifications</h3>
                    <p class="text-muted mb-4 fs-6 fw-500">Organize your collection by managing genres, sections, and metadata categories for easier discoverability.</p>
                </div>
                <div class="col-lg-4 d-none d-lg-block text-end">
                    <div class="bg-white rounded-circle shadow-sm d-inline-flex align-items-center justify-content-center" style="width: 140px; height: 140px;">
                        <i class="fas fa-layer-group text-primary" style="font-size: 3.5rem;"></i>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <div class="row g-4 mb-5">
        <?php foreach (array_slice($categories, 0, 3) as $cat): ?>
        <div class="col-lg-4">
            <div class="card card-admin border-0 shadow-sm rounded-4 p-4 text-center">
                <div class="stat-icon-premium bg-primary-soft text-primary shadow-sm mb-3 mx-auto">
                    <i class="fas fa-folder-open"></i>
                </div>
                <h4 class="fw-800 text-dark mb-1"><?= e($cat['category']) ?></h4>
                <p class="text-muted smallest fw-bold text-uppercase mb-0"><?= $cat['count'] ?> ACTIVE TITLES</p>
                <div class="mt-4">
                    <button class="btn btn-soft-primary rounded-pill px-4 btn-sm fw-bold">Manage Genre</button>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
    </div>

    <div class="card card-admin border-0 shadow-sm overflow-hidden">
        <div class="card-header bg-white py-4 border-0 d-flex justify-content-between align-items-center px-4">
            <div>
                <h5 class="mb-0 fw-800 text-dark">Category Analytics</h5>
                <p class="text-muted smallest fw-bold mb-0 text-uppercase tracking-wider">ORGANIZATIONAL OVERVIEW</p>
            </div>
            <button class="btn btn-primary rounded-pill px-4 fw-bold btn-sm shadow-sm"><i class="fas fa-plus me-2"></i>New Category</button>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead>
                        <tr class="bg-lightest border-bottom">
                            <th class="py-3 px-4 border-0 text-muted smallest fw-800 text-uppercase tracking-wider">Classification Name</th>
                            <th class="py-3 px-4 border-0 text-muted smallest fw-800 text-uppercase tracking-wider text-center">Volume</th>
                            <th class="py-3 px-4 border-0 text-muted smallest fw-800 text-uppercase tracking-wider text-end">Management</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($categories as $cat): ?>
                        <tr>
                            <td class="px-4 py-4">
                                <div class="d-flex align-items-center gap-3">
                                    <div class="avatar-sm bg-lightest rounded p-2 text-primary">
                                        <i class="fas fa-tag"></i>
                                    </div>
                                    <span class="fw-800 text-dark"><?= e($cat['category']) ?></span>
                                </div>
                            </td>
                            <td class="px-4 py-4 text-center">
                                <span class="badge bg-success-soft text-success rounded-pill px-3 py-2 border border-success-subtle smallest fw-800">
                                    <?= $cat['count'] ?> TITLES
                                </span>
                            </td>
                            <td class="px-4 py-4 text-end">
                                <button class="btn btn-icon-only rounded-pill btn-soft-primary me-1"><i class="fas fa-edit"></i></button>
                                <button class="btn btn-icon-only rounded-pill btn-soft-danger"><i class="fas fa-trash"></i></button>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <?php
});
