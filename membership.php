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
            'Borrow Limit: 3 Books',
            'Duration: 14 Days',
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
            'Borrow Limit: 3 Books',
            'Duration: 14 Days + Extension',
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
            'Borrow Limit: 5 Books',
            'Duration: 14 Days + Extension',
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
            'Borrow Limit: Unlimited',
            'Duration: Flexible',
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
</style>

<section class="ms-hero">
    <div class="container text-center">
        <h1 class="ms-title">Elevate Your Reading</h1>
        <p class="ms-subtitle">Unlock exclusive privileges, higher borrow limits, and special discounts with our premium membership tiers.</p>
    </div>
</section>

<div class="container pb-5">
    <div class="row g-4">
        <?php foreach ($tiers as $key => $tier): 
            $isActive = ($currentTier === $key);
            $isHigher = ($tier['price'] > 0); // Logic for upgradeability
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
                <?php else: ?>
                    <button class="btn btn-tier <?= $isHigher ? 'btn-tier-primary' : 'btn-tier-outline' ?>" 
                            onclick="upgradeTier('<?= $key ?>', '<?= $tier['name'] ?>', <?= $tier['price'] ?>)">
                        <?= $isHigher ? 'Upgrade Now' : 'Join Membership' ?>
                    </button>
                <?php endif; ?>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
</div>

<script>
function upgradeTier(tierKey, tierName, price) {
    Swal.fire({
        title: 'Upgrade to ' + tierName + '?',
        text: price > 0 ? 'This will cost ' + price.toLocaleString() + ' Ks per month.' : 'Do you want to switch to ' + tierName + ' membership?',
        icon: 'question',
        showCancelButton: true,
        confirmButtonColor: '#E07A5F',
        cancelButtonColor: '#6c757d',
        confirmButtonText: 'Yes, Upgrade!',
        reverseButtons: true
    }).then((result) => {
        if (result.isConfirmed) {
            Swal.fire({
                title: 'Processing Upgrade...',
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
                        text: 'Your account has been upgraded successfully.',
                        confirmButtonColor: '#E07A5F'
                    }).then(() => {
                        window.location.reload();
                    });
                } else {
                    Swal.fire({ icon: 'error', title: 'Error', text: data.message });
                }
            })
            .catch(err => {
                Swal.fire({ icon: 'error', title: 'Error', text: 'Something went wrong. Please try again later.' });
            });
        }
    });
}
</script>

<?php include 'views/footer.php'; ?>
