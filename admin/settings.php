<?php
/**
 * Admin Settings Page
 * Includes Maintenance Mode Control
 * SECURITY: Only accessible by logged-in admins
 */

session_start();
require_once '../includes/config.php';
require_once '../includes/db_connect.php';
require_once '../includes/functions.php';
require_once '../includes/cache-manager.php';

// SECURITY: Check authentication - Admin access only
if (!isLoggedIn() || !hasAdminAccess()) {
    header('Location: login.php');
    exit;
}

$message = '';
$messageType = '';

// Get current maintenance status
function getMaintenanceStatus() {
    try {
        $pdo = getDB();
        $stmt = $pdo->prepare("SELECT setting_value FROM settings WHERE setting_key = 'maintenance_enabled'");
        $stmt->execute();
        $result = $stmt->fetch();
        return $result ? (bool)$result['setting_value'] : false;
    } catch (Exception $e) {
        return false;
    }
}

// Get maintenance settings
function getMaintenanceSettings() {
    try {
        $pdo = getDB();
        $stmt = $pdo->prepare("SELECT setting_key, setting_value FROM settings WHERE setting_key LIKE 'maintenance_%'");
        $stmt->execute();
        $results = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);
        return $results;
    } catch (Exception $e) {
        return [];
    }
}

$maintenanceEnabled = getMaintenanceStatus();
$maintenanceSettings = getMaintenanceSettings();

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    try {
        $pdo = getDB();
        
        switch ($action) {
            case 'enable_maintenance':
                // Enable maintenance mode
                $hours = intval($_POST['duration_hours'] ?? 3);
                $end_time = time() + ($hours * 60 * 60);
                
                $settings = [
                    'maintenance_enabled' => '1',
                    'maintenance_title' => $_POST['title'] ?? 'Site Under Maintenance',
                    'maintenance_message' => $_POST['message'] ?? 'We\'re performing scheduled maintenance to improve your experience.',
                    'maintenance_start_time' => time(),
                    'maintenance_end_time' => $end_time,
                    'maintenance_contact_email' => $_POST['contact_email'] ?? 'support@techcompare.com'
                ];
                
                foreach ($settings as $key => $value) {
                    $stmt = $pdo->prepare("
                        INSERT INTO settings (setting_key, setting_value, description) 
                        VALUES (?, ?, ?)
                        ON DUPLICATE KEY UPDATE setting_value = ?
                    ");
                    $stmt->execute([$key, $value, 'Maintenance mode setting', $value]);
                }
                
                // Create .htaccess rule
                $htaccess_rule = "
# Maintenance Mode - Auto Generated
RewriteEngine On
# Skip maintenance for admin area
RewriteCond %{REQUEST_URI} ^/Smartphone-Accessories/admin/ [OR]
RewriteCond %{REQUEST_URI} ^/Smartphone-Accessories/admin$ [OR]
# Skip maintenance for maintenance files
RewriteCond %{REQUEST_URI} ^/Smartphone-Accessories/maintenance\\.php$ [OR]
RewriteCond %{REQUEST_URI} ^/Smartphone-Accessories/403\\.html$ [OR]
# Skip maintenance for assets
RewriteCond %{REQUEST_URI} ^/Smartphone-Accessories/css/ [OR]
RewriteCond %{REQUEST_URI} ^/Smartphone-Accessories/js/ [OR]
RewriteCond %{REQUEST_URI} ^/Smartphone-Accessories/assets/ [OR]
RewriteCond %{REQUEST_URI} ^/Smartphone-Accessories/uploads/ [OR]
# Skip maintenance for special access or admin bypass
RewriteCond %{QUERY_STRING} special_access= [OR]
RewriteCond %{QUERY_STRING} admin_bypass=1
RewriteRule ^ - [S=1]
# Redirect everything else to maintenance (without 503 in redirect)
RewriteRule ^(.*)$ /Smartphone-Accessories/maintenance.php [L]
# End Maintenance Mode
";
                
                $htaccess_file = '../.htaccess';
                $current_htaccess = file_exists($htaccess_file) ? file_get_contents($htaccess_file) : '';
                
                // Remove existing maintenance rules - loop to remove all
                $attempts = 0;
                while (strpos($current_htaccess, '# Maintenance Mode - Auto Generated') !== false && $attempts < 10) {
                    $new_content = preg_replace('/# Maintenance Mode - Auto Generated.*?# End Maintenance Mode\s*/s', '', $current_htaccess);
                    if ($new_content === $current_htaccess) {
                        break;
                    }
                    $current_htaccess = $new_content;
                    $attempts++;
                }
                
                // Add new rule
                $new_htaccess = $htaccess_rule . "\n" . $current_htaccess;
                file_put_contents($htaccess_file, $new_htaccess);
                
                $message = 'Maintenance mode ENABLED successfully! Site is now in maintenance mode.';
                $messageType = 'success';
                $maintenanceEnabled = true;
                break;
                
            case 'disable_maintenance':
                // Disable maintenance mode
                $stmt = $pdo->prepare("
                    INSERT INTO settings (setting_key, setting_value, description) 
                    VALUES ('maintenance_enabled', '0', 'Maintenance mode setting')
                    ON DUPLICATE KEY UPDATE setting_value = '0'
                ");
                $stmt->execute();
                
                // Increment site version to force cache refresh for all users
                $cacheManager = new CacheManager($pdo);
                $newVersion = $cacheManager->incrementVersion();
                
                // Remove .htaccess rule
                $htaccess_file = '../.htaccess';
                $current_htaccess = file_exists($htaccess_file) ? file_get_contents($htaccess_file) : '';
                
                // Remove all maintenance rules - loop to ensure complete removal
                $attempts = 0;
                while (strpos($current_htaccess, '# Maintenance Mode - Auto Generated') !== false && $attempts < 10) {
                    $new_content = preg_replace('/# Maintenance Mode - Auto Generated.*?# End Maintenance Mode\s*/s', '', $current_htaccess);
                    if ($new_content === $current_htaccess) {
                        break;
                    }
                    $current_htaccess = $new_content;
                    $attempts++;
                }
                
                file_put_contents($htaccess_file, $current_htaccess);
                
                $message = 'Maintenance mode DISABLED successfully! Site version updated (v' . $newVersion . '). All users will receive the latest changes automatically.';
                $messageType = 'success';
                $maintenanceEnabled = false;
                break;
                
            case 'update_site_settings':
                // Update general site settings
                $site_settings = [
                    'site_name' => $_POST['site_name'] ?? SITE_NAME,
                    'site_email' => $_POST['site_email'] ?? 'support@techcompare.com',
                    'items_per_page' => intval($_POST['items_per_page'] ?? 12)
                ];
                
                foreach ($site_settings as $key => $value) {
                    $stmt = $pdo->prepare("
                        INSERT INTO settings (setting_key, setting_value, description) 
                        VALUES (?, ?, ?)
                        ON DUPLICATE KEY UPDATE setting_value = ?
                    ");
                    $desc = ucwords(str_replace('_', ' ', $key));
                    $stmt->execute([$key, $value, $desc, $value]);
                }
                
                $message = 'Site settings updated successfully!';
                $messageType = 'success';
                break;
        }
        
        // Refresh maintenance settings
        $maintenanceSettings = getMaintenanceSettings();
        
    } catch (Exception $e) {
        $message = 'Error: ' . $e->getMessage();
        $messageType = 'error';
    }
}

// Calculate time remaining for maintenance
$timeRemaining = 0;
$hoursRemaining = 0;
$minutesRemaining = 0;
if ($maintenanceEnabled && isset($maintenanceSettings['maintenance_end_time'])) {
    $timeRemaining = max(0, intval($maintenanceSettings['maintenance_end_time']) - time());
    $hoursRemaining = floor($timeRemaining / 3600);
    $minutesRemaining = floor(($timeRemaining % 3600) / 60);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Settings - <?php echo SITE_NAME; ?> Admin</title>
    <link rel="stylesheet" href="../css/admin.css">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <style>
        .dashboard-content {
            padding: 2rem;
            background: #f8f9fa;
            min-height: calc(100vh - 120px);
        }
        
        .settings-container {
            width: 100%;
            padding: 0;
        }
        
        .settings-tabs {
            display: flex;
            gap: 1rem;
            margin-bottom: 2rem;
            border-bottom: 2px solid #e5e7eb;
            background: white;
            padding: 0 1rem;
            border-radius: 8px 8px 0 0;
        }
        
        .tab-button {
            padding: 1rem 2rem;
            background: none;
            border: none;
            border-bottom: 3px solid transparent;
            font-size: 1rem;
            font-weight: 600;
            color: #6b7280;
            cursor: pointer;
            transition: all 0.3s ease;
        }
        
        .tab-button:hover {
            color: #3b82f6;
        }
        
        .tab-button.active {
            color: #3b82f6;
            border-bottom-color: #3b82f6;
        }
        
        .tab-content {
            display: none;
        }
        
        .tab-content.active {
            display: block;
            animation: fadeIn 0.3s ease;
        }
        
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(10px); }
            to { opacity: 1; transform: translateY(0); }
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
        
        .maintenance-status {
            display: flex;
            align-items: center;
            gap: 1rem;
            padding: 1.5rem;
            background: #f3f4f6;
            border-radius: 8px;
            margin-bottom: 2rem;
        }
        
        .maintenance-status.active {
            background: #fef2f2;
            border: 2px solid #ef4444;
        }
        
        .maintenance-status.inactive {
            background: #f0fdf4;
            border: 2px solid #22c55e;
        }
        
        .status-indicator {
            width: 16px;
            height: 16px;
            border-radius: 50%;
            animation: pulse 2s infinite;
        }
        
        .status-indicator.active {
            background: #ef4444;
        }
        
        .status-indicator.inactive {
            background: #22c55e;
        }
        
        @keyframes pulse {
            0%, 100% { opacity: 1; }
            50% { opacity: 0.5; }
        }
        
        .form-group {
            margin-bottom: 1.5rem;
        }
        
        .form-group label {
            display: block;
            font-weight: 600;
            margin-bottom: 0.5rem;
            color: #374151;
        }
        
        .form-group input[type="text"],
        .form-group input[type="email"],
        .form-group input[type="number"],
        .form-group textarea,
        .form-group select {
            width: 100%;
            padding: 0.75rem;
            border: 1px solid #d1d5db;
            border-radius: 6px;
            font-size: 1rem;
        }
        
        .form-group textarea {
            min-height: 100px;
            resize: vertical;
        }
        
        .form-group small {
            display: block;
            margin-top: 0.25rem;
            color: #6b7280;
        }
        
        .btn {
            padding: 0.75rem 1.5rem;
            border: none;
            border-radius: 6px;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
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
        
        .btn-success {
            background: #22c55e;
            color: white;
        }
        
        .btn-success:hover {
            background: #16a34a;
        }
        
        .btn-danger {
            background: #ef4444;
            color: white;
        }
        
        .btn-danger:hover {
            background: #dc2626;
        }
        
        .btn-secondary {
            background: #6b7280;
            color: white;
        }
        
        .btn-secondary:hover {
            background: #4b5563;
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
        }
        
        .info-box ul {
            margin-left: 1.5rem;
            color: #1e3a8a;
        }
        
        .countdown-display {
            display: flex;
            gap: 1rem;
            margin-top: 1rem;
        }
        
        .countdown-item {
            background: white;
            padding: 1rem;
            border-radius: 8px;
            text-align: center;
            min-width: 80px;
        }
        
        .countdown-number {
            font-size: 2rem;
            font-weight: 700;
            color: #ef4444;
        }
        
        .countdown-label {
            font-size: 0.875rem;
            color: #6b7280;
            text-transform: uppercase;
        }
        
        /* Responsive design */
        @media (max-width: 768px) {
            .settings-tabs {
                flex-direction: column;
                gap: 0;
            }
            
            .tab-button {
                border-bottom: none;
                border-left: 3px solid transparent;
                text-align: left;
            }
            
            .tab-button.active {
                border-bottom: none;
                border-left-color: #3b82f6;
            }
            
            .countdown-display {
                flex-wrap: wrap;
            }
            
            .maintenance-status {
                flex-direction: column;
                align-items: flex-start;
            }
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
                    <li class="nav-item active">
                        <a href="settings.php" class="nav-link">
                            <i class="fas fa-cog"></i>
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
                <h1 class="page-title"><i class="fas fa-cog"></i> Settings</h1>
                <div class="header-actions">
                    <span class="current-time">
                        <?php echo date('M d, Y - g:i A'); ?>
                    </span>
                </div>
            </div>

            <div class="dashboard-content">
    
    <div class="settings-container">
        <?php if ($message): ?>
        <div class="alert alert-<?php echo $messageType; ?>">
            <i class="fas fa-<?php echo $messageType === 'success' ? 'check-circle' : 'exclamation-circle'; ?>"></i>
            <span><?php echo htmlspecialchars($message); ?></span>
        </div>
        <?php endif; ?>
        
        <!-- Settings Tabs -->
        <div class="settings-tabs">
            <button class="tab-button active" onclick="switchTab('maintenance')">
                <i class="fas fa-tools"></i> Maintenance Mode
            </button>
            <button class="tab-button" onclick="switchTab('general')">
                <i class="fas fa-sliders-h"></i> General Settings
            </button>
            <button class="tab-button" onclick="switchTab('special-access')">
                <i class="fas fa-key"></i> Special Access
            </button>
        </div>
        
        <!-- Maintenance Mode Tab -->
        <div id="maintenance-tab" class="tab-content active">
            <div class="settings-card">
                <h2><i class="fas fa-tools"></i> Maintenance Mode Control</h2>
                
                <!-- Current Status -->
                <div class="maintenance-status <?php echo $maintenanceEnabled ? 'active' : 'inactive'; ?>">
                    <div class="status-indicator <?php echo $maintenanceEnabled ? 'active' : 'inactive'; ?>"></div>
                    <div style="flex: 1;">
                        <h3 style="margin: 0; font-size: 1.25rem;">
                            <?php if ($maintenanceEnabled): ?>
                                <i class="fas fa-exclamation-triangle"></i> Maintenance Mode is ACTIVE
                            <?php else: ?>
                                <i class="fas fa-check-circle"></i> Site is ONLINE
                            <?php endif; ?>
                        </h3>
                        <?php if ($maintenanceEnabled && $timeRemaining > 0): ?>
                        <p style="margin: 0.5rem 0 0 0; color: #6b7280;">
                            Estimated end time: <?php echo date('M d, Y H:i', $maintenanceSettings['maintenance_end_time']); ?>
                        </p>
                        <div class="countdown-display">
                            <div class="countdown-item">
                                <div class="countdown-number"><?php echo str_pad($hoursRemaining, 2, '0', STR_PAD_LEFT); ?></div>
                                <div class="countdown-label">Hours</div>
                            </div>
                            <div class="countdown-item">
                                <div class="countdown-number"><?php echo str_pad($minutesRemaining, 2, '0', STR_PAD_LEFT); ?></div>
                                <div class="countdown-label">Minutes</div>
                            </div>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
                
                <?php if (!$maintenanceEnabled): ?>
                <!-- Enable Maintenance Form -->
                <form method="POST" action="">
                    <input type="hidden" name="action" value="enable_maintenance">
                    
                    <div class="info-box">
                        <h4><i class="fas fa-info-circle"></i> What happens when you enable maintenance mode?</h4>
                        <ul>
                            <li>Regular users will see a maintenance page</li>
                            <li>Admins can still access the admin panel</li>
                            <li>You can generate special access tokens for team members</li>
                            <li>All static assets (CSS, JS, images) will still load</li>
                        </ul>
                    </div>
                    
                    <div class="form-group">
                        <label for="title">Maintenance Title</label>
                        <input type="text" id="title" name="title" value="Site Under Maintenance" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="message">Maintenance Message</label>
                        <textarea id="message" name="message" required>We're performing scheduled maintenance to improve your experience. Thank you for your patience!</textarea>
                        <small>This message will be displayed to users</small>
                    </div>
                    
                    <div class="form-group">
                        <label for="duration_hours">Estimated Duration (hours)</label>
                        <input type="number" id="duration_hours" name="duration_hours" value="3" min="1" max="24" required>
                        <small>How long will the maintenance take?</small>
                    </div>
                    
                    <div class="form-group">
                        <label for="contact_email">Contact Email</label>
                        <input type="email" id="contact_email" name="contact_email" value="support@techcompare.com" required>
                        <small>Support email for urgent inquiries</small>
                    </div>
                    
                    <button type="submit" class="btn btn-danger">
                        <i class="fas fa-power-off"></i> Enable Maintenance Mode
                    </button>
                </form>
                
                <?php else: ?>
                <!-- Disable Maintenance Form -->
                <div class="info-box">
                    <h4><i class="fas fa-info-circle"></i> Maintenance Mode is Currently Active</h4>
                    <ul>
                        <li>Regular users see: <?php echo htmlspecialchars($maintenanceSettings['maintenance_title'] ?? 'Maintenance page'); ?></li>
                        <li>Started: <?php echo isset($maintenanceSettings['maintenance_start_time']) ? date('M d, Y H:i', $maintenanceSettings['maintenance_start_time']) : 'N/A'; ?></li>
                        <li>Contact: <?php echo htmlspecialchars($maintenanceSettings['maintenance_contact_email'] ?? 'N/A'); ?></li>
                    </ul>
                </div>
                
                <form method="POST" action="" onsubmit="return confirm('Are you sure you want to disable maintenance mode and make the site public?');">
                    <input type="hidden" name="action" value="disable_maintenance">
                    <button type="submit" class="btn btn-success">
                        <i class="fas fa-check"></i> Disable Maintenance Mode & Go Live
                    </button>
                </form>
                
                <div style="margin-top: 1rem;">
                    <a href="special-access.php" class="btn btn-secondary">
                        <i class="fas fa-key"></i> Generate Special Access Token
                    </a>
                </div>
                <?php endif; ?>
            </div>
        </div>
        
        <!-- General Settings Tab -->
        <div id="general-tab" class="tab-content">
            <div class="settings-card">
                <h2><i class="fas fa-sliders-h"></i> General Site Settings</h2>
                
                <form method="POST" action="">
                    <input type="hidden" name="action" value="update_site_settings">
                    
                    <div class="form-group">
                        <label for="site_name">Site Name</label>
                        <input type="text" id="site_name" name="site_name" value="<?php echo SITE_NAME; ?>" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="site_email">Site Email</label>
                        <input type="email" id="site_email" name="site_email" value="support@techcompare.com" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="items_per_page">Items Per Page</label>
                        <input type="number" id="items_per_page" name="items_per_page" value="12" min="6" max="100" required>
                        <small>Number of products to display per page</small>
                    </div>
                    
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save"></i> Save Settings
                    </button>
                </form>
            </div>
        </div>
        
        <!-- Special Access Tab -->
        <div id="special-access-tab" class="tab-content">
            <div class="settings-card">
                <h2><i class="fas fa-key"></i> Special Access Tokens</h2>
                
                <div class="info-box">
                    <h4><i class="fas fa-info-circle"></i> About Special Access Tokens</h4>
                    <ul>
                        <li>Generate temporary access links for team members during maintenance</li>
                        <li>Tokens expire after a set duration</li>
                        <li>Each token is unique and can be deactivated</li>
                    </ul>
                </div>
                
                <a href="special-access.php" class="btn btn-primary">
                    <i class="fas fa-plus"></i> Generate New Token
                </a>
            </div>
        </div>
    </div>
    
            </div><!-- /.dashboard-content -->
        </main><!-- /.admin-main -->
    </div><!-- /.admin-container -->
    
    <script>
        function switchTab(tabName) {
            // Hide all tabs
            document.querySelectorAll('.tab-content').forEach(tab => {
                tab.classList.remove('active');
            });
            
            // Remove active class from all buttons
            document.querySelectorAll('.tab-button').forEach(btn => {
                btn.classList.remove('active');
            });
            
            // Show selected tab
            document.getElementById(tabName + '-tab').classList.add('active');
            
            // Add active class to clicked button
            event.target.classList.add('active');
        }
    </script>
</body>
</html>
