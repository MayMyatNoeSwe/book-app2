<?php
// admin/users.php
require_once dirname(__DIR__) . '/vendor/autoload.php';
require_once dirname(__DIR__) . '/includes/sessions.php';
require_once dirname(__DIR__) . '/includes/env_loader.php';
require_once dirname(__DIR__) . '/includes/functions.php';
require_once dirname(__DIR__) . '/views/admin/layout.php';

use App\User;
use App\Library;

$library = new Library();
$userModel = new User($library->getPdo());

// Handle POST actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $userId = $_POST['id'] ?? 0;

    if ($action === 'update_role' && $userId) {
        $newRole = $_POST['role'] ?? 'user';
        if ($userModel->updateUserRole($userId, $newRole)) {
            setFlashMessage('User role updated successfully!', 'success');
        } else {
            setFlashMessage('Failed to update user role.', 'danger');
        }
    }
}

// Handle GET actions (Delete)
if (isset($_GET['action']) && $_GET['action'] === 'delete' && isset($_GET['id'])) {
    $userId = (int)$_GET['id'];
    if ($userModel->deleteUser($userId)) {
        setFlashMessage('User deleted successfully!', 'success');
    } else {
        setFlashMessage('Could not delete user. (You cannot delete yourself)', 'danger');
    }
    header('Location: ' . baseUrl() . '/admin/users.php');
    exit();
}

// Fetch users
$users = $userModel->getAllUsers();

