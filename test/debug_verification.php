<?php
/**
 * Debug Token Verification Process
 * This script simulates what happens when the overlay submits the token
 */

require_once '../includes/config.php';
require_once '../includes/db_connect.php';
require_once '../includes/functions.php';

echo "🔍 DEBUG: TOKEN VERIFICATION PROCESS\n";
echo "====================================\n\n";

// Get test token
$manager = getSpecialAccessManager();
$tokens = $manager->getAllTokens();

if (empty($tokens)) {
    echo "❌ No tokens found\n";
    exit;
}

$testToken = $tokens[0]['token'];
echo "🧪 Testing with token: " . substr($testToken, 0, 20) . "...\n\n";

// Test 1: Direct database check
echo "1️⃣  DATABASE TOKEN CHECK\n";
try {
    $pdo = getDB();
    $stmt = $pdo->prepare("SELECT * FROM special_access_tokens WHERE token = ? AND is_active = 1");
    $stmt->execute([$testToken]);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($result) {
        echo "   ✅ Token found in database\n";
        echo "   📋 Token ID: " . $result['id'] . "\n";
        echo "   👤 User: " . ($result['name'] ?? 'Unknown') . "\n";
        echo "   🔑 Passkey: " . $result['passkey'] . "\n";
        echo "   ⚡ Active: " . ($result['is_active'] ? 'YES' : 'NO') . "\n";
    } else {
        echo "   ❌ Token NOT found or inactive\n";
    }
} catch (Exception $e) {
    echo "   ❌ Database error: " . $e->getMessage() . "\n";
}

echo "\n";

// Test 2: Simulate POST to verify-special-access.php
echo "2️⃣  SIMULATE VERIFICATION REQUEST\n";
echo "   🎯 Simulating POST to verify-special-access.php...\n";

// Start output buffering to capture what verify-special-access.php would return
ob_start();

// Simulate POST data
$_POST['token'] = $testToken;
$_SERVER['REQUEST_METHOD'] = 'POST';

try {
    // Include the verification script (but capture its output)
    include '../verify-special-access.php';
    $output = ob_get_contents();
} catch (Exception $e) {
    $output = "Error: " . $e->getMessage();
} finally {
    ob_end_clean();
}

echo "   📄 Response length: " . strlen($output) . " characters\n";
echo "   🔍 Response contains:\n";

// Check what the response contains
$checks = [
    'passkey-form' => 'Passkey form (SUCCESS)',
    'Enter Passkey' => 'Passkey input (SUCCESS)', 
    'Invalid token' => 'Invalid token error',
    'expired' => 'Expired token error',
    'Verification failed' => 'Generic failure',
    'error' => 'General error',
    'success' => 'Success indicator'
];

foreach ($checks as $search => $description) {
    $found = stripos($output, $search) !== false;
    echo "      " . ($found ? "✅" : "❌") . " $description\n";
}

echo "\n";

// Test 3: Check session state
echo "3️⃣  SESSION STATE CHECK\n";
session_start();
echo "   📋 Session ID: " . session_id() . "\n";
echo "   🔑 Special Access Token: " . ($_SESSION['special_access_token'] ?? 'NOT SET') . "\n";
echo "   🎫 Token ID: " . ($_SESSION['special_access_token_id'] ?? 'NOT SET') . "\n";
echo "   ✅ Verified: " . (isset($_SESSION['special_access_verified']) && $_SESSION['special_access_verified'] ? 'YES' : 'NO') . "\n";

echo "\n";

// Test 4: Check verify-special-access.php file
echo "4️⃣  VERIFICATION FILE CHECK\n";
$verifyFile = '../verify-special-access.php';
if (file_exists($verifyFile)) {
    echo "   ✅ verify-special-access.php exists\n";
    echo "   📏 File size: " . filesize($verifyFile) . " bytes\n";
    
    // Check if file is readable
    if (is_readable($verifyFile)) {
        echo "   ✅ File is readable\n";
    } else {
        echo "   ❌ File is not readable\n";
    }
} else {
    echo "   ❌ verify-special-access.php missing\n";
}

echo "\n";

// Test 5: JavaScript fetch simulation
echo "5️⃣  JAVASCRIPT FETCH SIMULATION\n";
echo "   🔄 Testing what JavaScript fetch() receives...\n";

// Reset for clean test
session_destroy();
session_start();
$_POST = ['token' => $testToken];
$_SERVER['REQUEST_METHOD'] = 'POST';

// Capture headers and output
ob_start();
$headers_sent_before = headers_sent();

try {
    include '../verify-special-access.php';
    $response = ob_get_contents();
    $headers_sent_after = headers_sent();
} catch (Exception $e) {
    $response = "PHP Error: " . $e->getMessage();
    $headers_sent_after = false;
} finally {
    ob_end_clean();
}

echo "   📡 Headers sent: " . ($headers_sent_after ? 'YES' : 'NO') . "\n";
echo "   📄 Response preview: " . substr($response, 0, 100) . "...\n";

// Check for redirect headers
$headers = headers_list();
foreach ($headers as $header) {
    if (stripos($header, 'Location:') !== false) {
        echo "   🚀 Redirect found: $header\n";
    }
}

echo "\n✨ Debug completed!\n";
echo "\n📋 TROUBLESHOOTING RECOMMENDATIONS:\n";
echo "   1. Check if token is found in database\n";
echo "   2. Verify verify-special-access.php runs without errors\n";
echo "   3. Check session handling\n";
echo "   4. Test direct access to verify-special-access.php\n";
?>