<?php
/**
 * Order Success Page
 * Food-Mania - Order confirmation with Order ID and summary
 */

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/functions.php';

requireLogin();

$pdo = getDBConnection();
$userId = $_SESSION['user_id'];

$orderId = isset($_GET['order_id']) ? (int)$_GET['order_id'] : 0;

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
    SELECT oi.*, f.name, f.image 
    FROM order_items oi 
    JOIN food_items f ON oi.food_item_id = f.id 
    WHERE oi.order_id = :oid
");
$stmtItems->execute([':oid' => $orderId]);
$orderItems = $stmtItems->fetchAll();

$pageTitle = 'Order Confirmed';
include __DIR__ . '/../includes/header.php';
include __DIR__ . '/../includes/navbar.php';
?>

<meta name="site-url" content="<?php echo SITE_URL; ?>">

<section class="section-padding">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-lg-8">
                <div class="checkout-card">
                    <!-- Success Header -->
                    <div class="order-success">
                        <div class="success-icon">
                            <i class="fas fa-check"></i>
                        </div>
                        <h2>Order Placed Successfully!</h2>
                        <p class="text-muted mb-2">Thank you for your order. We're preparing it now!</p>
                        <div class="order-id-display">
                            Order #<?php echo str_pad($order['id'], 6, '0', STR_PAD_LEFT); ?>
                        </div>
                    </div>
                    
                    <hr>
                    
                    <!-- Order Details -->
                    <div class="p-3">
                        <h5 class="mb-3"><i class="fas fa-info-circle text-orange me-2"></i> Order Details</h5>
                        
                        <div class="row g-3 mb-4">
                            <div class="col-sm-6">
                                <strong>Order Date:</strong><br>
                                <span class="text-muted"><?php echo formatDate($order['created_at']); ?></span>
                            </div>
                            <div class="col-sm-6">
                                <strong>Payment Method:</strong><br>
                                <span class="text-muted"><?php echo $order['payment_method'] === 'cod' ? 'Cash on Delivery' : 'Online Payment'; ?></span>
                            </div>
                            <div class="col-sm-6">
                                <strong>Delivery Address:</strong><br>
                                <span class="text-muted"><?php echo sanitize($order['delivery_address']); ?></span>
                            </div>
                            <div class="col-sm-6">
                                <strong>Phone:</strong><br>
                                <span class="text-muted"><?php echo sanitize($order['phone']); ?></span>
                            </div>
                        </div>
                        
                        <h5 class="mb-3"><i class="fas fa-list text-orange me-2"></i> Items Ordered</h5>
                        
                        <div class="table-responsive">
                            <table class="table">
                                <thead class="table-light">
                                    <tr>
                                        <th>Item</th>
                                        <th>Price</th>
                                        <th>Qty</th>
                                        <th class="text-end">Subtotal</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($orderItems as $oi): ?>
                                        <tr>
                                            <td><?php echo sanitize($oi['name']); ?></td>
                                            <td><?php echo formatPrice($oi['price']); ?></td>
                                            <td><?php echo $oi['quantity']; ?></td>
                                            <td class="text-end fw-600"><?php echo formatPrice($oi['price'] * $oi['quantity']); ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                                <tfoot>
                                    <tr class="fw-700" style="font-size:1.1rem;">
                                        <td colspan="3" class="text-end">Total:</td>
                                        <td class="text-end text-orange"><?php echo formatPrice($order['total_amount']); ?></td>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>
                    </div>
                    
                    <!-- Action Buttons -->
                    <div class="text-center mt-3 mb-3">
                        <a href="<?php echo SITE_URL; ?>/user/order_track.php?id=<?php echo $orderId; ?>" class="btn btn-primary-custom rounded-pill me-2">
                            <i class="fas fa-map-marker-alt me-2"></i> Track Order
                        </a>
                        <a href="<?php echo SITE_URL; ?>/user/menu.php" class="btn btn-outline-secondary rounded-pill">
                            <i class="fas fa-utensils me-2"></i> Order More
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<?php include __DIR__ . '/../includes/footer.php'; ?>
