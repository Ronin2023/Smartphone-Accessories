<?php
/**
 * Clean up special access sessions
 */

require_once '../includes/config.php';
require_once '../includes/db_connect.php';

echo "🧹 CLEANING UP SPECIAL ACCESS SESSIONS\n";
echo "======================================\n\n";

try {
    $pdo = getDB();
    
    // Check current sessions
    echo "1️⃣  CURRENT ACTIVE SESSIONS\n";
    $stmt = $pdo->query("
        SELECT s.*, t.name, t.email 
        FROM special_access_sessions s
        JOIN special_access_tokens t ON s.token_id = t.id
        WHERE s.is_active = 1
        ORDER BY s.created_at DESC
    ");
    $sessions = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (!empty($sessions)) {
        foreach ($sessions as $session) {
            echo "   📋 Session ID: " . substr($session['session_id'], 0, 10) . "...\n";
            echo "      User: " . $session['name'] . " (" . $session['email'] . ")\n";
            echo "      Created: " . $session['created_at'] . "\n";
            echo "      Expires: " . ($session['expires_at'] ?? 'Never') . "\n";
            echo "      Last Activity: " . ($session['last_activity'] ?? 'Never') . "\n\n";
        }
        echo "   Total active sessions: " . count($sessions) . "\n\n";
        
        // Clean up old sessions
        echo "2️⃣  CLEANING UP OLD SESSIONS\n";
        
        // Deactivate expired sessions
        $stmt = $pdo->prepare("
            UPDATE special_access_sessions 
            SET is_active = 0 
            WHERE expires_at < NOW() AND is_active = 1
        ");
        $stmt->execute();
        $expiredCleaned = $stmt->rowCount();
        echo "   ✅ Deactivated $expiredCleaned expired sessions\n";
        
        // Deactivate sessions older than 1 hour with no activity
        $stmt = $pdo->prepare("
            UPDATE special_access_sessions 
            SET is_active = 0 
            WHERE last_activity < DATE_SUB(NOW(), INTERVAL 1 HOUR) 
            AND is_active = 1
        ");
        $stmt->execute();
        $inactiveCleaned = $stmt->rowCount();
        echo "   ✅ Deactivated $inactiveCleaned inactive sessions (>1 hour)\n";
        
        // For testing purposes, deactivate all sessions
        echo "\n3️⃣  FORCE CLEANUP FOR TESTING\n";
        $stmt = $pdo->prepare("UPDATE special_access_sessions SET is_active = 0");
        $stmt->execute();
        $allCleaned = $stmt->rowCount();
        echo "   🧹 Force deactivated all $allCleaned sessions for testing\n";
        
    } else {
        echo "   ℹ️  No active sessions found\n\n";
    }
    
    // Check max sessions setting
    echo "4️⃣  TOKEN SESSION LIMITS\n";
    $stmt = $pdo->query("
        SELECT name, email, max_sessions, usage_count 
        FROM special_access_tokens 
        WHERE is_active = 1
    ");
    $tokens = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    foreach ($tokens as $token) {
        echo "   👤 " . $token['name'] . " (" . $token['email'] . ")\n";
        echo "      Max Sessions: " . $token['max_sessions'] . "\n";
        echo "      Usage Count: " . $token['usage_count'] . "\n\n";
    }
    
    echo "✅ Cleanup completed!\n";
    echo "\n🧪 NOW TEST AGAIN:\n";
    echo "The passkey verification should work now.\n";
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}
?>