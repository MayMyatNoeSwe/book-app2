<?php
// admin/expenses.php — Expense Tracking & Management
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
$limit = 10;
$offset = ($page - 1) * $limit;

$filters = [
    'search' => $_GET['search'] ?? '',
    'category' => $_GET['category'] ?? '',
    'sort' => $_GET['sort'] ?? 'newest',
    'start_date' => $_GET['start_date'] ?? '',
    'end_date' => $_GET['end_date'] ?? ''
];

// Handle Actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'add') {
        $title = trim($_POST['title']);
        $amount = (float)$_POST['amount'];
        $category = $_POST['category'];
        $description = trim($_POST['description']);
        $date = $_POST['date'];
        
        if ($library->addExpense($title, $amount, $category, $description, $date)) {
            setFlashMessage('Expense recorded successfully.', 'success');
        } else {
            setFlashMessage('Failed to record expense.', 'danger');
        }
    } elseif ($action === 'delete') {
        $id = (int)$_POST['id'];
        if ($library->deleteExpense($id)) {
            setFlashMessage('Expense deleted.', 'warning');
        } else {
            setFlashMessage('Failed to delete expense.', 'danger');
        }
    }
    
    header('Location: expenses.php');
    exit;
}

$expenses = $library->getExpenses($filters, $limit, $offset);
$totalRecords = $library->countExpenses($filters);
$totalPages = ceil($totalRecords / $limit);

