<?php
// Simple database connection test
// This file should only test connectivity, not redirect
header('Content-Type: text/plain');
header('Cache-Control: no-cache, no-store, must-revalidate');

// Prevent redirects for this test endpoint
if (isset($_SERVER['HTTP_X_CONNECTION_TEST'])) {
    // Direct database test without using the redirect mechanism
    try {
        require_once 'includes/config.php';
        
        // Direct PDO connection test without using Database class
        $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET;
        $options = [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
            PDO::ATTR_TIMEOUT => 3 // Short timeout for testing
        ];
        
        $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
        $stmt = $pdo->query('SELECT 1 as test');
        $result = $stmt->fetch();
        
        if ($result && $result['test'] == 1) {
            echo "✅ Database is available\n";
            echo "Connection: OK\n";
            echo "Status: READY\n";
            http_response_code(200);
        } else {
            echo "❌ Database query failed\n";
            echo "Status: ERROR\n";
            http_response_code(503);
        }
    } catch (Exception $e) {
        echo "❌ Database connection failed\n";
        echo "Error: " . $e->getMessage() . "\n";
        echo "Status: UNAVAILABLE\n";
        http_response_code(503);
    }
} else {
    // Normal request - use the regular database connection which may redirect
    try {
        require_once 'includes/config.php';
        require_once 'includes/db_connect.php';
        
        $pdo = getDB();
        $stmt = $pdo->query('SELECT 1');
        
        if ($stmt) {
            echo "✅ Database is available\n";
            echo "Connection: OK\n";
            echo "Status: READY\n";
        } else {
            echo "❌ Database query failed\n";
        }
    } catch (Exception $e) {
        echo "❌ Database connection failed\n";
        echo "Error: " . $e->getMessage() . "\n";
    }
}
?>