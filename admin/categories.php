<?php
session_start();
require_once '../includes/config.php';
require_once '../includes/db_connect.php';
require_once '../includes/functions.php';

// Check if admin or editor is logged in
if (!isLoggedIn() || !hasAdminAccess()) {
    redirect('login.php');
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
            $category_id = $post_action === 'edit' ? (int)$_POST['category_id'] : null;
            $name = sanitize($_POST['name']);
            $description = sanitize($_POST['description']);
            $icon_class = sanitize($_POST['icon_class']);
            
            // Generate SEO-friendly slug
            $slug = generateSlug($name);
            
            // Check if slug already exists (for different category)
            $slug_check_sql = "SELECT id FROM categories WHERE slug = ?";
            if ($post_action === 'edit') {
                $slug_check_sql .= " AND id != ?";
            }
            $stmt = $pdo->prepare($slug_check_sql);
            $params = [$slug];
            if ($post_action === 'edit') {
                $params[] = $category_id;
            }
            $stmt->execute($params);
            
            if ($stmt->fetch()) {
                $slug = $slug . '-' . time(); // Make it unique
            }
            
            if (empty($error)) {
                try {
                    if ($post_action === 'add') {
                        $stmt = $pdo->prepare("
                            INSERT INTO categories (name, slug, description, icon_class, created_at) 
                            VALUES (?, ?, ?, ?, NOW())
                        ");
                        $stmt->execute([$name, $slug, $description, $icon_class]);
                        $message = 'Category added successfully!';
                    } else {
                        $stmt = $pdo->prepare("
                            UPDATE categories SET name = ?, slug = ?, description = ?, icon_class = ?, updated_at = NOW() 
                            WHERE id = ?
                        ");
                        $stmt->execute([$name, $slug, $description, $icon_class, $category_id]);
                        $message = 'Category updated successfully!';
                    }
                    $action = 'list';
                } catch (Exception $e) {
                    if (strpos($e->getMessage(), 'Duplicate entry') !== false) {
                        $error = 'A category with this name already exists.';
                    } else {
                        $error = 'Database error: ' . $e->getMessage();
                    }
                }
            }
            break;
            
        case 'delete':
            $category_id = (int)$_POST['category_id'];
            try {
                // Check if category has products
                $stmt = $pdo->prepare("SELECT COUNT(*) FROM products WHERE category_id = ?");
                $stmt->execute([$category_id]);
                $product_count = $stmt->fetchColumn();
                
                if ($product_count > 0) {
                    $error = "Cannot delete category. It has {$product_count} products associated with it.";
                } else {
                    $stmt = $pdo->prepare("DELETE FROM categories WHERE id = ?");
                    $stmt->execute([$category_id]);
                    $message = 'Category deleted successfully!';
                }
            } catch (Exception $e) {
                $error = 'Error deleting category: ' . $e->getMessage();
            }
            break;
    }
}

// Get categories list with pagination
$page = max(1, (int)($_GET['page'] ?? 1));
$per_page = 15;
$offset = ($page - 1) * $per_page;

$search = $_GET['search'] ?? '';
$where_clause = '';
$params = [];

if (!empty($search)) {
    $where_clause = "WHERE name LIKE ? OR description LIKE ?";
    $search_param = "%{$search}%";
    $params = [$search_param, $search_param];
}

// Count total categories
$count_sql = "SELECT COUNT(*) FROM categories {$where_clause}";
$count_stmt = $pdo->prepare($count_sql);
$count_stmt->execute($params);
$total_categories = $count_stmt->fetchColumn();
$total_pages = ceil($total_categories / $per_page);

// Get categories with product count
$sql = "
    SELECT c.*, COUNT(p.id) as product_count 
    FROM categories c 
    LEFT JOIN products p ON c.id = p.category_id 
    {$where_clause}
    GROUP BY c.id 
    ORDER BY c.name ASC 
    LIMIT {$per_page} OFFSET {$offset}
";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$categories = $stmt->fetchAll();

// Get specific category for editing
$edit_category = null;
if ($action === 'edit' && isset($_GET['id'])) {
    $stmt = $pdo->prepare("SELECT * FROM categories WHERE id = ?");
    $stmt->execute([(int)$_GET['id']]);
    $edit_category = $stmt->fetch();
    if (!$edit_category) {
        $action = 'list';
        $error = 'Category not found!';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Categories Management - TechCompare Admin</title>
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
        .icon-preview {
            font-size: 2rem;
            padding: 1rem;
            text-align: center;
            border: 1px solid #ddd;
            border-radius: 4px;
            background: #f8f9fa;
            margin-top: 0.5rem;
        }
        .category-icon {
            font-size: 1.5rem;
            width: 40px;
            height: 40px;
            display: flex;
            align-items: center;
            justify-content: center;
            background: #f8f9fa;
            border-radius: 50%;
            margin-right: 1rem;
        }
        .category-info {
            display: flex;
            align-items: center;
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
            grid-template-columns: 1fr auto;
            gap: 1rem;
            align-items: end;
        }
        .icon-selector {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(60px, 1fr));
            gap: 0.5rem;
            max-height: 200px;
            overflow-y: auto;
            border: 1px solid #ddd;
            padding: 1rem;
            border-radius: 4px;
            margin-top: 0.5rem;
        }
        .icon-option {
            padding: 0.5rem;
            text-align: center;
            border: 1px solid #ddd;
            border-radius: 4px;
            cursor: pointer;
            transition: all 0.3s ease;
        }
        .icon-option:hover, .icon-option.selected {
            background: #4361ee;
            color: white;
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
                    <li class="nav-item active">
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
                    <?php if (isAdmin()): ?>
                    <li class="nav-item">
                        <a href="settings.php" class="nav-link">
                            <i class="fas fa-cogs"></i>
                            <span>Settings</span>
                        </a>
                    </li>
                    <?php endif; ?>
                    <li class="nav-divider" style="margin: 1rem 0; border-top: 1px solid rgba(255,255,255,0.1);"></li>
                    <li class="nav-item">
                        <a href="../index.php" class="nav-link" target="_blank">
                            <i class="fas fa-external-link-alt"></i>
                            <span>View Site</span>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="logout.php" class="nav-link" style="color: #dc3545 !important;">
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
                        case 'add': echo 'Add New Category'; break;
                        case 'edit': echo 'Edit Category'; break;
                        default: echo 'Categories Management'; break;
                    }
                    ?>
                </h1>
                <div class="header-actions">
                    <?php if ($action === 'list'): ?>
                        <a href="?action=add" class="btn btn-primary">
                            <i class="fas fa-plus"></i> Add New Category
                        </a>
                    <?php else: ?>
                        <a href="categories.php" class="btn btn-secondary">
                            <i class="fas fa-arrow-left"></i> Back to Categories
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
                    <!-- Add/Edit Category Form -->
                    <div class="card">
                        <div class="card-header">
                            <h3><?php echo $action === 'add' ? 'Add New Category' : 'Edit Category'; ?></h3>
                        </div>
                        <div class="card-body">
                            <form method="POST">
                                <input type="hidden" name="action" value="<?php echo $action; ?>">
                                <?php if ($action === 'edit'): ?>
                                    <input type="hidden" name="category_id" value="<?php echo $edit_category['id']; ?>">
                                <?php endif; ?>

                                <div class="form-grid">
                                    <div class="form-group">
                                        <label for="name">Category Name *</label>
                                        <input type="text" id="name" name="name" required 
                                               value="<?php echo htmlspecialchars($edit_category['name'] ?? ''); ?>">
                                        <small>Slug will be auto-generated from the name</small>
                                    </div>

                                    <div class="form-group">
                                        <label for="icon_class">Icon Class</label>
                                        <input type="text" id="icon_class" name="icon_class" 
                                               value="<?php echo htmlspecialchars($edit_category['icon_class'] ?? 'fas fa-tag'); ?>"
                                               placeholder="fas fa-tag">
                                        <small>FontAwesome icon class (e.g., fas fa-mobile-alt)</small>
                                        <div class="icon-preview" id="icon-preview">
                                            <i class="<?php echo htmlspecialchars($edit_category['icon_class'] ?? 'fas fa-tag'); ?>"></i>
                                        </div>
                                    </div>
                                </div>

                                <div class="form-group form-grid-full">
                                    <label for="description">Description</label>
                                    <textarea id="description" name="description" rows="4"><?php echo htmlspecialchars($edit_category['description'] ?? ''); ?></textarea>
                                </div>

                                <div class="form-group form-grid-full">
                                    <label>Common Icons (Click to select)</label>
                                    <div class="icon-selector">
                                        <?php 
                                        $common_icons = [
                                            'fas fa-mobile-alt', 'fas fa-laptop', 'fas fa-tablet-alt', 'fas fa-headphones',
                                            'fas fa-camera', 'fas fa-tv', 'fas fa-gamepad', 'fas fa-clock',
                                            'fas fa-heart', 'fas fa-home', 'fas fa-car', 'fas fa-bicycle',
                                            'fas fa-music', 'fas fa-book', 'fas fa-utensils', 'fas fa-coffee',
                                            'fas fa-tshirt', 'fas fa-running', 'fas fa-dumbbell', 'fas fa-gift'
                                        ];
                                        foreach ($common_icons as $icon): ?>
                                            <div class="icon-option" data-icon="<?php echo $icon; ?>">
                                                <i class="<?php echo $icon; ?>"></i>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                </div>

                                <div class="form-actions">
                                    <button type="submit" class="btn btn-primary">
                                        <i class="fas fa-save"></i>
                                        <?php echo $action === 'add' ? 'Add Category' : 'Update Category'; ?>
                                    </button>
                                    <a href="categories.php" class="btn btn-secondary">
                                        <i class="fas fa-times"></i> Cancel
                                    </a>
                                </div>
                            </form>
                        </div>
                    </div>

                <?php else: ?>
                    <!-- Categories List -->
                    <div class="filters">
                        <form method="GET" class="filters-grid">
                            <div class="form-group">
                                <input type="text" name="search" placeholder="Search categories..." 
                                       value="<?php echo htmlspecialchars($search); ?>">
                            </div>
                            <div>
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-search"></i> Search
                                </button>
                            </div>
                        </form>
                    </div>

                    <div class="card">
                        <div class="card-header">
                            <h3>Categories (<?php echo $total_categories; ?> total)</h3>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table">
                                    <thead>
                                        <tr>
                                            <th>Category</th>
                                            <th>Slug</th>
                                            <th>Description</th>
                                            <th>Products</th>
                                            <th>Created</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php if (empty($categories)): ?>
                                            <tr>
                                                <td colspan="6" class="text-center">No categories found</td>
                                            </tr>
                                        <?php else: ?>
                                            <?php foreach ($categories as $category): ?>
                                                <tr>
                                                    <td>
                                                        <div class="category-info">
                                                            <div class="category-icon">
                                                                <i class="<?php echo htmlspecialchars($category['icon_class'] ?: 'fas fa-tag'); ?>"></i>
                                                            </div>
                                                            <strong><?php echo htmlspecialchars($category['name']); ?></strong>
                                                        </div>
                                                    </td>
                                                    <td>
                                                        <code><?php echo htmlspecialchars($category['slug']); ?></code>
                                                    </td>
                                                    <td>
                                                        <?php 
                                                        $desc = $category['description'];
                                                        echo htmlspecialchars(strlen($desc) > 50 ? substr($desc, 0, 50) . '...' : $desc); 
                                                        ?>
                                                    </td>
                                                    <td>
                                                        <span class="badge badge-info"><?php echo $category['product_count']; ?></span>
                                                    </td>
                                                    <td><?php echo date('M d, Y', strtotime($category['created_at'])); ?></td>
                                                    <td>
                                                        <div class="actions">
                                                            <a href="?action=edit&id=<?php echo $category['id']; ?>" 
                                                               class="btn btn-sm btn-primary" title="Edit">
                                                                <i class="fas fa-edit"></i>
                                                            </a>
                                                            <?php if ($category['product_count'] == 0): ?>
                                                                <form method="POST" style="display: inline;" 
                                                                      onsubmit="return confirm('Are you sure you want to delete this category?');">
                                                                    <input type="hidden" name="action" value="delete">
                                                                    <input type="hidden" name="category_id" value="<?php echo $category['id']; ?>">
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

                            <?php if ($total_pages > 1): ?>
                                <div class="pagination">
                                    <?php if ($page > 1): ?>
                                        <a href="?page=<?php echo $page - 1; ?>&search=<?php echo urlencode($search); ?>">
                                            <i class="fas fa-chevron-left"></i> Previous
                                        </a>
                                    <?php endif; ?>

                                    <?php for ($i = max(1, $page - 2); $i <= min($total_pages, $page + 2); $i++): ?>
                                        <?php if ($i == $page): ?>
                                            <span class="current"><?php echo $i; ?></span>
                                        <?php else: ?>
                                            <a href="?page=<?php echo $i; ?>&search=<?php echo urlencode($search); ?>">
                                                <?php echo $i; ?>
                                            </a>
                                        <?php endif; ?>
                                    <?php endfor; ?>

                                    <?php if ($page < $total_pages): ?>
                                        <a href="?page=<?php echo $page + 1; ?>&search=<?php echo urlencode($search); ?>">
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
        // Icon preview update
        document.getElementById('icon_class').addEventListener('input', function() {
            const iconClass = this.value || 'fas fa-tag';
            document.querySelector('#icon-preview i').className = iconClass;
        });

        // Icon selector
        document.addEventListener('click', function(e) {
            if (e.target.closest('.icon-option')) {
                const iconOption = e.target.closest('.icon-option');
                const iconClass = iconOption.dataset.icon;
                
                // Update input and preview
                document.getElementById('icon_class').value = iconClass;
                document.querySelector('#icon-preview i').className = iconClass;
                
                // Update selected state
                document.querySelectorAll('.icon-option').forEach(opt => opt.classList.remove('selected'));
                iconOption.classList.add('selected');
            }
        });

        // Set initial selected icon
        document.addEventListener('DOMContentLoaded', function() {
            const currentIcon = document.getElementById('icon_class').value;
            const currentOption = document.querySelector(`[data-icon="${currentIcon}"]`);
            if (currentOption) {
                currentOption.classList.add('selected');
            }
        });
    </script>
</body>
</html>
