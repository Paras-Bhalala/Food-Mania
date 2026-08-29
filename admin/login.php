<?php
/**
 * Admin Login
 * Food-Mania - Separate admin authentication using admins table
 */

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/functions.php';

// Redirect if already logged in as admin
if (isAdmin()) {
    redirect(SITE_URL . '/admin/dashboard.php');
}

$pdo = getDBConnection();
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!validateCSRFToken($_POST['csrf_token'] ?? '')) {
        $errors[] = 'Invalid form submission.';
    } else {
        $email    = sanitize($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';
        
        if (empty($email))    $errors[] = 'Email is required.';
        if (empty($password)) $errors[] = 'Password is required.';
        
        if (empty($errors)) {
            $stmt = $pdo->prepare("SELECT * FROM admins WHERE email = :email");
            $stmt->execute([':email' => $email]);
            $admin = $stmt->fetch();
            
            if ($admin) {
                if (password_verify($password, $admin['password'])) {
                    $_SESSION['admin_id']   = $admin['id'];
                    $_SESSION['admin_name'] = $admin['name'];
                    redirect(SITE_URL . '/admin/dashboard.php');
                } else {
                    $errors[] = 'Debug: Admin record found. Input password: ' . htmlspecialchars($password) . ' | Stored password hash: ' . htmlspecialchars($admin['password']) . ' | password_verify returned false.';
                }
            } else {
                $errors[] = 'Debug: No admin record found with email: ' . htmlspecialchars($email);
            }
        }
    }
}

$pageTitle = 'Admin Login';
include __DIR__ . '/../includes/header.php';
?>

<!-- Admin Login Page -->
<section class="auth-section">
    <div class="container">
        <div class="auth-card fade-in">
            <div class="brand-header">
                <i class="fas fa-user-shield" style="color:var(--secondary);"></i>
                <h2>Admin Login</h2>
                <p class="text-muted">Access the Food-Mania admin dashboard</p>
            </div>
            
            <?php if (!empty($errors)): ?>
                <div class="alert alert-danger">
                    <ul class="mb-0">
                        <?php foreach ($errors as $err): ?>
                            <li><?php echo $err; ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>
            
            <form method="POST" action="">
                <?php echo csrfField(); ?>
                
                <div class="mb-3">
                    <label for="email" class="form-label">Email Address</label>
                    <div class="input-group">
                        <span class="input-group-text"><i class="fas fa-envelope"></i></span>
                        <input type="email" class="form-control" id="email" name="email" required
                               value="<?php echo sanitize($_POST['email'] ?? ''); ?>"
                               placeholder="admin@foodmania.com">
                    </div>
                </div>
                
                <div class="mb-4">
                    <label for="password" class="form-label">Password</label>
                    <div class="input-group">
                        <span class="input-group-text"><i class="fas fa-lock"></i></span>
                        <input type="password" class="form-control" id="password" name="password" required
                               placeholder="Enter admin password">
                    </div>
                </div>
                
                <button type="submit" class="btn btn-auth" style="background:linear-gradient(135deg,var(--secondary),var(--secondary-dark));">
                    <i class="fas fa-sign-in-alt me-2"></i> Login to Dashboard
                </button>
            </form>
            
            <!-- Demo Credentials -->
            <div class="mt-4 p-3" style="background:var(--gray-100);border-radius:var(--radius-sm);">
                <p class="mb-1 fw-600 text-muted" style="font-size:0.8rem;">
                    <i class="fas fa-info-circle me-1"></i> Demo Admin Credentials:
                </p>
                <p class="mb-0" style="font-size:0.8rem;color:var(--gray-500);">
                    Email: <code>admin@foodmania.com</code> | Password: <code>admin123</code>
                </p>
            </div>
            
            <div class="text-center mt-3">
                <a href="<?php echo SITE_URL; ?>/" class="text-muted"><i class="fas fa-arrow-left me-1"></i> Back to Website</a>
            </div>
        </div>
    </div>
</section>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
