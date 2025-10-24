/**
 * Dark Mode Toggle Functionality for Admin Dashboard
 * Persists user preference in localStorage
 */

(function() {
    'use strict';

    // Initialize dark mode from localStorage or system preference
    function initDarkMode() {
        // Check if user has previously set a preference
        const savedTheme = localStorage.getItem('adminTheme');
        
        if (savedTheme) {
            setTheme(savedTheme);
        } else {
            // Check system preference
            const prefersDark = window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches;
            setTheme(prefersDark ? 'dark' : 'light');
        }
    }

    // Set theme
    function setTheme(theme) {
        document.documentElement.setAttribute('data-theme', theme);
        localStorage.setItem('adminTheme', theme);
        updateToggleButton(theme);
    }

    // Toggle between themes
    function toggleTheme() {
        const currentTheme = document.documentElement.getAttribute('data-theme') || 'light';
        const newTheme = currentTheme === 'light' ? 'dark' : 'light';
        setTheme(newTheme);
        
        // Add animation effect
        document.body.style.transition = 'background-color 0.3s ease';
    }

    // Update toggle button appearance
    function updateToggleButton(theme) {
        const toggleBtn = document.getElementById('themeToggle');
        if (!toggleBtn) return;

        const iconElement = toggleBtn.querySelector('.theme-toggle-icon');
        const textElement = toggleBtn.querySelector('.theme-toggle-text');
        
        if (theme === 'dark') {
            iconElement.className = 'fas fa-sun theme-toggle-icon';
            if (textElement) textElement.textContent = 'Light Mode';
        } else {
            iconElement.className = 'fas fa-moon theme-toggle-icon';
            if (textElement) textElement.textContent = 'Dark Mode';
        }
    }

    // Create and inject toggle button
    function createToggleButton() {
        // Check if button already exists
        if (document.getElementById('themeToggle')) return;

        const toggleBtn = document.createElement('button');
        toggleBtn.id = 'themeToggle';
        toggleBtn.className = 'theme-toggle';
        toggleBtn.setAttribute('aria-label', 'Toggle Dark Mode');
        toggleBtn.setAttribute('title', 'Toggle Dark/Light Mode (Ctrl+Shift+D)');
        
        const currentTheme = document.documentElement.getAttribute('data-theme') || 'light';
        const icon = currentTheme === 'dark' ? 'sun' : 'moon';
        const text = currentTheme === 'dark' ? 'Light Mode' : 'Dark Mode';
        
        toggleBtn.innerHTML = `
            <i class="fas fa-${icon} theme-toggle-icon"></i>
            <span class="theme-toggle-text">${text}</span>
        `;
        
        toggleBtn.addEventListener('click', toggleTheme);
        
        // Insert button directly into body (CSS will center it)
        document.body.appendChild(toggleBtn);
    }

    // Listen for system theme changes
    function watchSystemTheme() {
        if (!window.matchMedia) return;
        
        const mediaQuery = window.matchMedia('(prefers-color-scheme: dark)');
        mediaQuery.addListener((e) => {
            // Only auto-switch if user hasn't set a preference
            if (!localStorage.getItem('adminTheme')) {
                setTheme(e.matches ? 'dark' : 'light');
            }
        });
    }

    // Keyboard shortcut (Ctrl+Shift+D or Cmd+Shift+D)
    function setupKeyboardShortcut() {
        document.addEventListener('keydown', (e) => {
            if ((e.ctrlKey || e.metaKey) && e.shiftKey && e.key === 'D') {
                e.preventDefault();
                toggleTheme();
                showToast('Theme switched!');
            }
        });
    }

    // Show toast notification
    function showToast(message) {
        const existingToast = document.querySelector('.theme-toast');
        if (existingToast) existingToast.remove();

        const toast = document.createElement('div');
        toast.className = 'theme-toast';
        toast.textContent = message;
        toast.style.cssText = `
            position: fixed;
            bottom: 30px;
            left: 50%;
            transform: translateX(-50%);
            background: var(--card-bg);
            color: var(--text-dark);
            padding: 12px 24px;
            border-radius: 8px;
            box-shadow: var(--shadow-lg);
            z-index: 10001;
            animation: slideUp 0.3s ease, fadeOut 0.3s ease 2.7s;
            border: 1px solid var(--border-color);
        `;
        
        document.body.appendChild(toast);
        setTimeout(() => toast.remove(), 3000);
    }

    // Add toast animation styles
    function addToastStyles() {
        if (document.getElementById('themeToastStyles')) return;

        const style = document.createElement('style');
        style.id = 'themeToastStyles';
        style.textContent = `
            @keyframes slideUp {
                from {
                    opacity: 0;
                    transform: translate(-50%, 20px);
                }
                to {
                    opacity: 1;
                    transform: translate(-50%, 0);
                }
            }
            @keyframes fadeOut {
                from { opacity: 1; }
                to { opacity: 0; }
            }
        `;
        document.head.appendChild(style);
    }

    // Initialize everything when DOM is ready
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', () => {
            initDarkMode();
            createToggleButton();
            watchSystemTheme();
            setupKeyboardShortcut();
            addToastStyles();
        });
    } else {
        initDarkMode();
        createToggleButton();
        watchSystemTheme();
        setupKeyboardShortcut();
        addToastStyles();
    }

    // Export for external use
    window.adminTheme = {
        toggle: toggleTheme,
        set: setTheme,
        get: () => document.documentElement.getAttribute('data-theme') || 'light'
    };

})();
