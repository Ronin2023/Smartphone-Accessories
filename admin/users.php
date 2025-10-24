<?php
/**
 * Admin User Management Page
 * Only accessible by administrators
 * Manages users, editors, and admins with full CRUD operations
 */

session_start();
require_once '../includes/config.php';
require_once '../includes/db_connect.php';
require_once '../includes/functions.php';

// Check if admin is logged in
if (!isLoggedIn() || !isAdmin()) {
    redirect('index');
    exit();
}

$pdo = getDB();

$message = '';
$error = '';

// Check for session messages
if (isset($_SESSION['success_message'])) {
    $message = $_SESSION['success_message'];
    unset($_SESSION['success_message']);
}

if (isset($_SESSION['error_message'])) {
    $error = $_SESSION['error_message'];
    unset($_SESSION['error_message']);
}

// Handle user actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    switch($action) {
        case 'add_user':
            $username = sanitize($_POST['username']);
            $email = sanitize($_POST['email']);
            $password = $_POST['password'];
            $role = $_POST['role'];
            $first_name = sanitize($_POST['first_name']);
            $last_name = sanitize($_POST['last_name']);
            
            // Validate input
            if (empty($username) || empty($email) || empty($password) || empty($role)) {
                $error = 'All fields are required.';
            } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $error = 'Invalid email format.';
            } elseif (strlen($password) < 6) {
                $error = 'Password must be at least 6 characters long.';
            } else {
                try {
                    // Check if username or email already exists
                    $stmt = $pdo->prepare("SELECT id FROM users WHERE username = ? OR email = ?");
                    $stmt->execute([$username, $email]);
                    if ($stmt->fetch()) {
                        $error = 'Username or email already exists.';
                    } else {
                        // Create new user
                        $password_hash = password_hash($password, PASSWORD_DEFAULT);
                        $stmt = $pdo->prepare("
                            INSERT INTO users (username, email, password_hash, role, first_name, last_name, is_active, created_at) 
                            VALUES (?, ?, ?, ?, ?, ?, 1, NOW())
                        ");
                        $stmt->execute([$username, $email, $password_hash, $role, $first_name, $last_name]);
                        $message = 'User created successfully.';
                    }
                } catch (PDOException $e) {
                    $error = 'Database error: ' . $e->getMessage();
                }
            }
            break;
            
        case 'delete_user':
            $user_id = (int)$_POST['user_id'];
            $current_user_id = $_SESSION['user_id'];
            
            if ($user_id === $current_user_id) {
                $error = 'You cannot delete your own account.';
            } else {
                try {
                    $stmt = $pdo->prepare("DELETE FROM users WHERE id = ?");
                    $stmt->execute([$user_id]);
                    $message = 'User deleted successfully.';
                } catch (PDOException $e) {
                    $error = 'Error deleting user: ' . $e->getMessage();
                }
            }
            break;
            
        case 'reset_password':
            $user_id = (int)$_POST['user_id'];
            $new_password = $_POST['new_password'];
            $confirm_admin_password = $_POST['confirm_admin_password'];
            
            // Verify admin password for security
            $stmt = $pdo->prepare("SELECT password_hash FROM users WHERE id = ?");
            $stmt->execute([$_SESSION['user_id']]);
            $admin = $stmt->fetch();
            
            if (!password_verify($confirm_admin_password, $admin['password_hash'])) {
                $error = 'Admin password verification failed.';
            } elseif (strlen($new_password) < 6) {
                $error = 'New password must be at least 6 characters long.';
            } else {
                try {
                    $new_password_hash = password_hash($new_password, PASSWORD_DEFAULT);
                    $stmt = $pdo->prepare("UPDATE users SET password_hash = ?, updated_at = NOW() WHERE id = ?");
                    $stmt->execute([$new_password_hash, $user_id]);
                    $message = 'Password reset successfully.';
                } catch (PDOException $e) {
                    $error = 'Error resetting password: ' . $e->getMessage();
                }
            }
            break;
            
        case 'toggle_active':
            $user_id = (int)$_POST['user_id'];
            $current_user_id = $_SESSION['user_id'];
            
            if ($user_id <= 0) {
                $error = 'Invalid user ID.';
            } elseif ($user_id === $current_user_id) {
                $error = 'You cannot deactivate your own account.';
            } else {
                try {
                    // Get current status
                    $stmt = $pdo->prepare("SELECT is_active, status FROM users WHERE id = ?");
                    $stmt->execute([$user_id]);
                    $user = $stmt->fetch(PDO::FETCH_ASSOC);
                    
                    if ($user) {
                        $new_is_active = $user['is_active'] ? 0 : 1; // Explicit toggle
                        $new_status = $new_is_active ? 'active' : 'locked';
                        
                        $stmt = $pdo->prepare("UPDATE users SET is_active = ?, status = ?, updated_at = NOW() WHERE id = ?");
                        $result = $stmt->execute([(int)$new_is_active, $new_status, $user_id]);
                        
                        if ($result && $stmt->rowCount() > 0) {
                            $message = 'User status updated successfully.';
                        } else {
                            $error = 'Failed to update user status.';
                        }
                    } else {
                        $error = 'User not found.';
                    }
                } catch (PDOException $e) {
                    $error = 'Error updating user status: ' . $e->getMessage();
                }
            }
            break;
            
        case 'toggle_active':
            $user_id = (int)$_POST['user_id'];
            $current_user_id = $_SESSION['user_id'];
            
            if ($user_id <= 0) {
                $error = 'Invalid user ID.';
            } elseif ($user_id === $current_user_id) {
                $error = 'You cannot deactivate your own account.';
            } else {
                try {
                    // Get current status
                    $stmt = $pdo->prepare("SELECT is_active, status FROM users WHERE id = ?");
                    $stmt->execute([$user_id]);
                    $user = $stmt->fetch(PDO::FETCH_ASSOC);
                    
                    if ($user) {
                        $new_is_active = $user['is_active'] ? 0 : 1; // Explicit toggle
                        $new_status = $new_is_active ? 'active' : 'locked';
                        
                        $stmt = $pdo->prepare("UPDATE users SET is_active = ?, status = ?, updated_at = NOW() WHERE id = ?");
                        $result = $stmt->execute([(int)$new_is_active, $new_status, $user_id]);
                        
                        if ($result && $stmt->rowCount() > 0) {
                            $message = 'User status updated successfully.';
                        } else {
                            $error = 'Failed to update user status.';
                        }
                    } else {
                        $error = 'User not found.';
                    }
                } catch (PDOException $e) {
                    $error = 'Error updating user status: ' . $e->getMessage();
                }
            }
            break;
            
        case 'update_status':
            $user_id = (int)$_POST['user_id'];
            $status = trim($_POST['status'] ?? '');
            $current_user_id = $_SESSION['user_id'];
            
            if (empty($status)) {
                $error = 'Status cannot be empty.';
            } elseif ($user_id <= 0) {
                $error = 'Invalid user ID.';
            } elseif ($user_id === $current_user_id && in_array($status, ['locked', 'suspended', 'inactive'])) {
                $error = 'You cannot lock/suspend your own account.';
            } else {
                try {
                    $valid_statuses = ['active', 'inactive', 'locked', 'suspended', 'pending'];
                    if (in_array($status, $valid_statuses)) {
                        $is_active = ($status === 'active') ? 1 : 0;
                        
                        $stmt = $pdo->prepare("UPDATE users SET status = ?, is_active = ?, updated_at = NOW() WHERE id = ?");
                        $result = $stmt->execute([$status, (int)$is_active, $user_id]);
                        
                        if ($result && $stmt->rowCount() > 0) {
                            $message = 'User status updated to ' . ucfirst($status) . ' successfully.';
                        } else {
                            $error = 'Failed to update user status. User may not exist.';
                        }
                    } else {
                        $error = 'Invalid status selected: ' . htmlspecialchars($status);
                    }
                } catch (PDOException $e) {
                    $error = 'Error updating user status: ' . $e->getMessage();
                }
            }
            break;
    }
}

