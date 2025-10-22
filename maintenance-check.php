<?php
/**
 * Maintenance Check Script
 * 
 * This script is called by .htaccess to check if maintenance mode is enabled
 * Returns 'enabled' or 'disabled' based on database setting
 */

try {
    require_once 'includes/config.php';
    require_once 'includes/db_connect.php';
    
    $pdo = getDB();
    $stmt = $pdo->prepare("SELECT setting_value FROM settings WHERE setting_key = 'maintenance_enabled'");
    $stmt->execute();
    $result = $stmt->fetchColumn();
    
    if ($result) {
        echo 'enabled';
    } else {
        echo 'disabled';
    }
} catch (Exception $e) {
    // If there's an error, default to disabled
    echo 'disabled';
}
?>