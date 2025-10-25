<?php
/**
 * Search Suggestions API
 * Returns product suggestions and related products for AJAX search
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

require_once '../includes/db_connect.php';
require_once '../includes/config.php';

try {
    $db = getDB();
    
    // Get search query
    $query = isset($_GET['q']) ? trim($_GET['q']) : '';
    $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 5;
    
    if (empty($query)) {
        echo json_encode([
            'success' => false,
            'message' => 'Search query is required',
            'suggestions' => []
        ]);
        exit;
    }
    
    // Search for products
    $stmt = $db->prepare("
        SELECT 
            p.id,
            p.name,
            p.model,
            p.price,
            p.discount_price,
            p.main_image,
            p.category_id,
            b.name as brand_name,
            c.name as category_name
        FROM products p
        LEFT JOIN brands b ON p.brand_id = b.id
        LEFT JOIN categories c ON p.category_id = c.id
        WHERE 
            p.name LIKE :query1 
            OR p.model LIKE :query2 
            OR b.name LIKE :query3
            OR c.name LIKE :query4
        ORDER BY 
            CASE 
                WHEN p.name LIKE :exact_query THEN 1
                WHEN p.name LIKE :start_query THEN 2
                ELSE 3
            END,
            p.is_featured DESC,
            p.name ASC
        LIMIT :limit
    ");
    
    $searchQuery = "%{$query}%";
    $exactQuery = $query;
    $startQuery = "{$query}%";
    
    $stmt->bindParam(':query1', $searchQuery);
    $stmt->bindParam(':query2', $searchQuery);
    $stmt->bindParam(':query3', $searchQuery);
    $stmt->bindParam(':query4', $searchQuery);
    $stmt->bindParam(':exact_query', $exactQuery);
    $stmt->bindParam(':start_query', $startQuery);
    $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
    $stmt->execute();
    
    $suggestions = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Format suggestions
    foreach ($suggestions as &$product) {
        $product['display_price'] = $product['discount_price'] ?: $product['price'];
        $product['has_discount'] = !empty($product['discount_price']);
        $product['image_url'] = !empty($product['main_image']) ? $product['main_image'] : 'assets/images/placeholder.jpg';
    }
    
    echo json_encode([
        'success' => true,
        'suggestions' => $suggestions,
        'count' => count($suggestions)
    ]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Error searching products: ' . $e->getMessage(),
        'suggestions' => []
    ]);
}
