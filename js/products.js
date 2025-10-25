// products.js - Products page functionality

// Helper function to get absolute image URL
function getImageUrl(relativePath) {
    if (!relativePath) return '';
    if (relativePath.startsWith('http')) return relativePath; // Already absolute
    // Get current base URL dynamically (works with ngrok, localhost, production)
    const baseUrl = window.location.origin + window.location.pathname.replace(/\/[^\/]*$/, '');
    return baseUrl + '/' + relativePath;
}

document.addEventListener('DOMContentLoaded', function() {
    // Initialize products page
    initializeProductsPage();
});

let currentPage = 1;
let currentFilters = {
    category: '',
    brand: '',
    sort: 'featured',
    maxPrice: 100000,
    search: ''
};
let allProducts = [];
let compareProducts = [];

function initializeProductsPage() {
    // Get URL parameters
    parseURLParameters();
    
    // Initialize filters
    initializeFilters();
    
    // Initialize search
    initializeSearch();
    
    // Initialize new AJAX search
    initializeAjaxSearch();
    
    // Initialize view toggles
    initializeViewToggles();
    
    // Initialize modals
    initializeModals();
    
    // Load initial data
    loadBrands();
    loadProducts();
    
    // Set up event listeners
    setupEventListeners();
}

function parseURLParameters() {
    const urlParams = new URLSearchParams(window.location.search);
    
    if (urlParams.get('category')) {
        currentFilters.category = urlParams.get('category');
        document.getElementById('category-filter').value = currentFilters.category;
    }
    
    if (urlParams.get('search')) {
        currentFilters.search = urlParams.get('search');
        document.getElementById('search-input').value = currentFilters.search;
    }
    
    if (urlParams.get('brand')) {
        currentFilters.brand = urlParams.get('brand');
    }
}

function setupEventListeners() {
    // Filter changes
    document.getElementById('category-filter').addEventListener('change', handleFilterChange);
    document.getElementById('brand-filter').addEventListener('change', handleFilterChange);
    document.getElementById('sort-filter').addEventListener('change', handleFilterChange);
    document.getElementById('price-range').addEventListener('input', handlePriceRangeChange);
    document.getElementById('clear-filters').addEventListener('click', clearAllFilters);
    
    // Search
    let searchTimeout;
    document.getElementById('search-input').addEventListener('input', function(e) {
        clearTimeout(searchTimeout);
        searchTimeout = setTimeout(() => {
            currentFilters.search = e.target.value;
            currentPage = 1;
            loadProducts();
        }, 300);
    });
}

function initializeFilters() {
    // Set initial values from current filters
    document.getElementById('category-filter').value = currentFilters.category;
    document.getElementById('sort-filter').value = currentFilters.sort;
    document.getElementById('price-range').value = currentFilters.maxPrice;
    document.getElementById('max-price').textContent = currentFilters.maxPrice;
}

function initializeSearch() {
    const searchInput = document.getElementById('search-input');
    const searchResults = document.getElementById('search-results');
    
    // Check if old search elements exist (backwards compatibility)
    if (!searchInput || !searchResults) {
        console.log('Old search elements not found - using new AJAX search');
        return;
    }
    
    searchInput.addEventListener('focus', function() {
        if (this.value.length >= 2) {
            searchResults.classList.add('show');
        }
    });
    
    searchInput.addEventListener('blur', function() {
        // Delay hiding to allow clicks on results
        setTimeout(() => {
            searchResults.classList.remove('show');
        }, 200);
    });
    
    // Search functionality
    searchInput.addEventListener('input', function() {
        if (this.value.length >= 2) {
            performQuickSearch(this.value);
        } else {
            searchResults.classList.remove('show');
        }
    });
}

async function performQuickSearch(query) {
    try {
        const response = await fetch(`api/search_products.php?q=${encodeURIComponent(query)}`);
        const results = await response.json();
        
        const searchResults = document.getElementById('search-results');
        
        if (results.length > 0) {
            searchResults.innerHTML = results.map(product => `
                <div class="search-result-item" onclick="goToProduct('${product.url}')">
                    <img src="${getImageUrl(product.main_image)}" 
                         alt="${product.name}"
                         onerror="this.style.display='none';">
                    <div class="result-info">
                        <h4>${escapeHtml(product.name)}</h4>
                        <p>${escapeHtml(product.brand_name)} - ₹${product.price}</p>
                    </div>
                </div>
            `).join('');
            searchResults.classList.add('show');
        } else {
            searchResults.innerHTML = '<div class="search-result-item">No products found</div>';
            searchResults.classList.add('show');
        }
    } catch (error) {
        console.error('Search error:', error);
    }
}