// Get all users with pagination
$page = (int)($_GET['page'] ?? 1);
$limit = 10;
$offset = ($page - 1) * $limit;

$role_filter = $_GET['role'] ?? '';
$search = $_GET['search'] ?? '';

$where_clauses = [];
$params = [];

if ($role_filter) {
    $where_clauses[] = "role = ?";
    $params[] = $role_filter;
}

if ($search) {
    $where_clauses[] = "(username LIKE ? OR email LIKE ? OR first_name LIKE ? OR last_name LIKE ?)";
    $search_param = "%$search%";
    $params = array_merge($params, [$search_param, $search_param, $search_param, $search_param]);
}

$where_sql = $where_clauses ? "WHERE " . implode(" AND ", $where_clauses) : "";

// Get total count
$count_sql = "SELECT COUNT(*) FROM users $where_sql";
$stmt = $pdo->prepare($count_sql);
$stmt->execute($params);
$total_users = $stmt->fetchColumn();
$total_pages = ceil($total_users / $limit);

// Get users
$sql = "
    SELECT id, username, email, role, first_name, last_name, is_active, status, created_at, last_login 
    FROM users 
    $where_sql 
    ORDER BY created_at DESC 
    LIMIT $limit OFFSET $offset
";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$users = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get role counts
$role_counts = [];
$roles = ['admin', 'editor', 'user'];
foreach ($roles as $role) {
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM users WHERE role = ?");
    $stmt->execute([$role]);
    $role_counts[$role] = $stmt->fetchColumn();
}

