<?php
session_start();
set_time_limit(30);

require_once '../includes/config.php';
require_once '../includes/db_connect.php';
require_once '../includes/functions.php';

// Check authentication - Allow both admin and editor access
if (!isLoggedIn() || !hasAdminAccess()) {
    redirect('login.php');
    exit();
}

$error = null;
$stats = array('products' => 0, 'categories' => 0, 'brands' => 0, 'featured' => 0, 'contacts' => 0);

// Check if table exists
function tableExists($table) {
    try {
        $db = getDB();
        $stmt = $db->query("SHOW TABLES LIKE '$table'");
        $exists = $stmt->rowCount() > 0;
        closeDB($db);
        return $exists;
    } catch (Exception $e) {
        return false;
    }
}

// Get stats
try {
    $db = getDB();
    
    if (tableExists('products')) {
        $stmt = $db->query('SELECT COUNT(*) FROM products');
        $stats['products'] = (int)$stmt->fetchColumn();
        
        $stmt = $db->query('SELECT COUNT(*) FROM products WHERE is_featured = 1');
        $stats['featured'] = (int)$stmt->fetchColumn();
    }
    
    if (tableExists('categories')) {
        $stmt = $db->query('SELECT COUNT(*) FROM categories');
        $stats['categories'] = (int)$stmt->fetchColumn();
    }
    
    if (tableExists('brands')) {
        $stmt = $db->query('SELECT COUNT(*) FROM brands');
        $stats['brands'] = (int)$stmt->fetchColumn();
    }
    
    if (tableExists('contact_submissions')) {
        $stmt = $db->query('SELECT COUNT(*) FROM contact_submissions');
        $stats['contacts'] = (int)$stmt->fetchColumn();
    }
    
    closeDB($db);
} catch (Exception $e) {
    $error = "Database error: " . $e->getMessage();
}

// Check maintenance mode status
$maintenance_status = [
    'enabled' => false,
    'end_time' => null,
    'message' => '',
    'admin_key' => md5(SITE_NAME . date('Y-m-d'))
];

try {
    $db = getDB();
    if (tableExists('settings')) {
        $stmt = $db->prepare("SELECT setting_key, setting_value FROM settings WHERE setting_key LIKE 'maintenance_%'");
        $stmt->execute();
        $maintenance_settings = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);
        
        if (isset($maintenance_settings['maintenance_enabled']) && $maintenance_settings['maintenance_enabled'] == '1') {
            $maintenance_status['enabled'] = true;
            $maintenance_status['end_time'] = $maintenance_settings['maintenance_end_time'] ?? null;
            $maintenance_status['message'] = $maintenance_settings['maintenance_message'] ?? 'Site is under maintenance';
        }
    }
    closeDB($db);
} catch (Exception $e) {
    // Maintenance status check failed, continue normally
}

$showAll = isset($_GET['show']) && $_GET['show'] === 'products';
$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$products = array();

