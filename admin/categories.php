<?php
/**
 * Admin - Manage Categories
 * Food-Mania - Full CRUD with Bootstrap modals and image upload
 */

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/functions.php';

requireAdmin();

$pdo = getDBConnection();
$uploadDir = __DIR__ . '/../assets/images/categories/';

// Handle Add Category
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_category'])) {
    if (validateCSRFToken($_POST['csrf_token'] ?? '')) {
        $name   = sanitize($_POST['name'] ?? '');
        $status = sanitize($_POST['status'] ?? 'active');
        $image  = null;
        
        if (!empty($name)) {
            if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
                $image = uploadImage($_FILES['image'], $uploadDir);
            }
            
            $stmt = $pdo->prepare("INSERT INTO categories (name, image, status) VALUES (:name, :image, :status)");
            $stmt->execute([':name' => $name, ':image' => $image, ':status' => $status]);
            setFlash('success', 'Category added successfully!');
        } else {
            setFlash('error', 'Category name is required.');
        }
    }
    redirect(SITE_URL . '/admin/categories.php');
}

// Handle Edit Category
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['edit_category'])) {
    if (validateCSRFToken($_POST['csrf_token'] ?? '')) {
        $id     = (int)$_POST['id'];
        $name   = sanitize($_POST['name'] ?? '');
        $status = sanitize($_POST['status'] ?? 'active');
        
        if (!empty($name) && $id > 0) {
            if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
                $image = uploadImage($_FILES['image'], $uploadDir);
                $stmt = $pdo->prepare("UPDATE categories SET name = :name, image = :image, status = :status WHERE id = :id");
                $stmt->execute([':name' => $name, ':image' => $image, ':status' => $status, ':id' => $id]);
            } else {
                $stmt = $pdo->prepare("UPDATE categories SET name = :name, status = :status WHERE id = :id");
                $stmt->execute([':name' => $name, ':status' => $status, ':id' => $id]);
            }
            setFlash('success', 'Category updated successfully!');
        }
    }
    redirect(SITE_URL . '/admin/categories.php');
}

// Handle Delete Category
if (isset($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    if ($id > 0) {
        $stmt = $pdo->prepare("DELETE FROM categories WHERE id = :id");
        $stmt->execute([':id' => $id]);
        setFlash('success', 'Category deleted successfully!');
    }
    redirect(SITE_URL . '/admin/categories.php');
}

// Fetch all categories
$categories = $pdo->query("
    SELECT c.*, COUNT(f.id) AS item_count 
    FROM categories c 
    LEFT JOIN food_items f ON c.id = f.category_id 
    GROUP BY c.id 
    ORDER BY c.created_at DESC
")->fetchAll();

$pageTitle = 'Manage Categories';
include __DIR__ . '/includes/admin_header.php';
include __DIR__ . '/includes/admin_sidebar.php';
?>

<?php echo displayFlash(); ?>

<!-- Header Row -->
<div class="d-flex justify-content-between align-items-center mb-4">
    <h4 class="mb-0"><i class="fas fa-th-large text-orange me-2"></i> Categories</h4>
    <button class="btn btn-primary-custom rounded-pill" data-bs-toggle="modal" data-bs-target="#addModal">
        <i class="fas fa-plus me-2"></i> Add Category
    </button>
</div>

<!-- Categories Table -->
<div class="table-responsive">
    <table class="table table-custom">
        <thead>
            <tr>
                <th>#</th>
                <th>Image</th>
                <th>Name</th>
                <th>Items</th>
                <th>Status</th>
                <th>Created</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($categories as $i => $cat): ?>
                <tr>
                    <td><?php echo $i + 1; ?></td>
                    <td>
                        <div style="width:50px;height:50px;border-radius:8px;background:linear-gradient(135deg,#f8f9fa,#e9ecef);display:flex;align-items:center;justify-content:center;font-size:1.5rem;">
                            <?php
                            $icons = ['Pizza'=>'🍕','Burgers'=>'🍔','Chinese'=>'🥡','Desserts'=>'🍰','Beverages'=>'🥤','Indian'=>'🍛'];
                            echo $icons[$cat['name']] ?? '🍽️';
                            ?>
                        </div>
                    </td>
                    <td class="fw-600"><?php echo sanitize($cat['name']); ?></td>
                    <td><span class="badge bg-secondary"><?php echo $cat['item_count']; ?></span></td>
                    <td>
                        <?php if ($cat['status'] === 'active'): ?>
                            <span class="badge bg-success">Active</span>
                        <?php else: ?>
                            <span class="badge bg-danger">Inactive</span>
                        <?php endif; ?>
                    </td>
                    <td><small><?php echo formatDate($cat['created_at'], 'd M Y'); ?></small></td>
                    <td>
                        <button class="btn btn-action edit" data-bs-toggle="modal" data-bs-target="#editModal"
                                data-id="<?php echo $cat['id']; ?>"
                                data-name="<?php echo sanitize($cat['name']); ?>"
                                data-status="<?php echo $cat['status']; ?>"
                                title="Edit">
                            <i class="fas fa-edit"></i>
                        </button>
                        <a href="?delete=<?php echo $cat['id']; ?>" class="btn btn-action delete" 
                           onclick="return confirmDelete('Delete this category? All associated food items will also be deleted.')"
                           title="Delete">
                            <i class="fas fa-trash-alt"></i>
                        </a>
                    </td>
                </tr>
            <?php endforeach; ?>
            <?php if (empty($categories)): ?>
                <tr><td colspan="7" class="text-center text-muted py-3">No categories found.</td></tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<!-- Add Category Modal -->
<div class="modal fade" id="addModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST" enctype="multipart/form-data">
                <?php echo csrfField(); ?>
                <input type="hidden" name="add_category" value="1">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="fas fa-plus me-2"></i> Add Category</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Category Name <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" name="name" required placeholder="e.g., Pizza">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Image</label>
                        <input type="file" class="form-control" name="image" accept="image/*"
                               onchange="previewImage(this, 'addPreview')">
                        <img id="addPreview" class="mt-2" style="max-height:100px;display:none;border-radius:8px;">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Status</label>
                        <select class="form-select" name="status">
                            <option value="active">Active</option>
                            <option value="inactive">Inactive</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary-custom">Add Category</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Edit Category Modal -->
<div class="modal fade" id="editModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST" enctype="multipart/form-data">
                <?php echo csrfField(); ?>
                <input type="hidden" name="edit_category" value="1">
                <input type="hidden" name="id" id="editId">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="fas fa-edit me-2"></i> Edit Category</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Category Name <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" name="name" id="editName" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Image (leave empty to keep current)</label>
                        <input type="file" class="form-control" name="image" accept="image/*">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Status</label>
                        <select class="form-select" name="status" id="editStatus">
                            <option value="active">Active</option>
                            <option value="inactive">Inactive</option>
                        </select>
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
// Populate edit modal with data
document.getElementById('editModal').addEventListener('show.bs.modal', function(e) {
    const btn = e.relatedTarget;
    document.getElementById('editId').value = btn.dataset.id;
    document.getElementById('editName').value = btn.dataset.name;
    document.getElementById('editStatus').value = btn.dataset.status;
});
</script>

        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script src="<?php echo SITE_URL; ?>/assets/js/script.js"></script>
</body>
</html>
