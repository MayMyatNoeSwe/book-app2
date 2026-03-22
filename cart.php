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
    background: #fff; border-radius: 20px;
    border: 1px solid rgba(0,0,0,0.06);
    padding: 28px; position: sticky; top: 90px;
}
[data-bs-theme="dark"] .ct-summary { background: #1e293b; border-color: rgba(255,255,255,0.06); }
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

/* Promo */
.ct-promo {
    background: #fff; border-radius: 16px;
    border: 1px solid rgba(0,0,0,0.06);
    padding: 20px; margin-top: 14px;
}
[data-bs-theme="dark"] .ct-promo { background: #1e293b; border-color: rgba(255,255,255,0.06); }
.ct-promo h6 { font-weight: 700; font-size: 14px; color: var(--bookhouse-text); margin-bottom: 12px; }
.ct-promo-row { display: flex; gap: 8px; }
.ct-promo-row input {
    flex: 1; border: 1px solid rgba(0,0,0,0.08); border-radius: 12px;
    padding: 10px 16px; font-size: 13px; background: rgba(0,0,0,0.02);
    color: var(--bookhouse-text); outline: none;
}
[data-bs-theme="dark"] .ct-promo-row input { background: rgba(255,255,255,0.05); border-color: rgba(255,255,255,0.1); }
.ct-promo-row input:focus { border-color: var(--bookhouse-orange); }
.ct-promo-row button {
    padding: 10px 20px; border: 2px solid rgba(0,0,0,0.08) !important;
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
            <div class="row g-4">
                <!-- Cart Items -->
                <div class="col-lg-8">
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
                            <h5><a href="book-details.php?id=<?= $item['book_id'] ?>"><?= e($item['title']) ?></a></h5>
                            <div class="meta">by <?= e($item['author']) ?> · <?= $item['year'] ?></div>
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
                            <div class="total">$<?= number_format($item['price'] * $item['quantity'], 2) ?></div>
                            <div class="each">$<?= number_format($item['price'], 2) ?> each</div>
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
                <div class="col-lg-4">
                    <div class="ct-summary">
                        <h4>Order Summary</h4>

                        <div class="ct-summary-row">
                            <span>Subtotal (<?= $cartCount ?> items)</span>
                            <span class="val" id="subtotal">$<?= number_format($cartTotal, 2) ?></span>
                        </div>
                        <div class="ct-summary-row">
                            <span>Shipping</span>
                            <span class="val" style="color:#10b981;">FREE</span>
                        </div>
                        <div class="ct-summary-row">
                            <span>Tax</span>
                            <span class="val">$0.00</span>
                        </div>

                        <hr class="ct-summary-divider">

                        <div class="ct-summary-total">
                            <span>Total</span>
                            <span class="amount" id="total">$<?= number_format($cartTotal, 2) ?></span>
                        </div>

                        <button class="ct-checkout-btn" onclick="proceedToCheckout()">
                            <i class="fas fa-lock"></i> Proceed to Checkout
                        </button>

                        <div class="ct-secure">
                            <i class="fas fa-shield-alt me-1"></i> Secure & encrypted checkout
                        </div>
                    </div>

                    <!-- Promo -->
                    <div class="ct-promo">
                        <h6><i class="fas fa-tag me-1" style="color:var(--bookhouse-orange);"></i> Have a promo code?</h6>
                        <div class="ct-promo-row">
                            <input type="text" placeholder="Enter code">
                            <button type="button">Apply</button>
                        </div>
                    </div>
                </div>
            </div>
        <?php endif; ?>
    </div>
</div>

<script>
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

function proceedToCheckout() {
    window.location.href = 'checkout.php';
}
</script>

<?php include 'views/footer.php'; ?>
