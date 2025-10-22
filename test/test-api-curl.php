<?php
echo "Testing Contact API with cURL..." . PHP_EOL;

// Test data
$postData = [
    'name' => 'Test User',
    'email' => 'test@example.com',
    'subject' => 'Test Subject',
    'message' => 'Test message for API testing',
    'priority' => 'medium'
];

echo "Sending POST request to API..." . PHP_EOL;

// Initialize cURL
$ch = curl_init();

curl_setopt_array($ch, [
    CURLOPT_URL => 'http://localhost/Smartphone-Accessories/api/submit_contact.php',
    CURLOPT_POST => true,
    CURLOPT_POSTFIELDS => http_build_query($postData),
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_HEADER => true,
    CURLOPT_HTTPHEADER => [
        'X-Requested-With: XMLHttpRequest',
        'Content-Type: application/x-www-form-urlencoded'
    ],
    CURLOPT_TIMEOUT => 30,
    CURLOPT_FOLLOWLOCATION => false
]);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$headerSize = curl_getinfo($ch, CURLINFO_HEADER_SIZE);

if (curl_errno($ch)) {
    echo "cURL Error: " . curl_error($ch) . PHP_EOL;
} else {
    echo "HTTP Status Code: " . $httpCode . PHP_EOL;
    
    // Separate headers and body
    $headers = substr($response, 0, $headerSize);
    $body = substr($response, $headerSize);
    
    echo PHP_EOL . "Response Headers:" . PHP_EOL;
    echo $headers . PHP_EOL;
    
    echo "Response Body:" . PHP_EOL;
    echo $body . PHP_EOL;
    
    // Check for success indicators
    if ($httpCode === 503) {
        echo PHP_EOL . "✅ HTTP 503 STATUS DETECTED!" . PHP_EOL;
        echo "This indicates a database connection error." . PHP_EOL;
    }
    
    // Try to decode JSON response
    $json = json_decode($body, true);
    if ($json) {
        echo PHP_EOL . "Parsed JSON Response:" . PHP_EOL;
        print_r($json);
        
        // Check for connection error flag
        if (isset($json['connection_error']) && $json['connection_error']) {
            echo PHP_EOL . "✅ CONNECTION ERROR FLAG DETECTED!" . PHP_EOL;
            echo "The contact form should detect this and redirect to connection-error.php" . PHP_EOL;
        } else if (isset($json['success']) && $json['success']) {
            echo PHP_EOL . "✅ SUCCESSFUL SUBMISSION!" . PHP_EOL;
            echo "Database is working normally." . PHP_EOL;
        } else {
            echo PHP_EOL . "❌ Unexpected response format." . PHP_EOL;
        }
    } else {
        echo PHP_EOL . "❌ Could not parse JSON response." . PHP_EOL;
    }
}

curl_close($ch);

echo PHP_EOL . "Test completed." . PHP_EOL;

// Additional test - check if database is actually connected
echo PHP_EOL . "Checking database connection status..." . PHP_EOL;
try {
    require_once 'includes/config.php';
    require_once 'includes/db_connect.php';
    
    if (isDatabaseAvailable()) {
        echo "✅ Database is available and connected." . PHP_EOL;
    } else {
        echo "❌ Database is NOT available." . PHP_EOL;
    }
} catch (Exception $e) {
    echo "❌ Database check failed: " . $e->getMessage() . PHP_EOL;
}
?>