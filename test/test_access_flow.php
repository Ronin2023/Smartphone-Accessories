<?php
require_once '../includes/config.php';
require_once '../includes/db_connect.php';
require_once '../includes/functions.php';

echo "Testing Special Access Link Flow...\n";
echo "===================================\n";

// Get a sample token
$manager = getSpecialAccessManager();
$tokens = $manager->getAllTokens();

if (empty($tokens)) {
    echo "❌ No tokens found. Create a token first.\n";
    exit;
}

$sampleToken = $tokens[0];
echo "✅ Using token: " . substr($sampleToken['token'], 0, 16) . "...\n";
echo "🔑 Passkey: " . $sampleToken['passkey'] . "\n\n";

// Test URL construction
$testUrl = SITE_URL . "?special_access_token=" . $sampleToken['token'];
echo "🔗 Test URL: $testUrl\n\n";

// Simulate middleware logic
echo "📋 MIDDLEWARE SIMULATION:\n";

// 1. Check maintenance mode
try {
    $pdo = getDB();
    $stmt = $pdo->prepare("SELECT setting_value FROM settings WHERE setting_key = 'maintenance_enabled'");
    $stmt->execute();
    $maintenanceEnabled = (bool)$stmt->fetchColumn();
    
    echo "1. Maintenance Mode: " . ($maintenanceEnabled ? "✅ ENABLED" : "❌ DISABLED") . "\n";
    
    if ($maintenanceEnabled) {
        // 2. Check if token exists
        $stmt = $pdo->prepare("SELECT id FROM special_access_tokens WHERE token = ? AND is_active = 1");
        $stmt->execute([$sampleToken['token']]);
        $tokenExists = $stmt->fetch();
        
        echo "2. Token Valid: " . ($tokenExists ? "✅ YES" : "❌ NO") . "\n";
        
        if ($tokenExists) {
            echo "3. Expected Behavior: ✅ Allow access to index.php with overlay\n";
            echo "4. Overlay Should: ✅ Appear automatically with pre-filled token\n";
        } else {
            echo "3. Expected Behavior: ❌ Redirect to maintenance.php\n";
        }
    } else {
        echo "2. Expected Behavior: ✅ Normal access (no overlay needed)\n";
    }
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}

echo "\n🧪 TESTING INSTRUCTIONS:\n";
echo "1. Copy the test URL above\n";
echo "2. Open it in a browser\n";
echo "3. You should see index.php with blurred overlay\n";
echo "4. Token should be pre-filled in the overlay\n";
echo "5. Click 'Verify Token' to proceed to passkey verification\n";
echo "6. If you see maintenance.php instead, there's still an issue\n";
?>