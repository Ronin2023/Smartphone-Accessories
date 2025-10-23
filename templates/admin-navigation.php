<?php
/**
 * Admin Navigation Template
 * Role-based navigation for admin panel
 * Shows different menu items based on user role (admin vs editor)
 */

// Ensure user is logged in and has admin access
if (!isLoggedIn() || !hasAdminAccess()) {
    redirect('login.php');
    exit();
}

// Get current page for active state
$currentPage = basename($_SERVER['PHP_SELF']);
?>

<aside class="admin-sidebar">
    <div class="sidebar-header">
        <div class="logo">
            <i class="fas fa-balance-scale-right"></i>
            <span>TechCompare</span>
        </div>
        <p class="admin-welcome">
            Welcome, <?php echo htmlspecialchars($_SESSION['username']); ?>
            <small style="display: block; font-size: 0.8em; opacity: 0.8; margin-top: 2px;">
                <?php echo getUserRoleDisplay($_SESSION['user_role']); ?>
            </small>
        </p>
    </div>
    <nav class="sidebar-nav">
        <ul>
            <li class="nav-item <?php echo ($currentPage === 'dashboard.php') ? 'active' : ''; ?>">
                <a href="dashboard" class="nav-link">
                    <i class="fas fa-tachometer-alt"></i>
                    <span>Dashboard</span>
                </a>
            </li>
            <li class="nav-item <?php echo ($currentPage === 'products.php') ? 'active' : ''; ?>">
                <a href="products" class="nav-link">
                    <i class="fas fa-box"></i>
                    <span>Products</span>
                </a>
            </li>
            <li class="nav-item <?php echo ($currentPage === 'categories.php') ? 'active' : ''; ?>">
                <a href="categories" class="nav-link">
                    <i class="fas fa-tags"></i>
                    <span>Categories</span>
                </a>
            </li>
            <li class="nav-item <?php echo ($currentPage === 'brands.php') ? 'active' : ''; ?>">
                <a href="brands" class="nav-link">
                    <i class="fas fa-award"></i>
                    <span>Brands</span>
                </a>
            </li>
            
            <?php if (isAdmin()): ?>
            <!-- Admin-only sections -->
            <li class="nav-item <?php echo ($currentPage === 'users.php') ? 'active' : ''; ?>">
                <a href="users" class="nav-link">
                    <i class="fas fa-users"></i>
                    <span>Users</span>
                </a>
            </li>
            <?php endif; ?>
            
            <li class="nav-item <?php echo ($currentPage === 'contacts.php') ? 'active' : ''; ?>">
                <a href="contacts" class="nav-link">
                    <i class="fas fa-envelope"></i>
                    <span>Contact Messages</span>
                </a>
            </li>
            
            <?php if (isAdmin()): ?>
            <!-- Settings - Admin only -->
            <li class="nav-item <?php echo ($currentPage === 'settings.php') ? 'active' : ''; ?>">
                <a href="settings" class="nav-link">
                    <i class="fas fa-cogs"></i>
                    <span>Settings</span>
                </a>
            </li>
            <?php endif; ?>
            
            <li class="nav-divider" style="margin: 1rem 0; border-top: 1px solid rgba(255,255,255,0.1);"></li>
            <li class="nav-item">
                <a href="../index" class="nav-link" target="_blank">
                    <i class="fas fa-external-link-alt"></i>
                    <span>View Site</span>
                </a>
            </li>
            <li class="nav-item">
                <a href="logout" class="nav-link" style="color: #dc3545 !important; background: rgba(220, 53, 69, 0.1); border-radius: 6px; margin: 0 1rem;">
                    <i class="fas fa-sign-out-alt"></i>
                    <span>Logout</span>
                </a>
            </li>
        </ul>
    </nav>
</aside>