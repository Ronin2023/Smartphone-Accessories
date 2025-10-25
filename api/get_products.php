<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET');
header('Access-Control-Allow-Headers: Content-Type');

require_once '../includes/db_connect.php';
require_once '../includes/config.php';

try {
    $db = getDB();
    
    // Get query parameters
    $category = $_GET['category'] ?? null;
    $brand = $_GET['brand'] ?? null;
    $search = $_GET['search'] ?? null;
    $sort = $_GET['sort'] ?? 'featured';
    $maxPrice = $_GET['maxPrice'] ?? null;
    $page = (int)($_GET['page'] ?? 1);
    $limit = (int)($_GET['limit'] ?? PRODUCTS_PER_PAGE);
    $offset = ($page - 1) * $limit;
    
    // Build base query
    $whereConditions = [];
    $params = [];
    
    if ($category) {
        $whereConditions[] = "c.slug = :category";
        $params[':category'] = $category;
    }
    
    if ($brand) {
        $whereConditions[] = "b.name = :brand";
        $params[':brand'] = $brand;
    }
    
    if ($search) {
        $whereConditions[] = "(p.name LIKE :search1 OR p.model LIKE :search2 OR p.description LIKE :search3 OR b.name LIKE :search4)";
        $searchParam = '%' . $search . '%';
        $params[':search1'] = $searchParam;
        $params[':search2'] = $searchParam;
        $params[':search3'] = $searchParam;
        $params[':search4'] = $searchParam;
    }
    
    if ($maxPrice) {
        $whereConditions[] = "p.price <= :maxPrice";
        $params[':maxPrice'] = $maxPrice;
    }
    
    $whereClause = !empty($whereConditions) ? 'WHERE ' . implode(' AND ', $whereConditions) : '';
    
    // Determine sort order
    $orderBy = match($sort) {
        'price-low' => 'p.price ASC',
        'price-high' => 'p.price DESC',
        'rating' => 'p.rating DESC, p.review_count DESC',
        'newest' => 'p.created_at DESC',
        default => 'p.is_featured DESC, p.rating DESC, p.created_at DESC'
    };
    
    // Get total count
    $countSql = "SELECT COUNT(*) as total 
                 FROM products p 
                 LEFT JOIN brands b ON p.brand_id = b.id 
                 LEFT JOIN categories c ON p.category_id = c.id 
                 $whereClause";
    
    $countStmt = $db->prepare($countSql);
    $countStmt->execute($params);
    $total = $countStmt->fetch()['total'];
    
    // Get products
    $sql = "SELECT p.*, b.name as brand_name, c.name as category_name, c.slug as category_slug
            FROM products p 
            LEFT JOIN brands b ON p.brand_id = b.id 
            LEFT JOIN categories c ON p.category_id = c.id 
            $whereClause
            ORDER BY $orderBy 
            LIMIT :limit OFFSET :offset";
    
    $stmt = $db->prepare($sql);
    foreach ($params as $key => $value) {
        $stmt->bindValue($key, $value);
    }
    $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
    $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
    $stmt->execute();
    
    $products = $stmt->fetchAll();
    
    // Format the response
    $response = [
        'products' => [],
        'pagination' => [
            'current_page' => $page,
            'per_page' => $limit,
            'total' => $total,
            'total_pages' => ceil($total / $limit),
            'has_next' => $page < ceil($total / $limit),
            'has_prev' => $page > 1
        ]
    ];
    
    foreach ($products as $product) {
        $specifications = json_decode($product['specifications'], true) ?? [];
        
        $response['products'][] = [
            'id' => $product['id'],
            'name' => $product['name'],
            'brand_name' => $product['brand_name'] ?? 'Unknown',
            'category_name' => $product['category_name'] ?? 'Unknown',
            'category_slug' => $product['category_slug'] ?? '',
            'model' => $product['model'],
            'description' => $product['description'],
            'specifications' => $specifications,
            'price' => number_format($product['price'], 2),
            'discount_price' => $product['discount_price'] ? number_format($product['discount_price'], 2) : null,
            'main_image' => $product['main_image'] ?: 'uploads/placeholder.svg',
            'rating' => $product['rating'] ?? 0,
            'review_count' => $product['review_count'] ?? 0,
            'availability_status' => $product['availability_status'],
            'is_featured' => (bool)$product['is_featured']
        ];
    }
    
    echo json_encode($response);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'error' => 'Failed to fetch products',
        'message' => DEBUG_MODE ? $e->getMessage() : 'Internal server error'
    ]);
}
?>
