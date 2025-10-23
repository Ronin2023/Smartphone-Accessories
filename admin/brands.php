<?php
session_start();
require_once '../includes/config.php';
require_once '../includes/db_connect.php';
require_once '../includes/functions.php';

// Check if admin or editor is logged in
if (!isLoggedIn() || !hasAdminAccess()) {
    redirect('index');
    exit();
}

$pdo = getDB();
$message = '';
$error = '';
$action = $_GET['action'] ?? 'list';

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $post_action = $_POST['action'] ?? '';
    
    switch($post_action) {
        case 'add':
        case 'edit':
            $brand_id = $post_action === 'edit' ? (int)$_POST['brand_id'] : null;
            $name = sanitize($_POST['name']);
            $website = sanitize($_POST['website']);
            $description = sanitize($_POST['description']);
            
            // Handle logo upload
            $logo_url = '';
            if (!empty($_FILES['logo']['name'])) {
                try {
                    $upload_dir = '../uploads/brands/';
                    if (!file_exists($upload_dir)) {
                        mkdir($upload_dir, 0755, true);
                    }
                    
                    $file_extension = strtolower(pathinfo($_FILES['logo']['name'], PATHINFO_EXTENSION));
                    $allowed_types = ['jpg', 'jpeg', 'png', 'webp', 'svg'];
                    
                    if (in_array($file_extension, $allowed_types)) {
                        // Create SEO-friendly filename
                        $slug = generateSlug($name);
                        $filename = $slug . '-logo.' . $file_extension;
                        $target_path = $upload_dir . $filename;
                        
                        // Delete old logo if editing
                        if ($post_action === 'edit') {
                            $stmt = $pdo->prepare("SELECT logo_url FROM brands WHERE id = ?");
                            $stmt->execute([$brand_id]);
                            $existing = $stmt->fetch();
                            if ($existing['logo_url'] && file_exists('../' . $existing['logo_url'])) {
                                unlink('../' . $existing['logo_url']);
                            }
                        }
                        
                        if (move_uploaded_file($_FILES['logo']['tmp_name'], $target_path)) {
                            $logo_url = 'uploads/brands/' . $filename;
                        }
                    } else {
                        $error = 'Invalid file type. Please upload JPG, PNG, WebP, or SVG files only.';
                    }
                } catch (Exception $e) {
                    $error = 'Error uploading logo: ' . $e->getMessage();
                }
            } else if ($post_action === 'edit') {
                // Keep existing logo if no new one uploaded
                $stmt = $pdo->prepare("SELECT logo_url FROM brands WHERE id = ?");
                $stmt->execute([$brand_id]);
                $existing = $stmt->fetch();
                $logo_url = $existing['logo_url'] ?? '';
            }
            
            if (empty($error)) {
                try {
                    if ($post_action === 'add') {
                        $stmt = $pdo->prepare("
                            INSERT INTO brands (name, logo_url, website, description, created_at) 
                            VALUES (?, ?, ?, ?, NOW())
                        ");
                        $stmt->execute([$name, $logo_url, $website, $description]);
                        $message = 'Brand added successfully!';
                    } else {
                        $stmt = $pdo->prepare("
                            UPDATE brands SET name = ?, logo_url = ?, website = ?, description = ?, updated_at = NOW() 
                            WHERE id = ?
                        ");
                        $stmt->execute([$name, $logo_url, $website, $description, $brand_id]);
                        $message = 'Brand updated successfully!';
                    }
                    $action = 'list';
                } catch (Exception $e) {
                    if (strpos($e->getMessage(), 'Duplicate entry') !== false) {
                        $error = 'A brand with this name already exists.';
                    } else {
                        $error = 'Database error: ' . $e->getMessage();
                    }
                }
            }
            break;
            
        case 'delete':
            $brand_id = (int)$_POST['brand_id'];
            try {
                // Check if brand has products
                $stmt = $pdo->prepare("SELECT COUNT(*) FROM products WHERE brand_id = ?");
                $stmt->execute([$brand_id]);
                $product_count = $stmt->fetchColumn();
                
                if ($product_count > 0) {
                    $error = "Cannot delete brand. It has {$product_count} products associated with it.";
                } else {
                    // Get logo to delete file
                    $stmt = $pdo->prepare("SELECT logo_url FROM brands WHERE id = ?");
                    $stmt->execute([$brand_id]);
                    $brand = $stmt->fetch();
                    
                    // Delete brand from database
                    $stmt = $pdo->prepare("DELETE FROM brands WHERE id = ?");
                    $stmt->execute([$brand_id]);
                    
                    // Delete logo file
                    if ($brand['logo_url'] && file_exists('../' . $brand['logo_url'])) {
                        unlink('../' . $brand['logo_url']);
                    }
                    
                    $message = 'Brand deleted successfully!';
                }
            } catch (Exception $e) {
                $error = 'Error deleting brand: ' . $e->getMessage();
            }
            break;
    }
}

// Get brands list with pagination
$page = max(1, (int)($_GET['page'] ?? 1));
$per_page = 12;
$offset = ($page - 1) * $per_page;

$search = $_GET['search'] ?? '';
$where_clause = '';
$params = [];

if (!empty($search)) {
    $where_clause = "WHERE name LIKE ? OR description LIKE ? OR website LIKE ?";
    $search_param = "%{$search}%";
    $params = [$search_param, $search_param, $search_param];
}

// Count total brands
$count_sql = "SELECT COUNT(*) FROM brands {$where_clause}";
$count_stmt = $pdo->prepare($count_sql);
$count_stmt->execute($params);
$total_brands = $count_stmt->fetchColumn();
$total_pages = ceil($total_brands / $per_page);

// Get brands with product count
$sql = "
    SELECT b.*, COUNT(p.id) as product_count 
    FROM brands b 
    LEFT JOIN products p ON b.id = p.brand_id 
    {$where_clause}
    GROUP BY b.id 
    ORDER BY b.name ASC 
    LIMIT {$per_page} OFFSET {$offset}
";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$brands = $stmt->fetchAll();

// Get specific brand for editing
$edit_brand = null;
if ($action === 'edit' && isset($_GET['id'])) {
    $stmt = $pdo->prepare("SELECT * FROM brands WHERE id = ?");
    $stmt->execute([(int)$_GET['id']]);
    $edit_brand = $stmt->fetch();
    if (!$edit_brand) {
        $action = 'list';
        $error = 'Brand not found!';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Brands Management - TechCompare Admin</title>
    <link rel="stylesheet" href="../css/style.css">
    <link rel="stylesheet" href="../css/admin.css">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        .form-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 1rem;
            margin-bottom: 1rem;
        }
        .form-grid-full {
            grid-column: 1 / -1;
        }
        .logo-preview {
            max-width: 120px;
            max-height: 80px;
            object-fit: contain;
            border-radius: 4px;
            border: 1px solid #ddd;
            padding: 0.5rem;
            background: white;
        }
        .brand-logo {
            width: 60px;
            height: 40px;
            object-fit: contain;
            border-radius: 4px;
            background: white;
            padding: 0.25rem;
            border: 1px solid #eee;
        }
        .no-logo {
            width: 60px;
            height: 40px;
            background: #f0f0f0;
            border-radius: 4px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #666;
            font-size: 0.7rem;
            text-align: center;
            border: 1px solid #eee;
        }
        .brand-info {
            display: flex;
            align-items: center;
            gap: 1rem;
        }
        .brand-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
            gap: 1rem;
        }
        .brand-card {
            background: white;
            border-radius: 8px;
            padding: 1.5rem;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            text-align: center;
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }
        .brand-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
        }
        .brand-card .brand-logo {
            width: 80px;
            height: 60px;
            margin: 0 auto 1rem auto;
            display: block;
        }
        .brand-card .no-logo {
            width: 80px;
            height: 60px;
            margin: 0 auto 1rem auto;
        }
        .brand-card h4 {
            margin: 0 0 0.5rem 0;
            color: #333;
        }
        .brand-card .website {
            color: #4361ee;
            text-decoration: none;
            font-size: 0.9rem;
            margin-bottom: 1rem;
            display: block;
        }
        .brand-card .description {
            color: #666;
            font-size: 0.9rem;
            line-height: 1.4;
            margin-bottom: 1rem;
            min-height: 3em;
        }
        .brand-card .actions {
            display: flex;
            gap: 0.5rem;
            justify-content: center;
        }
        .actions {
            display: flex;
            gap: 0.5rem;
        }
        .btn-sm {
            padding: 0.25rem 0.5rem;
            font-size: 0.875rem;
        }
        .table-responsive {
            overflow-x: auto;
        }
        .pagination {
            display: flex;
            justify-content: center;
            align-items: center;
            gap: 0.5rem;
            margin: 2rem 0;
        }
        .pagination a, .pagination span {
            padding: 0.5rem 1rem;
            text-decoration: none;
            border: 1px solid #ddd;
            border-radius: 4px;
            color: #4361ee;
        }
        .pagination .current {
            background: #4361ee;
            color: white;
        }
        .filters {
            background: white;
            padding: 1rem;
            border-radius: 8px;
            margin-bottom: 1rem;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .filters-grid {
            display: grid;
            grid-template-columns: 1fr auto auto;
            gap: 1rem;
            align-items: end;
        }
        .view-toggle {
            display: flex;
            gap: 0.5rem;
        }
        .view-toggle .btn {
            padding: 0.5rem 1rem;
        }
        .stats {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1rem;
            margin-bottom: 1rem;
        }
        .stat-card {
            background: white;
            padding: 1.5rem;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            text-align: center;
        }
        .stat-number {
            font-size: 2rem;
            font-weight: bold;
            color: #4361ee;
        }
        .stat-label {
            color: #666;
            font-size: 0.9rem;
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
                    <li class="nav-item">
                        <a href="dashboard" class="nav-link">
                            <i class="fas fa-tachometer-alt"></i>
                            <span>Dashboard</span>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="products" class="nav-link">
                            <i class="fas fa-box"></i>
                            <span>Products</span>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="categories" class="nav-link">
                            <i class="fas fa-tags"></i>
                            <span>Categories</span>
                        </a>
                    </li>
                    <li class="nav-item active">
                        <a href="brands" class="nav-link">
                            <i class="fas fa-award"></i>
                            <span>Brands</span>
                        </a>
                    </li>
                    <?php if (isAdmin()): ?>
                    <li class="nav-item">
                        <a href="users" class="nav-link">
                            <i class="fas fa-users"></i>
                            <span>Users</span>
                        </a>
                    </li>
                    <?php endif; ?>
                    <li class="nav-item">
                        <a href="contacts" class="nav-link">
                            <i class="fas fa-envelope"></i>
                            <span>Contact Messages</span>
                        </a>
                    </li>
                    <?php if (isAdmin()): ?>
                    <li class="nav-item">
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
                        <a href="logout" class="nav-link" style="color: #dc3545 !important;">
                            <i class="fas fa-sign-out-alt"></i>
                            <span>Logout</span>
                        </a>
                    </li>
                </ul>
            </nav>
        </aside>

        <main class="admin-main">
            <div class="admin-header">
                <h1 class="page-title">
                    <?php 
                    switch($action) {
                        case 'add': echo 'Add New Brand'; break;
                        case 'edit': echo 'Edit Brand'; break;
                        default: echo 'Brands Management'; break;
                    }
                    ?>
                </h1>
                <div class="header-actions">
                    <?php if ($action === 'list'): ?>
                        <a href="?action=add" class="btn btn-primary">
                            <i class="fas fa-plus"></i> Add New Brand
                        </a>
                    <?php else: ?>
                        <a href="brands" class="btn btn-secondary">
                            <i class="fas fa-arrow-left"></i> Back to Brands
                        </a>
                    <?php endif; ?>
                </div>
            </div>

            <div class="admin-content">
                <?php if (!empty($message)): ?>
                    <div class="alert alert-success">
                        <i class="fas fa-check-circle"></i>
                        <?php echo htmlspecialchars($message); ?>
                    </div>
                <?php endif; ?>

                <?php if (!empty($error)): ?>
                    <div class="alert alert-error">
                        <i class="fas fa-exclamation-triangle"></i>
                        <?php echo htmlspecialchars($error); ?>
                    </div>
                <?php endif; ?>

                <?php if ($action === 'add' || $action === 'edit'): ?>
                    <!-- Add/Edit Brand Form -->
                    <div class="card">
                        <div class="card-header">
                            <h3><?php echo $action === 'add' ? 'Add New Brand' : 'Edit Brand'; ?></h3>
                        </div>
                        <div class="card-body">
                            <form method="POST" enctype="multipart/form-data">
                                <input type="hidden" name="action" value="<?php echo $action; ?>">
                                <?php if ($action === 'edit'): ?>
                                    <input type="hidden" name="brand_id" value="<?php echo $edit_brand['id']; ?>">
                                <?php endif; ?>

                                <div class="form-grid">
                                    <div class="form-group">
                                        <label for="name">Brand Name *</label>
                                        <input type="text" id="name" name="name" required 
                                               value="<?php echo htmlspecialchars($edit_brand['name'] ?? ''); ?>">
                                    </div>

                                    <div class="form-group">
                                        <label for="website">Website URL</label>
                                        <input type="url" id="website" name="website" 
                                               value="<?php echo htmlspecialchars($edit_brand['website'] ?? ''); ?>"
                                               placeholder="https://example.com">
                                    </div>
                                </div>

                                <div class="form-group form-grid-full">
                                    <label for="description">Description</label>
                                    <textarea id="description" name="description" rows="4"><?php echo htmlspecialchars($edit_brand['description'] ?? ''); ?></textarea>
                                </div>

                                <div class="form-group form-grid-full">
                                    <label for="logo">Brand Logo *</label>
                                    <input type="file" id="logo" name="logo" accept="image/*" <?php echo $action === 'add' ? 'required' : ''; ?>>
                                    <small>Upload JPG, PNG, WebP, or SVG files. Recommended size: 200x100px</small>
                                    <?php if ($action === 'edit' && !empty($edit_brand['logo_url'])): ?>
                                        <div style="margin-top: 1rem;">
                                            <img src="../<?php echo htmlspecialchars($edit_brand['logo_url']); ?>" 
                                                 alt="Current logo" class="logo-preview">
                                            <br><small>Current logo</small>
                                        </div>
                                    <?php endif; ?>
                                </div>

                                <div class="form-actions">
                                    <button type="submit" class="btn btn-primary">
                                        <i class="fas fa-save"></i>
                                        <?php echo $action === 'add' ? 'Add Brand' : 'Update Brand'; ?>
                                    </button>
                                    <a href="brands" class="btn btn-secondary">
                                        <i class="fas fa-times"></i> Cancel
                                    </a>
                                </div>
                            </form>
                        </div>
                    </div>

                <?php else: ?>
                    <!-- Brands List -->
                    <div class="stats">
                        <div class="stat-card">
                            <div class="stat-number"><?php echo $total_brands; ?></div>
                            <div class="stat-label">Total Brands</div>
                        </div>
                        <div class="stat-card">
                            <div class="stat-number">
                                <?php 
                                $active_brands = array_filter($brands, function($brand) { return $brand['product_count'] > 0; });
                                echo count($active_brands); 
                                ?>
                            </div>
                            <div class="stat-label">Active Brands</div>
                        </div>
                        <div class="stat-card">
                            <div class="stat-number">
                                <?php 
                                $total_products = array_sum(array_column($brands, 'product_count'));
                                echo $total_products; 
                                ?>
                            </div>
                            <div class="stat-label">Total Products</div>
                        </div>
                    </div>

                    <div class="filters">
                        <form method="GET" class="filters-grid">
                            <div class="form-group">
                                <input type="text" name="search" placeholder="Search brands..." 
                                       value="<?php echo htmlspecialchars($search); ?>">
                            </div>
                            <div class="view-toggle">
                                <a href="?view=grid<?php echo $search ? '&search=' . urlencode($search) : ''; ?>" 
                                   class="btn <?php echo ($_GET['view'] ?? 'grid') === 'grid' ? 'btn-primary' : 'btn-secondary'; ?>">
                                    <i class="fas fa-th"></i> Grid
                                </a>
                                <a href="?view=list<?php echo $search ? '&search=' . urlencode($search) : ''; ?>" 
                                   class="btn <?php echo ($_GET['view'] ?? 'grid') === 'list' ? 'btn-primary' : 'btn-secondary'; ?>">
                                    <i class="fas fa-list"></i> List
                                </a>
                            </div>
                            <div>
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-search"></i> Search
                                </button>
                            </div>
                        </form>
                    </div>

                    <?php if (($_GET['view'] ?? 'grid') === 'grid'): ?>
                        <!-- Grid View -->
                        <div class="brand-grid">
                            <?php if (empty($brands)): ?>
                                <div style="grid-column: 1 / -1; text-align: center; padding: 2rem;">
                                    <p>No brands found</p>
                                </div>
                            <?php else: ?>
                                <?php foreach ($brands as $brand): ?>
                                    <div class="brand-card">
                                        <?php if (!empty($brand['logo_url'])): ?>
                                            <img src="../<?php echo htmlspecialchars($brand['logo_url']); ?>" 
                                                 alt="<?php echo htmlspecialchars($brand['name']); ?>" 
                                                 class="brand-logo">
                                        <?php else: ?>
                                            <div class="no-logo">No Logo</div>
                                        <?php endif; ?>
                                        
                                        <h4><?php echo htmlspecialchars($brand['name']); ?></h4>
                                        
                                        <?php if ($brand['website']): ?>
                                            <a href="<?php echo htmlspecialchars($brand['website']); ?>" 
                                               target="_blank" class="website">
                                                <i class="fas fa-external-link-alt"></i> Website
                                            </a>
                                        <?php endif; ?>
                                        
                                        <div class="description">
                                            <?php 
                                            $desc = $brand['description'];
                                            echo htmlspecialchars(strlen($desc) > 100 ? substr($desc, 0, 100) . '...' : $desc); 
                                            ?>
                                        </div>
                                        
                                        <div style="margin-bottom: 1rem;">
                                            <span class="badge badge-info"><?php echo $brand['product_count']; ?> products</span>
                                        </div>
                                        
                                        <div class="actions">
                                            <a href="?action=edit&id=<?php echo $brand['id']; ?>" 
                                               class="btn btn-sm btn-primary" title="Edit">
                                                <i class="fas fa-edit"></i> Edit
                                            </a>
                                            <?php if ($brand['product_count'] == 0): ?>
                                                <form method="POST" style="display: inline;" 
                                                      onsubmit="return confirm('Are you sure you want to delete this brand?');">
                                                    <input type="hidden" name="action" value="delete">
                                                    <input type="hidden" name="brand_id" value="<?php echo $brand['id']; ?>">
                                                    <button type="submit" class="btn btn-sm btn-danger" title="Delete">
                                                        <i class="fas fa-trash"></i> Delete
                                                    </button>
                                                </form>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                    <?php else: ?>
                        <!-- List View -->
                        <div class="card">
                            <div class="card-header">
                                <h3>Brands (<?php echo $total_brands; ?> total)</h3>
                            </div>
                            <div class="card-body">
                                <div class="table-responsive">
                                    <table class="table">
                                        <thead>
                                            <tr>
                                                <th>Brand</th>
                                                <th>Website</th>
                                                <th>Description</th>
                                                <th>Products</th>
                                                <th>Created</th>
                                                <th>Actions</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php if (empty($brands)): ?>
                                                <tr>
                                                    <td colspan="6" class="text-center">No brands found</td>
                                                </tr>
                                            <?php else: ?>
                                                <?php foreach ($brands as $brand): ?>
                                                    <tr>
                                                        <td>
                                                            <div class="brand-info">
                                                                <?php if (!empty($brand['logo_url'])): ?>
                                                                    <img src="../<?php echo htmlspecialchars($brand['logo_url']); ?>" 
                                                                         alt="<?php echo htmlspecialchars($brand['name']); ?>" 
                                                                         class="brand-logo">
                                                                <?php else: ?>
                                                                    <div class="no-logo">No Logo</div>
                                                                <?php endif; ?>
                                                                <strong><?php echo htmlspecialchars($brand['name']); ?></strong>
                                                            </div>
                                                        </td>
                                                        <td>
                                                            <?php if ($brand['website']): ?>
                                                                <a href="<?php echo htmlspecialchars($brand['website']); ?>" 
                                                                   target="_blank" class="text-primary">
                                                                    <i class="fas fa-external-link-alt"></i>
                                                                </a>
                                                            <?php else: ?>
                                                                -
                                                            <?php endif; ?>
                                                        </td>
                                                        <td>
                                                            <?php 
                                                            $desc = $brand['description'];
                                                            echo htmlspecialchars(strlen($desc) > 60 ? substr($desc, 0, 60) . '...' : $desc); 
                                                            ?>
                                                        </td>
                                                        <td>
                                                            <span class="badge badge-info"><?php echo $brand['product_count']; ?></span>
                                                        </td>
                                                        <td><?php echo date('M d, Y', strtotime($brand['created_at'])); ?></td>
                                                        <td>
                                                            <div class="actions">
                                                                <a href="?action=edit&id=<?php echo $brand['id']; ?>" 
                                                                   class="btn btn-sm btn-primary" title="Edit">
                                                                    <i class="fas fa-edit"></i>
                                                                </a>
                                                                <?php if ($brand['product_count'] == 0): ?>
                                                                    <form method="POST" style="display: inline;" 
                                                                          onsubmit="return confirm('Are you sure you want to delete this brand?');">
                                                                        <input type="hidden" name="action" value="delete">
                                                                        <input type="hidden" name="brand_id" value="<?php echo $brand['id']; ?>">
                                                                        <button type="submit" class="btn btn-sm btn-danger" title="Delete">
                                                                            <i class="fas fa-trash"></i>
                                                                        </button>
                                                                    </form>
                                                                <?php else: ?>
                                                                    <button class="btn btn-sm btn-danger" disabled title="Cannot delete - has products">
                                                                        <i class="fas fa-trash"></i>
                                                                    </button>
                                                                <?php endif; ?>
                                                            </div>
                                                        </td>
                                                    </tr>
                                                <?php endforeach; ?>
                                            <?php endif; ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    <?php endif; ?>

                    <?php if ($total_pages > 1): ?>
                        <div class="pagination">
                            <?php if ($page > 1): ?>
                                <a href="?page=<?php echo $page - 1; ?>&search=<?php echo urlencode($search); ?>&view=<?php echo $_GET['view'] ?? 'grid'; ?>">
                                    <i class="fas fa-chevron-left"></i> Previous
                                </a>
                            <?php endif; ?>

                            <?php for ($i = max(1, $page - 2); $i <= min($total_pages, $page + 2); $i++): ?>
                                <?php if ($i == $page): ?>
                                    <span class="current"><?php echo $i; ?></span>
                                <?php else: ?>
                                    <a href="?page=<?php echo $i; ?>&search=<?php echo urlencode($search); ?>&view=<?php echo $_GET['view'] ?? 'grid'; ?>">
                                        <?php echo $i; ?>
                                    </a>
                                <?php endif; ?>
                            <?php endfor; ?>

                            <?php if ($page < $total_pages): ?>
                                <a href="?page=<?php echo $page + 1; ?>&search=<?php echo urlencode($search); ?>&view=<?php echo $_GET['view'] ?? 'grid'; ?>">
                                    Next <i class="fas fa-chevron-right"></i>
                                </a>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>
                <?php endif; ?>
            </div>
        </main>
    </div>

    <script>
        // Preview logo before upload
        document.getElementById('logo')?.addEventListener('change', function(e) {
            const file = e.target.files[0];
            if (file) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    let preview = document.querySelector('.logo-upload-preview');
                    if (!preview) {
                        preview = document.createElement('img');
                        preview.className = 'logo-preview logo-upload-preview';
                        preview.style.marginTop = '1rem';
                        preview.style.display = 'block';
                        document.getElementById('logo').parentNode.appendChild(preview);
                    }
                    preview.src = e.target.result;
                };
                reader.readAsDataURL(file);
            }
        });
    </script>
</body>
</html>
