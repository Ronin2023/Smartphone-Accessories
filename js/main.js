// main.js - Main JavaScript functionality

// Helper function to get absolute image URL
function getImageUrl(relativePath) {
    if (!relativePath) return '';
    if (relativePath.startsWith('http')) return relativePath; // Already absolute
    // Get current base URL dynamically (works with ngrok, localhost, production)
    const baseUrl = window.location.origin + window.location.pathname.replace(/\/[^\/]*$/, '');
    return baseUrl + '/' + relativePath;
}

document.addEventListener('DOMContentLoaded', function() {
    // Initialize app
    initializeApp();
});

function initializeApp() {
    // Mobile menu toggle
    setupMobileMenu();
    
    // Load featured products
    loadFeaturedProducts();
    
    // Smooth scrolling for anchor links
    setupSmoothScrolling();
    
    // Header scroll effect
    setupHeaderScroll();
    
    // Close mobile menu when clicking outside
    setupOutsideClickClose();
    
    // Initialize animations
    setupAnimations();
}

// Mobile menu functionality
function setupMobileMenu() {
    const navToggle = document.querySelector('.nav-toggle');
    const navMenu = document.querySelector('.nav-menu');
    
    if (navToggle && navMenu) {
        navToggle.addEventListener('click', function(e) {
            e.stopPropagation();
            navMenu.classList.toggle('active');
            navToggle.classList.toggle('active');
            
            // Prevent body scroll when menu is open
            document.body.style.overflow = navMenu.classList.contains('active') ? 'hidden' : '';
        });
        
        // Close menu when clicking on links
        navMenu.querySelectorAll('.nav-link').forEach(link => {
            link.addEventListener('click', () => {
                navMenu.classList.remove('active');
                navToggle.classList.remove('active');
                document.body.style.overflow = '';
            });
        });
    }
}

// Close mobile menu when clicking outside
function setupOutsideClickClose() {
    document.addEventListener('click', function(e) {
        const navMenu = document.querySelector('.nav-menu');
        const navToggle = document.querySelector('.nav-toggle');
        
        if (navMenu && navToggle) {
            if (!navMenu.contains(e.target) && !navToggle.contains(e.target)) {
                navMenu.classList.remove('active');
                navToggle.classList.remove('active');
                document.body.style.overflow = '';
            }
        }
    });
}

// Header scroll effect
function setupHeaderScroll() {
    const header = document.querySelector('.header');
    let lastScrollTop = 0;
    
    window.addEventListener('scroll', function() {
        const scrollTop = window.pageYOffset || document.documentElement.scrollTop;
        
        if (scrollTop > 100) {
            header.style.background = 'rgba(255, 255, 255, 0.98)';
        } else {
            header.style.background = 'rgba(255, 255, 255, 0.95)';
        }
        
        lastScrollTop = scrollTop <= 0 ? 0 : scrollTop;
    });
}

// Smooth scrolling for anchor links
function setupSmoothScrolling() {
    document.querySelectorAll('a[href^="#"]').forEach(anchor => {
        anchor.addEventListener('click', function (e) {
            e.preventDefault();
            const target = document.querySelector(this.getAttribute('href'));
            if (target) {
                const headerOffset = 70;
                const elementPosition = target.getBoundingClientRect().top;
                const offsetPosition = elementPosition + window.pageYOffset - headerOffset;
                
                window.scrollTo({
                    top: offsetPosition,
                    behavior: 'smooth'
                });
            }
        });
    });
}

// Animation setup
function setupAnimations() {
    // Intersection Observer for animations
    const observerOptions = {
        threshold: 0.1,
        rootMargin: '0px 0px -50px 0px'
    };
    
    const observer = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                entry.target.style.opacity = '1';
                entry.target.style.transform = 'translateY(0)';
            }
        });
    }, observerOptions);
    
    // Observe elements for animation
    document.querySelectorAll('.category-card, .product-card').forEach(card => {
        card.style.opacity = '0';
        card.style.transform = 'translateY(20px)';
        card.style.transition = 'opacity 0.6s ease, transform 0.6s ease';
        observer.observe(card);
    });
}

// Load featured products from API with fallback
async function loadFeaturedProducts() {
    const productsGrid = document.getElementById('featured-products');
    if (!productsGrid) return;
    
    // Show loading state
    productsGrid.innerHTML = '<div class="loading">Loading featured products...</div>';
    
    try {
        const response = await fetch('api/get_featured_products.php');
        
        if (!response.ok) {
            throw new Error('API not available');
        }
        
        const products = await response.json();
        
        if (products && products.length > 0) {
            displayProducts(products, productsGrid);
        } else {
            throw new Error('No products found');
        }
    } catch (error) {
        console.error('Error loading featured products:', error);
        
        // Check if this is a connection error
        if (window.connectionErrorHandler && window.connectionErrorHandler.shouldShowConnectionError(error)) {
            // Let the connection error handler deal with it
            return;
        }
        
        // Show fallback products for other errors
        displayFallbackProducts(productsGrid);
    }
}

