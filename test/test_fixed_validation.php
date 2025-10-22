<?php
/**
 * Test the fixed token validation
 */

require_once '../includes/config.php';
require_once '../includes/db_connect.php';
require_once '../includes/functions.php';

echo "🔧 TESTING FIXED TOKEN VALIDATION\n";
echo "==================================\n\n";

// Get test token
$manager = getSpecialAccessManager();
$tokens = $manager->getAllTokens();

if (empty($tokens)) {
    echo "❌ No tokens found\n";
    exit;
}

$testToken = $tokens[0]['token'];
echo "🧪 Testing token: " . substr($testToken, 0, 20) . "...\n\n";

// Test the new validation endpoint
echo "1️⃣  TESTING TOKEN VALIDATION ENDPOINT\n";

// Simulate the GET request that JavaScript makes
$_GET['action'] = 'validate_token';
$_GET['token'] = $testToken;

ob_start();
try {
    include '../verify-special-access.php';
    $response = ob_get_contents();
} catch (Exception $e) {
    $response = json_encode(['error' => $e->getMessage()]);
} finally {
    ob_end_clean();
}

echo "   📡 Response: $response\n";

$data = json_decode($response, true);
if ($data && isset($data['valid'])) {
    if ($data['valid']) {
        echo "   ✅ Token validation: SUCCESS\n";
        echo "   🎯 JavaScript should redirect to passkey page\n";
    } else {
        echo "   ❌ Token validation: FAILED\n";
        echo "   🔍 Error: " . ($data['error'] ?? 'Unknown error') . "\n";
    }
} else {
    echo "   ❌ Invalid JSON response\n";
}

echo "\n";

// Test 2: Test the passkey page load
echo "2️⃣  TESTING PASSKEY PAGE ACCESS\n";

// Reset globals
unset($_GET['action']);
$_GET = ['token' => $testToken];
$_POST = [];
$_SERVER['REQUEST_METHOD'] = 'GET';

ob_start();
try {
    include '../verify-special-access.php';
    $passkeyPage = ob_get_contents();
} catch (Exception $e) {
    $passkeyPage = "Error: " . $e->getMessage();
} finally {
    ob_end_clean();
}

echo "   📄 Page loaded: " . (strlen($passkeyPage) > 0 ? "YES" : "NO") . "\n";
echo "   🔍 Contains passkey form: " . (strpos($passkeyPage, 'passkey') !== false ? "YES" : "NO") . "\n";
echo "   🔍 Page length: " . strlen($passkeyPage) . " characters\n";

echo "\n✨ Test completed!\n\n";

echo "📋 EXPECTED FLOW NOW:\n";
echo "1. User clicks special access link\n";
echo "2. Overlay appears with auto-filled token\n";
echo "3. Auto-submits after 1 second\n";
echo "4. JavaScript validates token via JSON endpoint\n";
echo "5. Redirects to verify-special-access.php?token=...\n";
echo "6. User enters passkey: " . $tokens[0]['passkey'] . "\n";
echo "7. Gets full site access\n\n";

echo "🧪 TEST THIS URL:\n";
echo SITE_URL . "?special_access_token=" . $testToken . "\n";
?>