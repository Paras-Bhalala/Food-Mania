<?php
/**
 * Admin - Manage Users & Customer Accounts
 * Food-Mania - User listing, search, spending analytics, and account status management
 */

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/functions.php';

requireAdmin();

$pdo = getDBConnection();

// Handle block/unblock
if (isset($_GET['toggle'])) {
    $userId = (int)$_GET['toggle'];
    if ($userId > 0) {
        $stmt = $pdo->prepare("SELECT status FROM users WHERE id = :id");
        $stmt->execute([':id' => $userId]);
        $user = $stmt->fetch();
        
        if ($user) {
            $newStatus = $user['status'] === 'active' ? 'blocked' : 'active';
            $stmtUpdate = $pdo->prepare("UPDATE users SET status = :status WHERE id = :id");
            $stmtUpdate->execute([':status' => $newStatus, ':id' => $userId]);
            setFlash('success', 'User account ' . ($newStatus === 'blocked' ? 'blocked' : 'unblocked') . ' successfully!');
        }
    }
    redirect(SITE_URL . '/admin/users.php');
}

// Filters
$search = trim($_GET['search'] ?? '');
$statusFilter = $_GET['status'] ?? 'all';

$where = [];
$params = [];

if (!empty($search)) {
    $where[] = "(u.name LIKE :search OR u.email LIKE :search OR u.phone LIKE :search)";
    $params[':search'] = "%$search%";
}

if ($statusFilter !== 'all') {
    $where[] = "u.status = :status";
    $params[':status'] = $statusFilter;
}

$whereSQL = !empty($where) ? 'WHERE ' . implode(' AND ', $where) : '';

