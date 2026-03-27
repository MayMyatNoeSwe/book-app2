<?php
// admin/membership.php — Membership Tier Management
require_once dirname(__DIR__) . '/vendor/autoload.php';
require_once dirname(__DIR__) . '/includes/sessions.php';
require_once dirname(__DIR__) . '/includes/env_loader.php';
require_once dirname(__DIR__) . '/includes/functions.php';
require_once dirname(__DIR__) . '/views/admin/layout.php';

use App\Library;
$library = new Library();

// Check if user is admin
if (!isAdmin()) {
    header('Location: ../login.php');
    exit;
}

// Handle Actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $requestId = (int)($_POST['request_id'] ?? 0);

    switch ($action) {
        case 'approve':
            if ($library->approveMembershipUpgrade($requestId)) {
                setFlashMessage('Membership upgrade approved! User tier updated.', 'success');
            } else {
                setFlashMessage('Failed to approve upgrade.', 'danger');
            }
            break;
        case 'reject':
            $reason = trim($_POST['reason'] ?? '');
            if ($library->rejectMembershipRequest($requestId, $reason)) {
                setFlashMessage('Request rejected.', 'warning');
            } else {
                setFlashMessage('Failed to reject request.', 'danger');
            }
            break;
    }
    header('Location: membership.php?tab=' . ($_POST['current_tab'] ?? 'pending'));
    exit;
}

$currentTab = $_GET['tab'] ?? 'pending';
$counts = [
    'pending' => $library->countMembershipRequests('pending'),
    'approved' => $library->countMembershipRequests('approved'),
    'rejected' => $library->countMembershipRequests('rejected'),
    'all' => $library->countMembershipRequests('all')
];

$records = $library->getMembershipRequests($currentTab);

