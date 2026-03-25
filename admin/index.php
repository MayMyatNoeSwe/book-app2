<?php
// admin/index.php
require_once dirname(__DIR__) . '/vendor/autoload.php';
require_once dirname(__DIR__) . '/includes/sessions.php';
require_once dirname(__DIR__) . '/includes/env_loader.php';
require_once dirname(__DIR__) . '/includes/functions.php';
require_once dirname(__DIR__) . '/views/admin/layout.php';

// Mock data for dashboard
$stats = [
    'total_books' => 1250,
    'total_users' => 450,
    'total_borrowed' => 85,
    'monthly_revenue' => 3450500 // In KS
];

$recent_activities = [
    ['user' => 'John Doe', 'action' => 'borrowed "The Great Gatsby"', 'time' => '2 hours ago', 'type' => 'borrow'],
    ['user' => 'Jane Smith', 'action' => 'registered as new member', 'time' => '5 hours ago', 'type' => 'user'],
    ['user' => 'System', 'action' => 'database backup completed', 'time' => '1 day ago', 'type' => 'system']
];

renderAdminLayout('Dashboard Overview', function() use ($stats, $recent_activities) {
    ?>
    <section class="premium-hero mb-5 rounded-5 overflow-hidden position-relative p-5 border border-light-subtle shadow-sm bg-white">
        <div class="hero-pattern position-absolute top-0 start-0 w-100 h-100 opacity-5" style="background-image: radial-gradient(#4e73df 1px, transparent 1px); background-size: 30px 30px; z-index: 2;"></div>
        <div class="position-relative" style="z-index: 3;">
            <div class="row align-items-center">
                <div class="col-lg-8">
                    <h3 class="text-dark fw-800 mb-2">Welcome back, Admin</h3>
                    <p class="text-muted mb-4 fs-6 fw-500">You have 12 new notifications and 3 library requests pending. Let's get started.</p>
                    <div class="d-flex gap-2">
                        <a href="<?= baseUrl() ?>/admin/books.php" class="btn btn-primary rounded-pill px-4 fw-bold shadow-sm">Manage Collection</a>
                        <button class="btn btn-outline-secondary border rounded-pill px-4 fw-bold text-dark shadow-sm bg-white">View Analytics</button>
                    </div>
                </div>
                <div class="col-lg-4 d-none d-lg-block text-end">
                    <div class="bg-white rounded-circle shadow-sm d-inline-flex align-items-center justify-content-center border border-light" style="width: 140px; height: 140px;">
                        <i class="fas fa-chart-pie text-primary" style="font-size: 3.5rem;"></i>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <div class="row g-4 mb-5">
        <!-- Stat Cards -->
        <div class="col-xl-3 col-md-6">
            <div class="card border-0 shadow-sm rounded-4 p-4 bg-white">
                <div class="d-flex align-items-center gap-3 mb-3">
                    <div class="stat-icon-premium bg-primary-soft text-primary shadow-sm">
                        <i class="fas fa-book"></i>
                    </div>
                    <span class="badge bg-success-soft text-success rounded-pill px-2 py-1 smallest h6 mb-0">+12%</span>
                </div>
                <h3 class="fw-800 mb-1 text-dark"><?= number_format($stats['total_books']) ?></h3>
                <p class="text-muted small text-uppercase fw-bold letter-spacing-1 mb-0">Total Documents</p>
            </div>
        </div>
        <div class="col-xl-3 col-md-6">
            <div class="card border-0 shadow-sm rounded-4 p-4 bg-white">
                <div class="d-flex align-items-center gap-3 mb-3">
                    <div class="stat-icon-premium bg-success-soft text-success shadow-sm">
                        <i class="fas fa-users"></i>
                    </div>
                    <span class="badge bg-success-soft text-success rounded-pill px-2 py-1 smallest h6 mb-0">+5.4%</span>
                </div>
                <h3 class="fw-800 mb-1 text-dark"><?= number_format($stats['total_users']) ?></h3>
                <p class="text-muted small text-uppercase fw-bold letter-spacing-1 mb-0">Active Members</p>
            </div>
        </div>
        <div class="col-xl-3 col-md-6">
            <div class="card border-0 shadow-sm rounded-4 p-4 bg-white">
                <div class="d-flex align-items-center gap-3 mb-3">
                    <div class="stat-icon-premium bg-info-soft text-info shadow-sm">
                        <i class="fas fa-hand-holding-heart"></i>
                    </div>
                    <span class="badge bg-danger-soft text-danger rounded-pill px-2 py-1 smallest h6 mb-0">-1.2%</span>
                </div>
                <h3 class="fw-800 mb-1 text-dark"><?= number_format($stats['total_borrowed']) ?></h3>
                <p class="text-muted small text-uppercase fw-bold letter-spacing-1 mb-0">Weekly Borrows</p>
            </div>
        </div>
        <div class="col-xl-3 col-md-6">
            <div class="card border-0 shadow-sm rounded-4 p-4 bg-white">
                <div class="d-flex align-items-center gap-3 mb-3">
                    <div class="stat-icon-premium bg-warning-soft text-warning shadow-sm">
                        <i class="fas fa-gem"></i>
                    </div>
                    <span class="badge bg-success-soft text-success rounded-pill px-2 py-1 smallest h6 mb-0">+18%</span>
                </div>
                <h3 class="fw-800 mb-1 text-dark"><?= number_format($stats['monthly_revenue'] / 1000) ?>k</h3>
                <p class="text-muted small text-uppercase fw-bold letter-spacing-1 mb-0">Monthly Revenue (KS)</p>
            </div>
        </div>
    </div>

    <!-- Analytics Section -->
    <div class="row g-4 mb-4">
        <div class="col-lg-8">
            <div class="card border-0 shadow-sm rounded-4 bg-white h-100">
                <div class="card-header border-0 bg-transparent p-4 pb-0 d-flex justify-content-between align-items-center">
                    <div>
                        <h5 class="fw-800 text-dark mb-1">Engagement Analytics</h5>
                        <p class="text-muted smallest fw-bold m-0 text-uppercase tracking-wider">PLATFORM PERFORMANCE TRENDS</p>
                    </div>
                    <div class="badge bg-light text-dark py-2 px-3 border rounded-pill">Last 30 Days</div>
                </div>
                <div class="card-body p-4">
                    <div class="chart-container" style="position: relative; height: 300px;">
                        <canvas id="trafficChart"></canvas>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-lg-4">
            <div class="card border-0 shadow-sm rounded-4 bg-white h-100">
                <div class="card-header border-0 bg-transparent p-4 pb-0">
                    <h5 class="fw-800 text-dark mb-1">Real-time Activity</h5>
                    <p class="text-muted smallest fw-bold m-0 text-uppercase tracking-wider">LIVE PLATFORM FEED</p>
                </div>
                <div class="card-body p-4">
                    <div class="timeline-premium">
                        <?php foreach($recent_activities as $act): ?>
                        <div class="timeline-item-premium d-flex gap-3 mb-4" data-type="<?= $act['type'] ?>">
                            <div class="timeline-bg-icon bg-lightest text-primary">
                                <i class="fas fa-bolt small"></i>
                            </div>
                            <div>
                                <h6 class="mb-0 text-dark fw-bold" style="font-size: 0.9rem;"><?= e($act['user']) ?></h6>
                                <p class="mb-0 text-muted small"><?= e($act['action']) ?></p>
                                <span class="smallest text-muted opacity-75"><?= $act['time'] ?></span>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        const ctx = document.getElementById('trafficChart').getContext('2d');
        const gradient = ctx.createLinearGradient(0, 0, 0, 300);
        gradient.addColorStop(0, 'rgba(78, 115, 223, 0.1)');
        gradient.addColorStop(1, 'rgba(78, 115, 223, 0)');

        new Chart(ctx, {
            type: 'line',
            data: {
                labels: ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'],
                datasets: [{
                    label: 'Page Views',
                    data: [1200, 1900, 1500, 2500, 2200, 3100, 2800],
                    borderColor: '#4e73df',
                    backgroundColor: gradient,
                    borderWidth: 3,
                    fill: true,
                    tension: 0.4,
                    pointRadius: 4,
                    pointBackgroundColor: '#fff',
                    pointBorderColor: '#4e73df'
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: { legend: { display: false } },
                scales: {
                    x: { grid: { display: false }, ticks: { color: '#858796', font: { size: 11 } } },
                    y: { 
                        beginAtZero: true, 
                        grid: { color: '#f1f5f9' },
                        ticks: { color: '#858796', font: { size: 11 } }
                    }
                }
            }
        });
    });
    </script>
    <?php
});
