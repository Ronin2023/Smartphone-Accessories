// admin.js - Admin panel functionality

document.addEventListener('DOMContentLoaded', function() {
    initializeAdmin();
});

function initializeAdmin() {
    // Initialize sidebar toggle
    initializeSidebar();
    
    // Initialize tooltips and interactions
    initializeInteractions();
    
    // Initialize data tables if present
    initializeDataTables();
    
    // Initialize form handling
    initializeForms();
}

function initializeSidebar() {
    const sidebar = document.querySelector('.admin-sidebar');
    const main = document.querySelector('.admin-main');
    const toggleBtn = document.querySelector('.sidebar-toggle');
    
    if (toggleBtn) {
        toggleBtn.addEventListener('click', function() {
            toggleSidebar();
        });
    }
    
    // Handle mobile sidebar
    if (window.innerWidth <= 768) {
        sidebar?.classList.add('collapsed');
        if (main) main.style.marginLeft = '0';
    }
    
    // Handle window resize
    window.addEventListener('resize', function() {
        if (window.innerWidth <= 768) {
            sidebar?.classList.add('collapsed');
            if (main) main.style.marginLeft = '0';
        } else if (window.innerWidth > 768 && sidebar?.classList.contains('collapsed')) {
            sidebar.classList.remove('collapsed');
            if (main) main.style.marginLeft = '260px';
        }
    });
}

function toggleSidebar() {
    const sidebar = document.querySelector('.admin-sidebar');
    const main = document.querySelector('.admin-main');
    
    if (!sidebar || !main) return;
    
    sidebar.classList.toggle('collapsed');
    
    if (sidebar.classList.contains('collapsed')) {
        main.style.marginLeft = '70px';
    } else {
        main.style.marginLeft = '260px';
    }
}

function initializeInteractions() {
    // Add loading states to buttons
    document.querySelectorAll('.btn').forEach(btn => {
        if (btn.type === 'submit') {
            btn.addEventListener('click', function() {
                const form = this.closest('form');
                if (form && form.checkValidity()) {
                    addLoadingState(this);
                }
            });
        }
    });
    
    // Add confirmation dialogs for delete actions
    document.querySelectorAll('[data-confirm]').forEach(element => {
        element.addEventListener('click', function(e) {
            const message = this.dataset.confirm || 'Are you sure?';
            if (!confirm(message)) {
                e.preventDefault();
                return false;
            }
        });
    });
    
    // Auto-hide alerts
    setTimeout(() => {
        document.querySelectorAll('.alert').forEach(alert => {
            fadeOut(alert);
        });
    }, 5000);
}

function initializeDataTables() {
    // Add sorting to tables
    document.querySelectorAll('.data-table').forEach(table => {
        addTableSorting(table);
    });
    
    // Add search functionality
    document.querySelectorAll('.table-search').forEach(search => {
        search.addEventListener('input', function() {
            filterTable(this.value, this.dataset.target);
        });
    });
}

function initializeForms() {
    // Image preview functionality
    document.querySelectorAll('input[type="file"]').forEach(input => {
        if (input.accept && input.accept.includes('image')) {
            input.addEventListener('change', function() {
                previewImage(this);
            });
        }
    });
    
    // Form validation
    document.querySelectorAll('form').forEach(form => {
        form.addEventListener('submit', function(e) {
            if (!validateForm(this)) {
                e.preventDefault();
                return false;
            }
        });
    });
    
    // Auto-save functionality
    document.querySelectorAll('[data-autosave]').forEach(element => {
        element.addEventListener('change', function() {
            autoSave(this);
        });
    });
}

function addLoadingState(button) {
    const originalText = button.innerHTML;
    button.disabled = true;
    button.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Loading...';
    
    // Remove loading state after 10 seconds (fallback)
    setTimeout(() => {
        button.disabled = false;
        button.innerHTML = originalText;
    }, 10000);
}

function fadeOut(element) {
    element.style.transition = 'opacity 0.5s';
    element.style.opacity = '0';
    setTimeout(() => {
        element.style.display = 'none';
    }, 500);
}

function addTableSorting(table) {
    const headers = table.querySelectorAll('th[data-sort]');
    
    headers.forEach(header => {
        header.style.cursor = 'pointer';
        header.innerHTML += ' <i class="fas fa-sort"></i>';
        
        header.addEventListener('click', function() {
            const column = this.dataset.sort;
            const currentOrder = this.dataset.order || 'asc';
            const newOrder = currentOrder === 'asc' ? 'desc' : 'asc';
            
            // Update header icons
            headers.forEach(h => {
                const icon = h.querySelector('i');
                if (icon) {
                    icon.className = 'fas fa-sort';
                }
            });
            
            const icon = this.querySelector('i');
            if (icon) {
                icon.className = newOrder === 'asc' ? 'fas fa-sort-up' : 'fas fa-sort-down';
            }
            
            this.dataset.order = newOrder;
            sortTable(table, column, newOrder);
        });
    });
}

function sortTable(table, column, order) {
    const tbody = table.querySelector('tbody');
    const rows = Array.from(tbody.querySelectorAll('tr'));
    const columnIndex = table.querySelector(`th[data-sort="${column}"]`).cellIndex;
    
    rows.sort((a, b) => {
        const aValue = a.cells[columnIndex].textContent.trim();
        const bValue = b.cells[columnIndex].textContent.trim();
        
        // Try to parse as numbers
        const aNum = parseFloat(aValue);
        const bNum = parseFloat(bValue);
        
        let comparison;
        if (!isNaN(aNum) && !isNaN(bNum)) {
            comparison = aNum - bNum;
        } else {
            comparison = aValue.localeCompare(bValue);
        }
        
        return order === 'asc' ? comparison : -comparison;
    });
    
    // Re-append rows in new order
    rows.forEach(row => tbody.appendChild(row));
}

