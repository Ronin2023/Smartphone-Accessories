<?php
/**
 * Test Database Connection Error Handling
 * This file simulates what happens when the database is down
 */

// Force a database connection attempt to test error handling
try {
    require_once 'includes/config.php';
    require_once 'includes/db_connect.php';
    
    echo "Testing database connection...\n";
    $db = Database::getInstance();
    $connection = $db->getConnection();
    
    echo "✅ Database connection successful!\n";
    echo "Connection status: " . ($db->isConnected() ? "Connected" : "Disconnected") . "\n";
    
} catch (Exception $e) {
    echo "❌ Database connection failed: " . $e->getMessage() . "\n";
    echo "This should have redirected to the error page in a browser.\n";
}
?>