function goToProduct(url) {
    window.location.href = url;
}

function initializeViewToggles() {
    document.querySelectorAll('.view-btn').forEach(btn => {
        btn.addEventListener('click', function() {
            document.querySelectorAll('.view-btn').forEach(b => b.classList.remove('active'));
            this.classList.add('active');
            
            const view = this.dataset.view;
            const productsGrid = document.getElementById('products-grid');
            
            if (view === 'list') {
                productsGrid.classList.add('list-view');
            } else {
                productsGrid.classList.remove('list-view');
            }
        });
    });
}

function initializeModals() {
    // Close modal when clicking outside
    document.getElementById('quick-view-modal').addEventListener('click', function(e) {
        if (e.target === this) {
            this.classList.remove('show');
        }
    });
    
    // Close modal button
    document.querySelector('.modal-close').addEventListener('click', function() {
        document.getElementById('quick-view-modal').classList.remove('show');
    });
}

async function loadBrands() {
    try {
        const response = await fetch('api/get_brands.php');
        const brands = await response.json();
        
        const brandSelect = document.getElementById('brand-filter');
        brandSelect.innerHTML = '<option value="">All Brands</option>';
        
        brands.forEach(brand => {
            const option = document.createElement('option');
            option.value = brand.name;
            option.textContent = brand.name;
            if (brand.name === currentFilters.brand) {
                option.selected = true;
            }
            brandSelect.appendChild(option);
        });
    } catch (error) {
        console.error('Error loading brands:', error);
    }
}

async function loadProducts() {
    const productsGrid = document.getElementById('products-grid');
    const resultsCount = document.getElementById('results-count');
    
    console.log('📦 Loading products...');
    console.log('📦 Current filters:', currentFilters);
    
    // Show loading state
    productsGrid.innerHTML = '<div class="loading"><i class="fas fa-spinner fa-spin"></i> Loading products...</div>';
    
    try {
        const params = new URLSearchParams({
            page: currentPage,
            limit: 12,
            ...currentFilters
        });
        
        const url = `api/get_products.php?${params}`;
        console.log('📡 API URL:', url);
        
        const response = await fetch(url);
        console.log('📡 Response status:', response.status);
        
        const data = await response.json();
        console.log('📦 Products data:', data);
        
        if (data.products && data.products.length > 0) {
            console.log(`✅ Found ${data.products.length} products`);
            allProducts = data.products;
            displayProducts(data.products);
            updateResultsCount(data.pagination);
            generatePagination(data.pagination);
        } else {
            console.log('⚠️ No products found');
            showEmptyState();
        }
    } catch (error) {
        console.error('❌ Error loading products:', error);
        
        // Check if this is a connection error
        if (window.connectionErrorHandler && window.connectionErrorHandler.shouldShowConnectionError(error)) {
            // Let the connection error handler deal with it
            return;
        }
        
        // For other errors, show the regular error state
        showErrorState();
    }
}

