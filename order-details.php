<?php
$pageTitle = "Order Details";
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

$orderNumber = $_GET['id'] ?? null;
if (!$orderNumber) {
    header("Location: user-details.php");
    exit;
}

$config = require 'config/database.php';
$dsn = "mysql:host={$config['host']};dbname={$config['dbname']};charset={$config['charset']}";
$pdo = new PDO($dsn, $config['username'], $config['password'], $config['options']);

$cart = new Cart($pdo);
$userId = Auth::id();
$order = $cart->getOrderDetails($orderNumber, $userId);

if (!$order) {
    header("Location: user-details.php");
    exit;
}

include 'views/header.php';
?>

<style>
/* ─── Order Details Premium ─── */
.od-hero {
    position: relative; overflow: hidden;
    padding: 60px 0 40px;
    background:
        radial-gradient(ellipse at 15% 55%, rgba(224,122,95,0.1) 0%, transparent 55%),
        radial-gradient(ellipse at 85% 30%, rgba(129,178,154,0.06) 0%, transparent 50%),
        var(--bookhouse-bg, #FFF3F0);
}
[data-bs-theme="dark"] .od-hero {
    background:
        radial-gradient(ellipse at 15% 55%, rgba(224,122,95,0.12) 0%, transparent 55%),
        radial-gradient(ellipse at 85% 30%, rgba(129,178,154,0.09) 0%, transparent 50%),
        #0f172a;
}
.od-badge {
    display: inline-flex; align-items: center; gap: 6px;
    background: rgba(224,122,95,0.1); color: var(--bookhouse-orange);
    font-size: 11px; font-weight: 700; letter-spacing: 1.5px;
    text-transform: uppercase; padding: 6px 16px; border-radius: 999px;
    border: 1px solid rgba(224,122,95,0.2); margin-bottom: 14px;
}
.od-hero h1 {
    font-family: 'Playfair Display', serif;
    font-size: clamp(1.4rem, 3vw, 2.2rem);
    font-weight: 800; color: var(--bookhouse-text); margin-bottom: 8px;
}
.od-hero h1 span { color: var(--bookhouse-orange); font-size: 0.9em; }

/* Status */
.od-status-wrapper {
    display: flex; align-items: center; gap: 12px; margin-top: 15px;
}
.od-status-pill {
    padding: 8px 20px; border-radius: 999px; font-weight: 800; font-size: 13px;
    text-transform: uppercase; letter-spacing: 1px;
}
.od-status-pill.pending { background: #fef3c7; color: #92400e; }
.od-status-pill.processing { background: #dbeafe; color: #1e40af; }
.od-status-pill.completed { background: #dcfce7; color: #166534; }
.od-status-pill.cancelled { background: #fee2e2; color: #991b1b; }

/* Content */
.od-content { padding: 40px 0 80px; }

.od-card {
    background: #fff; border-radius: 24px;
    border: 1px solid rgba(0,0,0,0.06); padding: 30px;
    box-shadow: 0 10px 30px rgba(61,64,91,0.05); margin-bottom: 24px;
}
[data-bs-theme="dark"] .od-card { background: #1e293b; border-color: rgba(255,255,255,0.06); }

.od-section-title {
    font-weight: 800; font-size: 15px; margin-bottom: 18px; color: var(--bookhouse-text);
    display: flex; align-items: center; gap: 10px;
}
.od-section-title i { color: var(--bookhouse-orange); opacity: 0.8; }

/* Item List */
.od-item {
    display: flex; gap: 18px; padding: 18px 0;
    border-bottom: 1px solid rgba(0,0,0,0.05);
}
.od-item:last-child { border-bottom: none; }
.od-item-img {
    width: 60px; height: 85px; border-radius: 10px; overflow: hidden; flex-shrink: 0;
    background: #f0e6e0;
}
.od-item-img img { width: 100%; height: 100%; object-fit: cover; }
.od-item-info { flex: 1; }
.od-item-title { font-weight: 800; font-size: 15px; margin-bottom: 4px; color: var(--bookhouse-text); }
.od-item-meta { font-size: 13px; color: var(--bookhouse-text-muted); }

/* Summary */
.od-row { display: flex; justify-content: space-between; margin-bottom: 10px; font-size: 13px; }
.od-row.total { font-size: 17px; font-weight: 800; color: var(--bookhouse-orange); margin-top: 15px; padding-top: 15px; border-top: 1px dashed rgba(0,0,0,0.1); }
.od-row .label { color: var(--bookhouse-text-muted); }
.od-row .val { font-weight: 700; color: var(--bookhouse-text); }

/* Order Meta */
.od-meta-item { margin-bottom: 20px; }
.od-meta-item:last-child { margin-bottom: 0; }
.od-meta-label { font-size: 10px; font-weight: 800; text-transform: uppercase; color: var(--bookhouse-text-muted); letter-spacing: 1px; margin-bottom: 6px; }
.od-meta-val { font-size: 13.5px; color: var(--bookhouse-text); line-height: 1.6; }

.btn-back {
    display: inline-flex; align-items: center; gap: 8px;
    padding: 10px 20px; border-radius: 12px;
    background: transparent; border: 1.5px solid rgba(0,0,0,0.1);
    color: var(--bookhouse-text); text-decoration: none; font-weight: 700;
    transition: all 0.2s;
}
.btn-back:hover { border-color: var(--bookhouse-orange); color: var(--bookhouse-orange); transform: translateX(-4px); }
</style>

<!-- ═══════  HERO  ═══════ -->
<section class="od-hero">
    <div class="container position-relative" style="z-index:2;">
        <nav aria-label="breadcrumb" class="mb-3">
            <ol class="breadcrumb" style="font-size:13px;">
                <li class="breadcrumb-item"><a href="index.php" class="text-decoration-none text-muted">Home</a></li>
                <li class="breadcrumb-item"><a href="user-details.php" class="text-decoration-none text-muted">My Profile</a></li>
                <li class="breadcrumb-item active fw-bold" style="color:var(--bookhouse-orange);">Order Details</li>
            </ol>
        </nav>

        <div class="od-badge">Order Confirmed</div>
        <h1>Order <span>#<?= e($order['order_number']) ?></span></h1>
        
        <div class="od-status-wrapper">
            <span class="od-status-pill <?= strtolower($order['status']) ?>">
                 Status: <?= ucfirst($order['status']) ?>
            </span>
            <span class="text-muted" style="font-size:14px;">Placed on <?= date('F j, Y \a\t g:i A', strtotime($order['created_at'])) ?></span>
        </div>
    </div>
</section>

<!-- ═══════  CONTENT  ═══════ -->
<div class="od-content">
    <div class="container">
        <div class="row g-4">
            <!-- Items Detail -->
            <div class="col-lg-8">
                <div class="od-card">
                    <h5 class="od-section-title"><i class="fas fa-shopping-basket"></i> Order Items</h5>
                    
                    <div class="od-items">
                        <?php 
                        $subtotal = 0;
                        foreach ($order['items'] as $item): 
                            $itemTotal = $item['price'] * $item['quantity'];
                            $subtotal += $itemTotal;
                        ?>
                        <div class="od-item">
                            <div class="od-item-img">
                                <img src="<?= getBookCoverUrl((object)$item, $item['title'], $item['author']) ?>"
                                     alt="Cover"
                                     onerror="this.src='<?= getDummyBookCover($item['title'], $item['author'], 150, 200) ?>'">
                            </div>
                            <div class="od-item-info">
                                <div class="od-item-title"><?= e($item['title']) ?></div>
                                <div class="od-item-meta">by <?= e($item['author']) ?></div>
                                <div class="od-item-meta mt-2" style="font-weight:700;">
                                    <?= number_format($item['price']) ?> Ks × <?= $item['quantity'] ?>
                                </div>
                            </div>
                            <div class="text-end" style="font-weight:800; color:var(--bookhouse-text);">
                                <?= number_format($itemTotal) ?> Ks
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>

                <div class="d-none d-lg-block mt-4">
                    <a href="user-details.php" class="btn-back">
                        <i class="fas fa-arrow-left"></i> Back to My Orders
                    </a>
                </div>
            </div>

            <!-- Summary & Info -->
            <div class="col-lg-4">
                <div class="od-card">
                    <h5 class="od-section-title"><i class="fas fa-receipt"></i> Order Summary</h5>
                    
                    <div class="od-row">
                        <span class="label">Subtotal</span>
                        <span class="val"><?= number_format($subtotal) ?> Ks</span>
                    </div>
                    <div class="od-row">
                        <span class="label">Shipping cost</span>
                        <span class="val"><?= (int)$order['shipping_cost'] === 0 ? 'FREE' : number_format($order['shipping_cost']) . ' Ks' ?></span>
                    </div>
                    <div class="od-row">
                        <span class="label">Tax</span>
                        <span class="val">0 Ks</span>
                    </div>
                    <div class="od-row total">
                        <span class="label" style="color:var(--bookhouse-text);">Total Amount</span>
                        <span class="val"><?= number_format($order['total_amount']) ?> Ks</span>
                    </div>
                </div>

                <div class="od-card">
                    <h5 class="od-section-title"><i class="fas fa-truck"></i> Delivery Information</h5>
                    
                    <div class="od-meta-item">
                        <div class="od-meta-label">Shipping Address</div>
                        <div class="od-meta-val">
                            <strong><?= e($order['delivery_location']) ?></strong><br>
                            <?= nl2br(e($order['shipping_address'])) ?>
                        </div>
                    </div>
                    
                    <div class="od-meta-item">
                        <div class="od-meta-label">Delivery Method</div>
                        <div class="od-meta-val"><?= e($order['delivery_method']) ?></div>
                    </div>

                    <div class="od-meta-item">
                        <div class="od-meta-label">Payment Method</div>
                        <div class="od-meta-val"><?= e($order['payment_method']) ?></div>
                    </div>

                    <?php if($order['notes']): ?>
                    <div class="od-meta-item">
                        <div class="od-meta-label">Order Notes</div>
                        <div class="od-meta-val"><?= nl2br(e($order['notes'])) ?></div>
                    </div>
                    <?php endif; ?>
                </div>

                <div class="d-lg-none text-center mt-2 mb-4">
                    <a href="user-details.php" class="btn-back">
                        <i class="fas fa-arrow-left"></i> Back to My Orders
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include 'views/footer.php'; ?>
