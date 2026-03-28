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

// REDEEM CODE HANDLER
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['redeem_code'])) {
    $code = strtoupper(trim($_POST['code'] ?? ''));
    $lib = new \App\Library($pdo);
    $res = $lib->redeemMembershipCode($userId, $code);
    
    if ($res['success']) {
        $_SESSION['flash_message'] = ['text' => "SUCCESS! You've successfully redeemed a " . strtoupper($res['tier']) . " membership.", 'type' => 'success'];
    } else {
        $_SESSION['flash_message'] = ['text' => $res['message'], 'type' => 'danger'];
    }
    header("Location: membership.php");
    exit;
}
$subRecords = Auth::getSubscriptions(); // [[tier => ..., expires_at => ...]]
$activeTiers = [];
foreach($subRecords as $sr) {
    if (!isset($activeTiers[$sr['tier']])) {
        $activeTiers[$sr['tier']] = ['expiry' => $sr['expires_at'], 'count' => 0];
    }
    if (strtotime($sr['expires_at']) > strtotime($activeTiers[$sr['tier']]['expiry'])) {
        $activeTiers[$sr['tier']]['expiry'] = $sr['expires_at'];
    }
    $activeTiers[$sr['tier']]['count']++;
}
$currentTier = strtolower($pdo->query("SELECT membership_tier FROM users WHERE id = $userId")->fetchColumn() ?: 'bronze');

// Fetch all approved requests with codes for this user
$stmt = $pdo->prepare("SELECT tier, redeem_code, created_at FROM membership_requests WHERE user_id = ? AND status = 'approved' AND redeem_code IS NOT NULL ORDER BY id DESC");
$stmt->execute([$userId]);
$releasedCodes = $stmt->fetchAll(PDO::FETCH_ASSOC);

