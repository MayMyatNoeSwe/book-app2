<?php
// membership.php
$pageTitle = "Membership Tiers";
require_once 'includes/sessions.php';
require_once 'vendor/autoload.php';
require_once 'includes/env_loader.php';
require_once 'includes/functions.php';

use App\Auth;

if (!Auth::check()) {
    header('Location: login.php?redirect=membership.php');
    exit;
}

$config = require 'config/database.php';
$dsn = "mysql:host={$config['host']};dbname={$config['dbname']};charset={$config['charset']}";
$pdo = new PDO($dsn, $config['username'], $config['password'], $config['options']);

$userId = Auth::id();
$stmt = $pdo->prepare("SELECT membership_tier FROM users WHERE id = ?");
$stmt->execute([$userId]);
$currentTier = strtolower($stmt->fetchColumn() ?: 'bronze');

$tiers = [
    'bronze' => [
        'name' => 'Bronze',
        'price' => 0,
        'color' => '#cd7f32',
        'gradient' => 'linear-gradient(135deg, #cd7f32, #8b4513)',
        'benefits' => [
            'Borrow Limit: ' . getSetting('borrow_limit', 3) . ' Books',
            'Duration: ' . getSetting('borrow_duration', 14) . ' Days',
            'Shopping: Standard Pricing',
            'Support: Standard'
        ]
    ],
    'silver' => [
        'name' => 'Silver',
        'price' => 10000,
        'color' => '#bdc3c7',
        'gradient' => 'linear-gradient(135deg, #bdc3c7, #2c3e50)',
        'benefits' => [
            'Borrow Limit: ' . getSetting('silver_borrow_limit', 3) . ' Books',
            'Duration: ' . getSetting('silver_borrow_duration', 14) . ' Days + Extension',
            'Shopping: 10% Discount',
            'Support: Standard'
        ]
    ],
    'gold' => [
        'name' => 'Gold',
        'price' => 25000,
        'color' => '#f1c40f',
        'gradient' => 'linear-gradient(135deg, #f1c40f, #f39c12)',
        'benefits' => [
            'Borrow Limit: ' . getSetting('gold_borrow_limit', 5) . ' Books',
            'Duration: ' . getSetting('gold_borrow_duration', 14) . ' Days + Extension',
            'Shopping: 20% Discount',
            'Support: Standard'
        ]
    ],
    'platinum' => [
        'name' => 'Platinum',
        'price' => 50000,
        'color' => '#1e293b',
        'gradient' => 'linear-gradient(135deg, #1e293b, #334155)',
        'benefits' => [
            'Borrow Limit: ' . getSetting('platinum_borrow_limit', 100) . ' Books',
            'Duration: ' . getSetting('platinum_borrow_duration', 30) . ' Days',
            'Shopping: 25% Disc + Free Ship',
            'Support: Priority'
        ]
    ]
];

include 'views/header.php';
?>

<style>
.ms-hero {
    padding: 60px 0;
    background: radial-gradient(circle at top right, rgba(224,122,95,0.08), transparent), var(--bookhouse-bg);
    text-align: center;
}
.ms-title { font-family: 'Playfair Display', serif; font-weight: 800; font-size: 3rem; margin-bottom: 15px; }
.ms-subtitle { color: var(--bookhouse-text-muted); font-size: 1.1rem; max-width: 600px; margin: 0 auto 40px; }