function displayProducts(products) {
    const productsGrid = document.getElementById('products-grid');
    
    productsGrid.innerHTML = products.map(product => `
        <div class="product-card" data-product-id="${product.id}">
            <div class="product-image">
                <img src="${getImageUrl(product.main_image)}" 
                     alt="${product.name}"
                     onerror="this.onerror=null; this.style.display='none';"
                     onload="this.nextElementSibling.style.display='none';">
                <div class="image-fallback" style="display: none; width: 100%; height: 200px; background: #f0f0f0; display: flex; align-items: center; justify-content: center; color: #666;">No Image</div>
                <div class="product-badges">
                    ${product.discount_price ? '<span class="badge sale">Sale</span>' : ''}
                    ${product.is_featured ? '<span class="badge featured">Featured</span>' : ''}
                </div>
            </div>
            <div class="product-info">
                <div class="product-category">${product.category_name}</div>
                <h3 class="product-title">${escapeHtml(product.name)}</h3>
                <p class="product-brand">${escapeHtml(product.brand_name)}</p>
                <div class="product-rating">
                    <div class="rating-stars">
                        ${generateStars(product.rating)}
                    </div>
                    <span class="rating-text">(${product.review_count} reviews)</span>
                </div>
                <div class="product-price">
                    ${product.discount_price ? `
                        <span class="original-price">₹${product.price}</span>
                        <span class="current-price">₹${product.discount_price}</span>
                    ` : `
                        <span class="current-price">₹${product.price}</span>
                    `}
                </div>
                <div class="product-actions">
                    <button class="btn-quick-view" onclick="showQuickView(${product.id})">
                        Quick View
                    </button>
                    <button class="btn-compare ${compareProducts.includes(product.id) ? 'active' : ''}" 
                            onclick="toggleCompare(${product.id})" 
                            title="Add to compare">
                        <i class="fas fa-balance-scale"></i>
                    </button>
                </div>
            </div>
        </div>
    `).join('');
}

function generateStars(rating) {
    const fullStars = Math.floor(rating);
    const hasHalfStar = rating % 1 >= 0.5;
    const emptyStars = 5 - fullStars - (hasHalfStar ? 1 : 0);
    
    let stars = '';
    
    // Full stars
    for (let i = 0; i < fullStars; i++) {
        stars += '<i class="fas fa-star"></i>';
    }
    
    // Half star
    if (hasHalfStar) {
        stars += '<i class="fas fa-star-half-alt"></i>';
    }
    
    // Empty stars
    for (let i = 0; i < emptyStars; i++) {
        stars += '<i class="far fa-star"></i>';
    }
    
    return stars;
}

async function showQuickView(productId) {
    const modal = document.getElementById('quick-view-modal');
    const content = document.getElementById('quick-view-content');
    
    // Show loading
    content.innerHTML = '<div class="loading"><i class="fas fa-spinner fa-spin"></i> Loading...</div>';
    modal.classList.add('show');
    
    try {
        const response = await fetch(`api/get_product.php?id=${productId}`);
        const product = await response.json();
        
        content.innerHTML = `
            <div class="quick-view-product">
                <div class="quick-view-image">
                    <img src="${getImageUrl(product.main_image)}" 
                         alt="${product.name}"
                         onerror="this.style.display='none';">
                </div>
                <div class="quick-view-details">
                    <div class="product-category">${product.category_name}</div>
                    <h2>${escapeHtml(product.name)}</h2>
                    <p class="product-brand">${escapeHtml(product.brand_name)}</p>
                    
                    <div class="product-rating">
                        <div class="rating-stars">${generateStars(product.rating)}</div>
                        <span class="rating-text">(${product.review_count} reviews)</span>
                    </div>
                    
                    <div class="product-price">
                        ${product.discount_price ? `
                            <span class="original-price">₹${product.price}</span>
                            <span class="current-price">₹${product.discount_price}</span>
                        ` : `
                            <span class="current-price">₹${product.price}</span>
                        `}
                    </div>
                    
                    <div class="product-description">
                        <p>${product.description}</p>
                    </div>
                    
                    ${product.specifications ? `
                        <div class="product-specs">
                            <h4>Key Specifications</h4>
                            <ul>
                                ${Object.entries(JSON.parse(product.specifications)).slice(0, 5).map(([key, value]) => 
                                    `<li><strong>${formatSpecKey(key)}:</strong> ${value}</li>`
                                ).join('')}
                            </ul>
                        </div>
                    ` : ''}
                    
                    <div class="quick-view-actions">
                        <button class="btn btn-primary" onclick="addToCompare(${product.id})">
                            Add to Compare
                        </button>
                        <button class="btn btn-outline" onclick="viewFullDetails(${product.id})">
                            View Full Details
                        </button>
                    </div>
                </div>
            </div>
        `;
    } catch (error) {
        content.innerHTML = '<div class="error-message">Failed to load product details</div>';
    }
}

function formatSpecKey(key) {
    return key.replace(/_/g, ' ').replace(/\b\w/g, l => l.toUpperCase());
}

