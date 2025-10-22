<?php
/**
 * Easy Maintenance Mode Manager
 * Simple interface to turn maintenance mode on/off
 */

session_start();
require_once '../includes/config.php';
require_once '../includes/db_connect.php';
require_once '../includes/auth.php';
require_once '../includes/functions.php';

// Check if user is logged in and has admin/editor access
if (!isLoggedIn() || !hasAdminAccess()) {
    header('Location: login.php');
    exit;
}

$message = '';
$status = '';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $admin_key = md5(SITE_NAME . date('Y-m-d'));
    
    try {
        $pdo = getDB();
        
        if ($action === 'enable') {
            // Enable maintenance mode
            $end_time = time() + (3 * 60 * 60); // 3 hours from now
            
            // Update settings
            $settings = [
                'maintenance_enabled' => '1',
                'maintenance_title' => $_POST['title'] ?? 'Site Under Maintenance',
                'maintenance_message' => $_POST['message'] ?? 'We\'re performing scheduled maintenance.',
                'maintenance_start_time' => time(),
                'maintenance_end_time' => $end_time,
                'maintenance_contact_email' => $_POST['email'] ?? 'support@techcompare.com'
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
RewriteCond %{REQUEST_URI} ^/Smartphone-Accessories/maintenance-control\\.php$ [OR]
RewriteCond %{REQUEST_URI} ^/Smartphone-Accessories/get-admin-key\\.php$ [OR]
RewriteCond %{REQUEST_URI} ^/Smartphone-Accessories/disable-maintenance\\.php$ [OR]
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
            
            // Remove any existing maintenance rules - loop to remove all occurrences
            $attempts = 0;
            while (strpos($current_htaccess, '# Maintenance Mode - Auto Generated') !== false && $attempts < 10) {
                $new_content = preg_replace('/# Maintenance Mode - Auto Generated.*?# End Maintenance Mode\s*/s', '', $current_htaccess);
                if ($new_content === $current_htaccess) {
                    break; // No more matches
                }
                $current_htaccess = $new_content;
                $attempts++;
            }
            
            // Add new maintenance rule at the beginning
            $new_htaccess = $htaccess_rule . "\n" . $current_htaccess;
            file_put_contents($htaccess_file, $new_htaccess);
            
            $message = '✅ Maintenance mode ENABLED successfully!';
            $status = 'enabled';
            
        } elseif ($action === 'disable') {
            // Disable maintenance mode
            $stmt = $pdo->prepare("UPDATE settings SET setting_value = '0' WHERE setting_key = 'maintenance_enabled'");
            $stmt->execute();
            
            // Remove .htaccess rule - use loop to remove all occurrences
            $htaccess_file = '../.htaccess';
            if (file_exists($htaccess_file)) {
                $current_htaccess = file_get_contents($htaccess_file);
                // Remove all maintenance mode blocks (in case of duplicates)
                $attempts = 0;
                while (strpos($current_htaccess, '# Maintenance Mode - Auto Generated') !== false && $attempts < 10) {
                    $new_htaccess = preg_replace('/# Maintenance Mode - Auto Generated.*?# End Maintenance Mode\s*/s', '', $current_htaccess);
                    if ($new_htaccess === $current_htaccess) {
                        break; // No more matches
                    }
                    $current_htaccess = $new_htaccess;
                    $attempts++;
                }
                file_put_contents($htaccess_file, $current_htaccess);
            }
            
            $message = '✅ Maintenance mode DISABLED successfully!';
            $status = 'disabled';
        }
        
    } catch (Exception $e) {
        $message = '❌ Error: ' . $e->getMessage();
        $status = 'error';
    }
}

// Check current maintenance status
$maintenance_enabled = false;
try {
    $pdo = getDB();
    $stmt = $pdo->prepare("SELECT setting_value FROM settings WHERE setting_key = 'maintenance_enabled'");
    $stmt->execute();
    $maintenance_enabled = ($stmt->fetchColumn() == '1');
} catch (Exception $e) {
    // Database error
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Maintenance Manager - TechCompare</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../css/admin.css">
    <style>
        .maintenance-manager {
            max-width: 1000px;
            margin: 2rem auto;
            padding: 2rem;
        }
        
        .status-banner {
            padding: 1.5rem;
            border-radius: 10px;
            margin-bottom: 2rem;
            text-align: center;
            font-size: 1.2rem;
            font-weight: bold;
        }
        
        .status-enabled {
            background: #fee2e2;
            border: 3px solid #ef4444;
            color: #991b1b;
        }
        
        .status-disabled {
            background: #d1fae5;
            border: 3px solid #10b981;
            color: #065f46;
        }
        
        .control-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 2rem;
            margin: 2rem 0;
        }
        
        .control-card {
            background: white;
            padding: 2rem;
            border-radius: 10px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
        }
        
        .control-card h2 {
            color: #334155;
            margin-bottom: 1rem;
        }
        
        .form-group {
            margin-bottom: 1.5rem;
        }
        
        .form-group label {
            display: block;
            font-weight: 600;
            margin-bottom: 0.5rem;
            color: #475569;
        }
        
        .form-group input,
        .form-group textarea {
            width: 100%;
            padding: 0.75rem;
            border: 2px solid #e2e8f0;
            border-radius: 6px;
            font-size: 1rem;
        }
        
        .form-group textarea {
            min-height: 100px;
            resize: vertical;
        }
        
        .btn-large {
            width: 100%;
            padding: 1rem;
            font-size: 1.1rem;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            transition: all 0.3s;
            margin-top: 1rem;
        }
        
        .btn-enable {
            background: #f59e0b;
            color: white;
        }
        
        .btn-disable {
            background: #10b981;
            color: white;
        }
        
        .btn-large:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 20px rgba(0,0,0,0.2);
        }
        
        .quick-links {
            display: flex;
            gap: 1rem;
            flex-wrap: wrap;
            margin-top: 2rem;
        }
        
        .quick-link {
            flex: 1;
            min-width: 200px;
            padding: 1rem;
            background: #667eea;
            color: white;
            text-decoration: none;
            border-radius: 8px;
            text-align: center;
            transition: all 0.3s;
        }
        
        .quick-link:hover {
            background: #5568d3;
            transform: translateY(-2px);
        }
        
        .message-box {
            padding: 1rem;
            border-radius: 8px;
            margin-bottom: 2rem;
            text-align: center;
            font-weight: bold;
        }
        
        .message-success {
            background: #d1fae5;
            border: 2px solid #10b981;
            color: #065f46;
        }
        
        .message-error {
            background: #fee2e2;
            border: 2px solid #ef4444;
            color: #991b1b;
        }
    </style>
