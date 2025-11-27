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
            background: rgba(0, 0, 0, 0.6);
            backdrop-filter: blur(4px);
            animation: fadeIn 0.3s ease;
        }
        
        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }
        
        @keyframes slideDown {
            from {
                opacity: 0;
                transform: translateY(-50px) scale(0.95);
            }
            to {
                opacity: 1;
                transform: translateY(0) scale(1);
            }
        }
        
        .modal-content {
            background: linear-gradient(135deg, #ffffff 0%, #f8f9fa 100%);
            margin: 3% auto;
            padding: 0;
            border-radius: 16px;
            width: 90%;
            max-width: 500px;
            max-height: 85vh;
            overflow: hidden;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3), 0 0 0 1px rgba(0, 0, 0, 0.05);
            animation: slideDown 0.4s cubic-bezier(0.34, 1.56, 0.64, 1);
        }
        
        .modal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 1.5rem 2rem;
            background: linear-gradient(135deg, var(--primary-color) 0%, #0056b3 100%);
            color: white;
            border-bottom: none;
        }
        
        .modal-header h3 {
            margin: 0;
            font-size: 1.25rem;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }
        
        .modal-header i {
            font-size: 1.4rem;
        }
        
        .modal-close {
            background: rgba(255, 255, 255, 0.2);
            border: none;
            width: 36px;
            height: 36px;
            border-radius: 50%;
            font-size: 1.25rem;
            cursor: pointer;
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: all 0.3s ease;
        }
        
        .modal-close:hover {
            background: rgba(255, 255, 255, 0.3);
            transform: rotate(90deg);
        }
        
        .modal-body {
            padding: 2rem;
            background: white;
            max-height: 60vh;
            overflow-y: auto;
        }
        
        .modal-body::-webkit-scrollbar {
            width: 8px;
        }
        
        .modal-body::-webkit-scrollbar-track {
            background: #f1f1f1;
            border-radius: 10px;
        }
        
        .modal-body::-webkit-scrollbar-thumb {
            background: #cbd5e0;
            border-radius: 10px;
        }
        
        .modal-body::-webkit-scrollbar-thumb:hover {
            background: #a0aec0;
        }
        
        .form-group {
            margin-bottom: 1.5rem;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 0.6rem;
            font-weight: 600;
            color: #2c3e50;
            font-size: 0.9rem;
            letter-spacing: 0.3px;
        }
        
        .form-group input,
        .form-group select {
            width: 100%;
            padding: 0.85rem 1rem;
            border: 2px solid #e2e8f0;
            border-radius: 10px;
            font-size: 0.95rem;
            transition: all 0.3s ease;
            background: #f8f9fa;
        }
        
        .form-group input:focus,
        .form-group select:focus {
            outline: none;
            border-color: var(--primary-color);
            background: white;
            box-shadow: 0 0 0 4px rgba(0, 123, 255, 0.1);
        }
        
        .form-group small {
            display: block;
            margin-top: 0.5rem;
            color: #6c757d;
            font-size: 0.85rem;
        }
        
        .emergency-warning {
            background: linear-gradient(135deg, #fff9e6 0%, #fffbf0 100%);
            border: 2px solid #ffd93d;
            border-left: 5px solid #ffc107;
            color: #856404;
            padding: 1.25rem;
            border-radius: 10px;
            margin-bottom: 1.5rem;
            box-shadow: 0 2px 8px rgba(255, 193, 7, 0.15);
        }
        
        .emergency-warning strong {
            display: block;
            margin-bottom: 0.5rem;
            font-size: 1.05rem;
        }
        
        .modal-footer {
            padding: 1.5rem 2rem;
            background: #f8f9fa;
            border-top: 1px solid #e9ecef;
            display: flex;
            gap: 1rem;
            justify-content: flex-end;
        }
        
        .modal-footer .btn {
            padding: 0.75rem 1.5rem;
            font-weight: 500;
            border-radius: 8px;
            transition: all 0.3s ease;
            border: none;
            cursor: pointer;
            font-size: 0.95rem;
        }
        
        .modal-footer .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(0, 0, 0, 0.15);
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
        
        .confirmation-modal .modal-content {
            max-width: 450px;
        }
        
        .confirmation-message {
            padding: 3rem 2rem 2rem;
            text-align: center;
            font-size: 1.05rem;
            background: white;
        }
        
        .confirmation-message i {
            font-size: 4rem;
            margin-bottom: 1.5rem;
            display: block;
            animation: iconPulse 0.6s ease;
        }
        
        @keyframes iconPulse {
            0%, 100% { transform: scale(1); }
            50% { transform: scale(1.1); }
        }
        
        .confirmation-message.danger i {
            color: #dc3545;
            filter: drop-shadow(0 4px 8px rgba(220, 53, 69, 0.3));
        }
        
        .confirmation-message.warning i {
            color: #ffc107;
            filter: drop-shadow(0 4px 8px rgba(255, 193, 7, 0.3));
        }
        
        .confirmation-message.info i {
            color: #17a2b8;
            filter: drop-shadow(0 4px 8px rgba(23, 162, 184, 0.3));
        }
        
        .confirmation-message p {
            font-size: 1.1rem;
            color: #2c3e50;
            margin: 0 0 1rem;
            line-height: 1.6;
        }
        
        .confirmation-message small {
            display: block;
            color: #6c757d;
            margin-top: 0.75rem;
            font-size: 0.9rem;
        }
        
        .confirmation-message strong {
            color: var(--primary-color);
            font-weight: 600;
        }
        
        .modal-actions {
            display: flex;
            gap: 1rem;
            justify-content: center;
            padding: 0 2rem 2rem;
            background: white;
        }
        
        .modal-actions .btn {
            min-width: 120px;
            padding: 0.75rem 1.5rem;
            font-weight: 500;
            border-radius: 8px;
            transition: all 0.3s ease;
            border: none;
            cursor: pointer;
            font-size: 0.95rem;
        }
        
        .modal-actions .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(0, 0, 0, 0.15);
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
                                                <button type="button" class="btn btn-sm <?php echo $user['is_active'] ? 'btn-secondary' : 'btn-success'; ?>" 
                                                        onclick="openToggleStatusModal(<?php echo $user['id']; ?>, '<?php echo htmlspecialchars($user['username'], ENT_QUOTES); ?>', <?php echo $user['is_active'] ? 'true' : 'false'; ?>)">
                                                    <i class="fas fa-<?php echo $user['is_active'] ? 'ban' : 'check'; ?>"></i>
                                                </button>
                                                <button type="button" class="btn btn-sm btn-danger" 
                                                        onclick="openDeleteUserModal(<?php echo $user['id']; ?>, '<?php echo htmlspecialchars($user['username'], ENT_QUOTES); ?>')">
                                                    <i class="fas fa-trash"></i>
                                                </button>
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
                
                <div class="modal-body">
                    <div class="form-group">
                        <label for="username"><i class="fas fa-user"></i> Username *</label>
                        <input type="text" id="username" name="username" required maxlength="50" placeholder="Enter username">
                    </div>
                    
                    <div class="form-group">
                        <label for="email"><i class="fas fa-envelope"></i> Email Address *</label>
                        <input type="email" id="email" name="email" required maxlength="100" placeholder="user@example.com">
                    </div>
                    
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
                        <div class="form-group">
                            <label for="first_name"><i class="fas fa-id-card"></i> First Name</label>
                            <input type="text" id="first_name" name="first_name" maxlength="50" placeholder="First name">
                        </div>
                        
                        <div class="form-group">
                            <label for="last_name">Last Name</label>
                            <input type="text" id="last_name" name="last_name" maxlength="50" placeholder="Last name">
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="role"><i class="fas fa-user-tag"></i> Role *</label>
                        <select id="role" name="role" required>
                            <option value="user">User - Basic Access</option>
                            <option value="editor">Editor - Content Management</option>
                            <option value="admin">Admin - Full Access</option>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="password"><i class="fas fa-lock"></i> Password *</label>
                        <input type="password" id="password" name="password" required minlength="6" placeholder="Minimum 6 characters">
                        <small><i class="fas fa-info-circle"></i> Password must be at least 6 characters long</small>
                    </div>
                </div>
                
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" onclick="closeModal('addUserModal')">
                        <i class="fas fa-times"></i> Cancel
                    </button>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-plus-circle"></i> Create User
                    </button>
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
            
            <form method="POST">
                <input type="hidden" name="action" value="reset_password">
                <input type="hidden" name="user_id" id="resetUserId">
                
                <div class="modal-body">
                    <div class="emergency-warning">
                        <strong><i class="fas fa-exclamation-triangle"></i> Emergency Action</strong>
                        You are about to reset the password for user <strong id="resetUsername"></strong>. This action requires your admin password for verification.
                    </div>
                    
                    <div class="form-group">
                        <label for="new_password"><i class="fas fa-lock"></i> New Password *</label>
                        <input type="password" id="new_password" name="new_password" required minlength="6" placeholder="Enter new password">
                        <small><i class="fas fa-info-circle"></i> Minimum 6 characters</small>
                    </div>
                    
                    <div class="form-group">
                        <label for="confirm_admin_password"><i class="fas fa-shield-alt"></i> Your Admin Password *</label>
                        <input type="password" id="confirm_admin_password" name="confirm_admin_password" required placeholder="Enter your admin password">
                        <small><i class="fas fa-info-circle"></i> Required to verify this security action</small>
                    </div>
                </div>
                
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" onclick="closeModal('resetPasswordModal')">
                        <i class="fas fa-times"></i> Cancel
                    </button>
                    <button type="submit" class="btn btn-warning">
                        <i class="fas fa-key"></i> Reset Password
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Status Change Confirmation Modal -->
    <div id="statusChangeModal" class="modal confirmation-modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3><i class="fas fa-exchange-alt"></i> Confirm Status Change</h3>
                <button type="button" class="modal-close" onclick="closeModal('statusChangeModal')">&times;</button>
            </div>
            <div class="confirmation-message info">
                <i class="fas fa-question-circle"></i>
                <p>Change user status to <strong id="statusChangeTarget"></strong>?</p>
                <small>User: <strong id="statusChangeUsername"></strong></small>
            </div>
            <form method="POST" id="statusChangeForm">
                <input type="hidden" name="action" value="update_status">
                <input type="hidden" name="user_id" id="statusChangeUserId">
                <input type="hidden" name="status" id="statusChangeStatus">
                <div class="modal-actions">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-check"></i> Confirm
                    </button>
                    <button type="button" class="btn btn-secondary" onclick="cancelStatusChange()">
                        <i class="fas fa-times"></i> Cancel
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Toggle Active/Inactive Confirmation Modal -->
    <div id="toggleStatusModal" class="modal confirmation-modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3><i class="fas fa-toggle-on"></i> <span id="toggleActionTitle"></span></h3>
                <button type="button" class="modal-close" onclick="closeModal('toggleStatusModal')">&times;</button>
            </div>
            <div class="confirmation-message warning">
                <i class="fas fa-exclamation-triangle"></i>
                <p id="toggleMessage"></p>
                <small>User: <strong id="toggleUsername"></strong></small>
            </div>
            <form method="POST" id="toggleStatusForm">
                <input type="hidden" name="action" value="toggle_active">
                <input type="hidden" name="user_id" id="toggleUserId">
                <div class="modal-actions">
                    <button type="submit" class="btn btn-warning">
                        <i class="fas fa-check"></i> Confirm
                    </button>
                    <button type="button" class="btn btn-secondary" onclick="closeModal('toggleStatusModal')">
                        <i class="fas fa-times"></i> Cancel
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Delete User Confirmation Modal -->
    <div id="deleteUserModal" class="modal confirmation-modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3><i class="fas fa-trash-alt"></i> Confirm Delete User</h3>
                <button type="button" class="modal-close" onclick="closeModal('deleteUserModal')">&times;</button>
            </div>
            <div class="confirmation-message danger">
                <i class="fas fa-exclamation-circle"></i>
                <p>Are you sure you want to delete this user?</p>
                <small>User: <strong id="deleteUsername"></strong></small>
                <div style="margin-top: 1.5rem; padding: 1rem; background: #ffe6e6; border-radius: 8px; border-left: 4px solid #dc3545;">
                    <i class="fas fa-exclamation-circle"></i> <strong>This action cannot be undone!</strong>
                </div>
            </div>
            <form method="POST" id="deleteUserForm">
                <input type="hidden" name="action" value="delete_user">
                <input type="hidden" name="user_id" id="deleteUserId">
                <div class="modal-actions">
                    <button type="submit" class="btn btn-danger">
                        <i class="fas fa-trash"></i> Delete User
                    </button>
                    <button type="button" class="btn btn-secondary" onclick="closeModal('deleteUserModal')">
                        <i class="fas fa-times"></i> Cancel
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script>
        let currentStatusSelect = null;
        
        function updateUserStatus(selectElement, userId) {
            const newStatus = selectElement.value;
            const currentStatus = selectElement.dataset.currentStatus || selectElement.value;
            
            // Store reference to the select element
            currentStatusSelect = selectElement;
            
            // Get username from the row
            const row = selectElement.closest('tr');
            const username = row.querySelector('td strong').textContent;
            
            // Populate modal
            document.getElementById('statusChangeUserId').value = userId;
            document.getElementById('statusChangeStatus').value = newStatus;
            document.getElementById('statusChangeTarget').textContent = newStatus.charAt(0).toUpperCase() + newStatus.slice(1);
            document.getElementById('statusChangeUsername').textContent = username;
            
            // Open modal
            openModal('statusChangeModal');
        }
        
        function cancelStatusChange() {
            // Reset select to previous value
            if (currentStatusSelect) {
                currentStatusSelect.value = currentStatusSelect.dataset.currentStatus;
            }
            closeModal('statusChangeModal');
        }
        
        function openToggleStatusModal(userId, username, isActive) {
            document.getElementById('toggleUserId').value = userId;
            document.getElementById('toggleUsername').textContent = username;
            
            if (isActive) {
                document.getElementById('toggleActionTitle').textContent = 'Deactivate User';
                document.getElementById('toggleMessage').textContent = 'Are you sure you want to deactivate this user?';
            } else {
                document.getElementById('toggleActionTitle').textContent = 'Activate User';
                document.getElementById('toggleMessage').textContent = 'Are you sure you want to activate this user?';
            }
            
            openModal('toggleStatusModal');
        }
        
        function openDeleteUserModal(userId, username) {
            document.getElementById('deleteUserId').value = userId;
            document.getElementById('deleteUsername').textContent = username;
            openModal('deleteUserModal');
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
