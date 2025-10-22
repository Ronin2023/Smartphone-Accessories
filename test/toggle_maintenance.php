<?php
require_once '../includes/config.php';
require_once '../includes/db_connect.php';

$action = $_GET['action'] ?? 'status';

try {
    $pdo = getDB();
    
    if ($action === 'enable') {
        $stmt = $pdo->prepare("UPDATE settings SET setting_value = '1' WHERE setting_key = 'maintenance_enabled'");
        $stmt->execute();
        echo "✅ Maintenance mode ENABLED\n";
        
    } elseif ($action === 'disable') {
        $stmt = $pdo->prepare("UPDATE settings SET setting_value = '0' WHERE setting_key = 'maintenance_enabled'");
        $stmt->execute();
        echo "✅ Maintenance mode DISABLED\n";
        
    } else {
        // Show status
        $stmt = $pdo->prepare("SELECT setting_value FROM settings WHERE setting_key = 'maintenance_enabled'");
        $stmt->execute();
        $result = $stmt->fetchColumn();
        
        echo "Current status: " . ($result ? "ENABLED" : "DISABLED") . "\n";
        echo "\nUsage:\n";
        echo "- php toggle_maintenance.php?action=enable\n";
        echo "- php toggle_maintenance.php?action=disable\n";
        echo "- php toggle_maintenance.php (show status)\n";
    }
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}
?>