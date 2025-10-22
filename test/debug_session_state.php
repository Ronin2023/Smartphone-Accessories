<?php
/**
 * Debug Special Access Session Management
 */

session_start();
require_once '../includes/config.php';
require_once '../includes/db_connect.php';
require_once '../includes/functions.php';

echo "🔍 DEBUG: SPECIAL ACCESS SESSION STATE\n";
echo "======================================\n\n";

echo "1️⃣  SESSION INFORMATION\n";
echo "   Session ID: " . session_id() . "\n";
echo "   Session Status: " . session_status() . " (1=DISABLED, 2=ACTIVE, 3=NONE)\n";

echo "\n2️⃣  SPECIAL ACCESS SESSION VARIABLES\n";
$sessionVars = [
    'special_access_verified' => 'Verification Status',
    'special_access_token' => 'Current Token',
    'special_access_token_id' => 'Token ID',
    'special_access_name' => 'User Name',
    'special_access_expires' => 'Session Expires'
];

foreach ($sessionVars as $var => $description) {
    $value = $_SESSION[$var] ?? 'NOT SET';
    if ($var === 'special_access_token' && $value !== 'NOT SET') {
        $value = substr($value, 0, 20) . '...'; // Truncate token for security
    }
    echo "   $description: $value\n";
}

echo "\n3️⃣  SPECIAL ACCESS MANAGER CHECK\n";
try {
    $manager = getSpecialAccessManager();
    if ($manager) {
        echo "   ✅ Manager available\n";
        
        $hasActiveSession = $manager->hasActiveSession();
        echo "   Active Session: " . ($hasActiveSession ? 'YES' : 'NO') . "\n";
        
        if ($hasActiveSession) {
            echo "   🎉 User should have access to protected pages\n";
        } else {
            echo "   ❌ User should be redirected to maintenance\n";
        }
    } else {
        echo "   ❌ Manager not available\n";
    }
} catch (Exception $e) {
    echo "   ❌ Manager error: " . $e->getMessage() . "\n";
}

echo "\n4️⃣  MIDDLEWARE SIMULATION\n";

// Simulate accessing index.php with current session
$_SERVER['REQUEST_URI'] = '/Smartphone-Accessories/index.php';
$_SERVER['SCRIPT_NAME'] = '/Smartphone-Accessories/index.php';

// Check maintenance mode
try {
    $pdo = getDB();
    $stmt = $pdo->prepare("SELECT setting_value FROM settings WHERE setting_key = 'maintenance_enabled'");
    $stmt->execute();
    $maintenanceEnabled = (bool)$stmt->fetchColumn();
    echo "   Maintenance Mode: " . ($maintenanceEnabled ? 'ENABLED' : 'DISABLED') . "\n";
    
    if ($maintenanceEnabled) {
        // Check if user should have access
        if (isset($_SESSION['special_access_verified']) && $_SESSION['special_access_verified'] && $manager && $manager->hasActiveSession()) {
            echo "   🎯 RESULT: Should allow access to index.php\n";
        } else {
            echo "   🎯 RESULT: Should redirect to maintenance.php\n";
        }
    } else {
        echo "   🎯 RESULT: Normal access (maintenance disabled)\n";
    }
} catch (Exception $e) {
    echo "   ❌ Error checking maintenance: " . $e->getMessage() . "\n";
}

echo "\n5️⃣  RECOMMENDATIONS\n";

if (!isset($_SESSION['special_access_verified']) || !$_SESSION['special_access_verified']) {
    echo "   🔧 Need to complete passkey verification\n";
} elseif (!$manager || !$manager->hasActiveSession()) {
    echo "   🔧 Session verification failed - check manager implementation\n";
} else {
    echo "   ✅ Everything looks correct - should have access\n";
}

echo "\n🧪 TEST THESE URLS:\n";
echo "   Direct access: http://localhost/Smartphone-Accessories/index.php\n";
echo "   After verification: Should show index content, not maintenance\n";

echo "\n✨ Debug completed!\n";
?>