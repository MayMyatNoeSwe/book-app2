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

$lib = new \App\Library();
$pdo = $lib->getPdo();
$userId = Auth::id();

// Handle Membership Switching
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['switch_plan'])) {
    $subId = (int)$_POST['sub_id'];
    if ($lib->setActiveCard($userId, $subId)) {
        header("Location: user-details.php?success=plan_switched");
    } else {
        header("Location: user-details.php?error=switch_failed");
    }
    exit;
}

// Fetch user info
$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$userId]);
$user = $stmt->fetch();

$activeSubs = Auth::getSubscriptions();
$tier = strtolower($user['membership_tier'] ?? 'bronze');

$stmt = $pdo->prepare("SELECT COUNT(*) FROM borrowing_history WHERE user_id = ? AND status IN ('approved', 'returned', 'return_pending')");
$stmt->execute([$userId]);
$totalBorrows = $stmt->fetchColumn();

$stmt = $pdo->prepare("SELECT COUNT(*) FROM borrowing_history WHERE user_id = ? AND returned_at IS NOT NULL AND status = 'returned'");
$stmt->execute([$userId]);
$totalReturns = $stmt->fetchColumn();

$stmt = $pdo->prepare("SELECT SUM(penalty_fee) FROM borrowing_history WHERE user_id = ? AND status != 'rejected'");
$stmt->execute([$userId]);
$totalPenaltyAmount = $stmt->fetchColumn() ?: 0;

$stmt = $pdo->prepare("SELECT COUNT(*) FROM borrowing_history WHERE user_id = ? AND penalty_fee > 0 AND penalty_paid = 0");
$stmt->execute([$userId]);
$unpaidPenaltyCount = $stmt->fetchColumn();

// Keep a backup of individual stats for the personal activity row
$myBorrows = $totalBorrows;
$myReturns = $totalReturns;
$myPenaltyAmount = $totalPenaltyAmount;

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

// --- SHARING LOGIC ---
$lib = new \App\Library($pdo);

// Initialize Group Stats with defaults (Supports both standalone and group members)
$rules = $lib->getMembershipRules($userId);
$planLimit = $rules['limit'] ?? 0;
$groupPoolLimit = $rules['group_limit'] ?? 0;
$shareLimit = $rules['share_limit'] ?? 5;
$groupAggregate = [
    'active' => $user['active_subscription_id'] ? $lib->getGroupUsageCount($user['active_subscription_id']) : $myBorrows
];

// 1. Identify WHICH sub to manage in "Manage Family Shares" (Host Dashboard)
$activeSubId = $user['active_subscription_id'] ?? 0;
$possibleHostSubs = [];

foreach ($activeSubs as $sub) {
    if ($sub['is_host'] && in_array($sub['tier'], ['silver', 'gold', 'platinum'])) {
        $possibleHostSubs[] = $sub;
    }
}

// SORT by tier priority (Platinum > Gold > Silver)
$pri = ['platinum' => 3, 'gold' => 2, 'silver' => 1];
usort($possibleHostSubs, function($a, $b) use ($pri, $activeSubId) {
    if ($a['id'] == $activeSubId) return -1;
    if ($b['id'] == $activeSubId) return 1;
    return ($pri[$b['tier']] ?? 0) <=> ($pri[$a['tier']] ?? 0);
});

$isHost = false;
$hostSubId = null;
$hostSubTier = null;
$groupMembers = [];

if (!empty($possibleHostSubs)) {
    $isHost = true;
    $hostSubId = $possibleHostSubs[0]['id'];
    $hostSubTier = $possibleHostSubs[0]['tier'];
    
    // Override with host-specific group aggregates
    $totalBorrows = $lib->getGroupTotalBorrowsCount($hostSubId);
    $totalReturns = $lib->getGroupTotalReturnsCount($hostSubId);
    $totalPenaltyAmount = $lib->getGroupTotalPenaltyAmount($hostSubId);
    
    $groupAggregate['active'] = $lib->getGroupUsageCount($hostSubId);
    $groupMembers = $lib->getGroupMembers($hostSubId);

    unset($m);
}

// 2. Detect if user is a Shared Member (Guest on someone else's plan)
$isShared = false;
$parentSubId = null;
foreach ($activeSubs as $s) {
    if ($s['parent_id']) {
        $isShared = true;
        $parentSubId = $s['parent_id'];
        break;
    }
}

$pendingInvitesForMe = $lib->getPendingInvitationsForUser($user['email']);
$sentInvites = $hostSubId ? $lib->getSentInvitations($hostSubId) : [];

include 'views/header.php';
?>