renderAdminLayout('Membership Management', function () use ($currentTab, $counts, $records) {
?>

<style>
    .ms-stat-card {
        border: none; border-radius: 16px; padding: 24px;
        background: #fff; box-shadow: 0 2px 12px rgba(0,0,0,0.04);
        transition: all 0.2s; cursor: pointer; text-decoration: none; display: block;
    }
    .ms-stat-card:hover { transform: translateY(-2px); box-shadow: 0 8px 24px rgba(0,0,0,0.08); }
    .ms-stat-card.active { border: 2px solid #6366f1; }
    .ms-stat-card .stat-num { font-size: 32px; font-weight: 800; color: #1e293b; margin: 8px 0 2px; }
    .ms-stat-card .stat-label { font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: 1px; color: #94a3b8; }
    .ms-stat-card .stat-icon { width: 48px; height: 48px; border-radius: 14px; display: flex; align-items: center; justify-content: center; font-size: 18px; }

    .ms-tabs {
        display: flex; gap: 8px; background: #f1f5f9; border-radius: 16px;
        padding: 6px; margin-bottom: 30px; 
    }
    .ms-tab {
        padding: 10px 24px; border-radius: 12px; font-size: 14px; font-weight: 700;
        color: #64748b; border: none; background: transparent; cursor: pointer; transition: all 0.2s;
        display: flex; align-items: center; gap: 10px; text-decoration: none;
    }
    .ms-tab:hover { color: #1e293b; background: rgba(255,255,255,0.6); }
    .ms-tab.active { background: #fff; color: #1e293b; box-shadow: 0 2px 8px rgba(0,0,0,0.06); }
    .ms-tab .badge { font-size: 11px; padding: 4px 10px; border-radius: 99px; }

    .ms-table-card { background:#fff; border-radius:20px; border:1px solid #e2e8f0; overflow:hidden; }
    .ms-table { width: 100%; border-collapse: separate; border-spacing: 0; }
    .ms-table thead th {
        background: #f8fafc; border-bottom: 2px solid #e2e8f0; padding: 18px 20px;
        font-size: 11px; font-weight: 800; text-transform: uppercase; letter-spacing: 1px; color: #64748b;
    }
    .ms-table tbody td { padding: 20px; border-bottom: 1px solid #f1f5f9; vertical-align: middle; }
    
    .ms-user-info { display: flex; align-items: center; gap: 12px; }
    .ms-avatar { width: 40px; height: 40px; border-radius: 12px; background: #f1f5ff; color: #4e73df; display: flex; align-items: center; justify-content: center; font-weight: 800; }
    .ms-tier-req { font-weight: 800; text-transform: uppercase; font-size: 12px; color: #4338ca; background: #eef2ff; padding: 4px 10px; border-radius: 6px; }
    
    .screenshot-btn {
        width: 60px; height: 80px; border-radius: 8px; overflow: hidden; border: 2px solid #e2e8f0; cursor: pointer; position: relative;
    }
    .screenshot-btn img { width: 100%; height: 100%; object-fit: cover; }
    .screenshot-btn:hover { border-color: #6366f1; }
    .screenshot-btn::after {
        content: '\f06e'; font-family: 'Font Awesome 5 Free'; font-weight: 900;
        position: absolute; inset: 0; background: rgba(0,0,0,0.4); color: #fff;
        display: flex; align-items: center; justify-content: center; opacity: 0; transition: 0.2s;
    }
    .screenshot-btn:hover::after { opacity: 1; }

    .ms-btn {
        padding: 8px 16px; border-radius: 10px; font-size: 13px; font-weight: 700;
        border: none; transition: 0.2s; display: inline-flex; align-items: center; gap: 6px;
    }
    .ms-btn-approve { background: #10b981; color: #fff; }
    .ms-btn-approve:hover { background: #059669; transform: translateY(-1px); }
    .ms-btn-reject { background: #fee2e2; color: #dc2626; }
    .ms-btn-reject:hover { background: #fecaca; }

    .ms-status-badge {
        padding: 5px 12px; border-radius: 8px; font-size: 12px; font-weight: 700; text-transform: capitalize;
    }
    .ms-status-badge.pending { background: #fef3c7; color: #d97706; }
    .ms-status-badge.approved { background: #d1fae5; color: #065f46; }
    .ms-status-badge.rejected { background: #fee2e2; color: #b91c1c; }
</style>

<div class="row g-4 mb-4">
    <div class="col-md-3">
        <a href="?tab=pending" class="ms-stat-card <?= $currentTab === 'pending' ? 'active' : '' ?>">
            <div class="stat-icon" style="background:#eef2ff; color:#6366f1;"><i class="fas fa-hourglass-start"></i></div>
            <div class="stat-num"><?= $counts['pending'] ?></div>
            <div class="stat-label">Pending Requests</div>
        </a>
    </div>
    <div class="col-md-3">
        <a href="?tab=approved" class="ms-stat-card <?= $currentTab === 'approved' ? 'active' : '' ?>">
            <div class="stat-icon" style="background:#f0fdf4; color:#22c55e;"><i class="fas fa-check-circle"></i></div>
            <div class="stat-num"><?= $counts['approved'] ?></div>
            <div class="stat-label">Approved Today</div>
        </a>
    </div>
</div>

<div class="ms-tabs">
    <a href="?tab=pending" class="ms-tab <?= $currentTab === 'pending' ? 'active' : '' ?>">
        <i class="fas fa-hourglass-half"></i> Pending <span class="badge bg-warning text-dark ms-1"><?= $counts['pending'] ?></span>
    </a>
    <a href="?tab=approved" class="ms-tab <?= $currentTab === 'approved' ? 'active' : '' ?>">
        <i class="fas fa-user-check"></i> Approved
    </a>
    <a href="?tab=rejected" class="ms-tab <?= $currentTab === 'rejected' ? 'active' : '' ?>">
        <i class="fas fa-user-times"></i> Rejected
    </a>
    <a href="?tab=all" class="ms-tab <?= $currentTab === 'all' ? 'active' : '' ?>">
        <i class="fas fa-list"></i> All Requests
    </a>
</div>

<div class="ms-table-card">
    <table class="ms-table">
        <thead>
            <tr>
                <th>User / Member</th>
                <th>Desired Tier</th>
                <th>Payment Method</th>
                <th>Receipt</th>
                <th>Action</th>
                <th>Requested At</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($records)): ?>
                <tr>
                    <td colspan="6" class="text-center py-5 text-muted">
                        <i class="fas fa-inbox fa-3x mb-3 d-block opacity-25"></i>
                        No <?= $currentTab ?> requests found.
                    </td>
                </tr>
            <?php endif; ?>
            <?php foreach ($records as $r): ?>
                <tr>
                    <td>
                        <div class="ms-user-info">
                            <div class="ms-avatar"><?= substr($r['username'], 0, 1) ?></div>
                            <div>
                                <div class="fw-bold"><?= e($r['username']) ?></div>
                                <div class="text-muted smaller"><?= e($r['email']) ?></div>
                                <div class="smaller text-muted mt-1">Current: <span class="text-primary fw-bold text-uppercase"><?= e($r['current_tier']) ?></span></div>
                            </div>
                        </div>
                    </td>
                    <td>
                        <span class="ms-tier-req"><?= e($r['tier']) ?></span>
                    </td>
                    <td>
                        <span class="fw-bold text-muted" style="font-size:13px;"><?= e($r['payment_method']) ?></span>
                    </td>
                    <td>
                        <?php if ($r['payment_screenshot']): ?>
                            <div class="screenshot-btn" onclick="viewReceipt('../<?= $r['payment_screenshot'] ?>')">
                                <img src="../<?= $r['payment_screenshot'] ?>" alt="Receipt">
                            </div>
                        <?php else: ?>
                            <span class="text-muted smaller">No Receipt</span>
                        <?php endif; ?>
                    </td>
                    <td>
                        <?php if ($r['status'] === 'pending'): ?>
                            <div class="d-flex gap-2">
                                <button class="ms-btn ms-btn-approve" onclick="approveReq(<?= $r['id'] ?>)"><i class="fas fa-check"></i> Approve</button>
                                <button class="ms-btn ms-btn-reject" onclick="rejectReq(<?= $r['id'] ?>)"><i class="fas fa-times"></i> Reject</button>
                            </div>
                        <?php else: ?>
                            <span class="ms-status-badge <?= $r['status'] ?>"><?= $r['status'] ?></span>
                        <?php endif; ?>
                    </td>
                    <td>
                        <div class="smaller text-muted"><?= date('M j, Y', strtotime($r['created_at'])) ?></div>
                        <div class="smaller" style="font-size:11px;"><?= date('h:i A', strtotime($r['created_at'])) ?></div>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>

<script>
function approveReq(id) {
    Swal.fire({
        title: 'Approve Upgrade?',
        text: 'This will update the user\'s membership tier immediately.',
        icon: 'question',
        showCancelButton: true,
        confirmButtonColor: '#10b981',
        cancelButtonColor: '#94a3b8',
        confirmButtonText: 'Yes, Approve User',
        reverseButtons: true
    }).then((result) => {
        if (result.isConfirmed) {
            const form = document.createElement('form');
            form.method = 'POST';
            form.innerHTML = `
                <input type="hidden" name="action" value="approve">
                <input type="hidden" name="request_id" value="${id}">
                <input type="hidden" name="current_tab" value="<?= $currentTab ?>">
            `;
            document.body.appendChild(form);
            form.submit();
        }
    });
}

function viewReceipt(src) {
    Swal.fire({
        title: 'Payment Receipt',
        imageUrl: src,
        imageAlt: 'Receipt',
        confirmButtonColor: '#6366f1',
        confirmButtonText: 'Close',
        width: 'auto'
    });
}

function rejectReq(id) {
    Swal.fire({
        title: 'Reject Request',
        input: 'textarea',
        inputPlaceholder: 'Reason for rejection (sent to user)...',
        showCancelButton: true,
        confirmButtonColor: '#ef4444',
        confirmButtonText: 'Reject Request',
        preConfirm: (reason) => {
            if (!reason) {
                Swal.showValidationMessage('Please provide a reason');
            }
            return reason;
        }
    }).then((result) => {
        if (result.isConfirmed) {
            const form = document.createElement('form');
            form.method = 'POST';
            form.innerHTML = `
                <input type="hidden" name="action" value="reject">
                <input type="hidden" name="request_id" value="${id}">
                <input type="hidden" name="reason" value="${result.value}">
                <input type="hidden" name="current_tab" value="<?= $currentTab ?>">
            `;
            document.body.appendChild(form);
            form.submit();
        }
    });
}
</script>

<?php }); ?>
