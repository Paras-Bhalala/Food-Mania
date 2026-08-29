<?php
/**
 * Admin Dashboard
 * Food-Mania - Comprehensive Admin Dashboard with Real-time Metrics, Charts, & Quick Actions
 */

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/functions.php';

requireAdmin();

$pdo = getDBConnection();

// Period filter (default 7 days)
$daysFilter = isset($_GET['range']) && in_array((int)$_GET['range'], [7, 14, 30]) ? (int)$_GET['range'] : 7;

// --- Key Metrics ---
// Total & Today Orders
$totalOrders = (int)$pdo->query("SELECT COUNT(*) FROM orders")->fetchColumn();
$todayOrders = (int)$pdo->query("SELECT COUNT(*) FROM orders WHERE DATE(created_at) = CURDATE()")->fetchColumn();

// Revenue
$totalRevenue = (float)$pdo->query("SELECT COALESCE(SUM(total_amount), 0) FROM orders WHERE status != 'cancelled'")->fetchColumn();
$todayRevenue = (float)$pdo->query("SELECT COALESCE(SUM(total_amount), 0) FROM orders WHERE status != 'cancelled' AND DATE(created_at) = CURDATE()")->fetchColumn();

// Average Order Value
$avgOrderValue = $totalOrders > 0 ? ($totalRevenue / max(1, (int)$pdo->query("SELECT COUNT(*) FROM orders WHERE status != 'cancelled'")->fetchColumn())) : 0;

// Users
$totalUsers = (int)$pdo->query("SELECT COUNT(*) FROM users")->fetchColumn();
$activeUsers = (int)$pdo->query("SELECT COUNT(*) FROM users WHERE status = 'active'")->fetchColumn();

// Pending & Processing Orders
$pendingOrders = (int)$pdo->query("SELECT COUNT(*) FROM orders WHERE status = 'pending'")->fetchColumn();
$preparingOrders = (int)$pdo->query("SELECT COUNT(*) FROM orders WHERE status IN ('confirmed', 'preparing', 'out_for_delivery')")->fetchColumn();

// Food items and Categories count
$totalFoodItems = (int)$pdo->query("SELECT COUNT(*) FROM food_items")->fetchColumn();
$unavailableItems = (int)$pdo->query("SELECT COUNT(*) FROM food_items WHERE is_available = 0")->fetchColumn();
$totalCategories = (int)$pdo->query("SELECT COUNT(*) FROM categories")->fetchColumn();

// Contact Messages & Reviews
$unreadMessages = (int)$pdo->query("SELECT COUNT(*) FROM contact_messages WHERE is_read = 0")->fetchColumn();
$totalReviews = (int)$pdo->query("SELECT COUNT(*) FROM reviews")->fetchColumn();
$avgRating = (float)$pdo->query("SELECT COALESCE(AVG(rating), 0) FROM reviews")->fetchColumn();

