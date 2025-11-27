<?php
session_start();
require_once 'includes/config.php';
require_once 'includes/functions.php';

// Redirect if already logged in as user
if (isLoggedIn() && isUser()) {
    redirect('user_dashboard');
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Login - TechCompare</title>
    <link rel="stylesheet" href="css/style.css">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        :root {
            --primary-color: #2c3e50;
            --secondary-color: #3498db;
            --success-color: #27ae60;
            --warning-color: #f39c12;
            --danger-color: #e74c3c;
            --light-bg: #ecf0f1;
            --dark-text: #2c3e50;
            --light-text: #7f8c8d;
            --white: #ffffff;
            --shadow: 0 2px 10px rgba(0,0,0,0.1);
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 1rem;
        }

        .login-container {
            background: var(--white);
            border-radius: 20px;
            box-shadow: 0 20px 40px rgba(0,0,0,0.1);
            overflow: hidden;
            width: 100%;
            max-width: 900px;
            display: grid;
            grid-template-columns: 1fr 1fr;
            min-height: 600px;
        }

        .login-welcome {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 3rem;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            text-align: center;
            position: relative;
            overflow: hidden;
        }

        .login-welcome::before {
            content: '';
            position: absolute;
            top: -50%;
            left: -50%;
            width: 200%;
            height: 200%;
            background: url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100"><circle cx="50" cy="50" r="2" fill="rgba(255,255,255,0.1)"/></svg>') repeat;
            animation: float 20s linear infinite;
        }

        @keyframes float {
            0% { transform: translate(-50%, -50%) rotate(0deg); }
            100% { transform: translate(-50%, -50%) rotate(360deg); }
        }

        .welcome-content {
            position: relative;
            z-index: 2;
        }

        .welcome-logo {
            font-size: 4rem;
            margin-bottom: 1rem;
            color: #ffffff;
            text-shadow: 0 2px 4px rgba(0,0,0,0.3);
        }

        .welcome-title {
            font-size: 2.5rem;
            font-weight: 700;
            margin-bottom: 1rem;
            text-shadow: 0 2px 4px rgba(0,0,0,0.3);
        }

        .welcome-subtitle {
            font-size: 1.2rem;
            opacity: 0.9;
            margin-bottom: 2rem;
            line-height: 1.6;
        }

        .welcome-features {
            list-style: none;
            text-align: left;
        }

        .welcome-features li {
            margin-bottom: 1rem;
            display: flex;
            align-items: center;
            font-size: 1.1rem;
        }

        .welcome-features i {
            margin-right: 1rem;
            font-size: 1.2rem;
            opacity: 0.8;
        }

        .login-form-section {
            padding: 3rem;
            display: flex;
            flex-direction: column;
            justify-content: center;
        }

        .form-header {
            text-align: center;
            margin-bottom: 2rem;
        }

        .form-header h2 {
            color: var(--dark-text);
            font-size: 2rem;
            font-weight: 600;
            margin-bottom: 0.5rem;
        }

        .form-header p {
            color: var(--light-text);
            font-size: 1rem;
        }

        .login-tabs {
            display: flex;
            margin-bottom: 2rem;
            background: var(--light-bg);
            border-radius: 10px;
            padding: 0.25rem;
        }

        .tab-button {
            flex: 1;
            padding: 0.75rem 1rem;
            border: none;
            background: transparent;
            border-radius: 8px;
            font-size: 1rem;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.3s ease;
            color: var(--light-text);
        }

        .tab-button.active {
            background: white;
            color: var(--dark-text);
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        }

        .login-form {
            display: none;
        }

        .login-form.active {
            display: block;
        }

        .form-group {
            margin-bottom: 1.5rem;
            position: relative;
        }

        .form-group label {
            display: block;
            margin-bottom: 0.5rem;
            color: var(--dark-text);
            font-weight: 500;
        }

        .form-group input {
            width: 100%;
            padding: 1rem 1rem 1rem 3rem;
            border: 2px solid #e0e6ed;
            border-radius: 10px;
            font-size: 1rem;
            transition: all 0.3s ease;
            background: #f8f9fa;
        }

        .form-group input:focus {
            outline: none;
            border-color: var(--secondary-color);
            background: white;
            box-shadow: 0 0 0 3px rgba(52, 152, 219, 0.1);
            transform: translateY(-1px);
        }

        .form-group input.error {
            border-color: var(--danger-color);
            background: #fef5f5;
            animation: shake 0.5s ease-in-out;
        }

        @keyframes shake {
            0%, 100% { transform: translateX(0); }
            25% { transform: translateX(-5px); }
            75% { transform: translateX(5px); }
        }

        .form-group input:valid {
            border-color: var(--success-color);
        }

        .form-group i {
            position: absolute;
            left: 1rem;
            top: 2.4rem;
            color: var(--light-text);
            font-size: 1.1rem;
        }

        .password-toggle {
            position: absolute;
            right: 1rem;
            top: 2.4rem;
            cursor: pointer;
            color: var(--light-text);
            font-size: 1.1rem;
            transition: color 0.3s ease;
        }

        .password-toggle:hover {
            color: var(--dark-text);
        }

        .form-options {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 2rem;
            flex-wrap: wrap;
            gap: 1rem;
        }

        .remember-me {
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .remember-me input[type="checkbox"] {
            width: auto;
            margin: 0;
        }

        .forgot-password {
            color: var(--secondary-color);
            text-decoration: none;
            font-weight: 500;
            transition: color 0.3s ease;
        }

        .forgot-password:hover {
            color: var(--primary-color);
        }

        .login-button {
            width: 100%;
            padding: 1rem;
            background: linear-gradient(135deg, var(--secondary-color) 0%, var(--primary-color) 100%);
            border: none;
            border-radius: 10px;
            color: white;
            font-size: 1.1rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
        }

        .login-button:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(52, 152, 219, 0.3);
        }

        .login-button:active {
            transform: translateY(0);
        }

        .login-button:disabled {
            opacity: 0.7;
            cursor: not-allowed;
            transform: none;
        }

        .login-button .spinner {
            display: none;
            width: 20px;
            height: 20px;
            border: 2px solid rgba(255,255,255,0.3);
            border-top: 2px solid white;
            border-radius: 50%;
            animation: spin 1s linear infinite;
        }

        .login-button.loading .spinner {
            display: block;
        }

        .login-button.loading .button-text {
            display: none;
        }

        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }

        .signup-link {
            text-align: center;
            margin-top: 2rem;
            padding-top: 2rem;
            border-top: 1px solid var(--light-bg);
        }

        .signup-link a {
            color: var(--secondary-color);
            text-decoration: none;
            font-weight: 500;
            transition: color 0.3s ease;
        }

        .signup-link a:hover {
            color: var(--primary-color);
        }

        .alert {
            padding: 1rem;
            border-radius: 10px;
            margin-bottom: 1.5rem;
            border: 1px solid;
            display: flex;
            align-items: center;
            animation: slideIn 0.3s ease-out;
        }

        @keyframes slideIn {
            from {
                opacity: 0;
                transform: translateY(-10px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .alert i {
            margin-right: 0.5rem;
            font-size: 1.1rem;
        }

        .alert-success {
            background: #d4edda;
            border-color: #c3e6cb;
            color: #155724;
        }

        .alert-error {
            background: #f8d7da;
            border-color: #f5c6cb;
            color: #721c24;
        }

        .alert-warning {
            background: #fff3cd;
            border-color: #ffeaa7;
            color: #856404;
        }

        .field-error {
            color: var(--danger-color);
            font-size: 0.8rem;
            margin-top: 0.25rem;
            display: none;
        }

        .field-error.show {
            display: block;
            animation: slideIn 0.3s ease-out;
        }

        @media (max-width: 768px) {
            .login-container {
                grid-template-columns: 1fr;
                max-width: 500px;
            }

            .login-welcome {
                padding: 2rem;
                min-height: 300px;
            }

            .welcome-title {
                font-size: 2rem;
            }

            .welcome-subtitle {
                font-size: 1rem;
            }

            .login-form-section {
                padding: 2rem;
            }

            .form-options {
                flex-direction: column;
                align-items: flex-start;
            }
        }

        @media (max-width: 480px) {
            body {
                padding: 0.5rem;
            }

            .login-welcome {
                padding: 1.5rem;
            }

            .login-form-section {
                padding: 1.5rem;
            }

            .welcome-logo {
                font-size: 3rem;
            }

            .welcome-title {
                font-size: 1.8rem;
            }
        }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="login-welcome">
            <div class="welcome-content">
                <div class="welcome-logo">
                    <i class="fas fa-balance-scale-right"></i>
                </div>
                <h1 class="welcome-title">TechCompare</h1>
                <p class="welcome-subtitle">Smart technology comparisons at your fingertips</p>
                <ul class="welcome-features">
                    <li><i class="fas fa-chart-line"></i> Compare latest tech products</li>
                    <li><i class="fas fa-star"></i> Read authentic reviews</li>
                    <li><i class="fas fa-bookmark"></i> Save your favorites</li>
                    <li><i class="fas fa-bell"></i> Get price alerts</li>
                </ul>
            </div>
        </div>

        <div class="login-form-section">
            <div class="form-header">
                <h2>Welcome Back!</h2>
                <p>Sign in to your account to continue</p>
            </div>

            <!-- Login/Register Tabs -->
            <div class="login-tabs">
                <button type="button" class="tab-button active" onclick="switchTab('login')">Login</button>
                <button type="button" class="tab-button" onclick="switchTab('register')">Register</button>
            </div>

            <!-- Messages -->
            <?php if (isset($_SESSION['message'])): ?>
                <div class="alert alert-success">
                    <i class="fas fa-check-circle"></i>
                    <?php echo htmlspecialchars($_SESSION['message']); unset($_SESSION['message']); ?>
                </div>
            <?php endif; ?>

            <?php if (isset($_SESSION['error'])): ?>
                <div class="alert alert-error">
                    <i class="fas fa-exclamation-triangle"></i>
                    <?php echo htmlspecialchars($_SESSION['error']); unset($_SESSION['error']); ?>
                </div>
            <?php endif; ?>

            <!-- Login Form -->
            <form class="login-form active" id="loginForm" method="POST" action="user_auth.php">
                <!-- CSRF Protection -->
                <?php if (session_status() === PHP_SESSION_NONE) session_start(); require_once 'includes/functions.php'; echo csrfField(); ?>
                
                <input type="hidden" name="action" value="login">
                
                <div class="form-group">
                    <label for="login_username">Username or Email</label>
                    <i class="fas fa-user"></i>
                    <input type="text" id="login_username" name="username" required placeholder="Enter username or email">
                    <div class="field-error" id="login_username_error"></div>
                </div>

                <div class="form-group">
                    <label for="login_password">Password</label>
                    <i class="fas fa-lock"></i>
                    <input type="password" id="login_password" name="password" required placeholder="Enter password">
                    <i class="fas fa-eye password-toggle" onclick="togglePassword('login_password', this)"></i>
                    <div class="field-error" id="login_password_error"></div>
                </div>

                <div class="form-options">
                    <label class="remember-me">
                        <input type="checkbox" name="remember_me" value="1">
                        <span>Remember me</span>
                    </label>
                    <a href="#" class="forgot-password" onclick="showForgotPassword()">Forgot Password?</a>
                </div>

                <button type="submit" class="login-button" id="loginButton">
                    <div class="spinner"></div>
                    <span class="button-text">
                        <i class="fas fa-sign-in-alt"></i> Sign In
                    </span>
                </button>
            </form>

            <!-- Register Form -->
            <form class="login-form" id="registerForm" method="POST" action="user_auth.php">
                <!-- CSRF Protection -->
                <?php echo csrfField(); ?>
                
                <input type="hidden" name="action" value="register">
                
                <div class="form-group">
                    <label for="reg_username">Username</label>
                    <i class="fas fa-user"></i>
                    <input type="text" id="reg_username" name="username" required placeholder="Choose a username" maxlength="50">
                    <div class="field-error" id="reg_username_error"></div>
                </div>

                <div class="form-group">
                    <label for="reg_email">Email Address</label>
                    <i class="fas fa-envelope"></i>
                    <input type="email" id="reg_email" name="email" required placeholder="Enter your email" maxlength="100">
                    <div class="field-error" id="reg_email_error"></div>
                </div>

                <div class="form-group">
                    <label for="reg_first_name">First Name</label>
                    <i class="fas fa-id-card"></i>
                    <input type="text" id="reg_first_name" name="first_name" placeholder="Enter first name" maxlength="50">
                    <div class="field-error" id="reg_first_name_error"></div>
                </div>

                <div class="form-group">
                    <label for="reg_last_name">Last Name</label>
                    <i class="fas fa-id-card"></i>
                    <input type="text" id="reg_last_name" name="last_name" placeholder="Enter last name" maxlength="50">
                    <div class="field-error" id="reg_last_name_error"></div>
                </div>

                <div class="form-group">
                    <label for="reg_password">Password</label>
                    <i class="fas fa-lock"></i>
                    <input type="password" id="reg_password" name="password" required placeholder="Create password" minlength="6">
                    <i class="fas fa-eye password-toggle" onclick="togglePassword('reg_password', this)"></i>
                    <div class="field-error" id="reg_password_error"></div>
                </div>

                <div class="form-group">
                    <label for="reg_confirm_password">Confirm Password</label>
                    <i class="fas fa-lock"></i>
                    <input type="password" id="reg_confirm_password" name="confirm_password" required placeholder="Confirm password" minlength="6">
                    <i class="fas fa-eye password-toggle" onclick="togglePassword('reg_confirm_password', this)"></i>
                    <div class="field-error" id="reg_confirm_password_error"></div>
                </div>

                <button type="submit" class="login-button" id="registerButton">
                    <div class="spinner"></div>
                    <span class="button-text">
                        <i class="fas fa-user-plus"></i> Create Account
                    </span>
                </button>
            </form>

            <div class="signup-link">
                <p>By signing up, you agree to our <a href="#">Terms of Service</a> and <a href="#">Privacy Policy</a>.</p> 
        </div>
    </div>

    <script>
        // Check URL parameters for tab switching
        const urlParams = new URLSearchParams(window.location.search);
        const tab = urlParams.get('tab');
        
        function switchTab(tabName) {
            // Remove active class from all tabs and forms
            document.querySelectorAll('.tab-button').forEach(btn => btn.classList.remove('active'));
            document.querySelectorAll('.login-form').forEach(form => form.classList.remove('active'));
            
            // Add active class to selected tab and form
            const targetTab = document.querySelector(`[onclick="switchTab('${tabName}')"]`);
            if (targetTab) targetTab.classList.add('active');
            document.getElementById(tabName + 'Form').classList.add('active');
            
            // Update form header
            const header = document.querySelector('.form-header h2');
            const subtitle = document.querySelector('.form-header p');
            
            if (tabName === 'login') {
                header.textContent = 'Welcome Back!';
                subtitle.textContent = 'Sign in to your account to continue';
            } else {
                header.textContent = 'Join TechCompare';
                subtitle.textContent = 'Create your account to get started';
            }
        }

        // Auto-switch to login tab if specified in URL
        if (tab === 'login') {
            switchTab('login');
        }

        function togglePassword(inputId, icon) {
            const input = document.getElementById(inputId);
            const isPassword = input.type === 'password';
            
            input.type = isPassword ? 'text' : 'password';
            icon.classList.toggle('fa-eye');
            icon.classList.toggle('fa-eye-slash');
        }

        function showForgotPassword() {
            alert('Please contact support at support@techcompare.com for password reset assistance.');
        }

        // Validation functions
        function showFieldError(fieldId, message) {
            const field = document.getElementById(fieldId);
            const errorElement = document.getElementById(fieldId + '_error');
            
            field.classList.add('error');
            errorElement.textContent = message;
            errorElement.classList.add('show');
        }

        function clearFieldError(fieldId) {
            const field = document.getElementById(fieldId);
            const errorElement = document.getElementById(fieldId + '_error');
            
            field.classList.remove('error');
            errorElement.classList.remove('show');
        }

        function clearAllErrors(formId) {
            const form = document.getElementById(formId);
            const fields = form.querySelectorAll('input');
            fields.forEach(field => {
                clearFieldError(field.id);
            });
        }

        function validateEmail(email) {
            const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            return emailRegex.test(email);
        }

        function validateUsername(username) {
            const usernameRegex = /^[a-zA-Z0-9_]+$/;
            return usernameRegex.test(username) && username.length >= 3;
        }

        // Real-time validation for login form
        document.getElementById('login_username').addEventListener('blur', function() {
            const value = this.value.trim();
            if (!value) {
                showFieldError('login_username', 'Username or email is required');
            } else {
                clearFieldError('login_username');
            }
        });

        document.getElementById('login_password').addEventListener('blur', function() {
            const value = this.value;
            if (!value) {
                showFieldError('login_password', 'Password is required');
            } else {
                clearFieldError('login_password');
            }
        });

        // Real-time validation for registration form
        document.getElementById('reg_username').addEventListener('blur', function() {
            const value = this.value.trim();
            if (!value) {
                showFieldError('reg_username', 'Username is required');
            } else if (value.length < 3) {
                showFieldError('reg_username', 'Username must be at least 3 characters');
            } else if (!validateUsername(value)) {
                showFieldError('reg_username', 'Username can only contain letters, numbers, and underscores');
            } else {
                clearFieldError('reg_username');
            }
        });

        document.getElementById('reg_email').addEventListener('blur', function() {
            const value = this.value.trim();
            if (!value) {
                showFieldError('reg_email', 'Email is required');
            } else if (!validateEmail(value)) {
                showFieldError('reg_email', 'Please enter a valid email address');
            } else {
                clearFieldError('reg_email');
            }
        });

        document.getElementById('reg_password').addEventListener('input', function() {
            const value = this.value;
            if (value.length > 0 && value.length < 6) {
                showFieldError('reg_password', 'Password must be at least 6 characters');
            } else {
                clearFieldError('reg_password');
                // Check confirm password if it has value
                const confirmPassword = document.getElementById('reg_confirm_password').value;
                if (confirmPassword && value !== confirmPassword) {
                    showFieldError('reg_confirm_password', 'Passwords do not match');
                } else if (confirmPassword) {
                    clearFieldError('reg_confirm_password');
                }
            }
        });

        document.getElementById('reg_confirm_password').addEventListener('input', function() {
            const value = this.value;
            const password = document.getElementById('reg_password').value;
            if (value && value !== password) {
                showFieldError('reg_confirm_password', 'Passwords do not match');
            } else {
                clearFieldError('reg_confirm_password');
            }
        });

        // Form submissions with enhanced validation
        document.getElementById('loginForm').addEventListener('submit', function(e) {
            clearAllErrors('loginForm');
            
            const username = document.getElementById('login_username').value.trim();
            const password = document.getElementById('login_password').value;
            const button = document.getElementById('loginButton');
            let hasErrors = false;

            if (!username) {
                showFieldError('login_username', 'Username or email is required');
                hasErrors = true;
            }

            if (!password) {
                showFieldError('login_password', 'Password is required');
                hasErrors = true;
            }

            if (hasErrors) {
                e.preventDefault();
            } else {
                // Show loading state
                button.classList.add('loading');
                button.disabled = true;
            }
        });

        document.getElementById('registerForm').addEventListener('submit', function(e) {
            clearAllErrors('registerForm');
            
            const username = document.getElementById('reg_username').value.trim();
            const email = document.getElementById('reg_email').value.trim();
            const password = document.getElementById('reg_password').value;
            const confirmPassword = document.getElementById('reg_confirm_password').value;
            const button = document.getElementById('registerButton');
            let hasErrors = false;

            if (!username) {
                showFieldError('reg_username', 'Username is required');
                hasErrors = true;
            } else if (username.length < 3) {
                showFieldError('reg_username', 'Username must be at least 3 characters');
                hasErrors = true;
            } else if (!validateUsername(username)) {
                showFieldError('reg_username', 'Username can only contain letters, numbers, and underscores');
                hasErrors = true;
            }

            if (!email) {
                showFieldError('reg_email', 'Email is required');
                hasErrors = true;
            } else if (!validateEmail(email)) {
                showFieldError('reg_email', 'Please enter a valid email address');
                hasErrors = true;
            }

            if (!password) {
                showFieldError('reg_password', 'Password is required');
                hasErrors = true;
            } else if (password.length < 6) {
                showFieldError('reg_password', 'Password must be at least 6 characters');
                hasErrors = true;
            }

            if (!confirmPassword) {
                showFieldError('reg_confirm_password', 'Please confirm your password');
                hasErrors = true;
            } else if (password !== confirmPassword) {
                showFieldError('reg_confirm_password', 'Passwords do not match');
                hasErrors = true;
            }

            if (hasErrors) {
                e.preventDefault();
            } else {
                // Show loading state
                button.classList.add('loading');
                button.disabled = true;
            }
        });

        // Auto-hide alerts after 5 seconds
        document.addEventListener('DOMContentLoaded', function() {
            const alerts = document.querySelectorAll('.alert');
            alerts.forEach(alert => {
                setTimeout(() => {
                    alert.style.opacity = '0';
                    alert.style.transition = 'opacity 0.5s';
                    setTimeout(() => alert.remove(), 500);
                }, 5000);
            });
        });
    </script>
</body>
</html>