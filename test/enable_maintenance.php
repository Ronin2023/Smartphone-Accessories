<?php
require_once '../includes/config.php';
require_once '../includes/db_connect.php';

try {
    $pdo = getDB();
    $stmt = $pdo->prepare("UPDATE settings SET setting_value = '1' WHERE setting_key = 'maintenance_enabled'");
    $stmt->execute();
    echo "✅ Maintenance mode ENABLED\n";
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}
?>