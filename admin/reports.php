<?php
/**
 * Admin - Comprehensive Sales & Analytical Reports
 * Food-Mania - Multi-tab / Multi-filter reports with CSV Export and Printable PDF Mode
 */

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/functions.php';

requireAdmin();

$pdo = getDBConnection();

// Preset Date ranges
$preset = $_GET['preset'] ?? 'this_month';
$fromDate = $_GET['from'] ?? '';
$toDate   = $_GET['to'] ?? '';
$filterStatus = $_GET['status'] ?? 'all';
$filterPayment = $_GET['payment'] ?? 'all';

// Calculate dates based on preset if from/to not explicitly set
if (empty($fromDate) || empty($toDate)) {
    switch ($preset) {
        case 'today':
            $fromDate = date('Y-m-d');
            $toDate   = date('Y-m-d');
            break;
        case 'yesterday':
            $fromDate = date('Y-m-d', strtotime('-1 day'));
            $toDate   = date('Y-m-d', strtotime('-1 day'));
            break;
        case 'last_7_days':
            $fromDate = date('Y-m-d', strtotime('-6 days'));
            $toDate   = date('Y-m-d');
            break;
        case 'last_30_days':
            $fromDate = date('Y-m-d', strtotime('-29 days'));
            $toDate   = date('Y-m-d');
            break;
        case 'last_month':
            $fromDate = date('Y-m-01', strtotime('first day of last month'));
            $toDate   = date('Y-m-t', strtotime('last day of last month'));
            break;
        case 'this_month':
        default:
            $fromDate = date('Y-m-01');
            $toDate   = date('Y-m-d');
            $preset   = 'this_month';
            break;
    }
} else {
    $preset = 'custom';
}

// Build query conditions
$whereOrders = ["DATE(o.created_at) BETWEEN :from_date AND :to_date"];
$paramsOrders = [
    ':from_date' => $fromDate,
    ':to_date'   => $toDate
];

if ($filterStatus !== 'all') {
    $whereOrders[] = "o.status = :status";
    $paramsOrders[':status'] = $filterStatus;
}

if ($filterPayment !== 'all') {
    $whereOrders[] = "o.payment_method = :payment";
    $paramsOrders[':payment'] = $filterPayment;
}

$whereSQL = implode(' AND ', $whereOrders);