.ms-card {
    background: #fff; border-radius: 24px; border: 1px solid rgba(0,0,0,0.06);
    padding: 40px 30px; position: relative; overflow: hidden;
    transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    height: 100%; display: flex; flex-direction: column;
}
.ms-card:hover { transform: translateY(-10px); box-shadow: 0 20px 40px rgba(0,0,0,0.1); }
[data-bs-theme="dark"] .ms-card { background: #1e293b; border-color: rgba(255,255,255,0.06); }

.ms-card.active { border: 2px solid var(--bookhouse-orange); }

.tier-badge {
    position: absolute; top: 20px; right: -35px;
    background: var(--bookhouse-orange); color: #fff;
    padding: 5px 40px; transform: rotate(45deg);
    font-size: 10px; font-weight: 800; text-transform: uppercase;
}

.tier-icon {
    width: 60px; height: 60px; border-radius: 15px;
    display: flex; align-items: center; justify-content: center;
    font-size: 24px; color: #fff; margin-bottom: 25px;
}

.tier-name { font-weight: 800; font-size: 1.5rem; margin-bottom: 5px; color: var(--bookhouse-text); }
.tier-price { font-size: 2rem; font-weight: 800; color: var(--bookhouse-orange); margin-bottom: 25px; }
.tier-price span { font-size: 0.9rem; color: var(--bookhouse-text-muted); font-weight: 400; }

.benefit-list { list-style: none; padding: 0; margin: 0 0 30px; flex-grow: 1; }
.benefit-item { display: flex; align-items: center; gap: 12px; margin-bottom: 12px; font-size: 14px; color: var(--bookhouse-text-muted); }
.benefit-item i { color: #10b981; font-size: 14px; }

.btn-tier {
    width: 100%; padding: 12px; border-radius: 12px; border: none;
    font-weight: 800; transition: all 0.2s;
}
.btn-tier-outline { background: rgba(0,0,0,0.03); color: var(--bookhouse-text); border: 1px solid rgba(0,0,0,0.1); }
.btn-tier-outline:hover { background: rgba(0,0,0,0.06); }
.btn-tier-primary { background: var(--bookhouse-orange); color: #fff; box-shadow: 0 8px 16px rgba(224,122,95,0.3); }
.btn-tier-primary:hover { filter: brightness(1.1); transform: translateY(-2px); }

[data-bs-theme="dark"] .btn-tier-outline { background: rgba(255,255,255,0.05); color: #fff; border-color: rgba(255,255,255,0.1); }

/* --- Payment Modal Styles --- */
.pay-methods { display: flex; flex-direction: column; gap: 12px; margin-top: 20px; }
.pay-method-item {
    display: flex; align-items: center; justify-content: space-between;
    padding: 16px; border: 1.5px solid rgba(0,0,0,0.08); border-radius: 12px;
    cursor: pointer; transition: all 0.2s; background: #fff;
}
.pay-method-item:hover { border-color: var(--bookhouse-orange); background: rgba(224,122,95,0.04); }
.pay-method-item.selected { border-color: var(--bookhouse-orange); background: rgba(224,122,95,0.08); }
.pay-method-info { display: flex; align-items: center; gap: 12px; font-weight: 700; color: var(--bookhouse-text); }
.pay-method-icon { width: 40px; height: 40px; border-radius: 8px; overflow: hidden; display: flex; align-items: center; justify-content: center; background: #f8fafc; font-size: 20px; }
.pay-method-item i.select-icon { color: var(--bookhouse-text-muted); font-size: 16px; }
.pay-method-item.selected i.select-icon { color: var(--bookhouse-orange); }

[data-bs-theme="dark"] .pay-method-item { background: rgba(255,255,255,0.05); border-color: rgba(255,255,255,0.1); }
[data-bs-theme="dark"] .pay-method-info { color: #fff; }
</style>

<section class="ms-hero">
    <div class="container text-center">
        <h1 class="ms-title">Elevate Your Reading</h1>
        <p class="ms-subtitle">Unlock exclusive privileges, higher borrow limits, and special discounts with our premium membership tiers.</p>
    </div>
</section>

<div class="container pb-5">
    <div class="row g-4">
        <?php 
        $currentTierPrice = $tiers[$currentTier]['price'] ?? 0;
        foreach ($tiers as $key => $tier): 
            $isActive = ($currentTier === $key);
            $isUpgrade = ($tier['price'] > $currentTierPrice); 
        ?>
        <div class="col-lg-3 col-md-6">
            <div class="ms-card <?= $isActive ? 'active' : '' ?>">
                <?php if ($isActive): ?>
                    <div class="tier-badge">Current</div>
                <?php endif; ?>

                <div class="tier-icon" style="background: <?= $tier['gradient'] ?>">
                    <i class="fas fa-crown"></i>
                </div>
                
                <h3 class="tier-name"><?= $tier['name'] ?></h3>
                <div class="tier-price">
                    <?= $tier['price'] === 0 ? 'Free' : number_format($tier['price']) ?> 
                    <?php if ($tier['price'] > 0): ?><span>Ks/mo</span><?php endif; ?>
                </div>

                <ul class="benefit-list">
                    <?php foreach ($tier['benefits'] as $benefit): ?>
                        <li class="benefit-item">
                            <i class="fas fa-check-circle"></i>
                            <?= $benefit ?>
                        </li>
                    <?php endforeach; ?>
                </ul>

                <?php if ($isActive): ?>
                    <button class="btn btn-tier btn-tier-outline" disabled>Active Membership</button>
                <?php elseif ($isUpgrade): ?>
                    <button class="btn btn-tier btn-tier-primary" 
                            onclick="upgradeTier('<?= $key ?>', '<?= $tier['name'] ?>', <?= $tier['price'] ?>)">
                        Upgrade Now
                    </button>
                <?php else: ?>
                    <!-- Cannot join lower tiers if higher already active -->
                    <button class="btn btn-tier btn-tier-outline" disabled style="opacity: 0.5;">Higher Tier Active</button>
                <?php endif; ?>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
</div>

<script>
function upgradeTier(tierKey, tierName, price) {
    if (price === 0) {
        confirmUpgrade(tierKey, tierName, 'Free');
        return;
    }

    const priceFormatted = price.toLocaleString();

    Swal.fire({
        title: 'Upgrade to ' + tierName,
        html: `
            <div class="text-center mb-4">Select your preferred payment method:</div>
            <div class="pay-methods">
                <div class="pay-method-item selected" onclick="selectPay(this, 'kbz')">
                    <div class="pay-method-info">
                        <div class="pay-method-icon" style="background:#004692; color:#fff;"><i class="fas fa-wallet"></i></div>
                        <span>KBZ Pay</span>
                    </div>
                    <i class="fas fa-check-circle select-icon"></i>
                </div>
                <div class="pay-method-item" onclick="selectPay(this, 'wave')">
                    <div class="pay-method-info">
                        <div class="pay-method-icon" style="background:#f9ce1d; color:#e11d48;"><i class="fas fa-mobile-alt"></i></div>
                        <span>Wave Pay</span>
                    </div>
                    <i class="fas fa-circle select-icon opacity-25"></i>
                </div>
            </div>
        `,
        showCancelButton: true,
        confirmButtonColor: '#E07A5F',
        confirmButtonText: 'Next: Scan & Pay',
        cancelButtonText: 'Cancel',
        reverseButtons: true,
    }).then((result) => {
        if (result.isConfirmed) {
            const method = document.querySelector('.pay-method-item.selected span').innerText;
            showScanModal(tierKey, tierName, priceFormatted, method);
        }
    });
}

function selectPay(el, method) {
    document.querySelectorAll('.pay-method-item').forEach(item => {
        item.classList.remove('selected');
        item.querySelector('.select-icon').className = 'fas fa-circle select-icon opacity-25';
    });
    el.classList.add('selected');
    el.querySelector('.select-icon').className = 'fas fa-check-circle select-icon';
}

function showScanModal(tierKey, tierName, priceStr, method) {
    const qrSrc = method === 'KBZ Pay' ? 'assets/img/qr/kbz_qr.png' : 'assets/img/qr/kbz_qr.png'; // Use same dummy for now

    Swal.fire({
        title: 'Scan to Pay: ' + priceStr + ' Ks',
        html: `
            <div class="text-center">
                <p class="mb-3 text-muted">Please scan the QR code using your ${method} app and pay the amount.</p>
                <div class="mx-auto border p-2 rounded mb-4" style="width:200px; height:200px; background:#f8fafc;">
                    <img src="${qrSrc}" class="img-fluid" alt="Payment QR">
                </div>
                <div class="mb-4">
                    <label class="form-label d-block text-start fw-bold" style="font-size:13px;">Upload Screenshot (Payment Receipt)</label>
                    <input type="file" id="pay-screenshot" class="form-control" accept="image/*">
                </div>
                <div class="alert alert-info py-2" style="font-size:12px;">
                    <i class="fas fa-info-circle me-1"></i> Our admin will verify your payment within 1-2 hours.
                </div>
            </div>
        `,
        showCancelButton: true,
        confirmButtonColor: '#E07A5F',
        confirmButtonText: 'Submit Request',
        reverseButtons: true,
        preConfirm: () => {
            const fileInput = document.getElementById('pay-screenshot');
            if (fileInput.files.length === 0) {
                Swal.showValidationMessage('Please upload a payment screenshot');
                return false;
            }
            return fileInput.files[0];
        }
    }).then((result) => {
        if (result.isConfirmed) {
            submitMembershipRequest(tierKey, method, result.value);
        }
    });
}

function submitMembershipRequest(tier, method, file) {
    Swal.fire({
        title: 'Uploading Receipt...',
        allowOutsideClick: false,
        didOpen: () => { Swal.showLoading(); }
    });

    const formData = new FormData();
    formData.append('tier', tier);
    formData.append('payment_method', method);
    formData.append('screenshot', file);

    fetch('api/membership_request.php', {
        method: 'POST',
        body: formData
    })
    .then(res => res.json())
    .then(data => {
        if (data.success) {
            Swal.fire({
                icon: 'success',
                title: 'Request Sent!',
                text: data.message,
                confirmButtonColor: '#E07A5F'
            });
        } else {
            Swal.fire({ icon: 'error', title: 'Error', text: data.message });
        }
    })
    .catch(() => {
        Swal.fire({ icon: 'error', title: 'Error', text: 'Connection failed. Please try again.' });
    });
}

// Keep the old confirmUpgrade for Free tiers
function confirmUpgrade(tierKey, tierName) {
    Swal.fire({
        title: 'Processing...',
        allowOutsideClick: false,
        didOpen: () => { Swal.showLoading(); }
    });

    fetch('api/membership_upgrade.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ tier: tierKey })
    })
    .then(res => res.json())
    .then(data => {
        if (data.success) {
            Swal.fire({
                icon: 'success',
                title: 'Welcome to ' + tierName + '!',
                text: 'Your account has been upgraded.',
                confirmButtonColor: '#E07A5F'
            }).then(() => { window.location.reload(); });
        } else {
            Swal.fire({ icon: 'error', title: 'Error', text: data.message });
        }
    });
}
</script>

<?php include 'views/footer.php'; ?>
