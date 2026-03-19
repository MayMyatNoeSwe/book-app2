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

<div class="cart-container">
    <!-- Breadcrumb -->
    <div class="container-fluid">
        <nav aria-label="breadcrumb" class="pt-3">
            <ol class="breadcrumb">
                <li class="breadcrumb-item">
                    <a href="index.php"><i class="fas fa-home me-1"></i>Home</a>
                </li>
                <li class="breadcrumb-item active">Shopping Cart</li>
            </ol>
        </nav>
    </div>

    <!-- Cart Header -->
    <div class="cart-header">
        <div class="container-fluid">
            <h1 class="display-5 mb-2">
                <i class="fas fa-shopping-cart me-3"></i>Shopping Cart
            </h1>
            <p class="lead text-muted">
                <?= $cartCount ?> <?= $cartCount === 1 ? 'item' : 'items' ?> in your cart
            </p>
        </div>
    </div>

    <div class="container-fluid py-4">
        <?php if (empty($cartItems)): ?>
            <!-- Empty Cart -->
            <div class="empty-cart text-center py-5">
                <i class="fas fa-shopping-cart fa-5x text-muted mb-4"></i>
                <h3>Your cart is empty</h3>
                <p class="text-muted mb-4">Add some books to get started!</p>
                <a href="book-list.php" class="btn btn-primary btn-lg">
                    <i class="fas fa-book me-2"></i>Browse Books
                </a>
            </div>
        <?php else: ?>
            <div class="row g-4">
                <!-- Cart Items -->
                <div class="col-lg-8">
                    <div class="cart-items">
                        <?php foreach ($cartItems as $item): ?>
                            <div class="cart-item" id="cart-item-<?= $item['id'] ?>">
                                <div class="row g-3 align-items-center">
                                    <!-- Book Cover -->
                                    <div class="col-md-2 col-3">
                                        <img src="<?= getBookCoverUrl((object)$item, $item['title'], $item['author']) ?>" 
                                             alt="<?= e($item['title']) ?>"
                                             class="img-fluid rounded"
                                             onerror="this.src='<?= getDummyBookCover($item['title'], $item['author'], 150, 200) ?>'">
                                    </div>

                                    <!-- Book Info -->
                                    <div class="col-md-5 col-9">
                                        <h5 class="mb-1">
                                            <a href="book-details.php?id=<?= $item['book_id'] ?>" class="text-decoration-none">
                                                <?= e($item['title']) ?>
                                            </a>
                                        </h5>
                                        <p class="text-muted mb-1">by <?= e($item['author']) ?></p>
                                        <small class="text-muted">
                                            <span class="badge bg-light text-dark"><?= e($item['category']) ?></span>
                                            <span class="ms-2"><?= $item['year'] ?></span>
                                        </small>
                                    </div>

                                    <!-- Quantity -->
                                    <div class="col-md-2 col-6">
                                        <div class="quantity-control">
                                            <button class="btn btn-sm btn-outline-secondary" 
                                                    onclick="updateQuantity(<?= $item['id'] ?>, <?= $item['quantity'] - 1 ?>)">
                                                <i class="fas fa-minus"></i>
                                            </button>
                                            <input type="number" 
                                                   class="form-control form-control-sm text-center" 
                                                   value="<?= $item['quantity'] ?>" 
                                                   min="1" 
                                                   max="10"
                                                   id="qty-<?= $item['id'] ?>"
                                                   onchange="updateQuantity(<?= $item['id'] ?>, this.value)">
                                            <button class="btn btn-sm btn-outline-secondary" 
                                                    onclick="updateQuantity(<?= $item['id'] ?>, <?= $item['quantity'] + 1 ?>)">
                                                <i class="fas fa-plus"></i>
                                            </button>
                                        </div>
                                    </div>

                                    <!-- Price -->
                                    <div class="col-md-2 col-4">
                                        <div class="text-end">
                                            <h5 class="mb-0 text-primary">
                                                $<?= number_format($item['price'] * $item['quantity'], 2) ?>
                                            </h5>
                                            <small class="text-muted">$<?= number_format($item['price'], 2) ?> each</small>
                                        </div>
                                    </div>

                                    <!-- Remove Button -->
                                    <div class="col-md-1 col-2">
                                        <button class="btn btn-sm btn-outline-danger" 
                                                onclick="removeItem(<?= $item['id'] ?>)"
                                                title="Remove from cart">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>

                    <!-- Continue Shopping -->
                    <div class="mt-4">
                        <a href="book-list.php" class="btn btn-outline-primary">
                            <i class="fas fa-arrow-left me-2"></i>Continue Shopping
                        </a>
                    </div>
                </div>

                <!-- Cart Summary -->
                <div class="col-lg-4">
                    <div class="cart-summary sticky-top" style="top: 100px;">
                        <div class="card border-0 shadow-sm">
                            <div class="card-body p-4">
                                <h4 class="mb-4">Order Summary</h4>

                                <div class="summary-row">
                                    <span>Subtotal (<?= $cartCount ?> items)</span>
                                    <span id="subtotal">$<?= number_format($cartTotal, 2) ?></span>
                                </div>

                                <div class="summary-row">
                                    <span>Shipping</span>
                                    <span class="text-success">FREE</span>
                                </div>

                                <div class="summary-row">
                                    <span>Tax</span>
                                    <span>$0.00</span>
                                </div>

                                <hr>

                                <div class="summary-row total">
                                    <strong>Total</strong>
                                    <strong class="text-primary" id="total">$<?= number_format($cartTotal, 2) ?></strong>
                                </div>

                                <button class="btn btn-primary btn-lg w-100 mt-4" onclick="proceedToCheckout()">
                                    <i class="fas fa-lock me-2"></i>Proceed to Checkout
                                </button>

                                <div class="text-center mt-3">
                                    <small class="text-muted">
                                        <i class="fas fa-shield-alt me-1"></i>Secure checkout
                                    </small>
                                </div>
                            </div>
                        </div>

                        <!-- Promo Code (Optional) -->
                        <div class="card border-0 shadow-sm mt-3">
                            <div class="card-body">
                                <h6 class="mb-3">Have a promo code?</h6>
                                <div class="input-group">
                                    <input type="text" class="form-control" placeholder="Enter code">
                                    <button class="btn btn-outline-secondary" type="button">Apply</button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        <?php endif; ?>
    </div>
