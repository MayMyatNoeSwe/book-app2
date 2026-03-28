<?php
$currentScript = basename($_SERVER['SCRIPT_NAME']);
?>
<aside class="admin-sidebar d-flex flex-column" id="adminSidebar">
    <div class="sidebar-logo">
        <a href="<?= baseUrl() ?>/admin/index.php" class="text-decoration-none d-flex align-items-center gap-2">
            <div class="logo-box bg-primary rounded-3 d-flex align-items-center justify-content-center shadow-primary" style="width: 38px; height: 38px;">
                <i class="fas fa-shield-alt text-white"></i>
            </div>
            <span class="fs-4 fw-800 text-dark"><?= e(getSetting('site_name', 'BookHouse')) ?></span>
        </a>
    </div>

    <div class="sidebar-menu flex-grow-1 overflow-auto">
        <div class="menu-label">STRATEGY</div>
        <ul class="nav flex-column gap-1">
            <li class="nav-item">
                <a href="<?= baseUrl() ?>/admin/index.php" class="nav-link <?= $currentScript === 'index.php' ? 'active' : '' ?>">
                    <i class="fas fa-grid-2"></i>
                    <span>Dashboard</span>
                </a>
            </li>
            <li class="nav-item">
                <a href="<?= baseUrl() ?>/admin/books.php" class="nav-link <?= $currentScript === 'books.php' ? 'active' : '' ?>">
                    <i class="fas fa-book-sparkles"></i>
                    <span>Book Inventory</span>
                </a>
            </li>
        </ul>

        <div class="menu-label">MANAGEMENT</div>
        <ul class="nav flex-column gap-1">
            <li class="nav-item">
                <a href="<?= baseUrl() ?>/admin/borrows.php" class="nav-link <?= $currentScript === 'borrows.php' ? 'active' : '' ?>">
                    <i class="fas fa-hand-holding-heart"></i>
                    <span>Borrows</span>
                    <?php
                    // Show pending count badge
                    try {
                        $bPdo = (new \App\Library())->getPdo();
                        $pendingCount = (int)$bPdo->query("SELECT COUNT(*) FROM borrowing_history WHERE `status` IN ('pending','return_pending')")->fetchColumn();
                        if ($pendingCount > 0):
                    ?>
                        <span class="badge bg-danger rounded-pill ms-auto" style="font-size:10px;"><?= $pendingCount ?></span>
                    <?php endif; } catch(Exception $e) {} ?>
                </a>
            </li>
            <li class="nav-item">
                <a href="<?= baseUrl() ?>/admin/sales.php" class="nav-link <?= $currentScript === 'sales.php' ? 'active' : '' ?>">
                    <i class="fas fa-cart-shopping"></i>
                    <span>Sales</span>
                </a>
            </li>
            <li class="nav-item">
                <a href="<?= baseUrl() ?>/admin/categories.php" class="nav-link <?= $currentScript === 'categories.php' ? 'active' : '' ?>">
                    <i class="fas fa-layer-group"></i>
                    <span>Categories</span>
                </a>
            </li>
            <li class="nav-item">
                <a href="<?= baseUrl() ?>/admin/users.php" class="nav-link <?= $currentScript === 'users.php' ? 'active' : '' ?>">
                    <i class="fas fa-user-shield"></i>
                    <span>User Access</span>
                </a>
            </li>
            <li class="nav-item">
                <a href="<?= baseUrl() ?>/admin/membership.php" class="nav-link <?= $currentScript === 'membership.php' ? 'active' : '' ?>">
                    <i class="fas fa-gem"></i>
                    <span>Membership</span>
                    <?php
                    try {
                        $mPdo = (new \App\Library())->getPdo();
                        $msPending = (int)$mPdo->query("SELECT COUNT(*) FROM membership_requests WHERE `status` = 'pending'")->fetchColumn();
                        if ($msPending > 0):
                    ?>
                        <span class="badge bg-indigo rounded-pill ms-auto" style="font-size:10px; background:#6366f1 !important;"><?= $msPending ?></span>
                    <?php endif; } catch(Exception $e) {} ?>
                </a>
            </li>
            <li class="nav-item">
                <a href="<?= baseUrl() ?>/admin/membership_codes.php" class="nav-link <?= $currentScript === 'membership_codes.php' ? 'active' : '' ?>">
                    <i class="fas fa-ticket-alt"></i>
                    <span>Membership Keys</span>
                </a>
            </li>
            <li class="nav-item">
                <a href="<?= baseUrl() ?>/admin/messages.php" class="nav-link <?= $currentScript === 'messages.php' ? 'active' : '' ?>">
                    <i class="fas fa-envelope-open-text"></i>
                    <span>Messages</span>
                    <?php
                    // Show unread count badge
                    try {
                        $mPdo = (new \App\Library())->getPdo();
                        $unreadCount = (int)$mPdo->query("SELECT COUNT(*) FROM contact_messages WHERE `status` = 'unread'")->fetchColumn();
                        if ($unreadCount > 0):
                    ?>
                        <span class="badge bg-primary rounded-pill ms-auto" style="font-size:10px;"><?= $unreadCount ?></span>
                    <?php endif; } catch(Exception $e) {} ?>
                </a>
            </li>
            <li class="nav-item">
                <a href="<?= baseUrl() ?>/admin/activities.php" class="nav-link <?= $currentScript === 'activities.php' ? 'active' : '' ?>">
                    <i class="fas fa-history"></i>
                    <span>Activity Log</span>
                </a>
            </li>
            <li class="nav-item">
                <a href="<?= baseUrl() ?>/admin/accounting.php" class="nav-link <?= in_array($currentScript, ['accounting.php', 'expenses.php']) ? 'active' : '' ?>">
                    <i class="fas fa-coins text-warning"></i>
                    <span>Accounting</span>
                </a>
            </li>
        </ul>

        <div class="menu-label">PREMIUM TOOLS</div>
        <ul class="nav flex-column gap-1">
            <li class="nav-item">
                <a href="<?= baseUrl() ?>/admin/reports.php" class="nav-link <?= $currentScript === 'reports.php' ? 'active' : '' ?>">
                    <i class="fas fa-analytics"></i>
                    <span>Insights</span>
                </a>
            </li>
            <li class="nav-item">
                <a href="<?= baseUrl() ?>/admin/settings.php" class="nav-link <?= $currentScript === 'settings.php' ? 'active' : '' ?>">
                    <i class="fas fa-sliders-h"></i>
                    <span>Settings</span>
                </a>
            </li>
        </ul>
    </div>

    <div class="sidebar-footer p-4 border-top border-light-subtle">
        <div class="d-flex align-items-center gap-3">
            <div class="avatar-wrap position-relative">
                <img src="https://ui-avatars.com/api/?name=<?= urlencode($_SESSION['user_name'] ?? 'Admin') ?>&background=4e73df&color=fff" class="rounded-pill shadow-sm" style="width: 42px; height: 42px;" alt="Avatar">
                <span class="position-absolute bottom-0 end-0 bg-success border border-white border-2 rounded-circle" style="width: 12px; height: 12px;"></span>
            </div>
            <div class="user-meta flex-grow-1 overflow-hidden">
                <h6 class="mb-0 fw-bold text-dark text-truncate" style="font-size: 0.9rem;"><?= e($_SESSION['user_name'] ?? 'Admin') ?></h6>
                <p class="mb-0 text-muted smallest text-truncate">Administrator</p>
            </div>
            <a href="<?= baseUrl() ?>/logout.php" class="text-muted hover-text-danger" title="Logout">
                <i class="fas fa-sign-out-alt"></i>
            </a>
        </div>
    </div>
</aside>
