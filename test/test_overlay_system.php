<?php
/**
 * Test Special Access Overlay System
 * This script tests the new blurred overlay popup system
 */

require_once '../includes/config.php';
require_once '../includes/db_connect.php';
require_once '../includes/functions.php';

echo "🧪 Testing Special Access Overlay System\n";
echo "========================================\n\n";

// Test 1: Check if index.php contains the overlay code
echo "1️⃣  CHECKING OVERLAY CODE IN INDEX.PHP\n";
$indexContent = file_get_contents('../index.php');

$requiredElements = [
    'special-access-overlay' => 'Overlay container',
    'special-access-popup' => 'Popup container',
    'special_access_token' => 'Token parameter detection',
    'blurred-background' => 'Blur effect class',
    'fadeIn' => 'CSS animations'
];

foreach ($requiredElements as $element => $description) {
    if (strpos($indexContent, $element) !== false) {
        echo "✅ $description found\n";
    } else {
        echo "❌ $description missing\n";
    }
}

// Test 2: Check admin interface URL generation
echo "\n2️⃣  CHECKING ADMIN URL GENERATION\n";
$adminContent = file_get_contents('../admin/special-access.php');

if (strpos($adminContent, 'special_access_token=') !== false) {
    echo "✅ Admin generates correct URL parameter\n";
} else {
    echo "❌ Admin URL parameter incorrect\n";
}

// Test 3: Get a sample token for testing
echo "\n3️⃣  GETTING SAMPLE TOKEN FOR TESTING\n";
try {
    $manager = getSpecialAccessManager();
    $tokens = $manager->getAllTokens();
    
    if (!empty($tokens)) {
        $sampleToken = $tokens[0];
        $testUrl = SITE_URL . "?special_access_token=" . $sampleToken['token'];
        
        echo "✅ Sample token found: " . substr($sampleToken['token'], 0, 16) . "...\n";
        echo "🔗 Test URL: $testUrl\n";
        echo "🔑 Passkey: " . $sampleToken['passkey'] . "\n";
        
        // Test URL construction
        echo "\n📝 TESTING INSTRUCTIONS:\n";
        echo "1. Open the test URL in a browser\n";
        echo "2. You should see a blurred overlay popup\n";
        echo "3. The token should be pre-filled\n";
        echo "4. Click 'Verify Token' to test\n";
        echo "5. Enter the passkey when prompted\n";
        
    } else {
        echo "⚠️  No tokens found. Create a token first in admin panel.\n";
    }
    
} catch (Exception $e) {
    echo "❌ Error getting tokens: " . $e->getMessage() . "\n";
}

// Test 4: Verify overlay behavior
echo "\n4️⃣  OVERLAY BEHAVIOR ANALYSIS\n";
$overlayFeatures = [
    'special-access-overlay.*display.*none' => 'Hidden by default',
    'preventDefault.*stopPropagation' => 'Non-closable security',
    'backdrop-filter.*blur' => 'Backdrop blur effect',
    'fadeIn.*slideIn' => 'Smooth animations',
    'shake.*error' => 'Error feedback'
];

foreach ($overlayFeatures as $pattern => $description) {
    if (preg_match('/' . str_replace('.*', '.*?', $pattern) . '/s', $indexContent)) {
        echo "✅ $description implemented\n";
    } else {
        echo "❌ $description missing\n";
    }
}

echo "\n✨ Test completed! Check results above.\n";
echo "💡 To test live: Visit the test URL in a browser during maintenance mode.\n";
?>