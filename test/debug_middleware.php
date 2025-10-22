<?php
// Debug Special Access Middleware
echo "🔍 DEBUGGING SPECIAL ACCESS MIDDLEWARE\n";
echo "=====================================\n\n";

// Simulate the middleware environment
$_GET['special_access_token'] = '0589a63637397ec520d38d9a6d9f3fa93e36bb3a854e55df1142df842f31d985';
$_SERVER['REQUEST_URI'] = '/Smartphone-Accessories/index.php?special_access_token=0589a63637397ec520d38d9a6d9f3fa93e36bb3a854e55df1142df842f31d985';
$_SERVER['SCRIPT_NAME'] = '/Smartphone-Accessories/index.php';

require_once '../includes/config.php';
require_once '../includes/db_connect.php';
require_once '../includes/functions.php';

echo "1. 🔧 ENVIRONMENT SETUP\n";
echo "   REQUEST_URI: " . $_SERVER['REQUEST_URI'] . "\n";
echo "   SCRIPT_NAME: " . $_SERVER['SCRIPT_NAME'] . "\n";
echo "   GET Parameter: " . ($_GET['special_access_token'] ?? 'NOT SET') . "\n\n";

// Test maintenance mode check
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

echo "2. 🔧 MAINTENANCE MODE CHECK\n";
$maintenanceActive = isMaintenanceActive();
echo "   Maintenance Active: " . ($maintenanceActive ? "YES" : "NO") . "\n\n";

// Test whitelist check
function isWhitelistedPage() {
    $requestUri = $_SERVER['REQUEST_URI'];
    $scriptName = $_SERVER['SCRIPT_NAME'];
    
    $currentPage = basename($scriptName);
    
    $whitelist = [
        'maintenance.php',
        'verify-special-access.php',
        'admin',
        'api'
    ];
    
    foreach ($whitelist as $pattern) {
        if (strpos($requestUri, $pattern) !== false) {
            return true;
        }
    }
    
    if (preg_match('/\.(css|js|jpg|jpeg|png|gif|svg|woff|woff2|ttf|ico)$/i', $requestUri)) {
        return true;
    }
    
    return false;
}

echo "3. 🔧 WHITELIST CHECK\n";
$isWhitelisted = isWhitelistedPage();
echo "   Is Whitelisted: " . ($isWhitelisted ? "YES" : "NO") . "\n\n";

// Test admin bypass
function hasAdminBypass() {
    return isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'] === true;
}

echo "4. 🔧 ADMIN BYPASS CHECK\n";
$hasAdmin = hasAdminBypass();
echo "   Has Admin Bypass: " . ($hasAdmin ? "YES" : "NO") . "\n\n";

// Test special access link detection
function hasSpecialAccessLink() {
    return (isset($_GET['special_access']) && !empty($_GET['special_access'])) || 
           (isset($_GET['special_access_token']) && !empty($_GET['special_access_token']));
}

echo "5. 🔧 SPECIAL ACCESS LINK CHECK\n";
$hasLink = hasSpecialAccessLink();
echo "   Has Special Access Link: " . ($hasLink ? "YES" : "NO") . "\n";

if ($hasLink) {
    $token = $_GET['special_access'] ?? $_GET['special_access_token'];
    echo "   Token: " . substr($token, 0, 20) . "...\n";
    
    // Test token validation
    try {
        $pdo = getDB();
        $stmt = $pdo->prepare("SELECT id FROM special_access_tokens WHERE token = ? AND is_active = 1");
        $stmt->execute([$token]);
        $tokenValid = $stmt->fetch();
        
        echo "   Token Valid: " . ($tokenValid ? "YES" : "NO") . "\n";
    } catch (Exception $e) {
        echo "   Token Validation Error: " . $e->getMessage() . "\n";
    }
}

echo "\n";

// Simulate the complete middleware logic
echo "6. 🎯 MIDDLEWARE DECISION FLOW\n";

if (!$maintenanceActive) {
    echo "   ➤ RESULT: ALLOW ACCESS (Maintenance mode disabled)\n";
} elseif ($isWhitelisted) {
    echo "   ➤ RESULT: ALLOW ACCESS (Whitelisted page)\n";
} elseif ($hasAdmin) {
    echo "   ➤ RESULT: ALLOW ACCESS (Admin bypass)\n";
} elseif ($hasLink) {
    $token = $_GET['special_access'] ?? $_GET['special_access_token'];
    try {
        $pdo = getDB();
        $stmt = $pdo->prepare("SELECT id FROM special_access_tokens WHERE token = ? AND is_active = 1");
        $stmt->execute([$token]);
        $tokenValid = $stmt->fetch();
        
        if ($tokenValid) {
            echo "   ➤ RESULT: ALLOW ACCESS (Valid special access token)\n";
            echo "   ➤ INDEX.PHP SHOULD LOAD WITH OVERLAY\n";
        } else {
            echo "   ➤ RESULT: REDIRECT TO MAINTENANCE (Invalid token)\n";
        }
    } catch (Exception $e) {
        echo "   ➤ RESULT: REDIRECT TO MAINTENANCE (Token validation error)\n";
    }
} else {
    echo "   ➤ RESULT: REDIRECT TO MAINTENANCE (No valid access)\n";
}

echo "\n✨ Debug completed!\n";
?>