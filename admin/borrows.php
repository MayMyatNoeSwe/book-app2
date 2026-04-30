<?php
// admin/borrows.php — Borrow Management System
require_once dirname(__DIR__) . '/vendor/autoload.php';
require_once dirname(__DIR__) . '/includes/sessions.php';
require_once dirname(__DIR__) . '/includes/env_loader.php';
require_once dirname(__DIR__) . '/includes/functions.php';
require_once dirname(__DIR__) . '/views/admin/layout.php';

use App\Library;
$library = new Library();
$pdo = $library->getPdo();

// Handle POST actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $borrowId = (int)($_POST['borrow_id'] ?? 0);

    switch ($action) {
        case 'approve_borrow':
            if ($library->approveBorrow($borrowId)) {
                setFlashMessage('Borrow request approved successfully!', 'success');
            } else {
                setFlashMessage('Failed to approve borrow request.', 'danger');
            }
            break;

        case 'reject_borrow':
            $reason = trim($_POST['reason'] ?? '');
            if ($library->rejectBorrow($borrowId, $reason)) {
                setFlashMessage('Borrow request rejected.', 'warning');
            } else {
                setFlashMessage('Failed to reject borrow request.', 'danger');
            }
            break;

        case 'approve_return':
            if ($library->approveReturn($borrowId)) {
                setFlashMessage('Return approved. Book is back in stock!', 'success');
            } else {
                setFlashMessage('Failed to approve return.', 'danger');
            }
            break;

        case 'mark_paid':
            if ($library->markPenaltyPaid($borrowId)) {
                setFlashMessage('Penalty marked as paid.', 'success');
            } else {
                setFlashMessage('Failed to update payment status.', 'danger');
            }
            break;
    }

    header('Location: borrows.php?tab=' . ($_POST['current_tab'] ?? 'pending'));
    exit;
}

// Get filter
$currentTab = $_GET['tab'] ?? 'pending';

// Get counts for tabs
$counts = [
    'pending' => $library->countBorrowRequests('pending'),
    'approved' => $library->countBorrowRequests('approved'),
    'return_pending' => $library->countBorrowRequests('return_pending'),
    'returned' => $library->countBorrowRequests('returned'),
    'rejected' => $library->countBorrowRequests('rejected'),
    'all' => $library->countBorrowRequests('all'),
];

// Get records
$records = $library->getBorrowRequests($currentTab, 100, 0);

    // Fetch current active "Normal" usage for all users in the record set
    // This counts books they ALREADY have at home (approved or return_pending)
    $userIds = array_values(array_unique(array_column($records, 'user_id')));
    $existingUsage = [];
    if (!empty($userIds)) {
        $placeholders = implode(',', array_fill(0, count($userIds), '?'));
        $stmt = $library->getPdo()->prepare("
            SELECT user_id, COUNT(*) as count 
            FROM borrowing_history 
            WHERE user_id IN ($placeholders) 
            AND subscription_id IS NULL 
            AND returned_at IS NULL 
            AND status IN ('approved', 'return_pending')
            GROUP BY user_id
        ");
        $stmt->execute($userIds);
        $existingUsage = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);
    }

    // Sort records by time to process pending requests in order
    // We only want to sort a copy for calculation purposes if needed, 
    // but here we can just iterate and keep track of a running counter.
    $runningUsage = $existingUsage;
    
    // The user's free limit for normal users (Bronze)
    $freeLimit = (int)getSetting('borrow_limit', 3);

    foreach ($records as &$r) {
        $r['is_overdue'] = strtotime($r['due_date']) < time() && in_array($r['status'], ['approved', 'return_pending']);
        $r['overdue_days'] = 0;
        $r['calculated_penalty'] = 0;
        if ($r['is_overdue']) {
            $r['overdue_days'] = (int)floor((time() - strtotime($r['due_date'])) / 86400);
            $finePerDay = (int)getSetting('fine_per_day', 500);
            $r['calculated_penalty'] = $r['overdue_days'] * $finePerDay;
        }

        $r['effective_borrow_price'] = (float)($r['borrow_fee'] ?? 0);
    }
