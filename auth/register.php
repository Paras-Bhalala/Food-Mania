<?php
/**
 * User Registration
 * Food-Mania - Single Restaurant Food Ordering System
 * 
 * Registration form with server-side validation, password hashing, duplicate email check.
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
        $name     = sanitize($_POST['name'] ?? '');
        $email    = sanitize($_POST['email'] ?? '');
        $phone    = sanitize($_POST['phone'] ?? '');
        $password = $_POST['password'] ?? '';
        $confirm  = $_POST['confirm_password'] ?? '';
        
        // Validate
        if (empty($name))    $errors[] = 'Full name is required.';
        if (strlen($name) < 2) $errors[] = 'Name must be at least 2 characters.';
        if (empty($email))   $errors[] = 'Email is required.';
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = 'Please enter a valid email.';
        if (!empty($phone) && !preg_match('/^[0-9]{10}$/', $phone)) $errors[] = 'Phone must be 10 digits.';
        if (empty($password)) $errors[] = 'Password is required.';
        if (strlen($password) < 6) $errors[] = 'Password must be at least 6 characters.';
        if ($password !== $confirm) $errors[] = 'Passwords do not match.';
        
        // Check duplicate email
        if (empty($errors)) {
            $stmt = $pdo->prepare("SELECT id FROM users WHERE email = :email");
            $stmt->execute([':email' => $email]);
            if ($stmt->fetch()) {
                $errors[] = 'An account with this email already exists.';
            }
        }
        
        // Create account
        if (empty($errors)) {
            $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("INSERT INTO users (name, email, phone, password) VALUES (:name, :email, :phone, :password)");
            $stmt->execute([
                ':name'     => $name,
                ':email'    => $email,
                ':phone'    => $phone,
                ':password' => $hashedPassword,
            ]);
            
            setFlash('success', 'Registration successful! Please login to continue.');
            redirect(SITE_URL . '/auth/login.php');
        }
    }
}

$pageTitle = 'Register';
include __DIR__ . '/../includes/header.php';
?>

<meta name="site-url" content="<?php echo SITE_URL; ?>">

<!-- Register Page -->
<section class="auth-section">
    <div class="container">
        <div class="auth-card fade-in">
            <div class="brand-header">
                <i class="fas fa-utensils"></i>
                <h2>Create Account</h2>
                <p class="text-muted">Join Food-Mania and start ordering!</p>
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
            
            <form method="POST" action="" class="needs-validation" novalidate id="registerForm">
                <?php echo csrfField(); ?>
                
                <div class="mb-3">
                    <label for="name" class="form-label">Full Name <span class="text-danger">*</span></label>
                    <div class="input-group">
                        <span class="input-group-text"><i class="fas fa-user"></i></span>
                        <input type="text" class="form-control" id="name" name="name" required
                               value="<?php echo sanitize($_POST['name'] ?? ''); ?>"
                               placeholder="Enter your full name" minlength="2">
                    </div>
                </div>
                
                <div class="mb-3">
                    <label for="email" class="form-label">Email Address <span class="text-danger">*</span></label>
                    <div class="input-group">
                        <span class="input-group-text"><i class="fas fa-envelope"></i></span>
                        <input type="email" class="form-control" id="email" name="email" required
                               value="<?php echo sanitize($_POST['email'] ?? ''); ?>"
                               placeholder="Enter your email">
                    </div>
                </div>
                
                <div class="mb-3">
                    <label for="phone" class="form-label">Phone Number</label>
                    <div class="input-group">
                        <span class="input-group-text"><i class="fas fa-phone"></i></span>
                        <input type="tel" class="form-control" id="phone" name="phone"
                               value="<?php echo sanitize($_POST['phone'] ?? ''); ?>"
                               placeholder="10-digit number" pattern="[0-9]{10}">
                    </div>
                </div>
                
                <div class="mb-3">
                    <label for="password" class="form-label">Password <span class="text-danger">*</span></label>
                    <div class="input-group">
                        <span class="input-group-text"><i class="fas fa-lock"></i></span>
                        <input type="password" class="form-control" id="password" name="password" required
                               placeholder="Min 6 characters" minlength="6">
                    </div>
                </div>
                
                <div class="mb-4">
                    <label for="confirm_password" class="form-label">Confirm Password <span class="text-danger">*</span></label>
                    <div class="input-group">
                        <span class="input-group-text"><i class="fas fa-lock"></i></span>
                        <input type="password" class="form-control" id="confirm_password" name="confirm_password" required
                               placeholder="Re-enter password">
                    </div>
                </div>
                
                <button type="submit" class="btn btn-auth">
                    <i class="fas fa-user-plus me-2"></i> Create Account
                </button>
                
                <div class="text-center mt-3">
                    <p class="text-muted mb-0">
                        Already have an account? 
                        <a href="<?php echo SITE_URL; ?>/auth/login.php" class="fw-600">Login here</a>
                    </p>
                </div>
            </form>
        </div>
    </div>
</section>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script src="<?php echo SITE_URL; ?>/assets/js/script.js"></script>
</body>
</html>
