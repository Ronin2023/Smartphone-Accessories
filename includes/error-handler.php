<?php
/**
 * Global Error Handler for Database Connection Issues
 * This should be included at the top of critical PHP files
 */

// Set custom error handler for fatal errors
register_shutdown_function(function() {
    $error = error_get_last();
    
    if ($error && $error['type'] === E_ERROR) {
        // Check if it's a database connection error
        if (strpos($error['message'], 'SQLSTATE') !== false || 
            strpos($error['message'], 'Connection refused') !== false ||
            strpos($error['message'], 'No connection could be made') !== false) {
            
            // Clear any existing output
            if (ob_get_level()) {
                ob_clean();
            }
            
            // Redirect to static error page
            $basePath = '';
            if (strpos($_SERVER['REQUEST_URI'], '/Smartphone-Accessories/') !== false) {
                $basePath = '/Smartphone-Accessories';
            }
            
            header('Location: ' . $basePath . '/server-error.html');
            exit;
        }
    }
});

// Set custom exception handler
set_exception_handler(function($exception) {
    // Check if it's a database connection exception
    if (strpos($exception->getMessage(), 'SQLSTATE') !== false || 
        strpos($exception->getMessage(), 'Connection refused') !== false ||
        strpos($exception->getMessage(), 'No connection could be made') !== false) {
        
        // Clear any existing output
        if (ob_get_level()) {
            ob_clean();
        }
        
        // Redirect to static error page
        $basePath = '';
        if (strpos($_SERVER['REQUEST_URI'], '/Smartphone-Accessories/') !== false) {
            $basePath = '/Smartphone-Accessories';
        }
        
        header('Location: ' . $basePath . '/server-error.html');
        exit;
    }
    
    // For other exceptions, show generic error
    if (!headers_sent()) {
        http_response_code(500);
        header('Content-Type: text/html');
    }
    
    echo '<!DOCTYPE html>
    <html>
    <head><title>Server Error</title></head>
    <body>
    <h1>Server Error</h1>
    <p>An unexpected error occurred. Please try again later.</p>
    <a href="javascript:history.back()">Go Back</a>
    </body>
    </html>';
    exit;
});

// Function to safely connect to database with error handling
function safeDBConnect() {
    try {
        require_once __DIR__ . '/config.php';
        require_once __DIR__ . '/db_connect.php';
        return getDB();
    } catch (Exception $e) {
        // Check if it's a connection error
        if (strpos($e->getMessage(), 'SQLSTATE') !== false || 
            strpos($e->getMessage(), 'Connection refused') !== false ||
            strpos($e->getMessage(), 'No connection could be made') !== false) {
            
            // Redirect to error page
            $basePath = '';
            if (strpos($_SERVER['REQUEST_URI'], '/Smartphone-Accessories/') !== false) {
                $basePath = '/Smartphone-Accessories';
            }
            
            header('Location: ' . $basePath . '/server-error.html');
            exit;
        }
        
        // Re-throw other exceptions
        throw $e;
    }
}
?>