if ($showAll && $stats['products'] > 0) {
    try {
        $perPage = 12;
        $offset = ($page - 1) * $perPage;
        
        $db = getDB();
        $countStmt = $db->query('SELECT COUNT(*) FROM products');
        $totalProducts = (int)$countStmt->fetchColumn();
        $totalPages = ceil($totalProducts / $perPage);
        
        $stmt = $db->prepare('
            SELECT p.id, p.name, p.price, p.main_image, p.created_at, p.is_featured,
                   COALESCE(b.name, "Unknown") as brand_name,
                   COALESCE(c.name, "Uncategorized") as category_name
            FROM products p 
            LEFT JOIN brands b ON p.brand_id = b.id 
            LEFT JOIN categories c ON p.category_id = c.id 
            ORDER BY p.created_at DESC 
            LIMIT :limit OFFSET :offset
        ');
        $stmt->bindValue(':limit', $perPage, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();
        $products = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        closeDB($db);
    } catch (Exception $e) {
        $error = "Error loading products: " . $e->getMessage();
    }
} else {
    // Get recent products for summary
    try {
        if ($stats['products'] > 0) {
            $db = getDB();
            $stmt = $db->query('
                SELECT p.id, p.name, p.price, p.main_image,
                       COALESCE(b.name, "Unknown") as brand_name
                FROM products p 
                LEFT JOIN brands b ON p.brand_id = b.id 
                ORDER BY p.created_at DESC 
                LIMIT 5
            ');
            $products = $stmt->fetchAll(PDO::FETCH_ASSOC);
            closeDB($db);
        }
    } catch (Exception $e) {
        $error = "Error loading recent products: " . $e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - TechCompare</title>
    <link rel="stylesheet" href="../css/style.css">
    <link rel="stylesheet" href="../css/admin.css">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        .dashboard-content {
            padding: 2rem;
            background: #f8f9fa;
            min-height: calc(100vh - 120px);
        }
        
        .dashboard-section {
            background: white;
            border-radius: 12px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.05);
            margin-bottom: 2rem;
            overflow: hidden;
            border: 1px solid rgba(226, 232, 240, 0.8);
        }
        
        .section-header {
            padding: 1.5rem;
            border-bottom: 1px solid #e2e8f0;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .section-header h2 {
            margin: 0;
            color: #2d3748;
            font-size: 1.2rem;
            font-weight: 600;
        }
        
        .section-content {
            padding: 1.5rem;
        }
        
        .product-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 1.5rem;
        }
        
        .product-card {
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            padding: 1rem;
            background: white;
            transition: all 0.3s ease;
        }
        
        .product-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 15px rgba(0, 0, 0, 0.1);
        }
        
        .product-image {
            width: 100%;
            height: 200px;
            object-fit: cover;
            border-radius: 6px;
            margin-bottom: 1rem;
        }
        
        .no-image {
            width: 100%;
            height: 200px;
            background: #f8f9fa;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 6px;
            margin-bottom: 1rem;
            color: #6c757d;
            border: 2px dashed #dee2e6;
        }
        
        .product-info h4 {
            margin: 0 0 0.5rem 0;
            color: #2d3748;
            font-size: 1.1rem;
            font-weight: 600;
        }
        
        .product-meta {
            color: #718096;
            font-size: 0.9rem;
            margin-bottom: 0.5rem;
        }
        
        .product-price {
            color: #4361ee;
            font-size: 1.1rem;
            font-weight: 700;
            margin: 0.5rem 0;
        }
        
        .view-toggle {
            margin: 2rem 0;
            display: flex;
            gap: 1rem;
        }
        
        .btn {
            padding: 0.75rem 1.5rem;
            border-radius: 6px;
            text-decoration: none;
            font-weight: 500;
            transition: all 0.3s ease;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
        }
        
        .btn-primary {
            background: #4361ee;
            color: white;
            border: 1px solid #4361ee;
        }
        
        .btn-primary:hover {
            background: #3451d9;
            border-color: #3451d9;
        }
        
        .btn-outline {
            background: white;
            color: #4361ee;
            border: 1px solid #4361ee;
        }
        
        .btn-outline:hover {
            background: #4361ee;
            color: white;
        }
        
        .btn-success {
            background: #28a745;
            color: white;
            border: 1px solid #28a745;
        }
        
        .quick-actions {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1.5rem;
        }
        
        .action-card {
            display: block;
            padding: 2rem 1.5rem;
            background: white;
            border: 2px solid #e2e8f0;
            border-radius: 12px;
            text-decoration: none;
            text-align: center;
            transition: all 0.3s ease;
            color: #2d3748;
        }
        
        .action-card:hover {
            transform: translateY(-4px);
            box-shadow: 0 8px 20px rgba(0, 0, 0, 0.1);
        }
        
        .action-card.primary:hover {
            border-color: #4361ee;
            color: #4361ee;
        }
        
        .action-card.success:hover {
            border-color: #28a745;
            color: #28a745;
        }
        
        .action-card.warning:hover {
            border-color: #ffc107;
            color: #ffc107;
        }
        
        .action-card.info:hover {
            border-color: #6f42c1;
            color: #6f42c1;
        }
        
        .action-icon {
            font-size: 2rem;
            margin-bottom: 1rem;
            display: block;
        }
        
        .empty-state {
            text-align: center;
            padding: 3rem 2rem;
            background: #f8f9fa;
            border-radius: 12px;
            color: #6c757d;
        }
        
        .empty-state i {
            font-size: 4rem;
            margin-bottom: 1rem;
            opacity: 0.5;
        }
        
        .pagination {
            display: flex;
            justify-content: center;
            align-items: center;
            gap: 0.5rem;
            margin: 2rem 0;
        }
        
        .pagination a,
        .pagination span {
            padding: 0.5rem 1rem;
            text-decoration: none;
            border: 1px solid #e2e8f0;
            border-radius: 6px;
            color: #4361ee;
            transition: all 0.3s ease;
        }
        
        .pagination a:hover {
            background: #f8f9fa;
            border-color: #4361ee;
        }
        
        .pagination .current {
            background: #4361ee;
            color: white;
            border-color: #4361ee;
        }
        
        .stat-card {
            background: white;
            padding: 2rem;
            border-radius: 12px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.05);
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
        }
        
        .stat-card:hover {
            transform: translateY(-4px);
            box-shadow: 0 12px 24px rgba(0, 0, 0, 0.15);
        }
        
        .stat-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 4px;
            height: 100%;
            background: var(--card-accent, #4361ee);
        }
        
        .stat-icon {
            width: 60px;
            height: 60px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 1.5rem;
            margin-bottom: 1rem;
        }
        
        .stat-number {
            font-size: 2.5rem;
            font-weight: 700;
            color: #2d3748;
            margin: 0.5rem 0;
            line-height: 1;
        }
        
        .stat-label {
            color: #718096;
            font-size: 0.9rem;
            font-weight: 500;
            margin: 0;
        }
        
        .stat-link {
            color: #4361ee;
            text-decoration: none;
            font-size: 0.8rem;
            font-weight: 500;
            margin-top: 0.5rem;
            display: inline-block;
        }
        
        .stat-link:hover {
            text-decoration: underline;
        }
        
        /* Logout button special styling */
        .nav-link[href="logout.php"]:hover {
            background: rgba(220, 53, 69, 0.2) !important;
            transform: translateX(4px);
            transition: all 0.3s ease;
        }
    </style>
</head>
<body class="admin-body">
    <div class="admin-container">
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
                    <li class="nav-item active">
                        <a href="dashboard.php" class="nav-link">
                            <i class="fas fa-tachometer-alt"></i>
                            <span>Dashboard</span>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="products.php" class="nav-link">
                            <i class="fas fa-box"></i>
                            <span>Products</span>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="categories.php" class="nav-link">
                            <i class="fas fa-tags"></i>
                            <span>Categories</span>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="brands.php" class="nav-link">
                            <i class="fas fa-award"></i>
                            <span>Brands</span>
                        </a>
                    </li>
                    <?php if (isAdmin()): ?>
                    <li class="nav-item">
                        <a href="users.php" class="nav-link">
                            <i class="fas fa-users"></i>
                            <span>Users</span>
                        </a>
                    </li>
                    <?php endif; ?>
                    <li class="nav-item">
                        <a href="contacts.php" class="nav-link">
                            <i class="fas fa-envelope"></i>
                            <span>Contact Messages</span>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="settings.php" class="nav-link">
                            <i class="fas fa-cogs"></i>
                            <span>Settings</span>
                        </a>
                    </li>
                    <li class="nav-divider" style="margin: 1rem 0; border-top: 1px solid rgba(255,255,255,0.1);"></li>
                    <li class="nav-item">
                        <a href="../index.php" class="nav-link" target="_blank">
                            <i class="fas fa-external-link-alt"></i>
                            <span>View Site</span>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="logout.php" class="nav-link" style="color: #dc3545 !important; background: rgba(220, 53, 69, 0.1); border-radius: 6px; margin: 0 1rem;">
                            <i class="fas fa-sign-out-alt"></i>
                            <span>Logout</span>
                        </a>
                    </li>
                </ul>
            </nav>
        </aside>

        <main class="admin-main">
            <div class="admin-header">
                <h1 class="page-title">Dashboard</h1>
                <div class="header-actions">
                    <span class="current-time" title="Server Time (GMT+5:30)">
                        <?php echo date('M d, Y - g:i A'); ?>
                        <small style="display: block; font-size: 0.7em; opacity: 0.7;">GMT+5:30</small>
                    </span>
                </div>
            </div>

            <?php if ($error): ?>
                <div class="alert alert-error" style="background: linear-gradient(135deg, #f8d7da, #f5c6cb); border: 1px solid #f5c6cb; border-left: 4px solid #dc3545; padding: 1rem 1.5rem; margin: 1.5rem 0; border-radius: 8px; color: #721c24;">
                    <div style="display: flex; align-items: center; gap: 0.75rem;">
                        <i class="fas fa-exclamation-triangle" style="font-size: 1.2rem; color: #dc3545;"></i>
                        <div>
                            <strong>Error:</strong> <?php echo htmlspecialchars($error); ?>
                        </div>
                    </div>
                </div>
            <?php endif; ?>

            <div class="dashboard-content">
                <div class="stats-grid">
                    <div class="stat-card" style="--card-accent: #4361ee;">
                        <div class="stat-icon" style="background: linear-gradient(135deg, #4361ee, #7209b7);">
                            <i class="fas fa-box"></i>
                        </div>
                        <div class="stat-content">
                            <div class="stat-number"><?php echo number_format($stats['products']); ?></div>
                            <p class="stat-label">Total Products</p>
                            <?php if ($stats['products'] > 0): ?>
                                <a href="?show=products" class="stat-link">View All Products</a>
                            <?php endif; ?>
                        </div>
                    </div>

                    <div class="stat-card" style="--card-accent: #28a745;">
                        <div class="stat-icon" style="background: linear-gradient(135deg, #28a745, #20c997);">
                            <i class="fas fa-tags"></i>
                        </div>
                        <div class="stat-content">
                            <div class="stat-number"><?php echo number_format($stats['categories']); ?></div>
                            <p class="stat-label">Categories</p>
                        </div>
                    </div>

                    <div class="stat-card" style="--card-accent: #ffc107;">
                        <div class="stat-icon" style="background: linear-gradient(135deg, #ffc107, #fd7e14);">
                            <i class="fas fa-award"></i>
                        </div>
                        <div class="stat-content">
                            <div class="stat-number"><?php echo number_format($stats['brands']); ?></div>
                            <p class="stat-label">Brands</p>
                        </div>
                    </div>

                    <div class="stat-card" style="--card-accent: #6f42c1;">
                        <div class="stat-icon" style="background: linear-gradient(135deg, #6f42c1, #e83e8c);">
                            <i class="fas fa-star"></i>
                        </div>
                        <div class="stat-content">
                            <div class="stat-number"><?php echo number_format($stats['featured']); ?></div>
                            <p class="stat-label">Featured Products</p>
                        </div>
                    </div>

                    <div class="stat-card" style="--card-accent: #dc3545;">
                        <div class="stat-icon" style="background: linear-gradient(135deg, #dc3545, #fd7e14);">
                            <i class="fas fa-envelope"></i>
                        </div>
                        <div class="stat-content">
                            <div class="stat-number"><?php echo number_format($stats['contacts']); ?></div>
                            <p class="stat-label">Contact Submissions</p>
                        </div>
                    </div>
                </div>

                <!-- Maintenance Mode Status -->
                <?php if ($maintenance_status['enabled']): ?>
                <div class="maintenance-alert" style="
                    background: #fef3c7;
                    border: 2px solid #f59e0b;
                    border-radius: 10px;
                    padding: 1rem 1.5rem;
                    margin: 1.5rem 0;
                    display: flex;
                    align-items: center;
                    gap: 1rem;
                ">
                    <div style="color: #f59e0b; font-size: 1.5rem;">
                        <i class="fas fa-exclamation-triangle"></i>
                    </div>
                    <div style="flex: 1;">
                        <h3 style="color: #92400e; margin: 0 0 0.5rem 0; font-size: 1.1rem;">
                            ⚠️ Maintenance Mode Active
                        </h3>
                        <p style="color: #92400e; margin: 0; font-size: 0.9rem;">
                            <?php echo htmlspecialchars($maintenance_status['message']); ?>
                            <?php if ($maintenance_status['end_time']): ?>
                                <br><small>Estimated end: <?php echo date('Y-m-d H:i:s', $maintenance_status['end_time']); ?></small>
                            <?php endif; ?>
                        </p>
                    </div>
                    <div>
                        <a href="settings.php" 
                           class="btn" style="
                               background: #10b981;
                               color: white;
                               padding: 0.5rem 1rem;
                               text-decoration: none;
                               border-radius: 5px;
                               font-size: 0.9rem;
                           ">
                            <i class="fas fa-cog"></i> Manage in Settings
                        </a>
                    </div>
                </div>
                <?php else: ?>
                <div class="maintenance-controls" style="
                    background: #f8fafc;
                    border: 1px solid #e2e8f0;
                    border-radius: 10px;
                    padding: 1rem 1.5rem;
                    margin: 1.5rem 0;
                    display: flex;
                    align-items: center;
                    justify-content: space-between;
                ">
                    <div>
                        <h4 style="margin: 0 0 0.25rem 0; color: #334155;">
                            <i class="fas fa-shield-alt"></i> Maintenance Mode
                        </h4>
                        <p style="margin: 0; color: #64748b; font-size: 0.9rem;">
                            Site is currently operational. Enable maintenance mode for updates.
                        </p>
                    </div>
                    <div style="display: flex; gap: 0.5rem;">
                        <a href="settings.php" 
                           class="btn" style="
                               background: #f59e0b;
                               color: white;
                               padding: 0.5rem 1rem;
                               text-decoration: none;
                               border-radius: 5px;
                               font-size: 0.9rem;
                           ">
                            <i class="fas fa-tools"></i> Enable Maintenance
                        </a>
                        <a href="settings.php" 
                           class="btn" style="
                               background: #6b7280;
                               color: white;
                               padding: 0.5rem 1rem;
                               text-decoration: none;
                               border-radius: 5px;
                               font-size: 0.9rem;
                           ">
                            <i class="fas fa-cog"></i> Settings
                        </a>
                        <a href="special-access.php" 
                           class="btn" style="
                               background: #8b5cf6;
                               color: white;
                               padding: 0.5rem 1rem;
                               text-decoration: none;
                               border-radius: 5px;
                               font-size: 0.9rem;
                           ">
                            <i class="fas fa-key"></i> Special Access Links
                        </a>
                    </div>
                </div>
                <?php endif; ?>

                <div class="view-toggle">
                    <a href="dashboard.php" class="btn btn-primary">
                        <i class="fas fa-tachometer-alt"></i>
                        Dashboard Summary
                    </a>
                    <?php if ($stats['products'] > 0): ?>
                        <a href="?show=products" class="btn btn-success">
                            <i class="fas fa-th-large"></i>
                            All Products
                        </a>
                    <?php endif; ?>
                </div>

                <?php if ($showAll): ?>
                    <div class="dashboard-section">
                        <div class="section-header">
                            <h2>All Products (<?php echo $stats['products']; ?> total)</h2>
                            <a href="products.php?action=add" class="btn btn-primary">
                                <i class="fas fa-plus"></i>
                                Add New Product
                            </a>
                        </div>
                        
                        <div class="section-content">
                            <?php if (!empty($products)): ?>
                                <div class="product-grid">
                                    <?php foreach ($products as $product): ?>
                                        <div class="product-card">
                                            <?php if (!empty($product['main_image']) && file_exists("../uploads/products/" . $product['main_image'])): ?>
                                                <img src="../uploads/products/<?php echo htmlspecialchars($product['main_image']); ?>" 
                                                     alt="<?php echo htmlspecialchars($product['name']); ?>" 
                                                     class="product-image">
                                            <?php else: ?>
                                                <div class="no-image">
                                                    <i class="fas fa-image fa-3x"></i>
                                                </div>
                                            <?php endif; ?>
                                            
                                            <div class="product-info">
                                                <h4><?php echo htmlspecialchars($product['name']); ?></h4>
                                                <div class="product-meta">
                                                    <strong>Brand:</strong> <?php echo htmlspecialchars($product['brand_name']); ?>
                                                </div>
                                                <div class="product-meta">
                                                    <strong>Category:</strong> <?php echo htmlspecialchars($product['category_name']); ?>
                                                </div>
                                                <div class="product-price">
                                                    ₹<?php echo number_format($product['price'], 2); ?>
                                                </div>
                                                <div class="product-meta">
                                                    <strong>Added:</strong> <?php echo date('M d, Y', strtotime($product['created_at'])); ?>
                                                </div>
                                                
                                                <?php if ($product['is_featured']): ?>
                                                    <span style="background: linear-gradient(135deg, #28a745, #20c997); color: white; padding: 4px 12px; border-radius: 20px; font-size: 12px; font-weight: 500; display: inline-block; margin: 8px 0;">
                                                        <i class="fas fa-star"></i> Featured
                                                    </span>
                                                <?php endif; ?>
                                                
                                                <div style="margin-top: 1rem;">
                                                    <a href="products.php?action=edit&id=<?php echo $product['id']; ?>" 
                                                       class="btn btn-outline" style="font-size: 0.8rem; padding: 0.5rem 1rem;">
                                                        <i class="fas fa-edit"></i> Edit Product
                                                    </a>
                                                </div>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>

                                <?php if (isset($totalPages) && $totalPages > 1): ?>
                                    <div class="pagination">
                                        <?php if ($page > 1): ?>
                                            <a href="?show=products&page=<?php echo $page - 1; ?>">
                                                <i class="fas fa-chevron-left"></i> Previous
                                            </a>
                                        <?php endif; ?>

                                        <?php for ($i = max(1, $page - 2); $i <= min($totalPages, $page + 2); $i++): ?>
                                            <?php if ($i == $page): ?>
                                                <span class="current"><?php echo $i; ?></span>
                                            <?php else: ?>
                                                <a href="?show=products&page=<?php echo $i; ?>"><?php echo $i; ?></a>
                                            <?php endif; ?>
                                        <?php endfor; ?>

                                        <?php if ($page < $totalPages): ?>
                                            <a href="?show=products&page=<?php echo $page + 1; ?>">
                                                Next <i class="fas fa-chevron-right"></i>
                                            </a>
                                        <?php endif; ?>
                                    </div>
                                <?php endif; ?>
                            <?php else: ?>
                                <div class="empty-state">
                                    <i class="fas fa-search"></i>
                                    <h3>No Products Found</h3>
                                    <p>Unable to load products at this time.</p>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php else: ?>
                    <div class="dashboard-section">
                        <div class="section-header">
                            <h2>Recent Products</h2>
                            <?php if ($stats['products'] > 0): ?>
                                <a href="?show=products" class="btn btn-outline">
                                    <i class="fas fa-eye"></i> View All Products
                                </a>
                            <?php endif; ?>
                        </div>
                        
                        <div class="section-content">
                            <?php if (!empty($products)): ?>
                                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: 1.5rem;">
                                    <?php foreach ($products as $product): ?>
                                        <div class="product-card">
                                            <?php if (!empty($product['main_image']) && file_exists("../uploads/products/" . $product['main_image'])): ?>
                                                <img src="../uploads/products/<?php echo htmlspecialchars($product['main_image']); ?>" 
                                                     alt="<?php echo htmlspecialchars($product['name']); ?>" 
                                                     class="product-image">
                                            <?php else: ?>
                                                <div class="no-image">
                                                    <i class="fas fa-image fa-2x"></i>
                                                </div>
                                            <?php endif; ?>
                                            
                                            <div class="product-info">
                                                <h4><?php echo htmlspecialchars($product['name']); ?></h4>
                                                <div class="product-meta"><?php echo htmlspecialchars($product['brand_name']); ?></div>
                                                <div class="product-price">₹<?php echo number_format($product['price'], 2); ?></div>
                                                
                                                <a href="products.php?action=edit&id=<?php echo $product['id']; ?>" 
                                                   class="btn btn-outline" style="font-size: 0.8rem; padding: 0.5rem 1rem; margin-top: 0.5rem;">
                                                    <i class="fas fa-edit"></i> Edit
                                                </a>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            <?php elseif ($stats['products'] == 0): ?>
                                <div class="empty-state">
                                    <i class="fas fa-box"></i>
                                    <h3>No Products Found</h3>
                                    <p>Get started by adding your first product to the store</p>
                                    <a href="products.php?action=add" class="btn btn-primary" style="margin-top: 1rem;">
                                        <i class="fas fa-plus"></i> Add First Product
                                    </a>
                                </div>
                            <?php else: ?>
                                <div class="empty-state">
                                    <i class="fas fa-exclamation-triangle"></i>
                                    <h3>Unable to Load Products</h3>
                                    <p>There was an error loading recent products. Please check the database connection.</p>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>

                    <div class="dashboard-section">
                        <div class="section-header">
                            <h2>Quick Actions</h2>
                        </div>
                        
                        <div class="section-content">
                            <div class="quick-actions">
                                <a href="products.php?action=add" class="action-card primary">
                                    <i class="fas fa-plus-circle action-icon"></i>
                                    <div style="font-weight: 600;">Add New Product</div>
                                    <small style="color: #718096; margin-top: 0.5rem; display: block;">Create a new product listing</small>
                                </a>
                                
                                <a href="categories.php?action=add" class="action-card success">
                                    <i class="fas fa-tags action-icon"></i>
                                    <div style="font-weight: 600;">Add Category</div>
                                    <small style="color: #718096; margin-top: 0.5rem; display: block;">Organize products by category</small>
                                </a>
                                
                                <a href="brands.php?action=add" class="action-card warning">
                                    <i class="fas fa-award action-icon"></i>
                                    <div style="font-weight: 600;">Add Brand</div>
                                    <small style="color: #718096; margin-top: 0.5rem; display: block;">Manage product brands</small>
                                </a>
                                
                                <a href="contacts.php" class="action-card info">
                                    <i class="fas fa-envelope action-icon"></i>
                                    <div style="font-weight: 600;">View Contact Messages</div>
                                    <small style="color: #718096; margin-top: 0.5rem; display: block;">Review customer inquiries</small>
                                </a>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </main>
    </div>
</body>
</html>