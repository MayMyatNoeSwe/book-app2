<?php
$pageTitle = "My Profile";
require_once 'includes/sessions.php';
require_once 'vendor/autoload.php';
require_once 'includes/env_loader.php';
require_once 'includes/functions.php';

use App\Auth;
use App\Cart;

if (!Auth::check()) {
    header("Location: login.php?redirect=user-details.php");
    exit;
}

$config = require 'config/database.php';
$dsn = "mysql:host={$config['host']};dbname={$config['dbname']};charset={$config['charset']}";
$pdo = new PDO($dsn, $config['username'], $config['password'], $config['options']);

$userId = Auth::id();

// Fetch user info
$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$userId]);
$user = $stmt->fetch();

// Fetch statistics
$stmt = $pdo->prepare("SELECT COUNT(*) FROM borrowing_history WHERE user_id = ?");
$stmt->execute([$userId]);
$totalBorrows = $stmt->fetchColumn();

$stmt = $pdo->prepare("SELECT COUNT(*) FROM orders WHERE user_id = ?");
$stmt->execute([$userId]);
$totalOrders = $stmt->fetchColumn();

$stmt = $pdo->prepare("SELECT COUNT(*) FROM reviews WHERE user_id = ?");
$stmt->execute([$userId]);
$totalReviews = $stmt->fetchColumn();

$stmt = $pdo->prepare("SELECT COUNT(*) FROM reservations WHERE user_id = ?");
$stmt->execute([$userId]);
$totalReservations = $stmt->fetchColumn();

