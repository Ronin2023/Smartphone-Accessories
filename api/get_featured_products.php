<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET');
header('Access-Control-Allow-Headers: Content-Type');

require_once '../includes/db_connect.php';
require_once '../includes/config.php';

try {
    $db = getDB();
    
    // Get featured products with brand information
    $sql = "SELECT p.*, b.name as brand_name 
            FROM products p 
            LEFT JOIN brands b ON p.brand_id = b.id 
            WHERE p.is_featured = 1 
            ORDER BY p.rating DESC, p.created_at DESC 
            LIMIT 6";
    
    $stmt = $db->prepare($sql);
    $stmt->execute();
    $products = $stmt->fetchAll();
    
    // Format the response
    $response = [];
    foreach ($products as $product) {
        $response[] = [
            'id' => $product['id'],
            'name' => $product['name'],
            'brand_name' => $product['brand_name'] ?? 'Unknown',
            'price' => number_format($product['price'], 2),
            'discount_price' => $product['discount_price'] ? number_format($product['discount_price'], 2) : null,
            'main_image' => $product['main_image'] ?: 'uploads/placeholder.svg',
            'rating' => $product['rating'] ?? 0,
            'review_count' => $product['review_count'] ?? 0,
            'availability_status' => $product['availability_status']
        ];
    }
    
    echo json_encode($response);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'error' => 'Failed to fetch featured products',
        'message' => DEBUG_MODE ? $e->getMessage() : 'Internal server error'
    ]);
}
?>