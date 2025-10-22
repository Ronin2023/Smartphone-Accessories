<?php
echo "Testing Contact API..." . PHP_EOL;

// Test data
$postData = http_build_query([
    'name' => 'Test User',
    'email' => 'test@example.com',
    'subject' => 'Test Subject',
    'message' => 'Test message for API testing',
    'priority' => 'medium'
]);

// Create context for POST request
$context = stream_context_create([
    'http' => [
        'method' => 'POST',
        'header' => "Content-Type: application/x-www-form-urlencoded\r\n" .
                   "X-Requested-With: XMLHttpRequest\r\n",
        'content' => $postData
    ]
]);

echo "Sending POST request to API..." . PHP_EOL;

try {
    // Make the request
    $result = file_get_contents('http://localhost/Smartphone-Accessories/api/submit_contact.php', false, $context);
    
    echo "API Response:" . PHP_EOL;
    echo $result . PHP_EOL;
    
    // Try to decode as JSON
    $json = json_decode($result, true);
    if ($json) {
        echo PHP_EOL . "Parsed JSON:" . PHP_EOL;
        print_r($json);
        
        // Check for connection error flag
        if (isset($json['connection_error']) && $json['connection_error']) {
            echo PHP_EOL . "✅ CONNECTION ERROR FLAG DETECTED!" . PHP_EOL;
            echo "This should trigger the error page redirect." . PHP_EOL;
        } else {
            echo PHP_EOL . "❌ No connection error flag found." . PHP_EOL;
        }
    }
    
} catch (Exception $e) {
    echo "Exception occurred: " . $e->getMessage() . PHP_EOL;
    
    // Check HTTP response headers for 503 status
    if (isset($http_response_header)) {
        echo PHP_EOL . "HTTP Response Headers:" . PHP_EOL;
        foreach ($http_response_header as $header) {
            echo $header . PHP_EOL;
            if (strpos($header, '503') !== false) {
                echo "✅ HTTP 503 STATUS DETECTED! This indicates database connection error." . PHP_EOL;
            }
        }
        
        // Try to get the response content even for errors
        $lastError = error_get_last();
        if ($lastError && strpos($lastError['message'], 'HTTP request failed') !== false) {
            echo PHP_EOL . "Attempting to get error response content..." . PHP_EOL;
            
            // Create context that allows error responses
            $errorContext = stream_context_create([
                'http' => [
                    'method' => 'POST',
                    'header' => "Content-Type: application/x-www-form-urlencoded\r\n" .
                               "X-Requested-With: XMLHttpRequest\r\n",
                    'content' => $postData,
                    'ignore_errors' => true
                ]
            ]);
            
            $errorResult = file_get_contents('http://localhost/Smartphone-Accessories/api/submit_contact.php', false, $errorContext);
            if ($errorResult) {
                echo "Error Response Content:" . PHP_EOL;
                echo $errorResult . PHP_EOL;
                
                $errorJson = json_decode($errorResult, true);
                if ($errorJson && isset($errorJson['connection_error'])) {
                    echo PHP_EOL . "✅ CONNECTION ERROR FLAG FOUND IN ERROR RESPONSE!" . PHP_EOL;
                }
            }
        }
    }
}

echo PHP_EOL . "Test completed." . PHP_EOL;
?>