<style>
/* ─── User Profile Premium ─── */
.ud-hero {
    position: relative; overflow: hidden;
    padding: 80px 0 60px; border-bottom: 1px solid rgba(0,0,0,0.05);
    background: #fff;
}
[data-bs-theme="dark"] .ud-hero {
    background: #0f172a; border-bottom-color: rgba(255,255,255,0.05);
}
.ud-hero-bg {
    position: absolute; inset: 0; pointer-events: none;
    background-image: 
        radial-gradient(circle at 80% 20%, rgba(224,122,95,0.05), transparent 40%),
        radial-gradient(circle at 20% 80%, rgba(139,92,246,0.05), transparent 40%);
}
.ud-grid {
    position: absolute; inset: 0; opacity: 0.4;
    background-image: linear-gradient(#f1f5f9 1px, transparent 1px), linear-gradient(90deg, #f1f5f9 1px, transparent 1px);
    background-size: 40px 40px; pointer-events: none;
}
[data-bs-theme="dark"] .ud-grid {
    background-image: linear-gradient(rgba(255,255,255,0.03) 1px, transparent 1px), linear-gradient(90deg, rgba(255,255,255,0.03) 1px, transparent 1px);
}
}

.ud-profile-card {
    display: flex; align-items: center; gap: 30px;
    position: relative; z-index: 2;
}
.ud-avatar {
    width: 140px; height: 140px; border-radius: 50%;
    background: linear-gradient(135deg, #d48b71, #be6e56);
    color: #fff; display: flex; align-items: center; justify-content: center;
    font-size: 56px; font-weight: 800;
    box-shadow: 0 20px 40px rgba(212,139,113,0.3);
    border: 8px solid #fff; flex-shrink: 0;
}
[data-bs-theme="dark"] .ud-avatar { border-color: #1e293b; }

.ud-info h1 {
    font-family: 'Playfair Display', serif;
    font-weight: 800; font-size: 52px;
    color: #1e293b; margin-bottom: 8px; letter-spacing: -0.5px;
}
[data-bs-theme="dark"] .ud-info h1 { color: #f8fafc; }

.ud-info p {
    font-size: 18px; color: #64748b; margin-bottom: 24px;
    display: flex; align-items: center; gap: 10px; font-weight: 500;
}

.ud-badge {
    padding: 8px 20px; border-radius: 999px; font-size: 12px; font-weight: 800;
    text-transform: uppercase; letter-spacing: 1.5px;
    display: inline-flex; align-items: center; gap: 8px;
    box-shadow: 0 4px 10px rgba(0,0,0,0.03);
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
    position: relative; width: 100%; max-width: 400px; height: 250px;
    border-radius: 24px; padding: 30px; color: #fff; overflow: hidden;
    box-shadow: 0 20px 40px rgba(0,0,0,0.15); display: flex; flex-direction: column;
    justify-content: space-between; transition: transform 0.3s; margin: 0 auto;
}
.member-card:hover { transform: translateY(-5px) rotateX(2deg) rotateY(-2deg); }

.member-card.bronze   { background: linear-gradient(135deg, #a87932, #5c3b1e); }
.member-card.silver   { background: linear-gradient(135deg, #4b5563, #1e293b); }
.member-card.gold     { background: linear-gradient(135deg, #ca8a04, #713f12); }
.member-card.platinum { background: linear-gradient(135deg, #111827, #374151); }

.card-pattern {
    position: absolute; inset: 0; opacity: 0.1;
    background-image: url("data:image/svg+xml,%3Csvg width='24' height='24' viewBox='0 0 24 24' xmlns='http://www.w3.org/2000/svg'%3E%3Cpath d='M12 1L13 2H11L12 1ZM1 12L2 13V11L1 12ZM23 12L22 13V11L23 12ZM12 23L13 22H11L12 23Z' fill='%23ffffff'/%3E%3C/g%3E%3C/svg%3E");
    pointer-events: none;
}
.card-glass-shine {
    position: absolute; top: -100%; left: -100%; width: 300%; height: 300%;
    background: linear-gradient(45deg, transparent 40%, rgba(255,255,255,0.05) 45%, rgba(255,255,255,0.15) 50%, rgba(255,255,255,0.05) 55%, transparent 60%);
    animation: shine 8s infinite linear; pointer-events: none;
}

.card-header { display: flex; justify-content: space-between; align-items: center; z-index: 2; }
.card-logo { 
    font-size: 18px; font-weight: 800; letter-spacing: 0.5px;
    display: flex; align-items: center; gap: 8px; opacity: 0.9;
}
.card-chip { 
    width: 42px; height: 32px; background: linear-gradient(135deg, #e5e7eb, #9ca3af); 
    border-radius: 8px; position: relative; opacity: 1;
}
.card-chip::after { 
    content: ''; position: absolute; inset: 6px; 
    border: 1px solid rgba(0,0,0,0.1); border-radius: 4px; 
}

.card-body { position: relative; z-index: 2; margin: 40px 0; }
.card-number { 
    font-family: 'Space Mono', monospace; font-size: 24px; 
    letter-spacing: 4px; text-shadow: 0 2px 4px rgba(0,0,0,0.2);
    opacity: 0.95;
}

.card-footer { display: flex; justify-content: space-between; align-items: flex-end; z-index: 2; }
.card-user-info .lbl { font-size: 8px; text-transform: uppercase; letter-spacing: 1.5px; opacity: 0.6; margin-bottom: 4px; }
.card-user-info .val { font-size: 14px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.5px; }

.card-barcode { 
    background: rgba(255,255,255,0.9); padding: 4px; border-radius: 4px; 
    height: 36px; display: flex; align-items: center; mix-blend-mode: screen;
}

@media (max-width: 767px) {
    .member-card { height: 220px; padding: 20px; }
    .card-number { font-size: 18px; }
    .ud-profile-card { flex-direction: column; text-align: center; gap: 20px; }
    .ud-info p { justify-content: center; }
    .ud-item-img { width: 50px; height: 68px; }
}

.transition-all { transition: all 0.3s ease; }
.cursor-pointer { cursor: pointer; }
.hover-bg-white:hover { background-color: #fff !important; }
.collapse-chevron { transition: transform 0.3s ease; }
[aria-expanded="true"] .collapse-chevron { transform: rotate(180deg); }
.shadow-soft { box-shadow: 0 4px 15px rgba(0,0,0,0.05); }
.op-50 { opacity: 0.5; }
.op-30 { opacity: 0.3; }

.grayscale {
    filter: grayscale(100%);
    opacity: 0.6;
}
.transition-all {
    transition: all 0.2s ease-in-out;
}
</style>

<!-- ═══════  HERO  ═══════ -->
<section class="ud-hero">
    <div class="ud-hero-bg"></div>
    <div class="ud-grid"></div>
    <div class="container position-relative" style="z-index: 2;">
        <nav aria-label="breadcrumb" class="mb-4">
            <ol class="breadcrumb" style="font-size:13px;">
                <li class="breadcrumb-item"><a href="index.php" class="text-decoration-none text-muted">Home</a></li>
                <li class="breadcrumb-item active fw-bold" style="color:var(--bookhouse-orange);">My Profile</li>
            </ol>
        </nav>

        <div class="row align-items-center justify-content-between">
            <!-- Left: Profile Info -->
            <div class="col-lg-6 mb-5 mb-lg-0">
                <div class="d-flex align-items-center gap-4">
                    <div class="ud-avatar">
                        <?= strtoupper(substr($user['username'] ?? 'User', 0, 1)) ?>
                    </div>
                    <div class="ud-info">
                        <h1><?= e($user['username']) ?></h1>
                        <p class="mb-3"><i class="far fa-envelope"></i> <?= e($user['email']) ?></p>
                        <div class="d-flex flex-wrap gap-2">
                            <?php if ($isShared): ?>
                                <div class="ud-badge" style="background: rgba(139,92,246,0.1); color: #7c3aed;">
                                    <i class="fas fa-user-friends"></i> Shared <?= ucfirst($tier) ?>
                                </div>
                            <?php else: ?>
                                <div class="ud-badge">
                                    <i class="fas fa-crown"></i> <?= ucfirst($tier) ?> Member
                                </div>
                            <?php endif; ?>
                            <div class="ud-badge" style="background: rgba(59,130,246,0.1); color: #3b82f6;">
                                <i class="fas fa-id-card"></i> <?= e($user['membership_id'] ?? '#N/A') ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Right: Membership Card -->
            <div class="col-lg-5">
                <div class="member-card-perspective">
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
                                <svg width="100" height="24" viewBox="0 0 100 24" xmlns="http://www.w3.org/2000/svg">
                                    <?php for($i=0; $i<100; $i+=3): ?>
                                        <rect x="<?= $i ?>" y="0" width="<?= rand(1,2) ?>" height="24" fill="#000" />
                                    <?php endfor; ?>
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
<div class="container ud-stats-container mb-5">
    <!-- Active Membership Switching (Purchased Plans) -->
    <?php if (!empty($activeSubs) && !$isShared): ?>
        <div class="mb-4 animate__animated animate__fadeInUp">
            <div class="d-flex align-items-center gap-2 mb-3">
                <h6 class="smallest text-uppercase fw-900 text-muted ls-2 mb-0">Switch Active Card</h6>
                <div class="flex-grow-1 border-bottom opacity-10"></div>
            </div>
            <div class="d-flex flex-wrap gap-2">
                <?php 
                $activeCardId = $user['active_subscription_id'];
                foreach ($activeSubs as $sub): 
                    $isActive = ($activeCardId == $sub['id']);
                ?>
                    <form method="POST" style="display:inline;">
                        <input type="hidden" name="switch_plan" value="1">
                        <input type="hidden" name="sub_id" value="<?= $sub['id'] ?>">
                        <button type="submit" class="p-2 px-3 rounded-pill bg-white border d-flex align-items-center gap-2 shadow-sm transition-all <?= $isActive ? 'border-primary' : 'opacity-75 grayscale' ?>" style="font-size: 11px; outline: none; <?= $isActive ? 'border-width: 2px; box-shadow: 0 4px 12px rgba(78, 115, 223, 0.15) !important;' : '' ?>">
                            <div class="tier-dot" style="width: 10px; height: 10px; border-radius: 50%; background: <?= $isActive ? 'var(--bookhouse-orange)' : '#94a3b8' ?>;"></div>
                            <span class="fw-800 text-uppercase <?= $isActive ? 'text-dark' : 'text-muted' ?>"><?= e($sub['tier']) ?> Member</span>
                            <?php if ($isActive): ?>
                                <span class="badge bg-soft-primary text-primary border-0 ms-1" style="font-size: 9px;">CURRENTLY ACTIVE</span>
                            <?php else: ?>
                                <span class="text-muted" style="font-size: 9px;">• Click to Activate</span>
                            <?php endif; ?>
                        </button>
                    </form>
                <?php endforeach; ?>
            </div>
        </div>
    <?php endif; ?>

    <!-- Row 1: Family Group Activity (ONLY FOR HOST) -->
    <?php if ($isHost): ?>
    <div class="ud-stats-container animate__animated animate__fadeInUp">
        <div class="d-flex align-items-center gap-2 mb-3">
            <h6 class="smallest text-uppercase fw-900 text-muted ls-2 mb-0">Family Group Activity</h6>
            <div class="flex-grow-1 border-bottom opacity-10"></div>
        </div>
        <div class="row g-3 g-lg-4 row-cols-2 row-cols-md-3 row-cols-lg-6">
            <!-- Borrows -->
            <div class="col">
                <div class="ud-stat-card border-top border-orange border-4 shadow-sm h-100 bg-white shadow-soft">
                    <div class="ud-stat-icon orange mb-2"><i class="fas fa-layer-group"></i></div>
                    <div class="ud-stat-num mb-1"><?= number_format($totalBorrows) ?></div>
                    <div class="ud-stat-lbl text-muted fw-800 smaller">Group Total Borrows</div>
                </div>
            </div>
            <!-- Returns -->
            <div class="col">
                <div class="ud-stat-card border-top border-mint border-4 shadow-sm h-100 bg-white shadow-soft">
                    <div class="ud-stat-icon mint mb-2"><i class="fas fa-history"></i></div>
                    <div class="ud-stat-num mb-1"><?= number_format($totalReturns) ?></div>
                    <div class="ud-stat-lbl text-muted fw-800 smaller">Group Total Returns</div>
                </div>
            </div>
            <!-- Capacity -->
            <div class="col">
                <div class="ud-stat-card border-top border-blue border-4 shadow-sm h-100 bg-white shadow-soft">
                    <div class="ud-stat-icon blue mb-2"><i class="fas fa-book-reader"></i></div>
                    <div class="ud-stat-num mb-1"><?= $groupAggregate['active'] ?></div>
                    <div class="ud-stat-lbl text-muted fw-800 smaller">Books At Home</div>
                </div>
            </div>
            <!-- Shared Pool Quota -->
            <div class="col">
                <div class="ud-stat-card border-top border-blue border-4 shadow-sm h-100 bg-white shadow-soft">
                    <div class="ud-stat-icon blue mb-2"><i class="fas fa-layer-group"></i></div>
                    <div class="ud-stat-num mb-1"><?= $groupPoolLimit ?></div>
                    <div class="ud-stat-lbl text-muted fw-800 smaller">Group Pool Limit</div>
                </div>
            </div>
            <!-- Personal Limit -->
            <div class="col">
                <div class="ud-stat-card border-top border-warning border-4 shadow-sm h-100 bg-white shadow-soft">
                    <div class="ud-stat-icon gold mb-2"><i class="fas fa-user-lock"></i></div>
                    <div class="ud-stat-num mb-1"><?= $planLimit ?></div>
                    <div class="ud-stat-lbl text-muted fw-800 smaller">Your Active Limit</div>
                </div>
            </div>
            <!-- Fine -->
            <div class="col">
                <div class="ud-stat-card border-top border-danger border-4 shadow-sm h-100 bg-white shadow-soft">
                    <div class="ud-stat-icon hex-danger mb-2" style="background:rgba(220,53,69,0.1); color:#dc3545;"><i class="fas fa-exclamation-triangle"></i></div>
                    <div class="ud-stat-num mb-1"><?= number_format($totalPenaltyAmount) ?></div>
                    <div class="ud-stat-lbl text-muted fw-800 smaller">Group Total Fines</div>
                </div>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <!-- Row 2: Personal Activity -->
    <div class="animate__animated animate__fadeInUp" style="animation-delay: 0.1s;">
        <div class="d-flex align-items-center gap-2 mb-3">
            <h6 class="smallest text-uppercase fw-900 text-muted ls-2 mb-0">My Personal Activity</h6>
            <div class="flex-grow-1 border-bottom opacity-10"></div>
        </div>
        <div class="row g-3 g-lg-4">
            <div class="col-4 col-lg-2">
                <div class="ud-stat-card border-top border-orange border-4 shadow-sm h-100 bg-white-50">
                    <div class="ud-stat-icon orange mb-2"><i class="fas fa-book-open text-opacity-75"></i></div>
                    <div class="ud-stat-num mb-1"><?= number_format($myBorrows) ?></div>
                    <div class="ud-stat-lbl text-muted fw-700">My Borrows</div>
                </div>
            </div>
            <div class="col-4 col-lg-2">
                <div class="ud-stat-card border-top border-mint border-4 shadow-sm h-100 bg-white-50">
                    <div class="ud-stat-icon mint mb-2"><i class="fas fa-undo text-opacity-75"></i></div>
                    <div class="ud-stat-num mb-1"><?= number_format($myReturns) ?></div>
                    <div class="ud-stat-lbl text-muted fw-700">My Returns</div>
                </div>
            </div>
            <div class="col-4 col-lg-2">
                <div class="ud-stat-card border-top border-danger border-4 shadow-sm h-100 bg-white-50">
                    <div class="ud-stat-icon gold mb-2"><i class="fas fa-exclamation-triangle text-opacity-75"></i></div>
                    <div class="ud-stat-num mb-1"><?= number_format($myPenaltyAmount) ?></div>
                    <div class="ud-stat-lbl text-muted fw-700">My Fine</div>
                </div>
            </div>
            <div class="col-4 col-lg-2">
                <div class="ud-stat-card border-top border-primary border-4 shadow-sm h-100 bg-white-50">
                    <div class="ud-stat-icon blue mb-2"><i class="fas fa-user-tag text-opacity-75"></i></div>
                    <div class="ud-stat-num mb-1"><?= number_format($totalOrders) ?></div>
                    <div class="ud-stat-lbl text-muted fw-700">Orders</div>
                </div>
            </div>
            <div class="col-4 col-lg-2">
                <div class="ud-stat-card border-top border-primary border-4 shadow-sm h-100 bg-white-50">
                    <div class="ud-stat-icon blue mb-2"><i class="fas fa-bookmark text-opacity-75"></i></div>
                    <div class="ud-stat-num mb-1"><?= number_format($totalReservations) ?></div>
                    <div class="ud-stat-lbl text-muted fw-700">Reservations</div>
                </div>
            </div>
            <div class="col-4 col-lg-2">
                <div class="ud-stat-card border-top border-primary border-4 shadow-sm h-100 bg-white-50">
                    <div class="ud-stat-icon gold mb-2"><i class="fas fa-star text-opacity-75"></i></div>
                    <div class="ud-stat-num mb-1"><?= number_format($totalReviews) ?></div>
                    <div class="ud-stat-lbl text-muted fw-700">Reviews</div>
                </div>
            </div>
            <?php if ($isShared): ?>
            <div class="col-4 col-lg-2">
                <div class="ud-stat-card border-top border-warning border-4 shadow-sm h-100 bg-white-50">
                    <div class="ud-stat-icon gold mb-2"><i class="fas fa-user-shield text-opacity-75"></i></div>
                    <div class="ud-stat-num mb-1"><?= $planLimit ?></div>
                    <div class="ud-stat-lbl text-muted fw-700">Quota Limit</div>
                </div>
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php if ($isHost): ?>
<!-- ═══════  FAMILY GROUP DASHBOARD  ═══════ -->
<section class="mb-5 animate__animated animate__fadeIn">
    <div class="container">
        <h3 class="ud-section-title"><i class="fas fa-users"></i> Family Group Dashboard</h3>
        <div class="ud-card border-0 shadow-sm overflow-hidden p-0" id="family_sharing_dashboard" style="background: rgba(255,255,255,0.4); backdrop-filter: blur(20px);">
            <div class="row g-0">
                <!-- Share Member Management -->
                <div class="col-lg-5 border-end p-4 p-xl-5 bg-white">
                    <div class="mb-4">
                        <h5 class="fw-800 mb-1">Manage Members</h5>
                        <p class="text-muted smaller">Add or review your shared account activities. (<?= count($groupMembers) ?>/<?= $shareLimit ?> Slots Filled)</p>
                    </div>

                    <!-- Invite Form View -->
                    <form action="api/membership_invitations.php" method="POST" class="mb-5">
                        <input type="hidden" name="action" value="send">
                        <input type="hidden" name="sub_id" value="<?= $hostSubId ?>">
                        <div class="p-3 rounded-4 border bg-light d-flex gap-2">
                           <input type="email" name="email" class="form-control border-0 bg-transparent shadow-none" placeholder="Enter family email..." required>
                           <button type="submit" class="btn btn-dark rounded-pill px-4 fw-800 smaller">Invite</button>
                        </div>
                    </form>

                    <div class="mb-3 d-flex justify-content-between align-items-center">
                        <h6 class="smallest text-uppercase fw-800 text-muted opacity-75 letter-spacing-1">Group Members</h6>
                    </div>

                    <?php if (empty($groupMembers)): ?>
                        <div class="ud-empty py-5 border dashed rounded-4">
                            <i class="fas fa-user-plus d-block mb-3 opacity-50"></i>
                            <h6 class="fw-800">No members yet</h6>
                            <p class="small text-muted">Invite someone to start sharing benefits.</p>
                        </div>
                    <?php else: ?>
                        <?php foreach ($groupMembers as $idx => $m): ?>
                            <?php $collapseId = "page_memberBorrows_" . $idx; ?>
                            <div class="bg-light rounded-4 mb-3 border overflow-hidden">
                                <div class="p-3 d-flex justify-content-between align-items-center cursor-pointer hover-bg-white transition-all shadow-hover" 
                                     data-bs-toggle="collapse" 
                                     data-bs-target="#<?= $collapseId ?>" 
                                     style="cursor: pointer; user-select: none;">
                                    <div class="d-flex align-items-center gap-3">
                                        <div class="p-2 bg-white rounded-circle border d-flex align-items-center justify-content-center shadow-soft" style="width: 42px; height: 42px; flex-shrink: 0;">
                                            <i class="fas fa-user-circle text-muted fs-4"></i>
                                        </div>
                                        <div class="overflow-hidden">
                                            <div class="d-flex align-items-center gap-2 mb-0">
                                                <span class="fw-800 small text-dark text-truncate"><?= e($m['username']) ?></span>
                                                <span class="badge bg-soft-success text-success smallest fw-900 border-0" style="font-size: 8px; padding: 2px 6px;">ACTIVE</span>
                                            </div>
                                            <div class="smallest text-muted text-truncate"><?= e($m['email']) ?></div>
                                        </div>
                                    </div>
                                    
                                    <div class="d-flex align-items-center gap-3">
                                        <!-- New Compact Limit Pill -->
                                        <div class="d-flex align-items-center bg-white border rounded-pill shadow-soft transition-all cursor-pointer hover-bg-light" 
                                             style="height: 34px; overflow: hidden;"
                                             onclick="event.stopPropagation(); editMemberLimit(<?= $m['sub_id'] ?>, <?= $m['custom_borrow_limit'] ?? 0 ?>, '<?= addslashes($m['username']) ?>')">
                                            <div class="h-100 px-2 d-flex align-items-center bg-light border-end">
                                                <i class="fas fa-sliders-h text-muted" style="font-size: 10px;"></i>
                                            </div>
                                            <div class="px-3 fw-800 text-dark" style="font-size: 11px;">
                                                Limit: <span class="text-primary"><?= $m['custom_borrow_limit'] ?? 'Auto' ?></span>
                                            </div>
                                        </div>
                                        
                                        <i class="fas fa-chevron-down smallest text-muted collapse-chevron op-30 ms-1"></i>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>

                    <!-- Sent Invites (Small) -->
                    <?php if (!empty($sentInvites)): ?>
                        <div class="mt-5">
                            <h6 class="smallest text-uppercase fw-800 text-muted opacity-75 mb-3">Pending Invites</h6>
                            <div class="d-flex flex-column gap-2">
                                <?php foreach ($sentInvites as $si): ?>
                                    <div class="p-2 px-3 bg-light border rounded-pill d-flex justify-content-between align-items-center">
                                        <span class="smallest fw-800 text-dark"><?= e($si['email']) ?></span>
                                        <span class="smallest text-muted italic">Sent <?= date('M d', strtotime($si['created_at'])) ?></span>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- Live Feed / Activity Viewer -->
                <div class="col-lg-7 p-4 p-xl-5" style="background: rgba(248, 250, 252, 0.5);">
                    <div class="mb-4">
                        <h5 class="fw-800 mb-1">Group Activity Logs</h5>
                        <p class="text-muted smaller">Expand members to see their detailed browsing and reading history.</p>
                    </div>

                    <?php if (empty($groupMembers)): ?>
                        <div class="h-100 d-flex flex-column align-items-center justify-content-center py-5 opacity-25">
                            <i class="fas fa-stream fs-1 mb-3"></i>
                            <p class="fw-800">Logs will appear here</p>
                        </div>
                    <?php else: ?>
                        <?php foreach ($groupMembers as $idx => $m): ?>
                            <?php $collapseId = "page_memberBorrows_" . $idx; ?>
                            <div class="collapse <?= $idx === 0 ? 'show' : '' ?> member-act-log" id="<?= $collapseId ?>" data-bs-parent="#family_sharing_dashboard">
                                <div class="member-header-context d-flex align-items-center gap-3 mb-4 p-3 bg-white border rounded-4 shadow-sm">
                                    <div class="p-2 bg-orange text-white rounded-3 shadow-orange" style="width:40px; height:40px; display:flex; align-items:center; justify-content:center;">
                                       <i class="fas fa-user-check"></i>
                                    </div>
                                    <div>
                                        <h6 class="mb-0 fw-900"><?= e($m['username']) ?>'s Timeline</h6>
                                        <span class="smallest text-muted fw-700">Member ID: <?= e($m['membership_id'] ?? 'N/A') ?></span>
                                    </div>
                                    <div class="ms-auto d-flex gap-2">
                                        <div class="p-2 border rounded-3 bg-light text-center" style="min-width: 60px;">
                                            <div class="smallest fw-800 text-opacity-50">BORROWS</div>
                                            <div class="fw-900 small"><?= $m['stats']['total_b'] ?></div>
                                        </div>
                                        <div class="p-2 border rounded-3 bg-light text-center" style="min-width: 60px;">
                                            <div class="smallest fw-800 text-opacity-50">FINES</div>
                                            <div class="fw-900 small text-danger"><?= number_format($m['stats']['total_p'] ?? 0) ?></div>
                                        </div>
                                    </div>
                                </div>

                                <div class="activities-scroll-box" style="display: flex; flex-direction: column; gap: 12px;">
                                    <?php 
                                    $allActivities = array_merge(
                                        array_map(function($i){ $i['act_type'] = 'active'; return $i; }, $m['borrows']),
                                        array_map(function($i){ $i['act_type'] = 'past'; return $i; }, $m['history'])
                                    );
                                    usort($allActivities, function($a, $b){ return strtotime($b['borrowed_at']) - strtotime($a['borrowed_at']); });
                                    
                                    if (!empty($allActivities)): 
                                        foreach (array_slice($allActivities, 0, 10) as $b): ?>
                                            <div class="p-3 bg-white rounded-4 border d-flex align-items-center gap-3 hover-shadow-sm transition-all">
                                                <img src="<?= getBookCoverUrl((object)$b, $b['title'], $b['author']) ?>" 
                                                     class="rounded-3 shadow-sm" style="width:40px; height:60px; object-fit:cover;"
                                                     onerror="this.src='<?= getDummyBookCover($b['title'], $b['author'], 80, 120) ?>'">
                                                <div class="flex-grow-1 overflow-hidden">
                                                    <div class="fw-800 text-dark text-truncate mb-1"><?= e($b['title']) ?></div>
                                                    <div class="smallest d-flex align-items-center gap-2">
                                                        <?php if($b['act_type'] == 'active'): ?>
                                                            <?php if ($b['status'] === 'pending'): ?>
                                                                <span class="badge bg-soft-warning text-warning rounded-pill smaller fw-900"><i class="fas fa-hourglass-half fs-10 me-1"></i> PENDING</span>
                                                            <?php elseif ($b['status'] === 'return_pending'): ?>
                                                                <span class="badge bg-soft-info text-info rounded-pill smaller fw-900"><i class="fas fa-sync fs-10 me-1"></i> RETURNING</span>
                                                            <?php else: ?>
                                                                <span class="badge bg-soft-primary text-primary rounded-pill smaller fw-900"><i class="fas fa-clock fs-10 me-1"></i> READING</span>
                                                            <?php endif; ?>
                                                            <span class="text-muted fw-700">Due: <?= date('M d', strtotime($b['due_date'])) ?></span>
                                                        <?php else: ?>
                                                            <span class="badge bg-soft-success text-success rounded-pill smaller fw-900"><i class="fas fa-check-double fs-10 me-1"></i> RETURNED</span>
                                                            <span class="text-muted fw-700"><?= date('M d, Y', strtotime($b['returned_at'])) ?></span>
                                                        <?php endif; ?>
                                                    </div>
                                                </div>
                                                <?php if(($b['penalty_fee'] ?? 0) > 0): ?>
                                                    <div class="text-end">
                                                        <div class="smallest fw-900 text-danger"><?= number_format($b['penalty_fee']) ?> Ks</div>
                                                        <div class="smallest text-muted opacity-50" style="font-size:8px;">FINE</div>
                                                    </div>
                                                <?php endif; ?>
                                            </div>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <div class="text-center py-5 text-muted italic small opacity-50">No activity logged for this member.</div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</section>
<?php endif; ?>

<?php if (!empty($pendingInvitesForMe)): ?>
<section class="mb-5 animate__animated animate__fadeIn">
    <div class="container text-start">
        <?php foreach ($pendingInvitesForMe as $inv): ?>
            <div class="alert alert-info border-0 shadow-sm rounded-4 d-flex justify-content-between align-items-center p-4">
                <div class="d-flex align-items-center">
                    <div class="ud-stat-icon gold rounded-circle me-3" style="flex-shrink:0;"><i class="fas fa-envelope-open-text"></i></div>
                    <div>
                        <h6 class="mb-0 fw-800">Membership Invitation!</h6>
                        <span class="text-muted small"><strong><?= e($inv['host_username']) ?></strong> is inviting you to join their Family Group. Enjoy premium benefits for free!</span>
                    </div>
                </div>
                <form action="api/membership_invitations.php" method="POST" class="ms-3">
                    <input type="hidden" name="action" value="accept">
                    <input type="hidden" name="token" value="<?= e($inv['token']) ?>">
                    <button type="submit" class="btn btn-primary rounded-pill px-4 fw-bold shadow-sm">Accept Invite</button>
                </form>
            </div>
        <?php endforeach; ?>
    </div>
</section>
<?php endif; ?>

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
                                <?php elseif ($borrow['status'] === 'pending'): ?>
                                    <span class="ud-status pending">Pending</span>
                                <?php elseif ($borrow['status'] === 'return_pending'): ?>
                                    <span class="ud-status processing">Processing</span>
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
                <div class="ud-card mb-4">
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

                <!-- Penalties Section -->
                <h3 class="ud-section-title"><i class="fas fa-exclamation-circle"></i> Penalties & Fines</h3>
                <div class="ud-card">
                    <?php 
                    $stmt = $pdo->prepare("SELECT bh.*, b.title FROM borrowing_history bh JOIN books b ON bh.book_id = b.id WHERE bh.user_id = ? AND bh.penalty_fee > 0 ORDER BY bh.borrowed_at DESC LIMIT 5");
                    $stmt->execute([$userId]);
                    $penalties = $stmt->fetchAll();
                    
                    if (empty($penalties)): ?>
                        <div class="ud-empty">
                            <i class="fas fa-check-circle"></i>
                            <h6>No penalties</h6>
                            <p>Your library record is perfectly clean.</p>
                        </div>
                    <?php else: ?>
                        <?php foreach ($penalties as $p): ?>
                            <div class="ud-list-item">
                                <div class="ud-item-info">
                                    <div class="ud-item-title"><?= e($p['title']) ?></div>
                                    <div class="ud-item-meta">Amount: <span class="text-danger fw-bold"><?= number_format($p['penalty_fee']) ?> Ks</span></div>
                                </div>
                                <div class="text-end">
                                    <?php if ($p['penalty_paid']): ?>
                                        <span class="ud-status returned">Paid</span>
                                    <?php else: ?>
                                        <span class="ud-status cancelled">Unpaid</span>
                                        <div class="mt-1"><a href="borrow.php" class="smallest text-primary fw-bold text-decoration-none">How to Pay?</a></div>
                                    <?php endif; ?>
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
                                <?php 
                                    echo $planLimit . ' Active Books'; 
                                ?>
                                <?php if ($rules['is_custom'] ?? false): ?>
                                    <span class="badge bg-soft-warning text-warning smaller ms-1">Custom Limit</span>
                                <?php endif; ?>
                                <?php if ($groupPoolLimit > 0 && ($user['active_subscription_id'] ?? 0) > 0): ?>
                                    <div class="text-muted" style="font-size:11px; margin-top:2px;">
                                        <i class="fas fa-layer-group me-1"></i> Family Pool: <?= $groupPoolLimit ?> Books
                                    </div>
                                <?php endif; ?>
                            </div>
                        </li>
                        <li class="mb-2 d-flex gap-2">
                            <i class="fas fa-check-circle text-success mt-1"></i>
                            <div>
                                <strong>Duration:</strong> 
                                <?= getSetting(strtolower($user['membership_tier'] ?? 'bronze') . '_borrow_duration', getSetting('borrow_duration', 14)) ?> Days
                            </div>
                        </li>
                        <li class="mb-2 d-flex gap-2">
                            <i class="fas fa-check-circle text-success mt-1"></i>
                            <div>
                                <strong>Shopping:</strong> 
                                <?= getSetting($tier . '_discount', 'Standard Pricing') ?>
                            </div>
                        </li>
                        <li class="d-flex gap-2">
                            <i class="fas fa-check-circle text-success mt-1"></i>
                            <div>
                                <strong>Support:</strong> 
                                <?= getSetting($tier . '_support', 'Standard') ?>
                            </div>
                        </li>
                    </ul>
                </div>

            </div>
        </div>
    </div>
</section>


<script>
function editMemberLimit(subId, currentLimit, memberName) {
    Swal.fire({
        title: `Adjust Borrow Limit`,
        text: `Set individual borrow limit for ${memberName}`,
        input: 'number',
        inputLabel: 'Books (0 to reset to tier default)',
        inputValue: currentLimit || 0,
        showCancelButton: true,
        confirmButtonText: 'Update Limit',
        confirmButtonColor: '#E07A5F',
        inputValidator: (value) => {
            if (value < 0) {
                return 'Limit cannot be negative!'
            }
        }
    }).then((result) => {
        if (result.isConfirmed) {
            const formData = new FormData();
            formData.append('sub_id', subId);
            formData.append('limit', result.value);

            fetch('api/membership_update_limit.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    Swal.fire({
                        title: 'Updated!',
                        text: data.message,
                        icon: 'success',
                        confirmButtonColor: '#E07A5F'
                    }).then(() => {
                        location.reload();
                    });
                } else {
                    Swal.fire('Error', data.message, 'error');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                Swal.fire('Error', 'An unexpected error occurred.', 'error');
            });
        }
    });
}
</script>

<?php include 'views/footer.php'; ?>
