// Compare Page Enhanced JavaScript

let selectedProducts = [];
const maxProducts = 4;
let allProducts = [];
let currentCategory = 'all';

document.addEventListener('DOMContentLoaded', function() {
    initializeComparePage();
    loadProducts();
    initializeAnimations();
    initializeModals();
    initializeShare();
    
    // Initialize category tabs
    document.querySelectorAll('.tab-btn').forEach(btn => {
        btn.addEventListener('click', function() {
            const category = this.dataset.category;
            switchCategory(category);
        });
    });
    
    // Initialize search with enhanced functionality
    const searchInput = document.getElementById('productSearch');
    if (searchInput) {
        searchInput.addEventListener('input', debounce(handleSearch, 300));
        searchInput.addEventListener('focus', function() {
            this.parentElement.classList.add('focused');
        });
        searchInput.addEventListener('blur', function() {
            this.parentElement.classList.remove('focused');
        });
    }
    
    // Initialize comparison actions
    const exportBtn = document.getElementById('exportComparison');
    if (exportBtn) {
        exportBtn.addEventListener('click', exportComparison);
    }
    
    const printBtn = document.getElementById('printComparison');
    if (printBtn) {
        printBtn.addEventListener('click', printComparison);
    }
    
    const shareBtn = document.getElementById('shareComparison');
    if (shareBtn) {
        shareBtn.addEventListener('click', openShareModal);
    }
    
    console.log('Enhanced compare page loaded successfully!');
});

// Initialize animations and scroll effects
function initializeAnimations() {
    // Fade in animation for product cards
    const observerOptions = {
        threshold: 0.1,
        rootMargin: '0px 0px -50px 0px'
    };

    const observer = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                entry.target.classList.add('fade-in');
            }
        });
    }, observerOptions);

    // Observe product cards
    document.querySelectorAll('.product-selector-card').forEach(card => {
        observer.observe(card);
    });
    
    // Add loading animation to selector
    const productGrid = document.querySelector('.product-grid');
    if (productGrid) {
        productGrid.classList.add('loading');
        setTimeout(() => {
            productGrid.classList.remove('loading');
        }, 1000);
    }
}

// Initialize modal functionality
function initializeModals() {
    // Share modal
    const shareModal = document.getElementById('shareModal');
    const closeBtn = shareModal?.querySelector('.modal-close');
    
    if (closeBtn) {
        closeBtn.addEventListener('click', closeShareModal);
    }
    
    if (shareModal) {
        shareModal.addEventListener('click', function(e) {
            if (e.target === shareModal) {
                closeShareModal();
            }
        });
    }
    
    // Social sharing buttons
    document.querySelectorAll('.social-btn').forEach(btn => {
        btn.addEventListener('click', function() {
            const platform = this.classList.contains('facebook') ? 'facebook' :
                           this.classList.contains('twitter') ? 'twitter' :
                           this.classList.contains('linkedin') ? 'linkedin' : '';
            
            if (platform) {
                shareOnPlatform(platform);
            }
        });
    });
}

// Initialize share functionality
function initializeShare() {
    const copyLinkBtn = document.getElementById('copyLink');
    if (copyLinkBtn) {
        copyLinkBtn.addEventListener('click', copyComparisonLink);
    }
}

function initializeComparePage() {
    updateComparisonDisplay();
    
    // Check for URL parameters to restore comparison
    const urlParams = new URLSearchParams(window.location.search);
    const productIds = urlParams.get('products');
    if (productIds) {
        const ids = productIds.split(',');
        loadProductsForComparison(ids);
    }
}

async function loadProducts() {
    try {
        const response = await fetch('api/get_products.php');
        const result = await response.json();
        
        if (result.success) {
            allProducts = result.data;
            displayProducts(allProducts);
        } else {
            showError('Failed to load products');
        }
    } catch (error) {
        console.error('Error loading products:', error);
        showError('Error loading products');
    }
}

function displayProducts(products) {
    const productGrid = document.querySelector('.product-grid');
    if (!productGrid) return;
    
    productGrid.innerHTML = '';
    
    if (products.length === 0) {
        productGrid.innerHTML = `
            <div class="empty-state">
                <i class="fas fa-search"></i>
                <h3>No products found</h3>
                <p>Try adjusting your search criteria or browse different categories.</p>
            </div>
        `;
        return;
    }
    
    products.forEach(product => {
        const isSelected = selectedProducts.some(p => p.id === product.id);
        const productCard = createProductCard(product, isSelected);
        productGrid.appendChild(productCard);
    });
    
    // Re-initialize animations for new cards
    productGrid.querySelectorAll('.product-selector-card').forEach((card, index) => {
        card.style.animationDelay = `${index * 0.1}s`;
        card.classList.add('slide-up');
    });
}

