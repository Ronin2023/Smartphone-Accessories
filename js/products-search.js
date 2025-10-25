// ================================================
// NEW AJAX SEARCH FUNCTIONALITY FOR PRODUCTS PAGE
// ================================================

let searchTimeout = null;
let currentSelectedProduct = null;

function initializeAjaxSearch() {
    console.log('🔍 Initializing AJAX search...');
    
    const searchInput = document.getElementById('product-search-input');
    const searchBtn = document.getElementById('search-btn');
    const suggestionsDiv = document.getElementById('search-suggestions');
    const relatedSection = document.getElementById('related-products');
    
    if (!searchInput) {
        console.error('❌ Search input not found! ID: product-search-input');
        return;
    }
    
    console.log('✅ Search input found:', searchInput);
    console.log('✅ Search button found:', searchBtn);
    console.log('✅ Suggestions div found:', suggestionsDiv);
    console.log('✅ Related section found:', relatedSection);
    
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
            performProductSearch();
        }
    });
    
    // Search button click
    if (searchBtn) {
        searchBtn.addEventListener('click', function() {
            performProductSearch();
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
    console.log('🔍 Fetching suggestions for:', query);
    try {
        const url = `api/search_suggestions.php?q=${encodeURIComponent(query)}&limit=5`;
        console.log('📡 API URL:', url);
        
        const response = await fetch(url);
        console.log('📡 Response status:', response.status);
        
        const data = await response.json();
        console.log('📦 Response data:', data);
        
        if (data.success && data.suggestions.length > 0) {
            console.log('✅ Found suggestions:', data.suggestions.length);
            displaySuggestions(data.suggestions);
        } else {
            console.log('⚠️ No suggestions found');
            displayNoSuggestions();
        }
    } catch (error) {
        console.error('❌ Error fetching suggestions:', error);
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
    if (suggestionsDiv) {
        suggestionsDiv.classList.remove('show');
        setTimeout(() => {
            suggestionsDiv.innerHTML = '';
        }, 300);
    }
}

function hideRelatedProducts() {
    const relatedSection = document.getElementById('related-products');
    if (relatedSection) {
        relatedSection.style.display = 'none';
    }
}

async function selectProduct(productId) {
    currentSelectedProduct = productId;
    hideSuggestions();
    
    // Load the selected product and related products
    await loadProductDetails(productId);
    await loadRelatedProducts(productId);
}

async function loadProductDetails(productId) {
    try {
        const response = await fetch(`api/get_product.php?id=${productId}`);
        const data = await response.json();
        
        if (data.success && data.product) {
            // Filter products grid to show only this product
            currentFilters.search = data.product.name;
            currentPage = 1;
            loadProducts();
            
            // Update search input
            const searchInput = document.getElementById('product-search-input');
            if (searchInput) {
                searchInput.value = data.product.name;
            }
        }
    } catch (error) {
        console.error('Error loading product details:', error);
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
    
    if (!relatedSection || !relatedGrid) return;
    
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

function performProductSearch() {
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
