<?php
/**
 * Cart Page
 * Food-Mania - View, update, and manage cart items
 * 
 * Handles both AJAX requests (JSON response) and full page rendering.
 */

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/functions.php';

$pdo = getDBConnection();

// ============================================================
// HANDLE AJAX POST REQUESTS
// ============================================================
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    header('Content-Type: application/json');
    
    // Require login for cart operations
    if (!isLoggedIn()) {
        echo json_encode(['success' => false, 'message' => 'Please login to manage your cart.', 'redirect' => SITE_URL . '/auth/login.php']);
        exit;
    }
    
    $userId = $_SESSION['user_id'];
    $action = $_POST['action'] ?? '';
    
    switch ($action) {
        case 'add':
            $foodItemId = (int)($_POST['food_item_id'] ?? 0);
            $quantity   = max(1, (int)($_POST['quantity'] ?? 1));
            
            if ($foodItemId <= 0) {
                echo json_encode(['success' => false, 'message' => 'Invalid item.']);
                exit;
            }
            
            // Check if item exists and is available
            $stmtCheck = $pdo->prepare("SELECT id, name FROM food_items WHERE id = :id AND is_available = 1");
            $stmtCheck->execute([':id' => $foodItemId]);
            $foodItem = $stmtCheck->fetch();
            
            if (!$foodItem) {
                echo json_encode(['success' => false, 'message' => 'Item not available.']);
                exit;
            }
            
            // Check if already in cart
            $stmtExist = $pdo->prepare("SELECT id, quantity FROM cart WHERE user_id = :uid AND food_item_id = :fid");
            $stmtExist->execute([':uid' => $userId, ':fid' => $foodItemId]);
            $existing = $stmtExist->fetch();
            
            if ($existing) {
                // Update quantity
                $newQty = min(10, $existing['quantity'] + $quantity);
                $stmtUpdate = $pdo->prepare("UPDATE cart SET quantity = :qty WHERE id = :id");
                $stmtUpdate->execute([':qty' => $newQty, ':id' => $existing['id']]);
            } else {
                // Insert new cart item
                $stmtInsert = $pdo->prepare("INSERT INTO cart (user_id, food_item_id, quantity) VALUES (:uid, :fid, :qty)");
                $stmtInsert->execute([':uid' => $userId, ':fid' => $foodItemId, ':qty' => $quantity]);
            }
            
            $cartCount = getCartCount($pdo, $userId);
            echo json_encode([
                'success'   => true,
                'message'   => sanitize($foodItem['name']) . ' added to cart!',
                'cartCount' => $cartCount,
            ]);
            exit;
            
        case 'increase':
            $cartId = (int)($_POST['cart_id'] ?? 0);
            $stmtInc = $pdo->prepare("UPDATE cart SET quantity = LEAST(quantity + 1, 10) WHERE id = :id AND user_id = :uid");
            $stmtInc->execute([':id' => $cartId, ':uid' => $userId]);
            echo json_encode(['success' => true, 'cartCount' => getCartCount($pdo, $userId)]);
            exit;
            
        case 'decrease':
            $cartId = (int)($_POST['cart_id'] ?? 0);
            // Get current quantity
            $stmtGet = $pdo->prepare("SELECT quantity FROM cart WHERE id = :id AND user_id = :uid");
            $stmtGet->execute([':id' => $cartId, ':uid' => $userId]);
            $cartItem = $stmtGet->fetch();
            
            if ($cartItem && $cartItem['quantity'] > 1) {
                $stmtDec = $pdo->prepare("UPDATE cart SET quantity = quantity - 1 WHERE id = :id AND user_id = :uid");
                $stmtDec->execute([':id' => $cartId, ':uid' => $userId]);
            } else {
                // Remove item if quantity would be 0
                $stmtDel = $pdo->prepare("DELETE FROM cart WHERE id = :id AND user_id = :uid");
                $stmtDel->execute([':id' => $cartId, ':uid' => $userId]);
            }
            echo json_encode(['success' => true, 'cartCount' => getCartCount($pdo, $userId)]);
            exit;
            
        case 'remove':
            $cartId = (int)($_POST['cart_id'] ?? 0);
            $stmtRem = $pdo->prepare("DELETE FROM cart WHERE id = :id AND user_id = :uid");
            $stmtRem->execute([':id' => $cartId, ':uid' => $userId]);
            echo json_encode(['success' => true, 'message' => 'Item removed.', 'cartCount' => getCartCount($pdo, $userId)]);
            exit;
    }
    
    echo json_encode(['success' => false, 'message' => 'Invalid action.']);
    exit;
}

// ============================================================
// PAGE RENDERING (GET request)
// ============================================================
requireLogin();

$userId    = $_SESSION['user_id'];
$cartItems = getCartItems($pdo, $userId);
$cartTotal = getCartTotal($pdo, $userId);