function toggleCompare(productId) {
    const index = compareProducts.indexOf(productId);
    
    if (index > -1) {
        compareProducts.splice(index, 1);
    } else {
        if (compareProducts.length >= 4) {
            alert('You can compare up to 4 products at once.');
            return;
        }
        compareProducts.push(productId);
    }
    
    // Update button state
    updateCompareButtons();
    
    // Save to localStorage
    localStorage.setItem('compareProducts', JSON.stringify(compareProducts));
}

function updateCompareButtons() {
    document.querySelectorAll('.btn-compare').forEach(btn => {
        const productId = parseInt(btn.closest('.product-card').dataset.productId);
        if (compareProducts.includes(productId)) {
            btn.classList.add('active');
        } else {
            btn.classList.remove('active');
        }
    });
}

function addToCompare(productId) {
    toggleCompare(productId);
    document.getElementById('quick-view-modal').classList.remove('show');
}

function viewFullDetails(productId) {
    window.location.href = `product-detail?id=${productId}`;
}

function handleFilterChange() {
    currentFilters.category = document.getElementById('category-filter').value;
    currentFilters.brand = document.getElementById('brand-filter').value;
    currentFilters.sort = document.getElementById('sort-filter').value;
    currentPage = 1;
    
    // Update URL
    updateURL();
    
    loadProducts();
}

function handlePriceRangeChange(e) {
    currentFilters.maxPrice = e.target.value;
    document.getElementById('max-price').textContent = e.target.value;
    
    // Debounce the API call
    clearTimeout(this.priceTimeout);
    this.priceTimeout = setTimeout(() => {
        currentPage = 1;
        loadProducts();
    }, 500);
}

function clearAllFilters() {
    currentFilters = {
        category: '',
        brand: '',
        sort: 'featured',
        maxPrice: 1000,
        search: ''
    };
    currentPage = 1;
    
    // Reset form elements
    document.getElementById('category-filter').value = '';
    document.getElementById('brand-filter').value = '';
    document.getElementById('sort-filter').value = 'featured';
    document.getElementById('price-range').value = 1000;
    document.getElementById('max-price').textContent = '1000';
    document.getElementById('search-input').value = '';
    
    // Update URL and reload
    updateURL();
    loadProducts();
}

function updateURL() {
    const params = new URLSearchParams();
    
    Object.entries(currentFilters).forEach(([key, value]) => {
        if (value) {
            params.set(key, value);
        }
    });
    
    if (currentPage > 1) {
        params.set('page', currentPage);
    }
    
    const newURL = window.location.pathname + (params.toString() ? '?' + params.toString() : '');
    window.history.replaceState({}, '', newURL);
}

function updateResultsCount(pagination) {
    const resultsCount = document.getElementById('results-count');
    const start = ((pagination.current_page - 1) * pagination.per_page) + 1;
    const end = Math.min(start + pagination.per_page - 1, pagination.total);
    
    resultsCount.textContent = `Showing ${start}-${end} of ${pagination.total} products`;
}

function generatePagination(pagination) {
    const paginationContainer = document.getElementById('pagination');
    
    if (pagination.total_pages <= 1) {
        paginationContainer.innerHTML = '';
        return;
    }
    
    let html = '';
    
    // Previous button
    html += `
        <button class="pagination-btn ${!pagination.has_prev ? 'disabled' : ''}" 
                onclick="changePage(${pagination.current_page - 1})"
                ${!pagination.has_prev ? 'disabled' : ''}>
            <i class="fas fa-chevron-left"></i> Previous
        </button>
    `;
    
    // Page numbers
    const startPage = Math.max(1, pagination.current_page - 2);
    const endPage = Math.min(pagination.total_pages, pagination.current_page + 2);
    
    if (startPage > 1) {
        html += `<button class="pagination-btn" onclick="changePage(1)">1</button>`;
        if (startPage > 2) {
            html += `<span class="pagination-dots">...</span>`;
        }
    }
    
    for (let i = startPage; i <= endPage; i++) {
        html += `
            <button class="pagination-btn ${i === pagination.current_page ? 'active' : ''}" 
                    onclick="changePage(${i})">
                ${i}
            </button>
        `;
    }
    
    if (endPage < pagination.total_pages) {
        if (endPage < pagination.total_pages - 1) {
            html += `<span class="pagination-dots">...</span>`;
        }
        html += `<button class="pagination-btn" onclick="changePage(${pagination.total_pages})">${pagination.total_pages}</button>`;
    }
    
    // Next button
    html += `
        <button class="pagination-btn ${!pagination.has_next ? 'disabled' : ''}" 
                onclick="changePage(${pagination.current_page + 1})"
                ${!pagination.has_next ? 'disabled' : ''}>
            Next <i class="fas fa-chevron-right"></i>
        </button>
    `;
    
    paginationContainer.innerHTML = html;
}

