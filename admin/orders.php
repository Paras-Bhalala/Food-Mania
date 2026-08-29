<?php
/**
 * Admin - Manage Orders
 * Food-Mania - Orders table with filters, search, full item breakdown modal, and status updates
 */

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/functions.php';

requireAdmin();

$pdo = getDBConnection();

// Handle status update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_status'])) {
    if (validateCSRFToken($_POST['csrf_token'] ?? '')) {
        $orderId  = (int)$_POST['order_id'];
        $status   = sanitize($_POST['status'] ?? '');
        $allowed  = ['pending','confirmed','preparing','out_for_delivery','delivered','cancelled'];
        
        if ($orderId > 0 && in_array($status, $allowed)) {
            $stmt = $pdo->prepare("UPDATE orders SET status = :status WHERE id = :id");
            $stmt->execute([':status' => $status, ':id' => $orderId]);
            setFlash('success', 'Order #' . str_pad($orderId, 6, '0', STR_PAD_LEFT) . ' status updated to ' . ucfirst(str_replace('_', ' ', $status)) . '!');
        }
    }
    redirect(SITE_URL . '/admin/orders.php');
}

// Build query with filters
$where  = [];
$params = [];

$filterStatus = $_GET['status'] ?? '';
$filterSearch = trim($_GET['search'] ?? '');
$filterFrom   = $_GET['from'] ?? '';
$filterTo     = $_GET['to'] ?? '';

if (!empty($filterStatus)) {
    $where[]  = "o.status = :status";
    $params[':status'] = $filterStatus;
}
if (!empty($filterSearch)) {
    $where[]  = "(u.name LIKE :search OR u.email LIKE :search OR o.phone LIKE :search OR o.id = :search_id)";
    $params[':search'] = '%' . $filterSearch . '%';
    $params[':search_id'] = (int)$filterSearch;
}
if (!empty($filterFrom)) {
    $where[]  = "DATE(o.created_at) >= :from_date";
    $params[':from_date'] = $filterFrom;
}
if (!empty($filterTo)) {
    $where[]  = "DATE(o.created_at) <= :to_date";
    $params[':to_date'] = $filterTo;
}

$whereSQL = !empty($where) ? 'WHERE ' . implode(' AND ', $where) : '';

