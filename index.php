<?php
// Main index page - Simple version without maintenance mode complexity
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>TechCompare - Compare Smart Watches & Headphones</title>
    <meta name="description" content="Compare specifications, prices, and features of smart watches, wireless headphones, and wired headphones across top brands.">
    <meta name="keywords" content="smart watch comparison, headphones comparison, tech products, Apple Watch, Samsung Galaxy Watch, Sony headphones, Bose">
    
    <!-- SEO Meta Tags -->
    <meta property="og:title" content="TechCompare - Smart Tech Product Comparisons">
    <meta property="og:description" content="Compare the latest smart watches and headphones with detailed specifications and prices.">
    <meta property="og:type" content="website">
    <meta property="og:url" content="https://techcompare.example.com">
    
    <!-- CSS -->
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="css/responsive.css">
    <link rel="stylesheet" href="css/theme.css">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    
    <!-- Preload critical resources -->
    <link rel="preload" href="css/style.css" as="style">
    <link rel="preload" href="css/theme.css" as="style">
    <link rel="preload" href="js/main.js" as="script">
    <link rel="preload" href="js/theme.js" as="script">
</head>
<body>
    <!-- Header -->
    <header class="header">
        <nav class="navbar">
            <div class="nav-container">
                <a href="index.php" class="nav-logo">
                    <i class="fas fa-balance-scale-right"></i>
                    TechCompare
                </a>
                
                <div class="nav-menu">
                    <a href="index.php" class="nav-link active">Home</a>
                    <div class="nav-dropdown">
                        <a href="products.php" class="nav-link">Products <i class="fas fa-chevron-down"></i></a>
                        <div class="dropdown-content">
                            <a href="products.php?category=smart-watches">Smart Watches</a>
                            <a href="products.php?category=wireless-headphones">Wireless Headphones</a>
                            <a href="products.php?category=wired-headphones">Wired Headphones</a>
                        </div>
                    </div>
                    <a href="compare.php" class="nav-link">Compare</a>
                    <a href="about.php" class="nav-link">About</a>
                    <a href="contact.php" class="nav-link">Contact</a>
                </div>
                
                <div class="nav-actions">
                    <a href="user_login.php" class="btn btn-outline">
                        <i class="fas fa-user"></i> Login
                    </a>
                    <a href="user_login.php" class="btn btn-primary">
                        <i class="fas fa-user-plus"></i> Sign Up
                    </a>
                </div>
                
                <div class="nav-toggle">
                    <span class="bar"></span>
                    <span class="bar"></span>
                    <span class="bar"></span>
                </div>
            </div>
        </nav>
    </header>

    <!-- Hero Section -->
    <section class="hero">
        <div class="hero-container">
            <div class="hero-content">
                <h1 class="hero-title">Compare Tech Products Smartly</h1>
                <p class="hero-description">
                    Make informed decisions with our comprehensive comparison tool for smart watches, 
                    wireless headphones, and wired headphones across all major brands.
                </p>
                <div class="hero-buttons">
                    <a href="compare.php" class="btn btn-primary">Start Comparing</a>
                    <a href="products.php" class="btn btn-secondary">Browse Products</a>
                </div>
            </div>
            <div class="hero-visual">
                <div class="product-showcase">
                    <div class="showcase-item watch">
                        <img src="assets/images/products/watch-showcase.png" alt="Smart Watch" loading="lazy" onerror="this.src='https://via.placeholder.com/300x300/4361ee/ffffff?text=Smart+Watch'">
                    </div>
                    <div class="showcase-item headphones">
                        <img src="assets/images/products/headphones-showcase.png" alt="Wireless Headphones" loading="lazy" onerror="this.src='https://via.placeholder.com/300x300/7209b7/ffffff?text=Headphones'">
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Categories Section -->
    <section class="categories">
        <div class="container">
            <h2 class="section-title">Browse by Category</h2>
            <div class="categories-grid">
                <div class="category-card">
                    <div class="category-icon">
                        <i class="fas fa-clock"></i>
                    </div>
                    <h3>Smart Watches</h3>
                    <p>Compare features, battery life, and compatibility of the latest smart watches.</p>
                    <a href="products.php?category=smart-watches" class="category-link">Explore <i class="fas fa-arrow-right"></i></a>
                </div>
                
                <div class="category-card">
                    <div class="category-icon">
                        <i class="fas fa-headphones"></i>
                    </div>
                    <h3>Wireless Headphones</h3>
                    <p>Compare sound quality, battery life, and connectivity options.</p>
                    <a href="products.php?category=wireless-headphones" class="category-link">Explore <i class="fas fa-arrow-right"></i></a>
                </div>
                
                <div class="category-card">
                    <div class="category-icon">
                        <i class="fas fa-headphones-alt"></i>
                    </div>
                    <h3>Wired Headphones</h3>
                    <p>Compare audio quality, comfort, and durability of wired options.</p>
                    <a href="products.php?category=wired-headphones" class="category-link">Explore <i class="fas fa-arrow-right"></i></a>
                </div>
            </div>
        </div>
    </section>

    <!-- Featured Products -->
    <section class="featured-products">
        <div class="container">
            <h2 class="section-title">Featured Products</h2>
            <div class="products-grid" id="featured-products">
                <!-- Products loaded via JavaScript -->
            </div>
        </div>
    </section>

    <!-- Comparison Tool Preview -->
    <section class="comparison-preview">
        <div class="container">
            <div class="preview-content">
                <h2>Smart Comparison Tool</h2>
                <p>Our advanced comparison tool lets you side-by-side compare up to 4 products with detailed specifications.</p>
                <ul class="feature-list">
                    <li><i class="fas fa-check"></i> Detailed specification comparison</li>
                    <li><i class="fas fa-check"></i> Price tracking</li>
                    <li><i class="fas fa-check"></i> User reviews integration</li>
                    <li><i class="fas fa-check"></i> Expert ratings</li>
                </ul>
                <a href="compare.php" class="btn btn-primary">Try Comparison Tool</a>
            </div>
            <div class="preview-visual">
                <img src="assets/images/comparison-preview.svg" alt="Comparison Tool Preview" loading="lazy" onerror="this.src='https://via.placeholder.com/500x300/667eea/ffffff?text=Comparison+Tool'">
            </div>
        </div>
    </section>

    <!-- Footer -->
    <footer class="footer">
        <div class="container">
            <div class="footer-content">
                <div class="footer-section">
                    <h3>TechCompare</h3>
                    <p>Your trusted source for tech product comparisons and reviews.</p>
                    <div class="social-links">
                        <a href="#"><i class="fab fa-facebook"></i></a>
                        <a href="#"><i class="fab fa-twitter"></i></a>
                        <a href="#"><i class="fab fa-instagram"></i></a>
                    </div>
                </div>
                
                <div class="footer-section">
                    <h4>Quick Links</h4>
                    <ul>
                        <li><a href="index.php">Home</a></li>
                        <li><a href="products.php">Products</a></li>
                        <li><a href="compare.php">Compare</a></li>
                        <li><a href="about.php">About Us</a></li>
                    </ul>
                </div>
                
                <div class="footer-section">
                    <h4>Categories</h4>
                    <ul>
                        <li><a href="products.php?category=smart-watches">Smart Watches</a></li>
                        <li><a href="products.php?category=wireless-headphones">Wireless Headphones</a></li>
                        <li><a href="products.php?category=wired-headphones">Wired Headphones</a></li>
                    </ul>
                </div>
                
                <div class="footer-section">
                    <h4>Contact</h4>
                    <ul>
                        <li><i class="fas fa-envelope"></i> info@techcompare.com</li>
                        <li><i class="fas fa-phone"></i> +1 (555) 123-4567</li>
                        <li><i class="fas fa-paper-plane"></i> <a href="contact.php" style="color: inherit;">Contact Form</a></li>
                        <li><i class="fas fa-search"></i> <a href="check-response.php" style="color: inherit;">Check Response</a></li>
                    </ul>
                </div>
            </div>
            
            <div class="footer-bottom">
                <p>&copy; 2024 TechCompare. All rights reserved.</p>
            </div>
        </div>
    </footer>

    <!-- JavaScript -->
    <script src="js/theme.js"></script>
    <script src="js/connection-error-handler.js"></script>
    <script src="js/main.js"></script>
    
    <!-- Special Access Overlay -->
    <div id="special-access-overlay" class="special-access-overlay" style="display: none;">
        <div class="special-access-popup">
            <div class="popup-header">
                <i class="fas fa-key">🔑</i>
                <h3>Special Access - Token Verification</h3>
                <p>Verifying your access token... Please wait.</p>
            </div>
            
            <form id="special-access-form" class="special-access-form">
                <div class="input-group">
                    <label for="access-token">Access Token (Auto-Detected)</label>
                    <input type="text" id="access-token" name="token" placeholder="Loading access token..." required>
                    <div class="input-hint">64-character hexadecimal access token</div>
                </div>
                
                <div class="form-actions">
                    <button type="submit" class="btn-submit">
                        <i class="fas fa-unlock">🔓</i>
                        Verify Token & Continue
                    </button>
                    <div class="auto-submit-note">
                        Auto-submitting in 1 second...
                    </div>
                </div>
                
                <div id="token-error" class="error-message" style="display: none;"></div>
            </form>
            
            <div class="popup-footer">
                <p><strong>Step 1:</strong> Token verification<br>
                <strong>Next:</strong> You'll enter your 4-part passkey</p>
            </div>
        </div>
    </div>
    
    <!-- Special Access Scripts -->
    <script>
        // Special Access Token Detection and Overlay System
        (function() {
            'use strict';
            
            // Check if we have a special access token in URL
            const urlParams = new URLSearchParams(window.location.search);
            const specialToken = urlParams.get('special_access_token') || urlParams.get('special_access');
            
            console.log('🔍 Special Access Detection:', {
                url: window.location.href,
                search: window.location.search,
                token: specialToken ? specialToken.substring(0, 20) + '...' : 'NOT FOUND',
                tokenLength: specialToken ? specialToken.length : 'N/A'
            });
            
            if (specialToken) {
                console.log('✅ Token detected, showing overlay');
                // Show the overlay immediately
                showSpecialAccessOverlay();
                
                // Pre-fill the token field
                const tokenInput = document.getElementById('access-token');
                if (tokenInput) {
                    tokenInput.value = specialToken;
                    tokenInput.readOnly = true; // Make it read-only since it's auto-filled
                    console.log('✅ Token pre-filled in input field');
                    
                    // Auto-submit after a short delay to show the user what's happening
                    setTimeout(() => {
                        const submitBtn = document.querySelector('.btn-submit');
                        if (submitBtn) {
                            console.log('🚀 Auto-submitting token verification...');
                            submitBtn.click();
                        }
                    }, 1000);
                }
            } else {
                console.log('ℹ️  No special access token in URL - normal page load');
            }
            
            function showSpecialAccessOverlay() {
                const overlay = document.getElementById('special-access-overlay');
                if (overlay) {
                    overlay.style.display = 'flex';
                    document.body.style.overflow = 'hidden';
                    
                    // Add blur effect to main content
                    const mainContent = document.body;
                    mainContent.classList.add('blurred-background');
                    
                    // Focus on token input
                    setTimeout(() => {
                        const tokenInput = document.getElementById('access-token');
                        if (tokenInput) {
                            tokenInput.focus();
                        }
                    }, 100);
                }
            }
            
            function hideSpecialAccessOverlay() {
                const overlay = document.getElementById('special-access-overlay');
                if (overlay) {
                    overlay.style.display = 'none';
                    document.body.style.overflow = '';
                    
                    // Remove blur effect
                    const mainContent = document.body;
                    mainContent.classList.remove('blurred-background');
                }
            }
            
            // Handle form submission
            const form = document.getElementById('special-access-form');
            if (form) {
                form.addEventListener('submit', function(e) {
                    e.preventDefault();
                    
                    const tokenInput = document.getElementById('access-token');
                    const errorDiv = document.getElementById('token-error');
                    const submitBtn = form.querySelector('.btn-submit');
                    
                    if (!tokenInput || !errorDiv || !submitBtn) return;
                    
                    const token = tokenInput.value.trim();
                    
                    console.log('🔍 Token validation:', {
                        tokenLength: token.length,
                        tokenStart: token.substring(0, 20) + '...',
                        isHex: /^[a-fA-F0-9]+$/.test(token),
                        isCorrectLength: token.length === 64
                    });
                    
                    // Validate token format (64 hex characters) - FIXED: Allow both cases
                    if (!/^[a-fA-F0-9]{64}$/.test(token)) {
                        console.log('❌ Token validation failed');
                        showError('Invalid token format. Token must be 64 hexadecimal characters.');
                        return;
                    }
                    
                    console.log('✅ Token validation passed');
                    
                    // Show loading state
                    submitBtn.disabled = true;
                    submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Verifying...';
                    errorDiv.style.display = 'none';
                    
                    // Validate token in database first
                    fetch('verify-special-access.php?action=validate_token&token=' + encodeURIComponent(token), {
                        method: 'GET'
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.valid) {
                            // Token is valid, redirect to passkey verification
                            console.log('✅ Token valid, redirecting to passkey verification');
                            window.location.href = 'verify-special-access.php?token=' + encodeURIComponent(token);
                        } else {
                            showError(data.error || 'Invalid or expired token. Please check your token and try again.');
                        }
                    })
                    .catch(error => {
                        console.error('Verification error:', error);
                        showError('Network error. Please check your connection and try again.');
                    })
                    .finally(() => {
                        // Reset button state
                        submitBtn.disabled = false;
                        submitBtn.innerHTML = '<i class="fas fa-unlock"></i> Verify Token';
                    });
                    
                    function showError(message) {
                        errorDiv.textContent = message;
                        errorDiv.style.display = 'block';
                        
                        // Shake animation
                        form.classList.add('shake');
                        setTimeout(() => form.classList.remove('shake'), 500);
                    }
                });
            }
            
            // Prevent overlay from being closed by clicking outside or ESC
            const overlay = document.getElementById('special-access-overlay');
            if (overlay) {
                overlay.addEventListener('click', function(e) {
                    // Only close if clicking the overlay background itself
                    if (e.target === overlay) {
                        e.preventDefault();
                        e.stopPropagation();
                        // Don't allow closing - this is intentional for security
                    }
                });
                
                // Prevent ESC key from closing
                document.addEventListener('keydown', function(e) {
                    if (e.key === 'Escape' && overlay.style.display === 'flex') {
                        e.preventDefault();
                        e.stopPropagation();
                        // Don't allow closing - this is intentional for security
                    }
                });
            }
        })();
    </script>
    
    <style>
        /* Special Access Overlay Styles */
        .special-access-overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.8);
            backdrop-filter: blur(10px);
            z-index: 10000;
            display: flex;
            align-items: center;
            justify-content: center;
            animation: fadeIn 0.3s ease-out;
        }
        
        .blurred-background > *:not(#special-access-overlay) {
            filter: blur(5px);
            transition: filter 0.3s ease;
        }
        
        .special-access-popup {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            padding: 2rem;
            border-radius: 15px;
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.3);
            max-width: 450px;
            width: 90%;
            text-align: center;
            color: white;
            animation: slideIn 0.3s ease-out;
            position: relative;
        }
        
        .popup-header {
            margin-bottom: 1.5rem;
        }
        
        .popup-header i {
            font-size: 3rem;
            margin-bottom: 1rem;
            color: #ffd700;
            text-shadow: 0 2px 4px rgba(0, 0, 0, 0.3);
        }
        
        .popup-header h3 {
            margin: 0 0 0.5rem 0;
            font-size: 1.5rem;
            font-weight: 600;
        }
        
        .popup-header p {
            margin: 0;
            opacity: 0.9;
            font-size: 0.95rem;
        }
        
        .special-access-form {
            margin-bottom: 1.5rem;
        }
        
        .input-group {
            margin-bottom: 1rem;
            text-align: left;
        }
        
        .input-group label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 500;
            font-size: 0.9rem;
        }
        
        .input-group input {
            width: 100%;
            padding: 0.75rem 1rem;
            border: 2px solid rgba(255, 255, 255, 0.3);
            border-radius: 8px;
            background: rgba(255, 255, 255, 0.1);
            color: white;
            font-size: 0.9rem;
            box-sizing: border-box;
            transition: all 0.3s ease;
        }
        
        .input-group input::placeholder {
            color: rgba(255, 255, 255, 0.7);
        }
        
        .input-group input:focus {
            outline: none;
            border-color: #ffd700;
            background: rgba(255, 255, 255, 0.15);
            box-shadow: 0 0 0 3px rgba(255, 215, 0, 0.2);
        }
        
        .input-hint {
            font-size: 0.8rem;
            margin-top: 0.25rem;
            opacity: 0.8;
        }
        
        .form-actions {
            margin-top: 1.5rem;
        }
        
        .auto-submit-note {
            font-size: 0.8rem;
            opacity: 0.8;
            margin-top: 0.5rem;
            color: #ffd700;
        }
        
        .btn-submit {
            background: linear-gradient(135deg, #ffd700 0%, #ffed4a 100%);
            color: #333;
            border: none;
            padding: 0.75rem 2rem;
            border-radius: 25px;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            box-shadow: 0 4px 15px rgba(255, 215, 0, 0.3);
        }
        
        .btn-submit:hover:not(:disabled) {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(255, 215, 0, 0.4);
        }
        
        .btn-submit:disabled {
            opacity: 0.7;
            cursor: not-allowed;
            transform: none;
        }
        
        .error-message {
            background: rgba(255, 0, 0, 0.2);
            border: 1px solid rgba(255, 0, 0, 0.5);
            color: #ffcccc;
            padding: 0.75rem;
            border-radius: 8px;
            margin-top: 1rem;
            font-size: 0.9rem;
        }
        
        .popup-footer {
            font-size: 0.85rem;
            opacity: 0.8;
            padding-top: 1rem;
            border-top: 1px solid rgba(255, 255, 255, 0.2);
        }
        
        .popup-footer p {
            margin: 0;
        }
        
        /* Animations */
        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }
        
        @keyframes slideIn {
            from { 
                opacity: 0;
                transform: scale(0.9) translateY(-20px);
            }
            to { 
                opacity: 1;
                transform: scale(1) translateY(0);
            }
        }
        
        .shake {
            animation: shake 0.5s ease-in-out;
        }
        
        @keyframes shake {
            0%, 100% { transform: translateX(0); }
            25% { transform: translateX(-5px); }
            75% { transform: translateX(5px); }
        }
        
        /* Mobile Responsiveness */
        @media (max-width: 768px) {
            .special-access-popup {
                padding: 1.5rem;
                margin: 1rem;
            }
            
            .popup-header h3 {
                font-size: 1.3rem;
            }
            
            .popup-header i {
                font-size: 2.5rem;
            }
        }
    </style>
</body>
</html>