function changePage(page) {
    currentPage = page;
    updateURL();
    loadProducts();
    
    // Scroll to top of products section
    document.querySelector('.products-section').scrollIntoView({ 
        behavior: 'smooth',
        block: 'start'
    });
}

function showEmptyState() {
    const productsGrid = document.getElementById('products-grid');
    productsGrid.innerHTML = `
        <div class="empty-state">
            <i class="fas fa-search"></i>
            <h3>No products found</h3>
            <p>Try adjusting your filters or search terms</p>
            <button class="btn btn-primary" onclick="clearAllFilters()">Clear Filters</button>
        </div>
    `;
}

function showErrorState() {
    const productsGrid = document.getElementById('products-grid');
    productsGrid.innerHTML = `
        <div class="empty-state">
            <i class="fas fa-exclamation-triangle"></i>
            <h3>Something went wrong</h3>
            <p>We couldn't load the products. Please try again.</p>
            <button class="btn btn-primary" onclick="loadProducts()">Retry</button>
        </div>
    `;
}

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

// Load compare products from localStorage on page load
window.addEventListener('load', function() {
    const saved = localStorage.getItem('compareProducts');
    if (saved) {
        compareProducts = JSON.parse(saved);
    }
});

// ================================================
// NEW AJAX SEARCH FUNCTIONALITY
// ================================================

let searchTimeout = null;
let currentSelectedProduct = null;

function initializeAjaxSearch() {
    const searchInput = document.getElementById('product-search-input');
    const searchBtn = document.getElementById('search-btn');
    const suggestionsDiv = document.getElementById('search-suggestions');
    const relatedSection = document.getElementById('related-products');
    
    if (!searchInput) return; // Exit if element doesn't exist
    
    // Input event - show suggestions
    searchInput.addEventListener('input', function(e) {
        const query = e.target.value.trim();
        
        clearTimeout(searchTimeout);
        
        if (query.length < 2) {
            hideSuggestions();
            hideRelatedProducts();
            return;
        }
        
        searchTimeout = setTimeout(() => {
            fetchSuggestions(query);
        }, 300); // Debounce 300ms
    });
    
    // Enter key - search
    searchInput.addEventListener('keypress', function(e) {
        if (e.key === 'Enter') {
            e.preventDefault();
            performSearch();
        }
    });
    
    // Search button click
    if (searchBtn) {
        searchBtn.addEventListener('click', function() {
            performSearch();
        });
    }
    
    // Click outside to close suggestions
    document.addEventListener('click', function(e) {
        if (!searchInput.contains(e.target) && !suggestionsDiv.contains(e.target)) {
            hideSuggestions();
        }
    });
}

async function fetchSuggestions(query) {
    try {
        const response = await fetch(`api/search_suggestions.php?q=${encodeURIComponent(query)}&limit=5`);
        const data = await response.json();
        
        if (data.success && data.suggestions.length > 0) {
            displaySuggestions(data.suggestions);
        } else {
            displayNoSuggestions();
        }
    } catch (error) {
        console.error('Error fetching suggestions:', error);
        displayNoSuggestions();
    }
}

function displaySuggestions(suggestions) {
    const suggestionsDiv = document.getElementById('search-suggestions');
    
    const html = suggestions.map(product => `
        <div class="suggestion-item" onclick="selectProduct(${product.id})">
            <img src="${getImageUrl(product.image_url)}" 
                 alt="${escapeHtml(product.name)}"
                 onerror="this.src='assets/images/placeholder.jpg'">
            <div class="suggestion-info">
                <h4>${escapeHtml(product.name)}</h4>
                ${product.brand_name ? `<div class="suggestion-brand">${escapeHtml(product.brand_name)}</div>` : ''}
                <div class="suggestion-price">
                    ${product.has_discount ? 
                        `<span style="text-decoration: line-through; color: #999; font-size: 0.85rem;">₹${formatPrice(product.price)}</span> 
                         <span style="color: var(--primary-color);">₹${formatPrice(product.display_price)}</span>` :
                        `₹${formatPrice(product.display_price)}`
                    }
                </div>
            </div>
        </div>
    `).join('');
    
    suggestionsDiv.innerHTML = html;
    suggestionsDiv.classList.add('show');
}

