<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET');
header('Access-Control-Allow-Headers: Content-Type');

require_once '../includes/db_connect.php';
require_once '../includes/config.php';

try {
    $query = $_GET['q'] ?? '';
    
    if (strlen($query) < 2) {
        echo json_encode([]);
        exit;
    }
    
    $db = getDB();
    
    // Search in product names, descriptions, and brand names
    $sql = "SELECT p.id, p.name, p.price, p.discount_price, p.main_image, 
                   b.name as brand_name, c.slug as category_slug
            FROM products p 
            LEFT JOIN brands b ON p.brand_id = b.id 
            LEFT JOIN categories c ON p.category_id = c.id 
            WHERE (p.name LIKE :query 
                   OR p.description LIKE :query 
                   OR b.name LIKE :query 
                   OR p.model LIKE :query)
            ORDER BY 
                CASE 
                    WHEN p.name LIKE :exact_query THEN 1
                    WHEN p.name LIKE :start_query THEN 2
                    WHEN b.name LIKE :start_query THEN 3
                    ELSE 4
                END,
                p.is_featured DESC,
                p.rating DESC
            LIMIT 10";
    
    $searchTerm = '%' . $query . '%';
    $exactTerm = $query . '%';
    
    $stmt = $db->prepare($sql);
    $stmt->bindParam(':query', $searchTerm);
    $stmt->bindParam(':exact_query', $exactTerm);
    $stmt->bindParam(':start_query', $exactTerm);
    $stmt->execute();
    
    $results = $stmt->fetchAll();
    
    $response = [];
    foreach ($results as $product) {
        $response[] = [
            'id' => $product['id'],
            'name' => $product['name'],
            'brand_name' => $product['brand_name'] ?? 'Unknown',
            'price' => number_format($product['price'], 2),
            'discount_price' => $product['discount_price'] ? number_format($product['discount_price'], 2) : null,
            'main_image' => $product['main_image'] ?: 'uploads/placeholder.svg',
            'category_slug' => $product['category_slug'] ?? '',
            'url' => "products.html?id=" . $product['id']
        ];
    }
    
    echo json_encode($response);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'error' => 'Search failed',
        'message' => DEBUG_MODE ? $e->getMessage() : 'Internal server error'
    ]);
}
?>
