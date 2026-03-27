<?php
// admin/messages.php — Contact Message Management
require_once dirname(__DIR__) . '/vendor/autoload.php';
require_once dirname(__DIR__) . '/includes/sessions.php';
require_once dirname(__DIR__) . '/includes/env_loader.php';
require_once dirname(__DIR__) . '/includes/functions.php';
require_once dirname(__DIR__) . '/views/admin/layout.php';

use App\Library;
$library = new Library();
$pdo = $library->getPdo();

// Handle Actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $id = (int)($_POST['id'] ?? 0);

    if ($action === 'mark_read') {
        $stmt = $pdo->prepare("UPDATE contact_messages SET status = 'read' WHERE id = ?");
        $stmt->execute([$id]);
        setFlashMessage('Message marked as read.', 'success');
    } elseif ($action === 'delete') {
        $stmt = $pdo->prepare("DELETE FROM contact_messages WHERE id = ?");
        $stmt->execute([$id]);
        setFlashMessage('Message deleted.', 'info');
    }
    header('Location: messages.php');
    exit;
}

// Fetch Messages
$stmt = $pdo->query("SELECT * FROM contact_messages ORDER BY created_at DESC");
$messages = $stmt->fetchAll();

renderAdminLayout('User Messages', function() use ($messages) {
    ?>
    <style>
        :root {
            --msg-unread: #cf6a50;
            --msg-bg: #f8fafc;
            --msg-border: #e2e8f0;
        }
        .msg-container { max-width: 900px; margin: 0 auto; }
        .msg-card {
            border: 1px solid var(--msg-border); border-radius: 24px; 
            background: #fff; margin-bottom: 20px; transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            position: relative; overflow: hidden;
        }
        .msg-card:hover { border-color: #cbd5e1; box-shadow: 0 10px 30px rgba(0,0,0,0.04); transform: translateY(-2px); }
        .msg-card.unread { border-left: 5px solid var(--msg-unread); background: #fffcfb; }
        
        .msg-header { padding: 24px 30px; display: flex; justify-content: space-between; align-items: flex-start; }
        .msg-body { padding: 0 30px 24px; font-size: 15px; color: #334155; line-height: 1.7; }
        .msg-footer { padding: 16px 30px; background: #f8fafc; border-top: 1px solid #f1f5f9; display: flex; justify-content: space-between; align-items: center; }
        
        .user-meta-box { display: flex; gap: 16px; align-items: center; }
        .user-circle { width: 44px; height: 44px; border-radius: 14px; background: #f1f5f9; display: grid; place-items: center; font-weight: 800; color: #475569; font-size: 18px; }
        .unread .user-circle { background: rgba(207, 106, 80, 0.1); color: var(--msg-unread); }
        
        .msg-status-pill { font-size: 10px; font-weight: 900; text-transform: uppercase; padding: 4px 12px; border-radius: 20px; letter-spacing: 0.5px; }
        .unread .msg-status-pill { background: var(--msg-unread); color: #fff; }
        .status-read { background: #e2e8f0; color: #64748b; }

        .btn-reply { background: #1e293b; color: #fff; border: none; padding: 8px 20px; border-radius: 12px; font-weight: 700; font-size: 13px; transition: all 0.2s; display: flex; align-items: center; gap: 8px; }
        .btn-reply:hover { background: #0f172a; transform: scale(1.02); color: #fff; }
        
        .btn-mark { background: #fff; border: 1px solid #e2e8f0; color: #475569; padding: 8px 16px; border-radius: 12px; font-weight: 700; font-size: 13px; transition: all 0.2s; }
        .btn-mark:hover { background: #f8fafc; color: var(--msg-unread); border-color: var(--msg-unread); }

        .btn-delete-msg {
            width: 36px; height: 36px; border-radius: 12px; background: transparent; color: #94a3b8; border: none;
            display: grid; place-items: center; transition: all 0.2s; cursor: pointer;
        }
        .btn-delete-msg:hover { background: #fee2e2; color: #ef4444; }
        
        .subject-line { font-weight: 900; color: #0f172a; margin-bottom: 8px; font-size: 16px; display: flex; align-items: center; gap: 10px; }
        .subject-line::before { content: ''; width: 8px; height: 8px; border-radius: 50%; background: var(--msg-unread); display: none; }
        .unread .subject-line::before { display: block; }
    </style>

    <div class="row">
        <div class="col-lg-12">
            <div class="msg-container">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <div>
                        <h4 class="fw-900 text-dark mb-1">Message Inbox</h4>
                        <p class="text-muted mb-0">Manage customer inquiries and feedback</p>
                    </div>
                    <div class="badge bg-white border text-dark py-2 px-3 rounded-pill fw-800 shadow-sm" style="font-size: 13px;">
                        <i class="fas fa-inbox me-2 opacity-50"></i><?= count($messages) ?> Total
                    </div>
                </div>

                <?php if (empty($messages)): ?>
                    <div class="card border-0 shadow-sm rounded-5 text-center p-5 bg-white">
                        <div class="mb-3 opacity-10"><i class="fas fa-envelope-open fa-5x"></i></div>
                        <h5 class="fw-800">No messages found</h5>
                        <p class="text-muted mb-0">Your inbox is currently clear.</p>
                    </div>
                <?php else: ?>
                    <?php foreach ($messages as $m): ?>
                        <div class="msg-card <?= $m['status'] === 'unread' ? 'unread' : '' ?>">
                            <div class="msg-header">
                                <div class="user-meta-box">
                                    <div class="user-circle"><?= strtoupper(substr($m['name'], 0, 1)) ?></div>
                                    <div>
                                        <h6 class="mb-0 fw-800 text-dark"><?= e($m['name']) ?></h6>
                                        <div class="smallest text-muted"><?= e($m['email']) ?></div>
                                    </div>
                                </div>
                                <div class="text-end">
                                    <div class="smallest fw-800 text-uppercase text-muted mb-2"><?= date('M j, Y • h:i A', strtotime($m['created_at'])) ?></div>
                                    <span class="msg-status-pill <?= $m['status'] === 'unread' ? '' : 'status-read' ?>">
                                        <?= $m['status'] ?>
                                    </span>
                                </div>
                            </div>
                            <div class="msg-body">
                                <div class="subject-line"><?= e($m['subject']) ?></div>
                                <div class="msg-text"><?= nl2br(e($m['message'])) ?></div>
                            </div>
                            <div class="msg-footer">
                                <div class="d-flex gap-3">
                                    <?php if ($m['status'] === 'unread'): ?>
                                        <form method="POST">
                                            <input type="hidden" name="action" value="mark_read">
                                            <input type="hidden" name="id" value="<?= $m['id'] ?>">
                                            <button type="submit" class="btn-mark">
                                                <i class="fas fa-check-circle me-1"></i> Mark as Read
                                            </button>
                                        </form>
                                    <?php endif; ?>
                                    <a href="mailto:<?= e($m['email']) ?>?subject=Re: <?= urlencode($m['subject']) ?>" class="btn-reply">
                                        <i class="fas fa-reply-all"></i> Reply Message
                                    </a>
                                </div>
                                <form method="POST" class="delete-form">
                                    <input type="hidden" name="action" value="delete">
                                    <input type="hidden" name="id" value="<?= $m['id'] ?>">
                                    <button type="button" class="btn-delete-msg delete-btn-trigger" title="Delete Permanent">
                                        <i class="fas fa-trash-alt"></i>
                                    </button>
                                </form>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script>
    document.querySelectorAll('.delete-btn-trigger').forEach(btn => {
        btn.addEventListener('click', function(e) {
            const form = this.closest('form');
            Swal.fire({
                title: 'Are you sure?',
                text: "This message will be permanently deleted!",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#ef4444',
                cancelButtonColor: '#94a3b8',
                confirmButtonText: 'Yes, delete it!',
                cancelButtonText: 'Cancel',
                reverseButtons: true,
                customClass: {
                    popup: 'rounded-5 border-0 shadow-lg',
                    confirmButton: 'rounded-3 fw-bold px-4 py-2',
                    cancelButton: 'rounded-3 fw-bold px-4 py-2'
                }
            }).then((result) => {
                if (result.isConfirmed) {
                    form.submit();
                }
            });
        });
    });
    </script>
    <?php
});
