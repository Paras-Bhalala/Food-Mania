<?php
/**
 * Menu Page
 * Food-Mania - Browse food items by category with search and filtering
 * 
 * Card-grid layout with Add to Cart buttons.
 */

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/functions.php';

$pdo = getDBConnection();

// Fetch active categories
$stmtCat = $pdo->query("SELECT * FROM categories WHERE status = 'active' ORDER BY name");
$categories = $stmtCat->fetchAll();

// Fetch all available food items
$selectedCategory = isset($_GET['category']) ? (int)$_GET['category'] : 0;

if ($selectedCategory > 0) {
    $stmtFood = $pdo->prepare("
        SELECT f.*, c.name AS category_name 
        FROM food_items f 
        JOIN categories c ON f.category_id = c.id 
        WHERE f.is_available = 1 AND c.status = 'active' AND f.category_id = :cat_id
        ORDER BY f.name
    ");
    $stmtFood->execute([':cat_id' => $selectedCategory]);
} else {
    $stmtFood = $pdo->query("
        SELECT f.*, c.name AS category_name 
        FROM food_items f 
        JOIN categories c ON f.category_id = c.id 
        WHERE f.is_available = 1 AND c.status = 'active'
        ORDER BY c.name, f.name
    ");
}
$foodItems = $stmtFood->fetchAll();

$pageTitle = 'Our Menu';
$pageDescription = 'Browse our complete menu — pizzas, burgers, Chinese, Indian, desserts, beverages and more!';

include __DIR__ . '/../includes/header.php';
include __DIR__ . '/../includes/navbar.php';
?>

<meta name="site-url" content="<?php echo SITE_URL; ?>">

<!-- Page Header -->
<div class="page-header">
    <div class="container">
        <h1><i class="fas fa-book-open me-2"></i> Our Menu</h1>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="<?php echo SITE_URL; ?>/">Home</a></li>
                <li class="breadcrumb-item active" aria-current="page">Menu</li>
            </ol>
        </nav>
    </div>
</div>

<!-- Menu Section -->
<section class="section-padding">
    <div class="container">
        <?php echo displayFlash(); ?>
        
        <!-- Filters Bar -->
        <div class="menu-filters">
            <div class="row align-items-center g-3">
                <div class="col-lg-8">
                    <div class="d-flex flex-wrap gap-2">
                        <button class="filter-btn <?php echo $selectedCategory === 0 ? 'active' : ''; ?>" 
                                data-category="all">
                            All Items
                        </button>
                        <?php foreach ($categories as $cat): ?>
                            <button class="filter-btn <?php echo $selectedCategory === (int)$cat['id'] ? 'active' : ''; ?>" 
                                    data-category="<?php echo $cat['id']; ?>">
                                <?php echo sanitize($cat['name']); ?>
                            </button>
                        <?php endforeach; ?>
                    </div>
                </div>
                <div class="col-lg-4">
                    <div class="search-box">
                        <i class="fas fa-search"></i>
                        <input type="text" id="menuSearch" placeholder="Search dishes..." 
                               class="form-control">
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Food Items Grid -->
        <div class="row g-4" id="foodGrid">
            <?php if (empty($foodItems)): ?>
                <div class="col-12 text-center py-5">
                    <i class="fas fa-utensils fa-4x text-muted mb-3 d-block"></i>
                    <h4 class="text-muted">No items found</h4>
                    <p class="text-muted">Try browsing a different category.</p>
                </div>
            <?php endif; ?>
            
            <?php foreach ($foodItems as $item): 
                $rating = getFoodRating($pdo, $item['id']);
            ?>
                <div class="col-sm-6 col-lg-3 food-card-col" 
                     data-name="<?php echo sanitize($item['name']); ?>"
                     data-category="<?php echo sanitize($item['category_name']); ?>"
                     data-category-id="<?php echo $item['category_id']; ?>">
                    <div class="food-card">
                        <div class="food-card-img">
                            <div class="food-img-placeholder" style="height:220px;display:flex;align-items:center;justify-content:center;background:linear-gradient(135deg,#f8f9fa,#e9ecef);">
                                <?php
                                $foodIcons = [
                                    'Pizza' => '🍕', 'Burgers' => '🍔', 'Chinese' => '🥡',
                                    'Desserts' => '🍰', 'Beverages' => '🥤', 'Indian' => '🍛'
                                ];
                                echo '<span style="font-size:4rem;">' . ($foodIcons[$item['category_name']] ?? '🍽️') . '</span>';
                                ?>
                            </div>
                            <span class="food-card-badge"><?php echo sanitize($item['category_name']); ?></span>
                        </div>
                        <div class="food-card-body">
                            <span class="food-card-category"><?php echo sanitize($item['category_name']); ?></span>
                            <h5 class="food-card-title">
                                <a href="<?php echo SITE_URL; ?>/user/food_detail.php?id=<?php echo $item['id']; ?>">
                                    <?php echo sanitize($item['name']); ?>
                                </a>
                            </h5>
                            <p class="food-card-desc"><?php echo sanitize($item['description']); ?></p>
                            <div class="food-card-footer">
                                <div>
                                    <span class="food-card-price"><?php echo formatPrice($item['price']); ?></span>
                                    <?php if ($rating['count'] > 0): ?>
                                        <div class="food-card-rating">
                                            <?php echo renderStars($rating['average']); ?>
                                            <small class="text-muted">(<?php echo $rating['count']; ?>)</small>
                                        </div>
                                    <?php endif; ?>
                                </div>
                                <button class="btn btn-add-cart" 
                                        onclick="addToCart(<?php echo $item['id']; ?>, 1, this)"
                                        title="Add to Cart">
                                    <i class="fas fa-cart-plus me-1"></i> Add
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
        
        <!-- No Results Message (hidden by default) -->
        <div id="noResults" class="text-center py-5" style="display:none;">
            <i class="fas fa-search fa-3x text-muted mb-3 d-block"></i>
            <h4 class="text-muted">No dishes found</h4>
            <p class="text-muted">Try a different search term or category.</p>
        </div>
    </div>
</section>

<?php include __DIR__ . '/../includes/footer.php'; ?>