closeDB($pdo);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Management - TechCompare Admin</title>
    <link rel="stylesheet" href="../css/style.css">
    <link rel="stylesheet" href="../css/admin.css">`n    <link rel="stylesheet" href="../css/admin-dark-mode.css">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">`n    <script src="../js/admin-dark-mode.js"></script>
    <style>
        .users-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 2rem;
            flex-wrap: wrap;
            gap: 1rem;
        }
        
        .role-stats {
            display: flex;
            gap: 1rem;
            margin-bottom: 2rem;
            flex-wrap: wrap;
        }
        
        .role-stat {
            background: white;
            padding: 1rem 1.5rem;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            text-align: center;
            min-width: 120px;
        }
        
        .role-stat-number {
            font-size: 2rem;
            font-weight: bold;
            color: var(--primary-color);
        }
        
        .role-stat-label {
            color: var(--text-light);
            font-size: 0.9rem;
            text-transform: uppercase;
        }
        
        .filters-section {
            background: white;
            padding: 1.5rem;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            margin-bottom: 2rem;
        }
        
        .filters-row {
            display: flex;
            gap: 1rem;
            align-items: end;
            flex-wrap: wrap;
        }
        
        .filter-group {
            flex: 1;
            min-width: 200px;
        }
        
        .filter-group label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 500;
            color: var(--text-dark);
        }
        
        .filter-group select,
        .filter-group input {
            width: 100%;
            padding: 0.75rem;
            border: 1px solid var(--border-color);
            border-radius: 6px;
            font-size: 1rem;
        }
        
        .users-table {
            background: white;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            overflow: hidden;
        }
        
        .table-responsive {
            overflow-x: auto;
        }
        
        .users-table table {
            width: 100%;
            border-collapse: collapse;
        }
        
        .users-table th,
        .users-table td {
            padding: 1rem;
            text-align: left;
            border-bottom: 1px solid var(--border-color);
        }
        
        .users-table th {
            background: var(--primary-color);
            color: white;
            font-weight: 600;
        }
        
        .user-role {
            padding: 0.25rem 0.75rem;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 500;
        }
        
        .role-admin {
            background: #dc3545;
            color: white;
        }
        
        .role-editor {
            background: #fd7e14;
            color: white;
        }
        
        .role-user {
            background: #6c757d;
            color: white;
        }
        
        .status-badge {
            padding: 0.25rem 0.75rem;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 500;
        }
        
        .status-active {
            background: #d4edda;
            color: #155724;
        }
        
        .status-inactive {
            background: #f8d7da;
            color: #721c24;
        }
        
        .status-locked {
            background: #f0ad4e;
            color: #8a6d3b;
        }
        
        .status-suspended {
            background: #d9534f;
            color: #ffffff;
        }
        
        .status-pending {
            background: #5bc0de;
            color: #ffffff;
        }
        
        .status-actions {
            margin-top: 0.5rem;
        }
        
        .status-select {
            font-size: 0.75rem;
            padding: 0.25rem;
            width: 100px;
        }
        
        .user-actions {
            display: flex;
            gap: 0.5rem;
            flex-wrap: wrap;
        }
        
        .user-actions .btn {
            padding: 0.25rem 0.5rem;
            font-size: 0.8rem;
        }
        
        .modal {
            display: none;
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background: rgba(0,0,0,0.5);
        }
        
        .modal-content {
            background: white;
            margin: 5% auto;
            padding: 2rem;
            border-radius: 8px;
            width: 90%;
            max-width: 500px;
            max-height: 80vh;
            overflow-y: auto;
        }
        
        .modal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1.5rem;
            padding-bottom: 1rem;
            border-bottom: 1px solid var(--border-color);
        }
        
        .modal-close {
            background: none;
            border: none;
            font-size: 1.5rem;
            cursor: pointer;
            color: var(--text-light);
        }
        
        .form-group {
            margin-bottom: 1rem;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 500;
            color: var(--text-dark);
        }
        
        .form-group input,
        .form-group select {
            width: 100%;
            padding: 0.75rem;
            border: 1px solid var(--border-color);
            border-radius: 6px;
            font-size: 1rem;
        }
        
        .emergency-warning {
            background: #fff3cd;
            border: 1px solid #ffeaa7;
            color: #856404;
            padding: 1rem;
            border-radius: 6px;
            margin-bottom: 1rem;
        }
        
        .pagination {
            display: flex;
            justify-content: center;
            align-items: center;
            margin-top: 2rem;
            gap: 0.5rem;
        }
        
        .pagination a,
        .pagination span {
            padding: 0.5rem 1rem;
            border: 1px solid var(--border-color);
            color: var(--text-dark);
            text-decoration: none;
            border-radius: 4px;
        }
        
        .pagination .current {
            background: var(--primary-color);
            color: white;
            border-color: var(--primary-color);
        }
        
        .pagination a:hover {
            background: var(--primary-color);
            color: white;
            border-color: var(--primary-color);
        }
        
        @media (max-width: 768px) {
            .users-header {
                flex-direction: column;
                align-items: stretch;
            }
            
            .role-stats {
                justify-content: center;
            }
            
            .filters-row {
                flex-direction: column;
            }
            
            .user-actions {
                justify-content: center;
            }
        }
    </style>
