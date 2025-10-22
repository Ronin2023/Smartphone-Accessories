<?php
/**
 * Cache Version Check Template
 * Include this at the bottom of <body> in all HTML pages
 * 
 * Usage: <?php require_once 'includes/cache-version-check.php'; ?>
 */

require_once __DIR__ . '/db_connect.php';
require_once __DIR__ . '/cache-manager.php';

try {
    $pdo = getDB();
    $cacheManager = new CacheManager($pdo);
    
    // Output the version check script
    echo $cacheManager->getVersionCheckScript();
    
} catch (Exception $e) {
    // Silently fail if database is not available
    error_log("Cache version check failed: " . $e->getMessage());
}
?>