function displayNoSuggestions() {
    const suggestionsDiv = document.getElementById('search-suggestions');
    suggestionsDiv.innerHTML = '<div class="no-suggestions"><i class="fas fa-search"></i> No products found</div>';
    suggestionsDiv.classList.add('show');
}

function hideSuggestions() {
    const suggestionsDiv = document.getElementById('search-suggestions');
    suggestionsDiv.classList.remove('show');
    setTimeout(() => {
        suggestionsDiv.innerHTML = '';
    }, 300);
}

function hideRelatedProducts() {
    const relatedSection = document.getElementById('related-products');
    if (relatedSection) {
        relatedSection.style.display = 'none';
    }
}

async function selectProduct(productId) {
    console.log('🎯 Product selected:', productId);
    currentSelectedProduct = productId;
    hideSuggestions();
    
    // Load the selected product and related products
    await loadProductDetails(productId);
    await loadRelatedProducts(productId);
}

async function loadProductDetails(productId) {
    console.log('📦 Loading product details for ID:', productId);
    try {
        const url = `api/get_product.php?id=${productId}`;
        console.log('📡 Fetching from:', url);
        
        const response = await fetch(url);
        console.log('📡 Response status:', response.status);
        
        const data = await response.json();
        console.log('📦 Product data:', data);
        
        if (data.success && data.product) {
            console.log('✅ Product found:', data.product.name);
            
            // Filter products grid to show only this product
            currentFilters.search = data.product.name;
            currentPage = 1;
            
            console.log('🔍 Setting search filter to:', data.product.name);
            console.log('📦 Reloading products with new filter...');
            
            loadProducts();
            
            // Update search input
            const searchInput = document.getElementById('product-search-input');
            if (searchInput) {
                searchInput.value = data.product.name;
            }
        } else {
            console.error('❌ Product not found or invalid response:', data);
        }
    } catch (error) {
        console.error('❌ Error loading product details:', error);
    }
}

async function loadRelatedProducts(productId) {
    try {
        const response = await fetch(`api/get_related_products.php?id=${productId}&limit=6`);
        const data = await response.json();
        
        if (data.success && data.products.length > 0) {
            displayRelatedProducts(data.products);
        }
    } catch (error) {
        console.error('Error loading related products:', error);
    }
}

function displayRelatedProducts(products) {
    const relatedSection = document.getElementById('related-products');
    const relatedGrid = document.getElementById('related-products-grid');
    
    if (!relatedSection || !relatedGrid) {
        console.error('Related products container not found');
        return;
    }
    
    const html = products.map(product => `
        <div class="related-product-card" onclick="selectProduct(${product.id})">
            <img src="${getImageUrl(product.image_url)}" 
                 alt="${escapeHtml(product.name)}"
                 onerror="this.src='assets/images/placeholder.jpg'">
            <h4>${escapeHtml(truncateText(product.name, 40))}</h4>
            <div class="price">₹${formatPrice(product.display_price)}</div>
        </div>
    `).join('');
    
    relatedGrid.innerHTML = html;
    relatedSection.style.display = 'block';
    
    // Smooth scroll to related products
    setTimeout(() => {
        relatedSection.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
    }, 100);
}

function performSearch() {
    const searchInput = document.getElementById('product-search-input');
    if (!searchInput) return;
    
    const query = searchInput.value.trim();
    
    if (query.length < 2) return;
    
    // Update filters and reload products
    currentFilters.search = query;
    currentPage = 1;
    loadProducts();
    
    hideSuggestions();
    hideRelatedProducts();
}

function truncateText(text, maxLength) {
    if (text.length <= maxLength) return text;
    return text.substring(0, maxLength) + '...';
}

function formatPrice(price) {
    return parseFloat(price).toLocaleString('en-IN', {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2
    });
}