function createProductCard(product, isSelected) {
    const card = document.createElement('div');
    card.className = `product-selector-card ${isSelected ? 'selected' : ''}`;
    card.dataset.productId = product.id;
    
    card.innerHTML = `
        ${isSelected ? '<div class="selected-badge"><i class="fas fa-check"></i></div>' : ''}
        <div class="product-image">
            <img src="${product.image || `https://via.placeholder.com/200x150/4361ee/ffffff?text=${encodeURIComponent(product.name)}`}" 
                 alt="${product.name}" 
                 loading="lazy"
                 onerror="this.src='https://via.placeholder.com/200x150/4361ee/ffffff?text=${encodeURIComponent(product.name)}'">
        </div>
        <div class="product-info">
            <div class="product-brand">${product.brand || 'Unknown'}</div>
            <h3 class="product-title">${product.name}</h3>
            <div class="product-price">₹${parseFloat(product.price).toFixed(2)}</div>
            <div class="product-rating">
                ${generateStars(product.rating || 4)}
                <span class="rating-text">(${product.rating || 4.0})</span>
            </div>
        </div>
    `;
    
    card.addEventListener('click', () => {
        if (isSelected) {
            removeProductFromComparison(product.id);
        } else {
            addProductToComparison(product);
        }
    });
    
    return card;
}

function generateStars(rating) {
    const fullStars = Math.floor(rating);
    const hasHalfStar = rating % 1 >= 0.5;
    let starsHTML = '';
    
    for (let i = 0; i < fullStars; i++) {
        starsHTML += '<i class="fas fa-star"></i>';
    }
    
    if (hasHalfStar) {
        starsHTML += '<i class="fas fa-star-half-alt"></i>';
    }
    
    const remainingStars = 5 - fullStars - (hasHalfStar ? 1 : 0);
    for (let i = 0; i < remainingStars; i++) {
        starsHTML += '<i class="far fa-star"></i>';
    }
    
    return starsHTML;
}

function addProductToComparison(product) {
    if (selectedProducts.length >= maxProducts) {
        showNotification(`Maximum ${maxProducts} products can be compared`, 'warning');
        return;
    }
    
    selectedProducts.push(product);
    updateProductCardSelection(product.id, true);
    updateComparisonDisplay();
    showNotification(`${product.name} added to comparison`, 'success');
    
    // Add animation
    const card = document.querySelector(`[data-product-id="${product.id}"]`);
    if (card) {
        card.classList.add('pulse');
        setTimeout(() => card.classList.remove('pulse'), 600);
    }
}

function removeProductFromComparison(productId) {
    selectedProducts = selectedProducts.filter(p => p.id !== productId);
    updateProductCardSelection(productId, false);
    updateComparisonDisplay();
    
    const removedProduct = allProducts.find(p => p.id === productId);
    if (removedProduct) {
        showNotification(`${removedProduct.name} removed from comparison`, 'info');
    }
}

function updateProductCardSelection(productId, isSelected) {
    const card = document.querySelector(`[data-product-id="${productId}"]`);
    if (!card) return;
    
    if (isSelected) {
        card.classList.add('selected');
        if (!card.querySelector('.selected-badge')) {
            const badge = document.createElement('div');
            badge.className = 'selected-badge';
            badge.innerHTML = '<i class="fas fa-check"></i>';
            card.appendChild(badge);
        }
    } else {
        card.classList.remove('selected');
        const badge = card.querySelector('.selected-badge');
        if (badge) badge.remove();
    }
}

function updateComparisonDisplay() {
    const comparisonContainer = document.querySelector('.comparison-container');
    const emptyComparison = document.querySelector('.empty-comparison');
    
    if (selectedProducts.length === 0) {
        if (comparisonContainer) comparisonContainer.style.display = 'none';
        if (emptyComparison) emptyComparison.style.display = 'block';
        return;
    }
    
    if (comparisonContainer) comparisonContainer.style.display = 'block';
    if (emptyComparison) emptyComparison.style.display = 'none';
    
    updateComparisonTable();
    updateMobileComparison();
}

function updateComparisonTable() {
    const tableContainer = document.querySelector('.comparison-table-container');
    if (!tableContainer) return;
    
    const table = createComparisonTable();
    tableContainer.innerHTML = '';
    tableContainer.appendChild(table);
}

function createComparisonTable() {
    const table = document.createElement('table');
    table.className = 'comparison-table';
    
    // Create header
    const thead = document.createElement('thead');
    const headerRow = document.createElement('tr');
    
    // Spec column header
    const specHeader = document.createElement('th');
    specHeader.className = 'spec-column';
    specHeader.textContent = 'Specifications';
    headerRow.appendChild(specHeader);
    
    // Product headers
    selectedProducts.forEach(product => {
        const th = document.createElement('th');
        th.className = 'product-column';
        th.innerHTML = `
            <div class="product-header">
                <img src="${product.image || `https://via.placeholder.com/80x80/4361ee/ffffff?text=${encodeURIComponent(product.name.substring(0, 2))}`}" 
                     alt="${product.name}"
                     onerror="this.src='https://via.placeholder.com/80x80/4361ee/ffffff?text=${encodeURIComponent(product.name.substring(0, 2))}'">
                <h4 class="product-title">${product.name}</h4>
                <p class="product-brand">${product.brand || 'Unknown'}</p>
                <div class="product-price">₹${parseFloat(product.price).toFixed(2)}</div>
                <button class="remove-product" onclick="removeProductFromComparison(${product.id})">
                    <i class="fas fa-times"></i>
                </button>
            </div>
        `;
        headerRow.appendChild(th);
    });
    
    thead.appendChild(headerRow);
    table.appendChild(thead);
    
    // Create body with specifications
    const tbody = document.createElement('tbody');
    const specs = getComparisonSpecs();
    
    specs.forEach(spec => {
        const row = document.createElement('tr');
        
        // Spec name cell
        const specCell = document.createElement('td');
        specCell.className = 'spec-row';
        specCell.textContent = spec.name;
        row.appendChild(specCell);
        
        // Product spec cells
        selectedProducts.forEach(product => {
            const cell = document.createElement('td');
            cell.className = 'product-column';
            cell.innerHTML = getProductSpecValue(product, spec.key);
            row.appendChild(cell);
        });
        
        tbody.appendChild(row);
    });
    
    table.appendChild(tbody);
    return table;
}

function getComparisonSpecs() {
    return [
        { name: 'Price', key: 'price' },
        { name: 'Brand', key: 'brand' },
        { name: 'Rating', key: 'rating' },
        { name: 'Category', key: 'category' },
        { name: 'Description', key: 'description' }
    ];
}

function getProductSpecValue(product, specKey) {
    switch (specKey) {
        case 'price':
            return `<strong>₹${parseFloat(product.price).toFixed(2)}</strong>`;
        case 'rating':
            return `
                <div class="rating-display">
                    ${generateStars(product.rating || 4)}
                    <span>${product.rating || 4.0}</span>
                </div>
            `;
        case 'brand':
            return product.brand || 'Unknown';
        case 'category':
            return product.category || 'General';
        case 'description':
            return product.description || 'No description available';
        default:
            return product[specKey] || '-';
    }
}

function updateMobileComparison() {
    const mobileContainer = document.querySelector('.mobile-comparison');
    if (!mobileContainer) return;
    
    mobileContainer.innerHTML = '';
    
    selectedProducts.forEach(product => {
        const card = document.createElement('div');
        card.className = 'mobile-comparison-card';
        
        card.innerHTML = `
            <div class="mobile-card-header">
                <img src="${product.image || `https://via.placeholder.com/60x60/4361ee/ffffff?text=${encodeURIComponent(product.name.substring(0, 2))}`}" 
                     alt="${product.name}"
                     onerror="this.src='https://via.placeholder.com/60x60/4361ee/ffffff?text=${encodeURIComponent(product.name.substring(0, 2))}'">
                <div>
                    <h4>${product.name}</h4>
                    <p>${product.brand || 'Unknown'}</p>
                    <div class="price">₹${parseFloat(product.price).toFixed(2)}</div>
                </div>
                <button class="remove-product" onclick="removeProductFromComparison(${product.id})">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <div class="mobile-card-content">
                ${getComparisonSpecs().map(spec => `
                    <div class="mobile-spec-item">
                        <span class="spec-label">${spec.name}</span>
                        <span class="spec-value">${getProductSpecValue(product, spec.key)}</span>
                    </div>
                `).join('')}
            </div>
        `;
        
        mobileContainer.appendChild(card);
    });
}

function switchCategory(category) {
    currentCategory = category;
    
    // Update active tab
    document.querySelectorAll('.tab-btn').forEach(btn => {
        btn.classList.toggle('active', btn.dataset.category === category);
    });
    
    // Filter products
    let filteredProducts = allProducts;
    if (category !== 'all') {
        filteredProducts = allProducts.filter(product => 
            product.category && product.category.toLowerCase() === category.toLowerCase()
        );
    }
    
    displayProducts(filteredProducts);
}

function handleSearch(event) {
    const query = event.target.value.toLowerCase().trim();
    
    let filteredProducts = allProducts;
    
    // Apply category filter
    if (currentCategory !== 'all') {
        filteredProducts = filteredProducts.filter(product => 
            product.category && product.category.toLowerCase() === currentCategory.toLowerCase()
        );
    }
    
    // Apply search filter
    if (query) {
        filteredProducts = filteredProducts.filter(product =>
            product.name.toLowerCase().includes(query) ||
            (product.brand && product.brand.toLowerCase().includes(query)) ||
            (product.description && product.description.toLowerCase().includes(query))
        );
    }
    
    displayProducts(filteredProducts);
}

// Export functionality
function exportComparison() {
    if (selectedProducts.length === 0) {
        showNotification('No products to export', 'warning');
        return;
    }
    
    const csvContent = generateCSV();
    downloadFile(csvContent, 'product-comparison.csv', 'text/csv');
    showNotification('Comparison exported successfully', 'success');
}

function generateCSV() {
    const specs = getComparisonSpecs();
    const headers = ['Specification', ...selectedProducts.map(p => p.name)];
    
    let csvContent = headers.join(',') + '\n';
    
    specs.forEach(spec => {
        const row = [spec.name];
        selectedProducts.forEach(product => {
            const value = getProductSpecValue(product, spec.key)
                .replace(/<[^>]*>/g, '') // Remove HTML tags
                .replace(/,/g, ';'); // Replace commas with semicolons
            row.push(`"${value}"`);
        });
        csvContent += row.join(',') + '\n';
    });
    
    return csvContent;
}

function downloadFile(content, filename, mimeType) {
    const blob = new Blob([content], { type: mimeType });
    const url = URL.createObjectURL(blob);
    
    const link = document.createElement('a');
    link.href = url;
    link.download = filename;
    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);
    URL.revokeObjectURL(url);
}

// Print functionality
function printComparison() {
    if (selectedProducts.length === 0) {
        showNotification('No products to print', 'warning');
        return;
    }
    
    const printWindow = window.open('', '_blank');
    const printContent = generatePrintContent();
    
    printWindow.document.write(printContent);
    printWindow.document.close();
    printWindow.print();
}

function generatePrintContent() {
    return `
        <!DOCTYPE html>
        <html>
        <head>
            <title>Product Comparison - TechCompare</title>
            <style>
                body { font-family: Arial, sans-serif; margin: 20px; }
                .header { text-align: center; margin-bottom: 30px; }
                .comparison-table { width: 100%; border-collapse: collapse; }
                .comparison-table th, .comparison-table td { 
                    border: 1px solid #ddd; 
                    padding: 10px; 
                    text-align: left; 
                }
                .comparison-table th { background-color: #f5f5f5; }
                .product-image { width: 50px; height: 50px; object-fit: cover; }
                @media print { 
                    body { margin: 0; }
                    .no-print { display: none; }
                }
            </style>
        </head>
        <body>
            <div class="header">
                <h1>Product Comparison</h1>
                <p>Generated by TechCompare - ${new Date().toLocaleDateString()}</p>
            </div>
            ${document.querySelector('.comparison-table-container').innerHTML}
        </body>
        </html>
    `;
}

// Share functionality
function openShareModal() {
    const modal = document.getElementById('shareModal');
    if (modal) {
        modal.classList.add('show');
        generateShareLink();
    }
}

function closeShareModal() {
    const modal = document.getElementById('shareModal');
    if (modal) {
        modal.classList.remove('show');
    }
}

function generateShareLink() {
    const productIds = selectedProducts.map(p => p.id).join(',');
    const shareUrl = `${window.location.origin}${window.location.pathname}?products=${productIds}`;
    
    const shareInput = document.getElementById('shareInput');
    if (shareInput) {
        shareInput.value = shareUrl;
    }
    
    return shareUrl;
}

function copyComparisonLink() {
    const shareInput = document.getElementById('shareInput');
    if (shareInput) {
        shareInput.select();
        shareInput.setSelectionRange(0, 99999);
        
        try {
            document.execCommand('copy');
            showNotification('Link copied to clipboard!', 'success');
        } catch (err) {
            // Fallback for modern browsers
            navigator.clipboard.writeText(shareInput.value).then(() => {
                showNotification('Link copied to clipboard!', 'success');
            }).catch(() => {
                showNotification('Failed to copy link', 'error');
            });
        }
    }
}

function shareOnPlatform(platform) {
    const shareUrl = generateShareLink();
    const shareText = `Check out this product comparison on TechCompare!`;
    
    let platformUrl = '';
    
    switch (platform) {
        case 'facebook':
            platformUrl = `https://www.facebook.com/sharer/sharer.php?u=${encodeURIComponent(shareUrl)}`;
            break;
        case 'twitter':
            platformUrl = `https://twitter.com/intent/tweet?url=${encodeURIComponent(shareUrl)}&text=${encodeURIComponent(shareText)}`;
            break;
        case 'linkedin':
            platformUrl = `https://www.linkedin.com/sharing/share-offsite/?url=${encodeURIComponent(shareUrl)}`;
            break;
    }
    
    if (platformUrl) {
        window.open(platformUrl, '_blank', 'width=600,height=400');
    }
}

