<?php
/**
 * Profile Page
 * Food-Mania - Edit name/phone/address and change password
 */

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/functions.php';

requireLogin();

$pdo = getDBConnection();
$userId = $_SESSION['user_id'];
$user = getLoggedInUser($pdo);

$errors = [];
$passwordErrors = [];

// Handle profile update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_profile'])) {
    if (!validateCSRFToken($_POST['csrf_token'] ?? '')) {
        $errors[] = 'Invalid form submission.';
    } else {
        $name    = sanitize($_POST['name'] ?? '');
        $phone   = sanitize($_POST['phone'] ?? '');
        $address = sanitize($_POST['address'] ?? '');
        
        if (empty($name)) $errors[] = 'Name is required.';
        if (!empty($phone) && !preg_match('/^[0-9]{10}$/', $phone)) $errors[] = 'Phone must be 10 digits.';
        
        if (empty($errors)) {
            $stmt = $pdo->prepare("UPDATE users SET name = :name, phone = :phone, address = :address WHERE id = :id");
            $stmt->execute([
                ':name'    => $name,
                ':phone'   => $phone,
                ':address' => $address,
                ':id'      => $userId,
            ]);
            
            $_SESSION['user_name'] = $name;
            setFlash('success', 'Profile updated successfully!');
            redirect(SITE_URL . '/user/profile.php');
        }
    }
}

// Handle password change
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['change_password'])) {
    if (!validateCSRFToken($_POST['csrf_token'] ?? '')) {
        $passwordErrors[] = 'Invalid form submission.';
    } else {
        $currentPassword = $_POST['current_password'] ?? '';
        $newPassword     = $_POST['new_password'] ?? '';
        $confirmPassword = $_POST['confirm_password'] ?? '';
        
        if (empty($currentPassword)) $passwordErrors[] = 'Current password is required.';
        if (empty($newPassword))     $passwordErrors[] = 'New password is required.';
        if (strlen($newPassword) < 6) $passwordErrors[] = 'New password must be at least 6 characters.';
        if ($newPassword !== $confirmPassword) $passwordErrors[] = 'Passwords do not match.';
        
        if (empty($passwordErrors)) {
            if (!password_verify($currentPassword, $user['password'])) {
                $passwordErrors[] = 'Current password is incorrect.';
            } else {
                $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
                $stmt = $pdo->prepare("UPDATE users SET password = :password WHERE id = :id");
                $stmt->execute([':password' => $hashedPassword, ':id' => $userId]);
                
                setFlash('success', 'Password changed successfully!');
                redirect(SITE_URL . '/user/profile.php');
            }
        }
    }
}

// Re-fetch user data
$user = getLoggedInUser($pdo);

$pageTitle = 'My Profile';
include __DIR__ . '/../includes/header.php';
include __DIR__ . '/../includes/navbar.php';
?>

<meta name="site-url" content="<?php echo SITE_URL; ?>">

<!-- Page Header -->
<div class="page-header">
    <div class="container">
        <h1><i class="fas fa-user me-2"></i> My Profile</h1>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="<?php echo SITE_URL; ?>/">Home</a></li>
                <li class="breadcrumb-item active" aria-current="page">Profile</li>
            </ol>
        </nav>
    </div>
</div>

<section class="section-padding">
    <div class="container">
        <?php echo displayFlash(); ?>
        
        <div class="row g-4">
            <!-- Profile Info -->
            <div class="col-lg-4">
                <div class="profile-card text-center">
                    <div class="profile-avatar">
                        <i class="fas fa-user"></i>
                    </div>
                    <h4 class="fw-700"><?php echo sanitize($user['name']); ?></h4>
                    <p class="text-muted"><?php echo sanitize($user['email']); ?></p>
                    <hr>
                    <div class="text-start">
                        <p class="mb-2"><i class="fas fa-phone text-orange me-2"></i> <?php echo sanitize($user['phone'] ?: 'Not set'); ?></p>
                        <p class="mb-2"><i class="fas fa-map-marker-alt text-orange me-2"></i> <?php echo sanitize($user['address'] ?: 'Not set'); ?></p>
                        <p class="mb-0"><i class="fas fa-calendar text-orange me-2"></i> Member since <?php echo formatDate($user['created_at'], 'M Y'); ?></p>
                    </div>
                </div>
            </div>
            
            <!-- Edit Forms -->
            <div class="col-lg-8">
                <!-- Edit Profile -->
                <div class="profile-card mb-4">
                    <h4 class="mb-4"><i class="fas fa-edit text-orange me-2"></i> Edit Profile</h4>
                    
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
                        <input type="hidden" name="update_profile" value="1">
                        
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label for="name" class="form-label">Full Name</label>
                                <input type="text" class="form-control" id="name" name="name" 
                                       value="<?php echo sanitize($user['name']); ?>" required>
                            </div>
                            <div class="col-md-6">
                                <label for="email_display" class="form-label">Email (read-only)</label>
                                <input type="email" class="form-control" id="email_display" 
                                       value="<?php echo sanitize($user['email']); ?>" readonly disabled>
                            </div>
                            <div class="col-md-6">
                                <label for="phone" class="form-label">Phone</label>
                                <input type="tel" class="form-control" id="phone" name="phone" 
                                       value="<?php echo sanitize($user['phone'] ?? ''); ?>"
                                       pattern="[0-9]{10}" maxlength="10" placeholder="10-digit number">
                            </div>
                            <div class="col-12">
                                <label for="address" class="form-label">Address</label>
                                <textarea class="form-control" id="address" name="address" rows="3"
                                          placeholder="Enter your delivery address"><?php echo sanitize($user['address'] ?? ''); ?></textarea>
                            </div>
                            <div class="col-12">
                                <button type="submit" class="btn btn-primary-custom rounded-pill">
                                    <i class="fas fa-save me-2"></i> Save Changes
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
                
                <!-- Change Password -->
                <div class="profile-card">
                    <h4 class="mb-4"><i class="fas fa-lock text-orange me-2"></i> Change Password</h4>
                    
                    <?php if (!empty($passwordErrors)): ?>
                        <div class="alert alert-danger">
                            <ul class="mb-0">
                                <?php foreach ($passwordErrors as $err): ?>
                                    <li><?php echo $err; ?></li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                    <?php endif; ?>
                    
                    <form method="POST" action="">
                        <?php echo csrfField(); ?>
                        <input type="hidden" name="change_password" value="1">
                        
                        <div class="row g-3">
                            <div class="col-md-12">
                                <label for="current_password" class="form-label">Current Password</label>
                                <input type="password" class="form-control" id="current_password" 
                                       name="current_password" required>
                            </div>
                            <div class="col-md-6">
                                <label for="new_password" class="form-label">New Password</label>
                                <input type="password" class="form-control" id="new_password" 
                                       name="new_password" required minlength="6">
                            </div>
                            <div class="col-md-6">
                                <label for="confirm_password" class="form-label">Confirm New Password</label>
                                <input type="password" class="form-control" id="confirm_password" 
                                       name="confirm_password" required>
                            </div>
                            <div class="col-12">
                                <button type="submit" class="btn btn-outline-secondary rounded-pill">
                                    <i class="fas fa-key me-2"></i> Change Password
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</section>

<?php include __DIR__ . '/../includes/footer.php'; ?>
