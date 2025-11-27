<?php
/**
 * User Authentication Handler
 * Handles login and registration for regular users
 */

session_start();
require_once 'includes/config.php';
require_once 'includes/db_connect.php';
require_once 'includes/functions.php';

$pdo = getDB();
$response = ['success' => false, 'message' => ''];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validate CSRF token
    if (!validateCSRFToken($_POST['csrf_token'] ?? '')) {
        $response['message'] = 'Invalid security token. Please refresh the page and try again.';
        echo json_encode($response);
        exit();
    }
    
    $action = $_POST['action'] ?? '';
    
    try {
        switch($action) {
            case 'login':
                $username = sanitize($_POST['username']);
                $password = $_POST['password'];
                $remember_me = isset($_POST['remember_me']);
                
                if (empty($username) || empty($password)) {
                    throw new Exception('Username/email and password are required.');
                }
                
                // Find user by username or email (don't filter by is_active here)
                $stmt = $pdo->prepare("
                    SELECT id, username, email, password_hash, role, is_active, status, first_name, last_name 
                    FROM users 
                    WHERE (username = ? OR email = ?) AND role = 'user'
                ");
                $stmt->execute([$username, $username]);
                $user = $stmt->fetch(PDO::FETCH_ASSOC);
                
                if (!$user) {
                    throw new Exception('Invalid username/email or password. Please check your credentials and try again.');
                }
                
                if (!password_verify($password, $user['password_hash'])) {
                    throw new Exception('Invalid username/email or password. Please check your credentials and try again.');
                }
                
                // Check account status with specific error messages
                $status = $user['status'] ?? ($user['is_active'] ? 'active' : 'inactive');
                
                switch($status) {
                    case 'locked':
                        throw new Exception('Your account is locked due to internal policies. Kindly contact admin for support. Email: admin@techcompare.com');
                    case 'suspended':
                        throw new Exception('Your account has been temporarily suspended. Please contact admin for assistance. Email: admin@techcompare.com');
                    case 'inactive':
                        throw new Exception('Your account is inactive. Please contact admin to reactivate your account. Email: admin@techcompare.com');
                    case 'pending':
                        throw new Exception('Your account is pending approval. Please wait for admin verification or contact support. Email: admin@techcompare.com');
                    case 'active':
                        // Regenerate session ID to prevent session fixation
                        session_regenerate_id(true);
                        
                        // Continue with login
                        break;
                    default:
                        if (!$user['is_active']) {
                            throw new Exception('Your account is locked due to internal policies. Kindly contact admin for support. Email: admin@techcompare.com');
                        }
                }
                
                // Set session variables
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['username'] = $user['username'];
                $_SESSION['user_role'] = $user['role'];
                $_SESSION['user_email'] = $user['email'];
                $_SESSION['user_first_name'] = $user['first_name'];
                $_SESSION['user_last_name'] = $user['last_name'];
                
                // Update last login
                $stmt = $pdo->prepare("UPDATE users SET last_login = NOW() WHERE id = ?");
                $stmt->execute([$user['id']]);
                
                // Handle remember me
                if ($remember_me) {
                    $token = bin2hex(random_bytes(32));
                    setcookie('remember_token', $token, time() + (30 * 24 * 60 * 60), '/', '', false, true); // 30 days
                    
                    // Store token in database (you may want to create a remember_tokens table)
                    $stmt = $pdo->prepare("
                        INSERT INTO user_tokens (user_id, token, type, expires_at) 
                        VALUES (?, ?, 'remember', DATE_ADD(NOW(), INTERVAL 30 DAY))
                        ON DUPLICATE KEY UPDATE token = VALUES(token), expires_at = VALUES(expires_at)
                    ");
                    $stmt->execute([$user['id'], hash('sha256', $token)]);
                }
                
                $_SESSION['message'] = 'Welcome back, ' . $user['first_name'] . '!';
                $response['success'] = true;
                $response['redirect'] = 'user_dashboard';
                break;
                
            case 'register':
                $username = sanitize($_POST['username']);
                $email = sanitize($_POST['email']);
                $first_name = sanitize($_POST['first_name']);
                $last_name = sanitize($_POST['last_name']);
                $password = $_POST['password'];
                $confirm_password = $_POST['confirm_password'];
                
                // Validation
                if (empty($username) || empty($email) || empty($password)) {
                    throw new Exception('Username, email, and password are required.');
                }
                
                if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                    throw new Exception('Invalid email format.');
                }
                
                if (strlen($password) < 6) {
                    throw new Exception('Password must be at least 6 characters long.');
                }
                
                if ($password !== $confirm_password) {
                    throw new Exception('Passwords do not match.');
                }
                
                if (strlen($username) < 3) {
                    throw new Exception('Username must be at least 3 characters long.');
                }
                
                if (!preg_match('/^[a-zA-Z0-9_]+$/', $username)) {
                    throw new Exception('Username can only contain letters, numbers, and underscores.');
                }
                
                // Check if username or email already exists
                $stmt = $pdo->prepare("SELECT id FROM users WHERE username = ? OR email = ?");
                $stmt->execute([$username, $email]);
                if ($stmt->fetch()) {
                    throw new Exception('Username or email already exists.');
                }
                
                // Create new user
                $password_hash = password_hash($password, PASSWORD_DEFAULT);
                $stmt = $pdo->prepare("
                    INSERT INTO users (username, email, password_hash, role, first_name, last_name, is_active, created_at) 
                    VALUES (?, ?, ?, 'user', ?, ?, 1, NOW())
                ");
                $stmt->execute([$username, $email, $password_hash, $first_name, $last_name]);
                $user_id = $pdo->lastInsertId();
                
                $_SESSION['message'] = 'Account created successfully! Please login with your credentials.';
                $response['success'] = true;
                $response['redirect'] = 'user_login?tab=login';
                break;
                
            default:
                throw new Exception('Invalid action.');
        }
        
    } catch (Exception $e) {
        $response['message'] = $e->getMessage();
        $_SESSION['error'] = $e->getMessage();
    }
}

closeDB($pdo);

// Handle AJAX requests
if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && $_SERVER['HTTP_X_REQUESTED_WITH'] === 'XMLHttpRequest') {
    header('Content-Type: application/json');
    echo json_encode($response);
    exit;
}

// Handle regular form submissions
if ($response['success'] && isset($response['redirect'])) {
    header('Location: ' . $response['redirect']);
    exit;
} else {
    header('Location: user_login');
    exit;
}
?>