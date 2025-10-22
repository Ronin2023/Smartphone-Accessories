<?php
/**
 * Test Special Access Links
 * This page tests all access methods and link generation
 */

session_start();
require_once '../includes/config.php';
require_once '../includes/db_connect.php';
require_once '../includes/functions.php';

// Check if user is logged in
$is_logged_in = isLoggedIn();
$has_admin_access = hasAdminAccess();

// Get user info if logged in
$user_id = $_SESSION['user_id'] ?? 0;
$username = $_SESSION['username'] ?? 'Not logged in';
$role = $_SESSION['user_role'] ?? 'none';

// Generate test token
$test_token = '';
if ($is_logged_in && $has_admin_access) {
    $test_token = md5($username . $user_id . date('Y-m-d') . SITE_NAME);
}

// Get current environment info
$current_protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') || $_SERVER['SERVER_PORT'] == 443 ? 'https' : 'http';
$current_host = $_SERVER['HTTP_HOST'] ?? 'localhost';
$current_base = $current_protocol . '://' . $current_host . '/Smartphone-Accessories';

// Generate test links
$test_links = [
    'Homepage' => $current_base . '/index.html',
    'Products' => $current_base . '/products.html',
    'Contact' => $current_base . '/contact.html',
    'Admin Dashboard' => $current_base . '/admin/dashboard.php',
    'Maintenance Control' => $current_base . '/maintenance-control.php',
    'Special Access Generator' => $current_base . '/admin/special-access.php',
];

