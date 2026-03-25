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
    <script>
    document.getElementById('userSearch').addEventListener('input', function(e) {
        const val = e.target.value.toLowerCase();
        const rows = document.querySelectorAll('#userTableBody tr');
        rows.forEach(row => {
            const text = row.textContent.toLowerCase();
            row.style.display = text.includes(val) ? '' : 'none';
        });
    });
    </script>
    <?php
});
