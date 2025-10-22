<?php
/**
 * Test Complete Passkey Verification Process
 */

session_start();
require_once '../includes/config.php';
require_once '../includes/db_connect.php';
require_once '../includes/functions.php';

echo "🧪 TESTING COMPLETE PASSKEY VERIFICATION\n";
echo "========================================\n\n";

// Get test data
$manager = getSpecialAccessManager();
$tokens = $manager->getAllTokens();

if (empty($tokens)) {
    echo "❌ No tokens found\n";
    exit;
}

$testToken = $tokens[0];
echo "📋 Test Data:\n";
echo "   Token: " . substr($testToken['token'], 0, 20) . "...\n";
echo "   Passkey: " . $testToken['passkey'] . "\n";
echo "   User: " . ($testToken['name'] ?? 'Unknown') . "\n\n";

// Test 1: Verify passkey
echo "1️⃣  TESTING PASSKEY VERIFICATION\n";

$result = $manager->verifyPasskey($testToken['token'], $testToken['passkey']);

echo "   Result: " . ($result['success'] ? 'SUCCESS' : 'FAILED') . "\n";

if ($result['success']) {
    echo "   ✅ Token ID: " . $result['token_id'] . "\n";
    echo "   ✅ Name: " . $result['name'] . "\n";
    echo "   ✅ Session ID: " . $result['session_id'] . "\n";
    echo "   ✅ Expires: " . ($result['expires_at'] ?? 'No expiration') . "\n";
} else {
    echo "   ❌ Error: " . $result['error'] . "\n";
    echo "\n   🔧 Debugging verification failure...\n";
    
    // Debug database state
    try {
        $pdo = getDB();
        $stmt = $pdo->prepare("SELECT * FROM special_access_tokens WHERE token = ?");
        $stmt->execute([$testToken['token']]);
        $dbToken = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($dbToken) {
            echo "   📋 Token in DB: " . ($dbToken['is_active'] ? 'ACTIVE' : 'INACTIVE') . "\n";
            echo "   📋 DB Passkey: " . $dbToken['passkey'] . "\n";
            echo "   📋 Test Passkey: " . $testToken['passkey'] . "\n";
            echo "   📋 Match: " . ($dbToken['passkey'] === $testToken['passkey'] ? 'YES' : 'NO') . "\n";
        } else {
            echo "   ❌ Token not found in database\n";
        }
        
        // Check maintenance mode
        $stmt = $pdo->prepare("SELECT setting_value FROM settings WHERE setting_key = 'maintenance_enabled'");
        $stmt->execute();
        $maintenance = $stmt->fetchColumn();
        echo "   📋 Maintenance Mode: " . ($maintenance ? 'ENABLED' : 'DISABLED') . "\n";
        
    } catch (Exception $e) {
        echo "   ❌ DB Error: " . $e->getMessage() . "\n";
    }
    exit;
}

echo "\n";

// Test 2: Simulate setting session variables (what verify-special-access.php should do)
echo "2️⃣  SIMULATING SESSION SETUP\n";

$_SESSION['special_access_verified'] = true;
$_SESSION['special_access_token_id'] = $result['token_id'];
$_SESSION['special_access_name'] = $result['name'];
$_SESSION['special_access_expires'] = $result['expires_at'];

echo "   ✅ Session variables set:\n";
echo "   - special_access_verified: true\n";
echo "   - special_access_token_id: " . $result['token_id'] . "\n";
echo "   - special_access_name: " . $result['name'] . "\n";
echo "   - special_access_expires: " . ($result['expires_at'] ?? 'None') . "\n";

echo "\n";

// Test 3: Check if hasActiveSession works now
echo "3️⃣  TESTING hasActiveSession() METHOD\n";

$hasActive = $manager->hasActiveSession();
echo "   Has Active Session: " . ($hasActive ? 'YES' : 'NO') . "\n";

if (!$hasActive) {
    echo "   🔍 Debugging hasActiveSession failure...\n";
    
    // Check session ID
    echo "   Session ID: " . session_id() . "\n";
    
    // Check database record
    try {
        $pdo = getDB();
        $stmt = $pdo->prepare("
            SELECT s.*, t.name 
            FROM special_access_sessions s
            JOIN special_access_tokens t ON s.token_id = t.id
            WHERE s.session_id = ?
        ");
        $stmt->execute([session_id()]);
        $sessionRecord = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($sessionRecord) {
            echo "   ✅ Session record found in database\n";
            echo "   📋 Active: " . ($sessionRecord['is_active'] ? 'YES' : 'NO') . "\n";
            echo "   📋 Expires: " . ($sessionRecord['expires_at'] ?? 'Never') . "\n";
            
            if ($sessionRecord['expires_at']) {
                $now = new DateTime();
                $expires = new DateTime($sessionRecord['expires_at']);
                $expired = $now > $expires;
                echo "   📋 Expired: " . ($expired ? 'YES' : 'NO') . "\n";
            }
        } else {
            echo "   ❌ No session record found in database\n";
        }
        
    } catch (Exception $e) {
        echo "   ❌ Error checking session: " . $e->getMessage() . "\n";
    }
}

echo "\n";

// Test 4: Final middleware simulation
echo "4️⃣  FINAL MIDDLEWARE TEST\n";

if ($hasActive) {
    echo "   🎉 SUCCESS: User should now have access to protected pages\n";
    echo "   ✅ index.php should load normally (not maintenance page)\n";
} else {
    echo "   ❌ FAILED: User will still see maintenance page\n";
    echo "   🔧 Need to investigate session handling\n";
}

echo "\n🧪 MANUAL TEST:\n";
echo "After running this script, test:\n";
echo "http://localhost/Smartphone-Accessories/index.php\n";
echo "Should show: " . ($hasActive ? "INDEX CONTENT" : "MAINTENANCE PAGE") . "\n";

echo "\n✨ Test completed!\n";
?>