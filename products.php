<?php
// Products page - Simple version without maintenance mode complexity
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Products - TechCompare</title>
    <meta name="description" content="Browse and compare smart watches, wireless headphones, and wired headphones from top brands.">
    <meta name="keywords" content="products, smart watch, headphones, Apple, Samsung, Sony, Bose">
    
    <!-- CSS -->
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="css/products.css">
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
                        <a href="products" class="nav-link active">Products <i class="fas fa-chevron-down"></i></a>
                        <div class="dropdown-content">
                            <a href="products.html?category=smart-watches">Smart Watches</a>
                            <a href="products.html?category=wireless-headphones">Wireless Headphones</a>
                            <a href="products.html?category=wired-headphones">Wired Headphones</a>
                        </div>
                    </div>
                    <a href="compare" class="nav-link">Compare</a>
                    <a href="about" class="nav-link">About</a>
                    <a href="contact" class="nav-link">Contact</a>
                </div>
                
                <div class="nav-actions">
                    <a href="user_login" class="btn btn-outline btn-sm">
                        <i class="fas fa-user"></i> Login
                    </a>
                    <a href="user_login" class="btn btn-primary btn-sm">
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
        <!-- Page Header with Search -->
        <section class="page-header-new">
            <div class="container">
                <div class="header-content">
                    <div class="header-left">
                        <nav class="breadcrumb">
                            <a href="index">Home</a>
                            <span class="separator">/</span>
                            <span class="current">Products</span>
                        </nav>
                        <h1 class="page-title">All Products</h1>
                        <p class="page-description">Discover & compare the latest tech products</p>
                    </div>
                    
                    <div class="header-search">
                        <div class="search-wrapper">
                            <input type="text" 
                                   id="product-search-input" 
                                   placeholder="Search products..." 
                                   class="product-search-input"
                                   autocomplete="off">
                            <button class="search-btn" id="search-btn">
                                <i class="fas fa-search"></i>
                            </button>
                            <div id="search-suggestions" class="search-suggestions"></div>
                        </div>
                        <div id="related-products" class="related-products-section" style="display: none;">
                            <h3>Related Products</h3>
                            <div id="related-products-grid" class="related-products-grid"></div>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- Filters Section -->
        <section class="filters-section">
            <div class="container">
                <div class="filters-bar">
                    <div class="filter-group">
                        <label for="category-filter">Category:</label>
                        <select id="category-filter" class="filter-select">
                            <option value="">All Categories</option>
                            <option value="smart-watches">Smart Watches</option>
                            <option value="wireless-headphones">Wireless Headphones</option>
                            <option value="wired-headphones">Wired Headphones</option>
                        </select>
                    </div>
                    
                    <div class="filter-group">
                        <label for="brand-filter">Brand:</label>
                        <select id="brand-filter" class="filter-select">
                            <option value="">All Brands</option>
                        </select>
                    </div>
                    
                    <div class="filter-group">
                        <label for="sort-filter">Sort by:</label>
                        <select id="sort-filter" class="filter-select">
                            <option value="featured">Featured</option>
                            <option value="price-low">Price: Low to High</option>
                            <option value="price-high">Price: High to Low</option>
                            <option value="rating">Highest Rated</option>
                            <option value="newest">Newest First</option>
                        </select>
                    </div>
                    
                    <div class="filter-group">
                        <label for="price-range">Price:</label>
                        <div class="price-range-wrapper">
                            <input type="range" id="price-range" min="0" max="100000" value="100000" class="price-slider">
                            <span class="price-display">₹<span id="max-price">100000</span></span>
                        </div>
                    </div>
                    
                    <button id="clear-filters" class="btn btn-outline btn-clear">
                        <i class="fas fa-times"></i> Clear
                    </button>
                </div>
            </div>
        </section>

        <!-- Products Grid -->
        <section class="products-section">
            <div class="container">
                <div class="products-header">
                    <div class="results-info">
                        <span id="results-count">Loading products...</span>
                    </div>
                    <div class="view-options">
                        <button class="view-btn active" data-view="grid"><i class="fas fa-th"></i></button>
                        <button class="view-btn" data-view="list"><i class="fas fa-list"></i></button>
                    </div>
                </div>
                
                <div id="products-grid" class="products-grid">
                    <!-- Products will be loaded here -->
                    <div class="loading">
                        <i class="fas fa-spinner fa-spin"></i>
                        Loading products...
                    </div>
                </div>
                
                <!-- Pagination -->
                <div id="pagination" class="pagination">
                    <!-- Pagination will be generated here -->
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

    <!-- Quick View Modal -->
    <div id="quick-view-modal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3>Product Details</h3>
                <span class="modal-close">&times;</span>
            </div>
            <div class="modal-body" id="quick-view-content">
                <!-- Quick view content will be loaded here -->
            </div>
        </div>
    </div>

    <!-- JavaScript -->
    <script src="js/theme.js"></script>
    <script src="js/connection-error-handler.js"></script>
    <script src="js/main.js"></script>
    <script src="js/products.js"></script>
    <script src="js/products-search.js"></script>
</body>
</html>
