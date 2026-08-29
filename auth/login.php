<?php
/**
 * User Login
 * Food-Mania - Single Restaurant Food Ordering System
 * 
 * Login form with password_verify(), session creation, blocked user check.
 */

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/functions.php';

// Redirect if already logged in
if (isLoggedIn()) {
    redirect(SITE_URL . '/');
}

$pdo = getDBConnection();
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // CSRF validation
    if (!validateCSRFToken($_POST['csrf_token'] ?? '')) {
        $errors[] = 'Invalid form submission. Please try again.';
    } else {
        $email    = sanitize($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';
        
        // Validate
        if (empty($email))    $errors[] = 'Email is required.';
        if (empty($password)) $errors[] = 'Password is required.';
        
        if (empty($errors)) {
            // Find user by email
            $stmt = $pdo->prepare("SELECT * FROM users WHERE email = :email");
            $stmt->execute([':email' => $email]);
            $user = $stmt->fetch();
            
            if ($user && password_verify($password, $user['password'])) {
                // Check if user is blocked
                if ($user['status'] === 'blocked') {
                    $errors[] = 'Your account has been blocked. Please contact support.';
                } else {
                    // Set session variables
                    $_SESSION['user_id']   = $user['id'];
                    $_SESSION['user_name'] = $user['name'];
                    $_SESSION['user_email'] = $user['email'];
                    
                    setFlash('success', 'Welcome back, ' . sanitize($user['name']) . '!');
                    
                    // Redirect to intended page or home
                    $redirect = $_SESSION['redirect_url'] ?? SITE_URL . '/';
                    unset($_SESSION['redirect_url']);
                    redirect($redirect);
                }
            } else {
                $errors[] = 'Invalid email or password.';
            }
        }
    }
}

$pageTitle = 'Login';
include __DIR__ . '/../includes/header.php';
?>

<meta name="site-url" content="<?php echo SITE_URL; ?>">

<!-- Login Page -->
<section class="auth-section">
    <div class="container">
        <div class="auth-card fade-in">
            <div class="brand-header">
                <i class="fas fa-utensils"></i>
                <h2>Welcome Back</h2>
                <p class="text-muted">Login to your Food-Mania account</p>
            </div>
            
            <?php echo displayFlash(); ?>
            
            <?php if (!empty($errors)): ?>
                <div class="alert alert-danger">
                    <ul class="mb-0">
                        <?php foreach ($errors as $err): ?>
                            <li><?php echo $err; ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>
            
            <form method="POST" action="" class="needs-validation" novalidate id="loginForm">
                <?php echo csrfField(); ?>
                
                <div class="mb-3">
                    <label for="email" class="form-label">Email Address <span class="text-danger">*</span></label>
                    <div class="input-group">
                        <span class="input-group-text"><i class="fas fa-envelope"></i></span>
                        <input type="email" class="form-control" id="email" name="email" required
                               value="<?php echo sanitize($_POST['email'] ?? ''); ?>"
                               placeholder="Enter your email">
                    </div>
                </div>
                
                <div class="mb-4">
                    <label for="password" class="form-label">Password <span class="text-danger">*</span></label>
                    <div class="input-group">
                        <span class="input-group-text"><i class="fas fa-lock"></i></span>
                        <input type="password" class="form-control" id="password" name="password" required
                               placeholder="Enter your password">
                    </div>
                </div>
                
                <button type="submit" class="btn btn-auth">
                    <i class="fas fa-sign-in-alt me-2"></i> Login
                </button>
                
                <div class="text-center mt-3">
                    <p class="text-muted mb-0">
                        Don't have an account? 
                        <a href="<?php echo SITE_URL; ?>/auth/register.php" class="fw-600">Register here</a>
                    </p>
                </div>
            </form>
            
            <!-- Demo Credentials -->
            <div class="mt-4 p-3" style="background:var(--gray-100);border-radius:var(--radius-sm);">
                <p class="mb-1 fw-600 text-muted" style="font-size:0.8rem;">
                    <i class="fas fa-info-circle me-1"></i> Demo Credentials:
                </p>
                <p class="mb-0" style="font-size:0.8rem;color:var(--gray-500);">
                    Email: <code>rahul@example.com</code> | Password: <code>admin123</code>
                </p>
            </div>
        </div>
    </div>
</section>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script src="<?php echo SITE_URL; ?>/assets/js/script.js"></script>
</body>
</html>
