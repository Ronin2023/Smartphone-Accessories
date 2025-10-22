<?php
/**
 * Special Access Middleware
 * 
 * Include this at the top of pages that should be accessible
 * with special access during maintenance mode
 * 
 * Usage: require_once 'includes/special-access-middleware.php';
 */

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/db_connect.php';
require_once __DIR__ . '/functions.php';
require_once __DIR__ . '/special-access-manager.php';

/**
 * Check if maintenance mode is active
 */
function isMaintenanceActive() {
    try {
        $pdo = getDB();
        $stmt = $pdo->prepare("SELECT setting_value FROM settings WHERE setting_key = 'maintenance_enabled'");
        $stmt->execute();
        $result = $stmt->fetchColumn();
        return (bool)$result;
    } catch (Exception $e) {
        return false;
    }
}

/**
 * Check if current URL is in whitelist (always accessible)
 */
function isWhitelistedPage() {
    $requestUri = $_SERVER['REQUEST_URI'];
    $scriptName = $_SERVER['SCRIPT_NAME'];
    
    // Get just the filename
    $currentPage = basename($scriptName);
    
    // Pages that are always accessible during maintenance
    $whitelist = [
        'maintenance.php',
        'verify-special-access.php',
        'admin',
        'api'
    ];
    
    // Check if current page is whitelisted
    foreach ($whitelist as $pattern) {
        if (strpos($requestUri, $pattern) !== false) {
            return true;
        }
    }
    
    // Check for CSS, JS, images, fonts
    if (preg_match('/\.(css|js|jpg|jpeg|png|gif|svg|woff|woff2|ttf|ico)$/i', $requestUri)) {
        return true;
    }
    
    return false;
}

/**
 * Check if user has admin bypass
 */
function hasAdminBypass() {
    return isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'] === true;
}

/**
 * Check if accessed via special access link
 */
function hasSpecialAccessLink() {
    return (isset($_GET['special_access']) && !empty($_GET['special_access'])) || 
           (isset($_GET['special_access_token']) && !empty($_GET['special_access_token']));
}

/**
 * Main middleware logic
 */
function checkSpecialAccess() {
    // Skip if not in maintenance mode
    if (!isMaintenanceActive()) {
        return;
    }
    
    // Skip if whitelisted page
    if (isWhitelistedPage()) {
        return;
    }
    
    // Skip if admin
    if (hasAdminBypass()) {
        return;
    }
    
    // Check if user has verified special access
    $manager = getSpecialAccessManager();
    if ($manager && $manager->hasActiveSession()) {
        // Log page access
        if (isset($_SESSION['special_access_token_id'])) {
            $pdo = getDB();
            $tokenId = $_SESSION['special_access_token_id'];
            $sessionId = session_id();
            $pageUrl = $_SERVER['REQUEST_URI'] ?? '/';
            $ipAddress = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
            $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? 'unknown';
            
            $stmt = $pdo->prepare("
                INSERT INTO special_access_logs 
                (token_id, session_id, action, page_url, ip_address, user_agent) 
                VALUES (?, ?, 'page_access', ?, ?, ?)
            ");
            $stmt->execute([$tokenId, $sessionId, $pageUrl, $ipAddress, $userAgent]);
        }
        
        return; // Allow access
    }
    
    // Check if accessed via special access link
    if (hasSpecialAccessLink()) {
        $token = $_GET['special_access'] ?? $_GET['special_access_token'];
        
        // Verify token exists and is active
        $pdo = getDB();
        $stmt = $pdo->prepare("
            SELECT id FROM special_access_tokens 
            WHERE token = ? AND is_active = 1
        ");
        $stmt->execute([$token]);
        
        if ($stmt->fetch()) {
            // Token valid, allow access to the current page (index.php will show overlay)
            return;
        }
    }
    
    // No valid access, redirect to maintenance page
    header('Location: maintenance.php');
    exit;
}

// Run the middleware check
checkSpecialAccess();
?>
