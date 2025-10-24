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
            $product_id = $post_action === 'edit' ? (int)$_POST['product_id'] : null;
            $name = sanitize($_POST['name']);
            $brand_id = !empty($_POST['brand_id']) ? (int)$_POST['brand_id'] : null;
            $category_id = !empty($_POST['category_id']) ? (int)$_POST['category_id'] : null;
            $model = sanitize($_POST['model']);
            $description = sanitize($_POST['description']);
            $price = (float)$_POST['price'];
            $discount_price = !empty($_POST['discount_price']) ? (float)$_POST['discount_price'] : null;
            $availability_status = $_POST['availability_status'];
            $is_featured = isset($_POST['is_featured']) ? 1 : 0;
            
            // Process specifications JSON
            $specifications = [];
            if (!empty($_POST['spec_keys']) && !empty($_POST['spec_values'])) {
                foreach ($_POST['spec_keys'] as $index => $key) {
                    if (!empty($key) && !empty($_POST['spec_values'][$index])) {
                        $specifications[sanitize($key)] = sanitize($_POST['spec_values'][$index]);
                    }
                }
            }
            $specifications_json = json_encode($specifications);
            
            // Handle image upload
            $main_image = '';
            $gallery_images = [];
            
            // Main image upload
            if (!empty($_FILES['main_image']['name'])) {
                try {
                    $upload_dir = '../uploads/products/';
                    if (!file_exists($upload_dir)) {
                        mkdir($upload_dir, 0755, true);
                    }
                    
                    $file_extension = strtolower(pathinfo($_FILES['main_image']['name'], PATHINFO_EXTENSION));
                    $allowed_types = ['jpg', 'jpeg', 'png', 'webp'];
                    
                    if (in_array($file_extension, $allowed_types)) {
                        // Create SEO-friendly filename
                        $slug = generateSlug($name . '-' . $model);
                        $filename = $slug . '-main.' . $file_extension;
                        $target_path = $upload_dir . $filename;
                        
                        if (move_uploaded_file($_FILES['main_image']['tmp_name'], $target_path)) {
                            $main_image = 'uploads/products/' . $filename;
                        }
                    }
                } catch (Exception $e) {
                    $error = 'Error uploading main image: ' . $e->getMessage();
                }
            } else if ($post_action === 'edit') {
                // Keep existing main image if no new one uploaded
                $stmt = $pdo->prepare("SELECT main_image FROM products WHERE id = ?");
                $stmt->execute([$product_id]);
                $existing = $stmt->fetch();
                $main_image = $existing['main_image'] ?? '';
            }
            
            // Gallery images upload
            if (!empty($_FILES['gallery_images']['name'][0])) {
                try {
                    foreach ($_FILES['gallery_images']['name'] as $index => $filename) {
                        if (!empty($filename)) {
                            $file_extension = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
                            if (in_array($file_extension, ['jpg', 'jpeg', 'png', 'webp'])) {
                                $slug = generateSlug($name . '-' . $model);
                                $gallery_filename = $slug . '-gallery-' . ($index + 1) . '.' . $file_extension;
                                $target_path = $upload_dir . $gallery_filename;
                                
                                if (move_uploaded_file($_FILES['gallery_images']['tmp_name'][$index], $target_path)) {
                                    $gallery_images[] = 'uploads/products/' . $gallery_filename;
                                }
                            }
                        }
                    }
                } catch (Exception $e) {
                    $error = 'Error uploading gallery images: ' . $e->getMessage();
                }
            } else if ($post_action === 'edit') {
                // Keep existing gallery images if no new ones uploaded
                $stmt = $pdo->prepare("SELECT gallery_images FROM products WHERE id = ?");
                $stmt->execute([$product_id]);
                $existing = $stmt->fetch();
                $gallery_images = json_decode($existing['gallery_images'] ?? '[]', true);
            }
            
            $gallery_images_json = json_encode($gallery_images);
            
            if (empty($error)) {
                try {
                    if ($post_action === 'add') {
                        $stmt = $pdo->prepare("
                            INSERT INTO products (name, brand_id, category_id, model, description, specifications, 
                                                price, discount_price, main_image, gallery_images, 
                                                availability_status, is_featured, created_at) 
                            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())
                        ");
                        $stmt->execute([
                            $name, $brand_id, $category_id, $model, $description, $specifications_json,
                            $price, $discount_price, $main_image, $gallery_images_json,
                            $availability_status, $is_featured
                        ]);
                        $message = 'Product added successfully!';
                    } else {
                        $stmt = $pdo->prepare("
                            UPDATE products SET name = ?, brand_id = ?, category_id = ?, model = ?, 
                                              description = ?, specifications = ?, price = ?, discount_price = ?, 
                                              main_image = ?, gallery_images = ?, availability_status = ?, 
                                              is_featured = ?, updated_at = NOW() 
                            WHERE id = ?
                        ");
                        $stmt->execute([
                            $name, $brand_id, $category_id, $model, $description, $specifications_json,
                            $price, $discount_price, $main_image, $gallery_images_json,
                            $availability_status, $is_featured, $product_id
                        ]);
                        $message = 'Product updated successfully!';
                    }
                    $action = 'list';
                } catch (Exception $e) {
                    $error = 'Database error: ' . $e->getMessage();
                }
            }
            break;
            
        case 'delete':
            $product_id = (int)$_POST['product_id'];
            try {
                // Get image paths to delete files
                $stmt = $pdo->prepare("SELECT main_image, gallery_images FROM products WHERE id = ?");
                $stmt->execute([$product_id]);
                $product = $stmt->fetch();
                
                // Delete product from database
                $stmt = $pdo->prepare("DELETE FROM products WHERE id = ?");
                $stmt->execute([$product_id]);
                
                // Delete image files
                if ($product['main_image'] && file_exists('../' . $product['main_image'])) {
                    unlink('../' . $product['main_image']);
                }
                if ($product['gallery_images']) {
                    $gallery = json_decode($product['gallery_images'], true);
                    foreach ($gallery as $image_path) {
                        if (file_exists('../' . $image_path)) {
                            unlink('../' . $image_path);
                        }
                    }
                }
                
                $message = 'Product deleted successfully!';
            } catch (Exception $e) {
                $error = 'Error deleting product: ' . $e->getMessage();
            }
            break;
    }
}