// Utility functions
function showNotification(message, type = 'info') {
    // Remove existing notifications
    const existingNotification = document.querySelector('.notification');
    if (existingNotification) {
        existingNotification.remove();
    }
    
    const notification = document.createElement('div');
    notification.className = `notification notification-${type}`;
    notification.innerHTML = `
        <i class="fas fa-${getNotificationIcon(type)}"></i>
        <span>${message}</span>
        <button class="notification-close"><i class="fas fa-times"></i></button>
    `;
    
    document.body.appendChild(notification);
    
    // Auto remove after 5 seconds
    setTimeout(() => {
        if (notification.parentNode) {
            notification.remove();
        }
    }, 5000);
    
    // Add close button functionality
    const closeBtn = notification.querySelector('.notification-close');
    closeBtn.addEventListener('click', () => {
        notification.remove();
    });
    
    // Animate in
    setTimeout(() => {
        notification.classList.add('show');
    }, 10);
}

function getNotificationIcon(type) {
    switch (type) {
        case 'success': return 'check-circle';
        case 'warning': return 'exclamation-triangle';
        case 'error': return 'times-circle';
        default: return 'info-circle';
    }
}

function showError(message) {
    showNotification(message, 'error');
}

function debounce(func, wait) {
    let timeout;
    return function executedFunction(...args) {
        const later = () => {
            clearTimeout(timeout);
            func(...args);
        };
        clearTimeout(timeout);
        timeout = setTimeout(later, wait);
    };
}

// Add notification styles dynamically
const notificationCSS = `
    .notification {
        position: fixed;
        top: 20px;
        right: 20px;
        background: white;
        border-radius: 10px;
        padding: 15px 20px;
        box-shadow: 0 10px 30px rgba(0, 0, 0, 0.2);
        z-index: 10000;
        display: flex;
        align-items: center;
        gap: 10px;
        transform: translateX(400px);
        transition: transform 0.3s ease;
        max-width: 350px;
    }
    
    .notification.show {
        transform: translateX(0);
    }
    
    .notification-success {
        border-left: 4px solid #28a745;
        color: #155724;
    }
    
    .notification-warning {
        border-left: 4px solid #ffc107;
        color: #856404;
    }
    
    .notification-error {
        border-left: 4px solid #dc3545;
        color: #721c24;
    }
    
    .notification-info {
        border-left: 4px solid #007bff;
        color: #004085;
    }
    
    .notification-close {
        background: none;
        border: none;
        font-size: 14px;
        cursor: pointer;
        opacity: 0.6;
        transition: opacity 0.2s;
        margin-left: auto;
    }
    
    .notification-close:hover {
        opacity: 1;
    }
`;

const style = document.createElement('style');
style.textContent = notificationCSS;
document.head.appendChild(style);
