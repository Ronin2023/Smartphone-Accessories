<?php
/**
 * Special Access Token Management
 * Admin Interface for managing special access tokens with passkey system
 */

session_start();
require_once '../includes/config.php';
require_once '../includes/db_connect.php';
require_once '../includes/functions.php';

// SECURITY: Check authentication
if (!isLoggedIn() || !hasAdminAccess()) {
    redirect('index');
    exit;
}

$manager = getSpecialAccessManager();
$message = '';
$messageType = '';

// Get database connection
$conn = getDB();

// Handle actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validate CSRF token
    if (!validateCSRFToken($_POST['csrf_token'] ?? '')) {
        $message = 'Invalid security token. Please refresh the page and try again.';
        $messageType = 'error';
    } else {
        $action = $_POST['action'] ?? '';
    
    switch ($action) {
        case 'create_token':
            $userId = intval($_POST['user_id'] ?? 0);
            $name = trim($_POST['name'] ?? '');
            $email = trim($_POST['email'] ?? '');
            $description = trim($_POST['description'] ?? '');
            
            if (empty($userId) || empty($name) || empty($email)) {
                $message = 'User, name and email are required';
                $messageType = 'error';
            } else {
                $result = $manager->createToken($name, $email, $description, $_SESSION['user_id'] ?? null, $userId);
                
                if ($result['success']) {
                    $message = 'Token created successfully!';
                    $messageType = 'success';
                    $_SESSION['new_token'] = $result;
                } else {
                    $message = 'Failed to create token: ' . ($result['error'] ?? 'Unknown error');
                    $messageType = 'error';
                }
            }
            break;
            
        case 'revoke_token':
            $tokenId = intval($_POST['token_id'] ?? 0);
            $result = $manager->revokeToken($tokenId);
            
            if ($result['success']) {
                $message = 'Token revoked successfully';
                $messageType = 'success';
            } else {
                $message = 'Failed to revoke token: ' . ($result['error'] ?? 'Unknown error');
                $messageType = 'error';
            }
            break;
            
        case 'reactivate_token':
            $tokenId = intval($_POST['token_id'] ?? 0);
            $result = $manager->reactivateToken($tokenId);
            
            if ($result['success']) {
                $message = 'Token reactivated successfully';
                $messageType = 'success';
            } else {
                $message = 'Failed to reactivate token: ' . ($result['error'] ?? 'Unknown error');
                $messageType = 'error';
            }
            break;
            
        case 'clear_sessions':
            $tokenId = intval($_POST['token_id'] ?? 0);
            
            try {
                $conn = getDB();
                $stmt = $conn->prepare("UPDATE special_access_sessions SET is_active = 0 WHERE token_id = ?");
                $stmt->execute([$tokenId]);
                $clearedCount = $stmt->rowCount();
                
                // Log the action
                if ($clearedCount > 0) {
                    $logStmt = $conn->prepare("
                        INSERT INTO special_access_logs (token_id, session_id, action, page_url, ip_address, user_agent) 
                        VALUES (?, ?, 'sessions_cleared', '/admin/special-access.php', ?, ?)
                    ");
                    $logStmt->execute([$tokenId, session_id(), $_SERVER['REMOTE_ADDR'] ?? 'unknown', $_SERVER['HTTP_USER_AGENT'] ?? 'unknown']);
                }
                
                $message = "Cleared $clearedCount active session(s) for this token";
                $messageType = 'success';
                
            } catch (Exception $e) {
                $message = 'Failed to clear sessions: ' . $e->getMessage();
                $messageType = 'error';
            }
            break;
            
        case 'cleanup_unknown':
            $result = $manager->cleanupUnknownTokens();
            
            if ($result['success']) {
                $message = $result['message'] ?? ('Cleaned up ' . ($result['deleted'] ?? 0) . ' invalid token(s)');
                $messageType = 'success';
            } else {
                $message = 'Failed to cleanup: ' . ($result['error'] ?? 'Unknown error');
                $messageType = 'error';
            }
            break;
    }
    }
}

// Get all tokens
$tokens = $manager->getAllTokens();

