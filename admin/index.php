<?php
// admin/index.php
require_once dirname(__DIR__) . '/vendor/autoload.php';
require_once dirname(__DIR__) . '/includes/sessions.php';
require_once dirname(__DIR__) . '/includes/env_loader.php';
require_once dirname(__DIR__) . '/includes/functions.php';
require_once dirname(__DIR__) . '/views/admin/layout.php';
use App\Library;
$library = new Library();

// Fetch real data for dashboard
$pdo = $library->getPdo();

// Total Books
$totalBooks = $library->countBooks();

// Total Users
$stmt = $pdo->query("SELECT COUNT(*) FROM users");
$totalUsers = $stmt->fetchColumn();

// Total Borrowed (Current)
$stmt = $pdo->query("SELECT COUNT(*) FROM borrowing_history WHERE returned_at IS NULL");
$totalBorrowed = $stmt->fetchColumn();

// Monthly Revenue (from orders in the current month)
$stmt = $pdo->prepare("SELECT SUM(total_amount) FROM orders WHERE MONTH(created_at) = MONTH(CURRENT_DATE()) AND YEAR(created_at) = YEAR(CURRENT_DATE())");
$stmt->execute();
$monthlyRevenue = (float)$stmt->fetchColumn();

$stats = [
    'total_books' => $totalBooks,
    'total_users' => $totalUsers,
    'total_borrowed' => $totalBorrowed,
    'monthly_revenue' => $monthlyRevenue
];

// Fetch Recent Activities
$recent_activities = [];