// Fetch Borrowing History
$stmt = $pdo->prepare("
    SELECT bh.*, b.title, b.author, b.cover_image 
    FROM borrowing_history bh
    JOIN books b ON bh.book_id = b.id
    WHERE bh.user_id = ?
    ORDER BY bh.borrowed_at DESC LIMIT 5
");
$stmt->execute([$userId]);
$borrows = $stmt->fetchAll();

$cart = new Cart($pdo);
$orders = $cart->getUserOrders($userId, 5);

// Fetch Reservations
$stmt = $pdo->prepare("
    SELECT r.*, b.title, b.author, b.cover_image 
    FROM reservations r
    JOIN books b ON r.book_id = b.id
    WHERE r.user_id = ?
    ORDER BY r.reserved_at DESC LIMIT 5
");
$stmt->execute([$userId]);
$reservations = $stmt->fetchAll();

// Fetch Reviews
$stmt = $pdo->prepare("
    SELECT rev.*, b.title 
    FROM reviews rev
    JOIN books b ON rev.book_id = b.id
    WHERE rev.user_id = ?
    ORDER BY rev.created_at DESC LIMIT 5
");
$stmt->execute([$userId]);
$reviews = $stmt->fetchAll();

include 'views/header.php';
?>

<style>
/* ─── User Profile Premium ─── */
.ud-hero {
    position: relative; overflow: hidden;
    padding: 60px 0; border-bottom: 1px solid rgba(0,0,0,0.06);
    background:
        radial-gradient(ellipse at 85% 20%, rgba(224,122,95,0.08) 0%, transparent 60%),
        radial-gradient(ellipse at 10% 80%, rgba(129,178,154,0.05) 0%, transparent 50%),
        var(--bookhouse-bg, #FFF3F0);
}
[data-bs-theme="dark"] .ud-hero {
    background:
        radial-gradient(ellipse at 85% 20%, rgba(224,122,95,0.12) 0%, transparent 60%),
        radial-gradient(ellipse at 10% 80%, rgba(129,178,154,0.1) 0%, transparent 50%),
        #0f172a;
    border-bottom-color: rgba(255,255,255,0.06);
}
.ud-hero::before {
    content: ''; position: absolute; inset: 0;
    background-image:
        linear-gradient(rgba(0,0,0,0.02) 1px, transparent 1px),
        linear-gradient(90deg, rgba(0,0,0,0.02) 1px, transparent 1px);
    background-size: 60px 60px; pointer-events: none;
}
[data-bs-theme="dark"] .ud-hero::before {
    background-image:
        linear-gradient(rgba(255,255,255,0.025) 1px, transparent 1px),
        linear-gradient(90deg, rgba(255,255,255,0.025) 1px, transparent 1px);
}

.ud-profile-card {
    display: flex; align-items: center; gap: 30px;
    position: relative; z-index: 2;
}
.ud-avatar {
    width: 140px; height: 140px; border-radius: 50%;
    background: linear-gradient(135deg, var(--bookhouse-orange), #c2664e);
    color: #fff; display: flex; align-items: center; justify-content: center;
    font-size: 50px; font-weight: 800;
    box-shadow: 0 15px 35px rgba(224,122,95,0.25);
    border: 6px solid rgba(255,255,255,0.8);
    flex-shrink: 0;
}
[data-bs-theme="dark"] .ud-avatar { border-color: rgba(30,41,59,0.8); }
.ud-info h1 {
    font-family: 'Playfair Display', serif;
    font-weight: 800; font-size: clamp(2rem, 4vw, 3rem);
    color: var(--bookhouse-text); margin-bottom: 5px;
}
.ud-info p {
    font-size: 16px; color: var(--bookhouse-text-muted); margin-bottom: 20px;
    display: flex; align-items: center; gap: 8px;
}
.ud-badge {
    background: rgba(224,122,95,0.1); color: var(--bookhouse-orange);
    padding: 6px 16px; border-radius: 999px; font-size: 13px; font-weight: 700;
    display: inline-flex; align-items: center; gap: 6px; letter-spacing: 1px;
    text-transform: uppercase;
}

/* Stats Row */
.ud-stats-container {
    margin-top: -40px; position: relative; z-index: 10;
    margin-bottom: 50px;
}
.ud-stat-card {
    background: rgba(255,255,255,0.85); backdrop-filter: blur(12px);
    border: 1px solid rgba(0,0,0,0.05); padding: 24px;
    border-radius: 20px; text-align: center;
    box-shadow: 0 10px 30px rgba(0,0,0,0.04);
    transition: transform 0.3s;
}
.ud-stat-card:hover { transform: translateY(-5px); }
[data-bs-theme="dark"] .ud-stat-card {
    background: rgba(30,41,59,0.85); border-color: rgba(255,255,255,0.05);
}
.ud-stat-icon {
    width: 48px; height: 48px; border-radius: 12px; margin: 0 auto 14px;
    display: flex; align-items: center; justify-content: center; font-size: 20px;
}
.ud-stat-icon.orange { background: rgba(224,122,95,0.12); color: #E07A5F; }
.ud-stat-icon.mint { background: rgba(129,178,154,0.15); color: #81B29A; }
.ud-stat-icon.blue { background: rgba(59,130,246,0.12); color: #3b82f6; }
.ud-stat-icon.gold { background: rgba(242,204,143,0.2); color: #d4a646; }
.ud-stat-num { font-weight: 800; font-size: 28px; color: var(--bookhouse-text); line-height: 1; margin-bottom: 6px; }
.ud-stat-lbl { font-size: 12px; font-weight: 700; text-transform: uppercase; letter-spacing: 1px; color: var(--bookhouse-text-muted); }

/* Main Content Layout */
.ud-main { padding-bottom: 80px; }
.ud-section-title {
    font-family: 'Playfair Display', serif; font-weight: 800; font-size: 22px;
    color: var(--bookhouse-text); margin-bottom: 24px;
    display: flex; align-items: center; gap: 10px;
}
.ud-section-title i { color: var(--bookhouse-orange); font-size: 18px; }
.ud-card {
    background: #fff; border: 1px solid rgba(0,0,0,0.06);
    border-radius: 20px; padding: 24px; margin-bottom: 30px;
}
[data-bs-theme="dark"] .ud-card { background: #1e293b; border-color: rgba(255,255,255,0.06); }

/* List Items */
.ud-list-item {
    display: flex; align-items: center; gap: 16px;
    padding: 16px 0; border-bottom: 1px solid rgba(0,0,0,0.04);
}
.ud-list-item:last-child { border-bottom: none; padding-bottom: 0; }
.ud-list-item:first-child { padding-top: 0; }
[data-bs-theme="dark"] .ud-list-item { border-bottom-color: rgba(255,255,255,0.04); }

.ud-item-img {
    width: 60px; height: 80px; border-radius: 8px; flex-shrink: 0; object-fit: cover;
    background: #f0e6e0;
}
[data-bs-theme="dark"] .ud-item-img { background: #253141; }
.ud-item-info { flex: 1; min-width: 0; }
.ud-item-title { font-weight: 800; font-size: 15px; color: var(--bookhouse-text); margin-bottom: 4px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
.ud-item-meta { font-size: 13px; color: var(--bookhouse-text-muted); }

/* Status Badges */
.ud-status {
    padding: 4px 10px; border-radius: 6px; font-size: 11px; font-weight: 800;
    text-transform: uppercase; letter-spacing: 0.5px; white-space: nowrap;
}
.ud-status.pending { background: rgba(245,158,11,0.15); color: #f59e0b; }
.ud-status.processing { background: rgba(37,99,235,0.1); color: #2563eb; }
.ud-status.completed, .ud-status.fulfilled, .ud-status.returned { background: rgba(16,185,129,0.15); color: #10b981; }
.ud-status.active, .ud-status.waiting { background: rgba(59,130,246,0.15); color: #3b82f6; }
.ud-status.cancelled { background: rgba(239,68,68,0.15); color: #ef4444; }

/* Empty State */
.ud-empty {
    text-align: center; padding: 40px 20px;
    background: rgba(0,0,0,0.015); border-radius: 16px;
}
[data-bs-theme="dark"] .ud-empty { background: rgba(255,255,255,0.015); }
.ud-empty i { font-size: 32px; color: var(--bookhouse-text-muted); opacity: 0.5; margin-bottom: 12px; }
.ud-empty h6 { font-weight: 700; color: var(--bookhouse-text); margin-bottom: 6px; }
.ud-empty p { font-size: 14px; color: var(--bookhouse-text-muted); margin: 0; }

/* ─── Member Card System ─── */
.member-card-wrap {
    perspective: 1000px; margin-bottom: 40px;
}
.member-card {
    position: relative; width: 100%; max-width: 480px; height: 260px;
    border-radius: 24px; overflow: hidden;
    padding: 30px; display: flex; flex-direction: column;
    justify-content: space-between;
    box-shadow: 0 25px 50px -12px rgba(0,0,0,0.25);
    color: #fff; transform-style: preserve-3d;
    transition: transform 0.3s; margin: 0 auto;
}
.member-card:hover { transform: translateY(-5px) rotateX(2deg) rotateY(-2deg); }

.member-card.bronze   { background: linear-gradient(135deg, #cd7f32, #8b4513, #cd7f32); }
.member-card.silver   { background: linear-gradient(135deg, #bdc3c7, #2c3e50, #bdc3c7); }
.member-card.gold     { background: linear-gradient(135deg, #f1c40f, #f39c12, #f1c40f); }
.member-card.platinum { background: linear-gradient(135deg, #1e293b, #334155, #1e293b); }

.card-pattern {
    position: absolute; inset: 0; opacity: 0.15;
    background-image: url("data:image/svg+xml,%3Csvg width='60' height='60' viewBox='0 0 60 60' xmlns='http://www.w3.org/2000/svg'%3E%3Cg fill='none' fill-rule='evenodd'%3E%3Cg fill='%23ffffff' fill-opacity='0.4'%3E%3Cpath d='M36 34v-4h-2v4h-4v2h4v4h2v-4h4v-2h-4zm0-30V0h-2v4h-4v2h4v4h2V6h4V4h-4zM6 34v-4H4v4H0v2h4v4h2v-4h4v-2H6zM6 4V0H4v4H0v2h4v4h2V6h4V4H6z'/%3E%3C/g%3E%3C/g%3E%3C/svg%3E");
    pointer-events: none;
}
.card-glass-shine {
    position: absolute; top: -100%; left: -100%; width: 300%; height: 300%;
    background: linear-gradient(45deg, transparent 40%, rgba(255,255,255,0.15) 45%, rgba(255,255,255,0.25) 50%, rgba(255,255,255,0.15) 55%, transparent 60%);
    animation: shine 6s infinite linear; pointer-events: none;
}
@keyframes shine { 100% { transform: translate(30%, 30%); } }

.card-header { display: flex; justify-content: space-between; align-items: flex-start; z-index: 1; }
.card-logo { font-size: 24px; font-weight: 800; font-family: 'Playfair Display', serif; display: flex; align-items: center; gap: 10px; }
.card-chip { width: 45px; height: 35px; background: linear-gradient(135deg, #ffd700, #b8860bc2); border-radius: 6px; box-shadow: inset 0 0 5px rgba(0,0,0,0.2); position: relative; }
.card-chip::after { content: ''; position: absolute; inset: 6px; border: 1px solid rgba(0,0,0,0.1); border-radius: 2px; }

.card-body { position: relative; z-index: 1; margin: 20px 0; }
.card-number { font-family: 'Courier New', monospace; font-size: 22px; letter-spacing: 3px; text-shadow: 2px 2px 4px rgba(0,0,0,0.3); }

.card-footer { display: flex; justify-content: space-between; align-items: flex-end; z-index: 1; }
.card-user-info .lbl { font-size: 9px; text-transform: uppercase; letter-spacing: 1px; opacity: 0.8; margin-bottom: 2px; }
.card-user-info .val { font-size: 16px; font-weight: 700; text-transform: uppercase; }

.card-barcode { background: #fff; padding: 5px; border-radius: 4px; height: 40px; display: flex; align-items: center; }

@media (max-width: 767px) {
    .member-card { height: 220px; padding: 20px; }
    .card-number { font-size: 18px; }
    .ud-profile-card { flex-direction: column; text-align: center; gap: 20px; }
    .ud-info p { justify-content: center; }
    .ud-item-img { width: 50px; height: 68px; }
}
</style>

<!-- ═══════  HERO  ═══════ -->
<section class="ud-hero">
    <div class="container position-relative" style="z-index: 2;">
        <nav aria-label="breadcrumb" class="mb-4">
            <ol class="breadcrumb" style="font-size:13px;">
                <li class="breadcrumb-item"><a href="index.php" class="text-decoration-none text-muted">Home</a></li>
                <li class="breadcrumb-item active fw-bold" style="color:var(--bookhouse-orange);">My Profile</li>
            </ol>
        </nav>

        <div class="row align-items-center">
            <div class="col-lg-7 mb-4 mb-lg-0">
                <div class="ud-profile-card">
                    <div class="ud-avatar">
                        <?= strtoupper(substr($user['username'] ?? 'User', 0, 1)) ?>
                    </div>
                    <div class="ud-info">
                        <h1><?= e($user['username']) ?></h1>
                        <p><i class="fas fa-envelope text-muted"></i> <?= e($user['email']) ?></p>
                        <div class="ud-badge">
                            <i class="fas fa-shield-alt"></i> <?= strtoupper($user['role'] ?? 'USER') ?>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-lg-5">
                <div class="member-card-wrap">
                    <div class="member-card <?= e($user['membership_tier'] ?? 'bronze') ?>">
                        <div class="card-pattern"></div>
                        <div class="card-glass-shine"></div>
                        
                        <div class="card-header">
                            <div class="card-logo">
                                <i class="fas fa-book-reader"></i> Arctic Library
                            </div>
                            <div class="card-chip"></div>
                        </div>

                        <div class="card-body">
                            <div class="card-number"><?= e($user['membership_id'] ?? 'LIB-000000') ?></div>
                        </div>

                        <div class="card-footer">
                            <div class="card-user-info">
                                <div class="lbl">Member Name</div>
                                <div class="val"><?= e($user['username']) ?></div>
                            </div>
                            <div class="card-barcode">
                                <svg width="120" height="30" viewBox="0 0 120 30" xmlns="http://www.w3.org/2000/svg">
                                    <rect x="0" y="0" width="2" height="30" fill="#000" />
                                    <rect x="4" y="0" width="1" height="30" fill="#000" />
                                    <rect x="7" y="0" width="3" height="30" fill="#000" />
                                    <rect x="12" y="0" width="1" height="30" fill="#000" />
                                    <rect x="15" y="0" width="4" height="30" fill="#000" />
                                    <rect x="22" y="0" width="1" height="30" fill="#000" />
                                    <rect x="25" y="0" width="2" height="30" fill="#000" />
                                    <rect x="30" y="0" width="1" height="30" fill="#000" />
                                    <rect x="33" y="0" width="3" height="30" fill="#000" />
                                    <rect x="38" y="0" width="1" height="30" fill="#000" />
                                    <rect x="42" y="0" width="2" height="30" fill="#000" />
                                    <rect x="46" y="0" width="4" height="30" fill="#000" />
                                    <rect x="52" y="0" width="1" height="30" fill="#000" />
                                    <rect x="55" y="0" width="2" height="30" fill="#000" />
                                    <rect x="60" y="0" width="3" height="30" fill="#000" />
                                    <rect x="65" y="0" width="1" height="30" fill="#000" />
                                    <rect x="68" y="0" width="4" height="30" fill="#000" />
                                    <rect x="74" y="0" width="2" height="30" fill="#000" />
                                    <rect x="78" y="0" width="1" height="30" fill="#000" />
                                    <rect x="81" y="0" width="3" height="30" fill="#000" />
                                    <rect x="86" y="0" width="1" height="30" fill="#000" />
                                    <rect x="90" y="0" width="2" height="30" fill="#000" />
                                    <rect x="94" y="0" width="4" height="30" fill="#000" />
                                    <rect x="100" y="0" width="1" height="30" fill="#000" />
                                    <rect x="103" y="0" width="2" height="30" fill="#000" />
                                    <rect x="108" y="0" width="3" height="30" fill="#000" />
                                    <rect x="113" y="0" width="1" height="30" fill="#000" />
                                    <rect x="116" y="0" width="4" height="30" fill="#000" />
                                </svg>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- ═══════  STATS  ═══════ -->
<div class="container ud-stats-container">
    <div class="row g-3 g-lg-4">
        <div class="col-6 col-lg-3">
            <div class="ud-stat-card">
                <div class="ud-stat-icon orange"><i class="fas fa-book-open"></i></div>
                <div class="ud-stat-num"><?= number_format($totalBorrows) ?></div>
                <div class="ud-stat-lbl">Borrows</div>
            </div>
        </div>
        <div class="col-6 col-lg-3">
            <div class="ud-stat-card">
                <div class="ud-stat-icon mint"><i class="fas fa-shopping-bag"></i></div>
                <div class="ud-stat-num"><?= number_format($totalOrders) ?></div>
                <div class="ud-stat-lbl">Orders</div>
            </div>
        </div>
        <div class="col-6 col-lg-3">
            <div class="ud-stat-card">
                <div class="ud-stat-icon blue"><i class="fas fa-bookmark"></i></div>
                <div class="ud-stat-num"><?= number_format($totalReservations) ?></div>
                <div class="ud-stat-lbl">Reservations</div>
            </div>
        </div>
        <div class="col-6 col-lg-3">
            <div class="ud-stat-card">
                <div class="ud-stat-icon gold"><i class="fas fa-star"></i></div>
                <div class="ud-stat-num"><?= number_format($totalReviews) ?></div>
                <div class="ud-stat-lbl">Reviews</div>
            </div>
        </div>
    </div>
</div>

<!-- ═══════  MAIN CONTENT  ═══════ -->
<section class="ud-main">
    <div class="container">
        <div class="row g-4">
            
            <!-- Left Column -->
            <div class="col-lg-6">
                <!-- Borrowing History -->
                <h3 class="ud-section-title"><i class="fas fa-history"></i> Recent Borrows</h3>
                <div class="ud-card">
                    <?php if (empty($borrows)): ?>
                        <div class="ud-empty">
                            <i class="fas fa-book-reader"></i>
                            <h6>No borrows yet</h6>
                            <p>You haven't borrowed any physical books.</p>
                        </div>
                    <?php else: ?>
                        <?php foreach ($borrows as $borrow): 
                            $isReturned = !empty($borrow['returned_at']);
                            $isOverdue = !$isReturned && (strtotime($borrow['due_date']) < time());
                        ?>
                        <div class="ud-list-item">
                            <img src="<?= getBookCoverUrl((object)$borrow, $borrow['title'], $borrow['author']) ?>" 
                                 alt="Cover" class="ud-item-img"
                                 onerror="this.src='<?= getDummyBookCover($borrow['title'], $borrow['author'], 150, 200) ?>'">
                            <div class="ud-item-info">
                                <div class="ud-item-title"><?= e($borrow['title']) ?></div>
                                <div class="ud-item-meta">Borrowed: <?= date('M j, Y', strtotime($borrow['borrowed_at'])) ?></div>
                            </div>
                            <div class="text-end">
                                <?php if ($isReturned): ?>
                                    <span class="ud-status returned">Returned</span>
                                <?php elseif ($borrow['status'] === 'rejected'): ?>
                                    <span class="ud-status cancelled">Rejected</span>
                                <?php elseif ($isOverdue): ?>
                                    <span class="ud-status cancelled">Overdue</span>
                                <?php else: ?>
                                    <span class="ud-status active">Reading</span>
                                <?php endif; ?>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>

                <!-- Recent Orders -->
                <h3 class="ud-section-title" id="orders-section"><i class="fas fa-box-open"></i> Recent Orders</h3>
                <div class="ud-card">
                    <?php if (empty($orders)): ?>
                        <div class="ud-empty">
                            <i class="fas fa-shopping-cart"></i>
                            <h6>No orders yet</h6>
                            <p>You haven't placed any purchases.</p>
                        </div>
                    <?php else: ?>
                        <?php foreach ($orders as $order): ?>
                        <div class="ud-list-item">
                            <div class="ud-item-info">
                                <div class="ud-item-title"><a href="order-details.php?id=<?= e($order['order_number']) ?>" class="text-decoration-none" style="color:inherit;">Order #<?= e($order['order_number']) ?></a></div>
                                <div class="ud-item-meta"><?= date('M j, Y', strtotime($order['created_at'])) ?></div>
                            </div>
                            <div class="text-end">
                                <div style="font-weight:800; color:var(--bookhouse-text); margin-bottom:4px;">
                                    <?= number_format($order['total_amount']) ?> Ks
                                </div>
                                <div class="mt-1">
                                    <a href="order-details.php?id=<?= e($order['order_number']) ?>" style="font-size:11px; color:var(--bookhouse-orange); font-weight:700; text-decoration:none;">View Details</a>
                                </div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Right Column -->
            <div class="col-lg-6">
                <!-- Reservations -->
                <h3 class="ud-section-title"><i class="fas fa-bookmark"></i> My Reservations</h3>
                <div class="ud-card">
                    <?php if (empty($reservations)): ?>
                        <div class="ud-empty">
                            <i class="fas fa-clock"></i>
                            <h6>No reservations</h6>
                            <p>You aren't waiting for any books.</p>
                        </div>
                    <?php else: ?>
                        <?php foreach ($reservations as $res): ?>
                        <div class="ud-list-item">
                            <img src="<?= getBookCoverUrl((object)$res, $res['title'], $res['author']) ?>" 
                                 alt="Cover" class="ud-item-img"
                                 onerror="this.src='<?= getDummyBookCover($res['title'], $res['author'], 150, 200) ?>'">
                            <div class="ud-item-info">
                                <div class="ud-item-title"><?= e($res['title']) ?></div>
                                <div class="ud-item-meta">Reserved: <?= date('M j, Y', strtotime($res['reserved_at'])) ?></div>
                            </div>
                            <div class="text-end">
                                <span class="ud-status <?= strtolower($res['status']) ?>">
                                    <?= ucfirst($res['status']) ?>
                                </span>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>

                <!-- Recent Reviews -->
                <h3 class="ud-section-title"><i class="fas fa-star"></i> Recent Reviews</h3>
                <div class="ud-card">
                    <?php if (empty($reviews)): ?>
                        <div class="ud-empty">
                            <i class="fas fa-comment-dots"></i>
                            <h6>No reviews</h6>
                            <p>You haven't reviewed any books yet.</p>
                        </div>
                    <?php else: ?>
                        <?php foreach ($reviews as $rev): ?>
                        <div class="ud-list-item" style="align-items:flex-start;">
                            <div class="ud-item-info">
                                <div class="ud-item-title"><?= e($rev['title']) ?></div>
                                <div style="color:#f59e0b; font-size:12px; margin-bottom:6px;">
                                    <?php for($i=1; $i<=5; $i++): ?>
                                        <i class="fas fa-star <?= $i<=$rev['rating'] ? '' : 'text-muted opacity-50' ?>"></i>
                                    <?php endfor; ?>
                                </div>
                                <?php if($rev['review_text']): ?>
                                    <div style="font-size:13px; color:var(--bookhouse-text-muted); line-height:1.5; display:-webkit-box; -webkit-line-clamp:2; line-clamp:2; -webkit-box-orient:vertical; overflow:hidden;">
                                        "<?= e($rev['review_text']) ?>"
                                    </div>
                                <?php endif; ?>
                            </div>
                            <div class="ud-item-meta text-end" style="font-size:11px;">
                                <?= date('M j, Y', strtotime($rev['created_at'])) ?>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>

                <!-- Membership Benefits -->
                <h3 class="ud-section-title"><i class="fas fa-gem"></i> Tier Benefits</h3>
                <div class="ud-card" style="background: linear-gradient(135deg, rgba(224,122,95,0.05), rgba(129,178,154,0.05));">
                    <div class="d-flex align-items-center gap-3 mb-3">
                        <div class="ud-stat-icon gold m-0" style="width:40px; height:40px;"><i class="fas fa-crown"></i></div>
                        <div>
                            <h6 class="mb-0 fw-800 text-capitalize"><?= e($user['membership_tier'] ?? 'bronze') ?> Status</h6>
                            <p class="mb-0 small text-muted">Exclusive privileges for your tier</p>
                        </div>
                    </div>
                    
                    <ul class="list-unstyled mb-0" style="font-size:14px;">
                        <li class="mb-2 d-flex gap-2">
                            <i class="fas fa-check-circle text-success mt-1"></i>
                            <div>
                                <strong>Borrow Limit:</strong> 
                                <?php if(($user['membership_tier'] ?? 'bronze') === 'gold'): ?> 5 Books
                                <?php elseif(($user['membership_tier'] ?? 'bronze') === 'platinum'): ?> Unlimited
                                <?php else: ?> 3 Books
                                <?php endif; ?>
                            </div>
                        </li>
                        <li class="mb-2 d-flex gap-2">
                            <i class="fas fa-check-circle text-success mt-1"></i>
                            <div>
                                <strong>Duration:</strong> 14 Days
                                <?php if(($user['membership_tier'] ?? 'bronze') !== 'bronze'): ?> (Extendable)<?php endif; ?>
                            </div>
                        </li>
                        <li class="mb-2 d-flex gap-2">
                            <i class="fas fa-check-circle text-success mt-1"></i>
                            <div>
                                <strong>Shopping:</strong> 
                                <?php if(($user['membership_tier'] ?? 'bronze') === 'silver'): ?> 10% Discount
                                <?php elseif(($user['membership_tier'] ?? 'bronze') === 'gold'): ?> 20% Discount
                                <?php elseif(($user['membership_tier'] ?? 'bronze') === 'platinum'): ?> 25% Discount + Free Shipping
                                <?php else: ?> Standard Pricing
                                <?php endif; ?>
                            </div>
                        </li>
                        <li class="d-flex gap-2">
                            <i class="fas fa-check-circle text-success mt-1"></i>
                            <div>
                                <strong>Support:</strong> Standard
                                <?php if(($user['membership_tier'] ?? 'bronze') === 'platinum'): ?> (Priority)<?php endif; ?>
                            </div>
                        </li>
                    </ul>
                </div>

            </div>
        </div>
    </div>
</section>

<?php include 'views/footer.php'; ?>
