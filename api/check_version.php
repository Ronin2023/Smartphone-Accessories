<?php
/**
 * API Endpoint: Check Site Version
 * 
 * Returns current site version and cache information
 * Used by client-side cache manager for version comparison
 * 
 * Endpoint: /api/version or /api/check_version
 * Method: GET
 * Response: JSON
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Cache-Control: no-cache, no-store, must-revalidate');
header('Pragma: no-cache');
header('Expires: 0');

require_once __DIR__ . '/../includes/db_connect.php';
require_once __DIR__ . '/../includes/cache-manager.php';

try {
    $pdo = getDB();
    $cacheManager = new CacheManager($pdo);
    
    $currentVersion = $cacheManager->getCurrentVersion();
    $lastMaintenance = $cacheManager->getLastMaintenanceTime();
    
    // Get maintenance status
    $stmt = $pdo->prepare("SELECT setting_value FROM settings WHERE setting_key = 'maintenance_enabled'");
    $stmt->execute();
    $maintenanceResult = $stmt->fetch(PDO::FETCH_ASSOC);
    $maintenanceEnabled = $maintenanceResult ? (bool)$maintenanceResult['setting_value'] : false;
    
    $response = [
        'success' => true,
        'version' => $currentVersion,
        'last_maintenance' => $lastMaintenance,
        'last_maintenance_formatted' => date('Y-m-d H:i:s', $lastMaintenance),
        'maintenance_enabled' => $maintenanceEnabled,
        'timestamp' => time(),
        'cache_headers' => $cacheManager->getApiCacheHeaders(0) // No cache for version check
    ];
    
    // Check if client provided their version
    $clientVersion = $_GET['client_version'] ?? null;
    
    if ($clientVersion !== null) {
        $response['update_required'] = ($clientVersion != $currentVersion);
        $response['client_version'] = $clientVersion;
        
        if ($response['update_required']) {
            $response['message'] = 'New version available. Please refresh to get the latest updates.';
        } else {
            $response['message'] = 'You are using the latest version.';
        }
    }
    
    echo json_encode($response, JSON_PRETTY_PRINT);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Failed to check version',
        'message' => $e->getMessage(),
        'timestamp' => time()
    ], JSON_PRETTY_PRINT);
}
?>