renderAdminLayout('Expense Management', function () use ($expenses, $filters, $page, $totalPages, $totalRecords, $limit, $offset) {
?>

<style>
    .glass-card {
        background: rgba(255, 255, 255, 0.7);
        backdrop-filter: blur(10px);
        -webkit-backdrop-filter: blur(10px);
        border: 1px solid rgba(255, 255, 255, 0.3);
        border-radius: 24px; padding: 25px; box-shadow: 0 10px 30px rgba(0,0,0,0.02);
    }
    .expense-table { width: 100%; border-collapse: separate; border-spacing: 0; }
    .expense-table th {
        background: transparent; color: #94a3b8; font-weight: 800; font-size: 11px;
        text-transform: uppercase; letter-spacing: 1px; padding: 15px 20px;
        border-bottom: 2px solid #f1f5f9;
    }
    .expense-table td { padding: 18px 20px; border-bottom: 1px solid #f1f5f9; vertical-align: middle; }
    
    .btn-add {
        background: #6366f1; color: #fff; border: none; padding: 10px 20px;
        border-radius: 12px; font-weight: 800; font-size: 13px; transition: 0.2s;
        box-shadow: 0 4px 12px rgba(99, 102, 241, 0.3);
    }
    .btn-add:hover { transform: translateY(-2px); filter: brightness(1.1); }
    
    .exp-category {
        padding: 4px 10px; border-radius: 6px; font-size: 11px; font-weight: 800;
        text-transform: uppercase; letter-spacing: 0.5px;
    }
    .exp-cat-salary { background: #e0e7ff; color: #4338ca; }
    .exp-cat-utilities { background: #ffedd5; color: #9a3412; }
    .exp-cat-book { background: #dcfce7; color: #15803d; }
    .exp-cat-maintenance { background: #fee2e2; color: #b91c1c; }
    .exp-cat-other { background: #f1f5f9; color: #64748b; }

    .acc-nav { margin-bottom: 30px; display: flex; gap: 10px; }
    .btn-acc {
        padding: 10px 20px; border-radius: 12px; font-weight: 700; font-size: 14px;
        border: none; transition: 0.2s; background: #fff; color: #64748b;
        box-shadow: 0 2px 10px rgba(0,0,0,0.03);
    }
    .btn-acc:hover { background: #f1f5f9; color: #6366f1; }
    .btn-acc.active { background: #6366f1; color: #fff; }

    .input-glass {
        border: 1.5px solid #eef2f7; border-radius: 12px; padding: 10px 15px;
        font-size: 14px; transition: 0.2s; background: #fafbfc;
    }
    .input-glass:focus { border-color: #6366f1; box-shadow: 0 0 0 4px rgba(99, 102, 241, 0.1); outline: none; }
</style>

<div class="container-fluid">
    <!-- Header -->
    <div class="d-flex justify-content-between align-items-center mb-4 pt-2">
        <div>
            <h3 class="fw-900 mb-1" style="color: #0f172a;">Spending & Expenses</h3>
            <p class="text-muted small mb-0">Record all operational costs to keep profit margins accurate.</p>
        </div>
        <div class="acc-nav">
            <a href="accounting.php" class="btn-acc"><i class="fas fa-chart-line me-2"></i>Dashboard</a>
            <a href="expenses.php" class="btn-acc active"><i class="fas fa-receipt me-2"></i>Expenses</a>
        </div>
    </div>

    <div class="row g-4">
        <!-- Add Expense Form -->
        <div class="col-lg-4">
            <div class="glass-card">
                <h5 class="fw-800 mb-4">Record New Expense</h5>
                <form method="POST">
                    <input type="hidden" name="action" value="add">
                    <div class="mb-3">
                        <label class="smallest fw-800 text-uppercase text-muted mb-2 d-block">Title / Item</label>
                        <input type="text" name="title" class="form-control input-glass" placeholder="Office Rent, Book Purchase..." required>
                    </div>
                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <label class="smallest fw-800 text-uppercase text-muted mb-2 d-block">Amount (Ks)</label>
                            <input type="number" name="amount" class="form-control input-glass" placeholder="0" required>
                        </div>
                        <div class="col-md-6">
                            <label class="smallest fw-800 text-uppercase text-muted mb-2 d-block">Category</label>
                            <select name="category" class="form-select input-glass" required>
                                <option value="Salary">Staff Salary</option>
                                <option value="Utilities">Utilities (Electricity, Net)</option>
                                <option value="Book Purchase">Book Inventory</option>
                                <option value="Maintenance">Maintenance</option>
                                <option value="Marketing">Marketing/Ads</option>
                                <option value="Other">Other</option>
                            </select>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="smallest fw-800 text-uppercase text-muted mb-2 d-block">Expense Date</label>
                        <input type="date" name="date" class="form-control input-glass" value="<?= date('Y-m-d') ?>" required>
                    </div>
                    <div class="mb-4">
                        <label class="smallest fw-800 text-uppercase text-muted mb-2 d-block">Notes (Optional)</label>
                        <textarea name="description" class="form-control input-glass" rows="2" placeholder="More details..."></textarea>
                    </div>
                    <button type="submit" class="btn btn-add w-100"><i class="fas fa-plus me-2"></i>Record Transaction</button>
                </form>
            </div>
        </div>

        <!-- Expense List -->
        <div class="col-lg-8">
            <div class="glass-card h-100">
                <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
                    <h5 class="fw-800 mb-0">Spending History (<?= number_format($totalRecords) ?>)</h5>
                    <div class="d-flex gap-2">
                        <button class="btn btn-light btn-sm rounded-pill px-3 fw-bold small border" onclick="window.location.search = ''"><i class="fas fa-undo me-2"></i>Reset</button>
                    </div>
                </div>

                <!-- Filter Bar -->
                <form id="expFilterForm" class="row g-2 mb-4">
                    <div class="col-md-5">
                        <div class="input-group input-group-sm">
                            <span class="input-group-text bg-white border-end-0 rounded-start-pill ps-3"><i class="fas fa-search text-muted"></i></span>
                            <input type="text" name="search" class="form-control border-start-0 py-2" placeholder="Search item or note..." value="<?= e($filters['search']) ?>">
                            <select name="category" class="form-select border-start-0 rounded-end-pill py-2" onchange="this.form.submit()">
                                <option value="">All Categories</option>
                                <option value="Salary" <?= $filters['category'] === 'Salary' ? 'selected' : '' ?>>Salary</option>
                                <option value="Utilities" <?= $filters['category'] === 'Utilities' ? 'selected' : '' ?>>Utilities</option>
                                <option value="Book Purchase" <?= $filters['category'] === 'Book Purchase' ? 'selected' : '' ?>>Books</option>
                                <option value="Maintenance" <?= $filters['category'] === 'Maintenance' ? 'selected' : '' ?>>Maintenance</option>
                                <option value="Marketing" <?= $filters['category'] === 'Marketing' ? 'selected' : '' ?>>Marketing</option>
                                <option value="Other" <?= $filters['category'] === 'Other' ? 'selected' : '' ?>>Other</option>
                            </select>
                        </div>
                    </div>
                    <div class="col-md-7">
                        <div class="d-flex align-items-center gap-2">
                            <input type="date" name="start_date" class="form-control form-control-sm rounded-pill py-2" value="<?= e($filters['start_date']) ?>">
                            <span class="text-muted smallest fw-bold">-</span>
                            <input type="date" name="end_date" class="form-control form-control-sm rounded-pill py-2" value="<?= e($filters['end_date']) ?>">
                            <button type="submit" class="btn btn-primary btn-sm rounded-pill px-3"><i class="fas fa-filter"></i></button>
                        </div>
                    </div>
                </form>

                <div class="table-responsive">
                    <table class="expense-table">
                        <thead>
                            <tr>
                                <th>Item / Note</th>
                                <th>Category</th>
                                <th>Amount</th>
                                <th>Date</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($expenses)): ?>
                                <tr><td colspan="5" class="text-center py-5 text-muted">No expenses matching your filters.</td></tr>
                            <?php endif; ?>
                            <?php foreach ($expenses as $e): 
                                $catClass = strtolower(str_replace(' ', '-', $e['category']));
                            ?>
                            <tr>
                                <td>
                                    <div class="fw-800 text-dark small"><?= e($e['title']) ?></div>
                                    <div class="text-muted smallest"><?= e($e['description']) ?></div>
                                </td>
                                <td>
                                    <span class="exp-category exp-cat-<?= $catClass ?>">
                                        <?= e($e['category']) ?>
                                    </span>
                                </td>
                                <td>
                                    <span class="fw-800 text-danger small"><?= number_format($e['amount']) ?> Ks</span>
                                </td>
                                <td>
                                    <div class="smaller text-dark fw-700"><?= date('M j, Y', strtotime($e['date'])) ?></div>
                                </td>
                                <td>
                                    <button class="btn btn-light btn-sm rounded-circle border p-0 shadow-sm" style="width:28px; height:28px;" onclick="deleteExp(<?= $e['id'] ?>)">
                                        <i class="fas fa-trash smallest text-danger"></i>
                                    </button>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

                <!-- Pagination -->
                <div class="d-flex justify-content-between align-items-center mt-4 flex-wrap gap-2">
                    <div class="smallest fw-700 text-muted">
                        Showing <?= $totalRecords > 0 ? $offset + 1 : 0 ?> to <?= min($offset + $limit, $totalRecords) ?> of <?= $totalRecords ?> entries
                    </div>
                    <?php if ($totalPages > 1): ?>
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
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<form method="POST" id="deleteForm" style="display:none;">
    <input type="hidden" name="action" value="delete">
    <input type="hidden" name="id" id="deleteId">
</form>

<script>
function deleteExp(id) {
    Swal.fire({
        title: 'Delete Expense?',
        text: 'This will also remove it from transaction history.',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#ef4444',
        cancelButtonColor: '#94a3b8',
        confirmButtonText: 'Yes, Delete',
        reverseButtons: true
    }).then((result) => {
        if (result.isConfirmed) {
            document.getElementById('deleteId').value = id;
            document.getElementById('deleteForm').submit();
        }
    });
}
</script>

<?php }); ?>
