<?php
// admin/sales.php
require_once dirname(__DIR__) . '/vendor/autoload.php';
require_once dirname(__DIR__) . '/includes/sessions.php';
require_once dirname(__DIR__) . '/includes/env_loader.php';
require_once dirname(__DIR__) . '/includes/functions.php';
require_once dirname(__DIR__) . '/views/admin/layout.php';

use App\Library;
$library = new Library();
$pdo = $library->getPdo();

$currentTab = $_GET['tab'] ?? 'all';

// Handle Status Update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update_status') {
    $orderId = (int)$_POST['order_id'];
    $newStatus = $_POST['status'];
    
    if ($library->updateOrderStatus($orderId, $newStatus)) {
        $_SESSION['success_msg'] = "Order status updated to " . ucfirst($newStatus);
    } else {
        $_SESSION['error_msg'] = "Failed to update order status.";
    }
    header("Location: sales.php?tab=" . $currentTab);
    exit;
}

// Fetch Orders
$query = "SELECT o.*, u.username, u.email FROM orders o JOIN users u ON o.user_id = u.id";
$params = [];

if ($currentTab !== 'all') {
    $query .= " WHERE o.status = ?";
    $params[] = $currentTab;
}
$query .= " ORDER BY o.created_at DESC";

$stmt = $pdo->prepare($query);
$stmt->execute($params);
$orders = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Fetch counts for tabs
$counts = [
    'all' => $pdo->query("SELECT COUNT(*) FROM orders")->fetchColumn(),
    'pending' => $pdo->query("SELECT COUNT(*) FROM orders WHERE status = 'pending'")->fetchColumn(),
    'processing' => $pdo->query("SELECT COUNT(*) FROM orders WHERE status = 'processing'")->fetchColumn(),
    'completed' => $pdo->query("SELECT COUNT(*) FROM orders WHERE status = 'completed'")->fetchColumn(),
    'cancelled' => $pdo->query("SELECT COUNT(*) FROM orders WHERE status = 'cancelled'")->fetchColumn(),
];

