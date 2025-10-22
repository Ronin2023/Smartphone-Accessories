<?php
session_start();
require_once '../includes/config.php';
require_once '../includes/db_connect.php';
require_once '../includes/functions.php';

// Redirect if already logged in as admin or editor
if (isLoggedIn() && hasAdminAccess()) {
    redirect('dashboard.php');
}

$error = '';
$loginAttempts = $_SESSION['login_attempts'] ?? 0;
$lastAttempt = $_SESSION['last_attempt'] ?? 0;

// Check if locked out
if ($loginAttempts >= MAX_LOGIN_ATTEMPTS && (time() - $lastAttempt) < LOGIN_LOCKOUT_TIME) {
    $timeLeft = LOGIN_LOCKOUT_TIME - (time() - $lastAttempt);
    $error = "Too many failed attempts. Try again in " . ceil($timeLeft / 60) . " minutes.";
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && empty($error)) {
    $username = sanitize($_POST['username']);
    $password = $_POST['password'];
    
    if (!empty($username) && !empty($password)) {
        try {
            $db = getDB();
            $stmt = $db->prepare("SELECT * FROM users WHERE username = ? AND is_active = 1 AND role IN ('admin', 'editor')");
            $stmt->execute([$username]);
            $user = $stmt->fetch();
            
            if ($user && verifyPassword($password, $user['password_hash'])) {
                // Successful login
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['username'] = $user['username'];
                $_SESSION['user_role'] = $user['role'];
                $_SESSION['login_attempts'] = 0;
                
                // Update last login
                $stmt = $db->prepare("UPDATE users SET last_login = NOW() WHERE id = ?");
                $stmt->execute([$user['id']]);
                
                redirect('dashboard.php');
            } else {
                // Failed login
                $_SESSION['login_attempts'] = $loginAttempts + 1;
                $_SESSION['last_attempt'] = time();
                $error = 'Invalid credentials or insufficient privileges';
            }
        } catch (Exception $e) {
            $error = 'Login system temporarily unavailable';
            if (DEBUG_MODE) {
                $error .= ': ' . $e->getMessage();
            }
        }
    } else {
        $error = 'Please fill in all fields';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Login - TechCompare</title>
    <meta name="robots" content="noindex, nofollow">
    
    <!-- CSS -->
    <link rel="stylesheet" href="../css/login.css">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
</head>
<body class="admin-body login-page">
    <div class="login-container">
        <div class="login-box">
            <div class="login-header">
                <div class="logo">
                    <i class="fas fa-balance-scale-right"></i>
                    <h1>TechCompare</h1>
                </div>
                <h2>Admin Login</h2>
                <p>Please sign in to your account</p>
            </div>
            
            <?php if ($error): ?>
                <div class="alert alert-error">
                    <i class="fas fa-exclamation-triangle"></i>
                    <?php echo htmlspecialchars($error); ?>
                </div>
            <?php endif; ?>
            
            <?php if (isset($_GET['logged_out'])): ?>
                <div class="alert alert-success">
                    <i class="fas fa-check-circle"></i>
                    You have been successfully logged out.
                </div>
            <?php endif; ?>
            
            <?php if (isset($_GET['session_expired'])): ?>
                <div class="alert alert-warning">
                    <i class="fas fa-clock"></i>
                    Your session has expired. Please log in again.
                </div>
            <?php endif; ?>
            
            <form id="login-form" class="login-form" method="POST">
                <div class="form-group">
                    <label for="username">Username</label>
                    <div class="input-group">
                        <i class="fas fa-user"></i>
                        <input type="text" id="username" name="username" required placeholder="Enter your username" 
                               value="<?php echo htmlspecialchars($_POST['username'] ?? ''); ?>">
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="password">Password</label>
                    <div class="input-group">
                        <i class="fas fa-lock"></i>
                        <input type="password" id="password" name="password" required placeholder="Enter your password">
                        <button type="button" class="password-toggle" onclick="togglePassword()">
                            <i class="fas fa-eye" id="password-eye"></i>
                        </button>
                    </div>
                </div>
                
                <div class="form-options">
                    <label class="checkbox-container">
                        <input type="checkbox" id="remember" name="remember">
                        <span class="checkmark"></span>
                        Remember me
                    </label>
                </div>
                
                <button type="submit" class="login-btn" <?php echo $error && strpos($error, 'many failed') !== false ? 'disabled' : ''; ?>>
                    <span class="btn-text">Sign In</span>
                    <i class="fas fa-spinner fa-spin btn-spinner" style="display: none;"></i>
                </button>
            </form>
            
            <?php if (DEBUG_MODE): ?>
                <div class="debug-info">
                    <small>Debug: Default credentials - admin / admin@123</small>
                </div>
            <?php endif; ?>
            
            <div class="login-footer">
                <p>&copy; 2025 TechCompare. All rights reserved.</p>
            </div>
        </div>
        
        <div class="login-background">
            <div class="bg-overlay"></div>
            <div class="floating-elements">
                <div class="float-element"><i class="fas fa-mobile-alt"></i></div>
                <div class="float-element"><i class="fas fa-laptop"></i></div>
                <div class="float-element"><i class="fas fa-headphones"></i></div>
                <div class="float-element"><i class="fas fa-watch"></i></div>
                <div class="float-element"><i class="fas fa-tablet-alt"></i></div>
            </div>
        </div>
    </div>
    
    <script>
        function togglePassword() {
            const password = document.getElementById('password');
            const eye = document.getElementById('password-eye');
            
            if (password.type === 'password') {
                password.type = 'text';
                password.classList.add('password-field');
                eye.className = 'fas fa-eye-slash';
            } else {
                password.type = 'password';
                password.classList.remove('password-field');
                eye.className = 'fas fa-eye';
            }
        }
        
        // Form submission handling
        document.getElementById('login-form').addEventListener('submit', function() {
            const btn = document.querySelector('.login-btn');
            const text = btn.querySelector('.btn-text');
            const spinner = btn.querySelector('.btn-spinner');
            
            btn.disabled = true;
            text.style.display = 'none';
            spinner.style.display = 'inline-block';
        });
    </script>
</body>
</html>