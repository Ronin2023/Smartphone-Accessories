<?php
require_once '../includes/config.php';
require_once '../includes/db_connect.php';

echo "Checking Maintenance Mode Status...\n";
echo "====================================\n";

try {
    $pdo = getDB();
    $stmt = $pdo->prepare("SELECT setting_value FROM settings WHERE setting_key = 'maintenance_enabled'");
    $stmt->execute();
    $result = $stmt->fetchColumn();
    
    echo "Maintenance Mode: " . ($result ? "ENABLED" : "DISABLED") . "\n";
    echo "Raw Value: " . var_export($result, true) . "\n";
    
    // Also check if settings table exists
    $stmt = $pdo->query("SHOW TABLES LIKE 'settings'");
    $tableExists = $stmt->rowCount() > 0;
    echo "Settings Table: " . ($tableExists ? "EXISTS" : "NOT FOUND") . "\n";
    
    if ($tableExists) {
        // Check all settings
        $stmt = $pdo->query("SELECT * FROM settings");
        $settings = $stmt->fetchAll(PDO::FETCH_ASSOC);
        echo "\nAll Settings:\n";
        foreach ($settings as $setting) {
            echo "- {$setting['setting_key']}: {$setting['setting_value']}\n";
        }
    }
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>