</head>
<body class="admin-body">
    <div class="admin-container">
        <!-- Sidebar -->
        <aside class="admin-sidebar">
            <div class="sidebar-header">
                <div class="logo">
                    <i class="fas fa-balance-scale-right"></i>
                    <span>TechCompare</span>
                </div>
                <p class="admin-welcome">Welcome, <?php echo htmlspecialchars($_SESSION['username']); ?></p>
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
                    <li class="nav-item">
                        <a href="brands" class="nav-link">
                            <i class="fas fa-award"></i>
                            <span>Brands</span>
                        </a>
                    </li>
                    <li class="nav-item active">
                        <a href="users" class="nav-link">
                            <i class="fas fa-users"></i>
                            <span>Users</span>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="contacts" class="nav-link">
                            <i class="fas fa-envelope"></i>
                            <span>Contact Messages</span>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="settings" class="nav-link">
                            <i class="fas fa-cogs"></i>
                            <span>Settings</span>
                        </a>
                    </li>
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

        <!-- Main Content -->
        <main class="admin-main">
            <header class="admin-header">
                <h1><i class="fas fa-users"></i> User Management</h1>
                <div class="header-actions">
                    <span class="datetime"><?php echo date('Y-m-d H:i:s'); ?></span>
                </div>
            </header>

            <div class="admin-content">
                <!-- Messages -->
                <?php if ($message): ?>
                    <div class="alert alert-success">
                        <i class="fas fa-check-circle"></i>
                        <?php echo htmlspecialchars($message); ?>
                    </div>
                <?php endif; ?>

                <?php if ($error): ?>
                    <div class="alert alert-error">
                        <i class="fas fa-exclamation-triangle"></i>
                        <?php echo htmlspecialchars($error); ?>
                    </div>
                <?php endif; ?>

                <!-- Header with Add User Button -->
                <div class="users-header">
                    <h2>All Users (<?php echo $total_users; ?>)</h2>
                    <button class="btn btn-primary" onclick="openModal('addUserModal')">
                        <i class="fas fa-plus"></i> Add New User
                    </button>
                </div>

                <!-- Role Statistics -->
                <div class="role-stats">
                    <div class="role-stat">
                        <div class="role-stat-number"><?php echo $role_counts['admin']; ?></div>
                        <div class="role-stat-label">Admins</div>
                    </div>
                    <div class="role-stat">
                        <div class="role-stat-number"><?php echo $role_counts['editor']; ?></div>
                        <div class="role-stat-label">Editors</div>
                    </div>
                    <div class="role-stat">
                        <div class="role-stat-number"><?php echo $role_counts['user']; ?></div>
                        <div class="role-stat-label">Users</div>
                    </div>
                </div>

                <!-- Filters -->
                <div class="filters-section">
                    <form method="GET" class="filters-row">
                        <div class="filter-group">
                            <label for="role">Filter by Role</label>
                            <select name="role" id="role">
                                <option value="">All Roles</option>
                                <option value="admin" <?php echo $role_filter === 'admin' ? 'selected' : ''; ?>>Admin</option>
                                <option value="editor" <?php echo $role_filter === 'editor' ? 'selected' : ''; ?>>Editor</option>
                                <option value="user" <?php echo $role_filter === 'user' ? 'selected' : ''; ?>>User</option>
                            </select>
                        </div>
                        <div class="filter-group">
                            <label for="search">Search Users</label>
                            <input type="text" name="search" id="search" placeholder="Username, email, or name..." value="<?php echo htmlspecialchars($search); ?>">
                        </div>
                        <div class="filter-group">
                            <button type="submit" class="btn btn-secondary">
                                <i class="fas fa-search"></i> Filter
                            </button>
                            <a href="users" class="btn btn-light">
                                <i class="fas fa-times"></i> Clear
                            </a>
                        </div>
                    </form>
                </div>

                <!-- Users Table -->
                <div class="users-table">
                    <div class="table-responsive">
                        <table>
                            <thead>
                                <tr>
                                    <th>User</th>
                                    <th>Email</th>
                                    <th>Role</th>
                                    <th>Status</th>
                                    <th>Created</th>
                                    <th>Last Login</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($users as $user): ?>
                                <tr>
                                    <td>
                                        <div>
                                            <strong><?php echo htmlspecialchars($user['username']); ?></strong>
                                            <?php if ($user['first_name'] || $user['last_name']): ?>
                                                <br><small><?php echo htmlspecialchars(trim($user['first_name'] . ' ' . $user['last_name'])); ?></small>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                    <td><?php echo htmlspecialchars($user['email']); ?></td>
                                    <td>
                                        <span class="user-role role-<?php echo $user['role']; ?>">
                                            <?php echo ucfirst($user['role']); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <?php 
                                        $status = $user['status'] ?? ($user['is_active'] ? 'active' : 'inactive');
                                        $status_class = '';
                                        $status_text = '';
                                        
                                        switch($status) {
                                            case 'active':
                                                $status_class = 'active';
                                                $status_text = 'Active';
                                                break;
                                            case 'locked':
                                                $status_class = 'locked';
                                                $status_text = 'Locked';
                                                break;
                                            case 'suspended':
                                                $status_class = 'suspended';
                                                $status_text = 'Suspended';
                                                break;
                                            case 'inactive':
                                                $status_class = 'inactive';
                                                $status_text = 'Inactive';
                                                break;
                                            case 'pending':
                                                $status_class = 'pending';
                                                $status_text = 'Pending';
                                                break;
                                            default:
                                                $status_class = $user['is_active'] ? 'active' : 'inactive';
                                                $status_text = $user['is_active'] ? 'Active' : 'Inactive';
                                        }
                                        ?>
                                        <span class="status-badge status-<?php echo $status_class; ?>">
                                            <?php echo $status_text; ?>
                                        </span>
                                        <?php if ($user['id'] != $_SESSION['user_id']): ?>
                                            <div class="status-actions">
                                                <select class="form-control form-control-sm status-select" 
                                                        data-user-id="<?php echo $user['id']; ?>" 
                                                        onchange="updateUserStatus(this, <?php echo $user['id']; ?>)">
                                                    <option value="active" <?php echo $status === 'active' ? 'selected' : ''; ?>>Active</option>
                                                    <option value="inactive" <?php echo $status === 'inactive' ? 'selected' : ''; ?>>Inactive</option>
                                                    <option value="locked" <?php echo $status === 'locked' ? 'selected' : ''; ?>>Locked</option>
                                                    <option value="suspended" <?php echo $status === 'suspended' ? 'selected' : ''; ?>>Suspended</option>
                                                    <option value="pending" <?php echo $status === 'pending' ? 'selected' : ''; ?>>Pending</option>
                                                </select>
                                            </div>
                                        <?php endif; ?>
                                    </td>
                                    <td><?php echo date('M j, Y', strtotime($user['created_at'])); ?></td>
                                    <td><?php echo $user['last_login'] ? date('M j, Y H:i', strtotime($user['last_login'])) : 'Never'; ?></td>
                                    <td>
                                        <div class="user-actions">
                                            <?php if ($user['id'] != $_SESSION['user_id']): ?>
                                                <button class="btn btn-sm btn-warning" onclick="openResetPasswordModal(<?php echo $user['id']; ?>, '<?php echo htmlspecialchars($user['username']); ?>')">
                                                    <i class="fas fa-key"></i>
                                                </button>
                                                <form method="POST" style="display: inline;" onsubmit="return confirm('Are you sure you want to <?php echo $user['is_active'] ? 'deactivate' : 'activate'; ?> this user?')">
                                                    <input type="hidden" name="action" value="toggle_active">
                                                    <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                                                    <button type="submit" class="btn btn-sm <?php echo $user['is_active'] ? 'btn-secondary' : 'btn-success'; ?>">
                                                        <i class="fas fa-<?php echo $user['is_active'] ? 'ban' : 'check'; ?>"></i>
                                                    </button>
                                                </form>
                                                <form method="POST" style="display: inline;" onsubmit="return confirm('Are you sure you want to delete this user? This action cannot be undone.')">
                                                    <input type="hidden" name="action" value="delete_user">
                                                    <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                                                    <button type="submit" class="btn btn-sm btn-danger">
                                                        <i class="fas fa-trash"></i>
                                                    </button>
                                                </form>
                                            <?php else: ?>
                                                <span class="text-muted">Current User</span>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Pagination -->
                <?php if ($total_pages > 1): ?>
                <div class="pagination">
                    <?php if ($page > 1): ?>
                        <a href="?page=<?php echo $page - 1; ?>&role=<?php echo $role_filter; ?>&search=<?php echo urlencode($search); ?>">&laquo; Previous</a>
                    <?php endif; ?>
                    
                    <?php for ($i = max(1, $page - 2); $i <= min($total_pages, $page + 2); $i++): ?>
                        <?php if ($i == $page): ?>
                            <span class="current"><?php echo $i; ?></span>
                        <?php else: ?>
                            <a href="?page=<?php echo $i; ?>&role=<?php echo $role_filter; ?>&search=<?php echo urlencode($search); ?>"><?php echo $i; ?></a>
                        <?php endif; ?>
                    <?php endfor; ?>
                    
                    <?php if ($page < $total_pages): ?>
                        <a href="?page=<?php echo $page + 1; ?>&role=<?php echo $role_filter; ?>&search=<?php echo urlencode($search); ?>">Next &raquo;</a>
                    <?php endif; ?>
                </div>
                <?php endif; ?>
            </div>
        </main>
    </div>

    <!-- Add User Modal -->
    <div id="addUserModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3><i class="fas fa-user-plus"></i> Add New User</h3>
                <button type="button" class="modal-close" onclick="closeModal('addUserModal')">&times;</button>
            </div>
            <form method="POST">
                <input type="hidden" name="action" value="add_user">
                
                <div class="form-group">
                    <label for="username">Username *</label>
                    <input type="text" id="username" name="username" required maxlength="50">
                </div>
                
                <div class="form-group">
                    <label for="email">Email *</label>
                    <input type="email" id="email" name="email" required maxlength="100">
                </div>
                
                <div class="form-group">
                    <label for="first_name">First Name</label>
                    <input type="text" id="first_name" name="first_name" maxlength="50">
                </div>
                
                <div class="form-group">
                    <label for="last_name">Last Name</label>
                    <input type="text" id="last_name" name="last_name" maxlength="50">
                </div>
                
                <div class="form-group">
                    <label for="role">Role *</label>
                    <select id="role" name="role" required>
                        <option value="user">User</option>
                        <option value="editor">Editor</option>
                        <option value="admin">Admin</option>
                    </select>
                </div>
                
                <div class="form-group">
                    <label for="password">Password *</label>
                    <input type="password" id="password" name="password" required minlength="6">
                    <small>Minimum 6 characters</small>
                </div>
                
                <div class="form-group">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-plus"></i> Create User
                    </button>
                    <button type="button" class="btn btn-secondary" onclick="closeModal('addUserModal')">Cancel</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Reset Password Modal -->
    <div id="resetPasswordModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3><i class="fas fa-key"></i> Emergency Password Reset</h3>
                <button type="button" class="modal-close" onclick="closeModal('resetPasswordModal')">&times;</button>
            </div>
            
            <div class="emergency-warning">
                <strong>⚠️ Emergency Action:</strong> You are about to reset the password for user <span id="resetUsername"></span>. This action requires your admin password for verification.
            </div>
            
            <form method="POST">
                <input type="hidden" name="action" value="reset_password">
                <input type="hidden" name="user_id" id="resetUserId">
                
                <div class="form-group">
                    <label for="new_password">New Password *</label>
                    <input type="password" id="new_password" name="new_password" required minlength="6">
                    <small>Minimum 6 characters</small>
                </div>
                
                <div class="form-group">
                    <label for="confirm_admin_password">Your Admin Password (for verification) *</label>
                    <input type="password" id="confirm_admin_password" name="confirm_admin_password" required>
                    <small>Enter your current admin password to confirm this action</small>
                </div>
                
                <div class="form-group">
                    <button type="submit" class="btn btn-warning">
                        <i class="fas fa-key"></i> Reset Password
                    </button>
                    <button type="button" class="btn btn-secondary" onclick="closeModal('resetPasswordModal')">Cancel</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        function updateUserStatus(selectElement, userId) {
            const newStatus = selectElement.value;
            const currentStatus = selectElement.dataset.currentStatus || selectElement.value;
            
            if (confirm(`Are you sure you want to change user status to "${newStatus.charAt(0).toUpperCase() + newStatus.slice(1)}"?`)) {
                // Create a form and submit it
                const form = document.createElement('form');
                form.method = 'POST';
                form.action = window.location.href;
                
                const actionInput = document.createElement('input');
                actionInput.type = 'hidden';
                actionInput.name = 'action';
                actionInput.value = 'update_status';
                form.appendChild(actionInput);
                
                const userIdInput = document.createElement('input');
                userIdInput.type = 'hidden';
                userIdInput.name = 'user_id';
                userIdInput.value = userId;
                form.appendChild(userIdInput);
                
                const statusInput = document.createElement('input');
                statusInput.type = 'hidden';
                statusInput.name = 'status';
                statusInput.value = newStatus;
                form.appendChild(statusInput);
                
                document.body.appendChild(form);
                form.submit();
            } else {
                // Reset to previous value if cancelled
                selectElement.value = currentStatus;
            }
        }
        
        function openModal(modalId) {
            document.getElementById(modalId).style.display = 'block';
        }

        function closeModal(modalId) {
            document.getElementById(modalId).style.display = 'none';
        }

        function openResetPasswordModal(userId, username) {
            document.getElementById('resetUserId').value = userId;
            document.getElementById('resetUsername').textContent = username;
            openModal('resetPasswordModal');
        }

        // Close modal when clicking outside
        window.onclick = function(event) {
            const modals = document.getElementsByClassName('modal');
            for (let modal of modals) {
                if (event.target == modal) {
                    modal.style.display = 'none';
                }
            }
        }

        // Auto-hide alerts after 5 seconds
        document.addEventListener('DOMContentLoaded', function() {
            const alerts = document.querySelectorAll('.alert');
            alerts.forEach(alert => {
                setTimeout(() => {
                    alert.style.opacity = '0';
                    alert.style.transition = 'opacity 0.5s';
                    setTimeout(() => alert.remove(), 500);
                }, 5000);
            });
            
            // Store initial status for all selects
            const statusSelects = document.querySelectorAll('.status-select');
            statusSelects.forEach(select => {
                select.dataset.currentStatus = select.value;
            });
        });
    </script>
</body>
</html>
