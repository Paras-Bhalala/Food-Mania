<?php
/**
 * Admin - Customer Reviews & Ratings Management
 * Food-Mania - View, filter, and moderate customer reviews
 */

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/functions.php';

requireAdmin();

$pdo = getDBConnection();

// Delete review
if (isset($_GET['delete'])) {
    $reviewId = (int)$_GET['delete'];
    if ($reviewId > 0) {
        $stmt = $pdo->prepare("DELETE FROM reviews WHERE id = :id");
        $stmt->execute([':id' => $reviewId]);
        setFlash('success', 'Review deleted successfully.');
    }
    redirect(SITE_URL . '/admin/reviews.php');
}

// Filter by rating or food item
$filterRating = isset($_GET['rating']) && in_array((int)$_GET['rating'], [1, 2, 3, 4, 5]) ? (int)$_GET['rating'] : 0;
$filterFood   = (int)($_GET['food_id'] ?? 0);

$where = [];
$params = [];

if ($filterRating > 0) {
    $where[] = "r.rating = :rating";
    $params[':rating'] = $filterRating;
}
if ($filterFood > 0) {
    $where[] = "r.food_item_id = :food_id";
    $params[':food_id'] = $filterFood;
}

$whereSQL = !empty($where) ? 'WHERE ' . implode(' AND ', $where) : '';