renderAdminLayout('User Management', function() use ($users) {
    ?>
    <section class="admin-user-management">
        <div class="row g-4 align-items-center mb-4">
            <div class="col-lg-8">
                <div class="input-group input-group-lg border-0 shadow-sm rounded-4 overflow-hidden bg-white px-3 py-1">
                    <span class="input-group-text bg-white border-0"><i class="fas fa-search text-muted opacity-50"></i></span>
                    <input type="text" id="userSearch" class="form-control border-0 shadow-none ps-1 fs-6" placeholder="Find users by name, email or access level...">
                </div>
            </div>
            <div class="col-lg-4 text-lg-end">
                <button class="btn btn-outline-primary rounded-pill px-4 fw-bold border-2 d-none d-lg-inline-flex align-items-center gap-2">
                    <i class="fas fa-file-export small"></i>
                    <span>Export Data</span>
                </button>
            </div>
        </div>

        <div class="card card-admin border-0 shadow-sm overflow-hidden">
            <div class="card-header bg-white py-3 border-0 d-flex justify-content-between align-items-center px-4">
                <h6 class="m-0 fw-800 text-dark">Active System Users</h6>
                <span class="badge bg-primary-soft text-primary rounded-pill px-3 py-2 smallest fw-bold"><?= count($users) ?> Total Profiles</span>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0" id="usersTable">
                        <thead>
                            <tr>
                                <th>User Profile</th>
                                <th>Access Rights</th>
                                <th class="text-end">Operations</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($users as $user): ?>
                            <tr>
                                <td>
                                    <div class="d-flex align-items-center gap-3">
                                        <div class="avatar-box rounded-circle bg-light d-flex align-items-center justify-content-center shadow-sm" style="width: 45px; height: 45px; flex-shrink: 0;">
                                            <img src="https://ui-avatars.com/api/?name=<?= urlencode($user['username']) ?>&background=random" class="rounded-circle w-100 h-100" alt="Avatar">
                                        </div>
                                        <div class="user-meta">
                                            <h6 class="mb-0 fw-bold text-dark"><?= e($user['username']) ?></h6>
                                            <p class="mb-0 text-muted small"><i class="far fa-envelope me-1 opacity-50"></i> <?= e($user['email']) ?></p>
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    <?php if ($user['role'] === 'admin'): ?>
                                        <span class="badge bg-danger-soft text-danger border border-danger-subtle rounded-pill px-3 py-2 fw-bold shadow-sm">
                                            <i class="fas fa-shield-alt me-1"></i> Administrator
                                        </span>
                                    <?php else: ?>
                                        <span class="badge bg-success-soft text-success border border-success-subtle rounded-pill px-3 py-2 fw-bold shadow-sm">
                                            <i class="fas fa-user me-1"></i> Standard User
                                        </span>
                                    <?php endif; ?>
                                </td>
                                <td class="text-end">
                                    <div class="d-flex justify-content-end gap-2 px-2">
                                        <button class="btn btn-icon-only rounded-pill btn-soft-primary edit-role-btn" 
                                                title="Change Privileges"
                                                data-id="<?= $user['id'] ?>" 
                                                data-username="<?= e($user['username']) ?>"
                                                data-role="<?= $user['role'] ?>">
                                            <i class="fas fa-user-shield"></i>
                                        </button>
                                        <button class="btn btn-icon-only rounded-pill btn-soft-danger delete-user-btn" 
                                                title="Remove Profile"
                                                data-id="<?= $user['id'] ?>" 
                                                data-username="<?= e($user['username']) ?>">
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
    </section>

    <!-- Role Management Modal -->
    <div class="modal fade" id="roleModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-sm">
            <div class="modal-content overflow-hidden border-0 shadow-lg rounded-5 bg-glass">
                <div class="modal-header border-0 pt-4 px-4">
                    <div class="text-center w-100">
                        <div class="icon-box bg-primary-soft text-primary rounded-circle mx-auto mb-3 d-flex align-items-center justify-content-center" style="width: 60px; height: 60px; font-size: 1.5rem;">
                            <i class="fas fa-user-tag"></i>
                        </div>
                        <h5 class="modal-title fw-800 text-dark pb-0 mb-0">Update Privileges</h5>
                        <p class="text-muted small mt-1" id="roleModalUsername">Changing access for User</p>
                    </div>
                </div>
                <div class="modal-body p-4 pt-0">
                    <form action="" method="POST">
                        <input type="hidden" name="action" value="update_role">
                        <input type="hidden" name="id" id="roleUserId">
                        
                        <div class="role-selector mb-4">
                            <label class="field-label d-block mb-3 text-center fw-bold opacity-75">SELECT ACCESS LEVEL</label>
                            <div class="row g-2">
                                <div class="col-6">
                                    <input type="radio" class="btn-check" name="role" id="roleUser" value="user" autocomplete="off">
                                    <label class="btn btn-outline-success w-100 rounded-4 py-3 border-2" for="roleUser">
                                        <i class="fas fa-user d-block mb-1 fs-4"></i>
                                        <span class="fw-bold small">USER</span>
                                    </label>
                                </div>
                                <div class="col-6">
                                    <input type="radio" class="btn-check" name="role" id="roleAdmin" value="admin" autocomplete="off">
                                    <label class="btn btn-outline-danger w-100 rounded-4 py-3 border-2" for="roleAdmin">
                                        <i class="fas fa-shield-alt d-block mb-1 fs-4"></i>
                                        <span class="fw-bold small">ADMIN</span>
                                    </label>
                                </div>
                            </div>
                        </div>

                        <div class="d-grid gap-2">
                            <button type="submit" class="btn btn-primary rounded-pill py-2 fw-bold shadow-primary">Apply Changes</button>
                            <button type="button" class="btn btn-light rounded-pill py-2 fw-bold text-muted border-0" data-bs-dismiss="modal">Keep Original</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <style>
    .btn-icon-only {
        width: 38px;
        height: 38px;
        display: flex;
        align-items: center;
        justify-content: center;
        transition: all 0.2s;
        border: none;
    }
    .btn-soft-primary { background: rgba(78, 115, 223, 0.1); color: #4e73df; }
    .btn-soft-primary:hover { background: #4e73df; color: #fff; transform: scale(1.1); }
    .btn-soft-danger { background: rgba(231, 74, 59, 0.1); color: #e74a3b; }
    .btn-soft-danger:hover { background: #e74a3b; color: #fff; transform: scale(1.1); }
    
    .bg-glass {
        background: rgba(255, 255, 255, 0.95);
        backdrop-filter: blur(10px);
    }
    
    .bg-primary-soft { background: rgba(78, 115, 223, 0.1); }
    .bg-danger-soft { background: rgba(231, 74, 59, 0.1); }
    .bg-success-soft { background: rgba(28, 200, 138, 0.1); }
    
    .role-selector .btn-check:checked + .btn {
        box-shadow: 0 8px 15px rgba(0,0,0,0.1);
        background: rgba(0,0,0,0.02);
    }
    </style>

    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        // Search Functionality
        const searchInput = document.getElementById('userSearch');
        const rows = document.querySelectorAll('#usersTable tbody tr');

        searchInput.addEventListener('input', function() {
            const query = this.value.toLowerCase();
            rows.forEach(row => {
                const text = row.innerText.toLowerCase();
                row.style.display = text.includes(query) ? '' : 'none';
            });
        });

        // Edit Role Modal
        const roleModal = new bootstrap.Modal(document.getElementById('roleModal'));
        document.querySelectorAll('.edit-role-btn').forEach(btn => {
            btn.addEventListener('click', function() {
                const id = this.dataset.id;
                const username = this.dataset.username;
                const role = this.dataset.role;

                document.getElementById('roleUserId').value = id;
                document.getElementById('roleModalUsername').innerText = `Setting access for ${username}`;
                
                if (role === 'admin') {
                    document.getElementById('roleAdmin').checked = true;
                } else {
                    document.getElementById('roleUser').checked = true;
                }

                roleModal.show();
            });
        });

        // Delete User
        document.querySelectorAll('.delete-user-btn').forEach(btn => {
            btn.addEventListener('click', function() {
                const id = this.dataset.id;
                const username = this.dataset.username;

                Swal.fire({
                    title: 'System Security Alert',
                    html: `Are you sure you want to remove <b>${username}</b>?<br><span class="text-danger smallest h6">This action cannot be undone.</span>`,
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#e74a3b',
                    cancelButtonColor: '#858796',
                    confirmButtonText: 'Yes, Remove Profile',
                    cancelButtonText: 'Keep User',
                    borderRadius: '24px',
                    customClass: {
                        popup: 'rounded-5'
                    }
                }).then((result) => {
                    if (result.isConfirmed) {
                        window.location.href = `admin/users.php?action=delete&id=${id}`;
                    }
                });
            });
        });
    });
    </script>
    <?php
});
