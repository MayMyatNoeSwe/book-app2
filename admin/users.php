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

// Calculate stats for the hero section
$stats = [
    'total' => count($users),
    'silver' => count(array_filter($users, fn($u) => ($u['membership_tier'] ?? '') === 'silver')),
    'gold' => count(array_filter($users, fn($u) => ($u['membership_tier'] ?? '') === 'gold')),
    'platinum' => count(array_filter($users, fn($u) => ($u['membership_tier'] ?? '') === 'platinum')),
];

renderAdminLayout('User Access Management', function() use ($users, $stats) {
    ?>
    <!-- Premium Stats Section -->
    <div class="row g-4 mb-5">
        <div class="col-xl-3 col-md-6">
            <div class="card border-0 shadow-sm rounded-4 h-100 overflow-hidden bg-white">
                <div class="card-body p-4">
                    <div class="d-flex align-items-center mb-3">
                        <div class="rounded-3 bg-primary-soft text-primary p-3 me-3">
                            <i class="fas fa-users fs-4"></i>
                        </div>
                        <div>
                            <p class="text-muted smallest fw-800 text-uppercase mb-0 tracking-wider">Total Members</p>
                            <h3 class="mb-0 fw-800 text-dark"><?= $stats['total'] ?></h3>
                        </div>
                    </div>
                    <div class="progress" style="height: 4px;">
                        <div class="progress-bar bg-primary" style="width: 100%"></div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-md-6">
            <div class="card border-0 shadow-sm rounded-4 h-100 overflow-hidden bg-white">
                <div class="card-body p-4">
                    <div class="d-flex align-items-center mb-3">
                        <div class="rounded-3 bg-silver-soft text-silver p-3 me-3">
                            <i class="fas fa-star fs-4"></i>
                        </div>
                        <div>
                            <p class="text-muted smallest fw-800 text-uppercase mb-0 tracking-wider">Silver Tier</p>
                            <h3 class="mb-0 fw-800 text-dark"><?= $stats['silver'] ?></h3>
                        </div>
                    </div>
                    <div class="progress" style="height: 4px;">
                        <div class="progress-bar bg-silver" style="width: <?= $stats['total'] > 0 ? ($stats['silver']/$stats['total'])*100 : 0 ?>%"></div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-md-6">
            <div class="card border-0 shadow-sm rounded-4 h-100 overflow-hidden bg-white">
                <div class="card-body p-4">
                    <div class="d-flex align-items-center mb-3">
                        <div class="rounded-3 bg-gold-soft text-gold p-3 me-3">
                            <i class="fas fa-crown fs-4"></i>
                        </div>
                        <div>
                            <p class="text-muted smallest fw-800 text-uppercase mb-0 tracking-wider">Gold Tier</p>
                            <h3 class="mb-0 fw-800 text-dark"><?= $stats['gold'] ?></h3>
                        </div>
                    </div>
                    <div class="progress" style="height: 4px;">
                        <div class="progress-bar bg-gold" style="width: <?= $stats['total'] > 0 ? ($stats['gold']/$stats['total'])*100 : 0 ?>%"></div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-md-6">
            <div class="card border-0 shadow-sm rounded-4 h-100 overflow-hidden bg-white">
                <div class="card-body p-4">
                    <div class="d-flex align-items-center mb-3">
                        <div class="rounded-3 bg-platinum-soft text-platinum p-3 me-3">
                            <i class="fas fa-gem fs-4"></i>
                        </div>
                        <div>
                            <p class="text-muted smallest fw-800 text-uppercase mb-0 tracking-wider">Platinum Tier</p>
                            <h3 class="mb-0 fw-800 text-dark"><?= $stats['platinum'] ?></h3>
                        </div>
                    </div>
                    <div class="progress" style="height: 4px;">
                        <div class="progress-bar bg-platinum" style="width: <?= $stats['total'] > 0 ? ($stats['platinum']/$stats['total'])*100 : 0 ?>%"></div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Directory Hub Card -->
    <div class="card card-admin border-0 shadow-sm rounded-4 overflow-hidden mb-5">
        <div class="card-header bg-white py-4 border-0 px-4">
            <div class="row align-items-center g-3">
                <div class="col-md-4">
                    <h5 class="mb-0 fw-800 text-dark">Member Directory</h5>
                    <p class="text-muted smallest fw-bold mb-0 text-uppercase tracking-wider mt-1">SECURE ACCESS MANAGEMENT</p>
                </div>
                <div class="col-md-8">
                    <div class="d-flex flex-wrap gap-2 justify-content-md-end">
                        <div class="btn-group rounded-pill overflow-hidden border">
                            <button type="button" class="btn btn-white btn-sm active filter-tier" data-tier="all">All</button>
                            <button type="button" class="btn btn-white btn-sm filter-tier" data-tier="silver">Silver</button>
                            <button type="button" class="btn btn-white btn-sm filter-tier" data-tier="gold">Gold</button>
                            <button type="button" class="btn btn-white btn-sm filter-tier" data-tier="platinum">Platinum</button>
                        </div>
                        <div class="input-group input-group-sm border rounded-pill px-2 bg-lightest" style="max-width: 250px;">
                            <span class="input-group-text bg-transparent border-0"><i class="fas fa-search text-muted opacity-50"></i></span>
                            <input type="text" id="userSearch" class="form-control border-0 bg-transparent shadow-none" placeholder="Find member...">
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="bg-lightest border-bottom">
                        <tr>
                            <th class="py-3 px-4 border-0 text-muted smallest fw-800 text-uppercase tracking-wider">Member</th>
                            <th class="py-3 px-4 border-0 text-muted smallest fw-800 text-uppercase tracking-wider">Membership Status</th>
                            <th class="py-3 px-4 border-0 text-muted smallest fw-800 text-uppercase tracking-wider">Authorization</th>
                            <th class="py-3 px-4 border-0 text-muted smallest fw-800 text-uppercase tracking-wider">Join Date</th>
                            <th class="py-3 px-4 border-0 text-muted smallest fw-800 text-uppercase tracking-wider text-end">Control</th>
                        </tr>
                    </thead>
                    <tbody id="userTableBody">
                        <?php foreach ($users as $u): 
                            $activeTiers = $u['active_tiers'] ? explode(',', $u['active_tiers']) : ['bronze'];
                        ?>
                        <tr data-tiers="<?= e(implode(',', $activeTiers)) ?>">
                            <td class="px-4 py-4">
                                <div class="d-flex align-items-center gap-3">
                                    <div class="position-relative">
                                        <img src="https://ui-avatars.com/api/?name=<?= urlencode($u['username']) ?>&background=random&bold=true" class="rounded-circle shadow-sm" width="48" height="48">
                                        <?php if($u['role'] === 'admin'): ?>
                                            <span class="position-absolute bottom-0 end-0 bg-primary border border-white border-2 rounded-circle" style="width: 14px; height: 14px;" title="Administrator"></span>
                                        <?php endif; ?>
                                    </div>
                                    <div>
                                        <h6 class="mb-0 fw-800 text-dark"><?= e($u['username']) ?></h6>
                                        <p class="mb-0 text-muted smallest fw-600"><?= e($u['email']) ?></p>
                                    </div>
                                </div>
                            </td>
                            <td class="px-4 py-4">
                                <div class="d-flex align-items-center gap-2 mb-1">
                                    <span class="badge rounded-pill px-3 py-2 border smallest fw-800 text-uppercase tracking-wider 
                                        <?= ($u['membership_tier'] ?? 'bronze') === 'bronze' ? 'bg-bronze-soft text-bronze border-bronze-subtle' : '' ?>
                                        <?= ($u['membership_tier'] ?? 'silver') === 'silver' ? 'bg-silver-soft text-silver border-silver-subtle' : '' ?>
                                        <?= ($u['membership_tier'] ?? 'gold') === 'gold' ? 'bg-gold-soft text-gold border-gold-subtle' : '' ?>
                                        <?= ($u['membership_tier'] ?? 'platinum') === 'platinum' ? 'bg-platinum-soft text-platinum border-platinum-subtle' : '' ?>
                                    ">
                                        <?= e($u['membership_tier'] ?? 'bronze') ?>
                                    </span>
                                    <?php if(($u['sub_count'] ?? 0) > 1): ?>
                                        <span class="badge bg-primary-soft text-primary border border-primary-subtle rounded-pill px-2 py-1 smallest px-2" title="<?= $u['sub_count'] ?> Active Subscriptions">
                                            <i class="fas fa-layer-group me-1"></i>x<?= $u['sub_count'] ?>
                                        </span>
                                    <?php endif; ?>
                                </div>
                                <div class="smallest fw-bold">
                                    <span class="text-muted opacity-50"><?= e($u['membership_id'] ?? 'UNASSIGNED') ?></span>
                                    <?php if(!empty($u['membership_expires_at'])): ?>
                                        <span class="text-primary ms-2 d-inline-block"><i class="fas fa-hourglass-half f-small me-1"></i>Expires <?= date('M j, Y', strtotime($u['membership_expires_at'])) ?></span>
                                    <?php endif; ?>
                                </div>
                            </td>
                            <td class="px-4 py-4">
                                <?php if($u['role'] === 'admin'): ?>
                                    <div class="d-inline-flex align-items-center gap-2 px-3 py-1 rounded-pill bg-primary shadow-sm" style="font-size: 10px;">
                                        <i class="fas fa-shield-alt text-white"></i>
                                        <span class="text-white fw-800 text-uppercase tracking-tighter">Admin Access</span>
                                    </div>
                                <?php else: ?>
                                    <span class="badge bg-light text-muted rounded-pill px-3 py-1 border border-light-subtle smallest fw-700">USER ROLE</span>
                                <?php endif; ?>
                            </td>
                            <td class="px-4 py-4">
                                <span class="text-muted smallest fw-bold d-block"><?= date('M d, Y', strtotime($u['created_at'])) ?></span>
                                <span class="smallest opacity-50"><?= date('h:i A', strtotime($u['created_at'])) ?></span>
                            </td>
                            <td class="px-4 py-4 text-end">
                                <div class="d-flex justify-content-end gap-1">
                                    <button class="btn btn-icon-sm rounded-circle btn-info-soft view-details" data-id="<?= $u['id'] ?>" title="View Subscriptions">
                                        <i class="fas fa-search"></i>
                                    </button>
                                    <button class="btn btn-icon-sm rounded-circle btn-tier change-tier" data-id="<?= $u['id'] ?>" data-tier="<?= e($u['membership_tier']) ?>" title="Upgrade Member">
                                        <i class="fas fa-gem"></i>
                                    </button>
                                    <button class="btn btn-icon-sm rounded-circle btn-role change-role" data-id="<?= $u['id'] ?>" data-role="<?= $u['role'] ?>" title="Manage Access">
                                        <i class="fas fa-key"></i>
                                    </button>
                                    <button class="btn btn-icon-sm rounded-circle btn-danger-soft delete-member" data-id="<?= $u['id'] ?>" title="Remove User">
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
        <div class="card-footer bg-white py-4 border-0 px-4 text-center">
             <p class="text-muted smallest mb-0 fw-bold">PLATFORM SECURITY: All administrative actions are logged and traceable.</p>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
        .fs-small { font-size: 0.875rem; }
        .f-small { font-size: 11px; }
        .bg-lightest { background-color: #f8faff !important; }
        
        /* Member Tier Styles */
        .bg-bronze-soft { background-color: rgba(205, 127, 50, 0.08) !important; }
        .text-bronze { color: #8b4513 !important; }
        .border-bronze-subtle { border-color: rgba(205, 127, 50, 0.15) !important; }
        
        .bg-silver-soft { background-color: rgba(189, 195, 199, 0.15) !important; }
        .text-silver { color: #7f8c8d !important; }
        .border-silver-subtle { border-color: rgba(189, 195, 199, 0.25) !important; }
        .bg-silver { background-color: #bdc3c7; }
        
        .bg-gold-soft { background-color: rgba(241, 196, 15, 0.1) !important; }
        .text-gold { color: #d4a017 !important; }
        .border-gold-subtle { border-color: rgba(241, 196, 15, 0.2) !important; }
        .bg-gold { background-color: #f1c40f; }
        
        .bg-platinum-soft { background-color: rgba(30, 41, 59, 0.08) !important; }
        .text-platinum { color: #1e293b !important; }
        .border-platinum-subtle { border-color: rgba(30, 41, 59, 0.15) !important; }
        .bg-platinum { background-color: #334155; }

        .bg-primary-soft { background-color: rgba(78, 115, 223, 0.08) !important; }

        /* Icon Buttons */
        .btn-icon-sm {
            width: 36px;
            height: 36px;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1);
            border: 1px solid transparent;
            background: #fff;
        }
        .btn-tier { color: #f59e0b; border-color: #fef3c7; }
        .btn-tier:hover { background: #f59e0b; color: #fff; transform: translateY(-2px); shadow: 0 4px 6px -1px rgba(245, 158, 11, 0.2); }
        
        .btn-info-soft { color: #0ea5e9; border-color: #e0f2fe; }
        .btn-info-soft:hover { background: #0ea5e9; color: #fff; transform: translateY(-2px); }
        
        .btn-role { color: #4e73df; border-color: #e0e7ff; }
        .btn-role:hover { background: #4e73df; color: #fff; transform: translateY(-2px); shadow: 0 4px 6px -1px rgba(78, 115, 223, 0.2); }
        
        .btn-danger-soft { color: #ef4444; border-color: #fee2e2; }
        .btn-danger-soft:hover { background: #ef4444; color: #fff; transform: translateY(-2px); shadow: 0 4px 6px -1px rgba(239, 68, 68, 0.2); }

        .tracking-tighter { tracking-spacing: -0.05em; }
        .btn-white { background-color: #fff; border-color: #e2e8f0; color: #64748b; }
        .btn-white.active { background-color: #f8fafc; color: #0f172a; border-color: #cbd5e1; font-weight: 700; }
        
        .table-hover tbody tr:hover { background-color: #fcfdfe !important; }
    </style>
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        // Quick Filters
        const filterBtns = document.querySelectorAll('.filter-tier');
        const rows = document.querySelectorAll('#userTableBody tr');
        
        filterBtns.forEach(btn => {
            btn.addEventListener('click', function() {
                filterBtns.forEach(b => b.classList.remove('active'));
                this.classList.add('active');
                
                const tier = this.dataset.tier;
                rows.forEach(row => {
                    const tiersArr = (row.dataset.tiers || '').split(',');
                    if(tier === 'all' || tiersArr.includes(tier)) {
                        row.style.display = '';
                    } else {
                        row.style.display = 'none';
                    }
                });
            });
        });

        // Search Implementation
        const searchInput = document.getElementById('userSearch');
        if(searchInput) {
            searchInput.addEventListener('input', function(e) {
                const val = e.target.value.toLowerCase();
                rows.forEach(row => {
                    const text = row.innerText.toLowerCase();
                    row.style.display = text.includes(val) ? '' : 'none';
                });
            });
        }

        // View Subscription Details
        document.querySelectorAll('.view-details').forEach(btn => {
            btn.addEventListener('click', function() {
                const id = this.dataset.id;
                
                Swal.fire({
                    title: 'Fetching Records...',
                    didOpen: () => Swal.showLoading(),
                    allowOutsideClick: false,
                    borderRadius: '1rem'
                });

                fetch('../api/admin_users.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ id, action: 'get_subs' })
                })
                .then(res => res.json())
                .then(data => {
                    if(!data.success) throw new Error(data.message);
                    
                    let html = `
                        <div class="text-start mt-3">
                            <p class="smallest fw-bold text-muted text-uppercase tracking-wider mb-3">Active Membership History - ${data.username}</p>
                            <div class="table-responsive rounded-3 border">
                                <table class="table table-sm table-borderless mb-0">
                                    <thead class="bg-light">
                                        <tr>
                                            <th class="smallest fw-800 p-2">Tier Type</th>
                                            <th class="smallest fw-800 p-2">Expiration Timestamp</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                    `;

                    if (data.subscriptions.length === 0) {
                        html += `<tr><td colspan="2" class="p-4 text-center text-muted">No active subscriptions found.</td></tr>`;
                    } else {
                        data.subscriptions.forEach(s => {
                            html += `
                                <tr class="border-top">
                                    <td class="p-2"><span class="badge bg-light text-dark border rounded-pill px-2 py-1 text-uppercase smallest font-monospace">${s.tier}</span></td>
                                    <td class="p-2 text-muted smallest fw-bold">${s.expires_at}</td>
                                </tr>
                            `;
                        });
                    }

                    html += `</tbody></table></div></div>`;

                    Swal.fire({
                        title: 'Membership Integrity Audit',
                        html: html,
                        width: '500px',
                        confirmButtonText: 'Great',
                        confirmButtonColor: '#4e73df',
                        borderRadius: '1rem'
                    });
                })
                .catch(err => Swal.fire({ icon: 'error', title: 'Error', text: err.message, borderRadius: '1rem' }));
            });
        });

        // Tier Change Action
        document.querySelectorAll('.change-tier').forEach(btn => {
            btn.addEventListener('click', function() {
                const id = this.dataset.id;
                const currentTier = this.dataset.tier;
                
                Swal.fire({
                    title: 'Modify Membership Tier',
                    text: 'Assign a new security tier for this member profile.',
                    icon: 'question',
                    input: 'select',
                    inputOptions: {
                        'bronze': 'Bronze Standard',
                        'silver': 'Silver Membership',
                        'gold': 'Gold Premium',
                        'platinum': 'Platinum VIP'
                    },
                    inputValue: currentTier || 'bronze',
                    showCancelButton: true,
                    confirmButtonText: 'Confirm Upgrade',
                    confirmButtonColor: '#f59e0b',
                    background: '#fff',
                    borderRadius: '1rem'
                }).then(result => {
                    if (result.isConfirmed) {
                        updateUserAction(id, 'update_tier', { tier: result.value });
                    }
                });
            });
        });

        // Role Change Action
        document.querySelectorAll('.change-role').forEach(btn => {
            btn.addEventListener('click', function() {
                const id = this.dataset.id;
                const currentRole = this.dataset.role;
                
                Swal.fire({
                    title: 'Update System Access',
                    text: 'Redefine operational privileges for this account.',
                    icon: 'warning',
                    input: 'select',
                    inputOptions: { 'user': 'Standard Member', 'admin': 'System Administrator' },
                    inputValue: currentRole,
                    showCancelButton: true,
                    confirmButtonText: 'Update Authorization',
                    confirmButtonColor: '#4e73df',
                    borderRadius: '1rem'
                }).then(result => {
                    if (result.isConfirmed) {
                        updateUserAction(id, 'update_role', { role: result.value });
                    }
                });
            });
        });

        // Deletion Action
        document.querySelectorAll('.delete-member').forEach(btn => {
            btn.addEventListener('click', function() {
                const id = this.dataset.id;
                Swal.fire({
                    title: 'Deactivate Account?',
                    text: 'Warning: This action will revoke all member privileges and is logged.',
                    icon: 'error',
                    showCancelButton: true,
                    confirmButtonText: 'Revoke Access',
                    confirmButtonColor: '#ef4444',
                    borderRadius: '1rem'
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
                    Swal.fire({ 
                        icon: 'success', 
                        title: 'Registry Updated', 
                        text: 'Security records have been synchronized successfully.',
                        timer: 2000, 
                        showConfirmButton: false,
                        borderRadius: '1rem'
                    })
                    .then(() => location.reload());
                } else {
                    Swal.fire({ icon: 'error', title: 'Action Failed', text: data.message });
                }
            })
            .catch(err => Swal.fire({ icon: 'error', title: 'System Error', text: 'Communication timeout.' }));
        }
    });
    </script>
    <?php
});
