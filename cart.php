<?php
$pageTitle = "Shopping Cart";
require_once 'includes/sessions.php';
require_once 'vendor/autoload.php';
require_once 'includes/env_loader.php';
require_once 'includes/functions.php';

use App\Auth;
use App\Cart;

// Redirect if not logged in
if (!Auth::check()) {
    header('Location: login.php?redirect=cart.php');
    exit;
}

$config = require 'config/database.php';
$dsn = "mysql:host={$config['host']};dbname={$config['dbname']};charset={$config['charset']}";
$pdo = new PDO($dsn, $config['username'], $config['password'], $config['options']);

$cart = new Cart($pdo);
$userId = Auth::id();

$cartItems = $cart->getItems($userId);
$cartTotal = $cart->getTotal($userId);
$cartCount = $cart->getCount($userId);

include 'views/header.php';
?>

</div> <!-- Close header container to allow full-width hero -->


<style>
/* ─── Cart Premium ─── */
.ct-hero {
    position: relative; overflow: hidden;
    padding: 50px 0 40px;
    background:
        radial-gradient(ellipse at 15% 55%, rgba(224,122,95,0.12) 0%, transparent 55%),
        radial-gradient(ellipse at 85% 30%, rgba(129,178,154,0.08) 0%, transparent 50%),
        var(--bookhouse-bg, #FFF3F0);
}
[data-bs-theme="dark"] .ct-hero {
    background:
        radial-gradient(ellipse at 15% 55%, rgba(224,122,95,0.15) 0%, transparent 55%),
        radial-gradient(ellipse at 85% 30%, rgba(129,178,154,0.12) 0%, transparent 50%),
        #0f172a;
}
.ct-hero::before {
    content: ''; position: absolute; inset: 0;
    background-image:
        linear-gradient(rgba(0,0,0,0.02) 1px, transparent 1px),
        linear-gradient(90deg, rgba(0,0,0,0.02) 1px, transparent 1px);
    background-size: 60px 60px; pointer-events: none;
}
[data-bs-theme="dark"] .ct-hero::before {
    background-image:
        linear-gradient(rgba(255,255,255,0.025) 1px, transparent 1px),
        linear-gradient(90deg, rgba(255,255,255,0.025) 1px, transparent 1px);
}
.ct-badge {
    display: inline-flex; align-items: center; gap: 6px;
    background: rgba(224,122,95,0.1); color: var(--bookhouse-orange);
    font-size: 11px; font-weight: 700; letter-spacing: 1.5px;
    text-transform: uppercase; padding: 6px 16px; border-radius: 999px;
    border: 1px solid rgba(224,122,95,0.2); margin-bottom: 14px;
}
.ct-hero h1 {
    font-family: 'Playfair Display', serif;
    font-size: clamp(1.8rem, 4vw, 2.8rem);
    font-weight: 800; color: var(--bookhouse-text); margin-bottom: 8px;
}
.ct-hero h1 span {
    background: linear-gradient(135deg, var(--bookhouse-orange), #c2664e);
    -webkit-background-clip: text; -webkit-text-fill-color: transparent; background-clip: text;
}
.ct-hero-sub { font-size: 15px; color: var(--bookhouse-text-muted); }

/* ─── Content ─── */
.ct-content { padding: 40px 0 60px; }

/* Cart item */
.ct-item {
    display: flex; gap: 18px; align-items: center;
    background: #fff; border-radius: 18px;
    border: 1px solid rgba(0,0,0,0.06);
    padding: 18px 22px; margin-bottom: 14px;
    transition: transform 0.2s, box-shadow 0.2s;
}
.ct-item:hover {
    transform: translateY(-2px);
    box-shadow: 0 10px 30px rgba(61,64,91,0.08);
}
[data-bs-theme="dark"] .ct-item {
    background: #1e293b; border-color: rgba(255,255,255,0.06);
}
.ct-item-cover {
    width: 70px; min-width: 70px; height: 95px;
    border-radius: 12px; overflow: hidden; flex-shrink: 0;
    background: linear-gradient(135deg, #f0e6e0, #e8ddd5);
}
[data-bs-theme="dark"] .ct-item-cover { background: linear-gradient(135deg, #1a2332, #253141); }
.ct-item-cover img { width: 100%; height: 100%; object-fit: cover; }
.ct-item-info { flex: 1; min-width: 0; }
.ct-item-info .cat {
    font-size: 10px; font-weight: 800; letter-spacing: 1px;
    text-transform: uppercase; color: var(--bookhouse-orange); margin-bottom: 2px;
}
.ct-item-info h5 {
    font-weight: 800; font-size: 15px; margin: 0 0 3px;
    color: var(--bookhouse-text);
    overflow: hidden; text-overflow: ellipsis; white-space: nowrap;
}
.ct-item-info h5 a { color: inherit; text-decoration: none; transition: color 0.2s; }
.ct-item-info h5 a:hover { color: var(--bookhouse-orange); }
.ct-item-info .meta { font-size: 13px; color: var(--bookhouse-text-muted); }

/* Quantity */
.ct-qty {
    display: flex; align-items: center; gap: 0;
    border: 1px solid rgba(0,0,0,0.08); border-radius: 12px;
    overflow: hidden; flex-shrink: 0;
}
[data-bs-theme="dark"] .ct-qty { border-color: rgba(255,255,255,0.1); }
.ct-qty button {
    width: 36px; height: 36px; border: none;
    background: transparent; color: var(--bookhouse-text-muted);
    cursor: pointer; transition: background 0.15s; font-size: 12px;
    display: flex; align-items: center; justify-content: center;
}
.ct-qty button:hover { background: rgba(224,122,95,0.08); color: var(--bookhouse-orange); }
.ct-qty input {
    width: 42px; height: 36px; border: none; border-left: 1px solid rgba(0,0,0,0.06);
    border-right: 1px solid rgba(0,0,0,0.06);
    text-align: center; font-weight: 700; font-size: 14px;
    background: transparent; color: var(--bookhouse-text); outline: none;
    -moz-appearance: textfield;
}
.ct-qty input::-webkit-outer-spin-button,
.ct-qty input::-webkit-inner-spin-button { -webkit-appearance: none; margin: 0; }
[data-bs-theme="dark"] .ct-qty input { border-color: rgba(255,255,255,0.08); }

/* Price */
.ct-price {
    text-align: right; flex-shrink: 0; min-width: 80px;
}
.ct-price .total { font-weight: 800; font-size: 17px; color: var(--bookhouse-orange); }
.ct-price .each { font-size: 12px; color: var(--bookhouse-text-muted); }

/* Remove */
.ct-remove {
    width: 36px; height: 36px; border-radius: 10px;
    border: 1px solid rgba(239,68,68,0.15); background: rgba(239,68,68,0.04);
    color: #ef4444; cursor: pointer; font-size: 13px;
    display: flex; align-items: center; justify-content: center;
    transition: all 0.2s; flex-shrink: 0;
}
.ct-remove:hover { background: #ef4444; color: #fff; border-color: #ef4444; }

/* Summary card */
.ct-summary {
    background: #ffffff !important;
    border-radius: 24px;
    border: 1px solid rgba(0,0,0,0.06);
    padding: 30px;
    box-shadow: 0 15px 45px rgba(61,64,91,0.08);
    position: relative;
    z-index: 999 !important;
}
@media (max-width: 991px) {
    .ct-summary {
        margin-top: 30px;
    }
}
@media (min-width: 992px) {
    .ct-summary-wrapper {
        position: sticky;
        top: 100px;
        z-index: 999;
    }
}
[data-bs-theme="dark"] .ct-summary { background: #1e293b !important; border-color: rgba(255,255,255,0.06); }
.ct-summary h4 {
    font-weight: 800; font-size: 18px; color: var(--bookhouse-text); margin-bottom: 24px;
}
.ct-summary-row {
    display: flex; justify-content: space-between; align-items: center;
    padding: 8px 0; font-size: 14px; color: var(--bookhouse-text-muted);
}
.ct-summary-row .val { font-weight: 600; color: var(--bookhouse-text); }
.ct-summary-divider {
    border: none; border-top: 1px dashed rgba(0,0,0,0.08);
    margin: 14px 0;
}
[data-bs-theme="dark"] .ct-summary-divider { border-color: rgba(255,255,255,0.08); }
.ct-summary-total {
    display: flex; justify-content: space-between; align-items: center;
    padding: 12px 0; font-size: 20px; font-weight: 800; color: var(--bookhouse-text);
}
.ct-summary-total .amount { color: var(--bookhouse-orange); }
.ct-checkout-btn {
    display: flex; align-items: center; justify-content: center; gap: 10px;
    width: 100%; padding: 14px; border: none; border-radius: 14px;
    background: var(--bookhouse-orange); color: #fff;
    font-weight: 800; font-size: 15px; cursor: pointer;
    box-shadow: 0 8px 24px rgba(224,122,95,0.3);
    transition: all 0.25s; margin-top: 20px;
}
.ct-checkout-btn:hover { filter: brightness(1.1); transform: translateY(-2px); }
.ct-secure {
    text-align: center; margin-top: 14px;
    font-size: 12px; color: var(--bookhouse-text-muted);
}

/* Redesign Helpers */
.custom-select-premium {
    appearance: none;
    background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='16' height='16' fill='%2364748b' viewBox='0 0 16 16'%3E%3Cpath d='M7.247 11.14 2.451 5.658C2.017 5.163 2.372 4.5 3.012 4.5h9.976c.64 0 .995.663.561 1.158l-4.796 5.482a.89.89 0 0 1-1.306 0L7.247 11.14z'/%3E%3C/svg%3E");
    background-repeat: no-repeat;
    background-position: right 14px center;
    background-size: 12px;
    border: 1.5px solid rgba(0,0,0,0.06);
    border-radius: 12px;
    padding: 12px 14px;
    font-size: 14px;
    background-color: rgba(0,0,0,0.02);
    transition: all 0.2s;
    cursor: pointer;
}
.custom-select-premium:focus {
    border-color: var(--bookhouse-orange);
    box-shadow: 0 0 0 4px rgba(224,122,95,0.1);
    background-color: #fff;
    outline: none;
}
.btn-outline-premium {
    border: 1.5px solid rgba(0,0,0,0.06) !important;
    background: rgba(0,0,0,0.02);
    border-radius: 12px;
    padding: 12px 8px;
    color: var(--bookhouse-text-muted);
    transition: all 0.2s;
}
.btn-check:checked + .btn-outline-premium {
    background: rgba(224,122,95,0.08) !important;
    border-color: var(--bookhouse-orange) !important;
    color: var(--bookhouse-orange) !important;
}
.btn-outline-premium:hover {
    background: rgba(0,0,0,0.04);
    border-color: rgba(0,0,0,0.1) !important;
}
[data-bs-theme="dark"] .custom-select-premium { background-color: rgba(255,255,255,0.05); border-color: rgba(255,255,255,0.1); color: #fff; }
[data-bs-theme="dark"] .btn-outline-premium { background: rgba(255,255,255,0.05); border-color: rgba(255,255,255,0.1) !important; color: #94a3b8; }
[data-bs-theme="dark"] .btn-check:checked + .btn-outline-premium { background: rgba(224,122,95,0.15) !important; color: var(--bookhouse-orange) !important; }
[data-bs-theme="dark"] #delivery-address { background: rgba(255,255,255,0.05); border-color: rgba(255,255,255,0.1); color: #fff; }

/* Promo */
.ct-promo {
    margin-top: 24px; padding-top: 20px;
    border-top: 1px dashed rgba(0,0,0,0.08);
}
[data-bs-theme="dark"] .ct-promo { border-color: rgba(255,255,255,0.08); }
.ct-promo h6 { font-weight: 700; font-size: 13px; color: var(--bookhouse-text); margin-bottom: 12px; }
.ct-promo-row { display: flex; gap: 8px; }
.ct-promo-row input {
    flex: 1; border: 1.5px solid rgba(0,0,0,0.08); border-radius: 12px;
    padding: 10px 14px; font-size: 13px; background: rgba(0,0,0,0.02);
    color: var(--bookhouse-text); outline: none; transition: all 0.2s;
}
[data-bs-theme="dark"] .ct-promo-row input { background: rgba(255,255,255,0.05); border-color: rgba(255,255,255,0.1); }
.ct-promo-row input:focus { border-color: var(--bookhouse-orange); box-shadow: 0 0 0 4px rgba(224,122,95,0.1); }
.ct-promo-row button {
    padding: 10px 18px; border: 1.5px solid rgba(0,0,0,0.1) !important;
    border-radius: 12px; background: transparent;
    color: var(--bookhouse-text); font-weight: 700; font-size: 13px;
    cursor: pointer; transition: all 0.2s;
}
.ct-promo-row button:hover { border-color: var(--bookhouse-orange) !important; color: var(--bookhouse-orange); }

/* Empty */
.ct-empty {
    text-align: center; padding: 80px 20px;
    background: #fff; border-radius: 24px;
    border: 1px solid rgba(0,0,0,0.06);
}
[data-bs-theme="dark"] .ct-empty { background: #1e293b; border-color: rgba(255,255,255,0.06); }
.ct-empty-icon {
    width: 100px; height: 100px; margin: 0 auto 24px;
    background: rgba(224,122,95,0.08); border-radius: 50%;
    display: flex; align-items: center; justify-content: center;
    font-size: 40px; color: var(--bookhouse-orange); opacity: 0.6;
}
.ct-empty h3 { font-weight: 800; color: var(--bookhouse-text); margin-bottom: 10px; }
.ct-empty p { color: var(--bookhouse-text-muted); margin-bottom: 28px; max-width: 380px; margin-left: auto; margin-right: auto; font-size: 14px; }
.ct-btn-primary {
    display: inline-flex; align-items: center; gap: 8px;
    padding: 12px 28px; border-radius: 14px;
    background: var(--bookhouse-orange); color: #fff;
    font-weight: 700; font-size: 14px; text-decoration: none;
    box-shadow: 0 8px 20px rgba(224,122,95,0.3);
    transition: all 0.2s;
}
.ct-btn-primary:hover { filter: brightness(1.1); transform: translateY(-2px); color: #fff; }
.ct-btn-outline {
    display: inline-flex; align-items: center; gap: 8px;
    padding: 10px 22px; border-radius: 14px;
    border: 2px solid rgba(0,0,0,0.08); background: transparent;
    color: var(--bookhouse-text); font-weight: 700; font-size: 13px;
    text-decoration: none; transition: all 0.2s;
}
[data-bs-theme="dark"] .ct-btn-outline { border-color: rgba(255,255,255,0.1); }
.ct-btn-outline:hover { border-color: var(--bookhouse-orange); color: var(--bookhouse-orange); }

/* Responsive */
@media (max-width: 767px) {
    .ct-hero { padding: 40px 0 28px; text-align: center; }
    .ct-item { flex-wrap: wrap; gap: 14px; }
    .ct-item-info { flex-basis: calc(100% - 90px); }
    .ct-price { text-align: left; }
    .ct-qty, .ct-price, .ct-remove {  }
    .ct-item-bottom { display: flex; align-items: center; justify-content: space-between; width: 100%; gap: 12px; }
}
</style>

<!-- ═══════  HERO  ═══════ -->
<section class="ct-hero">
    <div class="container position-relative" style="z-index:2;">
        <nav aria-label="breadcrumb" class="mb-3">
            <ol class="breadcrumb" style="font-size:13px;">
                <li class="breadcrumb-item"><a href="index.php" class="text-decoration-none" style="color:var(--bookhouse-text-muted);">Home</a></li>
                <li class="breadcrumb-item active fw-bold" style="color:var(--bookhouse-orange);">Cart</li>
            </ol>
        </nav>

        <div class="ct-badge"><i class="fas fa-shopping-cart"></i> Shopping Cart</div>
        <h1>Your <span>Cart</span></h1>
        <p class="ct-hero-sub"><?= $cartCount ?> <?= $cartCount === 1 ? 'item' : 'items' ?> waiting for you</p>
    </div>
</section>

<!-- ═══════  CONTENT  ═══════ -->
<div class="ct-content">
    <div class="container">
        <?php if (empty($cartItems)): ?>
            <!-- Empty Cart -->
            <div class="ct-empty">
                <div class="ct-empty-icon"><i class="fas fa-shopping-cart"></i></div>
                <h3>Your cart is empty</h3>
                <p>Looks like you haven't added any books yet. Browse our collection and find your next read!</p>
                <a href="book-list.php" class="ct-btn-primary">
                    <i class="fas fa-book"></i> Browse Books
                </a>
            </div>
        <?php else: ?>
            <div class="row g-4 align-items-start">
                <!-- Cart Items -->
                <div class="col-md-7 col-lg-8">
                    <?php foreach ($cartItems as $item): ?>
                    <div class="ct-item" id="cart-item-<?= $item['id'] ?>">
                        <!-- Cover -->
                        <div class="ct-item-cover">
                            <img src="<?= getBookCoverUrl((object)$item, $item['title'], $item['author']) ?>"
                                 alt="<?= e($item['title']) ?>"
                                 onerror="this.src='<?= getDummyBookCover($item['title'], $item['author'], 150, 200) ?>'">
                        </div>

                        <!-- Info -->
                        <div class="ct-item-info">
                            <div class="cat"><?= e($item['category']) ?></div>
                            <h5>
                                <a href="book-details.php?id=<?= $item['book_id'] ?>"><?= e($item['title']) ?></a>
                                <?php if (($item['available_copies'] ?? 1) <= 0): ?>
                                    <span class="badge" style="background:rgba(82,79,125,0.1); color:#524f7d; font-size:10px; padding:4px 8px; border:1px solid rgba(82,79,125,0.2); vertical-align:middle; margin-left:6px;">Pre-order</span>
                                <?php endif; ?>
                            </h5>
                            <div class="meta">
                                by <?= e($item['author']) ?> · <?= $item['year'] ?>
                                <?php if (($item['available_copies'] ?? 1) <= 0): ?>
                                    · <span style="color:#524f7d; font-weight:600;">Backorder (Expected in 7-14 days)</span>
                                <?php endif; ?>
                            </div>
                        </div>

                        <!-- Quantity -->
                        <div class="ct-qty">
                            <button onclick="updateQuantity(<?= $item['id'] ?>, <?= $item['quantity'] - 1 ?>)"><i class="fas fa-minus"></i></button>
                            <input type="number" value="<?= $item['quantity'] ?>" min="1" max="10"
                                   id="qty-<?= $item['id'] ?>"
                                   onchange="updateQuantity(<?= $item['id'] ?>, this.value)">
                            <button onclick="updateQuantity(<?= $item['id'] ?>, <?= $item['quantity'] + 1 ?>)"><i class="fas fa-plus"></i></button>
                        </div>

                        <!-- Price -->
                        <div class="ct-price">
                            <div class="total"><?= number_format($item['price'] * $item['quantity']) ?> Ks</div>
                            <div class="each"><?= number_format($item['price']) ?> Ks each</div>
                        </div>

                        <!-- Remove -->
                        <button class="ct-remove" onclick="removeItem(<?= $item['id'] ?>)" title="Remove">
                            <i class="fas fa-trash"></i>
                        </button>
                    </div>
                    <?php endforeach; ?>

                    <div class="mt-4">
                        <a href="book-list.php" class="ct-btn-outline">
                            <i class="fas fa-arrow-left"></i> Continue Shopping
                        </a>
                    </div>
                </div>

                <!-- Summary -->
                <div class="col-md-5 col-lg-4">
                    <div class="ct-summary-wrapper">
                        <div class="ct-summary">
                            <h4>Order Summary</h4>

                            <!-- Redesigned Delivery Section -->
                            <div class="ct-delivery mt-4 pt-4" style="border-top: 2px solid rgba(0,0,0,0.03);">
                                <h6 style="font-size: 14px; font-weight: 800; color: var(--bookhouse-text); margin-bottom: 20px; display: flex; align-items: center; gap: 10px;">
                                    <div style="width: 32px; height: 32px; border-radius: 8px; background: rgba(224,122,95,0.1); display: flex; align-items: center; justify-content: center;">
                                        <i class="fas fa-truck" style="color: var(--bookhouse-orange); font-size: 14px;"></i>
                                    </div>
                                    Delivery Details
                                </h6>
                                
                                <!-- Township Selection -->
                                <div class="mb-4">
                                    <label class="form-label d-flex justify-content-between" style="font-size: 11px; text-transform: uppercase; font-weight: 800; color: #64748b; letter-spacing: 0.5px; margin-bottom: 8px;">
                                        <span>Select Your Township</span>
                                        <span id="zone-label" style="color: var(--bookhouse-orange);">Inner Yangon</span>
                                    </label>
                                    <div class="position-relative">
                                        <select class="form-select custom-select-premium" id="delivery-location" onchange="updateShippingCost()">
                                            <optgroup label="Inner Yangon (1,500 Ks)">
                                                <option value="ygn-inner" selected>Dagon</option>
                                                <option value="ygn-inner">Hlaing</option>
                                                <option value="ygn-inner">Bahan</option>
                                                <option value="ygn-inner">Downtown (Pabedan, Kyauktada)</option>
                                                <option value="ygn-inner">Sanchaung</option>
                                                <option value="ygn-inner">Kamayut</option>
                                                <option value="ygn-inner">Yankin</option>
                                            </optgroup>
                                            <optgroup label="Outer Yangon (2,000 Ks)">
                                                <option value="ygn-outer">Thaketa</option>
                                                <option value="ygn-outer">Thingangyun</option>
                                                <option value="ygn-outer">North Okkalapa</option>
                                                <option value="ygn-outer">South Okkalapa</option>
                                                <option value="ygn-outer">Tamwe</option>
                                                <option value="ygn-outer">Mayangone</option>
                                            </optgroup>
                                            <optgroup label="Distant/Suburbs (3,000 Ks)">
                                                <option value="ygn-distant">Mingalardon</option>
                                                <option value="ygn-distant">Insein</option>
                                                <option value="ygn-distant">Hlaing Tharyar</option>
                                                <option value="ygn-distant">South Dagon</option>
                                                <option value="ygn-distant">East Dagon</option>
                                                <option value="ygn-distant">North Dagon</option>
                                                <option value="ygn-distant">Shwepyithar</option>
                                            </optgroup>
                                        <optgroup label="Yangon (Royal Express Serviceable)">
                                            <option value="ygn-inner" selected>Yangon - Main Townships</option>
                                            <option value="ygn-distant">Hlaing Tharyar / Mingalardon</option>
                                            <option value="ygn-distant">Thanlyin / Kyauktan</option>
                                        </optgroup>
                                        <optgroup label="Mandalay Region">
                                            <option value="city-easy">Mandalay City</option>
                                            <option value="city-med">Pyin Oo Lwin</option>
                                            <option value="city-med">Kyaukse</option>
                                            <option value="city-med">Myingyan</option>
                                            <option value="city-med">Mogok</option>
                                        </optgroup>
                                        <optgroup label="Bago Region">
                                            <option value="city-easy">Bago City</option>
                                            <option value="city-med">Pyay</option>
                                            <option value="city-med">Taungoo</option>
                                            <option value="city-med">Nyaunglebin</option>
                                        </optgroup>
                                        <optgroup label="Naypyidaw (Special Region)">
                                            <option value="city-easy">Zabuthiri / Pyinmana</option>
                                            <option value="city-easy">Ottarathiri / Tatkon</option>
                                        </optgroup>
                                        <optgroup label="Shan State (Check Availability)">
                                            <option value="city-easy">Taunggyi</option>
                                            <option value="city-med">Kalaw / Aung Pan</option>
                                            <option value="city-med" disabled>Lashio (Svc Suspended)</option>
                                            <option value="city-med" disabled>Muse (Svc Suspended)</option>
                                            <option value="city-med" disabled>Tachileik (Svc Suspended)</option>
                                        </optgroup>
                                        <optgroup label="Rakhine & Kachin (Restricted)">
                                            <option value="city-hard" disabled>Sittwe (Svc Suspended)</option>
                                            <option value="city-hard" disabled>Myitkyina (Svc Suspended)</option>
                                            <option value="city-hard" disabled>Bhamo (Svc Suspended)</option>
                                        </optgroup>
                                        <optgroup label="Mon & Kayin State">
                                            <option value="city-easy">Mawlamyine</option>
                                            <option value="city-med">Hpa-An</option>
                                            <option value="city-med">Mudon / Ye</option>
                                        </optgroup>
                                        <optgroup label="Magway & Sagaing Region">
                                            <option value="city-easy">Magway City</option>
                                            <option value="city-med">Pakokku</option>
                                            <option value="city-easy">Monywa</option>
                                            <option value="city-med">Shwebo</option>
                                        </optgroup>
                                    </select>
                                        </select>
                                    </div>
                                </div>

                                <!-- Method Selection Cards (Dynamic) -->
                                <div class="mb-4">
                                    <label class="form-label" style="font-size: 11px; text-transform: uppercase; font-weight: 800; color: #64748b; letter-spacing: 0.5px; margin-bottom: 8px;">Delivery Method</label>
                                    <div class="row g-2" id="method-container">
                                        <!-- Will be filled by JS -->
                                    </div>
                                </div>

                                <div class="mb-2">
                                    <label class="form-label" style="font-size: 11px; text-transform: uppercase; font-weight: 800; color: #64748b; letter-spacing: 0.5px; margin-bottom: 8px;">Full Address</label>
                                    <textarea class="form-control" id="delivery-address" rows="2" placeholder="Street name, Home number..." style="font-size: 13px; border-radius: 12px; border: 1.5px solid rgba(0,0,0,0.06); background: rgba(0,0,0,0.02); resize: none; transition: all 0.2s;"></textarea>
                                </div>
                            </div>

                            <hr class="ct-summary-divider mt-4">

                            <div class="ct-summary-row">
                                <span>Subtotal (<?= $cartCount ?> items)</span>
                                <span class="val" id="subtotal"><?= number_format($cartTotal) ?> Ks</span>
                            </div>
                            <div class="ct-summary-row">
                                <span style="display: flex; align-items:center; gap:6px;">Shipping <i class="fas fa-info-circle text-muted" title="Free standard delivery for Yangon when ordering 5+ books" style="font-size:11px; cursor:help;"></i></span>
                                <span class="val" id="shipping-cost">1,500 Ks</span>
                            </div>
                            <div class="ct-summary-row">
                                <span>Tax</span>
                                <span class="val">0 Ks</span>
                            </div>

                            <div class="ct-summary-total">
                                <span>Total</span>
                                <span class="amount" id="total"><?= number_format($cartTotal) ?> Ks</span>
                            </div>

                            <!-- Promo Integrated -->
                            <div class="ct-promo">
                                <h6><i class="fas fa-tag me-1" style="color:var(--bookhouse-orange);"></i> Have a promo code?</h6>
                                <div class="ct-promo-row">
                                    <input type="text" placeholder="Enter code">
                                    <button type="button">Apply</button>
                                </div>
                            </div>

                            <button class="ct-checkout-btn" onclick="placeOrder()">
                                <i class="fas fa-shopping-bag"></i> Place Order
                            </button>

                            <div class="ct-secure">
                                <i class="fas fa-shield-alt me-1"></i> Secure & encrypted checkout
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        <?php endif; ?>
    </div>
</div>

<script>
const cartTotal = <?= (int)$cartTotal ?>;
const cartCount = <?= (int)$cartCount ?>;

// Initialize on load
document.addEventListener('DOMContentLoaded', () => {
    updateShippingCost();
});

function updateShippingCost() {
    const locationSelect = document.getElementById('delivery-location');
    const location = locationSelect.value;
    const shippingEl = document.getElementById('shipping-cost');
    const totalEl = document.getElementById('total');
    const zoneLabel = document.getElementById('zone-label');
    const methodContainer = document.getElementById('method-container');
    
    // Check digital only
    const isDigitalOnly = <?= $cartTotal > 0 ? 'false' : 'true' ?>; // Simple check for demo purposes
    // Better: let the PHP count physical items.
    
    // Update methods UI based on location
    const currentMethod = document.querySelector('input[name="delivery-method"]:checked')?.value || 'std';
    let methodsHTML = '';
    
    if (location.includes('ygn')) {
        methodsHTML = `
            <div class="col-6">
                <input type="radio" class="btn-check" name="delivery-method" id="m-std" value="std" ${currentMethod==='std' || currentMethod==='courier' ? 'checked' : ''} onchange="updateShippingCost()">
                <label class="btn btn-outline-premium w-100" for="m-std">
                    <i class="fas fa-home d-block mb-1"></i>
                    <span class="d-block" style="font-size:12px; font-weight:700;">Door + COD</span>
                    <small style="font-size:9px; opacity:0.7;">1-2 Days</small>
                </label>
            </div>
            <div class="col-6">
                <input type="radio" class="btn-check" name="delivery-method" id="m-exp" value="exp" ${currentMethod==='exp' || currentMethod==='bus' ? 'checked' : ''} onchange="updateShippingCost()">
                <label class="btn btn-outline-premium w-100" for="m-exp">
                    <i class="fas fa-bolt d-block mb-1"></i>
                    <span class="d-block" style="font-size:12px; font-weight:700;">Express</span>
                    <small style="font-size:9px; opacity:0.7;">Today/Next</small>
                </label>
            </div>
        `;
    } else if (location === 'city-easy') {
        methodsHTML = `
            <div class="col-12">
                <input type="radio" class="btn-check" name="delivery-method" id="m-courier" value="courier" checked onchange="updateShippingCost()">
                <label class="btn btn-outline-premium w-100" for="m-courier">
                    <i class="fas fa-shipping-fast d-block mb-1"></i>
                    <span class="d-block" style="font-size:12px; font-weight:700;">Courier Delivery</span>
                    <small style="font-size:9px; opacity:0.7;">City & Main Road Areas</small>
                </label>
            </div>
        `;
    } else {
        methodsHTML = `
            <div class="col-6">
                <input type="radio" class="btn-check" name="delivery-method" id="m-bus" value="bus" ${currentMethod==='std' || currentMethod==='bus' || currentMethod==='courier' ? 'checked' : ''} onchange="updateShippingCost()">
                <label class="btn btn-outline-premium w-100" for="m-bus">
                    <i class="fas fa-bus d-block mb-1"></i>
                    <span class="d-block" style="font-size:12px; font-weight:700;">Bus Gate</span>
                    <small style="font-size:9px; opacity:0.7;">Township Gate</small>
                </label>
            </div>
            <div class="col-6">
                <input type="radio" class="btn-check" name="delivery-method" id="m-post" value="post" ${currentMethod==='exp' || currentMethod==='post' ? 'checked' : ''} onchange="updateShippingCost()">
                <label class="btn btn-outline-premium w-100" for="m-post">
                    <i class="fas fa-envelope d-block mb-1"></i>
                    <span class="d-block" style="font-size:12px; font-weight:700;">Myanma Post</span>
                    <small style="font-size:9px; opacity:0.7;">Home Delivery</small>
                </label>
            </div>
        `;
    }
    
    // Only update if different
    if (methodContainer.innerHTML !== methodsHTML) {
        methodContainer.innerHTML = methodsHTML;
    }

    const method = document.querySelector('input[name="delivery-method"]:checked').value;
    
    // Update zone label for user
    const selectedText = locationSelect.options[locationSelect.selectedIndex].parentNode.label;
    if (selectedText) zoneLabel.innerText = selectedText.split(' (')[0];
    else if(location === 'mandalay') zoneLabel.innerText = "Mandalay & Cities";
    else zoneLabel.innerText = "Small Towns";

    let base = 0;
    
    // Tiered Base Calculation
    switch(location) {
        case 'ygn-inner':   base = 1500; break;
        case 'ygn-outer':   base = 2000; break;
        case 'ygn-distant': base = 3000; break;
        case 'city-easy':   base = 4000; break;
        case 'city-med':    base = 4500; break;
        case 'city-hard':   base = 5000; break;
    }
    
    // Method Multiplier/Addition
    let cost = base;
    if (method === 'exp') {
        cost += 1500; // Express surcharge
    }
    
    // Free Shipping Rule (Standard/COD/Bus is free over 5 books for Yangon only, Express gets 1.5k discount)
    if (cartCount >= 5 && location.includes('ygn')) {
        if (method === 'std') {
            cost = 0;
        } else if (method === 'exp') {
            cost -= 1500; 
        }
    }
    
    // Update Display
    if (cost === 0) {
        shippingEl.innerHTML = '<span style="color:#10b981; font-weight:800;">FREE</span>';
    } else {
        shippingEl.innerText = cost.toLocaleString() + " Ks";
    }
    
    totalEl.innerText = (cartTotal + cost).toLocaleString() + " Ks";
}

function updateQuantity(cartId, quantity) {
    quantity = parseInt(quantity);
    if (quantity < 1) { removeItem(cartId); return; }
    if (quantity > 10) {
        Swal.fire({ icon:'warning', title:'Maximum Quantity', text:'You can only order up to 10 copies', confirmButtonColor:'#E07A5F' });
        return;
    }
    fetch('api/cart_update.php', {
        method: 'POST',
        headers: {'Content-Type': 'application/json'},
        body: JSON.stringify({ cart_id: cartId, quantity: quantity })
    })
    .then(r => r.json())
    .then(data => {
        if (data.success) location.reload();
        else Swal.fire({ icon:'error', title:'Error', text: data.message, confirmButtonColor:'#E07A5F' });
    })
    .catch(() => Swal.fire({ icon:'error', title:'Error', text:'Failed to update cart', confirmButtonColor:'#E07A5F' }));
}

function removeItem(cartId) {
    Swal.fire({
        title: 'Remove Item?', text: 'Remove this book from your cart?', icon: 'warning',
        showCancelButton: true, confirmButtonColor: '#d33', cancelButtonColor: '#6c757d',
        confirmButtonText: 'Yes, remove it'
    }).then((result) => {
        if (result.isConfirmed) {
            fetch('api/cart_remove.php', {
                method: 'POST',
                headers: {'Content-Type': 'application/json'},
                body: JSON.stringify({ cart_id: cartId })
            })
            .then(r => r.json())
            .then(data => {
                if (data.success) {
                    Swal.fire({ icon:'success', title:'Removed!', timer:1500, showConfirmButton:false })
                        .then(() => location.reload());
                } else {
                    Swal.fire({ icon:'error', title:'Error', text: data.message, confirmButtonColor:'#E07A5F' });
                }
            });
        }
    });
}

function placeOrder() {
    const locationSelect = document.getElementById('delivery-location');
    const location = locationSelect.options[locationSelect.selectedIndex].text;
    const methodInput = document.querySelector('input[name="delivery-method"]:checked');
    const methodLine = methodInput.nextElementSibling.querySelector('span').innerText;
    const address = document.getElementById('delivery-address').value;
    const shippingText = document.getElementById('shipping-cost').innerText;
    
    if (!address.trim()) {
        Swal.fire({ icon:'warning', title:'Required', text:'Please enter your delivery address', confirmButtonColor:'#E07A5F' });
        return;
    }

    // Parse shipping cost
    let shippingCost = 0;
    if (shippingText !== 'FREE') {
        shippingCost = parseInt(shippingText.replace(/[^0-9]/g, ''));
    }

    const btn = event.currentTarget;
    const originalText = btn.innerHTML;
    btn.disabled = true;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Placing Order...';

    fetch('api/place_order.php', {
        method: 'POST',
        headers: {'Content-Type': 'application/json'},
        body: JSON.stringify({
            shipping_cost: shippingCost,
            delivery_location: location,
            delivery_method: methodLine,
            shipping_address: address,
            payment_method: 'Cash on Delivery'
        })
    })
    .then(r => r.json())
    .then(data => {
        if (data.success) {
            Swal.fire({
                icon:'success',
                title:'Success!',
                text:'Your order has been placed successfully.',
                confirmButtonColor:'#E07A5F',
                timer: 2000,
                showConfirmButton: false
            }).then(() => {
                window.location.href = 'order-details.php?id=' + data.order_number;
            });
        } else {
            Swal.fire({ icon:'error', title:'Error', text: data.message, confirmButtonColor:'#E07A5F' });
            btn.disabled = false;
            btn.innerHTML = originalText;
        }
    })
    .catch(() => {
        Swal.fire({ icon:'error', title:'Error', text:'Failed to place order', confirmButtonColor:'#E07A5F' });
        btn.disabled = false;
        btn.innerHTML = originalText;
    });
}
</script>

<div class="container pb-5"> <!-- Re-open container for footer -->
<?php include 'views/footer.php'; ?>