// Recent Borrows
$stmt = $pdo->query("SELECT u.username, b.title, h.borrowed_at 
                     FROM borrowing_history h 
                     JOIN users u ON h.user_id = u.id 
                     JOIN books b ON h.book_id = b.id 
                     ORDER BY h.borrowed_at DESC LIMIT 3");
while ($row = $stmt->fetch()) {
    $recent_activities[] = [
        'user' => $row['username'],
        'action' => 'borrowed "' . $row['title'] . '"',
        'time' => time_elapsed_string($row['borrowed_at']),
        'type' => 'borrow'
    ];
}

// Recent Users
$stmt = $pdo->query("SELECT username, created_at FROM users ORDER BY created_at DESC LIMIT 2");
while ($row = $stmt->fetch()) {
    $recent_activities[] = [
        'user' => $row['username'],
        'action' => 'joined the community',
        'time' => time_elapsed_string($row['created_at']),
        'type' => 'user'
    ];
}

// Sort activities by time (assuming we can parse or just interleaving is fine for now)
// For simplicity, I'll just keep them as they are or fetch together with UNION
// But since they come from different tables with different schemas, separate is easier.

renderAdminLayout('Dashboard Overview', function() use ($stats, $recent_activities) {
    ?>
    <section class="premium-hero mb-4 rounded-5 overflow-hidden position-relative p-5 shadow-lg">
        <div class="hero-bg position-absolute top-0 start-0 w-100 h-100" style="background: linear-gradient(135deg, #4e73df 0%, #224abe 100%); z-index: 1;"></div>
        <div class="hero-pattern position-absolute top-0 start-0 w-100 h-100 opacity-10" style="background-image: radial-gradient(#fff 1px, transparent 1px); background-size: 20px 20px; z-index: 2;"></div>
        
        <div class="position-relative" style="z-index: 3;">
            <div class="row align-items-center">
                <div class="col-lg-8">
                    <h2 class="text-white fw-800 display-6 mb-2">Welcome back, <?= e($_SESSION['user_name'] ?? 'Admin') ?>!</h2>
                    <p class="text-white opacity-75 mb-4 fs-5">Your library is growing faster than ever. Here's what has happened since your last visit.</p>
                    <div class="d-flex gap-3">
                        <a href="<?= baseUrl() ?>/admin/books.php" class="btn btn-white rounded-pill px-4 fw-bold shadow-sm">Manage Collection</a>
                        <button class="btn btn-outline-white rounded-pill px-4 fw-bold">Generate Report</button>
                    </div>
                </div>
                <div class="col-lg-4 d-none d-lg-block text-end">
                    <i class="fas fa-chart-line text-white opacity-25" style="font-size: 10rem;"></i>
                </div>
            </div>
        </div>
    </section>

    <section class="dashboard-stats mb-5">
        <div class="row g-4">
            <!-- Total Books Stat -->
            <div class="col-xl-3 col-md-6">
                <div class="card card-premium-stat h-100 border-0 shadow-sm rounded-4 overflow-hidden">
                    <div class="card-body p-4 position-relative">
                        <div class="stat-glow bg-primary"></div>
                        <div class="d-flex justify-content-between mb-3 align-items-start position-relative">
                            <div class="stat-icon-premium bg-primary-soft text-primary shadow-sm">
                                <i class="fas fa-book"></i>
                            </div>
                            <span class="badge bg-success-soft text-success rounded-pill px-2 py-1 smallest h6 mb-0">+12% <i class="fas fa-arrow-up px-1 small"></i></span>
                        </div>
                        <h3 class="fw-800 display-6 mb-1"><?= number_format($stats['total_books']) ?></h3>
                        <p class="text-muted small text-uppercase fw-bold letter-spacing-1 mb-0">Total Documents</p>
                    </div>
                </div>
            </div>

            <!-- Total Users Stat -->
            <div class="col-xl-3 col-md-6">
                <div class="card card-premium-stat h-100 border-0 shadow-sm rounded-4 overflow-hidden">
                    <div class="card-body p-4 position-relative">
                        <div class="stat-glow bg-success"></div>
                        <div class="d-flex justify-content-between mb-3 align-items-start position-relative">
                            <div class="stat-icon-premium bg-success-soft text-success shadow-sm">
                                <i class="fas fa-users"></i>
                            </div>
                            <span class="badge bg-success-soft text-success rounded-pill px-2 py-1 smallest h6 mb-0">+5.4% <i class="fas fa-arrow-up px-1 small"></i></span>
                        </div>
                        <h3 class="fw-800 display-6 mb-1"><?= number_format($stats['total_users']) ?></h3>
                        <p class="text-muted small text-uppercase fw-bold letter-spacing-1 mb-0">Active Members</p>
                    </div>
                </div>
            </div>

            <!-- Total Borrowed Stat -->
            <div class="col-xl-3 col-md-6">
                <div class="card card-premium-stat h-100 border-0 shadow-sm rounded-4 overflow-hidden">
                    <div class="card-body p-4 position-relative">
                        <div class="stat-glow bg-info"></div>
                        <div class="d-flex justify-content-between mb-3 align-items-start position-relative">
                            <div class="stat-icon-premium bg-info-soft text-info shadow-sm">
                                <i class="fas fa-hand-holding-heart"></i>
                            </div>
                            <span class="badge bg-danger-soft text-danger rounded-pill px-2 py-1 smallest h6 mb-0">-1.2% <i class="fas fa-arrow-down px-1 small"></i></span>
                        </div>
                        <h3 class="fw-800 display-6 mb-1"><?= number_format($stats['total_borrowed']) ?></h3>
                        <p class="text-muted small text-uppercase fw-bold letter-spacing-1 mb-0">Weekly Borrows</p>
                    </div>
                </div>
            </div>

            <!-- Revenue Stat -->
            <div class="col-xl-3 col-md-6">
                <div class="card card-premium-stat h-100 border-0 shadow-sm rounded-4 overflow-hidden">
                    <div class="card-body p-4 position-relative">
                        <div class="stat-glow bg-warning"></div>
                        <div class="d-flex justify-content-between mb-3 align-items-start position-relative">
                            <div class="stat-icon-premium bg-warning-soft text-warning shadow-sm">
                                <i class="fas fa-gem"></i>
                            </div>
                            <span class="badge bg-success-soft text-success rounded-pill px-2 py-1 smallest h6 mb-0">+18% <i class="fas fa-arrow-up px-1 small"></i></span>
                        </div>
                        <h3 class="fw-800 display-6 mb-1"><?= number_format($stats['monthly_revenue'] / 1000) ?>k</h3>
                        <p class="text-muted small text-uppercase fw-bold letter-spacing-1 mb-0">Monthly Revenue (KS)</p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Main Content Area -->
    <div class="row g-4 mb-4">
        <div class="col-lg-8">
            <div class="card card-premium-main border-0 shadow-sm rounded-4 h-100">
                <div class="card-header border-0 bg-transparent p-4 pb-0 d-flex justify-content-between align-items-center">
                    <div class="header-info">
                        <h5 class="fw-800 text-dark mb-0">Library Engagement Analytics</h5>
                        <p class="text-muted small mb-0">Traffic trends and user interaction over the last 30 days.</p>
                    </div>
                    <select class="form-select form-select-sm rounded-pill border-light shadow-sm bg-light-hint" style="width: auto;">
                        <option>Last 30 Days</option>
                        <option>Last 6 Months</option>
                        <option>Last Year</option>
                    </select>
                </div>
                <div class="card-body p-4">
                    <div class="chart-container ratio ratio-21x9">
                        <canvas id="trafficChart"></canvas>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-4">
            <div class="card card-premium-main border-0 shadow-sm rounded-4 h-100">
                <div class="card-header border-0 bg-transparent p-4 pb-0">
                    <h5 class="fw-800 text-dark mb-0">Live Activity Feed</h5>
                </div>
                <div class="card-body p-4">
                    <div class="premium-timeline">
                        <?php 
                        $types = [
                            'borrow' => ['icon' => 'fas fa-book-reader', 'color' => 'primary'],
                            'user' => ['icon' => 'fas fa-user-plus', 'color' => 'success'],
                            'system' => ['icon' => 'fas fa-cog', 'color' => 'info'],
                            'star' => ['icon' => 'fas fa-star', 'color' => 'warning']
                        ];
                        foreach ($recent_activities as $activity): 
                            $type = $types[$activity['type']] ?? $types['system'];
                        ?>
                        <div class="timeline-item d-flex gap-3 pb-4 position-relative">
                            <div class="timeline-icon bg-<?= $type['color'] ?> shadow-<?= $type['color'] ?> text-white rounded-circle d-flex align-items-center justify-content-center mt-1" style="width: 32px; height: 32px; flex-shrink: 0; z-index: 2;">
                                <i class="<?= $type['icon'] ?> small"></i>
                            </div>
                            <div class="timeline-content">
                                <p class="mb-0 text-dark fw-600" style="font-size: 0.9rem;"><?= e($activity['user']) ?> <span class="fw-normal text-muted"><?= e($activity['action']) ?></span></p>
                                <p class="smallest text-muted mt-1"><i class="far fa-clock me-1"></i> <?= e($activity['time']) ?></p>
                            </div>
                        </div>
                        <?php endforeach; ?>
                        
                        <div class="text-center">
                            <button class="btn btn-soft-primary btn-sm rounded-pill px-4 fw-bold mt-2">View Full Log</button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <style>
    .fw-800 { font-weight: 800; }
    .fw-600 { font-weight: 600; }
    .letter-spacing-1 { letter-spacing: 0.05rem; }
    .smallest { font-size: 0.72rem; }
    .bg-light-hint { background: rgba(0,0,0,0.02); }
    .btn-white { background: #fff; color: #4e73df; border: none; }
    .btn-white:hover { background: #f8f9fc; color: #224abe; }
    .btn-outline-white { background: transparent; color: #fff; border: 2px solid rgba(255,255,255,0.4); }
    .btn-outline-white:hover { background: rgba(255,255,255,0.1); border-color: #fff; }

    .card-premium-stat {
        transition: all 0.3s cubic-bezier(0.165, 0.84, 0.44, 1);
        border: none !important;
    }
    .card-premium-stat:hover { transform: translateY(-8px); box-shadow: 0 20px 40px rgba(0,0,0,0.08) !important; }

    .stat-glow {
        position: absolute;
        width: 151px;
        height: 151px;
        right: -50px;
        bottom: -50px;
        opacity: 0.05;
        border-radius: 50%;
        filter: blur(40px);
    }

    .stat-icon-premium {
        width: 48px;
        height: 48px;
        border-radius: 12px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.25rem;
    }

    .premium-timeline .timeline-item:not(:last-child)::after {
        content: '';
        position: absolute;
        left: 15px;
        top: 32px;
        height: calc(100% - 32px);
        width: 2px;
        background: rgba(0,0,0,0.05);
        z-index: 1;
    }

    [data-bs-theme="dark"] .card-premium-main,
    [data-bs-theme="dark"] .card-premium-stat {
        background: #1a1c23 !important;
    }
    [data-bs-theme="dark"] .text-dark { color: #f8fafc !important; }
    </style>

    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        const ctx = document.getElementById('trafficChart').getContext('2d');
        
        <?php
        // Fetch last 7 days of activity for the chart
        $labels = [];
        $orderCounts = [];
        $borrowCounts = [];
        for ($i = 6; $i >= 0; $i--) {
            $date = date('Y-m-d', strtotime("-$i days"));
            $labels[] = date('D', strtotime($date));
            
            // Orders count
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM orders WHERE DATE(created_at) = ?");
            $stmt->execute([$date]);
            $orderCounts[] = (int)$stmt->fetchColumn();
            
            // Borrows count
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM borrowing_history WHERE DATE(borrowed_at) = ?");
            $stmt->execute([$date]);
            $borrowCounts[] = (int)$stmt->fetchColumn();
        }
        ?>

        const labels = <?= json_encode($labels) ?>;
        const orderData = <?= json_encode($orderCounts) ?>;
        const borrowData = <?= json_encode($borrowCounts) ?>;

        const gradientOrders = ctx.createLinearGradient(0, 0, 0, 400);
        gradientOrders.addColorStop(0, 'rgba(78, 115, 223, 0.2)');
        gradientOrders.addColorStop(1, 'rgba(78, 115, 223, 0)');

        const gradientBorrows = ctx.createLinearGradient(0, 0, 0, 400);
        gradientBorrows.addColorStop(0, 'rgba(28, 200, 138, 0.1)');
        gradientBorrows.addColorStop(1, 'rgba(28, 200, 138, 0)');

        new Chart(ctx, {
            type: 'line',
            data: {
                labels: labels,
                datasets: [
                    {
                        label: 'Direct Sales',
                        data: orderData,
                        borderColor: '#4e73df',
                        backgroundColor: gradientOrders,
                        borderWidth: 3,
                        fill: true,
                        tension: 0.4,
                        pointRadius: 4,
                        pointHoverRadius: 6
                    },
                    {
                        label: 'Borrowings',
                        data: borrowData,
                        borderColor: '#1cc88a',
                        backgroundColor: gradientBorrows,
                        borderWidth: 3,
                        fill: true,
                        tension: 0.4,
                        pointRadius: 4,
                        pointHoverRadius: 6
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { position: 'top', align: 'end' }
                },
                scales: {
                    x: { grid: { display: false } },
                    y: { 
                        beginAtZero: true,
                        ticks: { stepSize: 1 },
                        grid: { borderDash: [5, 5], color: 'rgba(0,0,0,0.05)' }
                    }
                }
            }
        });
    });
    </script>
    <?php
});
