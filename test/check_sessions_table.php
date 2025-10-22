<?php
require_once '../includes/config.php';
require_once '../includes/db_connect.php';

echo "📋 SPECIAL ACCESS SESSIONS TABLE STRUCTURE\n";
echo "==========================================\n\n";

try {
    $pdo = getDB();
    $stmt = $pdo->query('DESCRIBE special_access_sessions');
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "Columns in special_access_sessions:\n";
    foreach ($columns as $col) {
        echo "- " . $col['Field'] . " (" . $col['Type'] . ")\n";
    }
    
    echo "\n📊 Current session count:\n";
    $stmt = $pdo->query('SELECT COUNT(*) as total FROM special_access_sessions WHERE is_active = 1');
    $count = $stmt->fetch();
    echo "Active sessions: " . $count['total'] . "\n";
    
    // Simple cleanup - deactivate all sessions for testing
    echo "\n🧹 Cleaning up sessions...\n";
    $stmt = $pdo->prepare("UPDATE special_access_sessions SET is_active = 0");
    $stmt->execute();
    $cleaned = $stmt->rowCount();
    echo "Deactivated $cleaned sessions\n";
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>