# TechCompare - Smart Tech Product Comparison Website

A full-featured web application for comparing smart watches, wireless headphones, and wired headphones across major brands.

## Features

- **Product Comparison**: Side-by-side comparison of up to 4 products
- **Advanced Search**: Search products by name, brand, or category
- **Responsive Design**: Mobile-first design that works on all devices
- **Admin Panel**: Complete admin interface for managing products, brands, and categories
- **SEO Optimized**: Clean URLs, meta tags, and structured data
- **Modern UI**: Clean, professional design with smooth animations

## Tech Stack

- **Frontend**: HTML5, CSS3, Vanilla JavaScript
- **Backend**: PHP 7.4+
- **Database**: MySQL 5.7+
- **Icons**: Font Awesome 6
- **Development**: Laragon (or XAMPP/WAMP)

## Installation

### Prerequisites

- PHP 7.4 or higher
- MySQL 5.7 or higher
- Web server (Apache/Nginx)
- Laragon, XAMPP, or similar development environment

### Setup Steps

1. **Clone/Download the project**
   Place the project folder in your web server directory
   (e.g., C:\laragon\www\Smartphone-Accessories)

2. **Database Setup**
   - Start your MySQL server
   - Navigate to `http://localhost/Smartphone-Accessories/database/setup.php`
   - This will create the database and insert sample data

3. **Configuration**
   - Update database credentials in `includes/config.php` if needed
   - Default settings work with Laragon's default MySQL setup

4. **File Permissions**
   - Ensure the `uploads/` directory is writable
   - Set appropriate permissions for file uploads

5. **Access the Application**
   - Main site: `http://localhost/Smartphone-Accessories/`
   - Admin panel: `http://localhost/Smartphone-Accessories/admin/`
   - Default admin credentials: `admin` / `admin@123`

## Project Structure

```
Smartphone-Accessories/
├── Documentations/         # 📚 All .md documentation files
├── test/                   # 🧪 Test & demo files
├── admin/                  # Admin panel files
├── api/                    # REST API endpoints
├── assets/                 # Static assets
│   ├── fonts/
│   ├── icons/
│   └── images/
├── css/                    # Stylesheets (includes theme.css for dark mode)
├── database/               # Database files
├── includes/               # PHP includes
├── js/                     # JavaScript files (includes theme.js for dark mode)
├── templates/              # HTML templates
├── uploads/                # User uploads
└── vendor/                 # Third-party libraries
```

## 📚 Documentation

All documentation files are organized in the **`Documentations/`** folder:

- **Dark Mode**: `DARK-MODE-IMPLEMENTATION.md`, `DARK-MODE-SUMMARY.md`, `DARK-MODE-VISUAL-GUIDE.md`
- **Organization**: `FILE-ORGANIZATION-GUIDE.md`
- **Special Access**: `SPECIAL-ACCESS-*.md` files
- **Maintenance**: `MAINTENANCE-*.md` files
- And 40+ more comprehensive guides

## 🧪 Testing

All test and demo files are in the **`test/`** folder:

- **Dark Mode Demo**: `test/dark-mode-demo.html`
- **Test Files**: Various `test-*.html` and `test-*.php` files
- **Debug Files**: Various `debug-*.html` files

## Project Structure

1. **Setup Database**: Visit `/database/setup.php` to create database and sample data
2. **Main Site**: Access at `/index.html`
3. **Admin Panel**: Login at `/admin/` with admin/admin@123

## Key Features

### ✅ Fixed Issues

- Logo display properly fixed with Font Awesome icons
- Responsive navigation with working mobile menu
- Complete CSS styling with animations
- JavaScript functionality with error handling
- Database schema with sample data
- API endpoints for dynamic content
- Fallback images for missing product photos
- Mobile-first responsive design

### 🔧 Technical Improvements

- Modern CSS Grid and Flexbox layouts
- Intersection Observer for animations
- Async/await for API calls
- Progressive enhancement approach
- Accessible design patterns
- SEO-optimized markup
