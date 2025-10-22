# 🔧 Quick Implementation Guide

## Add Auto-Update to Your Pages

### Step 1: Convert HTML to PHP

Your HTML files need to be PHP to include the cache check script.

**Option A: Rename Files**
```bash
# Rename .html files to .php
mv index.html index.php
mv products.html products.php
mv compare.html compare.php
```

**Option B: Use .htaccess** (Keep .html extension)
```apache
# Add to .htaccess
<FilesMatch "\.(html)$">
    SetHandler application/x-httpd-php
</FilesMatch>
```

### Step 2: Add Cache Check Script

Add this **before closing `</body>` tag** in each page:

```html
    <!-- ... your page content ... -->
    
    <?php require_once 'includes/cache-version-check.php'; ?>
</body>
</html>
```

### Step 3: Add Cache Busters to Assets (Optional)

**Current (No Cache Busting)**:
```html
<link rel="stylesheet" href="css/style.css">
<script src="js/main.js"></script>
```

**Updated (With Cache Busting)**:
```html
<?php require_once 'includes/cache-manager.php'; ?>
<link rel="stylesheet" href="<?php echo asset('css/style.css'); ?>">
<script src="<?php echo asset('js/main.js'); ?>"></script>
```

**Result**: Assets load with version numbers
```html
<link rel="stylesheet" href="css/style.css?v=1729432800">
<script src="js/main.js?v=1729432800">
```

---

## Example: Update index.html

**Before (index.html)**:
```html
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>TechCompare</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
    <h1>Welcome to TechCompare</h1>
    
    <script src="js/main.js"></script>
</body>
</html>
```

**After (index.php or index.html with PHP enabled)**:
```html
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>TechCompare</title>
    <?php require_once 'includes/cache-manager.php'; ?>
    <link rel="stylesheet" href="<?php echo asset('css/style.css'); ?>">
</head>
<body>
    <h1>Welcome to TechCompare</h1>
    
    <script src="<?php echo asset('js/main.js'); ?>"></script>
    
    <?php require_once 'includes/cache-version-check.php'; ?>
</body>
</html>
```

---

## Pages to Update

### Priority 1 (Must Have)
- [ ] `index.html` → User landing page
- [ ] `products.html` → Main product listing
- [ ] `compare.html` → Comparison page

### Priority 2 (Recommended)
- [ ] `about.html` → About page
- [ ] `contact.html` → Contact page

### Priority 3 (Optional)
- [ ] `maintenance.php` → Maintenance page
- [ ] Other custom pages

---

## Testing Checklist

After adding cache check to a page:

1. **Open the page** in browser
2. **Open Console** (F12)
3. **Look for message**: `🔧 TechCompare Cache Manager initialized`
4. **Check localStorage**: 
   ```javascript
   localStorage.getItem('techcompare_site_version')
   ```
5. **Test update detection**:
   ```javascript
   // Simulate old version
   localStorage.setItem('techcompare_site_version', '123');
   
   // Force check (should trigger update)
   TechCompareCache.checkVersion();
   ```

---

## Quick .htaccess Method

If you want to keep `.html` extensions:

**Add to .htaccess**:
```apache
# Enable PHP in HTML files
<FilesMatch "\.(html)$">
    SetHandler application/x-httpd-php
</FilesMatch>
```

Then you can use PHP code in `.html` files without renaming!

---

## Verification

After implementation, verify:

```bash
# Test version API
curl http://localhost/Smartphone-Accessories/api/check_version

# Should return:
{
    "success": true,
    "version": "1729432800",
    ...
}
```

**Browser Test**:
1. Visit page
2. Open DevTools → Application → Local Storage
3. Find `techcompare_site_version` key
4. Should match version from API

---

## Troubleshooting

### PHP Code Shows as Text

**Problem**: PHP code visible in browser  
**Cause**: Server not processing PHP in HTML files  
**Fix**: Rename file to `.php` or add .htaccess rule

### "File not found" Error

**Problem**: `require_once` fails  
**Cause**: Wrong path  
**Fix**: 
```php
// For root pages (index.html, products.html)
require_once 'includes/cache-version-check.php';

// For subdirectory pages
require_once '../includes/cache-version-check.php';
```

### Cache Not Clearing

**Problem**: Version updates but cache remains  
**Cause**: Service Workers, browser cache  
**Fix**: Hard refresh (Ctrl+Shift+R) or test in incognito mode

---

## Need Help?

See full documentation: `AUTO-CACHE-UPDATE-SYSTEM.md`

---

**Status**: Ready to implement 🚀
