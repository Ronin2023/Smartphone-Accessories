# Dark Mode / Light Mode Implementation Guide

## ✨ Features Implemented

### 🎨 Theme System
- **Automatic Theme Detection**: Detects user's system preference (dark/light mode)
- **Manual Toggle**: Toggle button in the navigation bar for instant switching
- **Persistent Storage**: Theme preference saved in localStorage and syncs across tabs
- **Smooth Transitions**: Beautiful 0.3s transitions between themes
- **Modern Design**: Carefully crafted color schemes for both light and dark modes

### 🎯 Key Components

#### 1. **CSS Variables** (`css/theme.css`)
- Uses CSS custom properties for easy theming
- Separate color schemes for light and dark modes
- Covers all UI elements: backgrounds, text, borders, shadows, inputs, cards, etc.

#### 2. **Theme Manager** (`js/theme.js`)
- JavaScript class that handles all theme logic
- Automatic initialization on page load
- Creates and manages the toggle button
- Watches for system theme changes
- Syncs theme across browser tabs

#### 3. **Toggle Button**
- Positioned in the navigation bar (`.nav-actions`)
- Animated slider with sun/moon icons
- Smooth transitions and hover effects
- Accessible with ARIA labels

## 🚀 Usage

### For Users
1. **Look for the theme toggle button** in the top navigation bar
2. **Click the button** to switch between light and dark modes
3. **Your preference is saved** and will be remembered on your next visit

### For Developers

#### Adding Theme to New Pages
Add these two lines to your HTML:

**In `<head>` section:**
```html
<link rel="stylesheet" href="css/theme.css">
```

**Before closing `</body>` tag (before other scripts):**
```html
<script src="js/theme.js"></script>
```

#### Using Theme Variables in CSS
```css
.my-element {
    background-color: var(--bg-primary);
    color: var(--text-primary);
    border: 1px solid var(--border-color);
}
```

#### JavaScript API
```javascript
// Get current theme
const theme = getCurrentTheme(); // Returns 'light' or 'dark'

// Toggle theme programmatically
toggleTheme();

// Set specific theme
setTheme('dark');  // or 'light'

// Listen for theme changes
window.addEventListener('themechange', (e) => {
    console.log('Theme changed to:', e.detail.theme);
});
```

## 🎨 Color Variables

### Light Mode
- **Primary Background**: `#ffffff` (Pure white)
- **Secondary Background**: `#f8f9fa` (Light gray)
- **Primary Text**: `#212529` (Dark gray)
- **Accent**: `#667eea` → `#764ba2` (Purple gradient)

### Dark Mode
- **Primary Background**: `#1a1a2e` (Deep blue-gray)
- **Secondary Background**: `#16213e` (Navy blue)
- **Primary Text**: `#e4e4e7` (Light gray)
- **Accent**: `#818cf8` → `#a78bfa` (Light purple gradient)

## 📋 Files Modified

### Main Pages (HTML)
- ✅ `index.html`
- ✅ `contact.html`
- ✅ `products.html`
- ✅ `compare.html`
- ✅ `about.html`

### PHP Pages
- ✅ `index.php`
- ✅ `contact.php`
- ✅ `products.php`
- ✅ `compare.php`

### New Files Created
- ✅ `css/theme.css` - Theme styles and variables
- ✅ `js/theme.js` - Theme manager JavaScript

## 🎯 Benefits

### User Experience
- **Reduced Eye Strain**: Dark mode is easier on the eyes in low-light conditions
- **Battery Saving**: Dark mode saves battery on OLED/AMOLED screens
- **Personalization**: Users can choose their preferred theme
- **Accessibility**: Better contrast options for users with visual preferences

### Technical Benefits
- **Modern Approach**: Uses CSS custom properties (CSS variables)
- **Performance**: Instant theme switching with hardware-accelerated transitions
- **Maintainability**: Centralized theme configuration
- **Extensibility**: Easy to add new color variations or themes

## 🔧 Customization

### Changing Colors
Edit `css/theme.css` and modify the CSS variables:

```css
:root {
    /* Light Mode - Change these values */
    --bg-primary: #ffffff;
    --text-primary: #212529;
    /* ...more variables */
}

[data-theme="dark"] {
    /* Dark Mode - Change these values */
    --bg-primary: #1a1a2e;
    --text-primary: #e4e4e7;
    /* ...more variables */
}
```

### Adjusting Transition Speed
In `css/theme.css`, find:
```css
* {
    transition: background-color 0.3s ease, 
                color 0.3s ease, 
                border-color 0.3s ease,
                box-shadow 0.3s ease;
}
```
Change `0.3s` to your preferred duration (e.g., `0.5s` for slower, `0.15s` for faster).

### Adding Theme to Custom Elements
Simply use the CSS variables in your custom styles:
```css
.custom-component {
    background: var(--card-bg);
    color: var(--text-primary);
    border: 1px solid var(--border-color);
    box-shadow: var(--shadow-md);
}
```

## 📱 Responsive Design
The theme toggle button is fully responsive:
- **Desktop**: Full-size button with icons
- **Mobile**: Slightly smaller button for better mobile UX
- **Tablet**: Adapts to screen size automatically

## ♿ Accessibility
- **Keyboard Navigation**: Toggle button is keyboard accessible
- **Screen Readers**: ARIA labels for better screen reader support
- **Reduced Motion**: Respects `prefers-reduced-motion` for users who prefer less animation
- **High Contrast**: Respects `prefers-contrast` for users who need higher contrast

## 🐛 Troubleshooting

### Theme Not Switching
1. Check browser console for JavaScript errors
2. Verify `theme.js` is loaded before other scripts
3. Clear browser cache and localStorage

### Toggle Button Not Appearing
1. Ensure `.nav-actions` container exists in your HTML
2. Check that `theme.js` is loaded
3. Verify no JavaScript errors in console

### Colors Not Changing
1. Verify `theme.css` is loaded
2. Check that elements use CSS variables (not hardcoded colors)
3. Inspect element to see if theme attribute is set on `<html>`

### Theme Not Persisting
1. Check if localStorage is enabled in browser
2. Verify no browser extensions blocking localStorage
3. Check browser console for storage errors

## 🔄 Future Enhancements

Possible additions:
- Additional theme options (e.g., "Blue Theme", "High Contrast")
- Auto-switch based on time of day
- Theme preview before switching
- Custom color picker for user-defined themes
- Theme sharing functionality

## 📞 Support

If you encounter any issues with the theme system:
1. Check the browser console for errors
2. Verify all files are properly linked
3. Test in a different browser
4. Clear cache and try again

## 🎉 Conclusion

The dark mode / light mode feature is now fully implemented across all pages of the TechCompare website. Users can easily switch between themes, and their preference will be saved for future visits. The implementation is modern, performant, and accessible.

**Enjoy your new theme system! 🌓**
