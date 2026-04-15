<?php
// admin/settings.php
require_once dirname(__DIR__) . '/vendor/autoload.php';
require_once dirname(__DIR__) . '/includes/sessions.php';
require_once dirname(__DIR__) . '/includes/env_loader.php';
require_once dirname(__DIR__) . '/includes/functions.php';
require_once dirname(__DIR__) . '/views/admin/layout.php';

use App\Library;

$library = new Library();
$pdo = $library->getPdo();

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] === 'update_general') {
        setSetting('site_name', $_POST['site_name'] ?? '');
        setSetting('contact_email', $_POST['contact_email'] ?? '');
        setSetting('library_address', $_POST['library_address'] ?? '');
        setSetting('currency', $_POST['currency'] ?? 'MMK');
        setFlashMessage('General settings updated successfully.', 'success');
        redirect(baseUrl() . '/admin/settings.php#general');
    } elseif ($_POST['action'] === 'update_library') {
        // Global defaults
        setSetting('borrow_limit', $_POST['borrow_limit'] ?? '3');
        setSetting('borrow_duration', $_POST['borrow_duration'] ?? '14');
        setSetting('fine_per_day', $_POST['fine_per_day'] ?? '500');
        
        // Tier specific overrides
        $tiers = ['silver', 'gold', 'platinum'];
        foreach ($tiers as $tier) {
            setSetting($tier . '_borrow_limit', $_POST[$tier . '_borrow_limit'] ?? $_POST['borrow_limit']);
            setSetting($tier . '_borrow_duration', $_POST[$tier . '_borrow_duration'] ?? $_POST['borrow_duration']);
            setSetting($tier . '_fine_per_day', $_POST[$tier . '_fine_per_day'] ?? $_POST['fine_per_day']);
            setSetting($tier . '_price', $_POST[$tier . '_price'] ?? '0');
            setSetting($tier . '_share_limit', $_POST[$tier . '_share_limit'] ?? '5');
        }

        setFlashMessage('Library member card policies updated successfully.', 'success');
        redirect(baseUrl() . '/admin/settings.php#library');
    } elseif ($_POST['action'] === 'update_preferences') {
        setSetting('maintenance_mode', isset($_POST['maintenance_mode']) ? '1' : '0');
        setSetting('allow_registration', isset($_POST['allow_registration']) ? '1' : '0');
        setFlashMessage('Application preferences updated.', 'success');
        redirect(baseUrl() . '/admin/settings.php#prefs');
    } elseif ($_POST['action'] === 'change_password') {
        $current = $_POST['current_password'] ?? '';
        $new = $_POST['new_password'] ?? '';
        $confirm = $_POST['confirm_password'] ?? '';

        if ($new !== $confirm) {
            setFlashMessage('New passwords do not match.', 'danger');
        } else {
            $userId = $_SESSION['user_id'];
            $stmt = $pdo->prepare("SELECT password FROM users WHERE id = ?");
            $stmt->execute([$userId]);
            $hashed = $stmt->fetchColumn();

            if (password_verify($current, $hashed)) {
                $newHash = password_hash($new, PASSWORD_DEFAULT);
                $stmt = $pdo->prepare("UPDATE users SET password = ? WHERE id = ?");
                $stmt->execute([$newHash, $userId]);
                setFlashMessage('Password changed successfully.', 'success');
            } else {
                setFlashMessage('Incorrect current password.', 'danger');
            }
        }
        redirect(baseUrl() . '/admin/settings.php#security');
    }
}

