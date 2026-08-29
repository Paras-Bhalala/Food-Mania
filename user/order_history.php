<?php
/**
 * Order History Page
 * Food-Mania - List past orders with status badges
 */

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/functions.php';

requireLogin();

$pdo = getDBConnection();
$userId = $_SESSION['user_id'];

// Fetch all orders for this user
$stmt = $pdo->prepare("
    SELECT o.*, 
           (SELECT COUNT(*) FROM order_items WHERE order_id = o.id) AS item_count
    FROM orders o 
    WHERE o.user_id = :uid 
    ORDER BY o.created_at DESC
");
$stmt->execute([':uid' => $userId]);
$orders = $stmt->fetchAll();

$pageTitle = 'My Orders';
include __DIR__ . '/../includes/header.php';
include __DIR__ . '/../includes/navbar.php';
?>

<meta name="site-url" content="<?php echo SITE_URL; ?>">

<!-- Page Header -->
<div class="page-header">
    <div class="container">
        <h1><i class="fas fa-receipt me-2"></i> My Orders</h1>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="<?php echo SITE_URL; ?>/">Home</a></li>
                <li class="breadcrumb-item active" aria-current="page">My Orders</li>
            </ol>
        </nav>
    </div>
</div>

<section class="section-padding">
    <div class="container">
        <?php echo displayFlash(); ?>
        
        <?php if (empty($orders)): ?>
            <div class="empty-cart">
                <i class="fas fa-receipt d-block"></i>
                <h3 class="text-muted">No orders yet</h3>
                <p class="text-muted mb-4">Place your first order and it will appear here.</p>
                <a href="<?php echo SITE_URL; ?>/user/menu.php" class="btn btn-primary-custom btn-lg rounded-pill">
                    <i class="fas fa-utensils me-2"></i> Browse Menu
                </a>
            </div>
        <?php else: ?>
            <div class="row g-4">
                <?php foreach ($orders as $order): ?>
                    <div class="col-md-6 col-lg-4">
                        <div class="checkout-card h-100">
                            <div class="d-flex justify-content-between align-items-start mb-3">
                                <div>
                                    <h5 class="fw-700 mb-1">Order #<?php echo str_pad($order['id'], 6, '0', STR_PAD_LEFT); ?></h5>
                                    <small class="text-muted"><?php echo formatDate($order['created_at']); ?></small>
                                </div>
                                <?php echo getStatusBadge($order['status']); ?>
                            </div>
                            
                            <hr>
                            
                            <div class="d-flex justify-content-between text-muted mb-2">
                                <span>Items:</span>
                                <span><?php echo $order['item_count']; ?> item(s)</span>
                            </div>
                            <div class="d-flex justify-content-between text-muted mb-2">
                                <span>Payment:</span>
                                <span><?php echo $order['payment_method'] === 'cod' ? 'COD' : 'Online'; ?></span>
                            </div>
                            <div class="d-flex justify-content-between fw-600 mt-2" style="font-size:1.1rem;">
                                <span>Total:</span>
                                <span class="text-orange"><?php echo formatPrice($order['total_amount']); ?></span>
                            </div>
                            
                            <div class="mt-3 d-flex gap-2">
                                <a href="<?php echo SITE_URL; ?>/user/order_track.php?id=<?php echo $order['id']; ?>" 
                                   class="btn btn-sm btn-primary-custom rounded-pill flex-fill">
                                    <i class="fas fa-eye me-1"></i> View Details
                                </a>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</section>

<?php include __DIR__ . '/../includes/footer.php'; ?>
