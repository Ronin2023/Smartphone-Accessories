// Connection Error Handler - Network & Server Connection Monitoring
// Include this script in pages that need connection error handling

class ConnectionErrorHandler {
    constructor(options = {}) {
        // Detect the current base path dynamically
        const currentPath = window.location.pathname;
        const basePath = this.detectBasePath(currentPath);
        
        this.options = {
            retryAttempts: options.retryAttempts || 3,
            retryDelay: options.retryDelay || 2000,
            timeoutDuration: options.timeoutDuration || 10000,
            checkInterval: options.checkInterval || 30000,
            errorPageUrl: options.errorPageUrl || basePath + 'connection-error.php',
            apiBasePath: basePath + 'api/',
            ...options
        };

        this.retryCount = 0;
        this.isOnline = navigator.onLine;
        this.lastConnectionCheck = new Date().getTime();
        this.basePath = basePath;
        
        this.init();
    }

    // Detect the base path of the application
    detectBasePath(currentPath) {
        // If we're in a subdirectory like /Smartphone-Accessories/, detect it
        if (currentPath.includes('/Smartphone-Accessories/')) {
            return '/Smartphone-Accessories/';
        }
        // If we're in the root of localhost
        else if (currentPath === '/' || currentPath.startsWith('/index') || 
                 currentPath === '' || currentPath.startsWith('/about') || 
                 currentPath.startsWith('/contact') || currentPath.startsWith('/products')) {
            return '/';
        }
        // Extract base path from current location
        else {
            const pathParts = currentPath.split('/').filter(part => part);
            if (pathParts.length > 0 && pathParts[0] === 'Smartphone-Accessories') {
                return '/Smartphone-Accessories/';
            }
            return '/';
        }
    }

    init() {
        // Listen for online/offline events
        window.addEventListener('online', () => this.handleOnline());
        window.addEventListener('offline', () => this.handleOffline());
        
        // Start periodic connection monitoring
        this.startConnectionMonitoring();
        
        // Override fetch for automatic error handling
        this.interceptFetch();
    }

    // Handle network coming online
    handleOnline() {
        this.isOnline = true;
        console.log('Network connection restored');
        
        // Show success message if we were offline
        this.showConnectionMessage('Connection restored!', 'success');
        
        // Reset retry count
        this.retryCount = 0;
    }

    // Handle network going offline
    handleOffline() {
        this.isOnline = false;
        console.log('Network connection lost');
        
        // Show offline message
        this.showConnectionMessage('Connection lost. Please check your internet.', 'error');
    }

    // Start monitoring connection periodically
    startConnectionMonitoring() {
        setInterval(() => {
            this.checkServerConnection();
        }, this.options.checkInterval);
    }

    // Check if server is reachable
    async checkServerConnection() {
        if (!this.isOnline) return false;

        try {
            const controller = new AbortController();
            const timeoutId = setTimeout(() => controller.abort(), 5000);

            const response = await fetch(this.options.apiBasePath + 'get_products.php?limit=1', {
                method: 'HEAD',
                signal: controller.signal,
                cache: 'no-cache'
            });

            clearTimeout(timeoutId);
            this.lastConnectionCheck = new Date().getTime();
            
            return response.ok;
        } catch (error) {
            console.warn('Server connection check failed:', error);
            return false;
        }
    }

    // Intercept fetch requests to handle connection errors
    interceptFetch() {
        const originalFetch = window.fetch;
        const self = this;

        window.fetch = async function(...args) {
            try {
                // Add timeout to all fetch requests
                const [url, options = {}] = args;
                
                if (!options.signal) {
                    const controller = new AbortController();
                    const timeoutId = setTimeout(() => controller.abort(), self.options.timeoutDuration);
                    options.signal = controller.signal;
                    
                    // Clear timeout when request completes
                    const originalThen = Promise.prototype.then;
                    const promise = originalFetch.apply(this, [url, options]);
                    
                    return promise.finally(() => clearTimeout(timeoutId));
                }

                return await originalFetch.apply(this, args);
                
            } catch (error) {
                return self.handleFetchError(error, ...args);
            }
        };
    }

    // Handle fetch errors
    async handleFetchError(error, url, options = {}) {
        console.error('Fetch error:', error);

        // Determine error type
        const isNetworkError = this.isNetworkError(error);
        const isTimeoutError = error.name === 'AbortError';
        const isServerError = error.name === 'TypeError' && error.message.includes('fetch');

        // Check if this is a connection-related error
        if (isNetworkError || isTimeoutError || isServerError || !this.isOnline) {
            return this.handleConnectionError(error, url, options);
        }

        // Re-throw non-connection errors
        throw error;
    }

    // Check if error is network-related
    isNetworkError(error) {
        const networkErrorMessages = [
            'network error',
            'failed to fetch',
            'connection refused',
            'network request failed',
            'timeout',
            'ERR_NETWORK',
            'ERR_INTERNET_DISCONNECTED'
        ];

        const errorMessage = error.message.toLowerCase();
        return networkErrorMessages.some(msg => errorMessage.includes(msg));
    }

