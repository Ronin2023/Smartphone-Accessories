<?php
// Simple database connection test endpoint
// Used by connection-error.php to check database availability

header('Content-Type: text/plain');
header('Cache-Control: no-cache, no-store, must-revalidate');
header('Pragma: no-cache');
header('Expires: 0');

// Check if this is a connection test request
if (isset($_SERVER['HTTP_X_CONNECTION_TEST'])) {
    try {
        // Include the database configuration
        require_once 'includes/config.php';
        
        // Test database connection directly without using Database class
        // (to avoid redirect behavior)
        $pdo = new PDO(
            "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME,
            DB_USER,
            DB_PASS,
            [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_TIMEOUT => 5
            ]
        );
        
        // Test with a simple query
        $stmt = $pdo->query("SELECT 1");
        $result = $stmt->fetch();
        
        if ($result) {
            echo "✅ Database is available";
        } else {
            echo "❌ Database query failed";
        }
        
    } catch (PDOException $e) {
        echo "❌ Database connection failed: " . $e->getMessage();
    } catch (Exception $e) {
        echo "❌ Database error: " . $e->getMessage();
    }
    exit;
}

// Original test functionality for debugging
echo "🧪 Testing Current Database Connection Behavior...\n\n";

// Test database availability
require_once 'includes/config.php';
require_once 'includes/db_connect.php';

echo "1. Testing isDatabaseAvailable():\n";
try {
    if (isDatabaseAvailable()) {
        echo "   ✅ Database is available\n";
    } else {
        echo "   ❌ Database is NOT available\n";
    }
} catch (Exception $e) {
    echo "   ❌ Exception: " . $e->getMessage() . "\n";
}

echo "\n2. Testing getDB():\n";
try {
    $pdo = getDB();
    echo "   ✅ getDB() successful\n";
} catch (Exception $e) {
    echo "   ❌ getDB() failed: " . $e->getMessage() . "\n";
}

echo "\n3. Testing Database::getInstance():\n";
try {
    $db = Database::getInstance();
    if ($db->isConnected()) {
        echo "   ✅ Database instance connected\n";
    } else {
        echo "   ❌ Database instance NOT connected\n";
        if ($db->getConnectionError()) {
            echo "   Error: " . $db->getConnectionError()->getMessage() . "\n";
        }
    }
} catch (Exception $e) {
    echo "   ❌ Exception: " . $e->getMessage() . "\n";
}

echo "\n4. Current Server Variables:\n";
echo "   REQUEST_URI: " . ($_SERVER['REQUEST_URI'] ?? 'Not set') . "\n";
echo "   HTTP_X_REQUESTED_WITH: " . ($_SERVER['HTTP_X_REQUESTED_WITH'] ?? 'Not set') . "\n";
echo "   HTTP_ACCEPT: " . ($_SERVER['HTTP_ACCEPT'] ?? 'Not set') . "\n";
echo "   CONTENT_TYPE: " . ($_SERVER['CONTENT_TYPE'] ?? 'Not set') . "\n";

echo "\n✅ Test completed.\n";
?>