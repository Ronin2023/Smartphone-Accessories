<?php
header('Content-Type: text/plain');
echo "🧪 .HTACCESS TEST - SPECIAL ACCESS\n";
echo "===================================\n\n";

echo "✅ This page loaded successfully!\n";
echo "📍 Current URL: " . $_SERVER['REQUEST_URI'] . "\n";
echo "🔍 Query String: " . ($_SERVER['QUERY_STRING'] ?? 'NONE') . "\n";
echo "🎯 Special Access Token: " . ($_GET['special_access_token'] ?? 'NOT FOUND') . "\n\n";

if (isset($_GET['special_access_token'])) {
    echo "🎉 SUCCESS: Special access token detected!\n";
    echo "🔧 .htaccess rules are working correctly.\n";
    echo "🚀 The 503 error should be resolved.\n\n";
    
    echo "📋 Next Steps:\n";
    echo "1. Test index.php with this token\n";
    echo "2. Verify overlay appears\n";
    echo "3. Test complete verification flow\n";
} else {
    echo "⚠️  No special access token found.\n";
    echo "🔗 Try accessing with ?special_access_token=YOUR_TOKEN\n";
}

echo "\n✨ Test completed at " . date('Y-m-d H:i:s') . "\n";
?>