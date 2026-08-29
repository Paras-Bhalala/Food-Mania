<?php
/**
 * Home Page
 * Food-Mania - Single Restaurant Food Ordering System
 * 
 * Displays: hero banner, featured food items, category shortcuts, testimonials.
 */

require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/functions.php';

$pdo = getDBConnection();

// Fetch active categories with item counts
$stmtCat = $pdo->query("
    SELECT c.*, COUNT(f.id) AS item_count 
    FROM categories c 
    LEFT JOIN food_items f ON c.id = f.category_id AND f.is_available = 1
    WHERE c.status = 'active' 
    GROUP BY c.id 
    ORDER BY c.name
");
$categories = $stmtCat->fetchAll();

// Fetch featured food items (latest 8 available items)
$stmtFeatured = $pdo->query("
    SELECT f.*, c.name AS category_name 
    FROM food_items f 
    JOIN categories c ON f.category_id = c.id 
    WHERE f.is_available = 1 AND c.status = 'active'
    ORDER BY f.created_at DESC 
    LIMIT 8
");
$featuredItems = $stmtFeatured->fetchAll();

$pageTitle = 'Welcome';
$pageDescription = 'Food-Mania — Your favorite restaurant for pizza, burgers, Chinese, Indian, desserts & beverages. Order now for fast delivery!';

include __DIR__ . '/includes/header.php';
include __DIR__ . '/includes/navbar.php';
?>

<!-- Meta tag for JS SITE_URL -->
<meta name="site-url" content="<?php echo SITE_URL; ?>">

<!-- ============================================================
     HERO SECTION
     ============================================================ -->
<section class="hero-section">
    <div class="container">
        <div class="row align-items-center">
            <div class="col-lg-6 hero-content">
                <h1 class="hero-title fade-in">
                    Delicious Food<br>
                    <span class="highlight">Delivered Fast</span><br>
                    To Your Door
                </h1>
                <p class="hero-subtitle fade-in">
                    From wood-fired pizzas to authentic Indian curries, crispy burgers to sweet desserts — 
                    experience restaurant-quality food at your doorstep.
                </p>
                <div class="hero-buttons fade-in">
                    <a href="<?php echo SITE_URL; ?>/user/menu.php" class="btn btn-primary-custom me-3">
                        <i class="fas fa-utensils me-2"></i> Explore Menu
                    </a>
                    <a href="#categories" class="btn btn-outline-light-custom">
                        <i class="fas fa-th-large me-2"></i> Browse Categories
                    </a>
                </div>
                
                <div class="hero-stats fade-in">
                    <div class="hero-stat">
                        <span class="hero-stat-number">50+</span>
                        <span class="hero-stat-label">Menu Items</span>
                    </div>
                    <div class="hero-stat">
                        <span class="hero-stat-number">1000+</span>
                        <span class="hero-stat-label">Happy Customers</span>
                    </div>
                    <div class="hero-stat">
                        <span class="hero-stat-number">30</span>
                        <span class="hero-stat-label">Min Delivery</span>
                    </div>
                </div>
            </div>
            <div class="col-lg-6 text-center hero-image-wrapper d-none d-lg-block">
                <div style="width:450px;height:450px;margin:0 auto;border-radius:50%;background:linear-gradient(135deg,rgba(255,107,53,0.2),rgba(255,193,7,0.2));display:flex;align-items:center;justify-content:center;">
                    <i class="fas fa-pizza-slice" style="font-size:12rem;color:rgba(255,193,7,0.5);"></i>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- ============================================================
     CATEGORIES SECTION
     ============================================================ -->
<section class="section-padding" id="categories">
    <div class="container">
        <div class="section-header">
            <h2 class="section-title">Explore Our <span class="highlight">Categories</span></h2>
            <p class="section-subtitle">Browse through our wide range of delicious food categories</p>
        </div>
        
        <div class="row g-4">
            <?php foreach ($categories as $cat): ?>
                <div class="col-6 col-md-4 col-lg-2">
                    <a href="<?php echo SITE_URL; ?>/user/menu.php?category=<?php echo $cat['id']; ?>" style="text-decoration:none;">
                        <div class="category-card">
                            <div class="category-icon food-img-placeholder" 
                                 style="width:80px;height:80px;border-radius:50%;margin:0 auto 1rem;display:flex;align-items:center;justify-content:center;font-size:2rem;">
                                <?php
                                // Category icons
                                $icons = [
                                    'Pizza' => '🍕', 'Burgers' => '🍔', 'Chinese' => '🥡',
                                    'Desserts' => '🍰', 'Beverages' => '🥤', 'Indian' => '🍛'
                                ];
                                echo $icons[$cat['name']] ?? '🍽️';
                                ?>
                            </div>
                            <h5><?php echo sanitize($cat['name']); ?></h5>
                            <span class="item-count"><?php echo $cat['item_count']; ?> items</span>
                        </div>
                    </a>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<!-- ============================================================
     FEATURED FOOD ITEMS
     ============================================================ -->
<section class="section-padding" style="background: var(--white);">
    <div class="container">
        <div class="section-header">
            <h2 class="section-title">Featured <span class="highlight">Dishes</span></h2>
            <p class="section-subtitle">Our most loved dishes, freshly prepared just for you</p>
        </div>
        
        <div class="row g-4">
            <?php foreach ($featuredItems as $item): 
                $rating = getFoodRating($pdo, $item['id']);
            ?>
                <div class="col-sm-6 col-lg-3">
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
                                    <i class="fas fa-cart-plus"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
        
        <div class="text-center mt-5">
            <a href="<?php echo SITE_URL; ?>/user/menu.php" class="btn btn-primary-custom btn-lg">
                <i class="fas fa-book-open me-2"></i> View Full Menu
            </a>
        </div>
    </div>
</section>

<!-- ============================================================
     WHY CHOOSE US
     ============================================================ -->
<section class="section-padding">
    <div class="container">
        <div class="section-header">
            <h2 class="section-title">Why Choose <span class="highlight">Food-Mania</span>?</h2>
            <p class="section-subtitle">We make your dining experience unforgettable</p>
        </div>
        
        <div class="row g-4">
            <div class="col-md-4">
                <div class="about-card">
                    <i class="fas fa-fire"></i>
                    <h5 class="mt-3">Fresh & Hot</h5>
                    <p class="text-muted">Every dish is freshly prepared using the finest ingredients and delivered hot to your doorstep.</p>
                </div>
            </div>
            <div class="col-md-4">
                <div class="about-card">
                    <i class="fas fa-truck"></i>
                    <h5 class="mt-3">Fast Delivery</h5>
                    <p class="text-muted">Our dedicated delivery team ensures your food arrives within 30 minutes of placing your order.</p>
                </div>
            </div>
            <div class="col-md-4">
                <div class="about-card">
                    <i class="fas fa-heart"></i>
                    <h5 class="mt-3">Made with Love</h5>
                    <p class="text-muted">Our passionate chefs craft each dish with love, care, and years of culinary expertise.</p>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- ============================================================
     CTA SECTION
     ============================================================ -->
<section style="background:linear-gradient(135deg, var(--primary), var(--secondary));padding:4rem 0;text-align:center;color:var(--white);">
    <div class="container">
        <h2 style="font-weight:800;font-size:2rem;margin-bottom:1rem;">Ready to Order?</h2>
        <p style="font-size:1.1rem;opacity:0.9;margin-bottom:2rem;max-width:500px;margin-left:auto;margin-right:auto;">
            Browse our menu, pick your favorites, and enjoy a delicious meal delivered right to your door.
        </p>
        <a href="<?php echo SITE_URL; ?>/user/menu.php" class="btn btn-light btn-lg rounded-pill px-5" 
           style="font-weight:600;color:var(--primary);">
            <i class="fas fa-utensils me-2"></i> Order Now
        </a>
    </div>
</section>

<?php include __DIR__ . '/includes/footer.php'; ?>
