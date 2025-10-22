<?php
require_once 'includes/config.php';
require_once 'includes/db_connect.php';
require_once 'includes/cache-manager.php';

try {
    $pdo = getDB();
    
    // Disable maintenance mode
    $stmt = $pdo->prepare("UPDATE settings SET setting_value = '0' WHERE setting_key = 'maintenance_enabled'");
    $stmt->execute();
    
    // Increment site version to force cache refresh for all users
    $cacheManager = new CacheManager($pdo);
    $newVersion = $cacheManager->incrementVersion();
    
    echo "✅ Maintenance mode disabled successfully!<br>";
    echo "✅ Site version updated (v{$newVersion}) - All users will get latest changes!<br>";
    echo "Site is now accessible to all users.<br>";
    echo "<a href='index.html'>Go to Homepage</a>";
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage();
}
?>