// --- Chart 1: Daily Orders & Revenue for selected range ---
$chartData = [];
for ($i = $daysFilter - 1; $i >= 0; $i--) {
    $date = date('Y-m-d', strtotime("-$i days"));
    $label = date('d M', strtotime("-$i days"));
    
    $stmtChart = $pdo->prepare("
        SELECT COUNT(*) AS order_count, COALESCE(SUM(total_amount), 0) AS revenue
        FROM orders WHERE DATE(created_at) = :date AND status != 'cancelled'
    ");
    $stmtChart->execute([':date' => $date]);
    $row = $stmtChart->fetch();
    
    $chartData[] = [
        'label'   => $label,
        'orders'  => (int)$row['order_count'],
        'revenue' => (float)$row['revenue'],
    ];
}

// --- Chart 2: Order Status Distribution ---
$statusCounts = [];
$statuses = ['pending', 'confirmed', 'preparing', 'out_for_delivery', 'delivered', 'cancelled'];
foreach ($statuses as $s) {
    $sc = $pdo->prepare("SELECT COUNT(*) FROM orders WHERE status = :s");
    $sc->execute([':s' => $s]);
    $statusCounts[$s] = (int)$sc->fetchColumn();
}

// --- Chart 3: Category Revenue Share ---
$categoryShareStmt = $pdo->query("
    SELECT c.name, COALESCE(SUM(oi.quantity * oi.price), 0) AS revenue
    FROM categories c
    LEFT JOIN food_items f ON c.id = f.category_id
    LEFT JOIN order_items oi ON f.id = oi.food_item_id
    LEFT JOIN orders o ON oi.order_id = o.id AND o.status != 'cancelled'
    GROUP BY c.id
    ORDER BY revenue DESC
");
$categoryShare = $categoryShareStmt->fetchAll();

// --- Recent Orders (Latest 6) ---
$stmtRecent = $pdo->query("
    SELECT o.*, u.name AS user_name, u.email AS user_email,
           (SELECT COUNT(*) FROM order_items WHERE order_id = o.id) AS item_count
    FROM orders o 
    JOIN users u ON o.user_id = u.id 
    ORDER BY o.created_at DESC 
    LIMIT 6
");
$recentOrders = $stmtRecent->fetchAll();

// --- Top 5 Best Selling Items ---
$topItemsStmt = $pdo->query("
    SELECT f.name, f.price, c.name AS category_name, SUM(oi.quantity) AS total_sold, SUM(oi.quantity * oi.price) AS total_revenue
    FROM order_items oi
    JOIN food_items f ON oi.food_item_id = f.id
    JOIN categories c ON f.category_id = c.id
    JOIN orders o ON oi.order_id = o.id
    WHERE o.status != 'cancelled'
    GROUP BY f.id
    ORDER BY total_sold DESC
    LIMIT 5
");
$topSellingItems = $topItemsStmt->fetchAll();

// --- Recent Customer Reviews ---
$recentReviewsStmt = $pdo->query("
    SELECT r.*, u.name AS user_name, f.name AS food_name
    FROM reviews r
    JOIN users u ON r.user_id = u.id
    JOIN food_items f ON r.food_item_id = f.id
    ORDER BY r.created_at DESC
    LIMIT 4
");
$recentReviews = $recentReviewsStmt->fetchAll();

$pageTitle = 'Admin Dashboard';
include __DIR__ . '/includes/admin_header.php';
include __DIR__ . '/includes/admin_sidebar.php';
?>

<!-- Welcome Banner -->
<div class="card border-0 shadow-sm mb-4" style="background: linear-gradient(135deg, #1A1A2E 0%, #16213E 100%); border-radius: var(--radius-md); color: #fff;">
    <div class="card-body p-4 d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3">
        <div>
            <span class="badge bg-warning text-dark px-3 py-2 rounded-pill mb-2 fw-600">
                <i class="fas fa-shield-alt me-1"></i> Admin Portal
            </span>
            <h3 class="fw-bold mb-1">Welcome back, <?php echo sanitize($_SESSION['admin_name'] ?? 'Administrator'); ?>! 👋</h3>
            <p class="text-white-50 mb-0">Here is the real-time business performance overview and analytics for Food-Mania.</p>
        </div>
        <div class="d-flex flex-wrap gap-2">
            <a href="<?php echo SITE_URL; ?>/admin/food_items.php" class="btn btn-outline-light rounded-pill btn-sm px-3">
                <i class="fas fa-plus me-1"></i> Add Food
            </a>
            <a href="<?php echo SITE_URL; ?>/admin/reports.php" class="btn btn-warning rounded-pill btn-sm px-3 fw-600 text-dark">
                <i class="fas fa-file-invoice-dollar me-1"></i> View Full Reports
            </a>
        </div>
    </div>
</div>

<!-- Primary Stats Cards -->
<div class="row g-3 mb-4">
    <!-- Total Revenue -->
    <div class="col-sm-6 col-xl-3">
        <div class="stat-card revenue">
            <div class="d-flex justify-content-between align-items-start">
                <div>
                    <div class="stat-label">Total Revenue</div>
                    <div class="stat-value text-success mt-1"><?php echo formatPrice($totalRevenue); ?></div>
                </div>
                <div class="stat-icon"><i class="fas fa-rupee-sign"></i></div>
            </div>
            <div class="mt-3 pt-2 border-top d-flex justify-content-between align-items-center text-muted" style="font-size:0.8rem;">
                <span>Today's Sales:</span>
                <strong class="text-success"><?php echo formatPrice($todayRevenue); ?></strong>
            </div>
        </div>
    </div>

    <!-- Total Orders -->
    <div class="col-sm-6 col-xl-3">
        <div class="stat-card orders">
            <div class="d-flex justify-content-between align-items-start">
                <div>
                    <div class="stat-label">Total Orders</div>
                    <div class="stat-value mt-1"><?php echo number_format($totalOrders); ?></div>
                </div>
                <div class="stat-icon"><i class="fas fa-shopping-bag"></i></div>
            </div>
            <div class="mt-3 pt-2 border-top d-flex justify-content-between align-items-center text-muted" style="font-size:0.8rem;">
                <span>Today: <strong><?php echo $todayOrders; ?> orders</strong></span>
                <span class="badge bg-warning text-dark"><?php echo $pendingOrders; ?> pending</span>
            </div>
        </div>
    </div>

    <!-- Average Order Value -->
    <div class="col-sm-6 col-xl-3">
        <div class="stat-card users">
            <div class="d-flex justify-content-between align-items-start">
                <div>
                    <div class="stat-label">Avg Order Value</div>
                    <div class="stat-value mt-1"><?php echo formatPrice($avgOrderValue); ?></div>
                </div>
                <div class="stat-icon" style="background:rgba(0,123,255,0.12);color:#007bff;"><i class="fas fa-calculator"></i></div>
            </div>
            <div class="mt-3 pt-2 border-top d-flex justify-content-between align-items-center text-muted" style="font-size:0.8rem;">
                <span>Active Menu Items:</span>
                <strong><?php echo ($totalFoodItems - $unavailableItems); ?> / <?php echo $totalFoodItems; ?></strong>
            </div>
        </div>
    </div>

    <!-- Registered Customers -->
    <div class="col-sm-6 col-xl-3">
        <div class="stat-card pending">
            <div class="d-flex justify-content-between align-items-start">
                <div>
                    <div class="stat-label">Total Customers</div>
                    <div class="stat-value mt-1"><?php echo number_format($totalUsers); ?></div>
                </div>
                <div class="stat-icon"><i class="fas fa-users"></i></div>
            </div>
            <div class="mt-3 pt-2 border-top d-flex justify-content-between align-items-center text-muted" style="font-size:0.8rem;">
                <span>Active Accounts:</span>
                <strong class="text-success"><?php echo $activeUsers; ?></strong>
            </div>
        </div>
    </div>
</div>

<!-- Secondary Alert Bar for Operations -->
<div class="row g-3 mb-4">
    <div class="col-md-3">
        <div class="p-3 bg-white rounded-3 shadow-sm d-flex align-items-center justify-content-between border-start border-4 border-warning">
            <div>
                <small class="text-muted d-block">Pending Orders</small>
                <h5 class="fw-bold mb-0 text-warning"><?php echo $pendingOrders; ?></h5>
            </div>
            <a href="<?php echo SITE_URL; ?>/admin/orders.php?status=pending" class="btn btn-sm btn-outline-warning rounded-pill">Manage</a>
        </div>
    </div>
    <div class="col-md-3">
        <div class="p-3 bg-white rounded-3 shadow-sm d-flex align-items-center justify-content-between border-start border-4 border-primary">
            <div>
                <small class="text-muted d-block">In Kitchen / Transit</small>
                <h5 class="fw-bold mb-0 text-primary"><?php echo $preparingOrders; ?></h5>
            </div>
            <a href="<?php echo SITE_URL; ?>/admin/orders.php" class="btn btn-sm btn-outline-primary rounded-pill">Track</a>
        </div>
    </div>
    <div class="col-md-3">
        <div class="p-3 bg-white rounded-3 shadow-sm d-flex align-items-center justify-content-between border-start border-4 border-danger">
            <div>
                <small class="text-muted d-block">Out of Stock Items</small>
                <h5 class="fw-bold mb-0 text-danger"><?php echo $unavailableItems; ?></h5>
            </div>
            <a href="<?php echo SITE_URL; ?>/admin/food_items.php" class="btn btn-sm btn-outline-danger rounded-pill">Check</a>
        </div>
    </div>
    <div class="col-md-3">
        <div class="p-3 bg-white rounded-3 shadow-sm d-flex align-items-center justify-content-between border-start border-4 border-info">
            <div>
                <small class="text-muted d-block">Unread Inquiries</small>
                <h5 class="fw-bold mb-0 text-info"><?php echo $unreadMessages; ?></h5>
            </div>
            <a href="<?php echo SITE_URL; ?>/admin/messages.php" class="btn btn-sm btn-outline-info rounded-pill">View</a>
        </div>
    </div>
</div>

<!-- Charts Row -->
<div class="row g-4 mb-4">
    <!-- Sales & Order Trend -->
    <div class="col-lg-8">
        <div class="checkout-card h-100">
            <div class="d-flex flex-column flex-sm-row justify-content-between align-items-sm-center mb-3 gap-2">
                <h5 class="mb-0 fw-bold">
                    <i class="fas fa-chart-line text-orange me-2"></i> Orders & Revenue Trends
                </h5>
                <div class="btn-group btn-group-sm" role="group">
                    <a href="?range=7" class="btn <?php echo $daysFilter == 7 ? 'btn-primary-custom' : 'btn-outline-secondary'; ?>">7 Days</a>
                    <a href="?range=14" class="btn <?php echo $daysFilter == 14 ? 'btn-primary-custom' : 'btn-outline-secondary'; ?>">14 Days</a>
                    <a href="?range=30" class="btn <?php echo $daysFilter == 30 ? 'btn-primary-custom' : 'btn-outline-secondary'; ?>">30 Days</a>
                </div>
            </div>
            <canvas id="dashboardChart" height="110"></canvas>
        </div>
    </div>

    <!-- Order Status Breakdown -->
    <div class="col-lg-4">
        <div class="checkout-card h-100">
            <h5 class="mb-3 fw-bold">
                <i class="fas fa-chart-pie text-orange me-2"></i> Order Status Distribution
            </h5>
            <div style="position:relative; max-height:240px;" class="d-flex justify-content-center">
                <canvas id="statusChart"></canvas>
            </div>
            <div class="row g-2 text-center mt-3 pt-2 border-top">
                <div class="col-4">
                    <small class="text-muted d-block">Delivered</small>
                    <span class="fw-bold text-success"><?php echo $statusCounts['delivered']; ?></span>
                </div>
                <div class="col-4">
                    <small class="text-muted d-block">Pending</small>
                    <span class="fw-bold text-warning"><?php echo $statusCounts['pending']; ?></span>
                </div>
                <div class="col-4">
                    <small class="text-muted d-block">Cancelled</small>
                    <span class="fw-bold text-danger"><?php echo $statusCounts['cancelled']; ?></span>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Widgets Row: Recent Orders & Top Sellers -->
<div class="row g-4 mb-4">
    <!-- Recent Orders Table -->
    <div class="col-lg-8">
        <div class="checkout-card h-100">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h5 class="mb-0 fw-bold">
                    <i class="fas fa-clock text-orange me-2"></i> Recent Orders
                </h5>
                <a href="<?php echo SITE_URL; ?>/admin/orders.php" class="btn btn-sm btn-outline-primary rounded-pill px-3">
                    View All Orders <i class="fas fa-arrow-right ms-1"></i>
                </a>
            </div>
            
            <div class="table-responsive">
                <table class="table table-custom align-middle mb-0">
                    <thead>
                        <tr>
                            <th>Order ID</th>
                            <th>Customer</th>
                            <th>Items</th>
                            <th>Total</th>
                            <th>Payment</th>
                            <th>Status</th>
                            <th>Date</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($recentOrders as $ro): ?>
                            <tr>
                                <td class="fw-bold text-primary">#<?php echo str_pad($ro['id'], 6, '0', STR_PAD_LEFT); ?></td>
                                <td>
                                    <div class="fw-600"><?php echo sanitize($ro['user_name']); ?></div>
                                    <small class="text-muted"><?php echo sanitize($ro['user_email']); ?></small>
                                </td>
                                <td><span class="badge bg-light text-dark"><?php echo $ro['item_count']; ?> items</span></td>
                                <td class="fw-bold text-orange"><?php echo formatPrice($ro['total_amount']); ?></td>
                                <td><span class="badge bg-secondary"><?php echo strtoupper($ro['payment_method']); ?></span></td>
                                <td><?php echo getStatusBadge($ro['status']); ?></td>
                                <td><small class="text-muted"><?php echo formatDate($ro['created_at'], 'd M, h:i A'); ?></small></td>
                                <td>
                                    <a href="<?php echo SITE_URL; ?>/admin/orders.php?status=<?php echo $ro['status']; ?>" class="btn btn-sm btn-light border rounded-pill" title="Manage Order">
                                        <i class="fas fa-external-link-alt text-muted"></i>
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        <?php if (empty($recentOrders)): ?>
                            <tr>
                                <td colspan="8" class="text-center py-4 text-muted">
                                    <i class="fas fa-inbox fa-2x mb-2 d-block text-gray-300"></i>
                                    No orders recorded yet.
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Top Selling Dishes -->
    <div class="col-lg-4">
        <div class="checkout-card h-100">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h5 class="mb-0 fw-bold">
                    <i class="fas fa-trophy text-warning me-2"></i> Top Selling Items
                </h5>
                <a href="<?php echo SITE_URL; ?>/admin/reports.php" class="text-muted small">More</a>
            </div>
            
            <?php if (!empty($topSellingItems)): ?>
                <div class="list-group list-group-flush">
                    <?php foreach ($topSellingItems as $index => $item): ?>
                        <div class="list-group-item px-0 py-2 d-flex justify-content-between align-items-center">
                            <div class="d-flex align-items-center">
                                <span class="badge rounded-circle me-3 <?php echo $index === 0 ? 'bg-warning text-dark' : ($index === 1 ? 'bg-secondary' : 'bg-light text-dark'); ?>" style="width:26px; height:26px; display:inline-flex; align-items:center; justify-content:center;">
                                    <?php echo $index + 1; ?>
                                </span>
                                <div>
                                    <div class="fw-600 mb-0"><?php echo sanitize($item['name']); ?></div>
                                    <small class="text-muted"><?php echo sanitize($item['category_name']); ?> • <?php echo formatPrice($item['price']); ?></small>
                                </div>
                            </div>
                            <div class="text-end">
                                <span class="badge bg-orange-soft text-orange fw-600"><?php echo $item['total_sold']; ?> sold</span>
                                <div class="small text-muted"><?php echo formatPrice($item['total_revenue']); ?></div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <p class="text-muted text-center py-4">No sales data available yet.</p>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Lower Section: Category Share & Recent Reviews -->
<div class="row g-4 mb-4">
    <!-- Category Revenue Share -->
    <div class="col-lg-6">
        <div class="checkout-card h-100">
            <h5 class="mb-3 fw-bold">
                <i class="fas fa-th-large text-orange me-2"></i> Revenue by Category
            </h5>
            <div class="row align-items-center">
                <div class="col-md-6 text-center">
                    <canvas id="categoryChart" style="max-height:200px;"></canvas>
                </div>
                <div class="col-md-6">
                    <ul class="list-unstyled mb-0">
                        <?php foreach ($categoryShare as $cat): ?>
                            <li class="d-flex justify-content-between align-items-center py-1 border-bottom">
                                <span class="fw-500"><?php echo sanitize($cat['name']); ?></span>
                                <span class="fw-600 text-dark"><?php echo formatPrice($cat['revenue']); ?></span>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            </div>
        </div>
    </div>

    <!-- Customer Reviews & Rating Summary -->
    <div class="col-lg-6">
        <div class="checkout-card h-100">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <div>
                    <h5 class="mb-0 fw-bold">
                        <i class="fas fa-star text-warning me-2"></i> Latest Reviews
                    </h5>
                    <small class="text-muted">Avg Rating: <strong><?php echo number_format($avgRating, 1); ?> / 5.0</strong> (<?php echo $totalReviews; ?> reviews)</small>
                </div>
                <a href="<?php echo SITE_URL; ?>/admin/reviews.php" class="btn btn-sm btn-outline-warning rounded-pill px-3">
                    View All
                </a>
            </div>

            <?php if (!empty($recentReviews)): ?>
                <div class="d-flex flex-column gap-2">
                    <?php foreach ($recentReviews as $rev): ?>
                        <div class="p-2 rounded bg-light border-start border-3 border-warning">
                            <div class="d-flex justify-content-between align-items-center">
                                <strong class="small"><?php echo sanitize($rev['user_name']); ?> <span class="text-muted fw-normal">on</span> <?php echo sanitize($rev['food_name']); ?></strong>
                                <span class="text-warning small"><?php echo renderStars($rev['rating']); ?></span>
                            </div>
                            <p class="mb-0 small text-secondary fst-italic">"<?php echo sanitize($rev['comment'] ?? 'No comment written.'); ?>"</p>
                            <small class="text-muted" style="font-size:0.75rem;"><?php echo formatDate($rev['created_at'], 'd M Y'); ?></small>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <p class="text-muted text-center py-4">No reviews recorded yet.</p>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Chart.js Scripts -->
<script>
// 1. Orders & Revenue Trend Bar/Line
const chartData = <?php echo json_encode($chartData); ?>;
const ctx = document.getElementById('dashboardChart').getContext('2d');
new Chart(ctx, {
    type: 'bar',
    data: {
        labels: chartData.map(d => d.label),
        datasets: [
            {
                label: 'Orders',
                data: chartData.map(d => d.orders),
                backgroundColor: 'rgba(255, 107, 53, 0.85)',
                borderRadius: 6,
                yAxisID: 'y'
            },
            {
                label: 'Revenue (₹)',
                data: chartData.map(d => d.revenue),
                type: 'line',
                borderColor: '#28A745',
                backgroundColor: 'rgba(40, 167, 69, 0.15)',
                borderWidth: 3,
                tension: 0.35,
                fill: true,
                yAxisID: 'y1'
            }
        ]
    },
    options: {
        responsive: true,
        interaction: { mode: 'index', intersect: false },
        plugins: {
            legend: { position: 'top', labels: { usePointStyle: true } }
        },
        scales: {
            y:  { position: 'left',  title: { display: true, text: 'Orders Count' }, beginAtZero: true, ticks: { precision: 0 } },
            y1: { position: 'right', title: { display: true, text: 'Revenue (₹)' }, beginAtZero: true, grid: { drawOnChartArea: false } }
        }
    }
});

// 2. Order Status Doughnut
const statusCtx = document.getElementById('statusChart').getContext('2d');
new Chart(statusCtx, {
    type: 'doughnut',
    data: {
        labels: ['Pending', 'Confirmed', 'Preparing', 'Out for Delivery', 'Delivered', 'Cancelled'],
        datasets: [{
            data: [
                <?php echo (int)$statusCounts['pending']; ?>,
                <?php echo (int)$statusCounts['confirmed']; ?>,
                <?php echo (int)$statusCounts['preparing']; ?>,
                <?php echo (int)$statusCounts['out_for_delivery']; ?>,
                <?php echo (int)$statusCounts['delivered']; ?>,
                <?php echo (int)$statusCounts['cancelled']; ?>
            ],
            backgroundColor: ['#FFC107', '#17A2B8', '#007BFF', '#FF6B35', '#28A745', '#DC3545'],
            borderWidth: 2,
            borderColor: '#ffffff'
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
            legend: { position: 'bottom', labels: { padding: 12, boxWidth: 10, usePointStyle: true } }
        },
        cutout: '68%'
    }
});

// 3. Category Revenue Chart
<?php
$catLabels = array_map(fn($c) => $c['name'], $categoryShare);
$catRevenues = array_map(fn($c) => (float)$c['revenue'], $categoryShare);
?>
const catCtx = document.getElementById('categoryChart').getContext('2d');
new Chart(catCtx, {
    type: 'pie',
    data: {
        labels: <?php echo json_encode($catLabels); ?>,
        datasets: [{
            data: <?php echo json_encode($catRevenues); ?>,
            backgroundColor: ['#FF6B35', '#FFC107', '#28A745', '#17A2B8', '#6F42C1', '#FD7E14', '#20C997'],
            borderWidth: 2,
            borderColor: '#ffffff'
        }]
    },
    options: {
        responsive: true,
        plugins: {
            legend: { display: false }
        }
    }
});
</script>

        </div><!-- /admin-main -->
    </div><!-- /admin-content -->
</div><!-- /admin-wrapper -->

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script src="<?php echo SITE_URL; ?>/assets/js/script.js"></script>
</body>
</html>
