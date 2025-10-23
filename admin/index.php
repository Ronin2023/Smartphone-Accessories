<?php
session_start();
require_once '../includes/config.php';
require_once '../includes/db_connect.php';
require_once '../includes/functions.php';

// Redirect to dashboard if already logged in
if (isLoggedIn() && hasAdminAccess()) {
    redirect('dashboard.php');
    exit();
}

// Get database connection
$pdo = getDB();

$error_message = '';
$success_message = '';

// Handle login form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    $remember_me = isset($_POST['remember_me']);
    
    // Basic validation
    if (empty($username) || empty($password)) {
        $error_message = 'Please fill in all fields.';
    } else {
        try {
            // Check if user exists in database (allow both admin and editor)
            $stmt = $pdo->prepare("SELECT * FROM users WHERE username = ? AND role IN ('admin', 'editor') AND is_active = 1");
            $stmt->execute([$username]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($user && verifyPassword($password, $user['password_hash'])) {
                // Login successful - Set standardized session variables
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['username'] = $user['username'];
                $_SESSION['user_role'] = $user['role'];
                $_SESSION['user_email'] = $user['email'];
                $_SESSION['login_time'] = time();
                
                // Legacy session variables for backward compatibility
                $_SESSION['admin_logged_in'] = true;
                $_SESSION['admin_user_id'] = $user['id'];
                $_SESSION['admin_username'] = $user['username'];
                $_SESSION['admin_email'] = $user['email'];
                $_SESSION['admin_name'] = $user['username'];
                
                // Update last login time
                $update_stmt = $pdo->prepare("UPDATE users SET last_login = NOW() WHERE id = ?");
                $update_stmt->execute([$user['id']]);
                
                // Set remember me cookie if requested
                if ($remember_me) {
                    $token = bin2hex(random_bytes(32));
                    setcookie('admin_remember_token', $token, time() + (86400 * 30), '/', '', true, true); // 30 days
                    
                    // Store hashed token in database
                    try {
                        $token_stmt = $pdo->prepare("UPDATE users SET remember_token = ? WHERE id = ?");
                        $token_stmt->execute([hash('sha256', $token), $user['id']]);
                    } catch (PDOException $e) {
                        // If remember_token column doesn't exist, skip this feature
                        error_log("Remember token feature disabled: " . $e->getMessage());
                    }
                }
                
                // Redirect to dashboard
                redirect('dashboard.php');
                exit();
                
            } else {
                $error_message = 'Invalid username or password.';
                
                // Log failed login attempt
                error_log("Failed admin login attempt for username: " . $username . " from IP: " . $_SERVER['REMOTE_ADDR']);
            }
            
        } catch (PDOException $e) {
            error_log("Database error during login: " . $e->getMessage());
            $error_message = 'An error occurred. Please try again.';
        }
    }
}

