<?php
require_once '../includes/config.php';
require_once '../includes/db_connect.php';
require_once '../includes/functions.php';

echo "🔍 TOKEN VALIDATION DEBUG\n";
echo "=========================\n\n";

// Get a sample token
$manager = getSpecialAccessManager();
$tokens = $manager->getAllTokens();

if (empty($tokens)) {
    echo "❌ No tokens found.\n";
    exit;
}

$sampleToken = $tokens[0];
$token = $sampleToken['token'];

echo "📋 TOKEN ANALYSIS:\n";
echo "Raw token: $token\n";
echo "Length: " . strlen($token) . " characters\n";
echo "Is hex: " . (ctype_xdigit($token) ? "YES" : "NO") . "\n";
echo "Regex test (/^[a-fA-F0-9]{64}$/): " . (preg_match('/^[a-fA-F0-9]{64}$/', $token) ? "PASS" : "FAIL") . "\n";

// Test URL encoding/decoding
$urlEncoded = urlencode($token);
$urlDecoded = urldecode($urlEncoded);

echo "\n🔗 URL ENCODING TEST:\n";
echo "Original: $token\n";
echo "URL Encoded: $urlEncoded\n";
echo "URL Decoded: $urlDecoded\n";
echo "Encoding changed: " . ($token !== $urlEncoded ? "YES" : "NO") . "\n";
echo "Round trip OK: " . ($token === $urlDecoded ? "YES" : "NO") . "\n";

// Test JavaScript validation
echo "\n🧪 JAVASCRIPT VALIDATION SIMULATION:\n";
echo "Testing with exact token from database...\n";

// Simulate what JavaScript receives
$testUrl = "?special_access_token=$token";
parse_str(parse_url($testUrl, PHP_URL_QUERY), $params);
$receivedToken = $params['special_access_token'] ?? '';

echo "Simulated received token: $receivedToken\n";
echo "Lengths match: " . (strlen($token) === strlen($receivedToken) ? "YES" : "NO") . "\n";
echo "Content matches: " . ($token === $receivedToken ? "YES" : "NO") . "\n";

// Test with problematic characters
echo "\n🔧 CHARACTER ANALYSIS:\n";
for ($i = 0; $i < strlen($token); $i++) {
    $char = $token[$i];
    if (!ctype_xdigit($char)) {
        echo "❌ Non-hex character at position $i: '$char'\n";
    }
}
echo "✅ Character analysis complete.\n";

// Test database query
echo "\n💾 DATABASE VALIDATION:\n";
try {
    $pdo = getDB();
    $stmt = $pdo->prepare("SELECT id, token FROM special_access_tokens WHERE token = ? AND is_active = 1");
    $stmt->execute([$token]);
    $result = $stmt->fetch();
    
    if ($result) {
        echo "✅ Token found in database\n";
        echo "DB Token: " . $result['token'] . "\n";
        echo "Exact match: " . ($token === $result['token'] ? "YES" : "NO") . "\n";
    } else {
        echo "❌ Token NOT found in database\n";
    }
} catch (Exception $e) {
    echo "❌ Database error: " . $e->getMessage() . "\n";
}

echo "\n✨ Debug completed!\n";
?>