renderAdminLayout('System Settings', function() {
    ?>
    <div class="row g-4">
        <div class="col-lg-3">
            <div class="card border-0 shadow-sm rounded-4 bg-white overflow-hidden">
                <div class="list-group list-group-flush settings-nav">
                    <a href="#general" class="list-group-item list-group-item-action border-0 py-3 active" data-bs-toggle="list">
                        <i class="fas fa-cog me-2"></i> General Settings
                    </a>
                    <a href="#library" class="list-group-item list-group-item-action border-0 py-3" data-bs-toggle="list">
                        <i class="fas fa-book-reader me-2"></i> Library Rules
                    </a>
                    <a href="#prefs" class="list-group-item list-group-item-action border-0 py-3" data-bs-toggle="list">
                        <i class="fas fa-sliders-h me-2"></i> Preferences
                    </a>
                    <a href="#security" class="list-group-item list-group-item-action border-0 py-3" data-bs-toggle="list">
                        <i class="fas fa-shield-alt me-2"></i> Security & Privacy
                    </a>
                </div>
            </div>
        </div>

        <div class="col-lg-9">
            <div class="tab-content border-0">
                <!-- General Settings -->
                <div class="tab-pane fade show active" id="general">
                    <div class="card border-0 shadow-sm rounded-4 bg-white p-4">
                        <h5 class="fw-800 text-dark mb-4">General Configuration</h5>
                        <form action="" method="POST">
                            <input type="hidden" name="action" value="update_general">
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label class="form-label smallest fw-bold text-uppercase text-muted">Library Site Name</label>
                                    <input type="text" name="site_name" class="form-control rounded-3" value="<?= e(getSetting('site_name', 'BookHouse')) ?>">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label smallest fw-bold text-uppercase text-muted">Contact Email Address</label>
                                    <input type="email" name="contact_email" class="form-control rounded-3" value="<?= e(getSetting('contact_email', 'admin@example.com')) ?>">
                                </div>
                                <div class="col-12">
                                    <label class="form-label smallest fw-bold text-uppercase text-muted">Physical Address</label>
                                    <textarea name="library_address" class="form-control rounded-3" rows="2"><?= e(getSetting('library_address', '')) ?></textarea>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label smallest fw-bold text-uppercase text-muted">Application Currency</label>
                                    <select name="currency" class="form-select rounded-3">
                                        <option value="MMK" <?= getSetting('currency') === 'MMK' ? 'selected' : '' ?>>Myanmar Kyat (MMK)</option>
                                        <option value="USD" <?= getSetting('currency') === 'USD' ? 'selected' : '' ?>>US Dollar (USD)</option>
                                        <option value="EUR" <?= getSetting('currency') === 'EUR' ? 'selected' : '' ?>>Euro (EUR)</option>
                                    </select>
                                </div>
                            </div>
                            <div class="mt-4 pt-3 border-top">
                                <button type="submit" class="btn btn-primary px-4 fw-bold">Save Changes</button>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Library Rules -->
                <div class="tab-pane fade" id="library">
                    <div class="card border-0 shadow-sm rounded-4 bg-white p-4">
                        <div class="d-flex justify-content-between align-items-center mb-4">
                            <h5 class="fw-800 text-dark mb-0">Library Rules & Tier Policies</h5>
                            <span class="badge bg-indigo-soft text-indigo fw-bold p-2 px-3 rounded-pill"><i class="fas fa-crown me-2"></i>Multi-Tier Enabled</span>
                        </div>
                        
                        <form action="" method="POST">
                            <input type="hidden" name="action" value="update_library">
                            
                            <!-- Default Global Rules -->
                            <div class="p-4 bg-lightest rounded-4 mb-5 border border-light">
                                <h6 class="fw-800 text-muted smallest text-uppercase mb-3">Default Global Rules (For Guest/Bronze)</h6>
                                <div class="row g-4">
                                    <div class="col-12">
                                        <div class="row align-items-center">
                                            <div class="col-md-4">
                                                <label class="form-label smallest fw-800 text-muted text-uppercase mb-0">Max Books per User</label>
                                                <p class="smallest text-muted opacity-75 mb-md-0">Standard borrowing limit</p>
                                            </div>
                                            <div class="col-md-8">
                                                <input type="number" name="borrow_limit" class="form-control form-control-lg rounded-3 border-light shadow-none" value="<?= e(getSetting('borrow_limit', 3)) ?>">
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-12 border-top pt-3 border-light-subtle">
                                        <div class="row align-items-center">
                                            <div class="col-md-4">
                                                <label class="form-label smallest fw-800 text-muted text-uppercase mb-0">Duration (Days)</label>
                                                <p class="smallest text-muted opacity-75 mb-md-0">Days until book is overdue</p>
                                            </div>
                                            <div class="col-md-8">
                                                <input type="number" name="borrow_duration" class="form-control form-control-lg rounded-3 border-light shadow-none" value="<?= e(getSetting('borrow_duration', 14)) ?>">
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-12 border-top pt-3 border-light-subtle">
                                        <div class="row align-items-center">
                                            <div class="col-md-4">
                                                <label class="form-label smallest fw-800 text-muted text-uppercase mb-0">Overdue Fine (Per Day)</label>
                                                <p class="smallest text-muted opacity-75 mb-md-0">Daily penalty fee in Ks</p>
                                            </div>
                                            <div class="col-md-8">
                                                <input type="number" name="fine_per_day" class="form-control form-control-lg rounded-3 border-light shadow-none" value="<?= e(getSetting('fine_per_day', 500)) ?>">
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Tier Specific Overrides -->
                            <h6 class="fw-800 text-dark smallest text-uppercase mb-4">Tier Specific Overrides (Privileges)</h6>
                            <div class="row g-4">
                                <?php 
                                $tiers = [
                                    ['key' => 'silver', 'name' => 'Silver Member', 'icon' => 'medal', 'color' => '#94a3b8'],
                                    ['key' => 'gold', 'name' => 'Gold Member', 'icon' => 'crown', 'color' => '#f59e0b'],
                                    ['key' => 'platinum', 'name' => 'Platinum Member', 'icon' => 'gem', 'color' => '#6366f1']
                                ];
                                foreach ($tiers as $tier):
                                ?>
                                <div class="col-12">
                                    <div class="card border-0 shadow-sm rounded-4 overflow-hidden mb-3">
                                        <div class="card-header bg-white border-bottom border-light p-3 d-flex align-items-center justify-content-between">
                                            <div class="d-flex align-items-center gap-3">
                                                <div class="rounded-circle d-flex align-items-center justify-content-center shadow-sm" style="width:38px; height:38px; background:<?= $tier['color'] ?>15; color:<?= $tier['color'] ?>;">
                                                    <i class="fas fa-<?= $tier['icon'] ?>"></i>
                                                </div>
                                                <h6 class="fw-900 text-dark mb-0"><?= $tier['name'] ?> Policies</h6>
                                            </div>
                                            <span class="badge bg-lightest text-muted border px-2 py-1 smallest fw-800 rounded-pill">CONFIGURABLE</span>
                                        </div>
                                        <div class="card-body p-4 bg-white">
                                            <div class="row g-4">
                                                <div class="col-md-4">
                                                    <label class="form-label smallest fw-800 text-muted text-uppercase mb-1">Monthly Price (KS)</label>
                                                    <input type="number" name="<?= $tier['key'] ?>_price" class="form-control rounded-3 border-light shadow-none" value="<?= getSetting($tier['key'].'_price', ($tier['key'] === 'silver' ? 5000 : ($tier['key'] === 'gold' ? 12000 : 25000))) ?>">
                                                </div>
                                                <div class="col-md-4">
                                                    <label class="form-label smallest fw-800 text-muted text-uppercase mb-1">Max Shared Members</label>
                                                    <input type="number" name="<?= $tier['key'] ?>_share_limit" class="form-control rounded-3 border-light shadow-none" value="<?= getSetting($tier['key'].'_share_limit', 5) ?>">
                                                </div>
                                                <div class="col-md-4 d-none">
                                                    <!-- Managed by plan owners -->
                                                </div>
                                                <div class="col-md-4">
                                                    <label class="form-label smallest fw-800 text-muted text-uppercase mb-1">Group Total Cap</label>
                                                    <input type="number" name="<?= $tier['key'] ?>_borrow_limit" class="form-control rounded-3 border-light shadow-none" value="<?= getSetting($tier['key'].'_borrow_limit', getSetting('borrow_limit', 3)) ?>">
                                                </div>
                                                <div class="col-md-4">
                                                    <label class="form-label smallest fw-800 text-muted text-uppercase mb-1">Borrow Duration (Days)</label>
                                                    <input type="number" name="<?= $tier['key'] ?>_borrow_duration" class="form-control rounded-3 border-light shadow-none" value="<?= getSetting($tier['key'].'_borrow_duration', getSetting('borrow_duration', 14)) ?>">
                                                </div>
                                                <div class="col-md-4">
                                                    <label class="form-label smallest fw-800 text-muted text-uppercase mb-1">Fine Per Day (Ks)</label>
                                                    <input type="number" name="<?= $tier['key'] ?>_fine_per_day" class="form-control rounded-3 border-light shadow-none" value="<?= getSetting($tier['key'].'_fine_per_day', getSetting('fine_per_day', 500)) ?>">
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <?php endforeach; ?>
                            </div>
<?php
// End of Policies
?>

                            <div class="mt-5 pt-3 border-top">
                                <button type="submit" class="btn btn-primary px-5 py-2 fw-900 rounded-pill shadow-sm"><i class="fas fa-sync-alt me-2"></i>Apply Multi-Tier Policies</button>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Preferences -->
                <div class="tab-pane fade" id="prefs">
                    <div class="card border-0 shadow-sm rounded-4 bg-white p-4">
                        <h5 class="fw-800 text-dark mb-4">Site Preferences</h5>
                        <form action="" method="POST">
                            <input type="hidden" name="action" value="update_preferences">
                            <div class="mb-4">
                                <div class="form-check form-switch custom-switch p-3 bg-lightest rounded-3 mb-3 border border-light">
                                    <input class="form-check-input ms-0 me-3" type="checkbox" name="maintenance_mode" id="maintenanceSwitch" <?= getSetting('maintenance_mode') === '1' ? 'checked' : '' ?>>
                                    <label class="form-check-label fw-bold text-dark" for="maintenanceSwitch">Maintenance Mode</label>
                                    <p class="text-muted smallest mb-0 mt-1">When enabled, users will see a maintenance page and cannot access the frontend.</p>
                                </div>
                                <div class="form-check form-switch custom-switch p-3 bg-lightest rounded-3 border border-light">
                                    <input class="form-check-input ms-0 me-3" type="checkbox" name="allow_registration" id="registrationSwitch" <?= getSetting('allow_registration', '1') === '1' ? 'checked' : '' ?>>
                                    <label class="form-check-label fw-bold text-dark" for="registrationSwitch">Member Registration</label>
                                    <p class="text-muted smallest mb-0 mt-1">Allow new users to sign up for library accounts from the frontend.</p>
                                </div>
                            </div>
                            <div class="mt-4 pt-3 border-top">
                                <button type="submit" class="btn btn-primary px-4 fw-bold">Save Preferences</button>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Security -->
                <div class="tab-pane fade" id="security">
                    <div class="card border-0 shadow-sm rounded-4 bg-white p-4">
                        <h5 class="fw-800 text-dark mb-4">Change Admin Password</h5>
                        <p class="text-muted small mb-4">Security is important. Use a strong password with letters, numbers, and symbols.</p>
                        <form action="" method="POST">
                            <input type="hidden" name="action" value="change_password">
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label class="form-label smallest fw-bold text-uppercase text-muted">Current Password</label>
                                    <input type="password" name="current_password" class="form-control rounded-3" required>
                                </div>
                                <div class="col-md-6"></div>
                                <div class="col-md-6">
                                    <label class="form-label smallest fw-bold text-uppercase text-muted">New Password</label>
                                    <input type="password" name="new_password" class="form-control rounded-3" required minlength="6">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label smallest fw-bold text-uppercase text-muted">Confirm New Password</label>
                                    <input type="password" name="confirm_password" class="form-control rounded-3" required minlength="6">
                                </div>
                            </div>
                            <div class="mt-4 pt-3 border-top">
                                <button type="submit" class="btn btn-danger px-4 fw-bold">Update Password</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <style>
    .settings-nav .list-group-item {
        color: var(--p-text-muted);
        font-weight: 500;
        font-size: 0.95rem;
        transition: all 0.2s;
    }
    .settings-nav .list-group-item:hover {
        color: var(--p-primary);
        background: rgba(78, 115, 223, 0.05);
    }
    .settings-nav .list-group-item.active {
        color: var(--p-primary);
        background: rgba(78, 115, 223, 0.1);
        font-weight: 700;
        border-right: 3px solid var(--p-primary) !important;
    }
    .custom-switch .form-check-input {
        width: 3rem;
        height: 1.5rem;
        cursor: pointer;
    }
    .custom-switch .form-check-input:checked {
        background-color: var(--p-success);
        border-color: var(--p-success);
    }
    .form-control:focus, .form-select:focus {
        border-color: var(--p-primary);
        box-shadow: 0 0 0 0.25rem rgba(78, 115, 223, 0.1);
    }
    </style>

    <script>
    document.addEventListener('DOMContentLoaded', function() {
        // Simple hash-based tab activation
        const hash = window.location.hash;
        if (hash) {
            const tabEl = document.querySelector(`.settings-nav a[href="${hash}"]`);
            if (tabEl) {
                bootstrap.Tab.getInstance(tabEl)?.show() || new bootstrap.Tab(tabEl).show();
            }
        }

        // Update hash when tab changes
        const triggerTabList = document.querySelectorAll('.settings-nav a');
        triggerTabList.forEach(triggerEl => {
            triggerEl.addEventListener('shown.bs.tab', event => {
                window.location.hash = event.target.getAttribute('href');
            });
        });
    });
    </script>
    <?php
});
