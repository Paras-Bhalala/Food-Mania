<?php
/**
 * Admin Sidebar Include
 * Food-Mania - Modern Sidebar navigation with live status badges + top bar
 */

$admin = getLoggedInAdmin($pdo);
$currentPage = basename($_SERVER['PHP_SELF']);

// Live badge counts
$sidebarPendingOrders = (int)$pdo->query("SELECT COUNT(*) FROM orders WHERE status = 'pending'")->fetchColumn();
$sidebarUnreadMsgs    = (int)$pdo->query("SELECT COUNT(*) FROM contact_messages WHERE is_read = 0")->fetchColumn();
?>

<div class="admin-wrapper">
    <!-- Sidebar -->
    <aside class="admin-sidebar">
        <div class="sidebar-brand">
            <h4>
                <i class="fas fa-utensils me-2 text-warning"></i>
                Food<span class="text-accent" style="color:#FFC107;">Mania</span>
            </h4>
            <span class="badge bg-warning text-dark mt-1" style="font-size:0.7rem; letter-spacing:0.5px;">ADMIN PANEL</span>
        </div>
        
        <ul class="sidebar-nav">
            <li class="nav-section-title text-uppercase px-4 py-2" style="font-size:0.7rem; color:rgba(255,255,255,0.4); font-weight:700;">
                Core Operations
            </li>
            <li>
                <a href="<?php echo SITE_URL; ?>/admin/dashboard.php" class="<?php echo $currentPage === 'dashboard.php' ? 'active' : ''; ?>">
                    <i class="fas fa-tachometer-alt"></i> Dashboard
                </a>
            </li>
            <li>
                <a href="<?php echo SITE_URL; ?>/admin/orders.php" class="<?php echo $currentPage === 'orders.php' ? 'active' : ''; ?> d-flex justify-content-between align-items-center">
                    <div><i class="fas fa-shopping-bag"></i> Orders</div>
                    <?php if ($sidebarPendingOrders > 0): ?>
                        <span class="badge bg-warning text-dark rounded-pill"><?php echo $sidebarPendingOrders; ?></span>
                    <?php endif; ?>
                </a>
            </li>
            <li>
                <a href="<?php echo SITE_URL; ?>/admin/categories.php" class="<?php echo $currentPage === 'categories.php' ? 'active' : ''; ?>">
                    <i class="fas fa-th-large"></i> Categories
                </a>
            </li>
            <li>
                <a href="<?php echo SITE_URL; ?>/admin/food_items.php" class="<?php echo $currentPage === 'food_items.php' ? 'active' : ''; ?>">
                    <i class="fas fa-hamburger"></i> Food Items
                </a>
            </li>

            <li class="nav-section-title text-uppercase px-4 pt-3 pb-1" style="font-size:0.7rem; color:rgba(255,255,255,0.4); font-weight:700;">
                Analytics & Reports
            </li>
            <li>
                <a href="<?php echo SITE_URL; ?>/admin/reports.php" class="<?php echo $currentPage === 'reports.php' ? 'active' : ''; ?>">
                    <i class="fas fa-chart-bar"></i> Sales Reports
                </a>
            </li>
            <li>
                <a href="<?php echo SITE_URL; ?>/admin/users.php" class="<?php echo $currentPage === 'users.php' ? 'active' : ''; ?>">
                    <i class="fas fa-users"></i> Users & Customers
                </a>
            </li>
            <li>
                <a href="<?php echo SITE_URL; ?>/admin/reviews.php" class="<?php echo $currentPage === 'reviews.php' ? 'active' : ''; ?>">
                    <i class="fas fa-star"></i> Reviews & Ratings
                </a>
            </li>
            <li>
                <a href="<?php echo SITE_URL; ?>/admin/messages.php" class="<?php echo $currentPage === 'messages.php' ? 'active' : ''; ?> d-flex justify-content-between align-items-center">
                    <div><i class="fas fa-envelope"></i> Messages</div>
                    <?php if ($sidebarUnreadMsgs > 0): ?>
                        <span class="badge bg-danger rounded-pill"><?php echo $sidebarUnreadMsgs; ?></span>
                    <?php endif; ?>
                </a>
            </li>

            <li style="margin-top:1.5rem; border-top:1px solid rgba(255,255,255,0.08); padding-top:1rem;">
                <a href="<?php echo SITE_URL; ?>/" target="_blank">
                    <i class="fas fa-external-link-alt"></i> View Website
                </a>
            </li>
            <li>
                <a href="<?php echo SITE_URL; ?>/admin/logout.php" style="color:rgba(255,100,100,0.85);">
                    <i class="fas fa-sign-out-alt"></i> Logout
                </a>
            </li>
        </ul>
    </aside>
    
    <!-- Main Content Area -->
    <div class="admin-content">
        <!-- Top Bar -->
        <div class="admin-topbar">
            <div class="d-flex align-items-center">
                <button class="btn btn-sm btn-outline-secondary d-lg-none me-3" id="sidebarToggle">
                    <i class="fas fa-bars"></i>
                </button>
                <div>
                    <h5 class="mb-0 fw-bold"><?php echo $pageTitle ?? 'Dashboard'; ?></h5>
                    <small class="text-muted d-none d-sm-inline">Food-Mania Single Restaurant Administration</small>
                </div>
            </div>
            <div class="d-flex align-items-center gap-3">
                <a href="<?php echo SITE_URL; ?>/admin/messages.php" class="btn btn-light btn-sm position-relative rounded-circle p-2" title="Unread Messages" style="width:36px;height:36px;display:flex;align-items:center;justify-content:center;">
                    <i class="fas fa-bell text-secondary"></i>
                    <?php if ($sidebarUnreadMsgs > 0): ?>
                        <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger" style="font-size:0.6rem;">
                            <?php echo $sidebarUnreadMsgs; ?>
                        </span>
                    <?php endif; ?>
                </a>
                <div class="d-flex align-items-center">
                    <div class="rounded-circle bg-warning text-dark fw-bold d-flex align-items-center justify-content-center me-2" style="width:34px;height:34px;">
                        <i class="fas fa-user-shield"></i>
                    </div>
                    <div class="d-none d-md-block text-start me-3">
                        <div class="fw-bold small"><?php echo sanitize($admin['name'] ?? 'Admin'); ?></div>
                        <small class="text-muted" style="font-size:0.75rem;">Administrator</small>
                    </div>
                </div>
                <a href="<?php echo SITE_URL; ?>/admin/logout.php" class="btn btn-sm btn-outline-danger rounded-pill px-3" title="Logout">
                    <i class="fas fa-sign-out-alt me-1"></i> <span class="d-none d-sm-inline">Logout</span>
                </a>
            </div>
        </div>
        
        <!-- Admin Main Content -->
        <div class="admin-main">
