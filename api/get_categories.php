<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET');
header('Access-Control-Allow-Headers: Content-Type');

require_once '../includes/db_connect.php';

try {
    $db = getDB();
    
    $stmt = $db->query("SELECT * FROM categories ORDER BY name ASC");
    $categories = $stmt->fetchAll();
    
    echo json_encode($categories);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'error' => 'Failed to fetch categories',
        'message' => DEBUG_MODE ? $e->getMessage() : 'Internal server error'
    ]);
}
?>