$tiers = [
    'bronze' => [
        'name' => 'Bronze',
        'price' => 0,
        'color' => '#cd7f32',
        'gradient' => 'linear-gradient(135deg, #cd7f32, #8b4513)',
        'benefits' => [
            'Borrow Limit: ' . getSetting('borrow_limit', 3) . ' Books',
            'Duration: ' . getSetting('borrow_duration', 14) . ' Days',
            'Shopping: ' . getSetting('bronze_discount', 'Standard Pricing'),
            'Support: ' . getSetting('bronze_support', 'Standard')
        ]
    ],
    'silver' => [
        'name' => 'Silver',
        'price' => (int)getSetting('silver_price', 10000),
        'color' => '#bdc3c7',
        'gradient' => 'linear-gradient(135deg, #bdc3c7, #2c3e50)',
        'benefits' => [
            'Borrow Limit: ' . getSetting('silver_borrow_limit', 10) . ' Books',
            'Duration: ' . getSetting('silver_borrow_duration', 14) . ' Days',
            'Shopping: ' . getSetting('silver_discount', '10% Discount'),
            'Support: ' . getSetting('silver_support', 'Standard')
        ]
    ],
    'gold' => [
        'name' => 'Gold',
        'price' => (int)getSetting('gold_price', 25000),
        'color' => '#f1c40f',
        'gradient' => 'linear-gradient(135deg, #f1c40f, #f39c12)',
        'benefits' => [
            'Borrow Limit: ' . getSetting('gold_borrow_limit', 50) . ' Books',
            'Duration: ' . getSetting('gold_borrow_duration', 30) . ' Days',
            'Shopping: ' . getSetting('gold_discount', '20% Discount'),
            'Support: ' . getSetting('gold_support', 'Standard')
        ]
    ],
    'platinum' => [
        'name' => 'Platinum',
        'price' => (int)getSetting('platinum_price', 50000),
        'color' => '#1e293b',
        'gradient' => 'linear-gradient(135deg, #1e293b, #334155)',
        'benefits' => [
            'Borrow Limit: ' . getSetting('platinum_borrow_limit', 100) . ' Books',
            'Duration: ' . getSetting('platinum_borrow_duration', 60) . ' Days',
            'Shopping: ' . getSetting('platinum_discount', '25% Disc + Free Ship'),
            'Support: ' . getSetting('platinum_support', 'Priority')
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
        
        <!-- Released Keys Notification (If any) -->
        <?php if (!empty($releasedCodes)): ?>
            <div class="row justify-content-center mb-4 text-start">
                <div class="col-lg-6">
                    <div class="p-4 rounded-5 border-0 shadow-sm bg-white border-start border-primary border-5">
                        <h6 class="fw-800 text-dark mb-3 small"><i class="fas fa-bell text-primary me-2"></i>Approved Membership Keys</h6>
                        <div class="row g-2">
                            <?php foreach ($releasedCodes as $rc): ?>
                                <div class="col-12">
                                    <div class="p-2 px-3 rounded-4 bg-lightest border d-flex justify-content-between align-items-center">
                                        <div>
                                            <span class="smallest fw-800 text-uppercase text-muted opacity-75 d-block mb-1"><?= $rc['tier'] ?> PLAN</span>
                                            <span class="fw-800 font-monospace text-primary tracking-widest fs-5"><?= $rc['redeem_code'] ?></span>
                                        </div>
                                        <button class="btn btn-sm btn-soft-primary rounded-pill px-3 copy-btn" data-code="<?= $rc['redeem_code'] ?>">
                                            <i class="fas fa-copy pe-1"></i>Copy
                                        </button>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                        <p class="smallest text-muted mt-3 mb-0 fw-bold opacity-75 italic">* COPY AND REDEEM THE CODE BELOW TO ACTIVATE YOUR PASS.</p>
                    </div>
                </div>
            </div>
        <?php endif; ?>

        <!-- Redeem Key Section -->
        <div class="row justify-content-center mb-5">
            <div class="col-lg-6">
                <div class="card border-0 shadow-sm rounded-5 overflow-hidden p-3" style="background: linear-gradient(135deg, rgba(78, 115, 223, 0.05), #ffffff); border: 1px solid rgba(78, 115, 223, 0.1) !important;">
                    <div class="card-body">
                        <div class="row align-items-center">
                            <div class="col-md-5 text-md-start mb-3 mb-md-0">
                                <h6 class="fw-800 text-dark mb-1">Redeem a Key</h6>
                                <p class="text-muted smallest fw-bold text-uppercase mb-0 opacity-75 small">HAVE A PRE-PAID CODE?</p>
                            </div>
                            <div class="col-md-7">
                                <form action="membership.php" method="POST" class="d-flex gap-2">
                                    <input type="text" name="code" class="form-control border-light-subtle rounded-pill px-3 fw-800 text-uppercase" placeholder="ENTER KEY" required>
                                    <button type="submit" name="redeem_code" class="btn btn-primary rounded-pill px-4 fw-bold shadow-sm">REDEEM</button>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<div class="container pb-5">
    <div class="row g-4">
        <?php 
        $currentTierPrice = $tiers[$currentTier]['price'] ?? 0;
        foreach ($tiers as $key => $tier): 
            $tierData = $activeTiers[$key] ?? null;
            $isActive = $tierData || ($key === 'bronze' && empty($activeTiers));
            $expiry = $tierData['expiry'] ?? null;
            $count = $tierData['count'] ?? 0;
            $isPrimary = ($currentTier === $key);
        ?>
        <div class="col-lg-3 col-md-6 text-start">
            <div class="ms-card <?= $isActive ? 'active' : '' ?>">
                <?php if ($isPrimary): ?>
                    <div class="tier-badge">Primary</div>
                <?php elseif ($isActive): ?>
                    <div class="tier-badge" style="background: #22c55e;">Active</div>
                <?php endif; ?>

                <?php if ($isActive && $expiry && $key !== 'bronze'): ?>
                    <div class="small fw-bold text-success mb-2" style="font-size: 11px;">
                        <i class="fas fa-clock me-1"></i> Max Expire: <?= date('M j, Y', strtotime($expiry)) ?>
                        <?php if ($count > 1): ?>
                            <span class="badge bg-primary ms-1"><?= $count ?> Purchased</span>
                        <?php endif; ?>
                    </div>
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

                <?php if ($key === 'bronze'): ?>
                    <button class="btn btn-tier btn-tier-outline" disabled>Default Plan</button>
                <?php else: ?>
                    <button class="btn btn-tier btn-tier-primary" 
                            onclick="upgradeTier('<?= $key ?>', '<?= $tier['name'] ?>', <?= $tier['price'] ?>)">
                        <?= $isActive ? 'Buy Again' : 'Join Now' ?>
                    </button>
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

// Copy Code Helper
document.querySelectorAll('.copy-btn').forEach(btn => {
    btn.addEventListener('click', function() {
        const code = this.dataset.code;
        navigator.clipboard.writeText(code).then(() => {
            const originalText = this.innerHTML;
            this.innerHTML = '<i class="fas fa-check"></i> Copied';
            this.classList.replace('btn-soft-primary', 'btn-success');
            setTimeout(() => {
                this.innerHTML = originalText;
                this.classList.replace('btn-success', 'btn-soft-primary');
            }, 2000);
        });
    });
});
</script>

<?php include 'views/footer.php'; ?>
