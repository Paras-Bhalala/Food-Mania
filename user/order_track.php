<?php
/**
 * Order Tracking Page
 * Food-Mania - Visual status timeline for a specific order
 */

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/functions.php';

requireLogin();

$pdo = getDBConnection();
$userId = $_SESSION['user_id'];

$orderId = isset($_GET['id']) ? (int)$_GET['id'] : 0;

// Fetch order
$stmt = $pdo->prepare("SELECT * FROM orders WHERE id = :id AND user_id = :uid");
$stmt->execute([':id' => $orderId, ':uid' => $userId]);
$order = $stmt->fetch();

if (!$order) {
    setFlash('error', 'Order not found.');
    redirect(SITE_URL . '/user/order_history.php');
}

// Fetch order items
$stmtItems = $pdo->prepare("
    SELECT oi.*, f.name 
    FROM order_items oi 
    JOIN food_items f ON oi.food_item_id = f.id 
    WHERE oi.order_id = :oid
");
$stmtItems->execute([':oid' => $orderId]);
$orderItems = $stmtItems->fetchAll();

// Define status steps
$statusSteps = [
    'pending'          => ['icon' => 'fa-clock',       'label' => 'Order Placed',       'desc' => 'Your order has been placed and is awaiting confirmation.'],
    'confirmed'        => ['icon' => 'fa-check-circle','label' => 'Confirmed',           'desc' => 'Your order has been confirmed by the restaurant.'],
    'preparing'        => ['icon' => 'fa-fire',        'label' => 'Preparing',            'desc' => 'Our chefs are preparing your delicious food.'],
    'out_for_delivery' => ['icon' => 'fa-motorcycle',  'label' => 'Out for Delivery',     'desc' => 'Your food is on its way to you!'],
    'delivered'        => ['icon' => 'fa-check-double','label' => 'Delivered',             'desc' => 'Your order has been delivered. Enjoy!'],
];

// Determine current step index
$statusKeys = array_keys($statusSteps);
$currentIndex = array_search($order['status'], $statusKeys);
if ($currentIndex === false) $currentIndex = -1; // cancelled

$pageTitle = 'Track Order #' . str_pad($order['id'], 6, '0', STR_PAD_LEFT);
include __DIR__ . '/../includes/header.php';
include __DIR__ . '/../includes/navbar.php';
?>

<meta name="site-url" content="<?php echo SITE_URL; ?>">

<!-- Page Header -->
<div class="page-header">
    <div class="container">
        <h1><i class="fas fa-map-marker-alt me-2"></i> Track Order</h1>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="<?php echo SITE_URL; ?>/">Home</a></li>
                <li class="breadcrumb-item"><a href="<?php echo SITE_URL; ?>/user/order_history.php">My Orders</a></li>
                <li class="breadcrumb-item active" aria-current="page">Track Order</li>
            </ol>
        </nav>
    </div>
</div>

<section class="section-padding">
    <div class="container">
        <div class="row g-4">
            <!-- Timeline -->
            <div class="col-lg-7">
                <div class="checkout-card">
                    <div class="d-flex justify-content-between align-items-center mb-4">
                        <h4 class="mb-0">
                            Order #<?php echo str_pad($order['id'], 6, '0', STR_PAD_LEFT); ?>
                        </h4>
                        <?php echo getStatusBadge($order['status']); ?>
                    </div>
                    
                    <?php if ($order['status'] === 'cancelled'): ?>
                        <div class="alert alert-danger">
                            <i class="fas fa-times-circle me-2"></i>
                            This order has been cancelled.
                        </div>
                    <?php else: ?>
                        <div class="order-timeline">
                            <?php $i = 0; foreach ($statusSteps as $key => $step): 
                                $isCompleted = $i < $currentIndex;
                                $isActive    = $i === $currentIndex;
                                $isFuture    = $i > $currentIndex;
                            ?>
                                <div class="timeline-step">
                                    <div class="timeline-icon <?php echo $isCompleted ? 'completed' : ($isActive ? 'active' : ''); ?>">
                                        <i class="fas <?php echo $step['icon']; ?>"></i>
                                    </div>
                                    <?php if ($i < count($statusSteps) - 1): ?>
                                        <div class="timeline-line <?php echo $isCompleted ? 'active' : ''; ?>"></div>
                                    <?php endif; ?>
                                    <div class="timeline-content">
                                        <h6 class="<?php echo $isFuture ? 'text-muted' : ''; ?>">
                                            <?php echo $step['label']; ?>
                                            <?php if ($isActive): ?>
                                                <span class="badge bg-primary ms-2" style="font-size:0.65rem;">Current</span>
                                            <?php endif; ?>
                                        </h6>
                                        <p class="<?php echo $isFuture ? 'text-muted' : ''; ?>">
                                            <?php echo $step['desc']; ?>
                                        </p>
                                    </div>
                                </div>
                            <?php $i++; endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
            
            <!-- Order Summary -->
            <div class="col-lg-5">
                <div class="cart-summary">
                    <h4><i class="fas fa-receipt me-2"></i> Order Summary</h4>
                    
                    <?php foreach ($orderItems as $oi): ?>
                        <div class="d-flex justify-content-between py-2 border-bottom">
                            <div>
                                <span class="fw-500"><?php echo sanitize($oi['name']); ?></span>
                                <small class="text-muted d-block">x<?php echo $oi['quantity']; ?></small>
                            </div>
                            <span class="fw-600"><?php echo formatPrice($oi['price'] * $oi['quantity']); ?></span>
                        </div>
                    <?php endforeach; ?>
                    
                    <div class="cart-summary-row cart-summary-total mt-3">
                        <span>Total</span>
                        <span><?php echo formatPrice($order['total_amount']); ?></span>
                    </div>
                    
                    <hr>
                    
                    <div class="mb-2">
                        <small class="text-muted"><i class="fas fa-calendar me-1"></i> <?php echo formatDate($order['created_at']); ?></small>
                    </div>
                    <div class="mb-2">
                        <small class="text-muted"><i class="fas fa-wallet me-1"></i> <?php echo $order['payment_method'] === 'cod' ? 'Cash on Delivery' : 'Online Payment'; ?></small>
                    </div>
                    <div class="mb-2">
                        <small class="text-muted"><i class="fas fa-map-marker-alt me-1"></i> <?php echo sanitize($order['delivery_address']); ?></small>
                    </div>
                    <div>
                        <small class="text-muted"><i class="fas fa-phone me-1"></i> <?php echo sanitize($order['phone']); ?></small>
                    </div>
                </div>
                
                <div class="mt-3">
                    <a href="<?php echo SITE_URL; ?>/user/order_history.php" class="btn btn-outline-secondary rounded-pill w-100">
                        <i class="fas fa-arrow-left me-2"></i> Back to Orders
                    </a>
                </div>
            </div>
        </div>
    </div>
</section>

<?php include __DIR__ . '/../includes/footer.php'; ?>