$stmtOrders = $pdo->prepare("
    SELECT o.*, u.name AS user_name, u.email AS user_email,
           (SELECT COUNT(*) FROM order_items WHERE order_id = o.id) AS item_count
    FROM orders o 
    JOIN users u ON o.user_id = u.id 
    $whereSQL
    ORDER BY o.created_at DESC
");
$stmtOrders->execute($params);
$orders = $stmtOrders->fetchAll();

// Fetch order items mapping for fast client-side modal population
$orderIds = array_column($orders, 'id');
$orderItemsMap = [];
if (!empty($orderIds)) {
    $inPlaceholders = implode(',', array_fill(0, count($orderIds), '?'));
    $stmtItems = $pdo->prepare("
        SELECT oi.*, f.name AS food_name, f.price AS food_unit_price
        FROM order_items oi
        JOIN food_items f ON oi.food_item_id = f.id
        WHERE oi.order_id IN ($inPlaceholders)
    ");
    $stmtItems->execute($orderIds);
    while ($row = $stmtItems->fetch()) {
        $orderItemsMap[$row['order_id']][] = [
            'name'     => $row['food_name'],
            'quantity' => (int)$row['quantity'],
            'price'    => (float)$row['price'],
            'total'    => (float)($row['quantity'] * $row['price'])
        ];
    }
}

// Counts for quick filter pills
$statusCountRows = $pdo->query("SELECT status, COUNT(*) as cnt FROM orders GROUP BY status")->fetchAll();
$statusCounts = array_column($statusCountRows, 'cnt', 'status');
$totalAllOrders = array_sum($statusCounts);

$pageTitle = 'Manage Orders';
include __DIR__ . '/includes/admin_header.php';
include __DIR__ . '/includes/admin_sidebar.php';
?>

<?php echo displayFlash(); ?>

<!-- Page Title & Overview -->
<div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center mb-4 gap-3">
    <div>
        <h4 class="fw-bold mb-1"><i class="fas fa-shopping-bag text-orange me-2"></i> Order Management</h4>
        <p class="text-muted mb-0">View customer orders, track fulfillment states, and inspect item details.</p>
    </div>
</div>

<!-- Status Filter Pills -->
<div class="d-flex flex-wrap gap-2 mb-4">
    <a href="?" class="btn btn-sm <?php echo empty($filterStatus) ? 'btn-primary-custom' : 'btn-light border'; ?> rounded-pill">
        All Orders <span class="badge bg-secondary ms-1"><?php echo $totalAllOrders; ?></span>
    </a>
    <a href="?status=pending" class="btn btn-sm <?php echo $filterStatus === 'pending' ? 'btn-warning text-dark' : 'btn-light border'; ?> rounded-pill">
        Pending <span class="badge bg-warning text-dark ms-1"><?php echo $statusCounts['pending'] ?? 0; ?></span>
    </a>
    <a href="?status=confirmed" class="btn btn-sm <?php echo $filterStatus === 'confirmed' ? 'btn-info text-white' : 'btn-light border'; ?> rounded-pill">
        Confirmed <span class="badge bg-info text-white ms-1"><?php echo $statusCounts['confirmed'] ?? 0; ?></span>
    </a>
    <a href="?status=preparing" class="btn btn-sm <?php echo $filterStatus === 'preparing' ? 'btn-primary' : 'btn-light border'; ?> rounded-pill">
        Preparing <span class="badge bg-primary ms-1"><?php echo $statusCounts['preparing'] ?? 0; ?></span>
    </a>
    <a href="?status=out_for_delivery" class="btn btn-sm <?php echo $filterStatus === 'out_for_delivery' ? 'btn-primary-custom' : 'btn-light border'; ?> rounded-pill">
        Out for Delivery <span class="badge bg-orange ms-1"><?php echo $statusCounts['out_for_delivery'] ?? 0; ?></span>
    </a>
    <a href="?status=delivered" class="btn btn-sm <?php echo $filterStatus === 'delivered' ? 'btn-success' : 'btn-light border'; ?> rounded-pill">
        Delivered <span class="badge bg-success ms-1"><?php echo $statusCounts['delivered'] ?? 0; ?></span>
    </a>
    <a href="?status=cancelled" class="btn btn-sm <?php echo $filterStatus === 'cancelled' ? 'btn-danger' : 'btn-light border'; ?> rounded-pill">
        Cancelled <span class="badge bg-danger ms-1"><?php echo $statusCounts['cancelled'] ?? 0; ?></span>
    </a>
</div>

<!-- Filters & Search Form -->
<div class="checkout-card mb-4">
    <form method="GET" class="row g-3 align-items-end">
        <div class="col-md-4">
            <label class="form-label small fw-600">Search Customer / Phone / Order ID</label>
            <input type="text" class="form-control form-control-sm" name="search" value="<?php echo sanitize($filterSearch); ?>" placeholder="e.g., Rahul, 9876543210, 4">
        </div>
        <div class="col-md-2">
            <label class="form-label small fw-600">From Date</label>
            <input type="date" class="form-control form-control-sm" name="from" value="<?php echo sanitize($filterFrom); ?>">
        </div>
        <div class="col-md-2">
            <label class="form-label small fw-600">To Date</label>
            <input type="date" class="form-control form-control-sm" name="to" value="<?php echo sanitize($filterTo); ?>">
        </div>
        <div class="col-md-2">
            <label class="form-label small fw-600">Status</label>
            <select class="form-select form-select-sm" name="status">
                <option value="">All Statuses</option>
                <?php foreach (['pending','confirmed','preparing','out_for_delivery','delivered','cancelled'] as $s): ?>
                    <option value="<?php echo $s; ?>" <?php echo $filterStatus === $s ? 'selected' : ''; ?>>
                        <?php echo ucfirst(str_replace('_', ' ', $s)); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-md-2">
            <button type="submit" class="btn btn-primary-custom btn-sm rounded-pill w-100">
                <i class="fas fa-search me-1"></i> Filter
            </button>
        </div>
    </form>
</div>

<!-- Orders Table -->
<div class="checkout-card">
    <div class="table-responsive">
        <table class="table table-custom align-middle mb-0">
            <thead>
                <tr>
                    <th>Order ID</th>
                    <th>Customer</th>
                    <th>Items</th>
                    <th>Total Amount</th>
                    <th>Payment</th>
                    <th>Status</th>
                    <th>Placed On</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($orders as $o): ?>
                    <?php 
                    $itemsData = json_encode($orderItemsMap[$o['id']] ?? []);
                    ?>
                    <tr>
                        <td class="fw-bold text-primary">#<?php echo str_pad($o['id'], 6, '0', STR_PAD_LEFT); ?></td>
                        <td>
                            <div class="fw-600"><?php echo sanitize($o['user_name']); ?></div>
                            <small class="text-muted"><?php echo sanitize($o['user_email']); ?> • <?php echo sanitize($o['phone']); ?></small>
                        </td>
                        <td>
                            <span class="badge bg-light text-dark border"><?php echo $o['item_count']; ?> item(s)</span>
                        </td>
                        <td class="fw-bold text-orange"><?php echo formatPrice($o['total_amount']); ?></td>
                        <td>
                            <span class="badge bg-secondary"><?php echo strtoupper($o['payment_method']); ?></span>
                        </td>
                        <td><?php echo getStatusBadge($o['status']); ?></td>
                        <td><small class="text-muted"><?php echo formatDate($o['created_at'], 'd M Y, h:i A'); ?></small></td>
                        <td>
                            <button class="btn btn-action view" data-bs-toggle="modal" data-bs-target="#orderDetailModal"
                                    data-id="<?php echo $o['id']; ?>"
                                    data-customer="<?php echo sanitize($o['user_name']); ?>"
                                    data-email="<?php echo sanitize($o['user_email']); ?>"
                                    data-phone="<?php echo sanitize($o['phone']); ?>"
                                    data-address="<?php echo sanitize($o['delivery_address']); ?>"
                                    data-amount="<?php echo formatPrice($o['total_amount']); ?>"
                                    data-payment="<?php echo strtoupper($o['payment_method']); ?>"
                                    data-status="<?php echo $o['status']; ?>"
                                    data-date="<?php echo formatDate($o['created_at']); ?>"
                                    data-items="<?php echo htmlspecialchars($itemsData, ENT_QUOTES, 'UTF-8'); ?>"
                                    title="View Full Order Details">
                                <i class="fas fa-eye"></i>
                            </button>
                        </td>
                    </tr>
                <?php endforeach; ?>
                <?php if (empty($orders)): ?>
                    <tr><td colspan="8" class="text-center text-muted py-4">No orders found matching the filter criteria.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Order Detail + Status Update Modal -->
<div class="modal fade" id="orderDetailModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header bg-dark text-white">
                <h5 class="modal-title mb-0">
                    <i class="fas fa-receipt text-warning me-2"></i> Order <span id="modalOrderId" class="fw-bold text-warning"></span>
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-4">
                <!-- Customer and Delivery Meta -->
                <div class="row g-3 mb-4 bg-light p-3 rounded">
                    <div class="col-md-6">
                        <small class="text-muted text-uppercase fw-bold">Customer Details</small>
                        <h6 class="fw-bold mb-1 mt-1" id="modalCustomer"></h6>
                        <div class="small text-muted mb-1"><i class="fas fa-envelope me-1"></i> <span id="modalEmail"></span></div>
                        <div class="small text-muted"><i class="fas fa-phone me-1"></i> <span id="modalPhone"></span></div>
                    </div>
                    <div class="col-md-6">
                        <small class="text-muted text-uppercase fw-bold">Order Details</small>
                        <div class="small mt-1"><strong>Placed On:</strong> <span id="modalDate"></span></div>
                        <div class="small"><strong>Payment Method:</strong> <span id="modalPayment" class="badge bg-secondary ms-1"></span></div>
                        <div class="small mt-1"><strong>Delivery Address:</strong> <span id="modalAddress" class="text-secondary d-block"></span></div>
                    </div>
                </div>

                <!-- Items Table in Modal -->
                <h6 class="fw-bold mb-2"><i class="fas fa-utensils text-orange me-1"></i> Ordered Items</h6>
                <div class="table-responsive mb-3">
                    <table class="table table-sm table-bordered align-middle">
                        <thead class="table-light">
                            <tr>
                                <th>Item</th>
                                <th class="text-center">Qty</th>
                                <th class="text-end">Price</th>
                                <th class="text-end">Subtotal</th>
                            </tr>
                        </thead>
                        <tbody id="modalItemsTableBody">
                            <!-- Injected via JavaScript -->
                        </tbody>
                        <tfoot>
                            <tr class="table-light">
                                <td colspan="3" class="text-end fw-bold">Grand Total:</td>
                                <td class="text-end fw-bold text-orange" id="modalAmount"></td>
                            </tr>
                        </tfoot>
                    </table>
                </div>

                <hr>

                <!-- Update Status Form -->
                <form method="POST" class="d-flex flex-column flex-sm-row align-items-sm-end gap-3">
                    <?php echo csrfField(); ?>
                    <input type="hidden" name="update_status" value="1">
                    <input type="hidden" name="order_id" id="modalStatusOrderId">
                    
                    <div class="flex-grow-1">
                        <label class="form-label fw-bold mb-1"><i class="fas fa-exchange-alt me-1"></i> Update Fulfillment Status</label>
                        <select class="form-select" name="status" id="modalStatusSelect">
                            <option value="pending">🟡 Pending (New Order)</option>
                            <option value="confirmed">🔵 Confirmed</option>
                            <option value="preparing">🍳 Preparing in Kitchen</option>
                            <option value="out_for_delivery">🛵 Out for Delivery</option>
                            <option value="delivered">🟢 Delivered</option>
                            <option value="cancelled">🔴 Cancelled</option>
                        </select>
                    </div>
                    <button type="submit" class="btn btn-primary-custom rounded-pill px-4">
                        <i class="fas fa-save me-1"></i> Save Status
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
document.getElementById('orderDetailModal').addEventListener('show.bs.modal', function(e) {
    const btn = e.relatedTarget;
    document.getElementById('modalOrderId').textContent = '#' + String(btn.dataset.id).padStart(6, '0');
    document.getElementById('modalDate').textContent = btn.dataset.date;
    document.getElementById('modalPayment').textContent = btn.dataset.payment;
    document.getElementById('modalCustomer').textContent = btn.dataset.customer;
    document.getElementById('modalEmail').textContent = btn.dataset.email;
    document.getElementById('modalPhone').textContent = btn.dataset.phone;
    document.getElementById('modalAddress').textContent = btn.dataset.address;
    document.getElementById('modalAmount').textContent = btn.dataset.amount;
    document.getElementById('modalStatusOrderId').value = btn.dataset.id;
    document.getElementById('modalStatusSelect').value = btn.dataset.status;

    // Render items
    const tbody = document.getElementById('modalItemsTableBody');
    tbody.innerHTML = '';
    
    let items = [];
    try {
        items = JSON.parse(btn.dataset.items || '[]');
    } catch(e) {
        items = [];
    }

    if (items.length === 0) {
        tbody.innerHTML = '<tr><td colspan="4" class="text-center text-muted">No items found for this order.</td></tr>';
    } else {
        items.forEach(it => {
            const tr = document.createElement('tr');
            tr.innerHTML = `
                <td class="fw-500">${it.name}</td>
                <td class="text-center">${it.quantity}</td>
                <td class="text-end">₹${parseFloat(it.price).toFixed(2)}</td>
                <td class="text-end fw-600">₹${parseFloat(it.total).toFixed(2)}</td>
            `;
            tbody.appendChild(tr);
        });
    }
});
</script>

        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script src="<?php echo SITE_URL; ?>/assets/js/script.js"></script>
</body>
</html>
