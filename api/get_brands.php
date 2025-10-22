<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET');
header('Access-Control-Allow-Headers: Content-Type');

require_once '../includes/db_connect.php';
require_once '../includes/config.php';

try {
    $db = getDB();
    
    $stmt = $db->query("SELECT * FROM brands ORDER BY name ASC");
    $brands = $stmt->fetchAll();
    
    // Format brands with absolute URLs for logo
    $formatted_brands = [];
    foreach ($brands as $brand) {
        $formatted_brands[] = [
            'id' => $brand['id'],
            'name' => $brand['name'],
            'logo_url' => $brand['logo_url'] ?: null,
            'website' => $brand['website'],
            'description' => $brand['description']
        ];
    }
    
    echo json_encode($formatted_brands);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'error' => 'Failed to fetch brands',
        'message' => DEBUG_MODE ? $e->getMessage() : 'Internal server error'
    ]);
}
?>
