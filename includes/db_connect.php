<?php
require_once 'config.php';

class Database {
    private static $instance = null;
    private $connection;
    private $connectionError = null;
    
    private function __construct() {
        try {
            $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET;
            $options = [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
                PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES " . DB_CHARSET,
                PDO::ATTR_TIMEOUT => 5 // 5 second connection timeout
            ];
            
            $this->connection = new PDO($dsn, DB_USER, DB_PASS, $options);
        } catch (PDOException $e) {
            $this->connectionError = $e;
            $this->handleConnectionError($e);
        }
    }
    
    private function handleConnectionError($e) {
        // Log the error for debugging
        error_log("Database connection failed: " . $e->getMessage());
        
        // Check if this is a request that should redirect to connection error page
        $this->redirectToConnectionError($e);
    }
    
    private function redirectToConnectionError($e) {
        // Log the error for debugging
        error_log("Database connection failed: " . $e->getMessage());
        
        // Always redirect to connection error page for ALL request types
        // This ensures consistent user experience regardless of AJAX or regular requests
        
        // For CLI requests, show error and exit
        if (php_sapi_name() === 'cli') {
            if (DEBUG_MODE) {
                die("Database connection failed: " . $e->getMessage());
            } else {
                die("Database connection failed. Please try again later.");
            }
            return;
        }
        
        // Detect the correct base path
        $currentPath = $_SERVER['REQUEST_URI'] ?? '/';
        $basePath = '/';
        if (strpos($currentPath, '/Smartphone-Accessories/') !== false) {
            $basePath = '/Smartphone-Accessories/';
        }
        
        $errorPageUrl = $basePath . 'connection-error.php';
        
        // Store error information in session for the error page
        if (session_status() == PHP_SESSION_NONE) {
            session_start();
        }
        $_SESSION['connection_error'] = [
            'type' => 'database',
            'message' => DEBUG_MODE ? $e->getMessage() : 'Database connection failed',
            'timestamp' => time(),
            'referring_page' => $_SERVER['HTTP_REFERER'] ?? $basePath
        ];
        
        // Check if we're already on the connection error page to prevent redirect loops
        $currentPath = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
        if (strpos($currentPath, 'connection-error.php') !== false || 
            strpos($currentPath, 'connection-error.html') !== false) {
            // If we're already on the error page, show a simple message
            if (DEBUG_MODE) {
                die("Database connection failed: " . $e->getMessage());
            } else {
                die("Service temporarily unavailable. Please try again later.");
            }
        }
        
        // For ALL request types (including AJAX), redirect to connection error page
        // This ensures consistent behavior and user experience
        header("Location: " . $errorPageUrl);
        exit;
    }
    
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    public function getConnection() {
        if ($this->connection === null) {
            throw new Exception("Database connection not available");
        }
        return $this->connection;
    }
    
    public function isConnected() {
        return $this->connection !== null;
    }
    
    public function getConnectionError() {
        return $this->connectionError;
    }
    
    // Test database connectivity
    public function testConnection() {
        if (!$this->isConnected()) {
            return false;
        }
        
        try {
            $this->connection->query('SELECT 1');
            return true;
        } catch (PDOException $e) {
            error_log("Database test query failed: " . $e->getMessage());
            return false;
        }
    }
    
    // Prevent cloning
    private function __clone() {}
    
    // Prevent unserialization
    public function __wakeup() {
        throw new Exception("Cannot unserialize singleton");
    }
}

// Function to get database connection
function getDB() {
    $database = Database::getInstance();
    
    // If database has a connection error, throw exception
    if (!$database->isConnected()) {
        $errorMessage = $database->getConnectionError() ? $database->getConnectionError()->getMessage() : 'Database connection failed';
        throw new Exception("Database connection not available: " . $errorMessage);
    }
    
    return $database->getConnection();
}

// Function to check if database is available
function isDatabaseAvailable() {
    $database = Database::getInstance();
    return $database->isConnected() && $database->testConnection();
}

// Function to handle database errors gracefully
function handleDatabaseError($callback = null) {
    try {
        $db = getDB();
        if ($callback && is_callable($callback)) {
            return $callback($db);
        }
        return $db;
    } catch (Exception $e) {
        error_log("Database error handled: " . $e->getMessage());
        return false;
    }
}
?>
