<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET');
header('Access-Control-Allow-Headers: Content-Type');

require_once '../includes/db_connect.php';
require_once '../includes/config.php';

try {
    $productId = $_GET['id'] ?? null;
    
    if (!$productId) {
        http_response_code(400);
        echo json_encode(['error' => 'Product ID is required']);
        exit;
    }
    
    $db = getDB();
    
    $stmt = $db->prepare("
        SELECT p.*, b.name as brand_name, c.name as category_name, c.slug as category_slug
        FROM products p 
        LEFT JOIN brands b ON p.brand_id = b.id 
        LEFT JOIN categories c ON p.category_id = c.id 
        WHERE p.id = :id
    ");
    $stmt->bindParam(':id', $productId, PDO::PARAM_INT);
    $stmt->execute();
    
    $product = $stmt->fetch();
    
    if (!$product) {
        http_response_code(404);
        echo json_encode(['error' => 'Product not found']);
        exit;
    }
    
    // Format the response
    $gallery_images = [];
    if ($product['gallery_images']) {
        $gallery_data = json_decode($product['gallery_images'], true);
        if (is_array($gallery_data)) {
            foreach ($gallery_data as $image) {
                $gallery_images[] = $image;
            }
        }
    }
    
    $response = [
        'id' => $product['id'],
        'name' => $product['name'],
        'brand_name' => $product['brand_name'] ?? 'Unknown',
        'category_name' => $product['category_name'] ?? 'Unknown',
        'category_slug' => $product['category_slug'] ?? '',
        'model' => $product['model'],
        'description' => $product['description'],
        'specifications' => $product['specifications'],
        'price' => number_format($product['price'], 2),
        'discount_price' => $product['discount_price'] ? number_format($product['discount_price'], 2) : null,
        'main_image' => $product['main_image'] ?: 'uploads/placeholder.svg',
        'gallery_images' => $gallery_images,
        'rating' => $product['rating'] ?? 0,
        'review_count' => $product['review_count'] ?? 0,
        'availability_status' => $product['availability_status'],
        'is_featured' => (bool)$product['is_featured']
    ];
    
    echo json_encode($response);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'error' => 'Failed to fetch product',
        'message' => DEBUG_MODE ? $e->getMessage() : 'Internal server error'
    ]);
}
?>
