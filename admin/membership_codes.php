<?php
// admin/membership_codes.php
require_once dirname(__DIR__) . '/vendor/autoload.php';
require_once dirname(__DIR__) . '/includes/sessions.php';
require_once dirname(__DIR__) . '/includes/env_loader.php';
require_once dirname(__DIR__) . '/includes/functions.php';
require_once dirname(__DIR__) . '/views/admin/layout.php';

use App\Auth;

if (!Auth::isAdmin()) {
    header("Location: ../index.php");
    exit;
}

$config = require dirname(__DIR__) . '/config/database.php';
$pdo = new PDO("mysql:host={$config['host']};dbname={$config['dbname']};charset={$config['charset']}", $config['username'], $config['password'], $config['options']);

// Process deletions
if (isset($_GET['delete'])) {
    $stmt = $pdo->prepare("DELETE FROM membership_codes WHERE id = ?");
    $stmt->execute([$_GET['delete']]);
    header("Location: membership_codes.php");
    exit;
}

// Generate new code
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['generate'])) {
    $tier = $_POST['tier'];
    $limit = (int)$_POST['limit'];
    
    // Generate an 8-character dash 8-character code
    $code = strtoupper(bin2hex(random_bytes(4)) . '-' . bin2hex(random_bytes(4)));
    
    $stmt = $pdo->prepare("INSERT INTO membership_codes (code, tier, usage_limit) VALUES (?, ?, ?)");
    $stmt->execute([$code, $tier, $limit]);
    
    $_SESSION['flash_message'] = ['text' => "Code $code generated successfully!", 'type' => 'success'];
    header("Location: membership_codes.php");
    exit;
}

