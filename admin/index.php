<?php
// admin/dashboard.php
require_once dirname(__DIR__) . '/vendor/autoload.php';
require_once dirname(__DIR__) . '/includes/sessions.php';
require_once dirname(__DIR__) . '/includes/env_loader.php';
require_once dirname(__DIR__) . '/includes/functions.php';
require_once dirname(__DIR__) . '/views/admin/layout.php';

// Mock data for dashboard (normally you would fetch this from DB)
$stats = [
    'total_books' => 1250,
    'total_users' => 450,
    'total_borrowed' => 85,
    'monthly_revenue' => 3450.50
];

$recent_activities = [
    ['user' => 'John Doe', 'action' => 'borrowed "The Great Gatsby"', 'time' => '2 hours ago'],
    ['user' => 'Jane Smith', 'action' => 'registered as new member', 'time' => '5 hours ago'],
    ['user' => 'System', 'action' => 'database backup completed', 'time' => '1 day ago'],
    ['user' => 'Mike Johnson', 'action' => 'reviewed "1984" - 5 stars', 'time' => '2 days ago'],
];

renderAdminLayout('System Overview', function() use ($stats, $recent_activities) {
    ?>
    <section class="dashboard-stats mb-5">
        <div class="row g-4">
            <!-- Total Books Stat -->
            <div class="col-xl-3 col-md-6">
                <div class="card card-admin p-3 border-start border-primary border-4">
                    <div class="card-body d-flex justify-content-between align-items-center">
                        <div class="stat-content">
                            <p class="text-xs font-weight-bold text-primary text-uppercase mb-1 small fw-bold">Books in Library</p>
                            <h3 class="h2 mb-0 fw-bold"><?= number_format($stats['total_books']) ?></h3>
                        </div>
                        <div class="stat-icon bg-primary-soft">
                            <i class="fas fa-book-open fa-2x"></i>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Total Users Stat -->
            <div class="col-xl-3 col-md-6">
                <div class="card card-admin p-3 border-start border-success border-4">
                    <div class="card-body d-flex justify-content-between align-items-center">
                        <div class="stat-content">
                            <p class="text-xs font-weight-bold text-success text-uppercase mb-1 small fw-bold">Active Users</p>
                            <h3 class="h2 mb-0 fw-bold"><?= number_format($stats['total_users']) ?></h3>
                        </div>
                        <div class="stat-icon bg-success-soft">
                            <i class="fas fa-users-cog fa-2x"></i>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Total Borrowed Stat -->
            <div class="col-xl-3 col-md-6">
                <div class="card card-admin p-3 border-start border-info border-4">
                    <div class="card-body d-flex justify-content-between align-items-center">
                        <div class="stat-content">
                            <p class="text-xs font-weight-bold text-info text-uppercase mb-1 small fw-bold">Borrowed This Week</p>
                            <h3 class="h2 mb-0 fw-bold"><?= number_format($stats['total_borrowed']) ?></h3>
                        </div>
                        <div class="stat-icon bg-info-soft">
                            <i class="fas fa-hand-holding-heart fa-2x"></i>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Revenue Stat -->
            <div class="col-xl-3 col-md-6">
                <div class="card card-admin p-3 border-start border-warning border-4">
                    <div class="card-body d-flex justify-content-between align-items-center">
                        <div class="stat-content">
                            <p class="text-xs font-weight-bold text-warning text-uppercase mb-1 small fw-bold">Monthly Revenue</p>
                            <h3 class="h2 mb-0 fw-bold">$<?= number_format($stats['monthly_revenue'], 2) ?></h3>
                        </div>
                        <div class="stat-icon bg-warning-soft">
                            <i class="fas fa-dollar-sign fa-2x"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Detailed Stats and Recent Activity -->
    <div class="row g-4">
        <!-- Main Chart/Stats Placeholder -->
        <div class="col-lg-8">
            <div class="card card-admin h-100">
                <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center border-0">
                    <h5 class="m-0 card-title-admin fw-bold">Library Traffic Overview</h5>
                    <button class="btn btn-sm btn-light border" type="button"><i class="fas fa-ellipsis-v"></i></button>
                </div>
                <div class="card-body p-4 text-center">
                    <div class="chart-placeholder d-flex flex-column align-items-center justify-content-center py-5">
                        <img src="https://via.placeholder.com/400x200?text=Weekly+Traffic+Chart" alt="Weekly Chart Placeholder" class="img-fluid opacity-25 mb-4">
                        <p class="text-muted small">Visualizing traffic and activity trends across the platform.</p>
                        <a href="#" class="btn btn-primary rounded-pill btn-sm px-4 shadow-sm mt-3">View Detailed Report</a>
                    </div>
                </div>
            </div>
        </div>

        <!-- Recent Activity Feed -->
        <div class="col-lg-4">
            <div class="card card-admin h-100">
                <div class="card-header bg-white py-3 border-0">
                    <h5 class="m-0 card-title-admin fw-bold">Recent Updates</h5>
                </div>
                <div class="card-body p-4 pt-0">
                    <div class="activity-feed">
                        <?php foreach ($recent_activities as $activity): ?>
                        <div class="activity-item d-flex gap-3 mb-4 last-child-mb-0 pb-3 border-bottom border-light">
                            <div class="activity-icon-sm rounded-circle bg-light d-flex align-items-center justify-content-center" style="width: 2.5rem; height: 2.5rem; flex-shrink: 0;">
                                <i class="fas fa-bell text-secondary opacity-50"></i>
                            </div>
                            <div class="activity-info">
                                <p class="mb-0 text-dark" style="font-size: 0.95rem;">
                                    <strong><?= e($activity['user']) ?></strong> <?= e($activity['action']) ?>
                                </p>
                                <p class="mb-0 text-muted small mt-1"><i class="far fa-clock me-1"></i> <?= e($activity['time']) ?></p>
                            </div>
                        </div>
                        <?php endforeach; ?>
                        
                        <div class="text-center mt-3">
                            <a href="#" class="text-decoration-none small fw-bold text-primary">View All Activities →</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <?php
});