// --- CSV Export Handler ---
if (isset($_GET['export']) && $_GET['export'] === 'csv') {
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="food_mania_sales_report_' . $fromDate . '_to_' . $toDate . '.csv"');
    
    $output = fopen('php://output', 'w');
    // Header row
    fputcsv($output, ['Order ID', 'Date & Time', 'Customer Name', 'Customer Email', 'Phone', 'Items Count', 'Payment Method', 'Status', 'Total Amount (INR)']);
    
    $stmtCSV = $pdo->prepare("
        SELECT o.id, o.created_at, u.name AS user_name, u.email AS user_email, o.phone,
               (SELECT COUNT(*) FROM order_items WHERE order_id = o.id) AS item_count,
               o.payment_method, o.status, o.total_amount
        FROM orders o 
        JOIN users u ON o.user_id = u.id
        WHERE $whereSQL
        ORDER BY o.created_at DESC
    ");
    $stmtCSV->execute($paramsOrders);
    
    while ($row = $stmtCSV->fetch()) {
        fputcsv($output, [
            '#' . str_pad($row['id'], 6, '0', STR_PAD_LEFT),
            date('d M Y, h:i A', strtotime($row['created_at'])),
            $row['user_name'],
            $row['user_email'],
            $row['phone'],
            $row['item_count'],
            strtoupper($row['payment_method']),
            ucwords(str_replace('_', ' ', $row['status'])),
            number_format($row['total_amount'], 2, '.', '')
        ]);
    }
    
    fclose($output);
    exit;
}

// --- Summary Metrics ---
// Total orders in period
$stmtSumm = $pdo->prepare("
    SELECT 
        COUNT(*) AS total_orders,
        COALESCE(SUM(CASE WHEN o.status != 'cancelled' THEN o.total_amount ELSE 0 END), 0) AS net_revenue,
        COALESCE(SUM(o.total_amount), 0) AS gross_revenue,
        COALESCE(SUM(CASE WHEN o.status = 'cancelled' THEN o.total_amount ELSE 0 END), 0) AS lost_revenue,
        COALESCE(SUM(CASE WHEN o.status = 'delivered' THEN 1 ELSE 0 END), 0) AS delivered_orders,
        COALESCE(SUM(CASE WHEN o.status = 'cancelled' THEN 1 ELSE 0 END), 0) AS cancelled_orders,
        COALESCE(AVG(CASE WHEN o.status != 'cancelled' THEN o.total_amount ELSE NULL END), 0) AS avg_order_val,
        COUNT(DISTINCT o.user_id) AS unique_customers
    FROM orders o
    WHERE $whereSQL
");
$stmtSumm->execute($paramsOrders);
$summary = $stmtSumm->fetch();

// --- Daily Breakdown for Chart & Table ---
$stmtDaily = $pdo->prepare("
    SELECT 
        DATE(o.created_at) AS order_date,
        COUNT(*) AS order_count,
        COALESCE(SUM(CASE WHEN o.status != 'cancelled' THEN o.total_amount ELSE 0 END), 0) AS daily_revenue,
        COALESCE(SUM(CASE WHEN o.status = 'delivered' THEN 1 ELSE 0 END), 0) AS delivered_count
    FROM orders o
    WHERE $whereSQL
    GROUP BY DATE(o.created_at)
    ORDER BY order_date ASC
");
$stmtDaily->execute($paramsOrders);
$dailyData = $stmtDaily->fetchAll();

// --- Top 10 Best-Selling Items in Period ---
$stmtTop = $pdo->prepare("
    SELECT f.id, f.name, c.name AS category_name, f.price,
           SUM(oi.quantity) AS total_quantity,
           SUM(oi.quantity * oi.price) AS total_revenue
    FROM order_items oi
    JOIN food_items f ON oi.food_item_id = f.id
    JOIN categories c ON f.category_id = c.id
    JOIN orders o ON oi.order_id = o.id
    WHERE $whereSQL AND o.status != 'cancelled'
    GROUP BY f.id
    ORDER BY total_revenue DESC
    LIMIT 10
");
$stmtTop->execute($paramsOrders);
$topItems = $stmtTop->fetchAll();

// --- Category Performance in Period ---
$stmtCat = $pdo->prepare("
    SELECT c.name, 
           COUNT(DISTINCT oi.id) AS items_ordered,
           SUM(oi.quantity) AS units_sold,
           COALESCE(SUM(oi.quantity * oi.price), 0) AS category_revenue
    FROM categories c
    JOIN food_items f ON c.id = f.category_id
    JOIN order_items oi ON f.id = oi.food_item_id
    JOIN orders o ON oi.order_id = o.id
    WHERE $whereSQL AND o.status != 'cancelled'
    GROUP BY c.id
    ORDER BY category_revenue DESC
");
$stmtCat->execute($paramsOrders);
$categoryPerf = $stmtCat->fetchAll();

// --- Top Customers in Period ---
$stmtCust = $pdo->prepare("
    SELECT u.id, u.name, u.email, u.phone,
           COUNT(o.id) AS order_count,
           SUM(CASE WHEN o.status != 'cancelled' THEN o.total_amount ELSE 0 END) AS spent
    FROM users u
    JOIN orders o ON u.id = o.user_id
    WHERE $whereSQL
    GROUP BY u.id
    ORDER BY spent DESC
    LIMIT 5
");
$stmtCust->execute($paramsOrders);
$topCustomers = $stmtCust->fetchAll();

// --- Detailed Orders in Period ---
$stmtOrders = $pdo->prepare("
    SELECT o.*, u.name AS user_name, u.email AS user_email,
           (SELECT COUNT(*) FROM order_items WHERE order_id = o.id) AS item_count
    FROM orders o 
    JOIN users u ON o.user_id = u.id
    WHERE $whereSQL
    ORDER BY o.created_at DESC
");
$stmtOrders->execute($paramsOrders);
$allOrders = $stmtOrders->fetchAll();

// Print Mode Check
$isPrintMode = isset($_GET['print']) && $_GET['print'] == 1;

if ($isPrintMode) {
    // Render clean printable version
    ?>
    <!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="UTF-8">
        <title>Sales Report (<?php echo $fromDate; ?> to <?php echo $toDate; ?>) - Food-Mania</title>
        <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
        <style>
            body { font-family: sans-serif; color: #333; padding: 20px; }
            .header-box { border-bottom: 2px solid #FF6B35; padding-bottom: 15px; margin-bottom: 20px; }
            .badge-status { font-size: 0.8rem; padding: 3px 8px; border-radius: 4px; border: 1px solid #ccc; }
            @media print {
                .no-print { display: none !important; }
            }
        </style>
    </head>
    <body>
        <div class="no-print mb-3 text-end">
            <button onclick="window.print()" class="btn btn-primary btn-sm"><i class="fas fa-print"></i> Print / Save as PDF</button>
            <button onclick="window.close()" class="btn btn-secondary btn-sm">Close</button>
        </div>

        <div class="header-box d-flex justify-content-between align-items-center">
            <div>
                <h2 class="mb-0 text-danger fw-bold">Food<span style="color:#FFC107;">Mania</span> Restaurant</h2>
                <p class="mb-0 text-muted">Official Financial & Operations Report</p>
            </div>
            <div class="text-end">
                <h5 class="mb-0">Period: <?php echo date('d M Y', strtotime($fromDate)); ?> - <?php echo date('d M Y', strtotime($toDate)); ?></h5>
                <small class="text-muted">Generated on: <?php echo date('d M Y, h:i A'); ?></small>
            </div>
        </div>

        <div class="row g-3 mb-4">
            <div class="col-3">
                <div class="p-3 border rounded">
                    <small class="text-muted d-block">Net Revenue</small>
                    <h4 class="fw-bold text-success mb-0"><?php echo formatPrice($summary['net_revenue']); ?></h4>
                </div>
            </div>
            <div class="col-3">
                <div class="p-3 border rounded">
                    <small class="text-muted d-block">Total Orders</small>
                    <h4 class="fw-bold mb-0"><?php echo $summary['total_orders']; ?> (<?php echo $summary['delivered_orders']; ?> Delivered)</h4>
                </div>
            </div>
            <div class="col-3">
                <div class="p-3 border rounded">
                    <small class="text-muted d-block">Avg Order Value</small>
                    <h4 class="fw-bold mb-0"><?php echo formatPrice($summary['avg_order_val']); ?></h4>
                </div>
            </div>
            <div class="col-3">
                <div class="p-3 border rounded">
                    <small class="text-muted d-block">Unique Customers</small>
                    <h4 class="fw-bold mb-0"><?php echo $summary['unique_customers']; ?></h4>
                </div>
            </div>
        </div>

        <h5>Orders Statement (<?php echo count($allOrders); ?> total)</h5>
        <table class="table table-bordered table-sm mt-2" style="font-size:0.85rem;">
            <thead class="table-light">
                <tr>
                    <th>Order #</th>
                    <th>Date</th>
                    <th>Customer</th>
                    <th>Phone</th>
                    <th>Items</th>
                    <th>Payment</th>
                    <th>Status</th>
                    <th class="text-end">Total Amount</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($allOrders as $ord): ?>
                    <tr>
                        <td>#<?php echo str_pad($ord['id'], 6, '0', STR_PAD_LEFT); ?></td>
                        <td><?php echo date('d M Y, h:i A', strtotime($ord['created_at'])); ?></td>
                        <td><?php echo sanitize($ord['user_name']); ?></td>
                        <td><?php echo sanitize($ord['phone']); ?></td>
                        <td><?php echo $ord['item_count']; ?></td>
                        <td><?php echo strtoupper($ord['payment_method']); ?></td>
                        <td><?php echo ucfirst(str_replace('_', ' ', $ord['status'])); ?></td>
                        <td class="text-end fw-bold"><?php echo formatPrice($ord['total_amount']); ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>

        <div class="row mt-5 pt-4 text-center">
            <div class="col-6">
                <p class="border-top pt-2 text-muted" style="width:200px; margin:auto;">Prepared By (Admin)</p>
            </div>
            <div class="col-6">
                <p class="border-top pt-2 text-muted" style="width:200px; margin:auto;">Authorized Signature</p>
            </div>
        </div>
    </body>
    </html>
    <?php
    exit;
}

$pageTitle = 'Reports & Analytics';
include __DIR__ . '/includes/admin_header.php';
include __DIR__ . '/includes/admin_sidebar.php';
?>

<?php echo displayFlash(); ?>

<!-- Page Title & Actions -->
<div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center mb-4 gap-3">
    <div>
        <h4 class="fw-bold mb-1"><i class="fas fa-chart-bar text-orange me-2"></i> Business Reports & Analytics</h4>
        <p class="text-muted mb-0">Track revenue, order volumes, bestselling menu items, and customer analytics.</p>
    </div>
    <div class="d-flex flex-wrap gap-2">
        <a href="?<?php echo http_build_query(array_merge($_GET, ['export' => 'csv'])); ?>" class="btn btn-success rounded-pill px-3">
            <i class="fas fa-file-csv me-1"></i> Export to CSV
        </a>
        <a href="?<?php echo http_build_query(array_merge($_GET, ['print' => '1'])); ?>" target="_blank" class="btn btn-outline-dark rounded-pill px-3">
            <i class="fas fa-print me-1"></i> Print / PDF Report
        </a>
    </div>
</div>

<!-- Filters Bar -->
<div class="checkout-card mb-4">
    <!-- Preset quick filter tabs -->
    <div class="d-flex flex-wrap gap-2 mb-3 pb-3 border-bottom">
        <span class="fw-600 align-self-center me-2 text-muted small"><i class="fas fa-calendar-alt me-1"></i> Quick Ranges:</span>
        <a href="?preset=today" class="btn btn-sm <?php echo $preset === 'today' ? 'btn-primary-custom' : 'btn-light border'; ?> rounded-pill">Today</a>
        <a href="?preset=yesterday" class="btn btn-sm <?php echo $preset === 'yesterday' ? 'btn-primary-custom' : 'btn-light border'; ?> rounded-pill">Yesterday</a>
        <a href="?preset=last_7_days" class="btn btn-sm <?php echo $preset === 'last_7_days' ? 'btn-primary-custom' : 'btn-light border'; ?> rounded-pill">Last 7 Days</a>
        <a href="?preset=this_month" class="btn btn-sm <?php echo $preset === 'this_month' ? 'btn-primary-custom' : 'btn-light border'; ?> rounded-pill">This Month</a>
        <a href="?preset=last_month" class="btn btn-sm <?php echo $preset === 'last_month' ? 'btn-primary-custom' : 'btn-light border'; ?> rounded-pill">Last Month</a>
    </div>

    <!-- Custom Search Form -->
    <form method="GET" class="row g-3 align-items-end">
        <input type="hidden" name="preset" value="custom">
        <div class="col-md-3 col-sm-6">
            <label class="form-label small fw-600">From Date</label>
            <input type="date" class="form-control form-control-sm" name="from" value="<?php echo sanitize($fromDate); ?>" required>
        </div>
        <div class="col-md-3 col-sm-6">
            <label class="form-label small fw-600">To Date</label>
            <input type="date" class="form-control form-control-sm" name="to" value="<?php echo sanitize($toDate); ?>" required>
        </div>
        <div class="col-md-2 col-sm-6">
            <label class="form-label small fw-600">Status</label>
            <select class="form-select form-select-sm" name="status">
                <option value="all" <?php echo $filterStatus === 'all' ? 'selected' : ''; ?>>All Statuses</option>
                <option value="delivered" <?php echo $filterStatus === 'delivered' ? 'selected' : ''; ?>>Delivered</option>
                <option value="pending" <?php echo $filterStatus === 'pending' ? 'selected' : ''; ?>>Pending</option>
                <option value="confirmed" <?php echo $filterStatus === 'confirmed' ? 'selected' : ''; ?>>Confirmed</option>
                <option value="preparing" <?php echo $filterStatus === 'preparing' ? 'selected' : ''; ?>>Preparing</option>
                <option value="out_for_delivery" <?php echo $filterStatus === 'out_for_delivery' ? 'selected' : ''; ?>>Out for Delivery</option>
                <option value="cancelled" <?php echo $filterStatus === 'cancelled' ? 'selected' : ''; ?>>Cancelled</option>
            </select>
        </div>
        <div class="col-md-2 col-sm-6">
            <label class="form-label small fw-600">Payment</label>
            <select class="form-select form-select-sm" name="payment">
                <option value="all" <?php echo $filterPayment === 'all' ? 'selected' : ''; ?>>All Methods</option>
                <option value="cod" <?php echo $filterPayment === 'cod' ? 'selected' : ''; ?>>Cash on Delivery</option>
                <option value="online" <?php echo $filterPayment === 'online' ? 'selected' : ''; ?>>Online / UPI</option>
            </select>
        </div>
        <div class="col-md-2 col-12">
            <button type="submit" class="btn btn-primary-custom btn-sm rounded-pill w-100">
                <i class="fas fa-filter me-1"></i> Apply Filter
            </button>
        </div>
    </form>
</div>

<!-- Report Summary Cards -->
<div class="row g-3 mb-4">
    <!-- Net Revenue -->
    <div class="col-sm-6 col-xl-3">
        <div class="stat-card revenue">
            <div class="d-flex justify-content-between align-items-start">
                <div>
                    <div class="stat-label">Net Sales Revenue</div>
                    <div class="stat-value text-success mt-1"><?php echo formatPrice($summary['net_revenue']); ?></div>
                </div>
                <div class="stat-icon"><i class="fas fa-wallet"></i></div>
            </div>
            <div class="mt-2 text-muted small">
                <span>Gross: <?php echo formatPrice($summary['gross_revenue']); ?></span>
            </div>
        </div>
    </div>

    <!-- Total Orders & Fulfillment Rate -->
    <div class="col-sm-6 col-xl-3">
        <div class="stat-card orders">
            <div class="d-flex justify-content-between align-items-start">
                <div>
                    <div class="stat-label">Total Orders in Period</div>
                    <div class="stat-value mt-1"><?php echo number_format($summary['total_orders']); ?></div>
                </div>
                <div class="stat-icon"><i class="fas fa-shopping-bag"></i></div>
            </div>
            <div class="mt-2 text-muted small">
                <span class="text-success fw-600"><?php echo $summary['delivered_orders']; ?> delivered</span> • 
                <span class="text-danger fw-600"><?php echo $summary['cancelled_orders']; ?> cancelled</span>
            </div>
        </div>
    </div>

    <!-- Avg Order Value -->
    <div class="col-sm-6 col-xl-3">
        <div class="stat-card users">
            <div class="d-flex justify-content-between align-items-start">
                <div>
                    <div class="stat-label">Average Order Value</div>
                    <div class="stat-value mt-1"><?php echo formatPrice($summary['avg_order_val']); ?></div>
                </div>
                <div class="stat-icon" style="background:rgba(0,123,255,0.12);color:#007bff;"><i class="fas fa-receipt"></i></div>
            </div>
            <div class="mt-2 text-muted small">
                <span>Per active successful order</span>
            </div>
        </div>
    </div>

    <!-- Unique Customers -->
    <div class="col-sm-6 col-xl-3">
        <div class="stat-card pending">
            <div class="d-flex justify-content-between align-items-start">
                <div>
                    <div class="stat-label">Paying Customers</div>
                    <div class="stat-value mt-1"><?php echo number_format($summary['unique_customers']); ?></div>
                </div>
                <div class="stat-icon"><i class="fas fa-user-check"></i></div>
            </div>
            <div class="mt-2 text-muted small">
                <span>Unique ordering users</span>
            </div>
        </div>
    </div>
</div>

<!-- Daily Sales Trend Chart -->
<?php if (!empty($dailyData)): ?>
<div class="checkout-card mb-4">
    <h5 class="fw-bold mb-3"><i class="fas fa-chart-area text-orange me-2"></i> Daily Sales & Order Performance</h5>
    <canvas id="dailyReportChart" height="90"></canvas>
</div>
<?php endif; ?>

<!-- Two Columns: Top Items & Category Breakdown -->
<div class="row g-4 mb-4">
    <!-- Top Selling Dishes -->
    <div class="col-lg-7">
        <div class="checkout-card h-100">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h5 class="fw-bold mb-0"><i class="fas fa-utensils text-orange me-2"></i> Top Selling Menu Items</h5>
                <span class="badge bg-light text-dark"><?php echo count($topItems); ?> items</span>
            </div>

            <?php if (empty($topItems)): ?>
                <p class="text-muted py-4 text-center">No item sales recorded in this period.</p>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-custom align-middle mb-0">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Item Name</th>
                                <th>Category</th>
                                <th>Unit Price</th>
                                <th>Qty Sold</th>
                                <th>Total Revenue</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($topItems as $idx => $it): ?>
                                <tr>
                                    <td>
                                        <span class="badge rounded-circle <?php echo $idx === 0 ? 'bg-warning text-dark' : 'bg-light text-dark border'; ?>" style="width:24px;height:24px;display:inline-flex;align-items:center;justify-content:center;">
                                            <?php echo $idx + 1; ?>
                                        </span>
                                    </td>
                                    <td class="fw-600"><?php echo sanitize($it['name']); ?></td>
                                    <td><span class="badge bg-secondary"><?php echo sanitize($it['category_name']); ?></span></td>
                                    <td><?php echo formatPrice($it['price']); ?></td>
                                    <td class="fw-bold text-center"><?php echo $it['total_quantity']; ?></td>
                                    <td class="fw-bold text-success"><?php echo formatPrice($it['total_revenue']); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Category Revenue Performance -->
    <div class="col-lg-5">
        <div class="checkout-card h-100">
            <h5 class="fw-bold mb-3"><i class="fas fa-th-list text-orange me-2"></i> Category Performance</h5>
            
            <?php if (empty($categoryPerf)): ?>
                <p class="text-muted py-4 text-center">No category sales recorded.</p>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-custom align-middle mb-0">
                        <thead>
                            <tr>
                                <th>Category</th>
                                <th>Units</th>
                                <th>Revenue</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($categoryPerf as $cat): ?>
                                <tr>
                                    <td class="fw-600"><?php echo sanitize($cat['name']); ?></td>
                                    <td><?php echo $cat['units_sold']; ?></td>
                                    <td class="fw-bold text-orange"><?php echo formatPrice($cat['category_revenue']); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>

            <!-- Top Customers Widget -->
            <h6 class="fw-bold mt-4 mb-2"><i class="fas fa-star text-warning me-1"></i> Top Customers in Period</h6>
            <div class="list-group list-group-flush">
                <?php foreach ($topCustomers as $c): ?>
                    <div class="list-group-item px-0 py-2 d-flex justify-content-between align-items-center">
                        <div>
                            <div class="fw-600 small"><?php echo sanitize($c['name']); ?></div>
                            <small class="text-muted"><?php echo $c['order_count']; ?> orders placed</small>
                        </div>
                        <span class="fw-bold text-success"><?php echo formatPrice($c['spent']); ?></span>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
</div>

<!-- Detailed Orders Log Table -->
<div class="checkout-card mb-4">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h5 class="fw-bold mb-0">
            <i class="fas fa-list-alt text-orange me-2"></i> Orders Transaction Log (<?php echo count($allOrders); ?>)
        </h5>
    </div>

    <div class="table-responsive">
        <table class="table table-custom align-middle mb-0">
            <thead>
                <tr>
                    <th>Order ID</th>
                    <th>Date & Time</th>
                    <th>Customer</th>
                    <th>Phone</th>
                    <th>Items</th>
                    <th>Payment</th>
                    <th>Status</th>
                    <th>Amount</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($allOrders as $o): ?>
                    <tr>
                        <td class="fw-bold text-primary">#<?php echo str_pad($o['id'], 6, '0', STR_PAD_LEFT); ?></td>
                        <td><small class="text-muted"><?php echo formatDate($o['created_at'], 'd M Y, h:i A'); ?></small></td>
                        <td>
                            <div class="fw-600"><?php echo sanitize($o['user_name']); ?></div>
                            <small class="text-muted"><?php echo sanitize($o['user_email']); ?></small>
                        </td>
                        <td><?php echo sanitize($o['phone']); ?></td>
                        <td><span class="badge bg-light text-dark"><?php echo $o['item_count']; ?> items</span></td>
                        <td><span class="badge bg-secondary"><?php echo strtoupper($o['payment_method']); ?></span></td>
                        <td><?php echo getStatusBadge($o['status']); ?></td>
                        <td class="fw-bold text-orange"><?php echo formatPrice($o['total_amount']); ?></td>
                    </tr>
                <?php endforeach; ?>
                <?php if (empty($allOrders)): ?>
                    <tr>
                        <td colspan="8" class="text-center py-4 text-muted">
                            No orders found matching the filter criteria.
                        </td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php if (!empty($dailyData)): ?>
<script>
const dailyLabels = <?php echo json_encode(array_map(fn($d) => date('d M', strtotime($d['order_date'])), $dailyData)); ?>;
const dailyRevenue = <?php echo json_encode(array_map(fn($d) => (float)$d['daily_revenue'], $dailyData)); ?>;
const dailyOrders = <?php echo json_encode(array_map(fn($d) => (int)$d['order_count'], $dailyData)); ?>;

const reportCtx = document.getElementById('dailyReportChart').getContext('2d');
new Chart(reportCtx, {
    type: 'line',
    data: {
        labels: dailyLabels,
        datasets: [
            {
                label: 'Revenue (₹)',
                data: dailyRevenue,
                borderColor: '#28A745',
                backgroundColor: 'rgba(40, 167, 69, 0.15)',
                tension: 0.3,
                fill: true,
                borderWidth: 2,
                yAxisID: 'y'
            },
            {
                label: 'Orders Count',
                data: dailyOrders,
                borderColor: '#FF6B35',
                backgroundColor: '#FF6B35',
                type: 'bar',
                borderRadius: 4,
                yAxisID: 'y1'
            }
        ]
    },
    options: {
        responsive: true,
        interaction: { mode: 'index', intersect: false },
        scales: {
            y:  { position: 'left',  title: { display: true, text: 'Revenue (₹)' }, beginAtZero: true },
            y1: { position: 'right', title: { display: true, text: 'Orders' }, beginAtZero: true, grid: { drawOnChartArea: false }, ticks: { precision: 0 } }
        }
    }
});
</script>
<?php endif; ?>

        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script src="<?php echo SITE_URL; ?>/assets/js/script.js"></script>
</body>
</html>