// Display products
function displayProducts(products, container) {
    container.innerHTML = products.map(product => `
        <div class="product-card">
            <div class="product-image">
                <img src="${getImageUrl(product.main_image)}" 
                     alt="${product.name}" 
                     loading="lazy"
                     onerror="this.style.display='none';">
                ${product.discount_price ? `<span class="discount-badge">Sale</span>` : ''}
            </div>
            <div class="product-info">
                <h3>${escapeHtml(product.name)}</h3>
                <p class="product-brand">${escapeHtml(product.brand_name)}</p>
                <div class="product-price">
                    ${product.discount_price ? `
                        <span class="original-price">₹${product.price}</span>
                        <span class="current-price">₹${product.discount_price}</span>
                    ` : `<span class="current-price">₹${product.price}</span>`}
                </div>
                <a href="products?id=${product.id}" class="btn btn-outline">View Details</a>
            </div>
        </div>
    `).join('');
}

// Display fallback products when API is not available
function displayFallbackProducts(container) {
    const fallbackProducts = [
        {
            id: 1,
            name: 'Apple Watch Series 9',
            brand_name: 'Apple',
            price: 399,
            discount_price: null,
            main_image: 'assets/images/placeholder.svg',
            category: 'smart-watches'
        },
        {
            id: 2,
            name: 'AirPods Pro (2nd generation)',
            brand_name: 'Apple',
            price: 249,
            discount_price: 199,
            main_image: 'assets/images/placeholder.svg',
            category: 'wireless-headphones'
        },
        {
            id: 3,
            name: 'Galaxy Watch 6',
            brand_name: 'Samsung',
            price: 329,
            discount_price: null,
            main_image: 'assets/images/placeholder.svg',
            category: 'smart-watches'
        },
        {
            id: 4,
            name: 'WH-1000XM5',
            brand_name: 'Sony',
            price: 399,
            discount_price: 349,
            main_image: 'assets/images/placeholder.svg',
            category: 'wireless-headphones'
        },
        {
            id: 5,
            name: 'HD 660S2',
            brand_name: 'Sennheiser',
            price: 599,
            discount_price: null,
            main_image: 'assets/images/placeholder.svg',
            category: 'wired-headphones'
        },
        {
            id: 6,
            name: 'QuietComfort 45',
            brand_name: 'Bose',
            price: 329,
            discount_price: 279,
            main_image: 'assets/images/placeholder.svg',
            category: 'wireless-headphones'
        }
    ];
    
    displayProducts(fallbackProducts, container);
}

// Utility function to escape HTML
function escapeHtml(text) {
    const map = {
        '&': '&amp;',
        '<': '&lt;',
        '>': '&gt;',
        '"': '&quot;',
        "'": '&#039;'
    };
    return text.toString().replace(/[&<>"']/g, function(m) { return map[m]; });
}

// Search functionality
function initializeSearch() {
    const searchInput = document.getElementById('search-input');
    const searchResults = document.getElementById('search-results');
    
    if (searchInput) {
        let searchTimeout;
        
        searchInput.addEventListener('input', function(e) {
            clearTimeout(searchTimeout);
            const query = e.target.value.trim();
            
            if (query.length < 2) {
                if (searchResults) searchResults.innerHTML = '';
                return;
            }
            
            searchTimeout = setTimeout(() => {
                performSearch(query);
            }, 300);
        });
    }
}

// Perform search
async function performSearch(query) {
    try {
        const response = await fetch(`api/search_products.php?q=${encodeURIComponent(query)}`);
        const results = await response.json();
        
        const searchResults = document.getElementById('search-results');
        if (searchResults && results) {
            displaySearchResults(results, searchResults);
        }
    } catch (error) {
        console.error('Search error:', error);
    }
}

// Display search results
function displaySearchResults(results, container) {
    if (results.length === 0) {
        container.innerHTML = '<div class="no-results">No products found</div>';
        return;
    }
    
    container.innerHTML = results.map(product => `
        <div class="search-result-item">
            <img src="${getImageUrl(product.main_image)}" 
                 alt="${product.name}"
                 onerror="this.style.display='none';">>
            <div class="result-info">
                <h4>${escapeHtml(product.name)}</h4>
                <p>${escapeHtml(product.brand_name)} - ₹${product.price}</p>
            </div>
        </div>
    `).join('');
}