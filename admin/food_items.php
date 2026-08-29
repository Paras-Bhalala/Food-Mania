<?php
/**
 * Admin - Manage Food Items
 * Food-Mania - Full CRUD with image upload, available/unavailable toggle
 */

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/functions.php';

requireAdmin();

$pdo = getDBConnection();
$uploadDir = __DIR__ . '/../assets/images/food/';

// Handle Add Food Item
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_food'])) {
    if (validateCSRFToken($_POST['csrf_token'] ?? '')) {
        $name        = sanitize($_POST['name'] ?? '');
        $categoryId  = (int)$_POST['category_id'];
        $price       = (float)$_POST['price'];
        $description = sanitize($_POST['description'] ?? '');
        $isAvailable = isset($_POST['is_available']) ? 1 : 0;
        $image       = null;
        
        if (!empty($name) && $categoryId > 0 && $price > 0) {
            if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
                $image = uploadImage($_FILES['image'], $uploadDir);
            }
            $stmt = $pdo->prepare("INSERT INTO food_items (category_id, name, description, price, image, is_available) VALUES (:cat, :name, :desc, :price, :img, :avail)");
            $stmt->execute([':cat' => $categoryId, ':name' => $name, ':desc' => $description, ':price' => $price, ':img' => $image, ':avail' => $isAvailable]);
            setFlash('success', 'Food item added successfully!');
        } else {
            setFlash('error', 'Please fill in all required fields.');
        }
    }
    redirect(SITE_URL . '/admin/food_items.php');
}

// Handle Edit Food Item
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['edit_food'])) {
    if (validateCSRFToken($_POST['csrf_token'] ?? '')) {
        $id          = (int)$_POST['id'];
        $name        = sanitize($_POST['name'] ?? '');
        $categoryId  = (int)$_POST['category_id'];
        $price       = (float)$_POST['price'];
        $description = sanitize($_POST['description'] ?? '');
        $isAvailable = isset($_POST['is_available']) ? 1 : 0;
        
        if ($id > 0 && !empty($name)) {
            if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
                $image = uploadImage($_FILES['image'], $uploadDir);
                $stmt = $pdo->prepare("UPDATE food_items SET category_id=:cat, name=:name, description=:desc, price=:price, image=:img, is_available=:avail WHERE id=:id");
                $stmt->execute([':cat'=>$categoryId, ':name'=>$name, ':desc'=>$description, ':price'=>$price, ':img'=>$image, ':avail'=>$isAvailable, ':id'=>$id]);
            } else {
                $stmt = $pdo->prepare("UPDATE food_items SET category_id=:cat, name=:name, description=:desc, price=:price, is_available=:avail WHERE id=:id");
                $stmt->execute([':cat'=>$categoryId, ':name'=>$name, ':desc'=>$description, ':price'=>$price, ':avail'=>$isAvailable, ':id'=>$id]);
            }
            setFlash('success', 'Food item updated successfully!');
        }
    }
    redirect(SITE_URL . '/admin/food_items.php');
}

// Handle Toggle Availability (AJAX)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['toggle_availability'])) {
    header('Content-Type: application/json');
    $id = (int)$_POST['food_id'];
    $status = (int)$_POST['status'];
    $stmt = $pdo->prepare("UPDATE food_items SET is_available = :status WHERE id = :id");
    $stmt->execute([':status' => $status, ':id' => $id]);
    echo json_encode(['success' => true]);
    exit;
}

