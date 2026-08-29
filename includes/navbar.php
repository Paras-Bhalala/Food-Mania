<?php
/**
 * Navbar Include
 * Food-Mania - Sticky top navigation bar
 * 
 * Displays: brand logo, nav links, cart badge, auth links.
 * Cart badge updates dynamically via JavaScript.
 */

// Get cart count if user is logged in
$cartCount = 0;
if (isLoggedIn()) {
    $pdo = getDBConnection();
    $cartCount = getCartCount($pdo, $_SESSION['user_id']);
}
?>
<!-- Main Navigation -->
<nav class="navbar navbar-expand-lg navbar-dark sticky-top" id="mainNavbar">
    <div class="container">
        <!-- Brand -->
        <a class="navbar-brand d-flex align-items-center" href="<?php echo SITE_URL; ?>/">
            <i class="fas fa-utensils me-2"></i>
            <span class="brand-text">Food<span class="text-accent">Mania</span></span>
        </a>
        
        <!-- Mobile Toggle -->
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" 
                data-bs-target="#navbarContent" aria-controls="navbarContent" 
                aria-expanded="false" aria-label="Toggle navigation">
            <span class="navbar-toggler-icon"></span>
        </button>
        
        <!-- Nav Links -->
        <div class="collapse navbar-collapse" id="navbarContent">
            <ul class="navbar-nav mx-auto mb-2 mb-lg-0">
                <li class="nav-item">
                    <a class="nav-link" href="<?php echo SITE_URL; ?>/">
                        <i class="fas fa-home me-1"></i> Home
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="<?php echo SITE_URL; ?>/user/menu.php">
                        <i class="fas fa-book-open me-1"></i> Menu
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="<?php echo SITE_URL; ?>/about.php">
                        <i class="fas fa-info-circle me-1"></i> About
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="<?php echo SITE_URL; ?>/contact.php">
                        <i class="fas fa-envelope me-1"></i> Contact
                    </a>
                </li>
            </ul>
            
            <!-- Right Side: Cart + Auth -->
            <ul class="navbar-nav ms-auto mb-2 mb-lg-0 align-items-center">
                <!-- Cart -->
                <li class="nav-item me-2">
                    <a class="nav-link cart-link position-relative" href="<?php echo SITE_URL; ?>/user/cart.php">
                        <i class="fas fa-shopping-cart fa-lg"></i>
                        <span class="cart-badge badge rounded-pill" id="cartBadge"
                              style="<?php echo $cartCount > 0 ? '' : 'display:none;'; ?>">
                            <?php echo $cartCount; ?>
                        </span>
                    </a>
                </li>
                
                <?php if (isLoggedIn()): ?>
                    <!-- Logged-in User Dropdown -->
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle d-flex align-items-center" href="#" 
                           role="button" data-bs-toggle="dropdown" aria-expanded="false">
                            <i class="fas fa-user-circle fa-lg me-1"></i>
                            <span class="d-none d-lg-inline"><?php echo sanitize($_SESSION['user_name']); ?></span>
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end dropdown-menu-dark">
                            <li>
                                <a class="dropdown-item" href="<?php echo SITE_URL; ?>/user/profile.php">
                                    <i class="fas fa-user me-2"></i> My Profile
                                </a>
                            </li>
                            <li>
                                <a class="dropdown-item" href="<?php echo SITE_URL; ?>/user/order_history.php">
                                    <i class="fas fa-receipt me-2"></i> My Orders
                                </a>
                            </li>
                            <li><hr class="dropdown-divider"></li>
                            <li>
                                <a class="dropdown-item text-danger" href="<?php echo SITE_URL; ?>/auth/logout.php">
                                    <i class="fas fa-sign-out-alt me-2"></i> Logout
                                </a>
                            </li>
                        </ul>
                    </li>
                <?php else: ?>
                    <!-- Guest Links -->
                    <li class="nav-item">
                        <a class="nav-link" href="<?php echo SITE_URL; ?>/auth/login.php">
                            <i class="fas fa-sign-in-alt me-1"></i> Login
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="btn btn-accent btn-sm ms-2 rounded-pill px-3" 
                           href="<?php echo SITE_URL; ?>/auth/register.php">
                            <i class="fas fa-user-plus me-1"></i> Register
                        </a>
                    </li>
                <?php endif; ?>
            </ul>
        </div>
    </div>
</nav>

<!-- Toast Container for notifications -->
<div class="toast-container position-fixed top-0 end-0 p-3" style="z-index: 9999;" id="toastContainer"></div>
