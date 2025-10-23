# 📁 Project File Organization Guide

## Directory Structure

```
Smartphone-Accessories/
├── Documentations/          ← All .md documentation files
│   ├── DARK-MODE-*.md
│   ├── SPECIAL-ACCESS-*.md
│   ├── MAINTENANCE-*.md
│   └── Other documentation...
│
├── test/                    ← All test/demo/debug files
│   ├── HTML test files
│   │   ├── dark-mode-demo.html
│   │   ├── debug-*.html
│   │   ├── test-*.html
│   │   └── connection-status.html
│   │
│   └── PHP test files
│       ├── test-*.php
│       ├── debug-*.php
│       └── check-*.php
│
├── css/                     ← Stylesheets
├── js/                      ← JavaScript files
├── includes/                ← PHP includes
├── api/                     ← API endpoints
├── admin/                   ← Admin panel
├── assets/                  ← Images, fonts, icons
└── Main application files   ← HTML/PHP pages
    ├── index.html/php
    ├── contact.html/php
    ├── products.html/php
    └── ...
```

## Naming Conventions

### Documentation Files (→ Documentations/)
- **Pattern**: `FEATURE-NAME-TYPE.md`
- **Examples**:
  - `DARK-MODE-IMPLEMENTATION.md`
  - `SPECIAL-ACCESS-QUICK-REFERENCE.md`
  - `MAINTENANCE-GUIDE.md`
- **Location**: Always in `Documentations/` folder

### Test/Demo Files (→ test/)
- **Patterns**:
  - `test-*.html` or `test-*.php`
  - `debug-*.html` or `debug-*.php`
  - `*-demo.html`
  - `*-test.html`
  - `check-*.php`
- **Examples**:
  - `dark-mode-demo.html`
  - `test-api.php`
  - `debug-contact-api.html`
  - `connection-status.html`
- **Location**: Always in `test/` folder

### Production Files (→ Root)
- **Main pages**: `index.html`, `contact.html`, etc.
- **PHP pages**: `index.php`, `contact.php`, etc.
- **Config files**: `.htaccess`, `robots.txt`, `sitemap.xml`

## Rules for New Files

### Creating Documentation
```bash
# Always create .md files in Documentations/
New-Item "Documentations/NEW-FEATURE-GUIDE.md"
```

### Creating Test/Demo Files
```bash
# Always create test files in test/
New-Item "test/test-new-feature.html"
New-Item "test/debug-feature.php"
```

### Path References in Test Files
When creating files in `test/` folder, use relative paths:
```html
<!-- CSS -->
<link rel="stylesheet" href="../css/style.css">
<link rel="stylesheet" href="../css/theme.css">

<!-- JavaScript -->
<script src="../js/theme.js"></script>
<script src="../js/main.js"></script>

<!-- Links -->
<a href="../index.html">Home</a>
<a href="../contact.html">Contact</a>
```

## Quick Commands

### Move Documentation Files
```powershell
Move-Item -Path "*.md" -Destination "Documentations\" -Force
```

### Move Test Files
```powershell
Move-Item -Path "test-*.html" -Destination "test\" -Force
Move-Item -Path "debug-*.html" -Destination "test\" -Force
Move-Item -Path "*-demo.html" -Destination "test\" -Force
```

### Find Misplaced Files
```powershell
# Find .md files in root
Get-ChildItem -Path "." -Filter "*.md" -File

# Find test files in root
Get-ChildItem -Path "." -Filter "test-*.html" -File
Get-ChildItem -Path "." -Filter "debug-*.html" -File
```

## Current Organization Status

### ✅ Properly Organized
- **Documentations/**: 41 .md files
- **test/**: 20 HTML files, 29 PHP files
- All paths updated and working

### 📊 File Counts
```
Documentations/
├── 41 documentation files (.md)

test/
├── 20 HTML test/demo files
└── 29 PHP test files

Total organized: 90 files
```

## Benefits of This Organization

1. **Clean Root Directory**
   - Only production files in root
   - Easy to find main application files
   - No clutter from test/debug files

2. **Easy Documentation Access**
   - All docs in one place
   - Easy to browse and search
   - Clear naming conventions

3. **Isolated Testing**
   - Test files don't interfere with production
   - Can be excluded from deployment
   - Easy to clean up or backup

4. **Version Control**
   - Can `.gitignore test/` if needed
   - Clear separation of concerns
   - Better diff tracking

## Maintenance Checklist

When creating new files, ask:

- [ ] Is this a documentation file? → `Documentations/`
- [ ] Is this a test/debug/demo file? → `test/`
- [ ] Is this a production file? → Root or appropriate subfolder
- [ ] Are paths relative (if in subfolder)? → Use `../`
- [ ] Is the naming convention followed? → Check patterns above

## Examples

### ✅ Good Organization
```
Documentations/API-INTEGRATION-GUIDE.md
test/test-api-integration.html
test/debug-api-calls.php
includes/api-functions.php
api/endpoint.php
```

### ❌ Bad Organization
```
API-INTEGRATION-GUIDE.md         ← Should be in Documentations/
test-api-integration.html        ← Should be in test/
debug-api-calls.php              ← Should be in test/
```

## Future Considerations

As the project grows, consider:
- Subdirectories in `test/` (e.g., `test/api/`, `test/ui/`)
- Subdirectories in `Documentations/` (e.g., `Documentations/api/`, `Documentations/user-guides/`)
- Automated cleanup scripts
- Documentation versioning

## Quick Reference

**Remember:**
- 📚 Documentation → `Documentations/`
- 🧪 Tests/Demos → `test/`
- 🚀 Production → Root or specific folders

**Always use relative paths (`../`) for files in subfolders!**

---

*Last Updated: October 23, 2025*
*Status: ✅ Organization Complete*