// Check for remember me cookie
if (!isLoggedIn() && isset($_COOKIE['admin_remember_token'])) {
    try {
        $token = $_COOKIE['admin_remember_token'];
        $hashed_token = hash('sha256', $token);
        
        $stmt = $pdo->prepare("SELECT * FROM users WHERE remember_token = ? AND role IN ('admin', 'editor') AND is_active = 1");
        $stmt->execute([$hashed_token]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($user) {
            // Auto login - Set standardized session variables
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['user_role'] = $user['role'];
            $_SESSION['user_email'] = $user['email'];
            $_SESSION['login_time'] = time();
            
            // Legacy session variables for backward compatibility
            $_SESSION['admin_logged_in'] = true;
            $_SESSION['admin_user_id'] = $user['id'];
            $_SESSION['admin_username'] = $user['username'];
            $_SESSION['admin_email'] = $user['email'];
            $_SESSION['admin_name'] = $user['username'];
            
            redirect('dashboard.php');
            exit();
        }
    } catch (PDOException $e) {
        // Clear invalid cookie if there's an error
        setcookie('admin_remember_token', '', time() - 3600, '/', '', true, true);
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=yes">
    <title>Admin Login - TechCompare</title>
    <meta name="description" content="Secure admin login for TechCompare administration panel">
    <meta name="robots" content="noindex, nofollow">
    
    <!-- CSS -->
    <link rel="stylesheet" href="../css/admin.css">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    
    <style>
        /* Comprehensive fixes for display issues at all zoom levels */
        html, body {
            height: auto !important;
            min-height: 100vh !important;
            overflow: auto !important;
            margin: 0 !important;
            padding: 0 !important;
            width: 100% !important;
        }
        
        .login-page {
            min-height: 100vh !important;
            height: auto !important;
            display: block !important;
            padding: 20px !important;
            box-sizing: border-box !important;
            overflow: visible !important;
            position: static !important;
        }
        
        .login-container {
            margin: 50px auto !important;
            display: block !important;
            position: relative !important;
            max-width: 450px !important;
            width: calc(100% - 40px) !important;
        }
        
        /* Force scrollbars to appear when needed */
        body {
            overflow-x: auto !important;
            overflow-y: auto !important;
        }
        
        /* Additional zoom-specific fixes */
        @media screen and (min-width: 1px) {
            body {
                zoom: 1;
                transform: scale(1);
                overflow: auto !important;
            }
        }
    </style>

</head>
<body class="login-page">
    <div class="login-container">
        <!-- Login Header -->
        <div class="login-header">
            <div class="login-logo">
                <i class="fas fa-shield-alt"></i>
                <h1 class="login-title">Admin Panel</h1>
                <p class="login-subtitle">Secure access to TechCompare administration</p>
            </div>
        </div>

        <!-- Login Form -->
        <form class="login-form" method="POST" action="" id="loginForm">
            <!-- Security Notice -->
            <div class="security-notice">
                <i class="fas fa-info-circle"></i>
                This is a secure area. All login attempts are monitored and logged.
            </div>

            <!-- Error/Success Messages -->
            <?php if (!empty($error_message)): ?>
                <div class="alert alert-danger">
                    <i class="fas fa-exclamation-triangle"></i>
                    <?php echo htmlspecialchars($error_message); ?>
                </div>
            <?php endif; ?>

            <?php if (!empty($success_message)): ?>
                <div class="alert alert-success">
                    <i class="fas fa-check-circle"></i>
                    <?php echo htmlspecialchars($success_message); ?>
                </div>
            <?php endif; ?>

            <!-- Username Field -->
            <div class="form-group">
                <label for="username" class="form-label">Username</label>
                <div class="form-input-container">
                    <input 
                        type="text" 
                        id="username" 
                        name="username" 
                        class="form-input" 
                        placeholder="Enter your username"
                        value="<?php echo htmlspecialchars($_POST['username'] ?? ''); ?>"
                        required
                        autocomplete="username"
                        autofocus
                    >
                    <i class="form-icon fas fa-user"></i>
                </div>
            </div>

            <!-- Password Field -->
            <div class="form-group">
                <label for="password" class="form-label">Password</label>
                <div class="form-input-container">
                    <input 
                        type="password" 
                        id="password" 
                        name="password" 
                        class="form-input" 
                        placeholder="Enter your password"
                        required
                        autocomplete="current-password"
                    >
                    <i class="form-icon fas fa-lock"></i>
                    <button type="button" class="password-toggle" id="togglePassword">
                        <i class="fas fa-eye"></i>
                    </button>
                </div>
            </div>

            <!-- Remember Me -->
            <div class="form-checkbox">
                <input 
                    type="checkbox" 
                    id="remember_me" 
                    name="remember_me" 
                    class="checkbox-input"
                    value="1"
                >
                <label for="remember_me" class="checkbox-label">
                    Remember me for 30 days
                </label>
            </div>

            <!-- Submit Button -->
            <button type="submit" class="login-button" id="loginButton">
                <div class="loading-spinner" id="loadingSpinner"></div>
                <span id="buttonText">
                    <i class="fas fa-sign-in-alt"></i>
                    Sign In
                </span>
            </button>
        </form>

        <!-- Login Footer -->
        <div class="login-footer">
            <p>
                <a href="../index">
                    <i class="fas fa-arrow-left"></i>
                    Back to Main Site
                </a>
            </p>
            <p style="margin-top: 0.5rem; font-size: 0.8rem; color: #6b7280;">
                Protected by advanced security measures
            </p>
        </div>
    </div>

    <!-- JavaScript -->
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const loginForm = document.getElementById('loginForm');
            const loginButton = document.getElementById('loginButton');
            const loadingSpinner = document.getElementById('loadingSpinner');
            const buttonText = document.getElementById('buttonText');
            const togglePassword = document.getElementById('togglePassword');
            const passwordInput = document.getElementById('password');
            
            // Password visibility toggle
            togglePassword.addEventListener('click', function() {
                const type = passwordInput.getAttribute('type') === 'password' ? 'text' : 'password';
                passwordInput.setAttribute('type', type);
                
                const icon = this.querySelector('i');
                if (type === 'password') {
                    icon.classList.remove('fa-eye-slash');
                    icon.classList.add('fa-eye');
                } else {
                    icon.classList.remove('fa-eye');
                    icon.classList.add('fa-eye-slash');
                }
            });
            
            // Form submission with loading state
            loginForm.addEventListener('submit', function(e) {
                loginButton.disabled = true;
                loadingSpinner.style.display = 'inline-block';
                buttonText.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Signing In...';
                
                // Add a small delay to show the loading state
                setTimeout(() => {
                    // Form will submit normally
                }, 100);
            });
            
            // Input field animations
            const inputs = document.querySelectorAll('.form-input');
            inputs.forEach(input => {
                input.addEventListener('focus', function() {
                    this.parentElement.classList.add('focused');
                });
                
                input.addEventListener('blur', function() {
                    this.parentElement.classList.remove('focused');
                });
                
                // Real-time validation feedback
                input.addEventListener('input', function() {
                    if (this.value.length > 0) {
                        this.classList.add('has-value');
                    } else {
                        this.classList.remove('has-value');
                    }
                });
            });
            
            // Keyboard shortcuts
            document.addEventListener('keydown', function(e) {
                // Enter key anywhere on page submits form
                if (e.key === 'Enter' && !e.shiftKey && !e.ctrlKey) {
                    const activeElement = document.activeElement;
                    if (activeElement.tagName !== 'BUTTON') {
                        e.preventDefault();
                        loginForm.submit();
                    }
                }
            });
            
            // Auto-hide alerts after 5 seconds
            const alerts = document.querySelectorAll('.alert');
            alerts.forEach(alert => {
                setTimeout(() => {
                    alert.style.opacity = '0';
                    alert.style.transform = 'translateY(-10px)';
                    setTimeout(() => {
                        alert.remove();
                    }, 300);
                }, 5000);
            });
            
            // Add ripple effect to button
            loginButton.addEventListener('click', function(e) {
                const ripple = document.createElement('span');
                const rect = this.getBoundingClientRect();
                const size = Math.max(rect.width, rect.height);
                const x = e.clientX - rect.left - size / 2;
                const y = e.clientY - rect.top - size / 2;
                
                ripple.style.width = ripple.style.height = size + 'px';
                ripple.style.left = x + 'px';
                ripple.style.top = y + 'px';
                ripple.classList.add('ripple');
                
                this.appendChild(ripple);
                
                setTimeout(() => {
                    ripple.remove();
                }, 600);
            });
            
            console.log('Admin login page initialized successfully!');
        });
    </script>


</body>
</html>