// Get all admin and editor users for dropdown
try {
    $stmt = $conn->query("
        SELECT id, username, email, role, 
               CONCAT(COALESCE(first_name, ''), ' ', COALESCE(last_name, '')) as full_name
        FROM users 
        WHERE role IN ('admin', 'editor') AND is_active = 1
        ORDER BY role DESC, username ASC
    ");
    $availableUsers = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    $availableUsers = [];
    error_log("Failed to fetch users: " . $e->getMessage());
}

// Check for newly created token
$newToken = null;
if (isset($_SESSION['new_token'])) {
    $newToken = $_SESSION['new_token'];
    unset($_SESSION['new_token']);
}

// Calculate statistics
$totalTokens = count($tokens);
$activeTokens = count(array_filter($tokens, fn($t) => ($t['is_active'] ?? 1)));
$revokedTokens = $totalTokens - $activeTokens;
$activeSessions = array_sum(array_map(fn($t) => $t['active_sessions'] ?? 0, $tokens));
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Special Access Management - <?php echo SITE_NAME; ?> Admin</title>
    <link rel="stylesheet" href="../css/admin.css">`n    <link rel="stylesheet" href="../css/admin-dark-mode.css">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">`n    <script src="../js/admin-dark-mode.js"></script>
    <style>
        /* Match Settings Page Exact Styling */
        .dashboard-content {
            padding: 2rem;
            background: #f8f9fa;
            min-height: calc(100vh - 120px);
        }
        
        .settings-container {
            width: 100%;
            padding: 0;
        }
        
        .settings-card {
            background: white;
            border-radius: 12px;
            padding: 2rem;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
            margin-bottom: 2rem;
        }
        
        .settings-card h2 {
            font-size: 1.5rem;
            margin-bottom: 1.5rem;
            color: #1f2937;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        
        .alert {
            padding: 1rem 1.5rem;
            border-radius: 8px;
            margin-bottom: 2rem;
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }
        
        .alert-success {
            background: #d1fae5;
            color: #065f46;
            border: 1px solid #6ee7b7;
        }
        
        .alert-error {
            background: #fee2e2;
            color: #991b1b;
            border: 1px solid #fca5a5;
        }
        
        .info-box {
            background: #eff6ff;
            border: 1px solid #bfdbfe;
            border-radius: 8px;
            padding: 1rem;
            margin-bottom: 1.5rem;
        }
        
        .info-box h4 {
            color: #1e40af;
            margin-bottom: 0.5rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
            font-size: 1rem;
        }
        
        .info-box ul {
            margin-left: 1.5rem;
            color: #1e3a8a;
            font-size: 0.875rem;
        }
        
        .info-box li {
            margin-bottom: 0.25rem;
        }
        
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1rem;
            margin-bottom: 2rem;
        }
        
        .stat-card {
            background: white;
            padding: 1.5rem;
            border-radius: 8px;
            border: 1px solid #e5e7eb;
            text-align: center;
        }
        
        .stat-value {
            font-size: 2rem;
            font-weight: 700;
            color: #1f2937;
            margin-bottom: 0.5rem;
        }
        
        .stat-label {
            font-size: 0.875rem;
            color: #6b7280;
        }
        
        .form-group {
            margin-bottom: 1.5rem;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 600;
            color: #374151;
            font-size: 0.875rem;
        }
        
        .form-group input,
        .form-group textarea {
            width: 100%;
            padding: 0.75rem;
            border: 1px solid #d1d5db;
            border-radius: 6px;
            font-size: 0.875rem;
            transition: all 0.3s ease;
        }
        
        .form-group input:focus,
        .form-group textarea:focus {
            outline: none;
            border-color: #3b82f6;
            box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
        }
        
        .btn {
            padding: 0.75rem 1.5rem;
            border-radius: 6px;
            border: none;
            cursor: pointer;
            font-weight: 600;
            font-size: 0.875rem;
            transition: all 0.3s ease;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
        }
        
        .btn-primary {
            background: #3b82f6;
            color: white;
        }
        
        .btn-primary:hover {
            background: #2563eb;
        }
        
        .btn-danger {
            background: #ef4444;
            color: white;
        }
        
        .btn-danger:hover {
            background: #dc2626;
        }
        
        .btn-success {
            background: #22c55e;
            color: white;
        }
        
        .btn-success:hover {
            background: #16a34a;
        }
        
        .btn-warning {
            background: #f59e0b;
            color: white;
        }
        
        .btn-warning:hover {
            background: #d97706;
        }
        
        .btn-sm {
            padding: 0.5rem 1rem;
            font-size: 0.8125rem;
        }
        
        .token-card {
            background: white;
            border: 1px solid #e5e7eb;
            border-radius: 8px;
            padding: 1.5rem;
            margin-bottom: 1rem;
        }
        
        .token-card.revoked {
            opacity: 0.6;
            background: #f9fafb;
        }
        
        .token-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1rem;
        }
        
        .token-name {
            font-size: 1.125rem;
            font-weight: 600;
            color: #1f2937;
        }
        
        .token-status {
            padding: 0.25rem 0.75rem;
            border-radius: 20px;
            font-size: 0.75rem;
            font-weight: 600;
        }
        
        .status-active {
            background: #d1fae5;
            color: #065f46;
        }
        
        .status-revoked {
            background: #fee2e2;
            color: #991b1b;
        }
        
        .status-with-session {
            background: #dbeafe;
            color: #1e40af;
        }
        
        .token-info {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1rem;
            margin-bottom: 1rem;
        }
        
        .info-item {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            font-size: 0.875rem;
            color: #6b7280;
        }
        
        .info-item i {
            color: #9ca3af;
            width: 16px;
        }
        
        .token-description {
            margin: 1rem 0;
            padding: 0.75rem;
            background: #f9fafb;
            border-radius: 4px;
            font-size: 0.875rem;
            color: #6b7280;
        }
        
        .token-actions {
            display: flex;
            gap: 0.5rem;
            flex-wrap: wrap;
        }
        
        .empty-state {
            text-align: center;
            padding: 3rem 1rem;
            color: #9ca3af;
        }
        
        .empty-state i {
            font-size: 4rem;
            margin-bottom: 1rem;
            opacity: 0.3;
        }
        
        .empty-state p {
            margin: 0.5rem 0;
        }
        
        .empty-state p:first-of-type {
            font-size: 1.125rem;
            font-weight: 600;
            color: #6b7280;
        }
        
        /* Modal Styles */
        .modal-overlay {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0, 0, 0, 0.5);
            display: flex;
            align-items: center;
            justify-content: center;
            z-index: 10000;
            animation: fadeIn 0.3s;
        }
        
        .modal-content {
            background: white;
            padding: 2rem;
            border-radius: 12px;
            max-width: 600px;
            width: 90%;
            max-height: 90vh;
            overflow-y: auto;
            box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1);
            animation: slideIn 0.3s;
        }
        
        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }
        
        @keyframes slideIn {
            from {
                opacity: 0;
                transform: translateY(-20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        .modal-header {
            text-align: center;
            margin-bottom: 2rem;
        }
        
        .modal-header i {
            font-size: 3rem;
            color: #22c55e;
            margin-bottom: 1rem;
        }
        
        .modal-header h2 {
            color: #1f2937;
            margin-bottom: 0.5rem;
        }
        
        .credential-box {
            background: #f9fafb;
            padding: 1.5rem;
            border-radius: 8px;
            margin: 1.5rem 0;
        }
        
        .credential-item {
            margin-bottom: 1.5rem;
        }
        
        .credential-item:last-child {
            margin-bottom: 0;
        }
        
        .credential-label {
            font-size: 0.75rem;
            color: #6b7280;
            margin-bottom: 0.5rem;
            font-weight: 600;
            text-transform: uppercase;
        }
        
        .credential-value {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            background: white;
            padding: 1rem;
            border-radius: 6px;
            border: 1px solid #e5e7eb;
        }
        
        .credential-text {
            flex: 1;
            font-family: 'Courier New', monospace;
            font-size: 0.875rem;
            color: #1f2937;
            word-break: break-all;
        }
        
        .copy-btn {
            background: #3b82f6;
            color: white;
            border: none;
            padding: 0.5rem 1rem;
            border-radius: 4px;
            cursor: pointer;
            font-size: 0.75rem;
            white-space: nowrap;
            transition: all 0.3s;
        }
        
        .copy-btn:hover {
            background: #2563eb;
        }
        
        .copy-btn.copied {
            background: #22c55e;
        }
        
        .warning-box {
            background: #fef3c7;
            border: 1px solid #fbbf24;
            padding: 1rem;
            margin: 1.5rem 0;
            border-radius: 8px;
        }
        
        .warning-box strong {
            display: block;
            margin-bottom: 0.5rem;
            color: #92400e;
        }
        
        .warning-box ul {
            margin: 0;
            padding-left: 1.25rem;
            color: #92400e;
            font-size: 0.875rem;
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
                <p class="admin-welcome">
                    Welcome, <?php echo htmlspecialchars($_SESSION['username'] ?? 'User'); ?>
                    <small style="display: block; font-size: 0.8em; opacity: 0.8; margin-top: 2px;">
                        <?php echo getUserRoleDisplay($_SESSION['user_role'] ?? 'editor'); ?>
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
                    <li class="nav-item">
                        <a href="settings" class="nav-link">
                            <i class="fas fa-cog"></i>
                            <span>Settings</span>
                        </a>
                    </li>
                    <li class="nav-item active">
                        <a href="special-access" class="nav-link">
                            <i class="fas fa-key"></i>
                            <span>Special Access</span>
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
                        <a href="logout" class="nav-link" style="color: #dc3545 !important;">
                            <i class="fas fa-sign-out-alt"></i>
                            <span>Logout</span>
                        </a>
                    </li>
                </ul>
            </nav>
        </aside>

        <!-- Main Content -->
        <main class="admin-main">
            <div class="admin-header">
                <h1 class="page-title"><i class="fas fa-key"></i> Special Access Management</h1>
                <div class="header-actions">
                    <span class="current-time">
                        <?php echo date('M d, Y - g:i A'); ?>
                    </span>
                </div>
            </div>

            <div class="dashboard-content">
                <div class="settings-container">
                    
                    <!-- Alert Messages -->
                    <?php if ($message): ?>
                    <div class="alert alert-<?php echo $messageType; ?>">
                        <i class="fas fa-<?php echo $messageType === 'success' ? 'check-circle' : 'exclamation-circle'; ?>"></i>
                        <span><?php echo htmlspecialchars($message); ?></span>
                    </div>
                    <?php endif; ?>

                    <!-- Info Box -->
                    <div class="info-box">
                        <h4><i class="fas fa-info-circle"></i> How Special Access Works</h4>
                        <ul>
                            <li><strong>Step 1:</strong> Create a token - generates unique access link + passkey</li>
                            <li><strong>Step 2:</strong> Share both credentials with developer/editor</li>
                            <li><strong>Step 3:</strong> User opens link, enters passkey, gets full site access</li>
                            <li><strong>Security:</strong> Only 1 active session per passkey, admin can revoke anytime</li>
                            <li><strong>Duration:</strong> Access lasts until maintenance mode ends</li>
                        </ul>
                    </div>

                    <!-- Statistics -->
                    <div class="stats-grid">
                        <div class="stat-card">
                            <div class="stat-value"><?php echo $totalTokens; ?></div>
                            <div class="stat-label">Total Tokens</div>
                        </div>
                        <div class="stat-card">
                            <div class="stat-value"><?php echo $activeTokens; ?></div>
                            <div class="stat-label">Active Tokens</div>
                        </div>
                        <div class="stat-card">
                            <div class="stat-value"><?php echo $activeSessions; ?></div>
                            <div class="stat-label">Active Sessions</div>
                        </div>
                        <div class="stat-card">
                            <div class="stat-value"><?php echo $revokedTokens; ?></div>
                            <div class="stat-label">Revoked Tokens</div>
                        </div>
                    </div>

                    <!-- Create New Token Form -->
                    <div class="settings-card">
                        <h2><i class="fas fa-plus-circle"></i> Create New Token</h2>
                        
                        <form method="POST" id="tokenForm" style="max-width: 600px;">
                            <!-- CSRF Protection -->
                            <?php echo csrfField(); ?>
                            
                            <input type="hidden" name="action" value="create_token">
                            
                            <div class="form-group">
                                <label>Select User (Admin/Editor) *</label>
                                <select name="user_id" id="userSelect" required onchange="updateUserInfo()">
                                    <option value="">-- Select a user --</option>
                                    <?php foreach ($availableUsers as $user): ?>
                                        <?php 
                                            $displayName = trim($user['full_name']) !== '' ? $user['full_name'] : $user['username'];
                                            $roleIcon = $user['role'] === 'admin' ? '👑' : '✏️';
                                        ?>
                                        <option value="<?php echo $user['id']; ?>" 
                                                data-username="<?php echo htmlspecialchars($user['username']); ?>"
                                                data-email="<?php echo htmlspecialchars($user['email']); ?>"
                                                data-fullname="<?php echo htmlspecialchars($displayName); ?>"
                                                data-role="<?php echo htmlspecialchars($user['role']); ?>">
                                            <?php echo $roleIcon; ?> <?php echo htmlspecialchars($displayName); ?> (<?php echo htmlspecialchars($user['username']); ?>) - <?php echo ucfirst($user['role']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                                <?php if (empty($availableUsers)): ?>
                                    <small style="color: #ef4444;">⚠️ No admin or editor users found. Please create users first.</small>
                                <?php endif; ?>
                            </div>
                            
                            <div class="form-group">
                                <label>Name / Role *</label>
                                <input type="text" name="name" id="userName" required placeholder="Will be auto-filled" readonly style="background: #f3f4f6;">
                            </div>
                            
                            <div class="form-group">
                                <label>Email *</label>
                                <input type="email" name="email" id="userEmail" required placeholder="Will be auto-filled" readonly style="background: #f3f4f6;">
                            </div>
                            
                            <div class="form-group">
                                <label>Description (optional)</label>
                                <textarea name="description" rows="2" placeholder="Purpose of this access token (e.g., 'Remote development access', 'Emergency maintenance')"></textarea>
                            </div>
                            
                            <button type="submit" class="btn btn-primary" <?php echo empty($availableUsers) ? 'disabled' : ''; ?>>
                                <i class="fas fa-plus"></i> Generate Token & Passkey
                            </button>
                        </form>
                        
                        <script>
                        function updateUserInfo() {
                            const select = document.getElementById('userSelect');
                            const selectedOption = select.options[select.selectedIndex];
                            
                            if (selectedOption.value) {
                                const fullName = selectedOption.dataset.fullname;
                                const username = selectedOption.dataset.username;
                                const email = selectedOption.dataset.email;
                                const role = selectedOption.dataset.role;
                                
                                // Update name field with full name or username + role
                                document.getElementById('userName').value = fullName + ' - ' + role.charAt(0).toUpperCase() + role.slice(1);
                                document.getElementById('userEmail').value = email;
                            } else {
                                document.getElementById('userName').value = '';
                                document.getElementById('userEmail').value = '';
                            }
                        }
                        </script>
                    </div>

                    <!-- Existing Tokens -->
                    <div class="settings-card">
                        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
                            <h2 style="margin: 0;"><i class="fas fa-list"></i> Access Tokens (<?php echo count($tokens); ?>)</h2>
                            <?php if (!empty($tokens)): ?>
                                <form method="POST" style="display: inline;" onsubmit="return confirm('Delete all tokens with name \'Unknown\'?\n\nThis action cannot be undone.')">
                                    <?php echo csrfField(); ?>
                                    <input type="hidden" name="action" value="cleanup_unknown">
                                    <button type="submit" class="btn" style="background: #ef4444; color: white; padding: 8px 16px; font-size: 14px;">
                                        <i class="fas fa-trash-alt"></i> Cleanup Unknown Tokens
                                    </button>
                                </form>
                            <?php endif; ?>
                        </div>
                        
                        <?php if (empty($tokens)): ?>
                            <div class="empty-state">
                                <i class="fas fa-key"></i>
                                <p>No tokens created yet</p>
                                <p>Create your first special access token to get started</p>
                            </div>
                        <?php else: ?>
                            <?php foreach ($tokens as $token): ?>
                                <div class="token-card <?php echo ($token['is_active'] ?? 1) ? '' : 'revoked'; ?>">
                                    <div class="token-header">
                                        <div class="token-name">
                                            <i class="fas fa-user"></i> <?php echo htmlspecialchars($token['name'] ?? 'Unknown'); ?>
                                            <?php if (!empty($token['user_username'])): ?>
                                                <span style="font-size: 13px; color: #6b7280; margin-left: 8px;">
                                                    (@<?php echo htmlspecialchars($token['user_username']); ?>)
                                                </span>
                                            <?php endif; ?>
                                        </div>
                                        <div>
                                            <?php if (($token['is_active'] ?? 1)): ?>
                                                <?php if (($token['active_sessions'] ?? 0) > 0): ?>
                                                    <span class="token-status status-with-session">
                                                        <i class="fas fa-circle"></i> Active Session
                                                    </span>
                                                <?php else: ?>
                                                    <span class="token-status status-active">
                                                        <i class="fas fa-check"></i> Active
                                                    </span>
                                                <?php endif; ?>
                                            <?php else: ?>
                                                <span class="token-status status-revoked">
                                                    <i class="fas fa-ban"></i> Revoked
                                                </span>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                    
                                    <div class="token-info">
                                        <?php if (!empty($token['user_role'])): ?>
                                            <div class="info-item">
                                                <i class="fas fa-id-badge"></i>
                                                <span>Role: <strong><?php echo ucfirst($token['user_role']); ?></strong></span>
                                            </div>
                                        <?php endif; ?>
                                        <?php if (!empty($token['email'] ?? '')): ?>
                                            <div class="info-item">
                                                <i class="fas fa-envelope"></i>
                                                <span><?php echo htmlspecialchars($token['email'] ?? ''); ?></span>
                                            </div>
                                        <?php endif; ?>
                                        <div class="info-item">
                                            <i class="fas fa-calendar"></i>
                                            <span>Created: <?php echo date('M j, Y', strtotime($token['created_at'] ?? 'now')); ?></span>
                                        </div>
                                        <div class="info-item">
                                            <i class="fas fa-clock"></i>
                                            <span>Used: <?php echo $token['usage_count'] ?? 0; ?> times</span>
                                        </div>
                                        <?php if (!empty($token['last_used_at'] ?? '')): ?>
                                            <div class="info-item">
                                                <i class="fas fa-history"></i>
                                                <span>Last: <?php echo date('M j, Y H:i', strtotime($token['last_used_at'])); ?></span>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                    
                                    <?php if (!empty($token['description'] ?? '')): ?>
                                        <div class="token-description">
                                            <i class="fas fa-info-circle"></i> <?php echo htmlspecialchars($token['description'] ?? ''); ?>
                                        </div>
                                    <?php endif; ?>
                                    
                                    <div class="token-actions">
                                        <button onclick="viewToken('<?php echo htmlspecialchars($token['token'], ENT_QUOTES); ?>', '<?php echo htmlspecialchars($token['passkey'], ENT_QUOTES); ?>')" 
                                                class="btn btn-primary btn-sm">
                                            <i class="fas fa-eye"></i> View Credentials
                                        </button>
                                        
                                        <?php if (($token['is_active'] ?? 1)): ?>
                                            <form method="POST" style="display: inline;" 
                                                  onsubmit="return confirm('Revoke access for <?php echo htmlspecialchars($token['name'] ?? 'this user', ENT_QUOTES); ?>?\n\nThis will immediately terminate their session.')">
                                                <input type="hidden" name="action" value="revoke_token">
                                                <input type="hidden" name="token_id" value="<?php echo $token['id']; ?>">
                                                <button type="submit" class="btn btn-danger btn-sm">
                                                    <i class="fas fa-ban"></i> Revoke Access
                                                </button>
                                            </form>
                                            
                                            <?php if (($token['active_sessions'] ?? 0) > 0): ?>
                                                <form method="POST" style="display: inline;" 
                                                      onsubmit="return confirm('Clear all active sessions for <?php echo htmlspecialchars($token['name'] ?? 'this user', ENT_QUOTES); ?>?\n\nThis will log them out immediately but keep the token active.')">
                                                    <input type="hidden" name="action" value="clear_sessions">
                                                    <input type="hidden" name="token_id" value="<?php echo $token['id']; ?>">
                                                    <button type="submit" class="btn btn-warning btn-sm" title="Clear <?php echo $token['active_sessions']; ?> active session(s)">
                                                        <i class="fas fa-users-slash"></i> Clear Sessions (<?php echo $token['active_sessions']; ?>)
                                                    </button>
                                                </form>
                                            <?php endif; ?>
                                        <?php else: ?>
                                            <form method="POST" style="display: inline;">
                                                <input type="hidden" name="action" value="reactivate_token">
                                                <input type="hidden" name="token_id" value="<?php echo $token['id']; ?>">
                                                <button type="submit" class="btn btn-success btn-sm">
                                                    <i class="fas fa-check"></i> Reactivate
                                                </button>
                                            </form>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <!-- New Token Modal -->
    <?php if ($newToken): ?>
        <div class="modal-overlay" id="newTokenModal">
            <div class="modal-content">
                <div class="modal-header">
                    <i class="fas fa-check-circle"></i>
                    <h2>Token Created Successfully!</h2>
                    <p>Share these credentials with <strong><?php echo htmlspecialchars($newToken['name']); ?></strong></p>
                </div>

                <div class="credential-box">
                    <div class="credential-item">
                        <div class="credential-label">🔗 Access Link</div>
                        <div class="credential-value">
                            <span class="credential-text" id="accessLink">
                                <?php echo SITE_URL; ?>?special_access_token=<?php echo htmlspecialchars($newToken['token']); ?>
                            </span>
                            <button class="copy-btn" onclick="copyToClipboard('accessLink', this)">
                                <i class="fas fa-copy"></i> Copy
                            </button>
                        </div>
                    </div>

                    <div class="credential-item">
                        <div class="credential-label">🔑 Passkey</div>
                        <div class="credential-value">
                            <span class="credential-text" id="passkey">
                                <?php echo htmlspecialchars($newToken['passkey']); ?>
                            </span>
                            <button class="copy-btn" onclick="copyToClipboard('passkey', this)">
                                <i class="fas fa-copy"></i> Copy
                            </button>
                        </div>
                    </div>
                </div>

                <div class="warning-box">
                    <strong><i class="fas fa-exclamation-triangle"></i> Important!</strong>
                    <ul>
                        <li>Save these credentials now - you won't see them again</li>
                        <li>User must use <strong>BOTH</strong> the access link AND passkey</li>
                        <li>Only one active session allowed per passkey</li>
                        <li>Access lasts until maintenance mode ends</li>
                        <li>You can revoke access anytime from this page</li>
                    </ul>
                </div>

                <button onclick="document.getElementById('newTokenModal').remove()" class="btn btn-primary" style="width: 100%;">
                    <i class="fas fa-check"></i> Got It!
                </button>
            </div>
        </div>
    <?php endif; ?>

    <script>
        function copyToClipboard(elementId, button) {
            const element = document.getElementById(elementId);
            const text = element.textContent.trim();
            
            navigator.clipboard.writeText(text).then(() => {
                const originalHTML = button.innerHTML;
                button.innerHTML = '<i class="fas fa-check"></i> Copied!';
                button.classList.add('copied');
                
                setTimeout(() => {
                    button.innerHTML = originalHTML;
                    button.classList.remove('copied');
                }, 2000);
            }).catch(err => {
                alert('Failed to copy. Please copy manually.');
            });
        }

        function viewToken(token, passkey) {
            const accessLink = '<?php echo SITE_URL; ?>?special_access_token=' + encodeURIComponent(token);
            
            const modal = document.createElement('div');
            modal.className = 'modal-overlay';
            modal.innerHTML = `
                <div class="modal-content">
                    <div class="modal-header">
                        <i class="fas fa-key"></i>
                        <h2>Token Credentials</h2>
                    </div>

                    <div class="credential-box">
                        <div class="credential-item">
                            <div class="credential-label">🔗 Access Link</div>
                            <div class="credential-value">
                                <span class="credential-text" id="viewAccessLink">${accessLink}</span>
                                <button class="copy-btn" onclick="copyToClipboard('viewAccessLink', this)">
                                    <i class="fas fa-copy"></i> Copy
                                </button>
                            </div>
                        </div>

                        <div class="credential-item">
                            <div class="credential-label">🔑 Passkey</div>
                            <div class="credential-value">
                                <span class="credential-text" id="viewPasskey">${passkey}</span>
                                <button class="copy-btn" onclick="copyToClipboard('viewPasskey', this)">
                                    <i class="fas fa-copy"></i> Copy
                                </button>
                            </div>
                        </div>
                    </div>

                    <button onclick="this.closest('.modal-overlay').remove()" class="btn btn-primary" style="width: 100%;">
                        <i class="fas fa-times"></i> Close
                    </button>
                </div>
            `;
            
            document.body.appendChild(modal);
            
            // Close on background click
            modal.addEventListener('click', (e) => {
                if (e.target === modal) {
                    modal.remove();
                }
            });
        }

        // Close modal on Escape key
        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape') {
                const modals = document.querySelectorAll('.modal-overlay');
                modals.forEach(modal => modal.remove());
            }
        });
    </script>
</body>
</html>