// Get products list with pagination
$page = max(1, (int)($_GET['page'] ?? 1));
$per_page = 10;
$offset = ($page - 1) * $per_page;

$search = $_GET['search'] ?? '';
$category_filter = $_GET['category'] ?? '';
$brand_filter = $_GET['brand'] ?? '';

$where_conditions = [];
$params = [];

if (!empty($search)) {
    $where_conditions[] = "(p.name LIKE ? OR p.model LIKE ? OR p.description LIKE ?)";
    $search_param = "%{$search}%";
    $params = array_merge($params, [$search_param, $search_param, $search_param]);
}

if (!empty($category_filter)) {
    $where_conditions[] = "p.category_id = ?";
    $params[] = $category_filter;
}

if (!empty($brand_filter)) {
    $where_conditions[] = "p.brand_id = ?";
    $params[] = $brand_filter;
}

$where_clause = $where_conditions ? 'WHERE ' . implode(' AND ', $where_conditions) : '';

// Count total products
$count_sql = "
    SELECT COUNT(*) 
    FROM products p 
    LEFT JOIN brands b ON p.brand_id = b.id 
    LEFT JOIN categories c ON p.category_id = c.id 
    {$where_clause}
";
$count_stmt = $pdo->prepare($count_sql);
$count_stmt->execute($params);
$total_products = $count_stmt->fetchColumn();
$total_pages = ceil($total_products / $per_page);

// Get products
$sql = "
    SELECT p.*, b.name as brand_name, c.name as category_name 
    FROM products p 
    LEFT JOIN brands b ON p.brand_id = b.id 
    LEFT JOIN categories c ON p.category_id = c.id 
    {$where_clause}
    ORDER BY p.created_at DESC 
    LIMIT {$per_page} OFFSET {$offset}
";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$products = $stmt->fetchAll();

