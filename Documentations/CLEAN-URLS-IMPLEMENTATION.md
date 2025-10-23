# Clean URLs Implementation Guide
**TechCompare Smartphone Accessories Project**

## Table of Contents
1. [Overview](#overview)
2. [How It Works](#how-it-works)
3. [Local Development Setup](#local-development-setup)
4. [Ngrok Setup](#ngrok-setup)
5. [Production Deployment](#production-deployment)
6. [Testing](#testing)
7. [Troubleshooting](#troubleshooting)
8. [Technical Details](#technical-details)

---

## Overview

**Feature**: Clean URLs without .php extensions
**Purpose**: Professional URL structure for better SEO and user experience
**Status**: ✅ Implemented and tested on localhost

### URL Format Examples

| Old Format (with .php) | New Clean Format |
|------------------------|------------------|
| `/index.php` | `/index` or `/` |
| `/products.php` | `/products` |
| `/contact.php` | `/contact` |
| `/products.php?id=123` | `/products?id=123` or `/product/123` |
| `/compare.php?products=1,2,3` | `/compare?products=1,2,3` |

### Benefits
- ✅ Better SEO rankings
- ✅ Professional appearance
- ✅ Easier to share and remember
- ✅ Automatic 301 redirects from old URLs
- ✅ Backward compatibility maintained

---

## How It Works

### 1. Server-Side URL Rewriting (.htaccess)

The Apache web server uses mod_rewrite to transform URLs:

```apache
# Step 1: Redirect old .php URLs to clean URLs (301 permanent)
RewriteCond %{THE_REQUEST} ^[A-Z]{3,9}\ /([^.]+)\.php
RewriteRule ^([^.]+)\.php$ /$1 [R=301,L]

# Step 2: Rewrite clean URLs back to .php files internally
RewriteCond %{REQUEST_FILENAME} !-f
RewriteCond %{REQUEST_FILENAME} !-d
RewriteRule ^([a-zA-Z0-9_-]+)$ $1.php [L,QSA]
```

**Flow Diagram:**
```
User visits: /contact
    ↓
Apache checks: contact file exists? NO
    ↓
Apache rewrites: /contact → /contact.php
    ↓
PHP executes: contact.php
    ↓
Browser shows: /contact
```

### 2. Internal Link Updates

All navigation links updated to use clean URLs:
```html
<!-- OLD -->
<a href="products.php">Products</a>

<!-- NEW -->
<a href="products">Products</a>
```

**Files Updated:**
- ✅ `index.php`
- ✅ `contact.php`
- ✅ `products.php`
- ✅ `compare.php`
- ✅ `about.php`
- ✅ `check-response.php`
- ✅ `js/main.js`
- ✅ `js/products.js`

---

## Local Development Setup

### Configuration File: `.htaccess`

**Current Settings (Localhost - Subfolder):**
```apache
RewriteBase /Smartphone-Accessories/
```

This tells Apache that your project is in a subfolder: `http://localhost/Smartphone-Accessories/`

### Verification Steps

1. **Check mod_rewrite is enabled:**
   ```powershell
   # In Laragon menu: Apache > Modules > rewrite_module (should be checked)
   # Or check httpd.conf:
   notepad C:\laragon\bin\apache\apache-2.4.62\conf\httpd.conf
   # Look for: LoadModule rewrite_module modules/mod_rewrite.so
   ```

2. **Test clean URLs:**
   ```powershell
   # Test in browser:
   http://localhost/Smartphone-Accessories/index
   http://localhost/Smartphone-Accessories/contact
   http://localhost/Smartphone-Accessories/products
   ```

3. **Test 301 redirects:**
   ```powershell
   # This should redirect to /contact:
   http://localhost/Smartphone-Accessories/contact.php
   ```

### Testing Results (Localhost)
```
✅ http://localhost/Smartphone-Accessories/index - Works! (200 OK)
✅ http://localhost/Smartphone-Accessories/contact - Works! (200 OK)
✅ http://localhost/Smartphone-Accessories/products - Works! (200 OK)
✅ http://localhost/Smartphone-Accessories/compare - Works! (200 OK)
✅ http://localhost/Smartphone-Accessories/about - Works! (200 OK)
✅ contact.php redirects to /contact (301)
```

---

## Ngrok Setup

### Why Ngrok?
Ngrok creates a public tunnel to your localhost, useful for:
- Testing on mobile devices
- Sharing with clients
- Testing webhooks and external APIs
- SSL/HTTPS testing

### Configuration for Ngrok

**Option 1: Keep Current .htaccess (Recommended)**
```apache
# Current .htaccess works with ngrok automatically
RewriteBase /Smartphone-Accessories/
```

Ngrok URL will be: `https://abc123.ngrok.io/Smartphone-Accessories/`

**Option 2: Dynamic RewriteBase (Advanced)**
```apache
# Automatically detect root vs subfolder
RewriteCond %{REQUEST_URI}::$1 ^(/.+)/(.*)::\2$
RewriteRule ^(.*) - [E=BASE:%1]
RewriteBase %{ENV:BASE}/
```

### Starting Ngrok

1. **Install ngrok:**
   ```powershell
   # Download from: https://ngrok.com/download
   # Or use Chocolatey:
   choco install ngrok
   ```

2. **Authenticate (one-time):**
   ```powershell
   ngrok config add-authtoken YOUR_AUTH_TOKEN
   ```

3. **Start tunnel:**
   ```powershell
   ngrok http 80 --host-header=localhost
   ```

4. **Test URLs:**
   ```
   https://abc123.ngrok.io/Smartphone-Accessories/index
   https://abc123.ngrok.io/Smartphone-Accessories/contact
   https://abc123.ngrok.io/Smartphone-Accessories/products
   ```

### Ngrok Testing Checklist

- [ ] Clean URLs load correctly
- [ ] .php URLs redirect to clean URLs (301)
- [ ] Navigation links work
- [ ] Images and CSS load properly
- [ ] JavaScript functions work
- [ ] Dark mode toggle works
- [ ] Forms submit correctly
- [ ] Database queries execute

### Troubleshooting Ngrok

**Issue: CSS/JS not loading**
```apache
# Add to .htaccess:
<IfModule mod_headers.c>
    Header set Access-Control-Allow-Origin "*"
</IfModule>
```

**Issue: Base URL wrong**
```php
// In config.php, detect ngrok:
if (strpos($_SERVER['HTTP_HOST'], 'ngrok.io') !== false) {
    define('BASE_URL', 'https://' . $_SERVER['HTTP_HOST'] . '/Smartphone-Accessories');
} else {
    define('BASE_URL', 'http://localhost/Smartphone-Accessories');
}
```

---

## Production Deployment

### Pre-Deployment Checklist

- [ ] Backup current .htaccess
- [ ] Backup database
- [ ] Test all URLs on localhost
- [ ] Update config.php with production domain
- [ ] Update database credentials
- [ ] Remove test/debug files
- [ ] Enable error logging (disable display_errors)

### Production .htaccess Configuration

**File: `.htaccess.production` (included in project)**

**Key Differences from Localhost:**

1. **RewriteBase:**
   ```apache
   # Localhost (subfolder):
   RewriteBase /Smartphone-Accessories/
   
   # Production (root):
   RewriteBase /
   ```

2. **Force HTTPS:**
   ```apache
   RewriteCond %{HTTPS} off
   RewriteRule ^(.*)$ https://%{HTTP_HOST}%{REQUEST_URI} [L,R=301]
   ```

3. **Remove www (optional):**
   ```apache
   RewriteCond %{HTTP_HOST} ^www\.(.*)$ [NC]
   RewriteRule ^(.*)$ https://%1%{REQUEST_URI} [R=301,L]
   ```

### Deployment Steps

1. **Upload files via FTP/cPanel:**
   ```
   - Upload all PHP files
   - Upload css/, js/, assets/ folders
   - Upload .htaccess.production as .htaccess
   - Set permissions: 644 for files, 755 for folders
   ```

2. **Update .htaccess for root deployment:**
   ```powershell
   # Before uploading, modify RewriteBase:
   RewriteBase /
   ```

3. **Test production URLs:**
   ```
   https://yourdomain.com/index
   https://yourdomain.com/contact
   https://yourdomain.com/products
   https://yourdomain.com/about
   ```

4. **Verify 301 redirects:**
   ```bash
   curl -I https://yourdomain.com/contact.php
   # Should return: HTTP/1.1 301 Moved Permanently
   ```

### Production Testing Checklist

- [ ] All pages load with HTTPS
- [ ] Clean URLs work (no .php)
- [ ] Old .php URLs redirect to clean URLs
- [ ] Navigation menu works
- [ ] Search functionality works
- [ ] Product pages load
- [ ] Compare feature works
- [ ] Contact form submits
- [ ] Admin panel accessible
- [ ] Database queries execute
- [ ] Images load from uploads/
- [ ] API endpoints respond
- [ ] Error pages display correctly
- [ ] Mobile responsive design works
- [ ] Dark/light mode toggle works

---

## Testing

### Manual Testing Script (PowerShell)

```powershell
# Test all main pages
$baseUrl = "http://localhost/Smartphone-Accessories"
$pages = @("index", "contact", "products", "compare", "about")

foreach ($page in $pages) {
    $url = "$baseUrl/$page"
    try {
        $response = Invoke-WebRequest -Uri $url -UseBasicParsing
        if ($response.StatusCode -eq 200) {
            Write-Host "✅ $url - Works! (200 OK)" -ForegroundColor Green
        }
    } catch {
        Write-Host "❌ $url - Failed! ($_)" -ForegroundColor Red
    }
}

# Test .php redirect
try {
    $response = Invoke-WebRequest -Uri "$baseUrl/contact.php" -MaximumRedirection 0 -ErrorAction SilentlyContinue
    if ($response.StatusCode -eq 301) {
        Write-Host "✅ contact.php redirects to clean URL (301)" -ForegroundColor Green
    }
} catch {
    Write-Host "❌ Redirect test failed!" -ForegroundColor Red
}
```

### Browser Testing

1. **Test clean URLs:**
   - Visit each page without .php
   - Check browser address bar shows clean URL
   - Verify page content loads correctly

2. **Test navigation:**
   - Click all menu links
   - Verify they go to clean URLs
   - Check no .php appears in address bar

3. **Test .php redirects:**
   - Type `contact.php` in address bar
   - Verify it redirects to `/contact`
   - Check browser shows 301 redirect in DevTools

4. **Test query parameters:**
   - Visit: `/products?id=123`
   - Visit: `/compare?products=1,2,3`
   - Verify parameters preserved

### Browser DevTools Testing

**Open DevTools (F12) → Network Tab:**

1. Clear cache and refresh
2. Check each request:
   - Clean URLs should show **200 OK**
   - .php URLs should show **301 Moved Permanently**
   - CSS/JS should show **200 OK** or **304 Not Modified**

3. Check Response Headers:
   ```
   Status: 200 OK
   Content-Type: text/html; charset=UTF-8
   Server: Apache
   ```

---

## Troubleshooting

### Common Issues

#### 1. 404 Not Found - Clean URLs don't work

**Cause:** mod_rewrite not enabled

**Solution:**
```powershell
# Laragon: Menu → Apache → Modules → Check rewrite_module
# Manual: Edit httpd.conf
LoadModule rewrite_module modules/mod_rewrite.so

# Also check:
AllowOverride All
# in <Directory> section
```

#### 2. 500 Internal Server Error

**Cause:** Syntax error in .htaccess

**Solution:**
```powershell
# Check Apache error log:
notepad C:\laragon\www\logs\apache_error.log

# Common issues:
# - Missing RewriteBase
# - Invalid regex pattern
# - Missing [L] flag
```

#### 3. CSS/JS not loading

**Cause:** Incorrect relative paths

**Solution:**
```html
<!-- Use absolute paths: -->
<link rel="stylesheet" href="/Smartphone-Accessories/css/style.css">
<script src="/Smartphone-Accessories/js/main.js"></script>

<!-- Or use BASE tag: -->
<base href="/Smartphone-Accessories/">
```

#### 4. Clean URLs work but .php URLs don't redirect

**Cause:** Redirect rule not matching

**Solution:**
```apache
# Debug: Check THE_REQUEST variable
RewriteCond %{THE_REQUEST} ^[A-Z]{3,9}\ /([^.]+)\.php
RewriteRule ^([^.]+)\.php$ /$1 [R=301,L]

# Alternative pattern:
RewriteCond %{REQUEST_FILENAME} !-d
RewriteRule ^(.+)\.php$ /$1 [R=301,L]
```

#### 5. Admin panel or API not working

**Cause:** Rewrite rules affecting admin/api folders

**Solution:**
```apache
# Skip rewriting for specific directories:
RewriteCond %{REQUEST_URI} !^/(admin|api|assets|uploads)/
```

#### 6. Query parameters lost

**Cause:** Missing [QSA] flag

**Solution:**
```apache
# Add QSA (Query String Append) flag:
RewriteRule ^([a-zA-Z0-9_-]+)$ $1.php [L,QSA]
```

### Debug Mode

**Enable detailed logging:**

```apache
# Add to .htaccess:
RewriteLog "C:/laragon/www/logs/rewrite.log"
RewriteLogLevel 3
```

**Check logs:**
```powershell
Get-Content C:\laragon\www\logs\rewrite.log -Tail 50
```

### Testing Tools

1. **Online Tools:**
   - https://htaccess.madewithlove.com/ (Test .htaccess online)
   - https://www.redirect-checker.org/ (Check 301 redirects)
   - https://www.webconfs.com/redirect-check.php (Redirect validator)

2. **Browser Extensions:**
   - Redirect Path (Chrome)
   - HTTP Header Live (Firefox)

3. **Command Line:**
   ```powershell
   # Test with curl:
   curl -I http://localhost/Smartphone-Accessories/contact.php
   # Should show: Location: /contact
   
   # Test clean URL:
   curl -I http://localhost/Smartphone-Accessories/contact
   # Should show: 200 OK
   ```

---

## Technical Details

### File Structure

```
Smartphone-Accessories/
├── .htaccess                    # Main config (localhost - subfolder)
├── .htaccess.production         # Production config (root deployment)
├── index.php                    # ✅ Updated with clean URLs
├── contact.php                  # ✅ Updated with clean URLs
├── products.php                 # ✅ Updated with clean URLs
├── compare.php                  # ✅ Updated with clean URLs
├── about.php                    # ✅ Updated with clean URLs
├── check-response.php           # ✅ Updated with clean URLs
├── js/
│   ├── main.js                  # ✅ Updated with clean URLs
│   └── products.js              # ✅ Updated with clean URLs
├── css/
│   └── style.css
├── admin/                       # ⏳ To be updated
│   ├── index.php
│   └── dashboard.php
└── api/                         # Protected from rewriting
    ├── get_products.php
    └── compare_products.php
```

### Rewrite Rules Explanation

```apache
# Rule 1: Redirect .php URLs to clean URLs
RewriteCond %{THE_REQUEST} ^[A-Z]{3,9}\ /([^.]+)\.php
# Matches: GET /contact.php HTTP/1.1
# Captures: contact (without .php)

RewriteRule ^([^.]+)\.php$ /$1 [R=301,L]
# Redirects: /contact.php → /contact
# Flags: R=301 (permanent redirect), L (last rule)

# Rule 2: Skip existing files and directories
RewriteCond %{REQUEST_FILENAME} !-f
# Don't rewrite if file exists (e.g., style.css)

RewriteCond %{REQUEST_FILENAME} !-d
# Don't rewrite if directory exists (e.g., /admin/)

# Rule 3: Rewrite clean URLs to .php
RewriteRule ^([a-zA-Z0-9_-]+)$ $1.php [L,QSA]
# Rewrites: /contact → contact.php
# Flags: L (last rule), QSA (preserve query string)
```

### Performance Considerations

**Impact on Server:**
- ✅ Minimal overhead (Apache handles natively)
- ✅ Rules cached after first request
- ✅ No PHP execution overhead
- ✅ Browser caching preserved

**SEO Benefits:**
- ✅ 301 redirects preserve PageRank
- ✅ Clean URLs improve click-through rate
- ✅ Better indexing by search engines
- ✅ Canonical URLs enforced

### Browser Compatibility

**Supported:**
- ✅ All modern browsers (Chrome, Firefox, Safari, Edge)
- ✅ Mobile browsers (iOS Safari, Chrome Mobile)
- ✅ Search engine bots (Google, Bing, etc.)

**Requirements:**
- ✅ JavaScript enabled (for navigation)
- ✅ Cookies enabled (for session management)
- ✅ No special browser configuration needed

---

## Maintenance

### Regular Checks

1. **Monthly:**
   - Test all clean URLs
   - Check Apache error logs
   - Verify 301 redirects working
   - Test on different browsers

2. **After Updates:**
   - Test new pages added
   - Update navigation links
   - Test old .php URLs redirect

3. **Performance:**
   - Monitor page load times
   - Check server response codes
   - Analyze Apache logs for 404s

### Backup Strategy

```powershell
# Backup .htaccess before changes:
Copy-Item .htaccess .htaccess.backup_$(Get-Date -Format 'yyyyMMdd')

# Test changes:
# Visit URLs and check functionality

# Rollback if needed:
Copy-Item .htaccess.backup_20240115 .htaccess
```

---

## Changelog

### Version 1.0 (January 2024)
- ✅ Initial implementation with mod_rewrite
- ✅ Clean URL support for all main pages
- ✅ 301 redirects from .php URLs
- ✅ Updated all internal links
- ✅ Updated JavaScript references
- ✅ Tested on localhost
- ⏳ Ngrok testing pending
- ⏳ Production deployment pending
- ⏳ Admin panel update pending

---

## Support

### Documentation Files
- `GIT-WORKFLOW-GUIDE.md` - Git usage guide
- `PHP-CONSOLIDATION-SUMMARY.md` - HTML to PHP migration
- `CLEAN-URLS-IMPLEMENTATION.md` - This file

### Resources
- Apache mod_rewrite: https://httpd.apache.org/docs/current/mod/mod_rewrite.html
- .htaccess Guide: https://httpd.apache.org/docs/current/howto/htaccess.html
- SEO Best Practices: https://developers.google.com/search/docs/crawling-indexing/url-structure

### Contact
For issues or questions, check:
1. Apache error logs
2. Browser DevTools console
3. This documentation
4. Stack Overflow: [apache] [mod-rewrite] [htaccess]

---

**Last Updated:** January 2024
**Status:** Production Ready (pending ngrok/production testing)
**Maintainer:** TechCompare Development Team
