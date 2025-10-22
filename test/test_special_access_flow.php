<?php
/**
 * Test Special Access Flow
 */
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db_connect.php';
require_once __DIR__ . '/../includes/functions.php';

echo "=== TESTING SPECIAL ACCESS FLOW ===\n\n";

try {
    $db = getDB();
    
    // 1. Create a test token for testing
    echo "1️⃣  Creating test token...\n";
    $manager = getSpecialAccessManager();
    
    // Get a test user
    $stmt = $db->query("SELECT id, username, email FROM users WHERE role IN ('admin', 'editor') LIMIT 1");
    $testUser = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$testUser) {
        echo "   ❌ No admin/editor users found. Please create a user first.\n";
        exit(1);
    }
    
    $result = $manager->createToken(
        $testUser['username'] . ' - Test', 
        $testUser['email'], 
        'Test token for special access flow', 
        1, 
        $testUser['id']
    );
    
    if (!$result['success']) {
        echo "   ❌ Failed to create test token: " . $result['error'] . "\n";
        exit(1);
    }
    
    echo "   ✅ Test token created successfully\n";
    echo "   Token: " . $result['token'] . "\n";
    echo "   Passkey: " . $result['passkey'] . "\n\n";
    
    // 2. Test the special access URL
    echo "2️⃣  Testing special access URL...\n";
    $specialAccessUrl = "http://localhost/Smartphone-Accessories/?special_access=" . $result['token'];
    echo "   URL: $specialAccessUrl\n";
    
    // Verify token exists in database
    $stmt = $db->prepare("SELECT * FROM special_access_tokens WHERE token = ? AND is_active = 1");
    $stmt->execute([$result['token']]);
    $tokenData = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($tokenData) {
        echo "   ✅ Token verified in database\n";
        echo "   Token ID: " . $tokenData['id'] . "\n";
        echo "   User ID: " . $tokenData['user_id'] . "\n";
        echo "   Name: " . $tokenData['name'] . "\n";
        echo "   Email: " . $tokenData['email'] . "\n";
        echo "   Passkey: " . $tokenData['passkey'] . "\n";
    } else {
        echo "   ❌ Token not found in database\n";
    }
    
    // 3. Test maintenance mode detection
    echo "\n3️⃣  Testing maintenance mode status...\n";
    
    // Check if maintenance is enabled
    $stmt = $db->prepare("SELECT setting_value FROM settings WHERE setting_key = 'maintenance_enabled'");
    $stmt->execute();
    $maintenanceEnabled = $stmt->fetchColumn();
    
    echo "   Maintenance enabled: " . ($maintenanceEnabled ? 'YES' : 'NO') . "\n";
    
    if (!$maintenanceEnabled) {
        echo "   ⚠️  Maintenance mode is disabled. Enable it to test special access flow.\n";
        echo "   You can enable it from admin panel > Settings > Maintenance Mode\n";
    } else {
        echo "   ✅ Maintenance mode is active - special access flow will work\n";
    }
    
    // 4. Test middleware function
    echo "\n4️⃣  Testing middleware functions...\n";
    
    // Include middleware to test functions
    require_once __DIR__ . '/../includes/special-access-middleware.php';
    
    echo "   ✅ Middleware loaded successfully\n";
    echo "   ✅ getSpecialAccessManager() function available\n";
    
    // 5. Show flow instructions
    echo "\n" . str_repeat("=", 60) . "\n";
    echo "🎯 TESTING INSTRUCTIONS:\n";
    echo str_repeat("=", 60) . "\n\n";
    
    echo "1. ENABLE MAINTENANCE MODE (if not already enabled):\n";
    echo "   • Go to: http://localhost/Smartphone-Accessories/admin/login.php\n";
    echo "   • Login as admin\n";
    echo "   • Go to Settings > Enable Maintenance Mode\n\n";
    
    echo "2. TEST SPECIAL ACCESS:\n";
    echo "   • Open: $specialAccessUrl\n";
    echo "   • Should redirect to passkey verification page\n";
    echo "   • Enter passkey: " . $result['passkey'] . "\n";
    echo "   • Should grant full site access\n\n";
    
    echo "3. ALTERNATIVE TEST (via maintenance page):\n";
    echo "   • Go to: http://localhost/Smartphone-Accessories/maintenance.php\n";
    echo "   • Enter token in special access form: " . $result['token'] . "\n";
    echo "   • Should redirect to passkey verification\n\n";
    
    echo "4. CLEANUP:\n";
    echo "   • After testing, you can delete this token from admin panel\n";
    echo "   • Or disable maintenance mode to return to normal operation\n\n";
    
    echo "✅ ALL SYSTEMS READY FOR TESTING!\n";
    
} catch (Exception $e) {
    echo "❌ ERROR: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
}