$pageTitle = 'My Cart';
include __DIR__ . '/../includes/header.php';
include __DIR__ . '/../includes/navbar.php';
?>

<meta name="site-url" content="<?php echo SITE_URL; ?>">

<!-- Page Header -->
<div class="page-header">
    <div class="container">
        <h1><i class="fas fa-shopping-cart me-2"></i> My Cart</h1>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="<?php echo SITE_URL; ?>/">Home</a></li>
                <li class="breadcrumb-item active" aria-current="page">Cart</li>
            </ol>
        </nav>
    </div>
</div>

<section class="section-padding">
    <div class="container">
        <?php echo displayFlash(); ?>
        
        <?php if (empty($cartItems)): ?>
            <!-- Empty Cart -->
            <div class="empty-cart">
                <i class="fas fa-shopping-cart d-block"></i>
                <h3 class="text-muted">Your cart is empty</h3>
                <p class="text-muted mb-4">Looks like you haven't added anything yet.</p>
                <a href="<?php echo SITE_URL; ?>/user/menu.php" class="btn btn-primary-custom btn-lg rounded-pill">
                    <i class="fas fa-utensils me-2"></i> Browse Menu
                </a>
            </div>
        <?php else: ?>
            <div class="row g-4">
                <!-- Cart Items Table -->
                <div class="col-lg-8">
                    <div class="cart-table">
                        <table class="table mb-0">
                            <thead>
                                <tr>
                                    <th>Item</th>
                                    <th>Price</th>
                                    <th>Quantity</th>
                                    <th>Subtotal</th>
                                    <th></th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($cartItems as $ci): 
                                    $subtotal = $ci['price'] * $ci['quantity'];
                                ?>
                                    <tr id="cartRow-<?php echo $ci['cart_id']; ?>">
                                        <td>
                                            <div class="d-flex align-items-center">
                                                <div class="cart-item-img food-img-placeholder d-flex align-items-center justify-content-center" 
                                                     style="background:linear-gradient(135deg,#f8f9fa,#e9ecef);font-size:1.5rem;">
                                                    🍽️
                                                </div>
                                                <div class="ms-3">
                                                    <span class="cart-item-name"><?php echo sanitize($ci['name']); ?></span>
                                                    <br><small class="text-muted"><?php echo sanitize($ci['category_name']); ?></small>
                                                </div>
                                            </div>
                                        </td>
                                        <td><?php echo formatPrice($ci['price']); ?></td>
                                        <td>
                                            <div class="quantity-control">
                                                <button type="button" onclick="updateCartQuantity(<?php echo $ci['cart_id']; ?>, 'decrease')">−</button>
                                                <input type="number" value="<?php echo $ci['quantity']; ?>" readonly>
                                                <button type="button" onclick="updateCartQuantity(<?php echo $ci['cart_id']; ?>, 'increase')">+</button>
                                            </div>
                                        </td>
                                        <td class="fw-600"><?php echo formatPrice($subtotal); ?></td>
                                        <td>
                                            <button class="btn btn-action delete" title="Remove"
                                                    onclick="if(confirm('Remove this item?')) updateCartQuantity(<?php echo $ci['cart_id']; ?>, 'remove')">
                                                <i class="fas fa-trash-alt"></i>
                                            </button>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    
                    <div class="mt-3">
                        <a href="<?php echo SITE_URL; ?>/user/menu.php" class="btn btn-outline-secondary rounded-pill">
                            <i class="fas fa-arrow-left me-2"></i> Continue Shopping
                        </a>
                    </div>
                </div>
                
                <!-- Cart Summary -->
                <div class="col-lg-4">
                    <div class="cart-summary">
                        <h4><i class="fas fa-receipt me-2"></i> Order Summary</h4>
                        
                        <div class="cart-summary-row">
                            <span>Subtotal (<?php echo count($cartItems); ?> items)</span>
                            <span><?php echo formatPrice($cartTotal); ?></span>
                        </div>
                        <div class="cart-summary-row">
                            <span>Delivery Fee</span>
                            <span class="text-success">Free</span>
                        </div>
                        <div class="cart-summary-row">
                            <span>Tax (5%)</span>
                            <span><?php echo formatPrice($cartTotal * 0.05); ?></span>
                        </div>
                        <div class="cart-summary-row cart-summary-total">
                            <span>Total</span>
                            <span><?php echo formatPrice($cartTotal * 1.05); ?></span>
                        </div>
                        
                        <a href="<?php echo SITE_URL; ?>/user/checkout.php" class="btn btn-checkout">
                            <i class="fas fa-lock me-2"></i> Proceed to Checkout
                        </a>
                    </div>
                </div>
            </div>
        <?php endif; ?>
    </div>
</section>

<?php include __DIR__ . '/../includes/footer.php'; ?>