// Fetch filtered users with order counts and spending
$stmtUsers = $pdo->prepare("
    SELECT u.*, 
           COUNT(o.id) AS order_count,
           COALESCE(SUM(CASE WHEN o.status != 'cancelled' THEN o.total_amount ELSE 0 END), 0) AS total_spent,
           MAX(o.created_at) AS last_order_date
    FROM users u 
    LEFT JOIN orders o ON u.id = o.user_id 
    $whereSQL
    GROUP BY u.id 
    ORDER BY u.created_at DESC
");
$stmtUsers->execute($params);
$users = $stmtUsers->fetchAll();

// General user statistics
$totalUsersCount = (int)$pdo->query("SELECT COUNT(*) FROM users")->fetchColumn();
$activeUsersCount = (int)$pdo->query("SELECT COUNT(*) FROM users WHERE status = 'active'")->fetchColumn();
$totalCustomerSpend = (float)$pdo->query("SELECT COALESCE(SUM(total_amount), 0) FROM orders WHERE status != 'cancelled'")->fetchColumn();

$pageTitle = 'Manage Customers';
include __DIR__ . '/includes/admin_header.php';
include __DIR__ . '/includes/admin_sidebar.php';
?>

<?php echo displayFlash(); ?>

<!-- Page Title -->
<div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center mb-4 gap-3">
    <div>
        <h4 class="fw-bold mb-1"><i class="fas fa-users text-orange me-2"></i> Customer Directory (<?php echo count($users); ?>)</h4>
        <p class="text-muted mb-0">View customer profile data, lifetime spend, order history, and account status.</p>
    </div>
</div>

<!-- Stats Row -->
<div class="row g-3 mb-4">
    <div class="col-md-4">
        <div class="stat-card users">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <div class="stat-label">Total Customers</div>
                    <div class="stat-value mt-1"><?php echo number_format($totalUsersCount); ?></div>
                </div>
                <div class="stat-icon"><i class="fas fa-users"></i></div>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="stat-card orders">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <div class="stat-label">Active Accounts</div>
                    <div class="stat-value text-success mt-1"><?php echo number_format($activeUsersCount); ?></div>
                </div>
                <div class="stat-icon" style="background:rgba(40,167,69,0.12); color:#28a745;"><i class="fas fa-user-check"></i></div>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="stat-card revenue">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <div class="stat-label">Lifetime Spend</div>
                    <div class="stat-value text-success mt-1"><?php echo formatPrice($totalCustomerSpend); ?></div>
                </div>
                <div class="stat-icon"><i class="fas fa-wallet"></i></div>
            </div>
        </div>
    </div>
</div>

<!-- Filter Bar -->
<div class="checkout-card mb-4">
    <form method="GET" class="row g-3 align-items-end">
        <div class="col-md-6">
            <label class="form-label small fw-600">Search Name, Email or Phone</label>
            <input type="text" class="form-control form-control-sm" name="search" value="<?php echo sanitize($search); ?>" placeholder="e.g., Priya Patel, priya@example.com">
        </div>
        <div class="col-md-3">
            <label class="form-label small fw-600">Account Status</label>
            <select class="form-select form-select-sm" name="status">
                <option value="all" <?php echo $statusFilter === 'all' ? 'selected' : ''; ?>>All Accounts</option>
                <option value="active" <?php echo $statusFilter === 'active' ? 'selected' : ''; ?>>Active Only</option>
                <option value="blocked" <?php echo $statusFilter === 'blocked' ? 'selected' : ''; ?>>Blocked Only</option>
            </select>
        </div>
        <div class="col-md-3">
            <button type="submit" class="btn btn-primary-custom btn-sm rounded-pill w-100">
                <i class="fas fa-search me-1"></i> Filter Customers
            </button>
        </div>
    </form>
</div>

<!-- Users Table -->
<div class="checkout-card">
    <div class="table-responsive">
        <table class="table table-custom align-middle mb-0">
            <thead>
                <tr>
                    <th>#</th>
                    <th>Customer Name</th>
                    <th>Email</th>
                    <th>Phone</th>
                    <th>Orders</th>
                    <th>Total Spend</th>
                    <th>Last Order</th>
                    <th>Status</th>
                    <th>Joined</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($users as $i => $u): ?>
                    <tr>
                        <td><?php echo $i + 1; ?></td>
                        <td>
                            <div class="fw-600"><?php echo sanitize($u['name']); ?></div>
                            <?php if (!empty($u['address'])): ?>
                                <small class="text-muted text-truncate d-inline-block" style="max-width: 180px;" title="<?php echo sanitize($u['address']); ?>">
                                    <i class="fas fa-map-marker-alt me-1"></i> <?php echo sanitize($u['address']); ?>
                                </small>
                            <?php endif; ?>
                        </td>
                        <td><?php echo sanitize($u['email']); ?></td>
                        <td><?php echo sanitize($u['phone'] ?: '—'); ?></td>
                        <td>
                            <a href="<?php echo SITE_URL; ?>/admin/orders.php?search=<?php echo urlencode($u['email']); ?>" class="badge bg-light text-dark border text-decoration-none">
                                <?php echo $u['order_count']; ?> order(s)
                            </a>
                        </td>
                        <td class="fw-bold text-orange"><?php echo formatPrice($u['total_spent']); ?></td>
                        <td>
                            <small class="text-muted">
                                <?php echo $u['last_order_date'] ? formatDate($u['last_order_date'], 'd M Y') : 'Never'; ?>
                            </small>
                        </td>
                        <td>
                            <?php if ($u['status'] === 'active'): ?>
                                <span class="badge bg-success">Active</span>
                            <?php else: ?>
                                <span class="badge bg-danger">Blocked</span>
                            <?php endif; ?>
                        </td>
                        <td><small class="text-muted"><?php echo formatDate($u['created_at'], 'd M Y'); ?></small></td>
                        <td>
                            <a href="?toggle=<?php echo $u['id']; ?>" 
                               class="btn btn-sm <?php echo $u['status'] === 'active' ? 'btn-outline-danger' : 'btn-outline-success'; ?> rounded-pill"
                               onclick="return confirm('<?php echo $u['status'] === 'active' ? 'Block this user account?' : 'Unblock this user account?'; ?>')">
                                <?php if ($u['status'] === 'active'): ?>
                                    <i class="fas fa-ban me-1"></i> Block
                                <?php else: ?>
                                    <i class="fas fa-check me-1"></i> Unblock
                                <?php endif; ?>
                            </a>
                        </td>
                    </tr>
                <?php endforeach; ?>
                <?php if (empty($users)): ?>
                    <tr><td colspan="10" class="text-center text-muted py-4">No customers found.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script src="<?php echo SITE_URL; ?>/assets/js/script.js"></script>
</body>
</html>