function filterTable(searchTerm, tableSelector) {
    const table = document.querySelector(tableSelector);
    if (!table) return;
    
    const rows = table.querySelectorAll('tbody tr');
    const term = searchTerm.toLowerCase();
    
    rows.forEach(row => {
        const text = row.textContent.toLowerCase();
        if (text.includes(term)) {
            row.style.display = '';
        } else {
            row.style.display = 'none';
        }
    });
}

function previewImage(input) {
    const file = input.files[0];
    if (!file) return;
    
    const reader = new FileReader();
    reader.onload = function(e) {
        let preview = document.getElementById(input.id + '-preview');
        
        if (!preview) {
            preview = document.createElement('img');
            preview.id = input.id + '-preview';
            preview.style.maxWidth = '200px';
            preview.style.maxHeight = '200px';
            preview.style.marginTop = '10px';
            preview.style.borderRadius = '8px';
            input.parentNode.appendChild(preview);
        }
        
        preview.src = e.target.result;
    };
    
    reader.readAsDataURL(file);
}

function validateForm(form) {
    let isValid = true;
    const errors = [];
    
    // Check required fields
    form.querySelectorAll('[required]').forEach(field => {
        if (!field.value.trim()) {
            isValid = false;
            field.classList.add('error');
            errors.push(`${field.name || field.id} is required`);
        } else {
            field.classList.remove('error');
        }
    });
    
    // Check email fields
    form.querySelectorAll('input[type="email"]').forEach(field => {
        if (field.value && !isValidEmail(field.value)) {
            isValid = false;
            field.classList.add('error');
            errors.push(`${field.name || field.id} must be a valid email`);
        }
    });
    
    // Check file size limits
    form.querySelectorAll('input[type="file"]').forEach(field => {
        if (field.files[0] && field.files[0].size > 5 * 1024 * 1024) { // 5MB
            isValid = false;
            field.classList.add('error');
            errors.push(`${field.name || field.id} must be less than 5MB`);
        }
    });
    
    // Show errors
    if (!isValid) {
        showNotification('Please correct the following errors:\n' + errors.join('\n'), 'error');
    }
    
    return isValid;
}

function isValidEmail(email) {
    const re = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    return re.test(email);
}

function autoSave(element) {
    const form = element.closest('form');
    if (!form) return;
    
    const formData = new FormData(form);
    formData.append('auto_save', '1');
    
    fetch(form.action || window.location.href, {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showNotification('Auto-saved', 'success', 2000);
        }
    })
    .catch(error => {
        console.error('Auto-save failed:', error);
    });
}

function showNotification(message, type = 'info', duration = 5000) {
    const notification = document.createElement('div');
    notification.className = `notification notification-${type}`;
    notification.innerHTML = `
        <div class="notification-content">
            <i class="fas fa-${getNotificationIcon(type)}"></i>
            <span>${message}</span>
        </div>
        <button class="notification-close" onclick="this.parentElement.remove()">
            <i class="fas fa-times"></i>
        </button>
    `;
    
    // Add styles
    notification.style.cssText = `
        position: fixed;
        top: 20px;
        right: 20px;
        background: ${getNotificationColor(type)};
        color: white;
        padding: 1rem;
        border-radius: 8px;
        box-shadow: 0 4px 12px rgba(0,0,0,0.15);
        z-index: 10000;
        display: flex;
        align-items: center;
        gap: 1rem;
        max-width: 400px;
        animation: slideIn 0.3s ease;
    `;
    
    document.body.appendChild(notification);
    
    if (duration > 0) {
        setTimeout(() => {
            notification.remove();
        }, duration);
    }
}

function getNotificationIcon(type) {
    const icons = {
        'success': 'check-circle',
        'error': 'exclamation-triangle',
        'warning': 'exclamation-circle',
        'info': 'info-circle'
    };
    return icons[type] || 'info-circle';
}

function getNotificationColor(type) {
    const colors = {
        'success': '#28a745',
        'error': '#dc3545',
        'warning': '#ffc107',
        'info': '#17a2b8'
    };
    return colors[type] || '#17a2b8';
}

// Utility functions
function formatBytes(bytes, decimals = 2) {
    if (bytes === 0) return '0 Bytes';
    
    const k = 1024;
    const dm = decimals < 0 ? 0 : decimals;
    const sizes = ['Bytes', 'KB', 'MB', 'GB', 'TB'];
    
    const i = Math.floor(Math.log(bytes) / Math.log(k));
    
    return parseFloat((bytes / Math.pow(k, i)).toFixed(dm)) + ' ' + sizes[i];
}

function debounce(func, wait, immediate) {
    let timeout;
    return function executedFunction(...args) {
        const later = () => {
            timeout = null;
            if (!immediate) func(...args);
        };
        const callNow = immediate && !timeout;
        clearTimeout(timeout);
        timeout = setTimeout(later, wait);
        if (callNow) func(...args);
    };
}

// Export functions for global use
window.toggleSidebar = toggleSidebar;
window.showNotification = showNotification;
window.addLoadingState = addLoadingState;
