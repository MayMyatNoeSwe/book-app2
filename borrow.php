<?php
$pageTitle = "My Borrowing History";
require_once 'includes/sessions.php';
require_once 'vendor/autoload.php';
require_once 'includes/env_loader.php';
require_once 'includes/functions.php';

use App\Auth;
use App\Library;

// Require authentication
if (!Auth::check()) {
    header('Location: login.php?redirect=borrow.php');
    exit;
}

$library = new Library();
$userId = Auth::id();

$config = require 'config/database.php';
$dsn = "mysql:host={$config['host']};dbname={$config['dbname']};charset={$config['charset']}";
$pdo = new PDO($dsn, $config['username'], $config['password'], $config['options']);

// Handle Return Action
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'return') {
    $bookId = $_POST['book_id'] ?? '';
    $paymentMethod = $_POST['payment_method'] ?? 'manual';
    
    $screenshotPath = null;
    if (isset($_FILES['screenshot']) && $_FILES['screenshot']['error'] === 0) {
        $ext = pathinfo($_FILES['screenshot']['name'], PATHINFO_EXTENSION);
        $fileName = 'return_' . $userId . '_' . time() . '.' . $ext;
        $uploadDir = 'assets/uploads/payments/';
        if (!is_dir($uploadDir)) mkdir($uploadDir, 0777, true);
        if (move_uploaded_file($_FILES['screenshot']['tmp_name'], $uploadDir . $fileName)) {
            $screenshotPath = $uploadDir . $fileName;
        }
    }

    if ($library->returnBook($bookId, $userId, ['method' => $paymentMethod, 'screenshot' => $screenshotPath])) {
        $_SESSION['success_msg'] = "Return request submitted! Waiting for admin approval.";
    } else {
        $_SESSION['error_msg'] = "Failed to submit return request.";
    }
    header('Location: borrow.php');
    exit;
}