// Handle Delete
if (isset($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    if ($id > 0) {
        $stmt = $pdo->prepare("DELETE FROM food_items WHERE id = :id");
        $stmt->execute([':id' => $id]);
        setFlash('success', 'Food item deleted successfully!');
    }
    redirect(SITE_URL . '/admin/food_items.php');
}

// Fetch food items with category names
$foodItems = $pdo->query("
    SELECT f.*, c.name AS category_name 
    FROM food_items f 
    JOIN categories c ON f.category_id = c.id 
    ORDER BY f.created_at DESC
")->fetchAll();

$categories = $pdo->query("SELECT * FROM categories WHERE status = 'active' ORDER BY name")->fetchAll();

$pageTitle = 'Manage Food Items';
include __DIR__ . '/includes/admin_header.php';
include __DIR__ . '/includes/admin_sidebar.php';
?>

<?php echo displayFlash(); ?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h4 class="mb-0"><i class="fas fa-hamburger text-orange me-2"></i> Food Items</h4>
    <button class="btn btn-primary-custom rounded-pill" data-bs-toggle="modal" data-bs-target="#addFoodModal">
        <i class="fas fa-plus me-2"></i> Add Food Item
    </button>
</div>

<div class="table-responsive">
    <table class="table table-custom">
        <thead>
            <tr>
                <th>#</th>
                <th>Name</th>
                <th>Category</th>
                <th>Price</th>
                <th>Available</th>
                <th>Created</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($foodItems as $i => $fi): ?>
                <tr>
                    <td><?php echo $i + 1; ?></td>
                    <td class="fw-600"><?php echo sanitize($fi['name']); ?></td>
                    <td><span class="badge bg-secondary"><?php echo sanitize($fi['category_name']); ?></span></td>
                    <td class="fw-600 text-orange"><?php echo formatPrice($fi['price']); ?></td>
                    <td>
                        <label class="toggle-switch">
                            <input type="checkbox" <?php echo $fi['is_available'] ? 'checked' : ''; ?>
                                   onchange="toggleAvailability(<?php echo $fi['id']; ?>, this.checked ? 1 : 0)">
                            <span class="toggle-slider"></span>
                        </label>
                    </td>
                    <td><small><?php echo formatDate($fi['created_at'], 'd M Y'); ?></small></td>
                    <td>
                        <button class="btn btn-action edit" data-bs-toggle="modal" data-bs-target="#editFoodModal"
                                data-id="<?php echo $fi['id']; ?>"
                                data-name="<?php echo sanitize($fi['name']); ?>"
                                data-category="<?php echo $fi['category_id']; ?>"
                                data-price="<?php echo $fi['price']; ?>"
                                data-description="<?php echo sanitize($fi['description']); ?>"
                                data-available="<?php echo $fi['is_available']; ?>"
                                title="Edit">
                            <i class="fas fa-edit"></i>
                        </button>
                        <a href="?delete=<?php echo $fi['id']; ?>" class="btn btn-action delete"
                           onclick="return confirmDelete('Delete this food item?')" title="Delete">
                            <i class="fas fa-trash-alt"></i>
                        </a>
                    </td>
                </tr>
            <?php endforeach; ?>
            <?php if (empty($foodItems)): ?>
                <tr><td colspan="7" class="text-center text-muted py-3">No food items found.</td></tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<!-- Add Food Modal -->
<div class="modal fade" id="addFoodModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <form method="POST" enctype="multipart/form-data">
                <?php echo csrfField(); ?>
                <input type="hidden" name="add_food" value="1">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="fas fa-plus me-2"></i> Add Food Item</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">Name <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" name="name" required>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Category <span class="text-danger">*</span></label>
                            <select class="form-select" name="category_id" required>
                                <option value="">Select</option>
                                <?php foreach ($categories as $cat): ?>
                                    <option value="<?php echo $cat['id']; ?>"><?php echo sanitize($cat['name']); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Price (₹) <span class="text-danger">*</span></label>
                            <input type="number" class="form-control" name="price" step="0.01" min="1" required>
                        </div>
                        <div class="col-12">
                            <label class="form-label">Description</label>
                            <textarea class="form-control" name="description" rows="3"></textarea>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Image</label>
                            <input type="file" class="form-control" name="image" accept="image/*">
                        </div>
                        <div class="col-md-6 d-flex align-items-end">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" name="is_available" id="addAvail" checked>
                                <label class="form-check-label" for="addAvail">Available for ordering</label>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary-custom">Add Food Item</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Edit Food Modal -->
<div class="modal fade" id="editFoodModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <form method="POST" enctype="multipart/form-data">
                <?php echo csrfField(); ?>
                <input type="hidden" name="edit_food" value="1">
                <input type="hidden" name="id" id="editFoodId">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="fas fa-edit me-2"></i> Edit Food Item</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">Name <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" name="name" id="editFoodName" required>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Category</label>
                            <select class="form-select" name="category_id" id="editFoodCat" required>
                                <?php foreach ($categories as $cat): ?>
                                    <option value="<?php echo $cat['id']; ?>"><?php echo sanitize($cat['name']); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Price (₹)</label>
                            <input type="number" class="form-control" name="price" id="editFoodPrice" step="0.01" min="1" required>
                        </div>
                        <div class="col-12">
                            <label class="form-label">Description</label>
                            <textarea class="form-control" name="description" id="editFoodDesc" rows="3"></textarea>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Image (leave empty to keep current)</label>
                            <input type="file" class="form-control" name="image" accept="image/*">
                        </div>
                        <div class="col-md-6 d-flex align-items-end">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" name="is_available" id="editFoodAvail">
                                <label class="form-check-label" for="editFoodAvail">Available for ordering</label>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary-custom">Save Changes</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
// Populate edit modal
document.getElementById('editFoodModal').addEventListener('show.bs.modal', function(e) {
    const btn = e.relatedTarget;
    document.getElementById('editFoodId').value = btn.dataset.id;
    document.getElementById('editFoodName').value = btn.dataset.name;
    document.getElementById('editFoodCat').value = btn.dataset.category;
    document.getElementById('editFoodPrice').value = btn.dataset.price;
    document.getElementById('editFoodDesc').value = btn.dataset.description;
    document.getElementById('editFoodAvail').checked = btn.dataset.available === '1';
});

// Toggle availability via AJAX
function toggleAvailability(foodId, status) {
    const formData = new FormData();
    formData.append('toggle_availability', '1');
    formData.append('food_id', foodId);
    formData.append('status', status);
    
    fetch('<?php echo SITE_URL; ?>/admin/food_items.php', {
        method: 'POST',
        body: formData
    })
    .then(r => r.json())
    .then(data => {
        // Silent success
    })
    .catch(err => console.error(err));
}
</script>

        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script src="<?php echo SITE_URL; ?>/assets/js/script.js"></script>
</body>
</html>
