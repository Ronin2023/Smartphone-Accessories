<?php
// Test the complete special access flow with maintenance enabled
require_once '../includes/config.php';
require_once '../includes/db_connect.php';
require_once '../includes/functions.php';

echo "🔥 COMPREHENSIVE SPECIAL ACCESS FLOW TEST\n";
echo "==========================================\n\n";

// 1. Check maintenance status
echo "1️⃣  MAINTENANCE STATUS CHECK\n";
try {
    $pdo = getDB();
    $stmt = $pdo->prepare("SELECT setting_value FROM settings WHERE setting_key = 'maintenance_enabled'");
    $stmt->execute();
    $maintenanceEnabled = (bool)$stmt->fetchColumn();
    echo "   Maintenance Mode: " . ($maintenanceEnabled ? "✅ ENABLED" : "❌ DISABLED") . "\n\n";
} catch (Exception $e) {
    echo "   ❌ Error checking maintenance: " . $e->getMessage() . "\n\n";
    exit;
}

if (!$maintenanceEnabled) {
    echo "⚠️  MAINTENANCE MODE IS DISABLED!\n";
    echo "   Special access is only needed during maintenance.\n";
    echo "   Enable maintenance mode first.\n\n";
}

// 2. Get test token
echo "2️⃣  TOKEN PREPARATION\n";
$manager = getSpecialAccessManager();
$tokens = $manager->getAllTokens();

if (empty($tokens)) {
    echo "   ❌ No tokens available\n\n";
    exit;
}

$testToken = $tokens[0];
echo "   ✅ Test token selected\n";
echo "   Token: " . substr($testToken['token'], 0, 20) . "...\n";
echo "   Passkey: " . $testToken['passkey'] . "\n";
echo "   User: " . ($testToken['name'] ?? 'Unknown') . "\n\n";

// 3. Test URLs
echo "3️⃣  URL CONSTRUCTION\n";
$baseUrl = SITE_URL;
$testUrls = [
    'index_with_token' => $baseUrl . "/index.php?special_access_token=" . $testToken['token'],
    'direct_test' => $baseUrl . "/direct_test.php?special_access_token=" . $testToken['token'],
    'normal_index' => $baseUrl . "/index.php",
    'maintenance_page' => $baseUrl . "/maintenance.php"
];

foreach ($testUrls as $name => $url) {
    echo "   🔗 $name:\n      $url\n";
}
echo "\n";

// 4. Simulate middleware logic
echo "4️⃣  MIDDLEWARE SIMULATION\n";
$_SERVER['REQUEST_URI'] = '/Smartphone-Accessories/index.php?special_access_token=' . $testToken['token'];
$_SERVER['SCRIPT_NAME'] = '/Smartphone-Accessories/index.php';
$_GET['special_access_token'] = $testToken['token'];

// Test each step
echo "   📍 Current Request: index.php with token\n";

// Check if whitelisted
$isWhitelisted = false;
$whitelist = ['maintenance.php', 'verify-special-access.php', 'admin', 'api'];
foreach ($whitelist as $pattern) {
    if (strpos($_SERVER['REQUEST_URI'], $pattern) !== false) {
        $isWhitelisted = true;
        break;
    }
}
echo "   📋 Is Whitelisted: " . ($isWhitelisted ? "YES" : "NO") . "\n";

// Check admin bypass
$hasAdmin = isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'] === true;
echo "   👤 Has Admin Bypass: " . ($hasAdmin ? "YES" : "NO") . "\n";

// Check active session
$hasActiveSession = $manager && $manager->hasActiveSession();
echo "   🎫 Has Active Session: " . ($hasActiveSession ? "YES" : "NO") . "\n";

// Check special access link
$hasSpecialLink = !empty($_GET['special_access_token']);
echo "   🔗 Has Special Access Link: " . ($hasSpecialLink ? "YES" : "NO") . "\n";

if ($hasSpecialLink) {
    // Validate token
    $stmt = $pdo->prepare("SELECT id FROM special_access_tokens WHERE token = ? AND is_active = 1");
    $stmt->execute([$testToken['token']]);
    $tokenValid = $stmt->fetch();
    echo "   🔑 Token Valid: " . ($tokenValid ? "YES" : "NO") . "\n";
}

// Final decision
echo "\n   🎯 MIDDLEWARE DECISION:\n";
if (!$maintenanceEnabled) {
    echo "      ➤ ALLOW ACCESS (Maintenance disabled)\n";
} elseif ($isWhitelisted) {
    echo "      ➤ ALLOW ACCESS (Whitelisted page)\n";
} elseif ($hasAdmin) {
    echo "      ➤ ALLOW ACCESS (Admin bypass)\n";
} elseif ($hasActiveSession) {
    echo "      ➤ ALLOW ACCESS (Active special session)\n";
} elseif ($hasSpecialLink && $tokenValid) {
    echo "      ➤ ALLOW ACCESS (Valid special access token)\n";
    echo "      ➤ INDEX.PHP SHOULD LOAD WITH OVERLAY\n";
} else {
    echo "      ➤ REDIRECT TO MAINTENANCE\n";
}

echo "\n";

// 5. Test token verification endpoint
echo "5️⃣  TOKEN VERIFICATION TEST\n";
echo "   Testing verify-special-access.php endpoint...\n";

// Simulate POST to verification endpoint
$verifyUrl = $baseUrl . "/verify-special-access.php";
echo "   🎯 Verification URL: $verifyUrl\n";
echo "   📝 Test with token: " . substr($testToken['token'], 0, 20) . "...\n";

// We can't easily simulate POST request here, but we can check if the file exists
if (file_exists('../verify-special-access.php')) {
    echo "   ✅ Verification endpoint exists\n";
} else {
    echo "   ❌ Verification endpoint missing\n";
}

echo "\n";

// 6. Final recommendations
echo "6️⃣  TESTING RECOMMENDATIONS\n";
echo "   🧪 Manual Testing Steps:\n";
echo "   \n";
if ($maintenanceEnabled) {
    echo "   1. Open: " . $testUrls['index_with_token'] . "\n";
    echo "      Expected: Index.php loads with blurred overlay\n";
    echo "   \n";
    echo "   2. In overlay, verify pre-filled token\n";
    echo "      Expected: Token field shows: " . substr($testToken['token'], 0, 20) . "...\n";
    echo "   \n";
    echo "   3. Click 'Verify Token'\n";
    echo "      Expected: Redirect to passkey verification\n";
    echo "   \n";
    echo "   4. Enter passkey: " . $testToken['passkey'] . "\n";
    echo "      Expected: Full site access granted\n";
    echo "   \n";
} else {
    echo "   ⚠️  Enable maintenance mode first!\n";
    echo "   Run: php enable_maintenance.php\n";
    echo "   \n";
}

echo "   🔧 If issues persist:\n";
echo "   - Check browser console for JavaScript errors\n";
echo "   - Verify no caching is affecting results\n";
echo "   - Test with direct_test.php first\n";

echo "\n✨ Test completed!\n";
?>