$stmtReviews = $pdo->prepare("
    SELECT r.*, u.name AS user_name, u.email AS user_email, f.name AS food_name, f.image AS food_image, c.name AS category_name
    FROM reviews r
    JOIN users u ON r.user_id = u.id
    JOIN food_items f ON r.food_item_id = f.id
    JOIN categories c ON f.category_id = c.id
    $whereSQL
    ORDER BY r.created_at DESC
");
$stmtReviews->execute($params);
$reviews = $stmtReviews->fetchAll();

// All food items for filter dropdown
$allFoodItems = $pdo->query("SELECT id, name FROM food_items ORDER BY name")->fetchAll();

// Rating stats
$ratingStats = $pdo->query("
    SELECT 
        COUNT(*) AS total_count,
        COALESCE(AVG(rating), 0) AS avg_rating,
        SUM(CASE WHEN rating = 5 THEN 1 ELSE 0 END) AS count_5,
        SUM(CASE WHEN rating = 4 THEN 1 ELSE 0 END) AS count_4,
        SUM(CASE WHEN rating = 3 THEN 1 ELSE 0 END) AS count_3,
        SUM(CASE WHEN rating = 2 THEN 1 ELSE 0 END) AS count_2,
        SUM(CASE WHEN rating = 1 THEN 1 ELSE 0 END) AS count_1
    FROM reviews
")->fetch();

$pageTitle = 'Customer Reviews';
include __DIR__ . '/includes/admin_header.php';
include __DIR__ . '/includes/admin_sidebar.php';
?>

<?php echo displayFlash(); ?>

<!-- Page Title -->
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h4 class="fw-bold mb-1"><i class="fas fa-star text-warning me-2"></i> Customer Reviews & Ratings</h4>
        <p class="text-muted mb-0">Monitor customer feedback, ratings, and moderate reviews.</p>
    </div>
</div>

<!-- Rating Overview Card -->
<div class="row g-3 mb-4">
    <div class="col-md-4">
        <div class="stat-card orders h-100 text-center d-flex flex-column justify-content-center">
            <div class="display-5 fw-bold text-warning mb-1">
                <?php echo number_format($ratingStats['avg_rating'], 1); ?>
            </div>
            <div class="mb-2 text-warning">
                <?php echo renderStars($ratingStats['avg_rating']); ?>
            </div>
            <div class="text-muted small">Based on <?php echo $ratingStats['total_count']; ?> customer reviews</div>
        </div>
    </div>
    <div class="col-md-8">
        <div class="checkout-card h-100">
            <h6 class="fw-bold mb-3">Rating Breakdown</h6>
            <?php for ($star = 5; $star >= 1; $star--): ?>
                <?php 
                $count = (int)$ratingStats["count_$star"];
                $pct = $ratingStats['total_count'] > 0 ? round(($count / $ratingStats['total_count']) * 100) : 0;
                ?>
                <div class="d-flex align-items-center gap-2 mb-2">
                    <span class="small fw-600 text-nowrap" style="width:40px;"><?php echo $star; ?> <i class="fas fa-star text-warning"></i></span>
                    <div class="progress flex-grow-1" style="height: 8px;">
                        <div class="progress-bar bg-warning" role="progressbar" style="width: <?php echo $pct; ?>%;"></div>
                    </div>
                    <span class="small text-muted text-end" style="width:50px;"><?php echo $count; ?> (<?php echo $pct; ?>%)</span>
                </div>
            <?php endfor; ?>
        </div>
    </div>
</div>

<!-- Filter Bar -->
<div class="checkout-card mb-4">
    <form method="GET" class="row g-3 align-items-end">
        <div class="col-md-4">
            <label class="form-label small fw-600">Filter by Food Item</label>
            <select class="form-select form-select-sm" name="food_id">
                <option value="">All Menu Items</option>
                <?php foreach ($allFoodItems as $f): ?>
                    <option value="<?php echo $f['id']; ?>" <?php echo $filterFood === (int)$f['id'] ? 'selected' : ''; ?>>
                        <?php echo sanitize($f['name']); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-md-3">
            <label class="form-label small fw-600">Filter by Rating</label>
            <select class="form-select form-select-sm" name="rating">
                <option value="">All Star Ratings</option>
                <option value="5" <?php echo $filterRating === 5 ? 'selected' : ''; ?>>5 Stars ★★★★★</option>
                <option value="4" <?php echo $filterRating === 4 ? 'selected' : ''; ?>>4 Stars ★★★★☆</option>
                <option value="3" <?php echo $filterRating === 3 ? 'selected' : ''; ?>>3 Stars ★★★☆☆</option>
                <option value="2" <?php echo $filterRating === 2 ? 'selected' : ''; ?>>2 Stars ★★☆☆☆</option>
                <option value="1" <?php echo $filterRating === 1 ? 'selected' : ''; ?>>1 Star ★☆☆☆☆</option>
            </select>
        </div>
        <div class="col-md-3">
            <button type="submit" class="btn btn-primary-custom btn-sm rounded-pill w-100">
                <i class="fas fa-filter me-1"></i> Filter Reviews
            </button>
        </div>
        <div class="col-md-2">
            <a href="<?php echo SITE_URL; ?>/admin/reviews.php" class="btn btn-outline-secondary btn-sm rounded-pill w-100">Reset</a>
        </div>
    </form>
</div>

<!-- Reviews Table -->
<div class="checkout-card">
    <div class="table-responsive">
        <table class="table table-custom align-middle mb-0">
            <thead>
                <tr>
                    <th>Customer</th>
                    <th>Dish / Item</th>
                    <th>Rating</th>
                    <th>Review Comment</th>
                    <th>Date</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($reviews as $rev): ?>
                    <tr>
                        <td>
                            <div class="fw-600"><?php echo sanitize($rev['user_name']); ?></div>
                            <small class="text-muted"><?php echo sanitize($rev['user_email']); ?></small>
                        </td>
                        <td>
                            <span class="badge bg-light text-dark fw-600"><?php echo sanitize($rev['food_name']); ?></span>
                            <small class="text-muted d-block"><?php echo sanitize($rev['category_name']); ?></small>
                        </td>
                        <td>
                            <div class="text-warning small"><?php echo renderStars($rev['rating']); ?></div>
                            <span class="small fw-bold">(<?php echo $rev['rating']; ?>/5)</span>
                        </td>
                        <td>
                            <p class="mb-0 small text-secondary" style="max-width: 320px;">
                                "<?php echo sanitize($rev['comment'] ?? 'No text review'); ?>"
                            </p>
                        </td>
                        <td><small class="text-muted"><?php echo formatDate($rev['created_at'], 'd M Y, h:i A'); ?></small></td>
                        <td>
                            <a href="?delete=<?php echo $rev['id']; ?>" class="btn btn-action delete"
                               onclick="return confirm('Are you sure you want to delete this customer review?')" title="Delete Review">
                                <i class="fas fa-trash-alt"></i>
                            </a>
                        </td>
                    </tr>
                <?php endforeach; ?>
                <?php if (empty($reviews)): ?>
                    <tr>
                        <td colspan="6" class="text-center py-4 text-muted">
                            No reviews found matching the filters.
                        </td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script src="<?php echo SITE_URL; ?>/assets/js/script.js"></script>
</body>
</html>
