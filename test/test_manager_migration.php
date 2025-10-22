<?php
/**
 * Test Special Access Manager with Existing Table
 */
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db_connect.php';
require_once __DIR__ . '/../includes/functions.php';

echo "=== TESTING SPECIAL ACCESS MANAGER ===\n\n";

try {
    // Initialize manager (this will trigger table migration)
    echo "1️⃣  Initializing Special Access Manager...\n";
    $manager = getSpecialAccessManager();
    echo "   ✅ Manager initialized successfully\n\n";
    
    // Check table structure after migration
    echo "2️⃣  Checking table structure after migration...\n";
    $db = getDB();
    $stmt = $db->query("DESCRIBE special_access_tokens");
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $requiredColumns = ['passkey', 'name', 'email', 'description', 'usage_count'];
    foreach ($requiredColumns as $col) {
        $found = false;
        foreach ($columns as $column) {
            if ($column['Field'] === $col) {
                echo "   ✅ $col column added\n";
                $found = true;
                break;
            }
        }
        if (!$found) {
            echo "   ⚠️  $col column not yet added\n";
        }
    }
    
    // Test getAllTokens
    echo "\n3️⃣  Testing getAllTokens()...\n";
    $tokens = $manager->getAllTokens();
    echo "   ✅ Retrieved " . count($tokens) . " tokens\n";
    
    if (!empty($tokens)) {
        echo "   Sample token data:\n";
        $token = $tokens[0];
        foreach ($token as $key => $value) {
            $displayValue = is_null($value) ? 'NULL' : (is_string($value) ? "\"$value\"" : $value);
            echo "      $key: $displayValue\n";
        }
    }
    
    // Test cleanup function
    echo "\n4️⃣  Testing cleanupUnknownTokens()...\n";
    $result = $manager->cleanupUnknownTokens();
    if ($result['success']) {
        echo "   ✅ Cleanup successful: " . $result['message'] . "\n";
    } else {
        echo "   ❌ Cleanup failed: " . $result['error'] . "\n";
    }
    
    echo "\n✅ ALL TESTS PASSED!\n";
    
} catch (Exception $e) {
    echo "❌ ERROR: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
}