/**
 * Theme Manager - Dark Mode / Light Mode
 * Handles theme switching, persistence, and initialization
 */

(function() {
    'use strict';

    class ThemeManager {
        constructor() {
            this.theme = this.getStoredTheme() || this.getSystemTheme();
            this.init();
        }

        /**
         * Initialize theme manager
         */
        init() {
            // Apply theme immediately to prevent flash
            this.applyTheme(this.theme, false);
            
            // Wait for DOM to be ready
            if (document.readyState === 'loading') {
                document.addEventListener('DOMContentLoaded', () => this.setup());
            } else {
                this.setup();
            }
        }

        /**
         * Setup theme toggle button and listeners
         */
        setup() {
            // Create theme toggle button if it doesn't exist
            this.createToggleButton();
            
            // Listen for system theme changes
            this.watchSystemTheme();
            
            // Listen for storage changes (sync across tabs)
            this.watchStorageChanges();
            
            console.log('✅ Theme Manager initialized with theme:', this.theme);
        }

        /**
         * Get stored theme from localStorage
         */
        getStoredTheme() {
            try {
                return localStorage.getItem('theme');
            } catch (e) {
                console.warn('localStorage not available:', e);
                return null;
            }
        }

        /**
         * Get system theme preference
         */
        getSystemTheme() {
            if (window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches) {
                return 'dark';
            }
            return 'light';
        }

        /**
         * Store theme preference
         */
        storeTheme(theme) {
            try {
                localStorage.setItem('theme', theme);
            } catch (e) {
                console.warn('Could not store theme preference:', e);
            }
        }

        /**
         * Apply theme to document
         */
        applyTheme(theme, animate = true) {
            const html = document.documentElement;
            
            if (animate) {
                html.classList.add('theme-transitioning');
                setTimeout(() => html.classList.remove('theme-transitioning'), 300);
            }
            
            html.setAttribute('data-theme', theme);
            this.theme = theme;
            this.storeTheme(theme);
            
            // Update toggle button if it exists
            this.updateToggleButton();
            
            // Emit custom event
            window.dispatchEvent(new CustomEvent('themechange', { 
                detail: { theme } 
            }));
            
            console.log('🎨 Theme applied:', theme);
        }

        /**
         * Toggle between light and dark theme
         */
        toggleTheme() {
            const newTheme = this.theme === 'dark' ? 'light' : 'dark';
            this.applyTheme(newTheme);
        }

        /**
         * Create theme toggle button
         */
        createToggleButton() {
            // Check if button already exists
            if (document.querySelector('.theme-toggle-btn')) {
                return;
            }

            // Find nav-actions container
            const navActions = document.querySelector('.nav-actions');
            if (!navActions) {
                console.warn('Nav actions container not found');
                return;
            }

            // Create toggle button
            const toggleBtn = document.createElement('button');
            toggleBtn.className = 'theme-toggle-btn';
            toggleBtn.setAttribute('aria-label', 'Toggle theme');
            toggleBtn.setAttribute('title', 'Toggle dark/light mode');
            toggleBtn.innerHTML = `
                <i class="fas fa-sun theme-toggle-icon sun"></i>
                <i class="fas fa-moon theme-toggle-icon moon"></i>
                <div class="theme-toggle-slider">
                    <i class="fas fa-${this.theme === 'dark' ? 'moon' : 'sun'}"></i>
                </div>
            `;

            // Add click handler
            toggleBtn.addEventListener('click', (e) => {
                e.preventDefault();
                this.toggleTheme();
                
                // Add pulse animation
                toggleBtn.style.transform = 'scale(1.1)';
                setTimeout(() => {
                    toggleBtn.style.transform = 'scale(1)';
                }, 200);
            });

            // Insert before first child of nav-actions
            navActions.insertBefore(toggleBtn, navActions.firstChild);
            
            console.log('✅ Theme toggle button created');
        }

        /**
         * Update toggle button state
         */
        updateToggleButton() {
            const toggleBtn = document.querySelector('.theme-toggle-btn');
            if (!toggleBtn) return;

            const sliderIcon = toggleBtn.querySelector('.theme-toggle-slider i');
            if (sliderIcon) {
                sliderIcon.className = `fas fa-${this.theme === 'dark' ? 'moon' : 'sun'}`;
            }
        }

        /**
         * Watch for system theme changes
         */
        watchSystemTheme() {
            if (!window.matchMedia) return;

            const darkModeQuery = window.matchMedia('(prefers-color-scheme: dark)');
            
            // Modern browsers
            if (darkModeQuery.addEventListener) {
                darkModeQuery.addEventListener('change', (e) => {
                    // Only auto-switch if user hasn't manually set a preference
                    if (!this.getStoredTheme()) {
                        this.applyTheme(e.matches ? 'dark' : 'light');
                    }
                });
            } 
            // Older browsers
            else if (darkModeQuery.addListener) {
                darkModeQuery.addListener((e) => {
                    if (!this.getStoredTheme()) {
                        this.applyTheme(e.matches ? 'dark' : 'light');
                    }
                });
            }
        }

        /**
         * Watch for storage changes (sync across tabs)
         */
        watchStorageChanges() {
            window.addEventListener('storage', (e) => {
                if (e.key === 'theme' && e.newValue) {
                    this.applyTheme(e.newValue, false);
                }
            });
        }

        /**
         * Get current theme
         */
        getTheme() {
            return this.theme;
        }

        /**
         * Set specific theme
         */
        setTheme(theme) {
            if (theme === 'dark' || theme === 'light') {
                this.applyTheme(theme);
            }
        }
    }

    // Create global theme manager instance
    window.themeManager = new ThemeManager();

    // Export for module usage
    if (typeof module !== 'undefined' && module.exports) {
        module.exports = ThemeManager;
    }

})();

// Additional utility functions

/**
 * Get current theme
 */
function getCurrentTheme() {
    return window.themeManager ? window.themeManager.getTheme() : 'light';
}

/**
 * Toggle theme (for inline onclick handlers)
 */
function toggleTheme() {
    if (window.themeManager) {
        window.themeManager.toggleTheme();
    }
}

/**
 * Set specific theme
 */
function setTheme(theme) {
    if (window.themeManager) {
        window.themeManager.setTheme(theme);
    }
}

// Listen for theme changes and log
window.addEventListener('themechange', (e) => {
    console.log('Theme changed to:', e.detail.theme);
});