</head>
<body>
    <div class="maintenance-manager">
        <h1><i class="fas fa-tools"></i> Maintenance Mode Manager</h1>
        <p>Easy control panel for managing site maintenance</p>
        
        <?php if ($message): ?>
        <div class="message-box <?php echo $status === 'error' ? 'message-error' : 'message-success'; ?>">
            <?php echo htmlspecialchars($message); ?>
        </div>
        <?php endif; ?>
        
        <div class="status-banner <?php echo $maintenance_enabled ? 'status-enabled' : 'status-disabled'; ?>">
            <?php if ($maintenance_enabled): ?>
                <i class="fas fa-exclamation-triangle"></i> MAINTENANCE MODE IS ACTIVE
            <?php else: ?>
                <i class="fas fa-check-circle"></i> SITE IS OPERATIONAL
            <?php endif; ?>
        </div>
        
        <div class="control-grid">
            <!-- Enable Maintenance -->
            <div class="control-card">
                <h2><i class="fas fa-power-off"></i> Enable Maintenance</h2>
                <p>Turn on maintenance mode to restrict public access</p>
                
                <form method="POST">
                    <input type="hidden" name="action" value="enable">
                    
                    <div class="form-group">
                        <label>Page Title</label>
                        <input type="text" name="title" value="Site Under Maintenance" required>
                    </div>
                    
                    <div class="form-group">
                        <label>Message to Users</label>
                        <textarea name="message" required>We're performing scheduled maintenance to improve your experience. Thank you for your patience!</textarea>
                    </div>
                    
                    <div class="form-group">
                        <label>Contact Email</label>
                        <input type="email" name="email" value="support@techcompare.com" required>
                    </div>
                    
                    <button type="submit" class="btn-large btn-enable" onclick="return confirm('This will enable maintenance mode. Continue?')">
                        <i class="fas fa-tools"></i> Enable Maintenance Mode
                    </button>
                </form>
            </div>
            
            <!-- Disable Maintenance -->
            <div class="control-card">
                <h2><i class="fas fa-play"></i> Disable Maintenance</h2>
                <p>Turn off maintenance mode to restore public access</p>
                
                <div style="padding: 2rem 0;">
                    <i class="fas fa-info-circle" style="font-size: 3rem; color: #10b981;"></i>
                    <p style="margin-top: 1rem; color: #64748b;">
                        Click the button below to immediately disable maintenance mode and make the site accessible to all users.
                    </p>
                </div>
                
                <form method="POST">
                    <input type="hidden" name="action" value="disable">
                    <button type="submit" class="btn-large btn-disable" onclick="return confirm('This will disable maintenance mode. Continue?')">
                        <i class="fas fa-check"></i> Disable Maintenance Mode
                    </button>
                </form>
                
                <div style="margin-top: 2rem; padding: 1rem; background: #f8fafc; border-radius: 8px;">
                    <p style="font-size: 0.9rem; color: #64748b;">
                        <i class="fas fa-lightbulb"></i> <strong>Tip:</strong> Admins and Editors can always access the site using special access links.
                    </p>
                </div>
            </div>
        </div>
        
        <div class="quick-links">
            <a href="dashboard.php" class="quick-link">
                <i class="fas fa-tachometer-alt"></i><br>Dashboard
            </a>
            <a href="special-access.php" class="quick-link">
                <i class="fas fa-key"></i><br>Special Access Links
            </a>
            <a href="../maintenance-control.php" class="quick-link">
                <i class="fas fa-cog"></i><br>Advanced Control
            </a>
            <a href="../get-admin-key.php" class="quick-link">
                <i class="fas fa-unlock"></i><br>Get Admin Key
            </a>
        </div>
    </div>
</body>
</html>