if ($test_token) {
    $test_links['Special Access (Token)'] = $current_base . '/index.html?special_access=' . $test_token;
    $test_links['Admin Bypass'] = $current_base . '/index.html?admin_bypass=1';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Test Special Access Links - TechCompare</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 2rem;
        }
        
        .container {
            max-width: 1000px;
            margin: 0 auto;
            background: white;
            border-radius: 20px;
            padding: 2rem;
            box-shadow: 0 20px 60px rgba(0,0,0,0.2);
        }
        
        .header {
            text-align: center;
            margin-bottom: 2rem;
            padding-bottom: 2rem;
            border-bottom: 2px solid #e2e8f0;
        }
        
        .status-badge {
            display: inline-block;
            padding: 0.5rem 1rem;
            border-radius: 20px;
            margin: 0.5rem;
            font-weight: bold;
        }
        
        .badge-success {
            background: #d1fae5;
            color: #065f46;
            border: 2px solid #10b981;
        }
        
        .badge-error {
            background: #fee2e2;
            color: #991b1b;
            border: 2px solid #ef4444;
        }
        
        .section {
            margin: 2rem 0;
            padding: 1.5rem;
            background: #f8fafc;
            border-radius: 10px;
            border-left: 4px solid #667eea;
        }
        
        .section h2 {
            color: #334155;
            margin-bottom: 1rem;
        }
        
        .info-grid {
            display: grid;
            grid-template-columns: 200px 1fr;
            gap: 0.5rem;
            background: white;
            padding: 1rem;
            border-radius: 8px;
            font-family: 'Courier New', monospace;
            font-size: 0.9rem;
        }
        
        .info-label {
            font-weight: bold;
            color: #64748b;
        }
        
        .info-value {
            color: #1e293b;
            word-break: break-all;
        }
        
        .link-table {
            width: 100%;
            background: white;
            border-radius: 8px;
            overflow: hidden;
            border-collapse: collapse;
        }
        
        .link-table th {
            background: #667eea;
            color: white;
            padding: 1rem;
            text-align: left;
        }
        
        .link-table td {
            padding: 1rem;
            border-bottom: 1px solid #e2e8f0;
        }
        
        .link-table tr:hover {
            background: #f8fafc;
        }
        
        .test-btn {
            padding: 0.5rem 1rem;
            background: #667eea;
            color: white;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            text-decoration: none;
            display: inline-block;
            font-size: 0.9rem;
        }
        
        .test-btn:hover {
            background: #5568d3;
        }
        
        .copy-btn {
            padding: 0.5rem 1rem;
            background: #10b981;
            color: white;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-size: 0.9rem;
            margin-left: 0.5rem;
        }
        
        .copy-btn:hover {
            background: #059669;
        }
        
        .alert {
            padding: 1rem;
            border-radius: 8px;
            margin: 1rem 0;
        }
        
        .alert-warning {
            background: #fef3c7;
            border: 2px solid #f59e0b;
            color: #92400e;
        }
        
        .alert-info {
            background: #dbeafe;
            border: 2px solid #3b82f6;
            color: #1e3a8a;
        }
        
        .env-indicator {
            display: inline-block;
            padding: 0.25rem 0.75rem;
            border-radius: 12px;
            font-size: 0.85rem;
            font-weight: bold;
        }
        
        .env-local {
            background: #dbeafe;
            color: #1e40af;
        }
        
        .env-ngrok {
            background: #e9d5ff;
            color: #6b21a8;
        }
        
        .env-production {
            background: #d1fae5;
            color: #065f46;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1><i class="fas fa-vial"></i> Special Access Link Tester</h1>
            <p>Test and verify all access links for localhost, ngrok, and production</p>
            
            <?php if ($is_logged_in): ?>
                <div class="status-badge badge-success">
                    <i class="fas fa-check-circle"></i> Logged in as: <?php echo htmlspecialchars($username); ?> (<?php echo ucfirst($role); ?>)
                </div>
            <?php else: ?>
                <div class="status-badge badge-error">
                    <i class="fas fa-times-circle"></i> Not logged in
                </div>
            <?php endif; ?>
        </div>
        
        <?php if (!$is_logged_in || !$has_admin_access): ?>
        <div class="alert alert-warning">
            <i class="fas fa-exclamation-triangle"></i>
            <strong>Notice:</strong> You must be logged in as an Admin or Editor to generate special access links.
            <br><br>
            <a href="login.php" style="color: #92400e; font-weight: bold;">
                <i class="fas fa-sign-in-alt"></i> Login to Admin Panel
            </a>
        </div>
        <?php endif; ?>
        
        <div class="section">
            <h2><i class="fas fa-server"></i> Environment Detection</h2>
            <div class="info-grid">
                <div class="info-label">Protocol:</div>
                <div class="info-value"><?php echo $current_protocol; ?></div>
                
                <div class="info-label">Host:</div>
                <div class="info-value"><?php echo htmlspecialchars($current_host); ?></div>
                
                <div class="info-label">Base URL:</div>
                <div class="info-value"><?php echo htmlspecialchars($current_base); ?></div>
                
                <div class="info-label">SITE_URL:</div>
                <div class="info-value"><?php echo htmlspecialchars(SITE_URL); ?></div>
                
                <div class="info-label">Environment:</div>
                <div class="info-value">
                    <?php 
                    if (strpos($current_host, 'ngrok') !== false) {
                        echo '<span class="env-indicator env-ngrok"><i class="fas fa-network-wired"></i> Ngrok Tunnel</span>';
                    } elseif ($current_host === 'localhost' || strpos($current_host, '127.0.0.1') !== false) {
                        echo '<span class="env-indicator env-local"><i class="fas fa-laptop-code"></i> Local Development</span>';
                    } else {
                        echo '<span class="env-indicator env-production"><i class="fas fa-globe"></i> Production Server</span>';
                    }
                    ?>
                </div>
                
                <?php if ($test_token): ?>
                <div class="info-label">Your Token:</div>
                <div class="info-value" style="color: #667eea; font-weight: bold;"><?php echo $test_token; ?></div>
                <?php endif; ?>
            </div>
        </div>
        
        <div class="section">
            <h2><i class="fas fa-link"></i> Test All Links</h2>
            <div class="alert alert-info">
                <i class="fas fa-info-circle"></i>
                Click "Test Link" to open in a new tab. Click "Copy" to copy the URL to clipboard.
            </div>
            
            <table class="link-table">
                <thead>
                    <tr>
                        <th>Link Type</th>
                        <th>URL</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($test_links as $name => $url): ?>
                    <tr>
                        <td><strong><?php echo htmlspecialchars($name); ?></strong></td>
                        <td style="font-family: 'Courier New', monospace; font-size: 0.85rem; color: #64748b;">
                            <?php echo htmlspecialchars($url); ?>
                        </td>
                        <td>
                            <a href="<?php echo htmlspecialchars($url); ?>" target="_blank" class="test-btn">
                                <i class="fas fa-external-link-alt"></i> Test
                            </a>
                            <button class="copy-btn" onclick="copyToClipboard('<?php echo htmlspecialchars($url, ENT_QUOTES); ?>', this)">
                                <i class="fas fa-copy"></i> Copy
                            </button>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        
        <div class="section">
            <h2><i class="fas fa-check-double"></i> Verification Checklist</h2>
            <ul style="list-style: none; padding: 0;">
                <li style="padding: 0.5rem; margin: 0.5rem 0; background: white; border-radius: 6px;">
                    <i class="fas fa-check" style="color: #10b981;"></i> <strong>Homepage Link:</strong> Should load homepage
                </li>
                <li style="padding: 0.5rem; margin: 0.5rem 0; background: white; border-radius: 6px;">
                    <i class="fas fa-check" style="color: #10b981;"></i> <strong>Admin Dashboard:</strong> Should load admin panel (requires login)
                </li>
                <li style="padding: 0.5rem; margin: 0.5rem 0; background: white; border-radius: 6px;">
                    <i class="fas fa-check" style="color: #10b981;"></i> <strong>Maintenance Control:</strong> Should load control panel
                </li>
                <?php if ($test_token): ?>
                <li style="padding: 0.5rem; margin: 0.5rem 0; background: white; border-radius: 6px;">
                    <i class="fas fa-check" style="color: #10b981;"></i> <strong>Special Access Link:</strong> Should bypass maintenance mode
                </li>
                <li style="padding: 0.5rem; margin: 0.5rem 0; background: white; border-radius: 6px;">
                    <i class="fas fa-check" style="color: #10b981;"></i> <strong>Admin Bypass:</strong> Should bypass maintenance mode
                </li>
                <?php endif; ?>
            </ul>
        </div>
        
        <div style="text-align: center; margin-top: 2rem; padding-top: 2rem; border-top: 2px solid #e2e8f0;">
            <a href="dashboard.php" style="margin: 0 0.5rem; color: #667eea; text-decoration: none;">
                <i class="fas fa-tachometer-alt"></i> Dashboard
            </a>
            <a href="special-access.php" style="margin: 0 0.5rem; color: #667eea; text-decoration: none;">
                <i class="fas fa-key"></i> Special Access
            </a>
            <a href="maintenance-manager.php" style="margin: 0 0.5rem; color: #667eea; text-decoration: none;">
                <i class="fas fa-tools"></i> Maintenance Manager
            </a>
        </div>
    </div>
    
    <script>
        function copyToClipboard(text, button) {
            navigator.clipboard.writeText(text).then(() => {
                const originalHTML = button.innerHTML;
                button.innerHTML = '<i class="fas fa-check"></i> Copied!';
                button.style.background = '#059669';
                
                setTimeout(() => {
                    button.innerHTML = originalHTML;
                    button.style.background = '#10b981';
                }, 2000);
            }).catch(err => {
                alert('Failed to copy: ' + err);
            });
        }
    </script>
</body>
</html>
