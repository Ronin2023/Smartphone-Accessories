<?php
require_once 'db_connect.php';

/**
 * Sanitize input data
 */
function sanitize($data) {
    return htmlspecialchars(trim($data), ENT_QUOTES, 'UTF-8');
}

/**
 * Validate email
 */
function isValidEmail($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL);
}

/**
 * Generate secure random token
 */
function generateToken($length = 32) {
    return bin2hex(random_bytes($length));
}

/**
 * Generate CSRF token
 * Creates a unique token for form submission protection
 */
function generateCSRFToken() {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    
    if (!isset($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    
    return $_SESSION['csrf_token'];
}

/**
 * Validate CSRF token
 * Prevents Cross-Site Request Forgery attacks
 * 
 * @param string $token The token to validate
 * @return bool True if valid, false otherwise
 */
function validateCSRFToken($token) {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    
    if (!isset($_SESSION['csrf_token'])) {
        return false;
    }
    
    return hash_equals($_SESSION['csrf_token'], $token);
}

/**
 * Get CSRF token field HTML
 * Convenience function to output hidden CSRF field
 * 
 * @return string HTML hidden input field
 */
function csrfField() {
    $token = generateCSRFToken();
    return '<input type="hidden" name="csrf_token" value="' . htmlspecialchars($token, ENT_QUOTES, 'UTF-8') . '">';
}

/**
 * Hash password
 */
function hashPassword($password) {
    return password_hash($password, PASSWORD_DEFAULT);
}

/**
 * Verify password
 */
function verifyPassword($password, $hash) {
    return password_verify($password, $hash);
}

/**
 * Get all categories
 */
function getCategories() {
    $db = getDB();
    $stmt = $db->query("SELECT * FROM categories ORDER BY name ASC");
    return $stmt->fetchAll();
}

/**
 * Get all brands
 */
function getBrands() {
    $db = getDB();
    $stmt = $db->query("SELECT * FROM brands ORDER BY name ASC");
    return $stmt->fetchAll();
}

/**
 * Get product by ID
 */
function getProductById($id) {
    $db = getDB();
    $stmt = $db->prepare("
        SELECT p.*, b.name as brand_name, c.name as category_name, c.slug as category_slug
        FROM products p 
        LEFT JOIN brands b ON p.brand_id = b.id 
        LEFT JOIN categories c ON p.category_id = c.id 
        WHERE p.id = :id
    ");
    $stmt->bindParam(':id', $id, PDO::PARAM_INT);
    $stmt->execute();
    return $stmt->fetch();
}

/**
 * Get products by category
 */
function getProductsByCategory($categorySlug, $limit = null) {
    $db = getDB();
    $sql = "
        SELECT p.*, b.name as brand_name, c.name as category_name
        FROM products p 
        LEFT JOIN brands b ON p.brand_id = b.id 
        LEFT JOIN categories c ON p.category_id = c.id 
        WHERE c.slug = :category_slug
        ORDER BY p.is_featured DESC, p.rating DESC, p.created_at DESC
    ";
    
    if ($limit) {
        $sql .= " LIMIT :limit";
    }
    
    $stmt = $db->prepare($sql);
    $stmt->bindParam(':category_slug', $categorySlug);
    if ($limit) {
        $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
    }
    $stmt->execute();
    return $stmt->fetchAll();
}

/**
 * Get featured products
 */
function getFeaturedProducts($limit = 6) {
    $db = getDB();
    $stmt = $db->prepare("
        SELECT p.*, b.name as brand_name, c.name as category_name
        FROM products p 
        LEFT JOIN brands b ON p.brand_id = b.id 
        LEFT JOIN categories c ON p.category_id = c.id 
        WHERE p.is_featured = 1 
        ORDER BY p.rating DESC, p.created_at DESC 
        LIMIT :limit
    ");
    $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
    $stmt->execute();
    return $stmt->fetchAll();
}

/**
 * Format price
 */
function formatPrice($price) {
    return '₹' . number_format($price, 2);
}

/**
 * Get price display (with discount if available)
 */
function getPriceDisplay($price, $discountPrice = null) {
    if ($discountPrice) {
        return '<span class="original-price">' . formatPrice($price) . '</span> ' .
               '<span class="current-price">' . formatPrice($discountPrice) . '</span>';
    }
    return '<span class="current-price">' . formatPrice($price) . '</span>';
}

/**
 * Calculate discount percentage
 */
function getDiscountPercentage($originalPrice, $discountPrice) {
    if (!$discountPrice || $originalPrice <= 0) {
        return 0;
    }
    return round((($originalPrice - $discountPrice) / $originalPrice) * 100);
}

/**
 * Get rating stars HTML
 */
function getRatingStars($rating, $maxRating = 5) {
    $html = '<div class="rating-stars">';
    $fullStars = floor($rating);
    $hasHalfStar = ($rating - $fullStars) >= 0.5;
    
    for ($i = 1; $i <= $maxRating; $i++) {
        if ($i <= $fullStars) {
            $html .= '<i class="fas fa-star"></i>';
        } elseif ($i == $fullStars + 1 && $hasHalfStar) {
            $html .= '<i class="fas fa-star-half-alt"></i>';
        } else {
            $html .= '<i class="far fa-star"></i>';
        }
    }
    
    $html .= '</div>';
    return $html;
}

/**
 * Upload file
 */
function uploadFile($file, $targetDir, $allowedTypes = null) {
    if (!$allowedTypes) {
        $allowedTypes = ALLOWED_IMAGE_TYPES;
    }
    
    $targetDir = rtrim($targetDir, '/') . '/';
    
    // Create directory if it doesn't exist
    if (!file_exists($targetDir)) {
        mkdir($targetDir, 0755, true);
    }
    
    $fileName = $file['name'];
    $fileSize = $file['size'];
    $fileTmpName = $file['tmp_name'];
    $fileError = $file['error'];
    
    if ($fileError !== UPLOAD_ERR_OK) {
        throw new Exception('File upload error: ' . $fileError);
    }
    
    if ($fileSize > MAX_FILE_SIZE) {
        throw new Exception('File size exceeds maximum limit');
    }
    
    $fileExt = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
    if (!in_array($fileExt, $allowedTypes)) {
        throw new Exception('File type not allowed');
    }
    
    // Generate unique filename
    $newFileName = uniqid('', true) . '.' . $fileExt;
    $targetPath = $targetDir . $newFileName;
    
    if (!move_uploaded_file($fileTmpName, $targetPath)) {
        throw new Exception('Failed to move uploaded file');
    }
    
    return $newFileName;
}

/**
 * Get setting value
 */
function getSetting($key, $default = null) {
    $db = getDB();
    $stmt = $db->prepare("SELECT setting_value FROM settings WHERE setting_key = :key");
    $stmt->bindParam(':key', $key);
    $stmt->execute();
    $result = $stmt->fetch();
    
    return $result ? $result['setting_value'] : $default;
}

/**
 * Update setting
 */
function updateSetting($key, $value) {
    $db = getDB();
    $stmt = $db->prepare("
        INSERT INTO settings (setting_key, setting_value) 
        VALUES (:key, :value) 
        ON DUPLICATE KEY UPDATE setting_value = :value2
    ");
    $stmt->bindParam(':key', $key);
    $stmt->bindParam(':value', $value);
    $stmt->bindParam(':value2', $value);
    return $stmt->execute();
}

/**
 * Log activity (for admin)
 */
function logActivity($userId, $action, $details = null) {
    $db = getDB();
    $stmt = $db->prepare("
        INSERT INTO activity_log (user_id, action, details, ip_address, user_agent, created_at) 
        VALUES (:user_id, :action, :details, :ip, :user_agent, NOW())
    ");
    
    $stmt->bindParam(':user_id', $userId);
    $stmt->bindParam(':action', $action);
    $stmt->bindParam(':details', $details);
    $stmt->bindValue(':ip', $_SERVER['REMOTE_ADDR'] ?? '');
    $stmt->bindValue(':user_agent', $_SERVER['HTTP_USER_AGENT'] ?? '');
    
    return $stmt->execute();
}

/**
 * Check if user is logged in
 */
function isLoggedIn() {
    return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
}

/**
 * Check if user is admin
 */
function isAdmin() {
    return isLoggedIn() && isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'admin';
}

/**
 * Check if user is editor
 */
function isEditor() {
    return isLoggedIn() && isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'editor';
}

/**
 * Check if user is regular user
 */
function isUser() {
    return isLoggedIn() && isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'user';
}

/**
 * Check if user has admin or editor privileges
 */
function hasAdminAccess() {
    return isAdmin() || isEditor();
}

/**
 * Get user role display name
 */
function getUserRoleDisplay($role) {
    $roles = [
        'admin' => 'Administrator',
        'editor' => 'Editor',
        'user' => 'User'
    ];
    return $roles[$role] ?? 'Unknown';
}

/**
 * Redirect to URL
 */
function redirect($url) {
    // If URL doesn't start with http:// or https://, make it relative
    if (!preg_match('/^https?:\/\//', $url)) {
        // Remove .php extension if present for clean URLs
        $url = preg_replace('/\.php$/', '', $url);
        
        // If URL doesn't start with /, prepend the base path
        if (substr($url, 0, 1) !== '/') {
            // Get the base path from the current script
            $scriptPath = dirname($_SERVER['SCRIPT_NAME']);
            // For files in root, scriptPath might be just '/'
            // For files in subdirectories like /Smartphone-Accessories, we need that path
            if ($scriptPath !== '/') {
                $url = $scriptPath . '/' . $url;
            } else {
                $url = '/' . $url;
            }
        }
    }
    header("Location: $url");
    exit();
}

/**
 * Generate breadcrumb
 */
function generateBreadcrumb($items) {
    $html = '<nav class="breadcrumb"><ol>';
    
    foreach ($items as $item) {
        if (isset($item['url'])) {
            $html .= '<li><a href="' . $item['url'] . '">' . $item['title'] . '</a></li>';
        } else {
            $html .= '<li class="active">' . $item['title'] . '</li>';
        }
    }
    
    $html .= '</ol></nav>';
    return $html;
}

/**
 * Format file size
 */
function formatFileSize($size) {
    $units = ['B', 'KB', 'MB', 'GB'];
    $factor = floor((strlen($size) - 1) / 3);
    return sprintf("%.2f", $size / pow(1024, $factor)) . ' ' . $units[$factor];
}

/**
 * Generate SEO-friendly slug
 */
function generateSlug($text) {
    $text = preg_replace('~[^\pL\d]+~u', '-', $text);
    $text = iconv('utf-8', 'us-ascii//TRANSLIT', $text);
    $text = preg_replace('~[^-\w]+~', '', $text);
    $text = trim($text, '-');
    $text = preg_replace('~-+~', '-', $text);
    $text = strtolower($text);
    
    return empty($text) ? 'n-a' : $text;
}

/**
 * Close database connection
 */
function closeDB(&$db) {
    if ($db) {
        $db = null;
        return true;
    }
    return false;
}

/**
 * Get paginated results
 */
function getPaginatedResults($query, $params = [], $page = 1, $perPage = 10) {
    try {
        $db = getDB();
        
        // Get total count
        $countQuery = "SELECT COUNT(*) FROM ($query) as count_table";
        $stmt = $db->prepare($countQuery);
        $stmt->execute($params);
        $totalRecords = $stmt->fetchColumn();
        
        // Calculate pagination
        $totalPages = ceil($totalRecords / $perPage);
        $offset = ($page - 1) * $perPage;
        
        // Get paginated data
        $paginatedQuery = $query . " LIMIT $perPage OFFSET $offset";
        $stmt = $db->prepare($paginatedQuery);
        $stmt->execute($params);
        $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        closeDB($db);
        
        return [
            'data' => $data,
            'pagination' => [
                'current_page' => $page,
                'total_pages' => $totalPages,
                'total_records' => $totalRecords,
                'per_page' => $perPage,
                'has_previous' => $page > 1,
                'has_next' => $page < $totalPages
            ]
        ];
    } catch (Exception $e) {
        if (isset($db)) closeDB($db);
        throw $e;
    }
}

/**
 * Get Special Access Manager instance
 * 
 * @return SpecialAccessManager
 */
function getSpecialAccessManager() {
    require_once __DIR__ . '/special-access-manager.php';
    $db = getDB();
    return new SpecialAccessManager($db);
}
?>