// Fetch all codes with their usage counts
$stmt = $pdo->query("
    SELECT mc.*, 
    (SELECT COUNT(*) FROM membership_code_usage mcu WHERE mcu.code_id = mc.id) as current_usage
    FROM membership_codes mc
    ORDER BY mc.created_at DESC
");
$codes = $stmt->fetchAll(PDO::FETCH_ASSOC);

renderAdminLayout('Membership Key Management', function() use ($codes) {
    ?>
    <section class="premium-hero mb-5 rounded-5 overflow-hidden position-relative p-5 border border-light-subtle shadow-sm bg-lightest">
        <div class="hero-pattern position-absolute top-0 start-0 w-100 h-100 opacity-5" style="background-image: radial-gradient(#4e73df 1px, transparent 1px); background-size: 30px 30px; z-index: 2;"></div>
        <div class="position-relative" style="z-index: 3;">
            <div class="row align-items-center">
                <div class="col-lg-8 text-start">
                    <h3 class="text-dark fw-800 mb-2">Membership Key Hub</h3>
                    <p class="text-muted mb-4 fs-6 fw-500">Generate and distribute unique redemption codes for Silver, Gold, and Platinum access.</p>
                    <button class="btn btn-primary rounded-pill px-4 fw-bold shadow-sm" data-bs-toggle="modal" data-bs-target="#generateModal">
                        <i class="fas fa-plus-circle me-2"></i>Generate New Code
                    </button>
                </div>
                <div class="col-lg-4 d-none d-lg-block text-end">
                    <div class="bg-white rounded-circle shadow-sm d-inline-flex align-items-center justify-content-center" style="width: 140px; height: 140px;">
                        <i class="fas fa-ticket-alt text-primary" style="font-size: 3.5rem;"></i>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Key Directory -->
    <div class="card card-admin border-0 shadow-sm rounded-4 overflow-hidden mb-5">
        <div class="card-header bg-white py-4 border-0 px-4">
             <h5 class="mb-0 fw-800 text-dark">Active Keys</h5>
             <p class="text-muted smallest fw-bold mb-0 text-uppercase tracking-wider mt-1">MULTI-USER REDEMPTION TRACKER</p>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="bg-lightest border-bottom">
                        <tr>
                            <th class="py-3 px-4 border-0 text-muted smallest fw-800 text-uppercase tracking-wider">Redemption Code</th>
                            <th class="py-3 px-4 border-0 text-muted smallest fw-800 text-uppercase tracking-wider">Tier Type</th>
                            <th class="py-3 px-4 border-0 text-muted smallest fw-800 text-uppercase tracking-wider text-center">Usage Progress</th>
                            <th class="py-3 px-4 border-0 text-muted smallest fw-800 text-uppercase tracking-wider">Created</th>
                            <th class="py-3 px-4 border-0 text-muted smallest fw-800 text-uppercase tracking-wider text-end">Control</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($codes)): ?>
                        <tr><td colspan="5" class="py-5 text-center text-muted fw-bold">No keys generated yet.</td></tr>
                        <?php endif; ?>
                        <?php foreach ($codes as $c): 
                            $percent = ($c['current_usage'] / $c['usage_limit']) * 100;
                            $pColor = $percent >= 100 ? 'bg-danger' : ($percent >= 70 ? 'bg-warning' : 'bg-success');
                        ?>
                        <tr>
                            <td class="px-4 py-4">
                                <span class="badge bg-light text-dark border p-2 px-3 fw-800 font-monospace smallest tracking-wider"><?= $c['code'] ?></span>
                            </td>
                            <td class="px-4 py-4">
                                <span class="badge rounded-pill px-3 py-2 border smallest fw-800 text-uppercase tracking-wider 
                                    <?= $c['tier'] === 'silver' ? 'bg-silver-soft text-silver border-silver-subtle' : '' ?>
                                    <?= $c['tier'] === 'gold' ? 'bg-gold-soft text-gold border-gold-subtle' : '' ?>
                                    <?= $c['tier'] === 'platinum' ? 'bg-platinum-soft text-platinum border-platinum-subtle' : '' ?>
                                ">
                                    <?= e($c['tier']) ?>
                                </span>
                            </td>
                            <td class="px-4 py-4" style="min-width: 200px;">
                                <div class="d-flex align-items-center gap-3">
                                    <div class="progress flex-grow-1" style="height: 6px;">
                                        <div class="progress-bar <?= $pColor ?>" role="progressbar" style="width: <?= $percent ?>%"></div>
                                    </div>
                                    <span class="smallest fw-800 text-dark"><?= $c['current_usage'] ?> / <?= $c['usage_limit'] ?></span>
                                </div>
                            </td>
                            <td class="px-4 py-4">
                                <span class="text-muted smallest fw-bold d-block"><?= date('M d, Y', strtotime($c['created_at'])) ?></span>
                            </td>
                            <td class="px-4 py-4 text-end">
                                <a href="?delete=<?= $c['id'] ?>" class="btn btn-icon-sm rounded-circle btn-danger-soft" onclick="return confirm('Revoke this code immediately?')">
                                    <i class="fas fa-trash-alt"></i>
                                </a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Generate Modal -->
    <div class="modal fade" id="generateModal" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content border-0 rounded-4 shadow">
                <form action="" method="POST">
                    <div class="modal-body p-5">
                        <div class="text-center mb-4">
                            <div class="bg-primary-soft text-primary rounded-circle d-inline-flex align-items-center justify-content-center mb-3" style="width: 70px; height: 70px;">
                                <i class="fas fa-keyboard fs-3"></i>
                            </div>
                            <h4 class="fw-800 text-dark mb-1">Generate Access Key</h4>
                            <p class="text-muted smaller fw-bold">CREATE A NEW MULTI-USE REDEMPTION CODE</p>
                        </div>
                        
                        <div class="mb-4">
                            <label class="form-label smallest fw-800 text-muted text-uppercase tracking-wider">Select Membership Tier</label>
                            <select name="tier" class="form-select form-select-lg border-2 rounded-3 fw-bold" required>
                                <option value="silver">Silver Membership</option>
                                <option value="gold">Gold Premium</option>
                                <option value="platinum">Platinum VIP</option>
                            </select>
                        </div>
                        
                        <div class="mb-5">
                            <label class="form-label smallest fw-800 text-muted text-uppercase tracking-wider">Usage Limit (Max Different Users)</label>
                            <input type="number" name="limit" class="form-control form-control-lg border-2 rounded-3 fw-bold" value="5" min="1" max="1000" required>
                            <p class="smallest text-muted mt-2 opacity-75">Each user email can only redeem this code once.</p>
                        </div>
                        
                        <div class="row g-2">
                            <div class="col-6">
                                <button type="button" class="btn btn-light rounded-pill w-100 py-3 fw-bold" data-bs-dismiss="modal">Cancel</button>
                            </div>
                            <div class="col-6">
                                <button type="submit" name="generate" class="btn btn-primary rounded-pill w-100 py-3 fw-bold shadow-sm">Generate Key</button>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <style>
        .bg-lightest { background-color: #f8faff !important; }
        .bg-primary-soft { background-color: rgba(78, 115, 223, 0.08) !important; }
        
        /* Tier badges (re-using your styles) */
        .bg-silver-soft { background-color: rgba(189, 195, 199, 0.15) !important; }
        .text-silver { color: #7f8c8d !important; }
        .border-silver-subtle { border-color: rgba(189, 195, 199, 0.25) !important; }
        
        .bg-gold-soft { background-color: rgba(241, 196, 15, 0.1) !important; }
        .text-gold { color: #d4a017 !important; }
        .border-gold-subtle { border-color: rgba(241, 196, 15, 0.2) !important; }
        
        .bg-platinum-soft { background-color: rgba(30, 41, 59, 0.08) !important; }
        .text-platinum { color: #1e293b !important; }
        .border-platinum-subtle { border-color: rgba(30, 41, 59, 0.15) !important; }

        .btn-icon-sm { width: 36px; height: 36px; display: flex; align-items: center; justify-content: center; background: #fff; transition: all 0.2s; }
        .btn-danger-soft { color: #ef4444; border: 1px solid #fee2e2; }
        .btn-danger-soft:hover { background: #ef4444; color: #fff; }
    </style>
    <?php
});
