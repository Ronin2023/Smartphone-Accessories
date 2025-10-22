<?php
/**
 * Test if getSpecialAccessManager() function is now available
 */

echo "🔧 TESTING FUNCTION AVAILABILITY\n";
echo "================================\n\n";

// Test includes
echo "1️⃣  TESTING INCLUDES\n";

try {
    require_once '../includes/config.php';
    echo "   ✅ config.php loaded\n";
} catch (Exception $e) {
    echo "   ❌ config.php error: " . $e->getMessage() . "\n";
}

try {
    require_once '../includes/db_connect.php';
    echo "   ✅ db_connect.php loaded\n";
} catch (Exception $e) {
    echo "   ❌ db_connect.php error: " . $e->getMessage() . "\n";
}

try {
    require_once '../includes/functions.php';
    echo "   ✅ functions.php loaded\n";
} catch (Exception $e) {
    echo "   ❌ functions.php error: " . $e->getMessage() . "\n";
}

try {
    require_once '../includes/special-access-manager.php';
    echo "   ✅ special-access-manager.php loaded\n";
} catch (Exception $e) {
    echo "   ❌ special-access-manager.php error: " . $e->getMessage() . "\n";
}

echo "\n";

// Test function availability
echo "2️⃣  TESTING FUNCTION AVAILABILITY\n";

if (function_exists('getSpecialAccessManager')) {
    echo "   ✅ getSpecialAccessManager() function exists\n";
    
    try {
        $manager = getSpecialAccessManager();
        if ($manager) {
            echo "   ✅ getSpecialAccessManager() returns manager object\n";
            echo "   📋 Manager class: " . get_class($manager) . "\n";
        } else {
            echo "   ❌ getSpecialAccessManager() returned null\n";
        }
    } catch (Exception $e) {
        echo "   ❌ Error calling getSpecialAccessManager(): " . $e->getMessage() . "\n";
    }
} else {
    echo "   ❌ getSpecialAccessManager() function NOT found\n";
    
    // Check what functions are available
    echo "   🔍 Available functions containing 'special':\n";
    $functions = get_defined_functions()['user'];
    foreach ($functions as $func) {
        if (stripos($func, 'special') !== false) {
            echo "      - $func\n";
        }
    }
}

echo "\n";

// Test verify-special-access.php direct inclusion
echo "3️⃣  TESTING VERIFY-SPECIAL-ACCESS.PHP\n";

// Get a test token first
try {
    $manager = getSpecialAccessManager();
    $tokens = $manager->getAllTokens();
    
    if (!empty($tokens)) {
        $testToken = $tokens[0]['token'];
        echo "   🧪 Test token: " . substr($testToken, 0, 20) . "...\n";
        
        // Simulate accessing verify-special-access.php
        $_GET = ['token' => $testToken];
        $_POST = [];
        $_SERVER['REQUEST_METHOD'] = 'GET';
        
        ob_start();
        try {
            include '../verify-special-access.php';
            $output = ob_get_contents();
            echo "   ✅ verify-special-access.php loaded successfully\n";
            echo "   📄 Output length: " . strlen($output) . " characters\n";
        } catch (Exception $e) {
            echo "   ❌ Error loading verify-special-access.php: " . $e->getMessage() . "\n";
        } finally {
            ob_end_clean();
        }
    } else {
        echo "   ⚠️  No test tokens available\n";
    }
} catch (Exception $e) {
    echo "   ❌ Error getting test tokens: " . $e->getMessage() . "\n";
}

echo "\n✨ Test completed!\n";
?>