</div>

<script>
// Update quantity
function updateQuantity(cartId, quantity) {
    quantity = parseInt(quantity);
    
    if (quantity < 1) {
        removeItem(cartId);
        return;
    }
    
    if (quantity > 10) {
        Swal.fire({
            icon: 'warning',
            title: 'Maximum Quantity',
            text: 'You can only order up to 10 copies of each book',
            confirmButtonColor: '#2e8a40'
        });
        return;
    }

    fetch('api/cart_update.php', {
        method: 'POST',
        headers: {'Content-Type': 'application/json'},
        body: JSON.stringify({ cart_id: cartId, quantity: quantity })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            location.reload();
        } else {
            Swal.fire({
                icon: 'error',
                title: 'Error',
                text: data.message,
                confirmButtonColor: '#2e8a40'
            });
        }
    })
    .catch(error => {
        console.error('Error:', error);
        Swal.fire({
            icon: 'error',
            title: 'Error',
            text: 'Failed to update cart',
            confirmButtonColor: '#2e8a40'
        });
    });
}

// Remove item
function removeItem(cartId) {
    Swal.fire({
        title: 'Remove Item?',
        text: 'Are you sure you want to remove this item from your cart?',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#d33',
        cancelButtonColor: '#6c757d',
        confirmButtonText: 'Yes, remove it',
        cancelButtonText: 'Cancel'
    }).then((result) => {
        if (result.isConfirmed) {
            fetch('api/cart_remove.php', {
                method: 'POST',
                headers: {'Content-Type': 'application/json'},
                body: JSON.stringify({ cart_id: cartId })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    Swal.fire({
                        icon: 'success',
                        title: 'Removed!',
                        text: 'Item removed from cart',
                        confirmButtonColor: '#2e8a40',
                        timer: 1500,
                        showConfirmButton: false
                    }).then(() => {
                        location.reload();
                    });
                } else {
                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: data.message,
                        confirmButtonColor: '#2e8a40'
                    });
                }
            });
        }
    });
}

// Proceed to checkout
function proceedToCheckout() {
    window.location.href = 'checkout.php';
}
</script>

<style>
.cart-container {
    min-height: 100vh;
    background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
}

.cart-header {
    background: linear-gradient(135deg, var(--primary-color), #34495e);
    color: white;
    padding: 40px 0;
    margin-bottom: 30px;
}

.cart-item {
    background: white;
    border-radius: 12px;
    padding: 20px;
    margin-bottom: 15px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.08);
    transition: all 0.3s ease;
}

.cart-item:hover {
    box-shadow: 0 4px 16px rgba(0,0,0,0.12);
}

.quantity-control {
    display: flex;
    align-items: center;
    gap: 8px;
}

.quantity-control input {
    width: 60px;
}

.cart-summary .summary-row {
    display: flex;
    justify-content: space-between;
    margin-bottom: 15px;
    font-size: 0.95rem;
}

.cart-summary .summary-row.total {
    font-size: 1.2rem;
    margin-top: 10px;
}

.empty-cart {
    background: white;
    border-radius: 15px;
    padding: 60px 20px;
}

[data-bs-theme="dark"] .cart-container {
    background: linear-gradient(135deg, #0f172a 0%, #1e293b 100%);
}

[data-bs-theme="dark"] .cart-item {
    background: #1e293b;
}

[data-bs-theme="dark"] .empty-cart {
    background: #1e293b;
}
</style>

<?php include 'views/footer.php'; ?>
