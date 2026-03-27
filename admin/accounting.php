<?php
// admin/accounting.php — Library Accounting & Financial Dashboard
require_once dirname(__DIR__) . '/vendor/autoload.php';
require_once dirname(__DIR__) . '/includes/sessions.php';
require_once dirname(__DIR__) . '/includes/env_loader.php';
require_once dirname(__DIR__) . '/includes/functions.php';
require_once dirname(__DIR__) . '/views/admin/layout.php';

use App\Library;
$library = new Library();

// Check if user is admin
requireAdmin();

// Pagination & Filtering
$page = (int)($_GET['page'] ?? 1);
$limit = 5;
$offset = ($page - 1) * $limit;

$filters = [
    'search' => $_GET['search'] ?? '',
    'category' => $_GET['category'] ?? '',
    'type' => $_GET['type'] ?? '',
    'sort' => $_GET['sort'] ?? 'newest',
    'start_date' => $_GET['start_date'] ?? '',
    'end_date' => $_GET['end_date'] ?? ''
];

$stats = $library->getAccountingStats();
$transactions = $library->getTransactions($filters, $limit, $offset);
$totalRecords = $library->countTransactions($filters);
$totalPages = ceil($totalRecords / $limit);

renderAdminLayout('Accounting Dashboard', function () use ($stats, $transactions, $filters, $page, $totalPages, $totalRecords, $limit, $offset) {
?>

<style>
    :root {
        --acc-primary: #6366f1;
        --acc-success: #10b981;
        --acc-danger: #ef4444;
        --acc-warning: #f59e0b;
        --acc-bg: #f8fafc;
        --acc-card-bg: #ffffff;
    }

    .acc-container { padding-bottom: 50px; }

    /* Stat Cards */
    .stat-card {
        border: none; border-radius: 20px; padding: 25px;
        background: var(--acc-card-bg); box-shadow: 0 4px 20px rgba(0,0,0,0.03);
        position: relative; overflow: hidden; height: 100%; transition: transform 0.3s;
    }
    .stat-card:hover { transform: translateY(-5px); }
    .stat-card::before {
        content: ''; position: absolute; top: -50%; right: -20%; width: 150px; height: 150px;
        background: currentColor; opacity: 0.05; border-radius: 50%;
    }
    .stat-icon {
        width: 50px; height: 50px; border-radius: 15px; display: flex;
        align-items: center; justify-content: center; font-size: 20px; margin-bottom: 20px;
    }
    .stat-value { font-size: 28px; font-weight: 800; color: #1e293b; margin-bottom: 5px; }
    .stat-label { font-size: 13px; font-weight: 700; color: #64748b; text-transform: uppercase; letter-spacing: 0.5px; }

    /* Glassmorphism for charts/tables */
    .glass-card {
        background: rgba(255, 255, 255, 0.7);
        backdrop-filter: blur(10px);
        -webkit-backdrop-filter: blur(10px);
        border: 1px solid rgba(255, 255, 255, 0.3);
        border-radius: 24px; padding: 30px; box-shadow: 0 10px 30px rgba(0,0,0,0.02);
    }

    /* Transactions Table */
    .tr-table { width: 100%; border-collapse: separate; border-spacing: 0; }
    .tr-table th {
        background: transparent; color: #64748b; font-weight: 800; font-size: 11px;
        text-transform: uppercase; letter-spacing: 1px; padding: 15px 20px;
        border-bottom: 2px solid #f1f5f9;
    }
    .tr-table td { padding: 18px 20px; border-bottom: 1px solid #f1f5f9; vertical-align: middle; }
    .tr-row:hover { background: rgba(99, 102, 241, 0.02); }

    .category-badge {
        padding: 5px 12px; border-radius: 8px; font-size: 11px; font-weight: 700;
        text-transform: uppercase; letter-spacing: 0.3px;
    }
    .cat-sale { background: #dcfce7; color: #15803d; }
    .cat-membership { background: #e0e7ff; color: #4338ca; }
    .cat-borrow { background: #fef9c3; color: #854d0e; }
    .cat-penalty { background: #ffedd5; color: #9a3412; }
    .cat-expense { background: #fee2e2; color: #b91c1c; }

    .amount-income { color: var(--acc-success); font-weight: 800; }
    .amount-expense { color: var(--acc-danger); font-weight: 800; }

    /* Navigation */
    .acc-nav { margin-bottom: 30px; display: flex; gap: 10px; }
    .btn-acc {
        padding: 10px 20px; border-radius: 12px; font-weight: 700; font-size: 14px;
        border: none; transition: 0.2s; background: #fff; color: #64748b;
        box-shadow: 0 2px 10px rgba(0,0,0,0.03);
    }
    .btn-acc:hover { background: #f1f5f9; color: var(--acc-primary); }
    .btn-acc.active { background: var(--acc-primary); color: #fff; }

    #accountingChart { max-height: 400px; }
</style>

<div class="acc-container">
    <!-- Header -->
    <div class="d-flex justify-content-between align-items-center mb-4 pt-2">
        <div>
            <h3 class="fw-900 mb-1" style="color: #0f172a;">Financial Intelligence</h3>
            <p class="text-muted small mb-0">Overview of your library's revenue and spending.</p>
        </div>
        <div class="acc-nav">
            <a href="accounting.php" class="btn-acc active"><i class="fas fa-chart-line me-2"></i>Dashboard</a>
            <a href="expenses.php" class="btn-acc"><i class="fas fa-receipt me-2"></i>Expenses</a>
        </div>
    </div>

    <!-- Stats Row -->
    <div class="row g-4 mb-5">
        <div class="col-md-4">
            <div class="stat-card" style="color: var(--acc-success);">
                <div class="stat-icon" style="background:#ecfdf5; color:var(--acc-success);"><i class="fas fa-arrow-up"></i></div>
                <div class="stat-value"><?= number_format($stats['total_income']) ?> Ks</div>
                <div class="stat-label">Total Revenue</div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="stat-card" style="color: var(--acc-danger);">
                <div class="stat-icon" style="background:#fef2f2; color:var(--acc-danger);"><i class="fas fa-arrow-down"></i></div>
                <div class="stat-value"><?= number_format($stats['total_expense']) ?> Ks</div>
                <div class="stat-label">Total Expenses</div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="stat-card" style="color: var(--acc-primary);">
                <div class="stat-icon" style="background:#eef2ff; color:var(--acc-primary);"><i class="fas fa-wallet"></i></div>
                <div class="stat-value"><?= number_format($stats['total_profit']) ?> Ks</div>
                <div class="stat-label">Net Profit</div>
            </div>
        </div>
    </div>

    <!-- Detailed Insights Section -->
    <div class="row g-4 mb-5">
        <!-- Sales Stats -->
        <div class="col-lg-4">
            <div class="glass-card h-100">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h5 class="fw-800 mb-0">Sales Stats</h5>
                    <i class="fas fa-shopping-cart text-muted"></i>
                </div>
                <div class="mb-4">
                    <div class="smallest fw-700 text-muted text-uppercase mb-1">Average Order Value</div>
                    <div class="fw-900 text-dark h4 mb-0"><?= number_format($stats['sales_detailed']['avg_order_value']) ?> Ks</div>
                </div>
                <div class="mb-2 smallest fw-800 text-uppercase text-muted">Top Selling Books</div>
                <?php foreach ($stats['sales_detailed']['top_selling_books'] as $book): ?>
                    <div class="d-flex justify-content-between align-items-center mb-2 py-2 border-bottom border-light">
                        <span class="smaller fw-700 text-dark text-truncate me-2"><?= e($book['title']) ?></span>
                        <span class="smallest fw-800 text-success"><?= number_format($book['total']) ?> Ks</span>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- Borrow Fees Stats -->
        <div class="col-lg-4">
            <div class="glass-card h-100">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h5 class="fw-800 mb-0">Borrow Fees</h5>
                    <i class="fas fa-book-reader text-muted"></i>
                </div>
                <div class="row g-2 mb-4">
                    <div class="col-6">
                        <div class="p-3 rounded-4 bg-light">
                            <div class="smallest fw-700 text-muted text-uppercase mb-1">Base Fees</div>
                            <div class="fw-900 text-dark small"><?= number_format($stats['borrow_detailed']['fee_distribution']['borrow_fee'] ?? 0) ?> Ks</div>
                        </div>
                    </div>
                    <div class="col-6">
                        <div class="p-3 rounded-4 bg-light">
                            <div class="smallest fw-700 text-muted text-uppercase mb-1">Penalties</div>
                            <div class="fw-900 text-danger small"><?= number_format($stats['borrow_detailed']['fee_distribution']['penalty_fee'] ?? 0) ?> Ks</div>
                        </div>
                    </div>
                </div>
                <div class="mb-2 smallest fw-800 text-uppercase text-muted">Borrowing Trend</div>
                <div style="height: 120px;">
                    <canvas id="borrowTrendChart"></canvas>
                </div>
            </div>
        </div>

        <!-- Membership Stats -->
        <div class="col-lg-4">
            <div class="glass-card h-100">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h5 class="fw-800 mb-0">Member Fees</h5>
                    <i class="fas fa-gem text-muted"></i>
                </div>
                <div class="mb-4">
                    <?php 
                    $tierColors = ['silver' => '#94a3b8', 'gold' => '#f59e0b', 'platinum' => '#6366f1'];
                    foreach ($stats['membership_detailed']['tier_distribution'] as $tier): 
                        $pct = ($tier['revenue'] / (array_sum(array_column($stats['membership_detailed']['tier_distribution'], 'revenue')) ?: 1)) * 100;
                    ?>
                    <div class="mb-3">
                        <div class="d-flex justify-content-between align-items-center mb-1">
                            <span class="smallest fw-800 text-uppercase" style="color: <?= $tierColors[$tier['tier']] ?? '#333' ?>;"><?= e($tier['tier']) ?> (<?= $tier['count'] ?>)</span>
                            <span class="smallest fw-900 text-dark"><?= number_format($tier['revenue']) ?> Ks</span>
                        </div>
                        <div class="progress" style="height: 6px; border-radius: 99px; background: #f1f5f9;">
                            <div class="progress-bar" style="width: <?= $pct ?>%; background: <?= $tierColors[$tier['tier']] ?? '#ccc' ?>;"></div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
                <div class="p-3 rounded-4 bg-indigo-soft text-indigo border border-indigo-subtle">
                    <div class="d-flex align-items-center gap-2">
                        <i class="fas fa-info-circle small"></i>
                        <span class="smallest fw-700">Membership revenue has grown by 12% this month.</span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Income Categories & Chart -->
    <div class="row g-4 mb-5">
        <!-- Monthly Chart -->
        <div class="col-lg-8">
            <div class="glass-card h-100">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h5 class="fw-800 mb-0">Revenue Flow</h5>
                    <div class="small text-muted fw-700">Last 12 Months</div>
                </div>
                <canvas id="accountingChart"></canvas>
            </div>
        </div>
        <!-- Income Categories -->
        <div class="col-lg-4">
            <div class="glass-card h-100">
                <h5 class="fw-800 mb-4">Income Streams</h5>
                <?php 
                $incomeCats = [
                    'sale' => ['label' => 'Sales', 'icon' => 'shopping-cart', 'color' => '#10b981'],
                    'membership_fee' => ['label' => 'Memberships', 'icon' => 'crown', 'color' => '#6366f1'],
                    'borrow_fee' => ['label' => 'Borrows', 'icon' => 'book', 'color' => '#f59e0b'],
                    'penalty_fee' => ['label' => 'Penalties', 'icon' => 'exclamation-triangle', 'color' => '#ef4444'],
                ];
                foreach ($incomeCats as $key => $info):
                    $val = $stats['income_by_category'][$key] ?? 0;
                    $percent = $stats['total_income'] > 0 ? ($val / $stats['total_income']) * 100 : 0;
                ?>
                <div class="mb-4">
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <div class="d-flex align-items-center gap-2">
                            <i class="fas fa-<?= $info['icon'] ?>" style="color: <?= $info['color'] ?>; font-size: 14px;"></i>
                            <span class="smallest fw-800 text-uppercase" style="color: #64748b;"><?= $info['label'] ?></span>
                        </div>
                        <span class="smallest fw-900 text-dark"><?= number_format($val) ?> Ks</span>
                    </div>
                    <div class="progress" style="height: 6px; border-radius: 99px;">
                        <div class="progress-bar" style="width: <?= $percent ?>%; background: <?= $info['color'] ?>;"></div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>

    <!-- Transaction History -->
    <div class="glass-card">
        <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-3">
            <h5 class="fw-800 mb-0">Record of Transactions (<?= number_format($totalRecords) ?>)</h5>
            <div class="d-flex gap-2">
                <button class="btn btn-light btn-sm rounded-pill px-3 fw-bold small border" onclick="window.location.search = ''"><i class="fas fa-undo me-2"></i>Reset</button>
                <button class="btn btn-light btn-sm rounded-pill px-3 fw-bold small border"><i class="fas fa-download me-2"></i>Export CSV</button>
            </div>
        </div>

        <!-- Filter Bar -->
        <form id="filterForm" class="row g-2 mb-4">
            <div class="col-md-3">
                <div class="input-group input-group-sm">
                    <span class="input-group-text bg-white border-end-0 rounded-start-pill ps-3"><i class="fas fa-search text-muted"></i></span>
                    <input type="text" name="search" class="form-control border-start-0 rounded-end-pill py-2" placeholder="Search transactions..." value="<?= e($filters['search']) ?>">
                </div>
            </div>
            <div class="col-md-2">
                <select name="category" class="form-select form-select-sm rounded-pill py-2" onchange="this.form.submit()">
                    <option value="">All Categories</option>
                    <option value="sale" <?= $filters['category'] === 'sale' ? 'selected' : '' ?>>Sales</option>
                    <option value="membership_fee" <?= $filters['category'] === 'membership_fee' ? 'selected' : '' ?>>Memberships</option>
                    <option value="borrow_fee" <?= $filters['category'] === 'borrow_fee' ? 'selected' : '' ?>>Borrows</option>
                    <option value="penalty_fee" <?= $filters['category'] === 'penalty_fee' ? 'selected' : '' ?>>Penalties</option>
                    <option value="expense" <?= $filters['category'] === 'expense' ? 'selected' : '' ?>>Expenses</option>
                </select>
            </div>
            <div class="col-md-2">
                <select name="sort" class="form-select form-select-sm rounded-pill py-2" onchange="this.form.submit()">
                    <option value="newest" <?= $filters['sort'] === 'newest' ? 'selected' : '' ?>>Newest First</option>
                    <option value="oldest" <?= $filters['sort'] === 'oldest' ? 'selected' : '' ?>>Oldest First</option>
                    <option value="amount_high" <?= $filters['sort'] === 'amount_high' ? 'selected' : '' ?>>Highest Amount</option>
                    <option value="amount_low" <?= $filters['sort'] === 'amount_low' ? 'selected' : '' ?>>Lowest Amount</option>
                </select>
            </div>
            <div class="col-md-4">
                <div class="d-flex align-items-center gap-2">
                    <input type="date" name="start_date" class="form-control form-control-sm rounded-pill py-2" value="<?= e($filters['start_date']) ?>">
                    <span class="text-muted smallest fw-bold">-</span>
                    <input type="date" name="end_date" class="form-control form-control-sm rounded-pill py-2" value="<?= e($filters['end_date']) ?>">
                    <button type="submit" class="btn btn-primary btn-sm rounded-pill px-3"><i class="fas fa-filter"></i></button>
                </div>
            </div>
        </form>

        <div class="table-responsive">
            <table class="tr-table">
                <thead>
                    <tr>
                        <th>Transaction</th>
                        <th>Category</th>
                        <th>User</th>
                        <th>Amount</th>
                        <th>Date</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($transactions)): ?>
                        <tr><td colspan="5" class="text-center py-5 text-muted">No transactions recorded yet.</td></tr>
                    <?php endif; ?>
                    <?php foreach ($transactions as $t): 
                        $catClass = str_replace('_fee', '', $t['category']);
                        $catClass = str_replace('membership', 'membership', $catClass);
                    ?>
                    <tr class="tr-row">
                        <td>
                            <div class="fw-800 text-dark small"><?= e($t['description']) ?></div>
                            <div class="text-muted smallest">Ref: #<?= e($t['reference_id']) ?> (<?= e($t['reference_table']) ?>)</div>
                        </td>
                        <td>
                            <span class="category-badge cat-<?= $catClass ?>">
                                <?= str_replace('_', ' ', $t['category']) ?>
                            </span>
                        </td>
                        <td>
                            <?php if ($t['username']): ?>
                                <div class="d-flex align-items-center gap-2">
                                    <div class="rounded-circle bg-light d-flex align-items-center justify-content-center fw-800 text-primary smaller" style="width:24px; height:24px; font-size:10px;">
                                        <?= strtoupper(substr($t['username'], 0, 1)) ?>
                                    </div>
                                    <span class="smaller fw-700 text-dark"><?= e($t['username']) ?></span>
                                </div>
                            <?php else: ?>
                                <span class="text-muted smallest fw-700">Internal</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <span class="amount-<?= $t['type'] ?>">
                                <?= $t['type'] === 'income' ? '+' : '-' ?><?= number_format($t['amount']) ?> Ks
                            </span>
                        </td>
                        <td>
                            <div class="smaller text-dark fw-700"><?= date('M j, Y', strtotime($t['created_at'])) ?></div>
                            <div class="text-muted smallest"><?= date('h:i A', strtotime($t['created_at'])) ?></div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <!-- Pagination -->
        <div class="d-flex justify-content-between align-items-center mt-4">
            <div class="smallest fw-700 text-muted">
                Showing <?= $totalRecords > 0 ? $offset + 1 : 0 ?> to <?= min($offset + $limit, $totalRecords) ?> of <?= $totalRecords ?> entries
            </div>
            <nav>
                <ul class="pagination pagination-sm mb-0">
                    <?php 
                    $queryParams = $_GET;
                    $buildUrl = function($p) use ($queryParams) {
                        $queryParams['page'] = $p;
                        return '?' . http_build_query($queryParams);
                    };
                    ?>
                    <li class="page-item <?= $page <= 1 ? 'disabled' : '' ?>">
                        <a class="page-link border shadow-sm rounded-pill px-3 d-flex align-items-center justify-content-center me-2" href="<?= $page > 1 ? $buildUrl($page - 1) : '#' ?>" style="height:32px; color:#64748b;"><i class="fas fa-chevron-left me-2 smallest"></i>Previous</a>
                    </li>
                    <?php 
                    $dispPages = $totalPages ?: 1;
                    for ($i = 1; $i <= $dispPages; $i++): 
                         if ($totalPages > 8 && !($i === 1 || $i === $totalPages || ($i >= $page - 1 && $i <= $page + 1))) {
                             if ($i === 2 || $i === $totalPages - 1) echo '<li class="page-item disabled"><span class="page-link border-0 bg-transparent">...</span></li>';
                             continue;
                         }
                    ?>
                        <li class="page-item <?= $i === $page ? 'active' : '' ?>">
                            <a class="page-link border shadow-sm rounded-circle d-flex align-items-center justify-content-center me-2 <?= $i === $page ? 'bg-primary border-primary' : '' ?>" href="<?= $buildUrl($i) ?>" style="width:32px; height:32px; <?= $i === $page ? 'color:#fff;' : 'color:#64748b;' ?>"><?= $i ?></a>
                        </li>
                    <?php endfor; ?>
                    <li class="page-item <?= $page >= $totalPages ? 'disabled' : '' ?>">
                        <a class="page-link border shadow-sm rounded-pill px-3 d-flex align-items-center justify-content-center" href="<?= $page < $totalPages ? $buildUrl($page + 1) : '#' ?>" style="height:32px; color:#64748b;">Next<i class="fas fa-chevron-right ms-2 smallest"></i></a>
                    </li>
                </ul>
            </nav>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    const ctx = document.getElementById('accountingChart').getContext('2d');
    
    const incomeData = <?= json_encode($stats['income_by_category']) ?>;
    const incomeLabels = Object.keys(incomeData).map(k => k.replace('_', ' ').toUpperCase());
    const incomeValues = Object.values(incomeData).map(Number); // Ensure numeric values

    new Chart(ctx, {
        type: 'doughnut',
        data: {
            labels: incomeLabels,
            datasets: [{
                data: incomeValues,
                backgroundColor: ['#10b981', '#6366f1', '#f59e0b', '#ef4444', '#f43f5e'],
                borderWidth: 0,
                hoverOffset: 15
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            cutout: '70%',
            plugins: {
                legend: {
                    position: 'bottom',
                    labels: {
                        usePointStyle: true,
                        padding: 20,
                        font: { weight: '700', size: 12 }
                    }
                },
                tooltip: {
                    padding: 12,
                    backgroundColor: '#1e293b',
                    cornerRadius: 12,
                    callbacks: {
                        label: function(context) {
                            let label = context.label || '';
                            let value = context.parsed || 0;
                            let total = context.dataset.data.reduce((a, b) => Number(a) + Number(b), 0);
                            let pct = total > 0 ? ((value / total) * 100).toFixed(1) : 0;
                            return ` ${label}: ${value.toLocaleString()} Ks (${pct}%)`;
                        }
                    }
                }
            }
        }
    });

    // Borrow Trend Chart
    const borrowCtx = document.getElementById('borrowTrendChart').getContext('2d');
    const borrowTrend = <?= json_encode($stats['borrow_detailed']['borrow_count_trend']) ?>;
    new Chart(borrowCtx, {
        type: 'bar',
        data: {
            labels: borrowTrend.map(d => d.month),
            datasets: [{
                label: 'Borrows',
                data: borrowTrend.map(d => d.count),
                backgroundColor: '#6366f1',
                borderRadius: 4
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: { legend: { display: false } },
            scales: {
                y: { display: false },
                x: { grid: { display: false }, ticks: { font: { size: 9 } } }
            }
        }
    });
});
</script>

<?php }); ?>
