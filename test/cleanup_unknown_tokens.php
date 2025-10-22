<?php
/**
 * Cleanup Unknown Tokens
 * This script deletes all tokens with name 'Unknown'
 */
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db_connect.php';

try {
    // Check existing unknown tokens
    $stmt = $conn->query("SELECT id, name, email, created_at FROM special_access_tokens WHERE name = 'Unknown' OR name IS NULL OR name = ''");
    $unknownTokens = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (empty($unknownTokens)) {
        echo "✅ No unknown tokens found. Database is clean!\n";
    } else {
        echo "Found " . count($unknownTokens) . " unknown tokens:\n";
        foreach ($unknownTokens as $token) {
            echo "  - ID: {$token['id']} | Name: '{$token['name']}' | Email: '{$token['email']}' | Created: {$token['created_at']}\n";
        }
        
        echo "\n🗑️  Deleting unknown tokens...\n";
        
        $stmt = $conn->prepare("DELETE FROM special_access_tokens WHERE name = 'Unknown' OR name IS NULL OR name = ''");
        $stmt->execute();
        
        $deleted = $stmt->rowCount();
        echo "✅ Deleted $deleted token(s)!\n";
    }
    
    // Show remaining tokens
    echo "\n📋 Remaining tokens:\n";
    $stmt = $conn->query("SELECT id, name, email, is_active, usage_count FROM special_access_tokens ORDER BY created_at DESC");
    $remainingTokens = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (empty($remainingTokens)) {
        echo "  No tokens in database (fresh start)\n";
    } else {
        foreach ($remainingTokens as $token) {
            $status = $token['is_active'] ? '✅' : '❌';
            echo "  $status ID: {$token['id']} | {$token['name']} | {$token['email']} | Used: {$token['usage_count']}\n";
        }
    }
    
} catch (Exception $e) {
    echo "❌ ERROR: " . $e->getMessage() . "\n";
}
