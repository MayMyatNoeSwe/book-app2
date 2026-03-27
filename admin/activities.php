<?php
// admin/activities.php
require_once dirname(__DIR__) . '/vendor/autoload.php';
require_once dirname(__DIR__) . '/includes/sessions.php';
require_once dirname(__DIR__) . '/includes/env_loader.php';
require_once dirname(__DIR__) . '/includes/functions.php';
require_once dirname(__DIR__) . '/views/admin/layout.php';

use App\Library;
$library = new Library();
$pdo = $library->getPdo();

// Fetch all activities
$activities = [];

// Recent borrows
try {
    $stmt = $pdo->query("
        SELECT u.username AS user, CONCAT('borrowed \"', b.title, '\"') AS action, bh.borrowed_at AS created_at, 'borrow' AS type
        FROM borrowing_history bh
        JOIN users u ON bh.user_id = u.id
        JOIN books b ON bh.book_id = b.id
        ORDER BY bh.borrowed_at DESC
    ");
    $activities = array_merge($activities, $stmt->fetchAll(PDO::FETCH_ASSOC));
} catch (Exception $e) {}

// Recent orders
try {
    $stmt = $pdo->query("
        SELECT u.username AS user, CONCAT('placed order #', o.order_number) AS action, o.created_at, 'order' AS type
        FROM orders o
        JOIN users u ON o.user_id = u.id
        ORDER BY o.created_at DESC
    ");
    $activities = array_merge($activities, $stmt->fetchAll(PDO::FETCH_ASSOC));
} catch (Exception $e) {}

// Recent user registrations
try {
    $stmt = $pdo->query("
        SELECT username AS user, 'registered as new member' AS action, created_at, 'user' AS type
        FROM users
        ORDER BY created_at DESC
    ");
    $activities = array_merge($activities, $stmt->fetchAll(PDO::FETCH_ASSOC));
} catch (Exception $e) {}

// Sort all activities by date descending
usort($activities, function($a, $b) {
    return strtotime($b['created_at']) - strtotime($a['created_at']);
});

// Convert timestamps to human-readable "time ago"
foreach ($activities as &$act) {
    $diff = time() - strtotime($act['created_at']);
    if ($diff < 60) $act['time'] = 'Just now';
    elseif ($diff < 3600) $act['time'] = floor($diff / 60) . ' min ago';
    elseif ($diff < 86400) $act['time'] = floor($diff / 3600) . ' hours ago';
    elseif ($diff < 604800) $act['time'] = floor($diff / 86400) . ' days ago';
    else $act['time'] = date('M d, Y', strtotime($act['created_at']));
}
unset($act);

renderAdminLayout('All Activities', function() use ($activities) {
    ?>
    <div class="mb-4 d-flex justify-content-between align-items-center">
        <div>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-1">
                    <li class="breadcrumb-item"><a href="index.php">Dashboard</a></li>
                    <li class="breadcrumb-item active" aria-current="page">All Activities</li>
                </ol>
            </nav>
            <h3 class="fw-800 text-dark mb-0">System Log & Activities</h3>
        </div>
        <a href="index.php" class="btn btn-light rounded-pill px-4 fw-bold border shadow-sm">
            <i class="fas fa-arrow-left me-2"></i>Dashboard
        </a>
    </div>

    <div class="card border-0 shadow-sm rounded-4 bg-white overflow-hidden">
        <div class="card-header border-0 bg-transparent p-4 pb-0">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h5 class="fw-800 text-dark mb-1">Activity History</h5>
                    <p class="text-muted smallest fw-bold m-0 text-uppercase tracking-wider">Full system activity feed</p>
                </div>
                <div class="badge bg-primary-soft text-primary rounded-pill px-3 py-2">Total: <?= count($activities) ?> items</div>
            </div>
        </div>
        <div class="card-body p-4 pt-5">
            <div class="timeline-premium">
                <?php foreach($activities as $act): 
                    $icon = 'fa-bolt';
                    $iconColor = 'text-primary';
                    $bgColor = 'bg-primary-soft';
                    if ($act['type'] === 'borrow') { $icon = 'fa-book-reader'; $iconColor = 'text-info'; $bgColor = 'bg-info-soft'; }
                    elseif ($act['type'] === 'order') { $icon = 'fa-shopping-bag'; $iconColor = 'text-success'; $bgColor = 'bg-success-soft'; }
                    elseif ($act['type'] === 'user') { $icon = 'fa-user-plus'; $iconColor = 'text-warning'; $bgColor = 'bg-warning-soft'; }
                ?>
                <div class="timeline-item-premium d-flex gap-4 mb-5">
                    <div class="timeline-bg-icon <?= $bgColor ?> <?= $iconColor ?> shadow-sm flex-shrink-0" style="width: 50px; height: 50px;">
                        <i class="fas <?= $icon ?>"></i>
                    </div>
                    <div class="flex-grow-1 border-bottom pb-4">
                        <div class="d-flex justify-content-between align-items-start mb-1">
                            <h6 class="mb-0 text-dark fw-800" style="font-size: 1.05rem;"><?= e($act['user']) ?></h6>
                            <span class="smallest text-muted fw-bold"><?= date('M d, Y • h:i A', strtotime($act['created_at'])) ?></span>
                        </div>
                        <p class="mb-2 text-muted fs-6"><?= e($act['action']) ?></p>
                        <span class="badge rounded-pill bg-light text-dark border px-2 py-1 smallest fw-bold opacity-75"><?= $act['time'] ?></span>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>

    <style>
    .bg-primary-soft { background: rgba(78, 115, 223, 0.1); }
    .bg-info-soft { background: rgba(54, 185, 204, 0.1); }
    .bg-success-soft { background: rgba(28, 200, 138, 0.1); }
    .bg-warning-soft { background: rgba(246, 194, 62, 0.1); }
    
    .timeline-premium { position: relative; padding-left: 25px; }
    .timeline-premium::before {
        content: '';
        position: absolute;
        left: -1px;
        top: 10px;
        bottom: 0;
        width: 2px;
        background: #f1f5f9;
        margin-left: 50px; /* Offset to center under icon box centers */
        z-index: 1;
        display: none; /* Already have icons space */
    }
    
    .timeline-item-premium { position: relative; z-index: 2; }
    
    .timeline-bg-icon {
        border-radius: 12px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.25rem;
        transition: transform 0.2s;
    }
    .timeline-bg-icon:hover { transform: scale(1.1); }
    
    .breadcrumb-item + .breadcrumb-item::before { content: "›"; font-weight: 800; color: #cbd5e1; }
    .letter-spacing-1 { letter-spacing: 0.1em; }
    </style>
    <?php
});