unset($r);

renderAdminLayout('Borrow Management', function () use ($currentTab, $counts, $records, $library) {
?>

<style>
    .bm-stat-card {
        border: none; border-radius: 16px; padding: 20px 24px;
        background: #fff; box-shadow: 0 2px 12px rgba(0,0,0,0.04);
        transition: all 0.2s; cursor: pointer; text-decoration: none; display: block;
    }
    .bm-stat-card:hover { transform: translateY(-2px); box-shadow: 0 8px 24px rgba(0,0,0,0.08); }
    .bm-stat-card.active { border: 2px solid #4e73df; }
    .bm-stat-card .stat-num { font-size: 28px; font-weight: 800; color: #1e293b; }
    .bm-stat-card .stat-label { font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.5px; color: #94a3b8; }
    .bm-stat-card .stat-icon { width: 42px; height: 42px; border-radius: 12px; display: flex; align-items: center; justify-content: center; font-size: 16px; }

    .bm-tabs {
        display: flex; gap: 4px; background: #f1f5f9; border-radius: 14px;
        padding: 4px; margin-bottom: 24px; flex-wrap: wrap;
    }
    .bm-tab {
        padding: 10px 20px; border-radius: 11px; font-size: 13px; font-weight: 700;
        color: #64748b; border: none; background: transparent; cursor: pointer; transition: all 0.2s;
        display: flex; align-items: center; gap: 8px; text-decoration: none;
    }
    .bm-tab:hover { color: #1e293b; background: rgba(255,255,255,0.5); }
    .bm-tab.active { background: #fff; color: #1e293b; box-shadow: 0 2px 8px rgba(0,0,0,0.06); }
    .bm-tab .badge { font-size: 10px; padding: 3px 8px; border-radius: 999px; }

    .bm-table { width: 100%; border-collapse: separate; border-spacing: 0; }
    .bm-table thead th {
        background: #f8fafc; border-bottom: 2px solid #e2e8f0; padding: 14px 16px;
        font-size: 11px; font-weight: 800; text-transform: uppercase; letter-spacing: 0.5px; color: #64748b;
    }
    .bm-table tbody td {
        padding: 16px; border-bottom: 1px solid #f1f5f9; vertical-align: middle; font-size: 14px;
    }
    .bm-table tbody tr { transition: background 0.15s; }
    .bm-table tbody tr:hover { background: #fafbfc; }

    .bm-user-cell { display: flex; align-items: center; gap: 10px; }
    .bm-user-avatar {
        width: 36px; height: 36px; border-radius: 10px;
        background: linear-gradient(135deg, #4e73df, #6f42c1); color: #fff;
        display: flex; align-items: center; justify-content: center; font-weight: 700; font-size: 13px;
    }
    .bm-book-cell { display: flex; align-items: center; gap: 10px; }
    .bm-book-cover { width: 40px; height: 55px; border-radius: 6px; object-fit: cover; background: #f1f5f9; }

    .bm-status {
        display: inline-flex; align-items: center; gap: 5px; padding: 5px 12px;
        border-radius: 8px; font-size: 12px; font-weight: 700;
    }
    .bm-status.pending { background: #fef3c7; color: #92400e; }
    .bm-status.approved { background: #dbeafe; color: #1e40af; }
    .bm-status.return_pending { background: #ede9fe; color: #5b21b6; }
    .bm-status.returned { background: #d1fae5; color: #065f46; }
    .bm-status.rejected { background: #fee2e2; color: #991b1b; }
    .bm-status.overdue { background: #fee2e2; color: #dc2626; }

    .bm-btn {
        padding: 7px 16px; border-radius: 8px; font-size: 12px; font-weight: 700;
        border: none; cursor: pointer; transition: all 0.2s; display: inline-flex; align-items: center; gap: 5px;
    }
    .bm-btn-approve { background: #10b981; color: #fff; }
    .bm-btn-approve:hover { background: #059669; transform: translateY(-1px); }
    .bm-btn-reject { background: #ef4444; color: #fff; }
    .bm-btn-reject:hover { background: #dc2626; transform: translateY(-1px); }
    .bm-btn-paid { background: #6366f1; color: #fff; }
    .bm-btn-paid:hover { background: #4f46e5; }
    .fee-breakdown { min-width: 140px; background: #f8fafc; padding: 10px; border-radius: 12px; border: 1px solid #e2e8f0; }
    .smaller { font-size: 12px; }
    .bm-btn-outline { background: transparent; border: 1.5px solid #e2e8f0; color: #64748b; }
    .bm-btn-outline:hover { border-color: #94a3b8; color: #1e293b; }

    .bm-penalty-box {
        background: #fef2f2; border: 1px solid #fecaca; border-radius: 10px;
        padding: 8px 12px; font-size: 13px; color: #991b1b; font-weight: 600;
    }
    .bm-penalty-box.paid {
        background: #f0fdf4; border-color: #bbf7d0; color: #166534;
    }

    .bm-empty {
        text-align: center; padding: 60px 20px;
    }
    .bm-empty i { font-size: 48px; color: #cbd5e1; margin-bottom: 16px; }
    .bm-empty h5 { font-weight: 800; color: #334155; margin-bottom: 8px; }
    .bm-empty p { color: #94a3b8; font-size: 14px; }

    @media (max-width: 768px) {
        .bm-table { display: block; overflow-x: auto; }
        .bm-tabs { overflow-x: auto; flex-wrap: nowrap; }
    }
</style>

<!-- Stats Row -->
<div class="row g-3 mb-4">
    <div class="col-xl-2 col-md-4 col-6">
        <a href="?tab=pending" class="bm-stat-card <?= $currentTab === 'pending' ? 'active' : '' ?>">
            <div class="d-flex align-items-center justify-content-between mb-2">
                <div class="stat-icon" style="background:#fef3c7;color:#f59e0b;"><i class="fas fa-clock"></i></div>
            </div>
            <div class="stat-num"><?= $counts['pending'] ?></div>
            <div class="stat-label">Pending</div>
        </a>
    </div>
    <div class="col-xl-2 col-md-4 col-6">
        <a href="?tab=approved" class="bm-stat-card <?= $currentTab === 'approved' ? 'active' : '' ?>">
            <div class="d-flex align-items-center justify-content-between mb-2">
                <div class="stat-icon" style="background:#dbeafe;color:#3b82f6;"><i class="fas fa-book-reader"></i></div>
            </div>
            <div class="stat-num"><?= $counts['approved'] ?></div>
            <div class="stat-label">Active</div>
        </a>
    </div>
    <div class="col-xl-2 col-md-4 col-6">
        <a href="?tab=return_pending" class="bm-stat-card <?= $currentTab === 'return_pending' ? 'active' : '' ?>">
            <div class="d-flex align-items-center justify-content-between mb-2">
                <div class="stat-icon" style="background:#ede9fe;color:#8b5cf6;"><i class="fas fa-undo"></i></div>
            </div>
            <div class="stat-num"><?= $counts['return_pending'] ?></div>
            <div class="stat-label">Return Requests</div>
        </a>
    </div>
    <div class="col-xl-2 col-md-4 col-6">
        <a href="?tab=returned" class="bm-stat-card <?= $currentTab === 'returned' ? 'active' : '' ?>">
            <div class="d-flex align-items-center justify-content-between mb-2">
                <div class="stat-icon" style="background:#d1fae5;color:#10b981;"><i class="fas fa-check-circle"></i></div>
            </div>
            <div class="stat-num"><?= $counts['returned'] ?></div>
            <div class="stat-label">Returned</div>
        </a>
    </div>
    <div class="col-xl-2 col-md-4 col-6">
        <a href="?tab=rejected" class="bm-stat-card <?= $currentTab === 'rejected' ? 'active' : '' ?>">
            <div class="d-flex align-items-center justify-content-between mb-2">
                <div class="stat-icon" style="background:#fee2e2;color:#ef4444;"><i class="fas fa-times-circle"></i></div>
            </div>
            <div class="stat-num"><?= $counts['rejected'] ?></div>
            <div class="stat-label">Rejected</div>
        </a>
    </div>
    <div class="col-xl-2 col-md-4 col-6">
        <a href="?tab=all" class="bm-stat-card <?= $currentTab === 'all' ? 'active' : '' ?>">
            <div class="d-flex align-items-center justify-content-between mb-2">
                <div class="stat-icon" style="background:#f1f5f9;color:#475569;"><i class="fas fa-list"></i></div>
            </div>
            <div class="stat-num"><?= $counts['all'] ?></div>
            <div class="stat-label">All Records</div>
        </a>
    </div>
</div>

<!-- Table -->
<div class="card border-0 shadow-sm rounded-4 bg-white">
    <div class="card-header border-0 bg-transparent p-4 pb-0 d-flex justify-content-between align-items-center flex-wrap gap-2">
        <div>
            <h5 class="fw-800 text-dark mb-1">
                <?php
                $tabLabels = [
                    'pending' => '⏳ Pending Borrow Requests',
                    'approved' => '📖 Active Borrows',
                    'return_pending' => '📦 Return Requests',
                    'returned' => '✅ Returned Books',
                    'rejected' => '❌ Rejected Requests',
                    'all' => '📋 All Records',
                ];
                echo $tabLabels[$currentTab] ?? 'All Records';
                ?>
            </h5>
            <p class="text-muted small mb-0"><?= count($records) ?> records found</p>
        </div>
    </div>
    <div class="card-body p-4 pt-3">
        <?php
        $memberRecords = [];
        $normalRecords = [];
        foreach ($records as $r) {
            if (!empty($r['active_subscription_id'])) {
                $memberRecords[] = $r;
            } else {
                $normalRecords[] = $r;
            }
        }
        
        if (empty($records)): 
        ?>
            <div class="bm-empty">
                <i class="fas fa-inbox"></i>
                <h5>No records found</h5>
                <p>There are no borrow records matching this filter.</p>
            </div>
        <?php else: 
            $tableTypes = [
                ['title' => '<i class="fas fa-crown text-warning me-2"></i>Membership Plan Borrows', 'records' => $memberRecords, 'isMember' => true],
                ['title' => '<i class="fas fa-user text-primary me-2"></i>Pay-Per-Borrow (Normal)', 'records' => $normalRecords, 'isMember' => false],
            ];
            foreach ($tableTypes as $tt):
                if (empty($tt['records'])) continue;
                $isMem = $tt['isMember'];
        ?>
            <h6 class="fw-800 text-dark mb-3 px-2 pt-2"><?= $tt['title'] ?></h6>
            <div class="table-responsive-stack-container mb-4">
                <table class="table table-borderless align-middle admin-table-premium mb-0">
                    <thead class="bg-lightest">
                        <tr>
                            <th class="ps-4 text-uppercase smallest fw-800">ID</th>
                            <th class="text-uppercase smallest fw-800">USER</th>
                            <th class="text-uppercase smallest fw-800">BOOK DETAILS</th>
                            <th class="text-uppercase smallest fw-800">BORROWED ON</th>
                            <th class="text-uppercase smallest fw-800">DUE DATE</th>
                            <th class="text-uppercase smallest fw-800">STATUS</th>
                            <th class="px-4 text-uppercase smallest fw-800"><?= $isMem ? 'PLAN' : 'FEES' ?></th>
                            <th class="pe-4 text-center text-uppercase smallest fw-800">ACTIONS</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($tt['records'] as $idx => $r):
                            $coverUrl = getBookCoverUrl($r, $r['title'] ?? '', $r['author'] ?? '');
                            $fallback = getDummyBookCover($r['title'] ?? '', $r['author'] ?? '', 80, 110);
                        ?>
                        <tr>
                            <td class="ps-4 text-muted fw-bold" data-label="ID">#<?= $r['id'] ?></td>
                            <td data-label="USER">
                                <div class="bm-user-cell">
                                    <div class="bm-user-avatar bg-primary-soft text-primary"><?= strtoupper(substr($r['username'], 0, 1)) ?></div>
                                    <div>
                                        <div class="fw-bold" style="font-size:13px;"><?= e($r['username']) ?></div>
                                        <div class="text-muted smallest"><?= e($r['email']) ?></div>
                                    </div>
                                </div>
                            </td>
                            <td data-label="BOOK DETAILS">
                                <div class="bm-book-cell">
                                    <img src="<?= $coverUrl ?>" class="bm-book-cover shadow-sm rounded-1" alt="" onerror="this.src='<?= $fallback ?>'">
                                    <div class="overflow-hidden">
                                        <div class="fw-800 text-dark text-truncate" style="font-size:13px; max-width:180px;"><?= e($r['title']) ?></div>
                                        <div class="text-muted smallest"><?= e($r['author']) ?></div>
                                    </div>
                                </div>
                            </td>
                            <td data-label="BORROWED ON">
                                <div style="font-size:13px;" class="fw-bold"><?= date('M j, Y', strtotime($r['borrowed_at'])) ?></div>
                                <div class="text-muted smallest"><?= date('h:i A', strtotime($r['borrowed_at'])) ?></div>
                            </td>
                            <td data-label="DUE DATE">
                                <div style="font-size:13px;" class="fw-bold"><?= date('M j, Y', strtotime($r['due_date'])) ?></div>
                                <?php if ($r['is_overdue']): ?>
                                    <div class="text-danger fw-800 smallest">
                                        <i class="fas fa-exclamation-triangle ms-1"></i> <?= $r['overdue_days'] ?> days overdue
                                    </div>
                                <?php endif; ?>
                            </td>
                            <td data-label="STATUS">
                                <?php if ($r['is_overdue'] && in_array($r['status'], ['approved', 'return_pending'])): ?>
                                    <span class="bm-status overdue"><i class="fas fa-exclamation-circle"></i> Overdue</span>
                                <?php else: ?>
                                    <?php
                                    $statusIcons = [
                                        'pending' => 'fa-clock', 'approved' => 'fa-book-reader',
                                        'return_pending' => 'fa-undo', 'returned' => 'fa-check-circle',
                                        'rejected' => 'fa-times-circle',
                                    ];
                                    $icon = $statusIcons[$r['status']] ?? 'fa-circle';
                                    ?>
                                    <span class="bm-status <?= $r['status'] ?>"><i class="fas <?= $icon ?>"></i> <?= ucfirst(str_replace('_', ' ', $r['status'])) ?></span>
                                <?php endif; ?>
                            </td>
                             <td class="px-4" data-label="<?= $isMem ? 'PLAN' : 'FEES' ?>">
                                <div class="fee-breakdown">
                                    <?php if ($isMem): ?>
                                        <div class="d-flex justify-content-between mb-1">
                                            <span class="text-muted smallest fw-700">Plan:</span>
                                            <span class="fw-800 text-primary smaller"><?= ucfirst($r['subscription_tier'] ?? 'Membership') ?></span>
                                        </div>
                                        <div class="text-center mt-2 mb-1">
                                            <span class="badge bg-success text-white" style="font-size:10px;">Free Member Borrow</span>
                                        </div>
                                    <?php else: ?>
                                        <?php if ($r['effective_borrow_price'] > 0): ?>
                                            <div class="d-flex justify-content-between mb-1">
                                                <span class="text-muted smallest fw-700">Borrow:</span>
                                                <span class="fw-800 text-dark smaller"><?= number_format($r['effective_borrow_price']) ?> Ks</span>
                                            </div>
                                        <?php else: ?>
                                            <div class="text-center mt-2 mb-1">
                                                <span class="badge bg-success text-white" style="font-size:10px;">Free Token Used</span>
                                            </div>
                                        <?php endif; ?>
                                    <?php endif; ?>
                                    
                                    <?php 
                                    $penalty = max($r['calculated_penalty'], $r['penalty_fee'] ?? 0);
                                    if ($penalty > 0): 
                                    ?>
                                        <div class="<?= $isMem ? 'border-top pt-1 mt-1' : '' ?> d-flex justify-content-between mb-1">
                                            <span class="text-muted smallest fw-700">Penalty:</span>
                                            <span class="fw-800 text-danger smaller"><?= number_format($penalty) ?> Ks</span>
                                        </div>
                                    <?php endif; ?>
                                    
                                    <?php if (!$isMem && ($r['effective_borrow_price'] > 0 || $penalty > 0)): ?>
                                        <div class="border-top pt-1 mt-1 d-flex justify-content-between">
                                            <span class="text-dark smallest fw-800">Total:</span>
                                            <span class="fw-900 text-primary smaller"><?= number_format($r['effective_borrow_price'] + $penalty) ?> Ks</span>
                                        </div>
                                    <?php endif; ?>

                                    <?php if ($penalty > 0 && ($r['penalty_paid'] ?? 0)): ?>
                                        <div class="text-success smallest fw-800 mt-1"><i class="fas fa-check-circle me-1"></i>Penalty Paid</div>
                                    <?php endif; ?>
                                </div>
                            </td>
                            <td data-label="ACTIONS">
                                <div class="d-flex gap-1 flex-wrap">
                                    <?php if ($r['status'] === 'pending'): ?>
                                        <form method="POST" id="approveBorrowForm_<?= $r['id'] ?>" style="display:inline;">
                                            <input type="hidden" name="action" value="approve_borrow">
                                            <input type="hidden" name="borrow_id" value="<?= $r['id'] ?>">
                                            <input type="hidden" name="current_tab" value="<?= $currentTab ?>">
                                            <button type="button" class="bm-btn bm-btn-approve" onclick="confirmApproveBorrow('<?= $r['id'] ?>')">
                                                <i class="fas fa-check"></i> Approve
                                            </button>
                                        </form>
                                        <button type="button" class="bm-btn bm-btn-reject" onclick="rejectBorrow(<?= $r['id'] ?>)">
                                            <i class="fas fa-times"></i> Reject
                                        </button>
                                    <?php elseif ($r['status'] === 'return_pending'): ?>
                                        <div class="d-flex flex-column gap-1">
                                            <?php if ($r['return_screenshot']): ?>
                                                <button type="button" class="bm-btn bm-btn-outline" 
                                                        onclick="viewPayment('<?= $r['return_payment_method'] ?>', '<?= $r['return_screenshot'] ?>')">
                                                    <i class="fas fa-receipt"></i> View Payment
                                                </button>
                                            <?php endif; ?>
                                            <form method="POST" id="approveReturnForm_<?= $r['id'] ?>" style="display:inline;">
                                                <input type="hidden" name="action" value="approve_return">
                                                <input type="hidden" name="borrow_id" value="<?= $r['id'] ?>">
                                                <input type="hidden" name="current_tab" value="<?= $currentTab ?>">
                                                <button type="button" class="bm-btn bm-btn-approve w-100" 
                                                        onclick="confirmApproveReturn('<?= $r['id'] ?>', <?= (float)$r['calculated_penalty'] ?>)">
                                                    <i class="fas fa-check"></i> Approve Return
                                                </button>
                                            </form>
                                        </div>
                                    <?php elseif ($r['status'] === 'returned' && ($r['penalty_fee'] ?? 0) > 0 && !($r['penalty_paid'] ?? 0)): ?>
                                        <form method="POST" id="markPaidForm_<?= $r['id'] ?>" style="display:inline;">
                                            <input type="hidden" name="action" value="mark_paid">
                                            <input type="hidden" name="borrow_id" value="<?= $r['id'] ?>">
                                            <input type="hidden" name="current_tab" value="<?= $currentTab ?>">
                                            <button type="button" class="bm-btn bm-btn-paid" onclick="confirmMarkPaid(<?= $r['id'] ?>)">
                                                <i class="fas fa-money-bill-wave"></i> Mark Paid
                                            </button>
                                        </form>
                                    <?php else: ?>
                                        <span class="text-muted" style="font-size:12px;">—</span>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</div>

<!-- View Payment Modal -->
<div class="modal fade" id="viewPaymentModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg" style="border-radius:20px;">
            <div class="modal-header border-0 p-4 pb-0">
                <h5 class="modal-title fw-800">Payment Verification</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-4">
                <div class="mb-3">
                    <span class="text-muted smallest fw-700 text-uppercase">Method:</span>
                    <span id="view_pay_method" class="fw-800 text-primary ms-2"></span>
                </div>
                <div class="rounded-4 overflow-hidden border bg-light">
                    <img id="view_pay_ss" src="" class="img-fluid d-block mx-auto" style="max-height:500px;">
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Reject Modal via SweetAlert2 -->
<form method="POST" id="rejectForm" style="display:none;">
    <input type="hidden" name="action" value="reject_borrow">
    <input type="hidden" name="borrow_id" id="rejectBorrowId">
    <input type="hidden" name="reason" id="rejectReason">
    <input type="hidden" name="current_tab" value="<?= $currentTab ?>">
</form>

<script>
// Bind functions to window to ensure global availability even within complex renders
window.confirmApproveBorrow = function(borrowId) {
    console.log("Approving borrow:", borrowId);
    Swal.fire({
        title: 'Approve Borrow Request?',
        text: "User will be notified and copies will be deducted.",
        icon: 'question',
        showCancelButton: true,
        confirmButtonColor: '#10b981',
        cancelButtonColor: '#94a3b8',
        confirmButtonText: 'Yes, Approve',
        reverseButtons: true
    }).then((result) => {
        if (result.isConfirmed) {
            const form = document.getElementById('approveBorrowForm_' + borrowId);
            if (form) form.submit();
            else console.error("Form not found:", 'approveBorrowForm_' + borrowId);
        }
    });
};

window.confirmApproveReturn = function(borrowId, penalty) {
    console.log("Approving return:", borrowId, "Penalty:", penalty);
    let text = "Book will be returned to stock.";
    if (penalty > 0) {
        text += " A penalty of " + penalty.toLocaleString() + " Ks will be recorded.";
    }
    
    Swal.fire({
        title: 'Approve This Return?',
        text: text,
        icon: 'question',
        showCancelButton: true,
        confirmButtonColor: '#10b981',
        cancelButtonColor: '#94a3b8',
        confirmButtonText: 'Yes, Confirm Return',
        reverseButtons: true
    }).then((result) => {
        if (result.isConfirmed) {
            const form = document.getElementById('approveReturnForm_' + borrowId);
            if (form) form.submit();
            else console.error("Form not found:", 'approveReturnForm_' + borrowId);
        }
    });
};

window.confirmMarkPaid = function(borrowId) {
    Swal.fire({
        title: 'Mark Penalty as Paid?',
        text: "This action cannot be undone.",
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#6366f1',
        cancelButtonColor: '#94a3b8',
        confirmButtonText: 'Yes, Mark Paid',
        reverseButtons: true
    }).then((result) => {
        if (result.isConfirmed) {
            const form = document.getElementById('markPaidForm_' + borrowId);
            if (form) form.submit();
            else console.error("Form not found:", 'markPaidForm_' + borrowId);
        }
    });
};

window.rejectBorrow = function(borrowId) {
    Swal.fire({
        title: 'Reject Borrow Request',
        input: 'textarea',
        inputLabel: 'Reason for rejection (optional)',
        inputPlaceholder: 'Enter reason...',
        icon: 'warning',
        iconColor: '#ef4444',
        showCancelButton: true,
        confirmButtonText: '<i class="fas fa-times me-1"></i> Reject',
        cancelButtonText: 'Cancel',
        confirmButtonColor: '#ef4444',
        cancelButtonColor: '#94a3b8',
        reverseButtons: true
    }).then((result) => {
        if (result.isConfirmed) {
            document.getElementById('rejectBorrowId').value = borrowId;
            document.getElementById('rejectReason').value = result.value || '';
            document.getElementById('rejectForm').submit();
        }
    });
};

window.viewPayment = function(method, screenshot) {
    document.getElementById('view_pay_method').textContent = method;
    document.getElementById('view_pay_ss').src = '../' + screenshot;
    const myModal = new bootstrap.Modal(document.getElementById('viewPaymentModal'));
    myModal.show();
};
</script>

<?php
});
