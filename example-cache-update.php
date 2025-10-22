<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Example - Auto Cache Update</title>
    
    <!-- Include cache manager for asset() helper -->
    <?php require_once 'includes/cache-manager.php'; ?>
    
    <!-- CSS with cache buster -->
    <link rel="stylesheet" href="<?php echo asset('css/style.css'); ?>">
    
    <style>
        body {
            font-family: Arial, sans-serif;
            max-width: 800px;
            margin: 50px auto;
            padding: 20px;
            background: #f5f5f5;
        }
        .example-card {
            background: white;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        h1 {
            color: #333;
            margin-bottom: 20px;
        }
        .info-box {
            background: #e3f2fd;
            padding: 15px;
            border-radius: 5px;
            margin: 20px 0;
            border-left: 4px solid #2196F3;
        }
        .code-box {
            background: #263238;
            color: #aed581;
            padding: 15px;
            border-radius: 5px;
            font-family: 'Courier New', monospace;
            font-size: 14px;
            overflow-x: auto;
            margin: 15px 0;
        }
        .button {
            background: #2196F3;
            color: white;
            padding: 10px 20px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 14px;
            margin: 5px;
        }
        .button:hover {
            background: #1976D2;
        }
        .success {
            color: #4CAF50;
            font-weight: bold;
        }
    </style>
</head>
<body>
    <div class="example-card">
        <h1>🔄 Auto Cache Update Example</h1>
        
        <div class="info-box">
            <strong>ℹ️ This page demonstrates the auto cache update system.</strong>
            <p>The cache version checker is running in the background. Open the console (F12) to see it in action!</p>
        </div>

        <h2>Current Status</h2>
        <div id="status">
            <p>Server Version: <span id="server-version" class="success">Loading...</span></p>
            <p>Cached Version: <span id="cached-version" class="success">Loading...</span></p>
            <p>Status: <span id="sync-status">Checking...</span></p>
        </div>

        <h2>Test Functions</h2>
        <p>Use these buttons to test the cache update system:</p>
        
        <button class="button" onclick="checkCurrentVersion()">
            Check Current Version
        </button>
        
        <button class="button" onclick="getCachedVersion()">
            Get Cached Version
        </button>
        
        <button class="button" onclick="testVersionCheck()">
            Test Version Check
        </button>
        
        <button class="button" onclick="simulateUpdate()">
            Simulate Update (Set Old Version)
        </button>
        
        <button class="button" onclick="forceUpdate()">
            Force Update
        </button>
        
        <button class="button" onclick="clearAllCachesManually()">
            Clear All Caches
        </button>

        <h2>Console Output</h2>
        <div class="code-box" id="console-output">
            Waiting for commands...
        </div>

        <h2>How It Works</h2>
        <div class="info-box">
            <ol>
                <li>The version checker runs automatically when the page loads</li>
                <li>It compares the server version with the cached version in localStorage</li>
                <li>If versions don't match, it:
                    <ul>
                        <li>Clears all caches (Service Worker, storage)</li>
                        <li>Shows a beautiful notification</li>
                        <li>Reloads the page automatically after 2 seconds</li>
                    </ul>
                </li>
                <li>Background checks happen every 60 seconds</li>
                <li>Checks also happen when you return to the tab or focus the window</li>
            </ol>
        </div>

        <h2>Implementation</h2>
        <p>To add this to your pages, simply add this before the closing &lt;/body&gt; tag:</p>
        <div class="code-box">
&lt;?php require_once 'includes/cache-version-check.php'; ?&gt;
        </div>

        <h2>API Endpoint</h2>
        <p>The system also provides a REST API endpoint:</p>
        <div class="code-box">
GET /api/check_version

Response:
{
  "success": true,
  "version": "1729432800",
  "last_maintenance": 1729432800,
  "maintenance_enabled": false,
  "update_required": false
}
        </div>

        <button class="button" onclick="testAPI()">
            Test API
        </button>
    </div>

    <!-- JavaScript for demo -->
    <script>
        function log(message) {
            const output = document.getElementById('console-output');
            const timestamp = new Date().toLocaleTimeString();
            output.innerHTML = `[${timestamp}] ${message}\n` + output.innerHTML;
            console.log(message);
        }

        function checkCurrentVersion() {
            if (typeof TechCompareCache !== 'undefined') {
                const version = TechCompareCache.getCurrentVersion();
                log(`✅ Current server version: ${version}`);
                updateStatus();
            } else {
                log('❌ TechCompareCache not available. Make sure cache-version-check.php is included.');
            }
        }

        function getCachedVersion() {
            const cached = localStorage.getItem('techcompare_site_version');
            log(`✅ Cached version: ${cached || 'Not set'}`);
            updateStatus();
        }

        function testVersionCheck() {
            if (typeof TechCompareCache !== 'undefined') {
                log('🔍 Running version check...');
                TechCompareCache.checkVersion();
                log('✅ Version check completed. See console for details.');
            } else {
                log('❌ TechCompareCache not available.');
            }
        }

        function simulateUpdate() {
            // Set an old version to simulate update scenario
            localStorage.setItem('techcompare_site_version', '123456');
            log('⚠️ Cached version set to old value (123456)');
            log('🔄 Run "Test Version Check" to trigger update notification');
            updateStatus();
        }

        function forceUpdate() {
            if (typeof TechCompareCache !== 'undefined') {
                log('🔄 Forcing update...');
                TechCompareCache.forceUpdate();
            } else {
                log('❌ TechCompareCache not available.');
            }
        }

        async function clearAllCachesManually() {
            if (typeof TechCompareCache !== 'undefined') {
                log('🗑️ Clearing all caches...');
                await TechCompareCache.clearAllCaches();
                log('✅ All caches cleared!');
            } else {
                log('❌ TechCompareCache not available.');
            }
        }

        async function testAPI() {
            try {
                log('🔍 Fetching version from API...');
                const response = await fetch('/Smartphone-Accessories/api/check_version');
                const data = await response.json();
                log('✅ API Response:');
                log(JSON.stringify(data, null, 2));
            } catch (error) {
                log('❌ API Error: ' + error.message);
            }
        }

        function updateStatus() {
            // Update status display
            const serverVersion = typeof TechCompareCache !== 'undefined' 
                ? TechCompareCache.getCurrentVersion() 
                : 'N/A';
            const cachedVersion = localStorage.getItem('techcompare_site_version') || 'Not set';
            const isMatch = serverVersion === cachedVersion;

            document.getElementById('server-version').textContent = serverVersion;
            document.getElementById('cached-version').textContent = cachedVersion;
            document.getElementById('sync-status').textContent = isMatch 
                ? '✅ Synchronized' 
                : '⚠️ Update available';
            document.getElementById('sync-status').style.color = isMatch ? '#4CAF50' : '#ff9800';
        }

        // Initial status update
        setTimeout(() => {
            updateStatus();
            log('🎉 Page loaded. Cache version checker is active.');
            log('💡 Tip: Open the browser console (F12) to see detailed logs.');
        }, 1000);

        // Update status every 5 seconds
        setInterval(updateStatus, 5000);
    </script>

    <!-- 
        ⭐ IMPORTANT: This is where the magic happens!
        Include the cache version checker at the bottom of the page
    -->
    <?php require_once 'includes/cache-version-check.php'; ?>
</body>
</html>
