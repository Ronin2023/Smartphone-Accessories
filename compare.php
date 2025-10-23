<?php
// Compare page - Simple version without maintenance mode complexity
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Compare Products - TechCompare</title>
    <meta name="description" content="Compare up to 4 tech products side by side with detailed specifications and pricing.">
    <meta name="keywords" content="product comparison, smart watch comparison, headphones comparison, tech specs">
    
    <!-- CSS -->
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="css/compare.css">
    <link rel="stylesheet" href="css/responsive.css">
    <link rel="stylesheet" href="css/theme.css">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
</head>
<body>
    <!-- Header -->
    <header class="header">
        <nav class="navbar">
            <div class="nav-container">
                <a href="index" class="nav-logo">
                    <i class="fas fa-balance-scale-right"></i>
                    TechCompare
                </a>
                
                <div class="nav-menu">
                    <a href="index" class="nav-link">Home</a>
                    <div class="nav-dropdown">
                        <a href="products" class="nav-link">Products <i class="fas fa-chevron-down"></i></a>
                        <div class="dropdown-content">
                            <a href="products.html?category=smart-watches">Smart Watches</a>
                            <a href="products.html?category=wireless-headphones">Wireless Headphones</a>
                            <a href="products.html?category=wired-headphones">Wired Headphones</a>
                        </div>
                    </div>
                    <a href="compare" class="nav-link active">Compare</a>
                    <a href="about" class="nav-link">About</a>
                    <a href="contact" class="nav-link">Contact</a>
                </div>
                
                <div class="nav-actions">
                    <a href="user_login" class="btn btn-outline">
                        <i class="fas fa-user"></i> Login
                    </a>
                    <a href="user_login" class="btn btn-primary">
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

    <!-- Main Content -->
    <main class="main-content">
        <!-- Page Header -->
        <section class="page-header">
            <div class="container">
                <h1 class="page-title">Product Comparison</h1>
                <p class="page-description">Compare up to 4 products side by side</p>
            </div>
        </section>

        <!-- Comparison Interface -->
        <section class="comparison-section">
            <div class="container">
                <!-- Product Selector -->
                <div class="product-selector" id="product-selector">
                    <div class="selector-header">
                        <h2>Select Products to Compare</h2>
                        <p>Choose up to 4 products from the same category for the best comparison experience</p>
                    </div>
                    
                    <div class="category-tabs">
                        <button class="tab-btn active" data-category="all">All Products</button>
                        <button class="tab-btn" data-category="smart-watches">Smart Watches</button>
                        <button class="tab-btn" data-category="wireless-headphones">Wireless Headphones</button>
                        <button class="tab-btn" data-category="wired-headphones">Wired Headphones</button>
                    </div>
                    
                    <div class="search-bar">
                        <input type="text" id="product-search" placeholder="Search products to compare..." class="search-input">
                        <i class="fas fa-search search-icon"></i>
                    </div>
                    
                    <div id="product-grid" class="product-grid">
                        <!-- Products will be loaded here -->
                    </div>
                </div>

                <!-- Comparison Table -->
                <div class="comparison-container" id="comparison-container">
                    <div class="comparison-header">
                        <h2>Product Comparison</h2>
                        <div class="comparison-actions">
                            <button id="clear-comparison" class="btn btn-outline">Clear All</button>
                            <button id="share-comparison" class="btn btn-secondary">Share Comparison</button>
                            <button id="export-comparison" class="btn btn-primary">Export PDF</button>
                        </div>
                    </div>
                    
                    <div class="comparison-table-container">
                        <table class="comparison-table" id="comparison-table">
                            <thead>
                                <tr id="product-headers">
                                    <th class="spec-column">Specifications</th>
                                    <!-- Product columns will be added here -->
                                </tr>
                            </thead>
                            <tbody id="comparison-body">
                                <!-- Comparison rows will be generated here -->
                            </tbody>
                        </table>
                    </div>
                    
                    <!-- Mobile Comparison Cards -->
                    <div class="mobile-comparison" id="mobile-comparison">
                        <!-- Mobile comparison cards will be generated here -->
                    </div>
                </div>

                <!-- Empty State -->
                <div class="empty-comparison" id="empty-comparison">
                    <div class="empty-content">
                        <i class="fas fa-balance-scale"></i>
                        <h3>Start Comparing Products</h3>
                        <p>Select products from above to begin your comparison. You can compare up to 4 products at once.</p>
                        <div class="empty-actions">
                            <a href="products" class="btn btn-primary">Browse Products</a>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- Comparison Features -->
        <section class="features-section">
            <div class="container">
                <h2 class="section-title">Why Use Our Comparison Tool?</h2>
                <div class="features-grid">
                    <div class="feature-card">
                        <div class="feature-icon">
                            <i class="fas fa-chart-line"></i>
                        </div>
                        <h3>Side-by-Side Analysis</h3>
                        <p>Compare specifications, features, and prices in an easy-to-read format.</p>
                    </div>
                    
                    <div class="feature-card">
                        <div class="feature-icon">
                            <i class="fas fa-filter"></i>
                        </div>
                        <h3>Smart Filtering</h3>
                        <p>Highlight differences and similarities to make informed decisions quickly.</p>
                    </div>
                    
                    <div class="feature-card">
                        <div class="feature-icon">
                            <i class="fas fa-share-alt"></i>
                        </div>
                        <h3>Share & Export</h3>
                        <p>Share your comparisons with others or export as PDF for later reference.</p>
                    </div>
                    
                    <div class="feature-card">
                        <div class="feature-icon">
                            <i class="fas fa-mobile-alt"></i>
                        </div>
                        <h3>Mobile Optimized</h3>
                        <p>Perfect comparison experience on any device, desktop or mobile.</p>
                    </div>
                </div>
            </div>
        </section>
    </main>

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
                        <li><a href="index">Home</a></li>
                        <li><a href="products">Products</a></li>
                        <li><a href="compare">Compare</a></li>
                        <li><a href="about">About Us</a></li>
                    </ul>
                </div>
                
                <div class="footer-section">
                    <h4>Categories</h4>
                    <ul>
                        <li><a href="products.html?category=smart-watches">Smart Watches</a></li>
                        <li><a href="products.html?category=wireless-headphones">Wireless Headphones</a></li>
                        <li><a href="products.html?category=wired-headphones">Wired Headphones</a></li>
                    </ul>
                </div>
                
                <div class="footer-section">
                    <h4>Contact</h4>
                    <ul>
                        <li><i class="fas fa-envelope"></i> info@techcompare.com</li>
                        <li><i class="fas fa-phone"></i> +1 (555) 123-4567</li>
                    </ul>
                </div>
            </div>
            
            <div class="footer-bottom">
                <p>&copy; 2024 TechCompare. All rights reserved.</p>
            </div>
        </div>
    </footer>

    <!-- Share Modal -->
    <div id="share-modal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3>Share Comparison</h3>
                <span class="modal-close">&times;</span>
            </div>
            <div class="modal-body">
                <div class="share-options">
                    <div class="share-option">
                        <label>Share Link:</label>
                        <div class="share-link-container">
                            <input type="text" id="share-link" readonly class="share-input">
                            <button class="btn btn-primary" onclick="copyShareLink()">Copy</button>
                        </div>
                    </div>
                    
                    <div class="share-social">
                        <h4>Share on Social Media</h4>
                        <div class="social-buttons">
                            <button class="social-btn facebook" onclick="shareOnFacebook()">
                                <i class="fab fa-facebook"></i> Facebook
                            </button>
                            <button class="social-btn twitter" onclick="shareOnTwitter()">
                                <i class="fab fa-twitter"></i> Twitter
                            </button>
                            <button class="social-btn linkedin" onclick="shareOnLinkedIn()">
                                <i class="fab fa-linkedin"></i> LinkedIn
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Share Modal -->
    <div id="shareModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3>Share Comparison</h3>
                <button class="modal-close">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <div class="modal-body">
                <div class="share-options">
                    <div>
                        <h4>Copy Link</h4>
                        <div class="share-link-container">
                            <input type="text" id="shareInput" class="share-input" readonly>
                            <button id="copyLink" class="btn btn-primary">
                                <i class="fas fa-copy"></i> Copy
                            </button>
                        </div>
                    </div>
                    
                    <div>
                        <h4>Share on Social Media</h4>
                        <div class="social-buttons">
                            <button class="social-btn facebook">
                                <i class="fab fa-facebook-f"></i> Facebook
                            </button>
                            <button class="social-btn twitter">
                                <i class="fab fa-twitter"></i> Twitter
                            </button>
                            <button class="social-btn linkedin">
                                <i class="fab fa-linkedin-in"></i> LinkedIn
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- JavaScript -->
    <script src="js/theme.js"></script>
    <script src="js/connection-error-handler.js"></script>
    <script src="js/main.js"></script>
    <script src="js/compare.js"></script>
</body>
</html>
