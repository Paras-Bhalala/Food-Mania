<?php
/**
 * Checkout Page
 * Food-Mania - Delivery address, payment method, and order placement
 */

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/functions.php';

requireLogin();

$pdo = getDBConnection();
$userId = $_SESSION['user_id'];

// Get user info for pre-filling
$user = getLoggedInUser($pdo);

// Get cart items
$cartItems = getCartItems($pdo, $userId);
$cartTotal = getCartTotal($pdo, $userId);

// Redirect if cart is empty
if (empty($cartItems)) {
    setFlash('warning', 'Your cart is empty. Add items before checkout.');
    redirect(SITE_URL . '/user/menu.php');
}

$tax = $cartTotal * 0.05;
$grandTotal = $cartTotal + $tax;
$errors = [];

// Handle order placement
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!validateCSRFToken($_POST['csrf_token'] ?? '')) {
        $errors[] = 'Invalid form submission.';
    } else {
        $deliveryAddress = sanitize($_POST['delivery_address'] ?? '');
        $phone           = sanitize($_POST['phone'] ?? '');
        $paymentMethod   = sanitize($_POST['payment_method'] ?? 'cod');
        
        // Validate
        if (empty($deliveryAddress)) $errors[] = 'Delivery address is required.';
        if (strlen($deliveryAddress) < 10) $errors[] = 'Please enter a complete delivery address.';
        if (empty($phone)) $errors[] = 'Phone number is required.';
        if (!preg_match('/^[0-9]{10}$/', $phone)) $errors[] = 'Phone must be 10 digits.';
        if (!in_array($paymentMethod, ['cod', 'online'])) $errors[] = 'Invalid payment method.';
        
        if (empty($errors)) {
            try {
                $pdo->beginTransaction();
                
                // Re-calculate total from DB to prevent tampering
                $cartTotal  = getCartTotal($pdo, $userId);
                $tax        = $cartTotal * 0.05;
                $grandTotal = $cartTotal + $tax;
                
                // Create order
                $stmtOrder = $pdo->prepare("
                    INSERT INTO orders (user_id, total_amount, status, payment_method, delivery_address, phone) 
                    VALUES (:uid, :total, 'pending', :payment, :address, :phone)
                ");
                $stmtOrder->execute([
                    ':uid'     => $userId,
                    ':total'   => $grandTotal,
                    ':payment' => $paymentMethod,
                    ':address' => $deliveryAddress,
                    ':phone'   => $phone,
                ]);
                $orderId = $pdo->lastInsertId();
                
                // Create order items
                $cartItems = getCartItems($pdo, $userId);
                $stmtItem = $pdo->prepare("
                    INSERT INTO order_items (order_id, food_item_id, quantity, price) 
                    VALUES (:oid, :fid, :qty, :price)
                ");
                foreach ($cartItems as $ci) {
                    $stmtItem->execute([
                        ':oid'   => $orderId,
                        ':fid'   => $ci['food_id'],
                        ':qty'   => $ci['quantity'],
                        ':price' => $ci['price'],
                    ]);
                }
                
                // Clear cart
                $stmtClear = $pdo->prepare("DELETE FROM cart WHERE user_id = :uid");
                $stmtClear->execute([':uid' => $userId]);
                
                $pdo->commit();
                
                // Redirect to success page
                redirect(SITE_URL . '/user/order_success.php?order_id=' . $orderId);
                
            } catch (Exception $e) {
                $pdo->rollBack();
                $errors[] = 'Something went wrong. Please try again.';
            }
        }
    }
}

$pageTitle = 'Checkout';
include __DIR__ . '/../includes/header.php';
include __DIR__ . '/../includes/navbar.php';
?>

<meta name="site-url" content="<?php echo SITE_URL; ?>">

<!-- Page Header -->
<div class="page-header">
    <div class="container">
        <h1><i class="fas fa-credit-card me-2"></i> Checkout</h1>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="<?php echo SITE_URL; ?>/">Home</a></li>
                <li class="breadcrumb-item"><a href="<?php echo SITE_URL; ?>/user/cart.php">Cart</a></li>
                <li class="breadcrumb-item active" aria-current="page">Checkout</li>
            </ol>
        </nav>
    </div>
</div>

<section class="section-padding">
    <div class="container">
        <?php if (!empty($errors)): ?>
            <div class="alert alert-danger">
                <ul class="mb-0">
                    <?php foreach ($errors as $err): ?>
                        <li><?php echo $err; ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>
        
        <form method="POST" action="" class="needs-validation" novalidate id="checkoutForm">
            <?php echo csrfField(); ?>
            
            <div class="row g-4">
                <!-- Delivery Details -->
                <div class="col-lg-7">
                    <div class="checkout-card mb-4">
                        <h4 class="mb-4"><i class="fas fa-map-marker-alt text-orange me-2"></i> Delivery Details</h4>
                        
                        <div class="mb-3">
                            <label for="delivery_address" class="form-label">Delivery Address <span class="text-danger">*</span></label>
                            <textarea class="form-control" id="delivery_address" name="delivery_address" rows="3" required
                                      minlength="10" placeholder="Enter your full delivery address..."><?php echo sanitize($_POST['delivery_address'] ?? $user['address'] ?? ''); ?></textarea>
                            <div class="invalid-feedback">Please enter a complete delivery address.</div>
                        </div>
                        
                        <div class="mb-3">
                            <label for="phone" class="form-label">Phone Number <span class="text-danger">*</span></label>
                            <input type="tel" class="form-control" id="phone" name="phone" required
                                   pattern="[0-9]{10}" maxlength="10"
                                   value="<?php echo sanitize($_POST['phone'] ?? $user['phone'] ?? ''); ?>"
                                   placeholder="10-digit mobile number">
                            <div class="invalid-feedback">Please enter a valid 10-digit phone number.</div>
                        </div>
                    </div>
                    
                    <!-- Payment Method -->
                    <div class="checkout-card">
                        <h4 class="mb-4"><i class="fas fa-wallet text-orange me-2"></i> Payment Method</h4>
                        
                        <div class="form-check mb-3 p-3 border rounded">
                            <input class="form-check-input" type="radio" name="payment_method" 
                                   id="paymentCOD" value="cod" checked>
                            <label class="form-check-label fw-500 ms-2" for="paymentCOD">
                                <i class="fas fa-money-bill-wave text-success me-2"></i>
                                Cash on Delivery (COD)
                                <br><small class="text-muted">Pay when your order arrives</small>
                            </label>
                        </div>
                        
                        <div class="form-check p-3 border rounded">
                            <input class="form-check-input" type="radio" name="payment_method" 
                                   id="paymentOnline" value="online">
                            <label class="form-check-label fw-500 ms-2" for="paymentOnline">
                                <i class="fas fa-credit-card text-primary me-2"></i>
                                Pay Online (Mock)
                                <br><small class="text-muted">UPI, Card, Net Banking (demo mode)</small>
                            </label>
                        </div>
                    </div>
                </div>
                
                <!-- Order Summary -->
                <div class="col-lg-5">
                    <div class="cart-summary">
                        <h4><i class="fas fa-receipt me-2"></i> Order Summary</h4>
                        
                        <?php foreach ($cartItems as $ci): ?>
                            <div class="d-flex justify-content-between align-items-center py-2 border-bottom">
                                <div>
                                    <span class="fw-500"><?php echo sanitize($ci['name']); ?></span>
                                    <small class="text-muted d-block">x<?php echo $ci['quantity']; ?></small>
                                </div>
                                <span class="fw-600"><?php echo formatPrice($ci['price'] * $ci['quantity']); ?></span>
                            </div>
                        <?php endforeach; ?>
                        
                        <div class="cart-summary-row mt-3">
                            <span>Subtotal</span>
                            <span><?php echo formatPrice($cartTotal); ?></span>
                        </div>
                        <div class="cart-summary-row">
                            <span>Delivery Fee</span>
                            <span class="text-success">Free</span>
                        </div>
                        <div class="cart-summary-row">
                            <span>Tax (5%)</span>
                            <span><?php echo formatPrice($tax); ?></span>
                        </div>
                        <div class="cart-summary-row cart-summary-total">
                            <span>Total</span>
                            <span><?php echo formatPrice($grandTotal); ?></span>
                        </div>
                        
                        <button type="submit" class="btn btn-checkout">
                            <i class="fas fa-check-circle me-2"></i> Place Order — <?php echo formatPrice($grandTotal); ?>
                        </button>
                        
                        <p class="text-center text-muted mt-3 mb-0" style="font-size:0.8rem;">
                            <i class="fas fa-lock me-1"></i> Your payment information is secure
                        </p>
                    </div>
                </div>
            </div>
        </form>
    </div>
</section>

<?php include __DIR__ . '/../includes/footer.php'; ?>