// Get brands and categories for dropdowns
$brands = $pdo->query("SELECT id, name FROM brands ORDER BY name")->fetchAll();
$categories = $pdo->query("SELECT id, name FROM categories ORDER BY name")->fetchAll();

// Get specific product for editing
$edit_product = null;
if ($action === 'edit' && isset($_GET['id'])) {
    $stmt = $pdo->prepare("SELECT * FROM products WHERE id = ?");
    $stmt->execute([(int)$_GET['id']]);
    $edit_product = $stmt->fetch();
    if (!$edit_product) {
        $action = 'list';
        $error = 'Product not found!';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Products Management - TechCompare Admin</title>
    <link rel="stylesheet" href="../css/style.css">
    <link rel="stylesheet" href="../css/admin.css">
    <link rel="stylesheet" href="../css/admin-dark-mode.css">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <script src="../js/admin-dark-mode.js"></script>
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
        .spec-row {
            display: grid;
            grid-template-columns: 1fr 1fr auto;
            gap: 0.5rem;
            margin-bottom: 0.5rem;
            align-items: center;
        }
        .image-preview {
            max-width: 100px;
            max-height: 100px;
            object-fit: cover;
            border-radius: 4px;
            border: 1px solid #ddd;
        }
        .gallery-preview {
            display: flex;
            gap: 0.5rem;
            flex-wrap: wrap;
            margin-top: 0.5rem;
        }
        .product-image {
            width: 60px;
            height: 60px;
            object-fit: cover;
            border-radius: 4px;
            border: 1px solid #ddd;
        }
        .no-image {
            width: 60px;
            height: 60px;
            background: #f0f0f0;
            border-radius: 4px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #666;
            font-size: 0.8rem;
            text-align: center;
            border: 1px solid #ddd;
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
            grid-template-columns: 2fr 1fr 1fr auto;
            gap: 1rem;
            align-items: end;
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
                    <li class="nav-item active">
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
                    <li class="nav-item">
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
                        case 'add': echo 'Add New Product'; break;
                        case 'edit': echo 'Edit Product'; break;
                        default: echo 'Products Management'; break;
                    }
                    ?>
                </h1>
                <div class="header-actions">
                    <?php if ($action === 'list'): ?>
                        <a href="?action=add" class="btn btn-primary">
                            <i class="fas fa-plus"></i> Add New Product
                        </a>
                    <?php else: ?>
                        <a href="products" class="btn btn-secondary">
                            <i class="fas fa-arrow-left"></i> Back to Products
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
                    <!-- Add/Edit Product Form -->
                    <div class="card">
                        <div class="card-header">
                            <h3><?php echo $action === 'add' ? 'Add New Product' : 'Edit Product'; ?></h3>
                        </div>
                        <div class="card-body">
                            <form method="POST" enctype="multipart/form-data">
                                <input type="hidden" name="action" value="<?php echo $action; ?>">
                                <?php if ($action === 'edit'): ?>
                                    <input type="hidden" name="product_id" value="<?php echo $edit_product['id']; ?>">
                                <?php endif; ?>

                                <div class="form-grid">
                                    <div class="form-group">
                                        <label for="name">Product Name *</label>
                                        <input type="text" id="name" name="name" required 
                                               value="<?php echo htmlspecialchars($edit_product['name'] ?? ''); ?>">
                                    </div>

                                    <div class="form-group">
                                        <label for="model">Model</label>
                                        <input type="text" id="model" name="model" 
                                               value="<?php echo htmlspecialchars($edit_product['model'] ?? ''); ?>">
                                    </div>

                                    <div class="form-group">
                                        <label for="brand_id">Brand</label>
                                        <select id="brand_id" name="brand_id">
                                            <option value="">Select Brand</option>
                                            <?php foreach ($brands as $brand): ?>
                                                <option value="<?php echo $brand['id']; ?>" 
                                                        <?php echo ($edit_product['brand_id'] ?? '') == $brand['id'] ? 'selected' : ''; ?>>
                                                    <?php echo htmlspecialchars($brand['name']); ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>

                                    <div class="form-group">
                                        <label for="category_id">Category</label>
                                        <select id="category_id" name="category_id">
                                            <option value="">Select Category</option>
                                            <?php foreach ($categories as $category): ?>
                                                <option value="<?php echo $category['id']; ?>" 
                                                        <?php echo ($edit_product['category_id'] ?? '') == $category['id'] ? 'selected' : ''; ?>>
                                                    <?php echo htmlspecialchars($category['name']); ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>

                                    <div class="form-group">
                                        <label for="price">Price *</label>
                                        <input type="number" id="price" name="price" step="0.01" required 
                                               value="<?php echo $edit_product['price'] ?? ''; ?>">
                                    </div>

                                    <div class="form-group">
                                        <label for="discount_price">Discount Price</label>
                                        <input type="number" id="discount_price" name="discount_price" step="0.01" 
                                               value="<?php echo $edit_product['discount_price'] ?? ''; ?>">
                                    </div>

                                    <div class="form-group">
                                        <label for="availability_status">Availability</label>
                                        <select id="availability_status" name="availability_status">
                                            <option value="in_stock" <?php echo ($edit_product['availability_status'] ?? 'in_stock') === 'in_stock' ? 'selected' : ''; ?>>In Stock</option>
                                            <option value="out_of_stock" <?php echo ($edit_product['availability_status'] ?? '') === 'out_of_stock' ? 'selected' : ''; ?>>Out of Stock</option>
                                            <option value="pre_order" <?php echo ($edit_product['availability_status'] ?? '') === 'pre_order' ? 'selected' : ''; ?>>Pre Order</option>
                                        </select>
                                    </div>

                                    <div class="form-group">
                                        <label>
                                            <input type="checkbox" name="is_featured" <?php echo ($edit_product['is_featured'] ?? 0) ? 'checked' : ''; ?>>
                                            Featured Product
                                        </label>
                                    </div>
                                </div>

                                <div class="form-group form-grid-full">
                                    <label for="description">Description</label>
                                    <textarea id="description" name="description" rows="4"><?php echo htmlspecialchars($edit_product['description'] ?? ''); ?></textarea>
                                </div>

                                <div class="form-group form-grid-full">
                                    <label for="main_image">Main Product Image *</label>
                                    <input type="file" id="main_image" name="main_image" accept="image/*" <?php echo $action === 'add' ? 'required' : ''; ?>>
                                    <?php if ($action === 'edit' && !empty($edit_product['main_image'])): ?>
                                        <div style="margin-top: 0.5rem;">
                                            <img src="../<?php echo htmlspecialchars($edit_product['main_image']); ?>" alt="Current main image" class="image-preview">
                                            <small>Current main image</small>
                                        </div>
                                    <?php endif; ?>
                                </div>

                                <div class="form-group form-grid-full">
                                    <label for="gallery_images">Gallery Images</label>
                                    <input type="file" id="gallery_images" name="gallery_images[]" accept="image/*" multiple>
                                    <small>You can select multiple images for the product gallery</small>
                                    <?php if ($action === 'edit' && !empty($edit_product['gallery_images'])): ?>
                                        <div class="gallery-preview">
                                            <?php 
                                            $gallery = json_decode($edit_product['gallery_images'], true);
                                            if ($gallery): 
                                                foreach ($gallery as $image): ?>
                                                    <img src="../<?php echo htmlspecialchars($image); ?>" alt="Gallery image" class="image-preview">
                                                <?php endforeach; 
                                            endif; ?>
                                        </div>
                                        <small>Current gallery images</small>
                                    <?php endif; ?>
                                </div>

                                <div class="form-group form-grid-full">
                                    <label>Product Specifications</label>
                                    <div id="specifications">
                                        <?php 
                                        $specs = [];
                                        if ($action === 'edit' && !empty($edit_product['specifications'])) {
                                            $specs = json_decode($edit_product['specifications'], true) ?: [];
                                        }
                                        
                                        if (empty($specs)) {
                                            $specs = ['Display' => '', 'Camera' => '', 'Battery' => '', 'Storage' => ''];
                                        }
                                        
                                        foreach ($specs as $key => $value): ?>
                                            <div class="spec-row">
                                                <input type="text" name="spec_keys[]" placeholder="Specification name" value="<?php echo htmlspecialchars($key); ?>">
                                                <input type="text" name="spec_values[]" placeholder="Specification value" value="<?php echo htmlspecialchars($value); ?>">
                                                <button type="button" class="btn btn-sm btn-danger remove-spec">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                    <button type="button" id="add-spec" class="btn btn-sm btn-secondary">
                                        <i class="fas fa-plus"></i> Add Specification
                                    </button>
                                </div>

                                <div class="form-actions">
                                    <button type="submit" class="btn btn-primary">
                                        <i class="fas fa-save"></i>
                                        <?php echo $action === 'add' ? 'Add Product' : 'Update Product'; ?>
                                    </button>
                                    <a href="products" class="btn btn-secondary">
                                        <i class="fas fa-times"></i> Cancel
                                    </a>
                                </div>
                            </form>
                        </div>
                    </div>

                <?php else: ?>
                    <!-- Products List -->
                    <div class="filters">
                        <form method="GET" class="filters-grid">
                            <div class="form-group">
                                <input type="text" name="search" placeholder="Search products..." 
                                       value="<?php echo htmlspecialchars($search); ?>">
                            </div>
                            <div class="form-group">
                                <select name="category">
                                    <option value="">All Categories</option>
                                    <?php foreach ($categories as $category): ?>
                                        <option value="<?php echo $category['id']; ?>" 
                                                <?php echo $category_filter == $category['id'] ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($category['name']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="form-group">
                                <select name="brand">
                                    <option value="">All Brands</option>
                                    <?php foreach ($brands as $brand): ?>
                                        <option value="<?php echo $brand['id']; ?>" 
                                                <?php echo $brand_filter == $brand['id'] ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($brand['name']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div>
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-search"></i> Filter
                                </button>
                            </div>
                        </form>
                    </div>

                    <div class="card">
                        <div class="card-header">
                            <h3>Products (<?php echo $total_products; ?> total)</h3>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table">
                                    <thead>
                                        <tr>
                                            <th>Image</th>
                                            <th>Product</th>
                                            <th>Brand</th>
                                            <th>Category</th>
                                            <th>Price</th>
                                            <th>Status</th>
                                            <th>Featured</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php if (empty($products)): ?>
                                            <tr>
                                                <td colspan="8" class="text-center">No products found</td>
                                            </tr>
                                        <?php else: ?>
                                            <?php foreach ($products as $product): ?>
                                                <tr>
                                                    <td>
                                                        <?php if (!empty($product['main_image'])): ?>
                                                            <img src="../<?php echo htmlspecialchars($product['main_image']); ?>" 
                                                                 alt="<?php echo htmlspecialchars($product['name']); ?>" 
                                                                 class="product-image"
                                                                 onerror="this.style.display='none'; this.nextElementSibling.style.display='flex';">
                                                            <div class="no-image" style="display: none;">No Image</div>
                                                        <?php else: ?>
                                                            <div class="no-image">No Image</div>
                                                        <?php endif; ?>
                                                    </td>
                                                    <td>
                                                        <strong><?php echo htmlspecialchars($product['name']); ?></strong>
                                                        <?php if ($product['model']): ?>
                                                            <br><small><?php echo htmlspecialchars($product['model']); ?></small>
                                                        <?php endif; ?>
                                                    </td>
                                                    <td><?php echo htmlspecialchars($product['brand_name'] ?? 'N/A'); ?></td>
                                                    <td><?php echo htmlspecialchars($product['category_name'] ?? 'N/A'); ?></td>
                                                    <td>
                                                        <?php if ($product['discount_price']): ?>
                                                            <span style="text-decoration: line-through; color: #999;">₹<?php echo number_format($product['price'], 2); ?></span><br>
                                                            <strong>₹<?php echo number_format($product['discount_price'], 2); ?></strong>
                                                        <?php else: ?>
                                                            <strong>₹<?php echo number_format($product['price'], 2); ?></strong>
                                                        <?php endif; ?>
                                                    </td>
                                                    <td>
                                                        <span class="badge badge-<?php echo $product['availability_status'] === 'in_stock' ? 'success' : ($product['availability_status'] === 'out_of_stock' ? 'danger' : 'warning'); ?>">
                                                            <?php echo ucfirst(str_replace('_', ' ', $product['availability_status'])); ?>
                                                        </span>
                                                    </td>
                                                    <td>
                                                        <?php if ($product['is_featured']): ?>
                                                            <span class="badge badge-primary">Featured</span>
                                                        <?php endif; ?>
                                                    </td>
                                                    <td>
                                                        <div class="actions">
                                                            <a href="?action=edit&id=<?php echo $product['id']; ?>" 
                                                               class="btn btn-sm btn-primary" title="Edit">
                                                                <i class="fas fa-edit"></i>
                                                            </a>
                                                            <form method="POST" style="display: inline;" 
                                                                  onsubmit="return confirm('Are you sure you want to delete this product?');">
                                                                <input type="hidden" name="action" value="delete">
                                                                <input type="hidden" name="product_id" value="<?php echo $product['id']; ?>">
                                                                <button type="submit" class="btn btn-sm btn-danger" title="Delete">
                                                                    <i class="fas fa-trash"></i>
                                                                </button>
                                                            </form>
                                                        </div>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                        <?php endif; ?>
                                    </tbody>
                                </table>
                            </div>

                            <?php if ($total_pages > 1): ?>
                                <div class="pagination">
                                    <?php if ($page > 1): ?>
                                        <a href="?page=<?php echo $page - 1; ?>&search=<?php echo urlencode($search); ?>&category=<?php echo $category_filter; ?>&brand=<?php echo $brand_filter; ?>">
                                            <i class="fas fa-chevron-left"></i> Previous
                                        </a>
                                    <?php endif; ?>

                                    <?php for ($i = max(1, $page - 2); $i <= min($total_pages, $page + 2); $i++): ?>
                                        <?php if ($i == $page): ?>
                                            <span class="current"><?php echo $i; ?></span>
                                        <?php else: ?>
                                            <a href="?page=<?php echo $i; ?>&search=<?php echo urlencode($search); ?>&category=<?php echo $category_filter; ?>&brand=<?php echo $brand_filter; ?>">
                                                <?php echo $i; ?>
                                            </a>
                                        <?php endif; ?>
                                    <?php endfor; ?>

                                    <?php if ($page < $total_pages): ?>
                                        <a href="?page=<?php echo $page + 1; ?>&search=<?php echo urlencode($search); ?>&category=<?php echo $category_filter; ?>&brand=<?php echo $brand_filter; ?>">
                                            Next <i class="fas fa-chevron-right"></i>
                                        </a>
                                    <?php endif; ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </main>
    </div>

    <script>
        // Add specification row
        document.getElementById('add-spec').addEventListener('click', function() {
            const container = document.getElementById('specifications');
            const row = document.createElement('div');
            row.className = 'spec-row';
            row.innerHTML = `
                <input type="text" name="spec_keys[]" placeholder="Specification name">
                <input type="text" name="spec_values[]" placeholder="Specification value">
                <button type="button" class="btn btn-sm btn-danger remove-spec">
                    <i class="fas fa-trash"></i>
                </button>
            `;
            container.appendChild(row);
        });

        // Remove specification row
        document.addEventListener('click', function(e) {
            if (e.target.classList.contains('remove-spec') || e.target.closest('.remove-spec')) {
                const row = e.target.closest('.spec-row');
                if (row) {
                    row.remove();
                }
            }
        });

        // Preview images before upload
        document.getElementById('main_image')?.addEventListener('change', function(e) {
            const file = e.target.files[0];
            if (file) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    let preview = document.querySelector('.main-image-preview');
                    if (!preview) {
                        preview = document.createElement('img');
                        preview.className = 'image-preview main-image-preview';
                        preview.style.marginTop = '0.5rem';
                        e.target.parentNode.appendChild(preview);
                    }
                    preview.src = e.target.result;
                };
                reader.readAsDataURL(file);
            }
        });
    </script>
</body>
</html>
