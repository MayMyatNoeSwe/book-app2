<?php
// admin/users.php
require_once dirname(__DIR__) . '/vendor/autoload.php';
require_once dirname(__DIR__) . '/includes/sessions.php';
require_once dirname(__DIR__) . '/includes/env_loader.php';
require_once dirname(__DIR__) . '/includes/functions.php';
require_once dirname(__DIR__) . '/views/admin/layout.php';

use App\User;
$userSvc = new User();
$users = $userSvc->getAllUsers();

renderAdminLayout('User Access Management', function() use ($users) {
    ?>
    <section class="premium-hero mb-5 rounded-5 overflow-hidden position-relative p-5 border border-light-subtle shadow-sm bg-lightest">
        <div class="hero-pattern position-absolute top-0 start-0 w-100 h-100 opacity-5" style="background-image: radial-gradient(#4e73df 1px, transparent 1px); background-size: 30px 30px; z-index: 2;"></div>
        <div class="position-relative" style="z-index: 3;">
            <div class="row align-items-center">
                <div class="col-lg-8">
                    <h3 class="text-dark fw-800 mb-2">User Access Control</h3>
                    <p class="text-muted mb-4 fs-6 fw-500">Manage member privileges, security roles, and platform registrations from one central dashboard.</p>
                </div>
                <div class="col-lg-4 d-none d-lg-block text-end">
                    <div class="bg-white rounded-circle shadow-sm d-inline-flex align-items-center justify-content-center" style="width: 140px; height: 140px;">
                        <i class="fas fa-user-shield text-primary" style="font-size: 3.5rem;"></i>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <div class="card card-admin border-0 shadow-sm overflow-hidden">
        <div class="card-header bg-white py-4 border-0 d-flex justify-content-between align-items-center px-4">
            <div>
                <h5 class="mb-0 fw-800 text-dark">Member Directory</h5>
                <p class="text-muted smallest fw-bold mb-0 text-uppercase tracking-wider">SECURE AUTHORIZATION HUB</p>
            </div>
            <div class="d-flex gap-2">
                <div class="input-group input-group-sm border rounded-pill px-2">
                    <span class="input-group-text bg-white border-0"><i class="fas fa-search text-muted opacity-50 f-small"></i></span>
                    <input type="text" id="userSearch" class="form-control border-0 shadow-none f-small" placeholder="Search members...">
                </div>
            </div>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead>
                        <tr class="bg-lightest border-bottom">
                            <th class="py-3 px-4 border-0 text-muted smallest fw-800 text-uppercase tracking-wider">Member</th>
                            <th class="py-3 px-4 border-0 text-muted smallest fw-800 text-uppercase tracking-wider">Membership Tier</th>
                            <th class="py-3 px-4 border-0 text-muted smallest fw-800 text-uppercase tracking-wider">Authorization</th>
                            <th class="py-3 px-4 border-0 text-muted smallest fw-800 text-uppercase tracking-wider">Registration</th>
                            <th class="py-3 px-4 border-0 text-muted smallest fw-800 text-uppercase tracking-wider text-end">Control</th>
                        </tr>
                    </thead>
                    <tbody id="userTableBody">
                        <?php foreach ($users as $u): ?>
                        <tr>
                            <td class="px-4 py-4">
                                <div class="d-flex align-items-center gap-3">
                                    <img src="https://ui-avatars.com/api/?name=<?= urlencode($u['username']) ?>&background=random" class="rounded-circle shadow-sm" width="45" height="45">
                                    <div>
                                        <h6 class="mb-0 fw-800 text-dark"><?= e($u['username']) ?></h6>
                                        <p class="mb-0 text-muted smallest fw-600"><?= e($u['email']) ?></p>
                                    </div>
                                </div>
                            </td>
                            <td class="px-4 py-4">
                                <span class="badge rounded-pill px-3 py-2 border smallest fw-800 text-uppercase tracking-wider 
                                    <?= ($u['membership_tier'] ?? 'bronze') === 'bronze' ? 'bg-bronze-soft text-bronze border-bronze-subtle' : '' ?>
                                    <?= ($u['membership_tier'] ?? 'silver') === 'silver' ? 'bg-silver-soft text-silver border-silver-subtle' : '' ?>
                                    <?= ($u['membership_tier'] ?? 'gold') === 'gold' ? 'bg-gold-soft text-gold border-gold-subtle' : '' ?>
                                    <?= ($u['membership_tier'] ?? 'platinum') === 'platinum' ? 'bg-platinum-soft text-platinum border-platinum-subtle' : '' ?>
                                ">
                                    <?= e($u['membership_tier'] ?? 'bronze') ?>
                                </span>
                                <div class="smallest text-muted mt-1 fw-bold"><?= e($u['membership_id'] ?? 'UNASSIGNED') ?></div>
                            </td>
                            <td class="px-4 py-4">
                                <?php if($u['role'] === 'admin'): ?>
                                    <span class="badge bg-primary-soft text-primary rounded-pill px-3 py-2 border border-primary-subtle smallest fw-800">ADMINISTRATOR</span>
                                <?php else: ?>
                                    <span class="badge bg-light text-dark rounded-pill px-3 py-2 border border-light-subtle smallest fw-800">STANDARD USER</span>
                                <?php endif; ?>
                            </td>
                            <td class="px-4 py-4">
                                <span class="text-muted smallest fw-bold"><?= date('M d, Y', strtotime($u['created_at'])) ?></span>
                            </td>
                            <td class="px-4 py-4 text-end">
                                <div class="d-flex justify-content-end gap-2 px-1">
                                    <button class="btn btn-icon-only rounded-pill btn-soft-warning change-tier" data-id="<?= $u['id'] ?>" data-tier="<?= $u['membership_tier'] ?>" title="Modify Tier">
                                        <i class="fas fa-gem"></i>
                                    </button>
                                    <button class="btn btn-icon-only rounded-pill btn-soft-primary change-role" data-id="<?= $u['id'] ?>" data-role="<?= $u['role'] ?>" title="Modify Access">
                                        <i class="fas fa-key"></i>
                                    </button>
                                    <button class="btn btn-icon-only rounded-pill btn-soft-danger delete-member" data-id="<?= $u['id'] ?>" title="Revoke Privilege">
                                        <i class="fas fa-trash-alt"></i>
                                    </button>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
        .bg-bronze-soft { background-color: rgba(205, 127, 50, 0.1) !important; }
        .text-bronze { color: #8b4513 !important; }
        .border-bronze-subtle { border-color: rgba(205, 127, 50, 0.2) !important; }
        
        .bg-silver-soft { background-color: rgba(189, 195, 199, 0.2) !important; }
        .text-silver { color: #7f8c8d !important; }
        .border-silver-subtle { border-color: rgba(189, 195, 199, 0.3) !important; }
        
        .bg-gold-soft { background-color: rgba(241, 196, 15, 0.15) !important; }
        .text-gold { color: #d4a017 !important; }
        .border-gold-subtle { border-color: rgba(241, 196, 15, 0.25) !important; }
        
        .bg-platinum-soft { background-color: rgba(30, 41, 59, 0.1) !important; }
        .text-platinum { color: #1e293b !important; }
        .border-platinum-subtle { border-color: rgba(30, 41, 59, 0.2) !important; }
        
        .btn-soft-warning { background: rgba(245, 158, 11, 0.1); color: #f59e0b; }
        .btn-soft-warning:hover { background: #f59e0b; color: #fff; }
    </style>
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        const searchInput = document.getElementById('userSearch');
        if(searchInput) {
            searchInput.addEventListener('input', function(e) {
                const val = e.target.value.toLowerCase();
                const rows = document.querySelectorAll('#userTableBody tr');
                rows.forEach(row => {
                    const text = row.textContent.toLowerCase();
                    row.style.display = text.includes(val) ? '' : 'none';
                });
            });
        }

        // Tier Change
        document.querySelectorAll('.change-tier').forEach(btn => {
            btn.addEventListener('click', function() {
                const id = this.dataset.id;
                const currentTier = this.dataset.tier;
                
                Swal.fire({
                    title: 'Update Membership Tier',
                    text: 'Select new tier for this member',
                    icon: 'question',
                    input: 'select',
                    inputOptions: {
                        'bronze': 'Bronze',
                        'silver': 'Silver',
                        'gold': 'Gold',
                        'platinum': 'Platinum'
                    },
                    inputValue: currentTier,
                    showCancelButton: true,
                    confirmButtonText: 'Update Tier',
                    confirmButtonColor: '#f59e0b'
                }).then(result => {
                    if (result.isConfirmed) {
                        updateUserAction(id, 'update_tier', { tier: result.value });
                    }
                });
            });
        });

        // Role Change
        document.querySelectorAll('.change-role').forEach(btn => {
            btn.addEventListener('click', function() {
                const id = this.dataset.id;
                const currentRole = this.dataset.role;
                
                Swal.fire({
                    title: 'Modify System Access',
                    text: 'Set authorization level for this member',
                    icon: 'warning',
                    input: 'select',
                    inputOptions: { 'user': 'Standard User', 'admin': 'Administrator' },
                    inputValue: currentRole,
                    showCancelButton: true,
                    confirmButtonText: 'Apply Role',
                    confirmButtonColor: '#4e73df'
                }).then(result => {
                    if (result.isConfirmed) {
                        updateUserAction(id, 'update_role', { role: result.value });
                    }
                });
            });
        });

        // Delete Member
        document.querySelectorAll('.delete-member').forEach(btn => {
            btn.addEventListener('click', function() {
                const id = this.dataset.id;
                Swal.fire({
                    title: 'Revoke All Privileges?',
                    text: 'This will permanently remove the member registration.',
                    icon: 'error',
                    showCancelButton: true,
                    confirmButtonText: 'Revoke Now',
                    confirmButtonColor: '#e74a3b'
                }).then(result => {
                    if (result.isConfirmed) {
                        updateUserAction(id, 'delete_user');
                    }
                });
            });
        });

        function updateUserAction(id, action, extra = {}) {
            fetch('../api/admin_users.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ id, action, ...extra })
            })
            .then(res => res.json())
            .then(data => {
                if(data.success) {
                    Swal.fire({ icon: 'success', title: 'Changes Saved', timer: 1500, showConfirmButton: false })
                    .then(() => location.reload());
                } else {
                    Swal.fire({ icon: 'error', title: 'Error', text: data.message });
                }
            })
            .catch(err => Swal.fire({ icon: 'error', title: 'Network Error', text: 'Service unavailable' }));
        }
    });
    </script>
    <?php
});