// Fetch Pending Borrows
$stmtPending = $pdo->prepare("
    SELECT bh.*, b.title, b.author, b.cover_image, b.category 
    FROM borrowing_history bh
    JOIN books b ON bh.book_id = b.id
    WHERE bh.user_id = ? AND bh.`status` = 'pending'
    ORDER BY bh.borrowed_at DESC
");
$stmtPending->execute([$userId]);
$pendingBorrows = $stmtPending->fetchAll();

// Fetch Active Borrows (approved)
$stmtActive = $pdo->prepare("
    SELECT bh.*, b.title, b.author, b.cover_image, b.category, b.borrow_price 
    FROM borrowing_history bh
    JOIN books b ON bh.book_id = b.id
    WHERE bh.user_id = ? AND bh.`status` IN ('approved', 'return_pending') AND bh.returned_at IS NULL
    ORDER BY bh.borrowed_at DESC
");
$stmtActive->execute([$userId]);
$activeBorrows = $stmtActive->fetchAll();

// Fetch Past Borrows (returned + rejected)
$stmtPast = $pdo->prepare("
    SELECT bh.*, b.title, b.author, b.cover_image, b.category, b.borrow_price 
    FROM borrowing_history bh
    JOIN books b ON bh.book_id = b.id
    WHERE bh.user_id = ? AND (bh.`status` IN ('returned', 'rejected') OR bh.returned_at IS NOT NULL)
    ORDER BY COALESCE(bh.returned_at, bh.borrowed_at) DESC
");
$stmtPast->execute([$userId]);
$pastBorrows = $stmtPast->fetchAll();

include 'views/header.php';
?>

<style>
/* ─── Premium Borrow Page ─── */
.bw-hero {
    position: relative; overflow: hidden;
    padding: 60px 0 40px; border-bottom: 1px solid rgba(0,0,0,0.06);
    background:
        radial-gradient(ellipse at 80% 20%, rgba(59,130,246,0.08) 0%, transparent 60%),
        radial-gradient(ellipse at 20% 80%, rgba(16,185,129,0.05) 0%, transparent 50%),
        var(--bookhouse-bg, #f8f9fa);
}
[data-bs-theme="dark"] .bw-hero {
    background:
        radial-gradient(ellipse at 80% 20%, rgba(59,130,246,0.12) 0%, transparent 60%),
        radial-gradient(ellipse at 20% 80%, rgba(16,185,129,0.1) 0%, transparent 50%),
        #0f172a; border-bottom-color: rgba(255,255,255,0.06);
}
.bw-hero::before {
    content: ''; position: absolute; inset: 0;
    background-image:
        linear-gradient(rgba(0,0,0,0.02) 1px, transparent 1px),
        linear-gradient(90deg, rgba(0,0,0,0.02) 1px, transparent 1px);
    background-size: 50px 50px; pointer-events: none;
}
[data-bs-theme="dark"] .bw-hero::before {
    background-image:
        linear-gradient(rgba(255,255,255,0.02) 1px, transparent 1px),
        linear-gradient(90deg, rgba(255,255,255,0.02) 1px, transparent 1px);
}
.bw-title {
    font-family: 'Playfair Display', serif; font-weight: 800;
    font-size: clamp(2rem, 4vw, 3rem); color: var(--bookhouse-text); margin-bottom: 6px;
    position: relative; z-index: 2;
}
.bw-subtitle { font-size: 16px; color: var(--bookhouse-text-muted); position: relative; z-index: 2; }

/* Custom Tabs */
.bw-nav-pills {
    background: #fff; border: 1px solid rgba(0,0,0,0.06);
    border-radius: 16px; padding: 6px; display: inline-flex; gap: 6px;
    box-shadow: 0 4px 15px rgba(0,0,0,0.02); margin-bottom: 30px;
}
[data-bs-theme="dark"] .bw-nav-pills { background: #1e293b; border-color: rgba(255,255,255,0.06); }
.bw-nav-pills .nav-link {
    border-radius: 12px; font-weight: 700; font-size: 14px;
    padding: 10px 24px; color: var(--bookhouse-text-muted); border: none; transition: all 0.2s;
}
.bw-nav-pills .nav-link:hover { color: var(--bookhouse-text); background: rgba(0,0,0,0.02); }
[data-bs-theme="dark"] .bw-nav-pills .nav-link:hover { background: rgba(255,255,255,0.02); }
.bw-nav-pills .nav-link.active { background: var(--bookhouse-orange); color: #fff; box-shadow: 0 4px 12px rgba(224,122,95,0.3); }

/* Book Card */
.bw-card {
    background: #fff; border: 1px solid rgba(0,0,0,0.06);
    border-radius: 20px; overflow: hidden; height: 100%;
    transition: transform 0.2s, box-shadow 0.2s;
    display: flex; flex-direction: column;
}
[data-bs-theme="dark"] .bw-card { background: #1e293b; border-color: rgba(255,255,255,0.06); }
.bw-card:hover { transform: translateY(-4px); box-shadow: 0 12px 30px rgba(0,0,0,0.06); }

.bw-card-top { display: flex; padding: 20px; gap: 18px; border-bottom: 1px dashed rgba(0,0,0,0.08); flex: 1; }
[data-bs-theme="dark"] .bw-card-top { border-bottom-color: rgba(255,255,255,0.08); }
.bw-cover {
    width: 80px; height: 115px; border-radius: 10px; flex-shrink: 0;
    object-fit: cover; box-shadow: 0 4px 12px rgba(0,0,0,0.08); background: #f0e6e0;
}
[data-bs-theme="dark"] .bw-cover { background: #253141; }
.bw-info { flex: 1; min-width: 0; }
.bw-cat { font-size: 10px; font-weight: 800; text-transform: uppercase; letter-spacing: 1px; color: var(--bookhouse-orange); margin-bottom: 4px; }
.bw-info h5 { font-weight: 800; font-size: 16px; margin-bottom: 4px; color: var(--bookhouse-text); white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
.bw-info h5 a { color: inherit; text-decoration: none; }
.bw-info h5 a:hover { color: var(--bookhouse-orange); }
.bw-author { font-size: 13px; color: var(--bookhouse-text-muted); margin-bottom: 12px; }

/* Status Badges & Due Dates */
.bw-status {
    display: inline-flex; align-items: center; gap: 6px; padding: 6px 12px; border-radius: 8px; font-size: 12px; font-weight: 700;
}
.bw-status i { font-size: 14px; }
.bw-time { font-size: 12px; color: var(--bookhouse-text-muted); margin-top: 6px; display: flex; align-items: center; gap: 6px; }
.bw-time i { opacity: 0.6; }

.bw-status.active { background: rgba(59,130,246,0.1); color: #3b82f6; }
.bw-status.overdue { background: rgba(239,68,68,0.1); color: #ef4444; }
.bw-status.returned { background: rgba(16,185,129,0.1); color: #10b981; }

.bw-card-actions { padding: 16px 20px; background: rgba(0,0,0,0.015); }
[data-bs-theme="dark"] .bw-card-actions { background: rgba(0,0,0,0.2); }
.bw-btn-return {
    width: 100%; padding: 10px; border: none; border-radius: 10px;
    background: var(--bookhouse-text); color: var(--bookhouse-bg);
    font-weight: 700; font-size: 14px; transition: all 0.2s;
    display: flex; align-items: center; justify-content: center; gap: 8px;
}
.bw-btn-return:hover { background: var(--bookhouse-orange); color: #fff; transform: translateY(-1px); }
.bw-btn-returned {
    width: 100%; padding: 10px; border-radius: 10px; text-align: center;
    background: transparent; color: var(--bookhouse-text-muted); font-size: 14px; font-weight: 600; border: 2px dashed rgba(0,0,0,0.08); pointer-events: none;
}
[data-bs-theme="dark"] .bw-btn-returned { border-color: rgba(255,255,255,0.08); }

/* Empty state */
.bw-empty {
    text-align: center; padding: 60px 20px; background: #fff;
    border-radius: 20px; border: 1px solid rgba(0,0,0,0.06);
}
[data-bs-theme="dark"] .bw-empty { background: #1e293b; border-color: rgba(255,255,255,0.06); }
.bw-empty i { font-size: 40px; color: var(--bookhouse-text-muted); opacity: 0.3; margin-bottom: 16px; }
.bw-empty h4 { font-weight: 800; color: var(--bookhouse-text); margin-bottom: 8px; }
.bw-empty p { color: var(--bookhouse-text-muted); font-size: 15px; }

</style>

<div class="bw-hero">
    <div class="container text-center">
        <h1 class="bw-title"><i class="fas fa-book-reader me-2"></i>My Borrowing</h1>
        <p class="bw-subtitle">Manage your active physical books and due dates</p>
    </div>
</div>

<div class="container py-5">
    
    <?php if (isset($_SESSION['success_msg'])): ?>
        <script>
            document.addEventListener("DOMContentLoaded", function() {
                Swal.fire({ icon: 'success', title: 'Success', text: '<?= e($_SESSION['success_msg']) ?>', confirmButtonColor: '#E07A5F' });
            });
        </script>
        <?php unset($_SESSION['success_msg']); ?>
    <?php endif; ?>
    <?php if (isset($_SESSION['error_msg'])): ?>
        <script>
            document.addEventListener("DOMContentLoaded", function() {
                Swal.fire({ icon: 'error', title: 'Oops', text: '<?= e($_SESSION['error_msg']) ?>', confirmButtonColor: '#E07A5F' });
            });
        </script>
        <?php unset($_SESSION['error_msg']); ?>
    <?php endif; ?>

    <!-- Tabs -->
    <div class="text-center">
        <ul class="nav bw-nav-pills" id="borrowTabs" role="tablist">
            <li class="nav-item" role="presentation">
                <button class="nav-link active" id="active-tab" data-bs-toggle="tab" data-bs-target="#active" type="button" role="tab" aria-selected="true">
                    Active Borrows (<?= count($activeBorrows) ?>)
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="pending-tab" data-bs-toggle="tab" data-bs-target="#pending" type="button" role="tab" aria-selected="false">
                    Pending Requests (<?= count($pendingBorrows) ?>)
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="past-tab" data-bs-toggle="tab" data-bs-target="#past" type="button" role="tab" aria-selected="false">
                    Past History (<?= count($pastBorrows) ?>)
                </button>
            </li>
        </ul>
    </div>

    <div class="tab-content" id="borrowTabsContent">
        
        <!-- Active Tab -->
        <div class="tab-pane fade show active" id="active" role="tabpanel" tabindex="0">
            <?php if (empty($activeBorrows)): ?>
                <div class="bw-empty">
                    <i class="fas fa-search"></i>
                    <h4>No active borrows</h4>
                    <p>It seems your reading desk is empty. Find a great book to borrow!</p>
                    <a href="book-list.php?availability=available" class="btn btn-primary fw-bold mt-2" style="background:var(--bookhouse-orange); border:none;"><i class="fas fa-book me-2"></i>Browse Library</a>
                </div>
            <?php else: ?>
                <div class="row row-cols-1 row-cols-md-2 row-cols-lg-3 g-4">
                    <?php foreach ($activeBorrows as $b): 
                        $isOverdue = strtotime($b['due_date']) < time() && $b['status'] === 'approved';
                        $isReturnPending = $b['status'] === 'return_pending';
                    ?>
                        <div class="col">
                            <div class="bw-card">
                                <div class="bw-card-top">
                                    <img src="<?= getBookCoverUrl((object)$b, $b['title'], $b['author']) ?>" class="bw-cover"
                                         onerror="this.src='<?= getDummyBookCover($b['title'], $b['author'], 150, 200) ?>'">
                                    <div class="bw-info">
                                        <div class="bw-cat"><?= e($b['category']) ?></div>
                                        <h5><a href="book-details.php?id=<?= $b['book_id'] ?>"><?= e($b['title']) ?></a></h5>
                                        <div class="bw-author">by <?= e($b['author']) ?></div>
                                        
                                        <?php if ($isReturnPending): ?>
                                            <div class="bw-status return_pending" style="background:rgba(139,92,246,0.1); color:#7c3aed;"><i class="fas fa-hourglass-half"></i> Return Pending</div>
                                        <?php elseif ($isOverdue): ?>
                                            <div class="bw-status overdue"><i class="fas fa-exclamation-circle"></i> Overdue</div>
                                        <?php else: ?>
                                            <div class="bw-status active"><i class="fas fa-clock"></i> Reading</div>
                                        <?php endif; ?>
                                        
                                        <div class="bw-time">
                                            <i class="far fa-calendar"></i> Due: <?= date('M j, Y', strtotime($b['due_date'])) ?>
                                        </div>

                                         <div class="mt-2 p-2 rounded-3 bg-light-subtle border" style="font-size:11px;">
                                            <div class="d-flex justify-content-between mb-1">
                                                <span class="text-muted">Borrow Fee:</span>
                                                <span class="fw-bold"><?= number_format($b['borrow_price']) ?> Ks</span>
                                            </div>
                                            <?php 
                                            $p = 0;
                                            if ($isOverdue): 
                                                $overdueDays = (int)floor((time() - strtotime($b['due_date'])) / 86400);
                                                $p = $overdueDays * 500;
                                            ?>
                                                <div class="d-flex justify-content-between text-danger mb-1">
                                                    <span>Penalty:</span>
                                                    <span class="fw-bold">+ <?= number_format($p) ?> Ks</span>
                                                </div>
                                            <?php endif; ?>
                                            <div class="d-flex justify-content-between border-top pt-1 mt-1 text-primary">
                                                <span class="fw-bold">Total:</span>
                                                <span class="fw-bold"><?= number_format($b['borrow_price'] + $p) ?> Ks</span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="bw-card-actions">
                                    <?php if ($isReturnPending): ?>
                                        <div class="bw-btn-returned" style="border-style: solid;"><i class="fas fa-clock me-2"></i>Waiting Approval</div>
                                    <?php else: ?>
                                        <button type="button" class="bw-btn-return" 
                                                onclick="initiateReturn('<?= e($b['book_id']) ?>', '<?= e($b['title']) ?>', <?= $b['borrow_price'] + $p ?>)">
                                            <i class="fas fa-box"></i> Return Book
                                        </button>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>

        <!-- Pending Tab -->
        <div class="tab-pane fade" id="pending" role="tabpanel" tabindex="0">
            <?php if (empty($pendingBorrows)): ?>
                <div class="bw-empty">
                    <i class="fas fa-hourglass-start"></i>
                    <h4>No pending requests</h4>
                    <p>Borrow requests waiting for admin approval will appear here.</p>
                </div>
            <?php else: ?>
                <div class="row row-cols-1 row-cols-md-2 row-cols-lg-3 g-4">
                    <?php foreach ($pendingBorrows as $b): ?>
                        <div class="col">
                            <div class="bw-card">
                                <div class="bw-card-top">
                                    <img src="<?= getBookCoverUrl((object)$b, $b['title'], $b['author']) ?>" class="bw-cover"
                                         onerror="this.src='<?= getDummyBookCover($b['title'], $b['author'], 150, 200) ?>'">
                                    <div class="bw-info">
                                        <div class="bw-cat"><?= e($b['category']) ?></div>
                                        <h5><a href="book-details.php?id=<?= $b['book_id'] ?>"><?= e($b['title']) ?></a></h5>
                                        <div class="bw-author">by <?= e($b['author']) ?></div>
                                        
                                        <div class="bw-status pending" style="background:rgba(245,158,11,0.1); color:#d97706;"><i class="fas fa-clock"></i> Pending Approval</div>
                                        
                                        <div class="bw-time">
                                            <i class="far fa-calendar-plus"></i> Requested: <?= date('M j, Y', strtotime($b['borrowed_at'])) ?>
                                        </div>
                                    </div>
                                </div>
                                <div class="bw-card-actions">
                                    <div class="bw-btn-returned" style="border-style: solid;"><i class="fas fa-shield-alt me-2"></i>Under Review</div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>

        <!-- Past Tab -->
        <div class="tab-pane fade" id="past" role="tabpanel" tabindex="0">
            <?php if (empty($pastBorrows)): ?>
                <div class="bw-empty">
                    <i class="fas fa-history"></i>
                    <h4>No history yet</h4>
                    <p>When you return books, they'll show up here.</p>
                </div>
            <?php else: ?>
                <div class="row row-cols-1 row-cols-md-2 row-cols-lg-3 g-4">
                    <?php foreach ($pastBorrows as $b): ?>
                        <div class="col">
                            <div class="bw-card">
                                <div class="bw-card-top">
                                    <img src="<?= getBookCoverUrl((object)$b, $b['title'], $b['author']) ?>" class="bw-cover bw-desaturated" style="filter: grayscale(40%);"
                                         onerror="this.src='<?= getDummyBookCover($b['title'], $b['author'], 150, 200) ?>'">
                                    <div class="bw-info">
                                        <div class="bw-cat"><span style="color:var(--bookhouse-text-muted);"><?= e($b['category']) ?></span></div>
                                        <h5><a href="book-details.php?id=<?= $b['book_id'] ?>"><?= e($b['title']) ?></a></h5>
                                        <div class="bw-author">by <?= e($b['author']) ?></div>
                                        
                                        <?php if ($b['status'] === 'rejected'): ?>
                                            <div class="bw-status overdue" style="background:rgba(239,68,68,0.05);"><i class="fas fa-times-circle"></i> Rejected</div>
                                            <?php if ($b['admin_notes']): ?>
                                                <div class="mt-1 small text-muted">Reason: <?= e($b['admin_notes']) ?></div>
                                            <?php endif; ?>
                                        <?php else: ?>
                                            <div class="bw-status returned" style="background:rgba(0,0,0,0.05); color:var(--bookhouse-text-muted);"><i class="fas fa-check-circle"></i> Returned</div>
                                        <?php endif; ?>
                                        
                                        <div class="bw-time mt-2">
                                            <div>Borrowed: <?= date('M j, Y', strtotime($b['borrowed_at'])) ?></div>
                                            <?php if ($b['returned_at']): ?>
                                                <div class="w-100"></div>
                                                <div>Returned: <?= date('M j, Y', strtotime($b['returned_at'])) ?></div>
                                            <?php endif; ?>
                                        </div>

                                         <div class="mt-2 p-2 rounded-3 bg-light-subtle border" style="font-size:11px;">
                                            <div class="d-flex justify-content-between mb-1">
                                                <span class="text-muted">Borrow Fee:</span>
                                                <span class="fw-bold"><?= number_format($b['borrow_price']) ?> Ks</span>
                                            </div>
                                            <?php if (($b['penalty_fee'] ?? 0) > 0 || ($isOverdue ?? false)): 
                                                $p = max($b['penalty_fee'] ?? 0, $penalty ?? 0);
                                                if ($p > 0):
                                            ?>
                                                <div class="d-flex justify-content-between text-danger mb-1">
                                                    <span>Penalty:</span>
                                                    <span class="fw-bold">+ <?= number_format($p) ?> Ks</span>
                                                </div>
                                            <?php endif; endif; ?>
                                            <div class="d-flex justify-content-between border-top pt-1 mt-1 text-primary">
                                                <span class="fw-bold">Total:</span>
                                                <span class="fw-bold"><?= number_format($b['borrow_price'] + ($p ?? 0)) ?> Ks</span>
                                            </div>
                                            <?php if (($b['penalty_fee'] ?? 0) > 0 && ($b['penalty_paid'] ?? 0)): ?>
                                                <div class="text-success mt-1 fw-bold"><i class="fas fa-check-circle"></i> Paid</div>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>
                                <div class="bw-card-actions">
                                    <div class="bw-btn-returned">
                                        <?php if ($b['status'] === 'rejected'): ?>
                                            <i class="fas fa-times me-2"></i>Rejected
                                        <?php else: ?>
                                            <i class="fas fa-check me-2"></i>Completed
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Payment Return Modal -->
<div class="modal fade" id="paymentModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg" style="border-radius:24px; overflow:hidden;">
            <div class="modal-header border-0 bg-primary text-white p-4">
                <h5 class="modal-title fw-800"><i class="fas fa-wallet me-2"></i>Finalize Return</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="borrow.php" method="POST" enctype="multipart/form-data">
                <div class="modal-body p-4">
                    <input type="hidden" name="action" value="return">
                    <input type="hidden" name="book_id" id="modal_book_id">
                    
                    <div class="text-center mb-4">
                        <div class="text-muted smaller fw-700 text-uppercase mb-1">Book to Return</div>
                        <h4 class="fw-800 text-dark mb-3" id="modal_book_title">—</h4>
                        <div class="d-inline-block py-2 px-4 bg-primary-subtle text-primary rounded-pill fw-900" style="font-size:18px;">
                            <span id="modal_total_amount">0</span> Ks
                        </div>
                    </div>

                    <div class="mb-4">
                        <label class="form-label fw-800 text-muted smaller text-uppercase">1. Choose Payment Method</label>
                        <div class="row g-2">
                            <div class="col-4">
                                <input type="radio" class="btn-check" name="payment_method" id="pay_wave" value="WavePay" checked onclick="updateQR('WavePay')">
                                <label class="btn btn-outline-primary w-100 p-3 rounded-4 fw-800" for="pay_wave">
                                    <img src="https://encrypted-tbn0.gstatic.com/images?q=tbn:ANd9GcR_LqI0S2I0m4hQj5vY5PzN4Y3F5G8p7y9-vA&s" class="d-block mx-auto mb-2 rounded-2" width="30">
                                    <span style="font-size:10px;">Wave</span>
                                </label>
                            </div>
                            <div class="col-4">
                                <input type="radio" class="btn-check" name="payment_method" id="pay_kpay" value="KPay" onclick="updateQR('KPay')">
                                <label class="btn btn-outline-primary w-100 p-3 rounded-4 fw-800" for="pay_kpay">
                                    <img src="https://upload.wikimedia.org/wikipedia/commons/0/0e/KPAY_logo.png" class="d-block mx-auto mb-2" width="30">
                                    <span style="font-size:10px;">KPay</span>
                                </label>
                            </div>
                            <div class="col-4">
                                <input type="radio" class="btn-check" name="payment_method" id="pay_kbz" value="KBZPay" onclick="updateQR('KBZPay')">
                                <label class="btn btn-outline-primary w-100 p-3 rounded-4 fw-800" for="pay_kbz">
                                    <img src="https://play-lh.googleusercontent.com/yU4V_rY0U2_Pz_f-fG-_vW8GvT_9vU4V_rY0U2_Pz_f-fG-" class="d-block mx-auto mb-2" width="30">
                                    <span style="font-size:10px;">KBZPay</span>
                                </label>
                            </div>
                        </div>
                    </div>

                    <div class="mb-4 text-center p-3 rounded-4 bg-light shadow-inner" id="qr_section">
                        <div class="text-muted smaller fw-700 text-uppercase mb-2">Scan to Pay</div>
                        <img id="payment_qr" src="public/img/payments/wave_qr.png" class="img-fluid rounded-3 border bg-white p-2 shadow-sm" style="max-height: 180px;">
                        <div class="mt-2 text-primary smaller fw-800" id="qr_label">WavePay Merchant</div>
                    </div>

                    <div class="mb-4">
                        <label class="form-label fw-800 text-muted smaller text-uppercase">2. Upload Screenshot</label>
                        <div class="p-3 border-dashed rounded-4 bg-light text-center pointer-cursor" onclick="document.getElementById('ss_input').click()">
                            <input type="file" name="screenshot" id="ss_input" hidden accept="image/*" required onchange="previewScreenshot(this)">
                            <div id="ss_preview" class="mb-2 d-none">
                                <img src="" id="ss_img" class="img-fluid rounded-3 shadow-sm" style="max-height: 150px;">
                            </div>
                            <div id="ss_placeholder">
                                <i class="fas fa-cloud-upload-alt text-primary fa-2x mb-2"></i>
                                <div class="smaller fw-700 text-muted">Tap to upload transaction receipt</div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer border-0 p-4 pt-0">
                    <button type="submit" class="btn btn-primary w-100 py-3 rounded-4 fw-900 shadow-sm border-0" style="background:var(--bookhouse-orange);">
                        Submit Return Request
                    </button>
                    <button type="button" class="btn btn-link text-muted fw-700 w-100 mt-2 text-decoration-none" data-bs-dismiss="modal">Cancel</button>
                </div>
            </form>
        </div>
    </div>
</div>

<style>
.border-dashed { border: 2px dashed #cbd5e1; transition: all 0.2s; }
.border-dashed:hover { border-color: #3b82f6; background: #eff6ff !important; cursor: pointer; }
.pointer-cursor { cursor: pointer; }
</style>

<script>
function updateQR(method) {
    const qrImg = document.getElementById('payment_qr');
    const qrLabel = document.getElementById('qr_label');
    
    if (method === 'WavePay') {
        qrImg.src = 'public/img/payments/wave_qr.png';
        qrLabel.textContent = 'WavePay Merchant';
    } else if (method === 'KPay') {
        qrImg.src = 'public/img/payments/kpay_qr.png';
        qrLabel.textContent = 'KPay Merchant';
    } else if (method === 'KBZPay') {
        qrImg.src = 'public/img/payments/kbzpay_qr.png';
        qrLabel.textContent = 'KBZPay Merchant';
    }
}

function initiateReturn(bookId, title, amount) {
    document.getElementById('modal_book_id').value = bookId;
    document.getElementById('modal_book_title').textContent = title;
    document.getElementById('modal_total_amount').textContent = amount.toLocaleString();
    
    // Reset form
    document.getElementById('ss_input').value = '';
    document.getElementById('ss_preview').classList.add('d-none');
    document.getElementById('ss_placeholder').classList.remove('d-none');
    document.getElementById('pay_wave').click();
    
    // Show modal
    const myModal = new bootstrap.Modal(document.getElementById('paymentModal'));
    myModal.show();
}

function previewScreenshot(input) {
    if (input.files && input.files[0]) {
        var reader = new FileReader();
        reader.onload = function(e) {
            document.getElementById('ss_img').src = e.target.result;
            document.getElementById('ss_preview').classList.remove('d-none');
            document.getElementById('ss_placeholder').classList.add('d-none');
        }
        reader.readAsDataURL(input.files[0]);
    }
}
</script>

<?php include 'views/footer.php'; ?>