// Fetch order items for modals
function getOrderItems($pdo, $orderId) {
    $stmt = $pdo->prepare("SELECT oi.*, b.title, b.cover_image FROM order_items oi JOIN books b ON oi.book_id = b.id WHERE oi.order_id = ?");
    $stmt->execute([$orderId]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

renderAdminLayout('Sales Management', function() use ($orders, $currentTab, $pdo, $counts) {
    ?>
    <style>
        .sales-tabs { border-bottom: 1px solid #eef2f7; margin-bottom: 25px; }
        .sales-tab { padding: 12px 24px; color: #64748b; text-decoration: none; font-weight: 700; border-bottom: 3px solid transparent; transition: 0.2s; display: inline-block; }
        .sales-tab:hover { color: #4e73df; }
        .sales-tab.active { color: #4e73df; border-color: #4e73df; }
        
        .order-card { background: white; border-radius: 16px; border: 1px solid #eef2f7; padding: 20px; transition: 0.2s; position: relative; }
        .order-card:hover { transform: translateY(-2px); box-shadow: 0 10px 25px -10px rgba(0,0,0,0.1); }
        
        .status-badge { padding: 4px 12px; border-radius: 20px; font-size: 11px; font-weight: 800; text-transform: uppercase; letter-spacing: 0.5px; }
        .status-pending { background: #fff7ed; color: #ea580c; border: 1px solid #fed7aa; }
        .status-processing { background: #eff6ff; color: #2563eb; border: 1px solid #dbeafe; }
        .status-completed { background: #f0fdf4; color: #16a34a; border: 1px solid #bbf7d0; }
        .status-cancelled { background: #fef2f2; color: #dc2626; border: 1px solid #fecaca; }
        
        .table-responsive { border-radius: 16px; box-shadow: 0 4px 20px -5px rgba(0,0,0,0.05); }
        .admin-table th { background: #f8fafc; color: #64748b; font-weight: 800; text-transform: uppercase; font-size: 11px; padding: 16px; border:0; }
        .admin-table td { padding: 16px; border-bottom: 1px solid #f8fafc; vertical-align: middle; }
        
        .book-stack img { width: 30px; height: 40px; object-fit: cover; border-radius: 4px; border: 2px solid white; box-shadow: 0 2px 5px rgba(0,0,0,0.1); }
    </style>

    <?php if (isset($_SESSION['success_msg'])): ?>
        <script>Swal.fire({ icon: 'success', title: 'Success', text: "<?= $_SESSION['success_msg'] ?>", timer: 2000, showConfirmButton: false });</script>
        <?php unset($_SESSION['success_msg']); ?>
    <?php endif; ?>

    <div class="sales-tabs">
        <a href="?tab=all" class="sales-tab <?= $currentTab === 'all' ? 'active' : '' ?>">
            All Orders <span class="badge bg-light text-dark border ms-1 fw-bold smallest"><?= $counts['all'] ?></span>
        </a>
        <a href="?tab=pending" class="sales-tab <?= $currentTab === 'pending' ? 'active' : '' ?>">
            Pending <span class="badge bg-light text-dark border ms-1 fw-bold smallest"><?= $counts['pending'] ?></span>
        </a>
        <a href="?tab=processing" class="sales-tab <?= $currentTab === 'processing' ? 'active' : '' ?>">
            Processing <span class="badge bg-light text-dark border ms-1 fw-bold smallest"><?= $counts['processing'] ?></span>
        </a>
        <a href="?tab=completed" class="sales-tab <?= $currentTab === 'completed' ? 'active' : '' ?>">
            Completed <span class="badge bg-light text-dark border ms-1 fw-bold smallest"><?= $counts['completed'] ?></span>
        </a>
        <a href="?tab=cancelled" class="sales-tab <?= $currentTab === 'cancelled' ? 'active' : '' ?>">
            Cancelled <span class="badge bg-light text-dark border ms-1 fw-bold smallest"><?= $counts['cancelled'] ?></span>
        </a>
    </div>

    <div class="table-responsive bg-white">
        <table class="table admin-table mb-0">
            <thead>
                <tr>
                    <th>Order #</th>
                    <th>Customer</th>
                    <th>Books</th>
                    <th>Total</th>
                    <th>Status</th>
                    <th>Date</th>
                    <th class="text-end">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($orders)): ?>
                    <tr><td colspan="7" class="text-center py-5 text-muted">No orders found in this category.</td></tr>
                <?php endif; ?>
                <?php foreach ($orders as $o): 
                    $items = getOrderItems($pdo, $o['id']);
                ?>
                <tr>
                    <td>
                        <div class="fw-800 text-dark small">#<?= e($o['order_number']) ?></div>
                    </td>
                    <td>
                        <div class="fw-800 text-dark smaller"><?= e($o['username']) ?></div>
                        <div class="text-muted smallest"><?= e($o['email']) ?></div>
                    </td>
                    <td>
                        <div class="book-stack d-flex">
                            <?php foreach(array_slice($items, 0, 3) as $i): ?>
                                <img src="<?= baseUrl() ?>/<?= $i['cover_image'] ?>" alt="" onerror="this.src='../assets/img/book-placeholder.png'" style="margin-right: -10px;">
                            <?php endforeach; ?>
                            <?php if (count($items) > 3): ?>
                                <div class="bg-light rounded-circle shadow-sm border d-flex align-items-center justify-content-center fw-800 smallest text-muted ms-2 ps-1" style="width: 30px; height: 30px;">+<?= count($items) - 3 ?></div>
                            <?php endif; ?>
                        </div>
                    </td>
                    <td>
                        <div class="fw-900 text-primary small"><?= number_format($o['total_amount']) ?> Ks</div>
                    </td>
                    <td>
                        <span class="status-badge status-<?= $o['status'] ?>"><?= $o['status'] ?></span>
                    </td>
                    <td>
                        <div class="smaller text-muted fw-700"><?= date('M j, Y', strtotime($o['created_at'])) ?></div>
                    </td>
                    <td class="text-end">
                        <div class="d-flex gap-1 justify-content-end">
                            <button type="button" class="btn btn-primary btn-sm rounded-pill px-3 fw-bold shadow-sm" style="font-size:11px;" onclick="viewOrder(<?= e(json_encode($o)) ?>, <?= e(json_encode($items)) ?>)">
                                <i class="fas fa-eye me-1"></i> Details
                            </button>
                            <div class="dropdown">
                                <button class="btn btn-light btn-sm rounded-pill px-2 shadow-sm border" type="button" data-bs-toggle="dropdown">
                                    <i class="fas fa-ellipsis-v small"></i>
                                </button>
                                <ul class="dropdown-menu dropdown-menu-end shadow-lg border-0 rounded-4 p-2 mt-2">
                                    <li><h6 class="dropdown-header smallest fw-800 opacity-50">SET STATUS</h6></li>
                                    <?php foreach(['pending','processing','completed','cancelled'] as $st): ?>
                                        <?php if ($st === $o['status']) continue; ?>
                                        <li>
                                            <form method="POST">
                                                <input type="hidden" name="action" value="update_status">
                                                <input type="hidden" name="order_id" value="<?= $o['id'] ?>">
                                                <input type="hidden" name="status" value="<?= $st ?>">
                                                <button type="submit" class="dropdown-item py-2 px-3 rounded-3 smallest fw-700 text-<?= $st === 'cancelled' ? 'danger' : 'dark' ?>">
                                                    <i class="fas fa-arrow-right me-2 opacity-50"></i> Mark as <?= ucfirst($st) ?>
                                                </button>
                                            </form>
                                        </li>
                                    <?php endforeach; ?>
                                </ul>
                            </div>
                        </div>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <!-- Order Details Modal -->
    <div class="modal fade" id="orderModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered">
            <div class="modal-content border-0 shadow-lg" style="border-radius:24px;">
                <div class="modal-header border-0 p-4 pb-0">
                    <h4 class="modal-title fw-900" id="order_modal_title">Order Details</h4>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body p-4 pt-4">
                    <div class="row g-4 mb-4">
                        <div class="col-md-6">
                            <div class="p-4 rounded-4 bg-light h-100">
                                <label class="text-muted smallest fw-800 text-uppercase mb-2 d-block">Customer Shipping</label>
                                <h6 class="fw-900 text-dark mb-1" id="order_username">User Name</h6>
                                <p class="text-muted small mb-3" id="order_address">Shipping Address</p>
                                
                                <label class="text-muted smallest fw-800 text-uppercase mb-2 d-block">Logistics</label>
                                <div class="d-flex gap-2">
                                    <span class="badge bg-white text-dark border rounded-pill fw-bold" id="order_delivery">—</span>
                                    <span class="badge bg-white text-dark border rounded-pill fw-bold" id="order_payment">—</span>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="p-4 rounded-4 border h-100">
                                <label class="text-muted smallest fw-800 text-uppercase mb-3 d-block">Financial Breakdown</label>
                                <div class="d-flex justify-content-between mb-2">
                                    <span class="text-muted small fw-700">Subtotal:</span>
                                    <span class="fw-800 text-dark" id="order_subtotal">0 Ks</span>
                                </div>
                                <div class="d-flex justify-content-between mb-2">
                                    <span class="text-muted small fw-700">Shipping:</span>
                                    <span class="fw-800 text-dark" id="order_shipping">0 Ks</span>
                                </div>
                                <div class="d-flex justify-content-between pt-2 border-top">
                                    <span class="text-dark fw-900 fs-5">Total:</span>
                                    <span class="fw-900 text-primary fs-5" id="order_total">0 Ks</span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <label class="text-muted smallest fw-800 text-uppercase mb-3 d-block">Ordered Items</label>
                    <div id="order_items_list">
                        <!-- Items will be injected here -->
                    </div>
                </div>
                <div class="modal-footer border-0 p-4 pt-0">
                    <button type="button" class="btn btn-light rounded-pill px-4 fw-bold" data-bs-dismiss="modal">Close</button>
                    <button type="button" class="btn btn-primary rounded-pill px-4 fw-bold shadow-sm" id="print_invoice_btn"><i class="fas fa-print me-2"></i>Print Invoice</button>
                </div>
            </div>
        </div>
    </div>

    <script>
    function viewOrder(order, items) {
        document.getElementById('order_modal_title').textContent = 'Order #' + order.order_number;
        document.getElementById('order_username').textContent = order.username;
        document.getElementById('order_address').textContent = order.shipping_address;
        document.getElementById('order_delivery').textContent = order.delivery_method || 'Standard';
        document.getElementById('order_payment').textContent = order.payment_method || 'CoD';
        
        const subtotal = parseFloat(order.total_amount) - parseFloat(order.shipping_cost);
        document.getElementById('order_subtotal').textContent = subtotal.toLocaleString() + ' Ks';
        document.getElementById('order_shipping').textContent = parseFloat(order.shipping_cost).toLocaleString() + ' Ks';
        document.getElementById('order_total').textContent = parseFloat(order.total_amount).toLocaleString() + ' Ks';
        
        const listContainer = document.getElementById('order_items_list');
        listContainer.innerHTML = '';
        
        items.forEach(item => {
            const row = document.createElement('div');
            row.className = 'd-flex align-items-center gap-3 p-3 rounded-4 bg-white border mb-2';
            row.innerHTML = `
                <img src="<?= baseUrl() ?>/${item.cover_image}" class="rounded-3 shadow-sm" style="width: 50px; height: 65px; object-fit: cover;">
                <div class="flex-grow-1">
                    <h6 class="mb-0 fw-800 text-dark" style="font-size: 0.9rem;">${item.title}</h6>
                    <div class="text-muted smallest fw-700">Quantity: ${item.quantity}</div>
                </div>
                <div class="text-end">
                    <div class="fw-900 text-dark">${parseFloat(item.price).toLocaleString()} Ks</div>
                    <div class="text-muted smallest fw-700 text-uppercase">Per Unit</div>
                </div>
            `;
            listContainer.appendChild(row);
        });
        
        const printBtn = document.getElementById('print_invoice_btn');
        printBtn.onclick = function() {
            window.open('<?= baseUrl() ?>/print-invoice.php?id=' + order.order_number + '&print=true', '_blank');
        };
        
        const modal = new bootstrap.Modal(document.getElementById('orderModal'));
        modal.show();
    }
    </script>
    <?php
});
