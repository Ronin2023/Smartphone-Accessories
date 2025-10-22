<?php
/**
 * Cache Manager - Version Control & Auto-Update System
 * 
 * This system ensures users always have the latest version of the website
 * after maintenance or updates by comparing server version with cached version.
 * 
 * Features:
 * - Automatic cache invalidation after maintenance
 * - Version tracking and comparison
 * - Force refresh when versions mismatch
 * - Service Worker cache clearing
 * - Browser cache busting
 * 
 * @version 1.0
 * @date October 20, 2025
 */

class CacheManager {
    private $db;
    private $versionKey = 'site_version';
    private $lastMaintenanceKey = 'last_maintenance_timestamp';
    
    public function __construct($dbConnection) {
        $this->db = $dbConnection;
    }
    
    /**
     * Get current site version from database
     */
    public function getCurrentVersion() {
        try {
            $stmt = $this->db->prepare("SELECT setting_value FROM settings WHERE setting_key = ?");
            $stmt->execute([$this->versionKey]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($result) {
                return $result['setting_value'];
            }
            
            // If version doesn't exist, create it
            return $this->initializeVersion();
        } catch (Exception $e) {
            error_log("Cache Manager Error: " . $e->getMessage());
            return time(); // Fallback to timestamp
        }
    }
    
    /**
     * Initialize version number in database
     */
    private function initializeVersion() {
        try {
            $version = time();
            $stmt = $this->db->prepare("
                INSERT INTO settings (setting_key, setting_value, updated_at) 
                VALUES (?, ?, NOW())
                ON DUPLICATE KEY UPDATE setting_value = ?, updated_at = NOW()
            ");
            $stmt->execute([$this->versionKey, $version, $version]);
            return $version;
        } catch (Exception $e) {
            error_log("Failed to initialize version: " . $e->getMessage());
            return time();
        }
    }
    
    /**
     * Increment site version (call this after maintenance or updates)
     */
    public function incrementVersion() {
        try {
            $newVersion = time();
            $stmt = $this->db->prepare("
                INSERT INTO settings (setting_key, setting_value, updated_at) 
                VALUES (?, ?, NOW())
                ON DUPLICATE KEY UPDATE setting_value = ?, updated_at = NOW()
            ");
            $stmt->execute([$this->versionKey, $newVersion, $newVersion]);
            
            // Also update last maintenance timestamp
            $this->updateLastMaintenanceTime();
            
            return $newVersion;
        } catch (Exception $e) {
            error_log("Failed to increment version: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Update last maintenance timestamp
     */
    private function updateLastMaintenanceTime() {
        try {
            $timestamp = time();
            $stmt = $this->db->prepare("
                INSERT INTO settings (setting_key, setting_value, updated_at) 
                VALUES (?, ?, NOW())
                ON DUPLICATE KEY UPDATE setting_value = ?, updated_at = NOW()
            ");
            $stmt->execute([$this->lastMaintenanceKey, $timestamp, $timestamp]);
        } catch (Exception $e) {
            error_log("Failed to update maintenance timestamp: " . $e->getMessage());
        }
    }
    
    /**
     * Get last maintenance timestamp
     */
    public function getLastMaintenanceTime() {
        try {
            $stmt = $this->db->prepare("SELECT setting_value FROM settings WHERE setting_key = ?");
            $stmt->execute([$this->lastMaintenanceKey]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            
            return $result ? $result['setting_value'] : 0;
        } catch (Exception $e) {
            return 0;
        }
    }
    
    /**
     * Generate cache buster query string for assets
     */
    public function getCacheBuster() {
        return '?v=' . $this->getCurrentVersion();
    }
    
    /**
     * Get cache headers for API responses
     */
    public function getApiCacheHeaders($maxAge = 300) {
        return [
            'Cache-Control' => 'public, max-age=' . $maxAge,
            'ETag' => md5($this->getCurrentVersion()),
            'Last-Modified' => gmdate('D, d M Y H:i:s', $this->getLastMaintenanceTime()) . ' GMT',
            'X-Site-Version' => $this->getCurrentVersion()
        ];
    }
    
    /**
     * Check if client cache is valid
     */
    public function isClientCacheValid() {
        $currentVersion = $this->getCurrentVersion();
        
        // Check If-None-Match (ETag)
        if (isset($_SERVER['HTTP_IF_NONE_MATCH'])) {
            $clientETag = trim($_SERVER['HTTP_IF_NONE_MATCH'], '"');
            $serverETag = md5($currentVersion);
            if ($clientETag === $serverETag) {
                return true;
            }
        }
        
        // Check If-Modified-Since
        if (isset($_SERVER['HTTP_IF_MODIFIED_SINCE'])) {
            $clientTime = strtotime($_SERVER['HTTP_IF_MODIFIED_SINCE']);
            $serverTime = $this->getLastMaintenanceTime();
            if ($clientTime >= $serverTime) {
                return true;
            }
        }
        
        return false;
    }
    
    /**
     * Send 304 Not Modified response
     */
    public function send304Response() {
        header('HTTP/1.1 304 Not Modified');
        header('ETag: "' . md5($this->getCurrentVersion()) . '"');
        exit();
    }
    
    /**
     * Generate JavaScript code for client-side version check
     */
    public function getVersionCheckScript() {
        $currentVersion = $this->getCurrentVersion();
        $siteUrl = defined('SITE_URL') ? SITE_URL : '';
        
        return <<<JAVASCRIPT
<script>
(function() {
    'use strict';
    
    const SITE_VERSION_KEY = 'techcompare_site_version';
    const LAST_CHECK_KEY = 'techcompare_last_version_check';
    const CHECK_INTERVAL = 60000; // Check every 60 seconds
    const SERVER_VERSION = '{$currentVersion}';
    const SITE_URL = '{$siteUrl}';
    
    /**
     * Check if cached version matches server version
     */
    function checkVersion() {
        const cachedVersion = localStorage.getItem(SITE_VERSION_KEY);
        const lastCheck = localStorage.getItem(LAST_CHECK_KEY);
        const now = Date.now();
        
        // If no cached version, set it
        if (!cachedVersion) {
            localStorage.setItem(SITE_VERSION_KEY, SERVER_VERSION);
            localStorage.setItem(LAST_CHECK_KEY, now);
            return;
        }
        
        // If version mismatch, force full reload
        if (cachedVersion !== SERVER_VERSION) {
            console.log('🔄 New version detected. Updating cache...');
            clearAllCaches().then(() => {
                localStorage.setItem(SITE_VERSION_KEY, SERVER_VERSION);
                localStorage.setItem(LAST_CHECK_KEY, now);
                
                // Show update notification
                showUpdateNotification();
                
                // Force hard reload after 2 seconds
                setTimeout(() => {
                    window.location.reload(true);
                }, 2000);
            });
        } else {
            // Update last check time
            localStorage.setItem(LAST_CHECK_KEY, now);
        }
    }
    
    /**
     * Clear all browser caches
     */
    async function clearAllCaches() {
        try {
            // Clear Service Worker caches
            if ('caches' in window) {
                const cacheNames = await caches.keys();
                await Promise.all(
                    cacheNames.map(cacheName => caches.delete(cacheName))
                );
                console.log('✅ Service Worker caches cleared');
            }
            
            // Clear session storage (except important data)
            const importantKeys = ['user_session', 'auth_token'];
            const sessionKeys = Object.keys(sessionStorage);
            sessionKeys.forEach(key => {
                if (!importantKeys.includes(key)) {
                    sessionStorage.removeItem(key);
                }
            });
            
            // Clear local storage cache data (except version and important data)
            const preserveKeys = [SITE_VERSION_KEY, LAST_CHECK_KEY, 'user_preferences', 'auth_data'];
            const localKeys = Object.keys(localStorage);
            localKeys.forEach(key => {
                if (!preserveKeys.includes(key) && !key.startsWith('user_')) {
                    localStorage.removeItem(key);
                }
            });
            
            console.log('✅ Browser storage cleared');
            
        } catch (error) {
            console.error('Error clearing caches:', error);
        }
    }
    
    /**
     * Show update notification to user
     */
    function showUpdateNotification() {
        // Remove existing notification if any
        const existing = document.getElementById('version-update-notification');
        if (existing) {
            existing.remove();
        }
        
        // Create notification element
        const notification = document.createElement('div');
        notification.id = 'version-update-notification';
        notification.innerHTML = `
            <div style="
                position: fixed;
                top: 20px;
                right: 20px;
                background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
                color: white;
                padding: 20px 25px;
                border-radius: 12px;
                box-shadow: 0 10px 40px rgba(0,0,0,0.3);
                z-index: 999999;
                max-width: 350px;
                animation: slideInRight 0.5s ease-out;
                font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            ">
                <div style="display: flex; align-items: center; margin-bottom: 10px;">
                    <svg style="width: 24px; height: 24px; margin-right: 10px;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
                    </svg>
                    <strong style="font-size: 16px;">Update Available!</strong>
                </div>
                <p style="margin: 0 0 10px 0; font-size: 14px; line-height: 1.5; opacity: 0.95;">
                    We've updated the site with new features and improvements. Refreshing to get the latest version...
                </p>
                <div style="display: flex; align-items: center; font-size: 12px; opacity: 0.8;">
                    <div class="spinner" style="
                        width: 16px;
                        height: 16px;
                        border: 2px solid rgba(255,255,255,0.3);
                        border-top-color: white;
                        border-radius: 50%;
                        animation: spin 1s linear infinite;
                        margin-right: 8px;
                    "></div>
                    Updating...
                </div>
            </div>
        `;
        
        // Add animations
        const style = document.createElement('style');
        style.textContent = `
            @keyframes slideInRight {
                from {
                    transform: translateX(400px);
                    opacity: 0;
                }
                to {
                    transform: translateX(0);
                    opacity: 1;
                }
            }
            @keyframes spin {
                to { transform: rotate(360deg); }
            }
        `;
        document.head.appendChild(style);
        document.body.appendChild(notification);
    }
    
    /**
     * Periodic version check
     */
    function startVersionMonitoring() {
        // Initial check
        checkVersion();
        
        // Periodic checks
        setInterval(checkVersion, CHECK_INTERVAL);
        
        // Check on page visibility change (when user returns to tab)
        document.addEventListener('visibilitychange', () => {
            if (!document.hidden) {
                const lastCheck = parseInt(localStorage.getItem(LAST_CHECK_KEY) || '0');
                const now = Date.now();
                
                // Check if more than 30 seconds since last check
                if (now - lastCheck > 30000) {
                    checkVersion();
                }
            }
        });
        
        // Check on window focus
        window.addEventListener('focus', () => {
            const lastCheck = parseInt(localStorage.getItem(LAST_CHECK_KEY) || '0');
            const now = Date.now();
            
            if (now - lastCheck > 30000) {
                checkVersion();
            }
        });
    }
    
    /**
     * Add cache buster to dynamically loaded resources
     */
    function addCacheBusters() {
        // This will be called when loading new scripts/styles dynamically
        window.addCacheBuster = function(url) {
            const separator = url.includes('?') ? '&' : '?';
            return url + separator + 'v=' + SERVER_VERSION;
        };
    }
    
    // Initialize when DOM is ready
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', () => {
            startVersionMonitoring();
            addCacheBusters();
        });
    } else {
        startVersionMonitoring();
        addCacheBusters();
    }
    
    // Also expose functions globally for manual use
    window.TechCompareCache = {
        checkVersion: checkVersion,
        clearAllCaches: clearAllCaches,
        getCurrentVersion: () => SERVER_VERSION,
        getCachedVersion: () => localStorage.getItem(SITE_VERSION_KEY),
        forceUpdate: () => {
            localStorage.removeItem(SITE_VERSION_KEY);
            checkVersion();
        }
    };
    
    console.log('🔧 TechCompare Cache Manager initialized (v' + SERVER_VERSION + ')');
})();
</script>
JAVASCRIPT;
    }
}

/**
 * Global helper function to get cache manager instance
 */
function getCacheManager() {
    global $pdo;
    static $cacheManager = null;
    
    if ($cacheManager === null && isset($pdo)) {
        $cacheManager = new CacheManager($pdo);
    }
    
    return $cacheManager;
}

/**
 * Helper function to add cache buster to asset URLs
 */
function asset($path) {
    $cacheManager = getCacheManager();
    if ($cacheManager) {
        return $path . $cacheManager->getCacheBuster();
    }
    return $path . '?v=' . time();
}
?>
