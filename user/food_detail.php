<?php
/**
 * Food Detail Page
 * Food-Mania - Full item details with quantity selector and reviews
 */

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/functions.php';

$pdo = getDBConnection();

// Get food item ID
$foodId = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($foodId <= 0) {
    setFlash('error', 'Invalid food item.');
    redirect(SITE_URL . '/user/menu.php');
}

// Fetch food item with category
$stmt = $pdo->prepare("
    SELECT f.*, c.name AS category_name 
    FROM food_items f 
    JOIN categories c ON f.category_id = c.id 
    WHERE f.id = :id
");
$stmt->execute([':id' => $foodId]);
$item = $stmt->fetch();

if (!$item) {
    setFlash('error', 'Food item not found.');
    redirect(SITE_URL . '/user/menu.php');
}

// Get rating
$rating = getFoodRating($pdo, $foodId);

// Get reviews
$stmtReviews = $pdo->prepare("
    SELECT r.*, u.name AS user_name 
    FROM reviews r 
    JOIN users u ON r.user_id = u.id 
    WHERE r.food_item_id = :food_id 
    ORDER BY r.created_at DESC
");
$stmtReviews->execute([':food_id' => $foodId]);
$reviews = $stmtReviews->fetchAll();

// Get related items from same category
$stmtRelated = $pdo->prepare("
    SELECT f.*, c.name AS category_name 
    FROM food_items f 
    JOIN categories c ON f.category_id = c.id 
    WHERE f.category_id = :cat_id AND f.id != :food_id AND f.is_available = 1
    LIMIT 4
");
$stmtRelated->execute([':cat_id' => $item['category_id'], ':food_id' => $foodId]);
$relatedItems = $stmtRelated->fetchAll();

$pageTitle = $item['name'];
$pageDescription = substr($item['description'], 0, 155);

include __DIR__ . '/../includes/header.php';
include __DIR__ . '/../includes/navbar.php';
?>

<meta name="site-url" content="<?php echo SITE_URL; ?>">

<!-- Page Header -->
<div class="page-header">
    <div class="container">
        <h1><?php echo sanitize($item['name']); ?></h1>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="<?php echo SITE_URL; ?>/">Home</a></li>
                <li class="breadcrumb-item"><a href="<?php echo SITE_URL; ?>/user/menu.php">Menu</a></li>
                <li class="breadcrumb-item active" aria-current="page"><?php echo sanitize($item['name']); ?></li>
            </ol>
        </nav>
    </div>
</div>

<!-- Food Detail -->
<section class="section-padding">
    <div class="container">
        <?php echo displayFlash(); ?>
        
        <div class="row g-5">
            <!-- Image -->
            <div class="col-lg-6">
                <div class="food-detail-img">
                    <div class="food-img-placeholder" style="height:400px;display:flex;align-items:center;justify-content:center;background:linear-gradient(135deg,#f8f9fa,#e9ecef);border-radius:var(--radius-lg);">
                        <?php
                        $foodIcons = [
                            'Pizza' => '🍕', 'Burgers' => '🍔', 'Chinese' => '🥡',
                            'Desserts' => '🍰', 'Beverages' => '🥤', 'Indian' => '🍛'
                        ];
                        echo '<span style="font-size:8rem;">' . ($foodIcons[$item['category_name']] ?? '🍽️') . '</span>';
                        ?>
                    </div>
                </div>
            </div>
            
            <!-- Info -->
            <div class="col-lg-6">
                <div class="food-detail-info">
                    <span class="badge bg-orange mb-2"><?php echo sanitize($item['category_name']); ?></span>
                    
                    <h2 class="fw-700 mb-2"><?php echo sanitize($item['name']); ?></h2>
                    
                    <!-- Rating -->
                    <?php if ($rating['count'] > 0): ?>
                        <div class="mb-3">
                            <?php echo renderStars($rating['average']); ?>
                            <span class="text-muted ms-2">(<?php echo $rating['average']; ?> / 5 — <?php echo $rating['count']; ?> reviews)</span>
                        </div>
                    <?php endif; ?>
                    
                    <p class="food-detail-price mb-3"><?php echo formatPrice($item['price']); ?></p>
                    
                    <p class="text-muted mb-4" style="line-height:1.8;">
                        <?php echo sanitize($item['description']); ?>
                    </p>
                    
                    <?php if ($item['is_available']): ?>
                        <!-- Quantity + Add to Cart -->
                        <div class="d-flex align-items-center gap-3 mb-4">
                            <div class="quantity-control">
                                <button type="button" onclick="decreaseQuantity('qty')">−</button>
                                <input type="number" id="qty" value="1" min="1" max="10" readonly>
                                <button type="button" onclick="increaseQuantity('qty')">+</button>
                            </div>
                            <button class="btn btn-add-cart btn-lg" 
                                    onclick="addToCart(<?php echo $item['id']; ?>, document.getElementById('qty').value, this)">
                                <i class="fas fa-cart-plus me-2"></i> Add to Cart
                            </button>
                        </div>
                    <?php else: ?>
                        <div class="alert alert-warning">
                            <i class="fas fa-exclamation-triangle me-2"></i>
                            This item is currently unavailable.
                        </div>
                    <?php endif; ?>
                    
                    <!-- Quick Info -->
                    <div class="row g-3 mt-2">
                        <div class="col-auto">
                            <div class="d-flex align-items-center text-muted">
                                <i class="fas fa-truck me-2 text-orange"></i>
                                <small>Free delivery above ₹500</small>
                            </div>
                        </div>
                        <div class="col-auto">
                            <div class="d-flex align-items-center text-muted">
                                <i class="fas fa-clock me-2 text-orange"></i>
                                <small>30 min delivery</small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Reviews Section -->
        <div class="row mt-5">
            <div class="col-lg-8">
                <div class="checkout-card">
                    <h4 class="mb-4"><i class="fas fa-star text-warning me-2"></i> Customer Reviews (<?php echo count($reviews); ?>)</h4>
                    
                    <?php if (empty($reviews)): ?>
                        <p class="text-muted">No reviews yet. Be the first to review!</p>
                    <?php else: ?>
                        <?php foreach ($reviews as $review): ?>
                            <div class="border-bottom pb-3 mb-3">
                                <div class="d-flex justify-content-between align-items-center mb-2">
                                    <div>
                                        <strong><?php echo sanitize($review['user_name']); ?></strong>
                                        <div><?php echo renderStars($review['rating']); ?></div>
                                    </div>
                                    <small class="text-muted"><?php echo formatDate($review['created_at']); ?></small>
                                </div>
                                <?php if ($review['comment']): ?>
                                    <p class="text-muted mb-0"><?php echo sanitize($review['comment']); ?></p>
                                <?php endif; ?>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        
        <!-- Related Items -->
        <?php if (!empty($relatedItems)): ?>
            <div class="mt-5">
                <h3 class="section-title mb-4">You May Also <span class="highlight">Like</span></h3>
                <div class="row g-4">
                    <?php foreach ($relatedItems as $rel): ?>
                        <div class="col-sm-6 col-lg-3">
                            <div class="food-card">
                                <div class="food-card-img">
                                    <div class="food-img-placeholder" style="height:180px;display:flex;align-items:center;justify-content:center;background:linear-gradient(135deg,#f8f9fa,#e9ecef);">
                                        <?php echo '<span style="font-size:3rem;">' . ($foodIcons[$rel['category_name']] ?? '🍽️') . '</span>'; ?>
                                    </div>
                                </div>
                                <div class="food-card-body">
                                    <h5 class="food-card-title">
                                        <a href="<?php echo SITE_URL; ?>/user/food_detail.php?id=<?php echo $rel['id']; ?>">
                                            <?php echo sanitize($rel['name']); ?>
                                        </a>
                                    </h5>
                                    <div class="food-card-footer">
                                        <span class="food-card-price"><?php echo formatPrice($rel['price']); ?></span>
                                        <button class="btn btn-add-cart btn-sm" onclick="addToCart(<?php echo $rel['id']; ?>, 1, this)">
                                            <i class="fas fa-cart-plus"></i>
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endif; ?>
    </div>
</section>

<?php include __DIR__ . '/../includes/footer.php'; ?>
