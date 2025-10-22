<?php
// Start session to get error information
session_start();

// Get error details from session
$errorInfo = $_SESSION['connection_error'] ?? null;

// Clear the error from session if it exists
if ($errorInfo) {
    unset($_SESSION['connection_error']);
}

// If no PHP session error info, we'll check JavaScript sessionStorage via script
$errorType = $errorInfo['type'] ?? 'database'; // Default to database for this context
$errorMessage = $errorInfo['message'] ?? 'Connection failed';
$referringPage = $errorInfo['referring_page'] ?? '/';

// Determine error details based on type
$isServerError = ($errorType === 'database');
$iconClass = $isServerError ? 'fas fa-database' : 'fas fa-wifi';
$errorTitle = $isServerError ? 'Server Connection Error' : 'Network Connection Error';
$statusText = $isServerError ? 'Database Server Unavailable' : 'No Internet Connection';

// Background gradient based on error type
$backgroundGradient = $isServerError ? 
    'linear-gradient(135deg, #e74c3c 0%, #c0392b 100%)' : 
    'linear-gradient(135deg, #ff6b6b 0%, #ee5a24 100%)';

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Connection Error - TechCompare</title>
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        /* Custom Connection Error Page Styles */
        .connection-error-container {
            min-height: 100vh;
            background: <?php echo $backgroundGradient; ?>;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
            font-family: 'Arial', sans-serif;
        }

        .connection-error-card {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border-radius: 20px;
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.1);
            padding: 40px;
            text-align: center;
            max-width: 600px;
            width: 100%;
            animation: slideUp 0.6s ease-out;
        }

        @keyframes slideUp {
            from {
                opacity: 0;
                transform: translateY(30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .connection-icon {
            font-size: 5rem;
            color: <?php echo $isServerError ? '#e74c3c' : '#ff6b6b'; ?>;
            margin-bottom: 20px;
            animation: pulse 2s infinite;
        }

        @keyframes pulse {
            0% {
                transform: scale(1);
                opacity: 1;
            }
            50% {
                transform: scale(1.1);
                opacity: 0.7;
            }
            100% {
                transform: scale(1);
                opacity: 1;
            }
        }

        .connection-status {
            display: inline-flex;
            align-items: center;
            gap: 10px;
            background: <?php echo $isServerError ? 'rgba(231, 76, 60, 0.1)' : 'rgba(255, 107, 107, 0.1)'; ?>;
            color: <?php echo $isServerError ? '#e74c3c' : '#ff6b6b'; ?>;
            padding: 10px 20px;
            border-radius: 25px;
            font-size: 1rem;
            font-weight: 500;
            margin-bottom: 20px;
        }

        .status-indicator {
            width: 12px;
            height: 12px;
            background: <?php echo $isServerError ? '#e74c3c' : '#ff6b6b'; ?>;
            border-radius: 50%;
            animation: blink 1s infinite;
        }

        @keyframes blink {
            0%, 50% {
                opacity: 1;
            }
            51%, 100% {
                opacity: 0.3;
            }
        }

        .connection-title {
            font-size: 2.5rem;
            color: #333;
            margin: 20px 0;
            font-weight: 700;
        }

        .connection-description {
            color: #666;
            font-size: 1.1rem;
            line-height: 1.6;
            margin-bottom: 30px;
        }

        .error-details {
            background: #f8f9fa;
            border-left: 4px solid <?php echo $isServerError ? '#e74c3c' : '#ff6b6b'; ?>;
            border-radius: 5px;
            padding: 15px 20px;
            margin: 20px 0;
            text-align: left;
        }

        .error-details h4 {
            margin: 0 0 10px 0;
            color: #333;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .error-details p {
            margin: 5px 0;
            color: #666;
            font-family: 'Courier New', monospace;
            font-size: 0.9rem;
            background: rgba(0,0,0,0.05);
            padding: 8px 12px;
            border-radius: 3px;
        }

        .connection-details {
            background: #f8f9fa;
            border-radius: 10px;
            padding: 20px;
            margin: 20px 0;
            text-align: left;
        }

        .connection-details h4 {
            margin: 0 0 15px 0;
            color: #333;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .connection-details ul {
            margin: 0;
            padding-left: 20px;
            color: #666;
        }

        .connection-details li {
            margin: 8px 0;
        }

        .retry-section {
            margin: 30px 0;
            padding: 20px;
            background: rgba(255, 255, 255, 0.5);
            border-radius: 15px;
            border: 2px dashed <?php echo $isServerError ? '#e74c3c' : '#ff6b6b'; ?>;
        }

        .retry-buttons {
            display: flex;
            gap: 15px;
            justify-content: center;
            flex-wrap: wrap;
            margin: 20px 0;
        }

        .btn {
            padding: 12px 24px;
            border-radius: 25px;
            text-decoration: none;
            font-weight: 500;
            transition: all 0.3s ease;
            border: none;
            cursor: pointer;
            font-size: 1rem;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }

        .btn-retry {
            background: linear-gradient(135deg, <?php echo $isServerError ? '#e74c3c, #c0392b' : '#ff6b6b, #ee5a24'; ?>);
            color: white;
            box-shadow: 0 4px 15px <?php echo $isServerError ? 'rgba(231, 76, 60, 0.4)' : 'rgba(255, 107, 107, 0.4)'; ?>;
        }

        .btn-retry:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px <?php echo $isServerError ? 'rgba(231, 76, 60, 0.6)' : 'rgba(255, 107, 107, 0.6)'; ?>;
        }

        .btn-home {
            background: transparent;
            color: <?php echo $isServerError ? '#e74c3c' : '#ff6b6b'; ?>;
            border: 2px solid <?php echo $isServerError ? '#e74c3c' : '#ff6b6b'; ?>;
        }

        .btn-home:hover {
            background: <?php echo $isServerError ? '#e74c3c' : '#ff6b6b'; ?>;
            color: white;
            transform: translateY(-2px);
        }

        .tips-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
            margin-top: 15px;
        }

        .tip-item {
            background: rgba(255, 255, 255, 0.7);
            padding: 15px;
            border-radius: 10px;
            border-left: 4px solid <?php echo $isServerError ? '#e74c3c' : '#ff6b6b'; ?>;
        }

        .tip-item i {
            color: <?php echo $isServerError ? '#e74c3c' : '#ff6b6b'; ?>;
            margin-right: 8px;
        }

        .tip-item strong {
            display: block;
            margin-bottom: 5px;
            color: #333;
        }

        .tip-item span {
            font-size: 0.9rem;
            color: #666;
        }

        /* Responsive Design */
        @media (max-width: 768px) {
            .connection-error-card {
                padding: 30px 20px;
                margin: 10px;
            }

            .connection-title {
                font-size: 2rem;
            }

            .retry-buttons {
                flex-direction: column;
            }

            .btn {
                width: 100%;
                justify-content: center;
            }

            .tips-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <div class="connection-error-container">
        <div class="connection-error-card">
            <i class="<?php echo $iconClass; ?> connection-icon"></i>
            
            <div class="connection-status">
                <div class="status-indicator"></div>
                <span><?php echo htmlspecialchars($statusText); ?></span>
            </div>

            <h1 class="connection-title"><?php echo htmlspecialchars($errorTitle); ?></h1>
            
            <?php if ($isServerError): ?>
                <p class="connection-description">
                    Our database server is currently unavailable. This could be due to maintenance, server overload, or a temporary technical issue.
                </p>
            <?php else: ?>
                <p class="connection-description">
                    We're having trouble connecting to our servers. This could be due to a network issue on your end or a temporary problem with our service.
                </p>
            <?php endif; ?>

            <!-- Error Details -->
            <?php if ($errorMessage && $errorMessage !== 'Connection failed'): ?>
            <div class="error-details">
                <h4>
                    <i class="fas fa-exclamation-circle"></i>
                    Error Details
                </h4>
                <p><?php echo htmlspecialchars($errorMessage); ?></p>
            </div>
            <?php endif; ?>

            <!-- Connection Details -->
            <div class="connection-details">
                <h4>
                    <i class="fas fa-info-circle"></i>
                    <?php echo $isServerError ? 'What might be causing this?' : 'What might be causing this?'; ?>
                </h4>
                <ul>
                    <?php if ($isServerError): ?>
                        <li>Database server is temporarily down for maintenance</li>
                        <li>High server load causing connection timeouts</li>
                        <li>Database connection pool exhausted</li>
                        <li>Network connectivity issues between web and database server</li>
                    <?php else: ?>
                        <li>Your internet connection is down or unstable</li>
                        <li>Our servers are temporarily unavailable</li>
                        <li>Firewall or proxy settings are blocking the connection</li>
                        <li>DNS resolution issues</li>
                    <?php endif; ?>
                </ul>
            </div>

            <!-- Retry Section -->
            <div class="retry-section">
                <h3>
                    <i class="fas fa-sync-alt"></i>
                    Try Again
                </h3>
                <div class="retry-buttons">
                    <button onclick="retryConnection()" class="btn btn-retry">
                        <i class="fas fa-sync-alt"></i>
                        Retry Connection
                    </button>
                    <button onclick="checkServerStatus()" class="btn btn-home">
                        <i class="fas fa-heartbeat"></i>
                        Check Server Status
                    </button>
                </div>
            </div>

            <!-- Action Buttons -->
            <div class="retry-buttons">
                <a href="index.html" class="btn btn-home">
                    <i class="fas fa-home"></i>
                    Go to Homepage
                </a>
                <a href="<?php echo htmlspecialchars($referringPage); ?>" class="btn btn-home">
                    <i class="fas fa-arrow-left"></i>
                    Try Again
                </a>
                <a href="contact.html" class="btn btn-home">
                    <i class="fas fa-envelope"></i>
                    Contact Support
                </a>
            </div>

            <!-- Connection Tips -->
            <div class="connection-tips" style="margin-top: 30px; padding-top: 20px; border-top: 1px solid #e1e5e9;">
                <h4>Troubleshooting Tips</h4>
                <div class="tips-grid">
                    <?php if ($isServerError): ?>
                        <div class="tip-item">
                            <i class="fas fa-clock"></i>
                            <strong>Wait & Retry</strong>
                            <span>Server issues usually resolve within a few minutes</span>
                        </div>
                        <div class="tip-item">
                            <i class="fas fa-sync-alt"></i>
                            <strong>Refresh Page</strong>
                            <span>Try refreshing the page after a few moments</span>
                        </div>
                        <div class="tip-item">
                            <i class="fas fa-users"></i>
                            <strong>Check Status</strong>
                            <span>Contact support if the issue persists</span>
                        </div>
                        <div class="tip-item">
                            <i class="fas fa-bookmark"></i>
                            <strong>Bookmark & Return</strong>
                            <span>Save this page and return later</span>
                        </div>
                    <?php else: ?>
                        <div class="tip-item">
                            <i class="fas fa-wifi"></i>
                            <strong>Check WiFi</strong>
                            <span>Ensure you're connected to a stable network</span>
                        </div>
                        <div class="tip-item">
                            <i class="fas fa-sync-alt"></i>
                            <strong>Refresh Page</strong>
                            <span>Sometimes a simple refresh fixes the issue</span>
                        </div>
                        <div class="tip-item">
                            <i class="fas fa-shield-alt"></i>
                            <strong>Check Firewall</strong>
                            <span>Disable VPN or proxy temporarily</span>
                        </div>
                        <div class="tip-item">
                            <i class="fas fa-clock"></i>
                            <strong>Wait & Retry</strong>
                            <span>Server issues usually resolve quickly</span>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <script>
        let retryCount = 0;
        const isServerError = <?php echo $isServerError ? 'true' : 'false'; ?>;
        const referringPage = '<?php echo addslashes($referringPage); ?>';
        
        // Check if we need to get error info from sessionStorage
        const phpErrorInfo = <?php echo json_encode($errorInfo); ?>;
        
        if (!phpErrorInfo) {
            // Try to get error info from sessionStorage
            const sessionErrorInfo = sessionStorage.getItem('connectionErrorInfo');
            if (sessionErrorInfo) {
                try {
                    const errorData = JSON.parse(sessionErrorInfo);
                    // Update page elements with error info from sessionStorage
                    updateErrorDisplay(errorData);
                    // Clear the error info from sessionStorage
                    sessionStorage.removeItem('connectionErrorInfo');
                } catch (e) {
                    console.log('Could not parse error info from sessionStorage');
                }
            }
        }
        
        function updateErrorDisplay(errorData) {
            // Update error message if available
            const errorMessageEl = document.querySelector('.error-info p');
            if (errorMessageEl && errorData.message) {
                errorMessageEl.textContent = errorData.message;
            }
            
            // Update referring page for go back button
            if (errorData.referring_page) {
                referringPage = errorData.referring_page;
            }
        }

        function retryConnection() {
            retryCount++;
            const retryBtn = document.querySelector('.btn-retry');
            
            retryBtn.disabled = true;
            retryBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Retrying...';

            // Detect current base path
            const currentPath = window.location.pathname;
            let basePath = '';
            if (currentPath.includes('/Smartphone-Accessories/')) {
                basePath = '/Smartphone-Accessories/';
            } else {
                basePath = '/';
            }

            // Create a special test endpoint that only checks database without redirecting
            const testUrl = basePath + 'test-db-connection.php';

            fetch(testUrl, {
                method: 'GET',
                cache: 'no-cache',
                headers: {
                    'X-Connection-Test': 'true'
                }
            })
            .then(response => {
                // First check if the response is actually successful
                if (!response.ok) {
                    throw new Error(`HTTP ${response.status}: Server error`);
                }
                
                // Check content type to ensure we got the test response, not an HTML redirect
                const contentType = response.headers.get('content-type');
                if (!contentType || !contentType.includes('text/plain')) {
                    throw new Error('Unexpected response format - likely redirected');
                }
                
                return response.text();
            })
            .then(data => {
                // More strict checking - must contain both success indicators
                if (data.includes('✅ Database is available') && 
                    data.includes('Status: READY') && 
                    !data.includes('❌') && 
                    !data.includes('Error:')) {
                    showMessage('Database connection restored! Redirecting...', 'success');
                    setTimeout(() => {
                        window.location.href = referringPage || (basePath + 'index.html');
                    }, 2000);
                } else {
                    // Log what we actually received for debugging
                    console.log('Database test response:', data);
                    throw new Error('Database still unavailable - response: ' + data.substring(0, 100));
                }
            })
            .catch(error => {
                showMessage(`Database still unavailable (attempt ${retryCount}). Please try again in a few moments.`, 'error');
                retryBtn.disabled = false;
                retryBtn.innerHTML = '<i class="fas fa-sync-alt"></i> Retry Connection';
            });
        }

        function checkServerStatus() {
            showMessage('Checking server status...', 'info');
            
            // Detect current base path
            const currentPath = window.location.pathname;
            let basePath = '';
            if (currentPath.includes('/Smartphone-Accessories/')) {
                basePath = '/Smartphone-Accessories/';
            } else {
                basePath = '/';
            }
            
            // Test specific endpoints with proper handling
            const endpoints = [
                { name: 'Web Server', url: basePath + 'index.html' },
                { name: 'Error System', url: basePath + 'connection-error.html' },
                { name: 'Database', url: basePath + 'test-db-connection.php' }
            ];

            let workingServices = 0;
            let completedTests = 0;
            const totalTests = endpoints.length;

            endpoints.forEach(endpoint => {
                const fetchOptions = {
                    method: 'GET',
                    cache: 'no-cache',
                    headers: {
                        'X-Connection-Test': 'true'
                    }
                };

                // For non-database endpoints, use HEAD method
                if (endpoint.name !== 'Database') {
                    fetchOptions.method = 'HEAD';
                    delete fetchOptions.headers;
                }

                fetch(endpoint.url, fetchOptions)
                .then(response => {
                    completedTests++;
                    
                    if (endpoint.name === 'Database') {
                        // For database test, check response content more strictly
                        return response.text().then(text => {
                            if (response.ok && 
                                text.includes('✅ Database is available') && 
                                text.includes('Status: READY') && 
                                !text.includes('❌')) {
                                workingServices++;
                                console.log(`✅ ${endpoint.name}: Online`);
                            } else {
                                console.log(`❌ ${endpoint.name}: Offline - ${text.substring(0, 200)}`);
                            }
                        });
                    } else {
                        // For other endpoints, check status code
                        if (response.ok) {
                            workingServices++;
                            console.log(`✅ ${endpoint.name}: Online`);
                        } else {
                            console.log(`❌ ${endpoint.name}: Offline (Status: ${response.status})`);
                        }
                    }
                })
                .catch(error => {
                    completedTests++;
                    console.log(`❌ ${endpoint.name}: Offline (Error: ${error.message})`);
                })
                .finally(() => {
                    if (completedTests === totalTests) {
                        if (workingServices === totalTests) {
                            showMessage(`All services online (${workingServices}/${totalTests})`, 'success');
                        } else if (workingServices > 0) {
                            showMessage(`Server partially available (${workingServices}/${totalTests} services working)`, 'warning');
                        } else {
                            showMessage('Server appears to be completely unavailable', 'error');
                        }
                    }
                });
            });
        }

        function showMessage(message, type) {
            // Remove existing messages
            const existingMessages = document.querySelectorAll('.status-message');
            existingMessages.forEach(msg => msg.remove());

            const messageDiv = document.createElement('div');
            messageDiv.className = 'status-message';
            messageDiv.style.cssText = `
                position: fixed;
                top: 20px;
                right: 20px;
                padding: 15px 20px;
                border-radius: 10px;
                font-weight: 500;
                z-index: 1000;
                animation: slideInRight 0.3s ease-out;
                max-width: 300px;
                ${getMessageStyles(type)}
            `;
            messageDiv.innerHTML = `
                <i class="${getMessageIcon(type)}" style="margin-right: 8px;"></i>
                ${message}
            `;

            document.body.appendChild(messageDiv);

            setTimeout(() => {
                messageDiv.style.animation = 'slideOutRight 0.3s ease-in';
                setTimeout(() => messageDiv.remove(), 300);
            }, 5000);
        }

        function getMessageStyles(type) {
            const styles = {
                success: 'background: #28a745; color: white;',
                error: 'background: #dc3545; color: white;',
                warning: 'background: #ffc107; color: #212529;',
                info: 'background: #17a2b8; color: white;'
            };
            return styles[type] || styles.info;
        }

        function getMessageIcon(type) {
            const icons = {
                success: 'fas fa-check-circle',
                error: 'fas fa-exclamation-triangle',
                warning: 'fas fa-exclamation-circle',
                info: 'fas fa-info-circle'
            };
            return icons[type] || icons.info;
        }

        // Add CSS animations
        const style = document.createElement('style');
        style.textContent = `
            @keyframes slideInRight {
                from { transform: translateX(100%); opacity: 0; }
                to { transform: translateX(0); opacity: 1; }
            }
            @keyframes slideOutRight {
                from { transform: translateX(0); opacity: 1; }
                to { transform: translateX(100%); opacity: 0; }
            }
        `;
        document.head.appendChild(style);

        // Auto-retry for server errors
        if (isServerError) {
            setTimeout(() => {
                if (retryCount === 0) {
                    retryConnection();
                }
            }, 10000); // Auto-retry after 10 seconds for server errors
        }
    </script>
</body>
</html>