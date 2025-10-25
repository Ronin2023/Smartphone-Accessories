<?php
/**
 * Related Products API
 * Returns related products based on a specific product
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

require_once '../includes/db_connect.php';
require_once '../includes/config.php';

try {
    $db = getDB();
    
    // Get product ID
    $productId = isset($_GET['id']) ? (int)$_GET['id'] : 0;
    $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 6;
    
    if ($productId <= 0) {
        echo json_encode([
            'success' => false,
            'message' => 'Product ID is required',
            'products' => []
        ]);
        exit;
    }
    
    // Get the product details first
    $stmt = $db->prepare("
        SELECT category_id, brand_id, price
        FROM products
        WHERE id = :id
    ");
    $stmt->execute([':id' => $productId]);
    $product = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$product) {
        echo json_encode([
            'success' => false,
            'message' => 'Product not found',
            'products' => []
        ]);
        exit;
    }
    
    // Get related products (same category or brand, similar price range)
    $priceMin = $product['price'] * 0.7; // 30% less
    $priceMax = $product['price'] * 1.3; // 30% more
    
    $stmt = $db->prepare("
        SELECT 
            p.id,
            p.name,
            p.model,
            p.price,
            p.discount_price,
            p.main_image,
            b.name as brand_name,
            c.name as category_name
        FROM products p
        LEFT JOIN brands b ON p.brand_id = b.id
        LEFT JOIN categories c ON p.category_id = c.id
        WHERE 
            p.id != :id
            AND (
                p.category_id = :category_id 
                OR p.brand_id = :brand_id
                OR (p.price BETWEEN :price_min AND :price_max)
            )
        ORDER BY 
            CASE 
                WHEN p.category_id = :category_id AND p.brand_id = :brand_id THEN 1
                WHEN p.category_id = :category_id THEN 2
                WHEN p.brand_id = :brand_id THEN 3
                ELSE 4
            END,
            p.is_featured DESC,
            ABS(p.price - :price) ASC
        LIMIT :limit
    ");
    
    $stmt->bindParam(':id', $productId, PDO::PARAM_INT);
    $stmt->bindParam(':category_id', $product['category_id'], PDO::PARAM_INT);
    $stmt->bindParam(':brand_id', $product['brand_id'], PDO::PARAM_INT);
    $stmt->bindParam(':price_min', $priceMin);
    $stmt->bindParam(':price_max', $priceMax);
    $stmt->bindParam(':price', $product['price']);
    $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
    $stmt->execute();
    
    $relatedProducts = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Format products
    foreach ($relatedProducts as &$prod) {
        $prod['display_price'] = $prod['discount_price'] ?: $prod['price'];
        $prod['has_discount'] = !empty($prod['discount_price']);
        $prod['image_url'] = !empty($prod['main_image']) ? $prod['main_image'] : 'assets/images/placeholder.jpg';
    }
    
    echo json_encode([
        'success' => true,
        'products' => $relatedProducts,
        'count' => count($relatedProducts)
    ]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Error fetching related products: ' . $e->getMessage(),
        'products' => []
    ]);
}