    // Handle connection errors
    async handleConnectionError(error, url, options) {
        this.retryCount++;

        console.log(`Connection error (attempt ${this.retryCount}/${this.options.retryAttempts}):`, error);

        // Show error message
        this.showConnectionMessage(`Connection failed. Retrying... (${this.retryCount}/${this.options.retryAttempts})`, 'warning');

        // If we haven't exceeded retry attempts, retry the request
        if (this.retryCount < this.options.retryAttempts) {
            await this.delay(this.options.retryDelay);
            
            try {
                return await fetch(url, options);
            } catch (retryError) {
                return this.handleConnectionError(retryError, url, options);
            }
        }

        // If all retries failed, redirect to connection error page
        this.redirectToConnectionErrorPage();
        
        // Return a rejected promise to maintain fetch behavior
        return Promise.reject(error);
    }

    // Redirect to connection error page
    redirectToConnectionErrorPage() {
        // Store comprehensive error information
        const errorInfo = {
            type: 'network',
            message: 'Network connection failed after multiple attempts',
            referring_page: window.location.href,
            timestamp: new Date().toISOString(),
            retry_count: this.retryCount
        };
        
        sessionStorage.setItem('connectionErrorInfo', JSON.stringify(errorInfo));
        sessionStorage.setItem('connectionErrorReferrer', window.location.href);
        sessionStorage.setItem('connectionErrorTime', new Date().getTime());
        
        // Show final message before redirect
        this.showConnectionMessage('Unable to connect. Redirecting to connection help page...', 'error');
        
        console.log('Redirecting to:', this.options.errorPageUrl);
        
        // Redirect after a short delay
        setTimeout(() => {
            window.location.href = this.options.errorPageUrl;
        }, 2000);
    }

    // Show connection status messages
    showConnectionMessage(message, type = 'info') {
        // Remove existing connection messages
        const existingMessages = document.querySelectorAll('.connection-status-message');
        existingMessages.forEach(msg => msg.remove());

        // Create message element
        const messageDiv = document.createElement('div');
        messageDiv.className = 'connection-status-message';
        messageDiv.style.cssText = `
            position: fixed;
            top: 20px;
            right: 20px;
            padding: 12px 20px;
            border-radius: 8px;
            font-weight: 500;
            font-size: 14px;
            z-index: 10000;
            max-width: 300px;
            animation: slideInFromRight 0.3s ease-out;
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
            ${this.getMessageStyles(type)}
        `;

        messageDiv.innerHTML = `
            <i class="${this.getMessageIcon(type)}" style="margin-right: 8px;"></i>
            ${message}
        `;

        document.body.appendChild(messageDiv);

        // Auto-remove message
        const duration = type === 'error' ? 8000 : 4000;
        setTimeout(() => {
            messageDiv.style.animation = 'slideOutToRight 0.3s ease-in';
            setTimeout(() => messageDiv.remove(), 300);
        }, duration);
    }

    // Get message styles based on type
    getMessageStyles(type) {
        const styles = {
            success: 'background: #28a745; color: white;',
            error: 'background: #dc3545; color: white;',
            warning: 'background: #ffc107; color: #212529;',
            info: 'background: #17a2b8; color: white;'
        };
        return styles[type] || styles.info;
    }

    // Get message icon based on type
    getMessageIcon(type) {
        const icons = {
            success: 'fas fa-check-circle',
            error: 'fas fa-exclamation-triangle',
            warning: 'fas fa-exclamation-circle',
            info: 'fas fa-info-circle'
        };
        return icons[type] || icons.info;
    }

    // Utility delay function
    delay(ms) {
        return new Promise(resolve => setTimeout(resolve, ms));
    }

    // Reset retry counter (call this on successful requests)
    resetRetryCount() {
        this.retryCount = 0;
    }

    // Check if we should show connection error (for manual error handling)
    shouldShowConnectionError(error) {
        return this.isNetworkError(error) || error.name === 'AbortError' || !this.isOnline;
    }

    // Manual redirect to connection error page
    showConnectionError() {
        this.redirectToConnectionErrorPage();
    }

    // Debug method to check paths
    debugPaths() {
        console.log('Connection Error Handler Debug Info:');
        console.log('Current pathname:', window.location.pathname);
        console.log('Detected base path:', this.basePath);
        console.log('Error page URL:', this.options.errorPageUrl);
        console.log('API base path:', this.options.apiBasePath);
        console.log('Current full URL:', window.location.href);
    }
}

// Add CSS animations for messages
const connectionErrorStyles = document.createElement('style');
connectionErrorStyles.textContent = `
    @keyframes slideInFromRight {
        from {
            transform: translateX(100%);
            opacity: 0;
        }
        to {
            transform: translateX(0);
            opacity: 1;
        }
    }

    @keyframes slideOutToRight {
        from {
            transform: translateX(0);
            opacity: 1;
        }
        to {
            transform: translateX(100%);
            opacity: 0;
        }
    }

    .connection-status-message {
        font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
    }
`;
document.head.appendChild(connectionErrorStyles);

// Auto-initialize connection error handler when DOM is ready
document.addEventListener('DOMContentLoaded', function() {
    // Only initialize if not already done and not on the connection error page
    if (!window.connectionErrorHandler && 
        !window.location.pathname.includes('connection-error.php') && 
        !window.location.pathname.includes('connection-error.php')) {
        
        window.connectionErrorHandler = new ConnectionErrorHandler();
        
        // Debug info for troubleshooting
        console.log('Connection Error Handler initialized');
        window.connectionErrorHandler.debugPaths();
        
        // Add global access for debugging
        window.debugConnectionHandler = () => window.connectionErrorHandler.debugPaths();
    }
});

// Export for manual usage
window.ConnectionErrorHandler = ConnectionErrorHandler;