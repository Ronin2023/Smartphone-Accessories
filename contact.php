<?php
// Contact Page - Simple version without maintenance mode complexity
// No middleware needed for contact page

// Start session for CSRF token
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once 'includes/functions.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Contact Us - TechCompare</title>
    <meta name="description" content="Get in touch with TechCompare. We're here to help with your tech product questions and comparisons.">
    
    <!-- CSS Files -->
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="css/responsive.css">
    <link rel="stylesheet" href="css/theme.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <!-- Custom Contact Page Styles -->
    <style>
        .contact-hero {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 80px 0 60px;
            text-align: center;
        }
        
        .contact-hero h1 {
            font-size: 3rem;
            margin-bottom: 1rem;
            font-weight: 700;
        }
        
        .contact-hero p {
            font-size: 1.2rem;
            opacity: 0.9;
            max-width: 600px;
            margin: 0 auto;
        }
        
        .contact-container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 60px 20px;
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 60px;
            align-items: start;
        }
        
        .contact-info {
            background: #f8f9fa;
            padding: 40px;
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
        }
        
        .contact-info h2 {
            color: #333;
            margin-bottom: 30px;
            font-size: 2rem;
        }
        
        .contact-item {
            display: flex;
            align-items: center;
            margin-bottom: 25px;
            padding: 20px;
            background: white;
            border-radius: 10px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.05);
            transition: transform 0.3s ease;
        }
        
        .contact-item:hover {
            transform: translateY(-2px);
        }
        
        .contact-item i {
            font-size: 1.5rem;
            color: #667eea;
            margin-right: 20px;
            width: 30px;
            text-align: center;
        }
        
        .contact-item div h3 {
            margin: 0 0 5px 0;
            color: #333;
            font-size: 1.1rem;
        }
        
        .contact-item div p {
            margin: 0;
            color: #666;
        }
        
        .contact-form {
            background: white;
            padding: 40px;
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
        }
        
        .contact-form h2 {
            color: #333;
            margin-bottom: 30px;
            font-size: 2rem;
        }
        
        .form-group {
            margin-bottom: 25px;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 8px;
            color: #333;
            font-weight: 500;
        }
        
        .form-group input,
        .form-group select,
        .form-group textarea {
            width: 100%;
            padding: 15px;
            border: 2px solid #e1e5e9;
            border-radius: 10px;
            font-size: 1rem;
            transition: border-color 0.3s ease;
            box-sizing: border-box;
        }
        
        .form-group input:focus,
        .form-group select:focus,
        .form-group textarea:focus {
            outline: none;
            border-color: #667eea;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
        }
        
        .form-group textarea {
            min-height: 120px;
            resize: vertical;
        }
        
        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
        }
        
        .submit-btn {
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: white;
            padding: 15px 40px;
            border: none;
            border-radius: 10px;
            font-size: 1.1rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            width: 100%;
        }
        
        .submit-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 20px rgba(102, 126, 234, 0.3);
        }
        
        .submit-btn:disabled {
            opacity: 0.7;
            cursor: not-allowed;
            transform: none;
        }
        
        .success-message,
        .error-message {
            padding: 15px;
            border-radius: 10px;
            margin: 20px 0;
            display: none;
        }
        
        .success-message {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        
        .error-message {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        
        .faq-section {
            max-width: 1200px;
            margin: 0 auto;
            padding: 60px 20px;
            background: #f8f9fa;
        }
        
        .faq-section h2 {
            text-align: center;
            color: #333;
            margin-bottom: 40px;
            font-size: 2.5rem;
        }
        
        .faq-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 30px;
        }
        
        .faq-item {
            background: white;
            padding: 30px;
            border-radius: 15px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }
        
        .faq-item h3 {
            color: #667eea;
            margin-bottom: 15px;
            font-size: 1.3rem;
        }
        
        .faq-item p {
            color: #666;
            line-height: 1.6;
        }
        
        /* Responsive Design */
        @media (max-width: 768px) {
            .contact-container {
                grid-template-columns: 1fr;
                gap: 40px;
                padding: 40px 20px;
            }
            
            .contact-hero h1 {
                font-size: 2rem;
            }
            
            .contact-hero {
                padding: 60px 0 40px;
            }
            
            .contact-info,
            .contact-form {
                padding: 30px 20px;
            }
            
            .form-row {
                grid-template-columns: 1fr;
            }
            
            .faq-grid {
                grid-template-columns: 1fr;
            }
        }
        
        /* Loading Animation */
        .loading {
            display: inline-block;
            width: 20px;
            height: 20px;
            border: 3px solid rgba(255,255,255,.3);
            border-radius: 50%;
            border-top-color: #fff;
            animation: spin 1s ease-in-out infinite;
            margin-right: 10px;
        }
        
        @keyframes spin {
            to { transform: rotate(360deg); }
        }
        
        /* Quick Links Section */
        .quick-links {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            padding: 40px 0;
            text-align: center;
        }
        
        .quick-links h3 {
            color: white;
            margin-bottom: 25px;
            font-size: 1.4rem;
        }
        
        .quick-links-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            max-width: 1000px;
            margin: 0 auto;
            padding: 0 20px;
        }
        
        .quick-link-card {
            background: rgba(255, 255, 255, 0.1);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.2);
            border-radius: 15px;
            padding: 25px;
            transition: all 0.3s ease;
            cursor: pointer;
            text-decoration: none;
            color: white;
        }
        
        .quick-link-card:hover {
            background: rgba(255, 255, 255, 0.2);
            transform: translateY(-5px);
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.2);
            color: white;
            text-decoration: none;
        }
        
        .quick-link-icon {
            font-size: 2.5rem;
            margin-bottom: 15px;
            display: block;
            color: rgba(255, 255, 255, 0.9);
        }
        
        .quick-link-title {
            font-size: 1.1rem;
            font-weight: 600;
            margin-bottom: 8px;
            color: white;
        }
        
        .quick-link-desc {
            font-size: 0.9rem;
            color: rgba(255, 255, 255, 0.8);
            line-height: 1.4;
        }
        
        /* Pulse animation for important link */
        .quick-link-card.highlight {
            animation: quickLinkPulse 3s ease-in-out infinite;
        }
        
        @keyframes quickLinkPulse {
            0%, 100% { 
                box-shadow: 0 0 0 0 rgba(255, 255, 255, 0.4);
                transform: translateY(0);
            }
            50% { 
                box-shadow: 0 0 0 15px rgba(255, 255, 255, 0);
                transform: translateY(-2px);
            }
        }
        
        @media (max-width: 768px) {
            .quick-links-grid {
                grid-template-columns: 1fr;
                padding: 0 15px;
            }
            
            .quick-link-card {
                padding: 20px;
            }
        }
    </style>
</head>
<body>
    <!-- Header -->
    <header class="header">
        <nav class="navbar">
            <div class="nav-container">
                <a href="index" class="nav-logo">
                    <i class="fas fa-balance-scale-right"></i>
                    TechCompare
                </a>
                
                <div class="nav-menu">
                    <a href="index" class="nav-link">Home</a>
                    <div class="nav-dropdown">
                        <a href="products" class="nav-link">Products <i class="fas fa-chevron-down"></i></a>
                        <div class="dropdown-content">
                            <a href="products?category=smart-watches">Smart Watches</a>
                            <a href="products?category=wireless-headphones">Wireless Headphones</a>
                            <a href="products?category=wired-headphones">Wired Headphones</a>
                        </div>
                    </div>
                    <a href="compare" class="nav-link">Compare</a>
                    <a href="about" class="nav-link">About</a>
                    <a href="contact" class="nav-link active">Contact</a>
                </div>
                
                <div class="nav-actions">
                    <a href="user_login" class="btn btn-outline">
                        <i class="fas fa-user"></i> Login
                    </a>
                    <a href="user_login" class="btn btn-primary">
                        <i class="fas fa-user-plus"></i> Sign Up
                    </a>
                </div>
                
                <div class="nav-toggle">
                    <span class="bar"></span>
                    <span class="bar"></span>
                    <span class="bar"></span>
                </div>
            </div>
        </nav>
    </header>

    <!-- Contact Hero Section -->
    <section class="contact-hero">
        <div class="container">
            <h1>Get in Touch</h1>
            <p>Have questions about our products or need help with comparisons? We're here to help you make the best tech decisions.</p>
        </div>
    </section>

    <!-- Quick Links Section -->
    <section class="quick-links">
        <div class="container">
            <h3>Already contacted us? Quick Actions</h3>
            <div class="quick-links-grid">
                <a href="check-response" class="quick-link-card highlight">
                    <i class="fas fa-search quick-link-icon"></i>
                    <div class="quick-link-title">Check Response Status</div>
                    <div class="quick-link-desc">Track your inquiry and view admin responses using your email address</div>
                </a>
                
                <a href="#contactForm" class="quick-link-card" onclick="scrollToForm()">
                    <i class="fas fa-envelope-open-text quick-link-icon"></i>
                    <div class="quick-link-title">Submit New Inquiry</div>
                    <div class="quick-link-desc">Have a new question? Send us another message below</div>
                </a>
                
                <a href="tel:+15551234567" class="quick-link-card">
                    <i class="fas fa-phone-alt quick-link-icon"></i>
                    <div class="quick-link-title">Call Us Now</div>
                    <div class="quick-link-desc">Speak directly with our support team during business hours</div>
                </a>
                
                <a href="mailto:support@techcompare.com" class="quick-link-card">
                    <i class="fas fa-paper-plane quick-link-icon"></i>
                    <div class="quick-link-title">Send Direct Email</div>
                    <div class="quick-link-desc">Email us directly for urgent matters or follow-ups</div>
                </a>
            </div>
        </div>
    </section>

    <!-- Contact Content -->
    <section class="contact-container">
        <!-- Contact Information -->
        <div class="contact-info">
            <h2>Contact Information</h2>
            
            <div class="contact-item">
                <i class="fas fa-map-marker-alt"></i>
                <div>
                    <h3>Address</h3>
                    <p>123 Tech Street, Innovation District<br>Digital City, DC 12345</p>
                </div>
            </div>
            
            <div class="contact-item">
                <i class="fas fa-phone"></i>
                <div>
                    <h3>Phone</h3>
                    <p>+1 (555) 123-4567<br>Mon-Fri: 9AM-6PM EST</p>
                </div>
            </div>
            
            <div class="contact-item">
                <i class="fas fa-envelope"></i>
                <div>
                    <h3>Email</h3>
                    <p>support@techcompare.com<br>We respond within 24 hours</p>
                </div>
            </div>
            
            <div class="contact-item">
                <i class="fas fa-clock"></i>
                <div>
                    <h3>Business Hours</h3>
                    <p>Monday - Friday: 9:00 AM - 6:00 PM<br>Saturday: 10:00 AM - 4:00 PM<br>Sunday: Closed</p>
                </div>
            </div>
            
            <div class="contact-item">
                <i class="fas fa-headset"></i>
                <div>
                    <h3>Live Chat</h3>
                    <p>Available during business hours<br>Get instant help with our chat support</p>
                </div>
            </div>
        </div>

        <!-- Contact Form -->
        <div class="contact-form">
            <h2>Send us a Message</h2>
            
            <!-- Rate Limit Notice -->
            <div class="rate-limit-notice" style="background: #e8f4fd; border: 1px solid #b8daff; border-radius: 8px; padding: 15px; margin-bottom: 20px; color: #004085;">
                <i class="fas fa-info-circle" style="color: #0056b3; margin-right: 8px;"></i>
                <strong>Note:</strong> To ensure quality support, you may submit only one inquiry per 24 hours per email address. This helps us provide better and faster responses to all customers.
            </div>
            
            <div class="success-message" id="successMessage">
                <i class="fas fa-check-circle"></i>
                Thank you for your message! We'll get back to you within 24 hours.
            </div>
            
            <div class="error-message" id="errorMessage">
                <i class="fas fa-exclamation-triangle"></i>
                <span id="errorText">Something went wrong. Please try again.</span>
            </div>
            
            <form id="contactForm" action="api/submit_contact.php" method="POST">
                <!-- CSRF Protection -->
                <?php echo csrfField(); ?>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="name">Full Name *</label>
                        <input type="text" id="name" name="name" required maxlength="100">
                    </div>
                    
                    <div class="form-group">
                        <label for="email">Email Address *</label>
                        <input type="email" id="email" name="email" required maxlength="100">
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="phone">Phone Number</label>
                        <input type="tel" id="phone" name="phone" maxlength="20">
                    </div>
                    
                    <div class="form-group">
                        <label for="priority">Priority Level</label>
                        <select id="priority" name="priority">
                            <option value="low">Low</option>
                            <option value="medium" selected>Medium</option>
                            <option value="high">High</option>
                            <option value="urgent">Urgent</option>
                        </select>
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="subject">Subject *</label>
                    <input type="text" id="subject" name="subject" required maxlength="255" placeholder="Brief description of your inquiry">
                </div>
                
                <div class="form-group">
                    <label for="message">Message *</label>
                    <textarea id="message" name="message" required placeholder="Please provide details about your question or issue..."></textarea>
                </div>
                
                <button type="submit" class="submit-btn" id="submitBtn">
                    <span id="submitText">Send Message</span>
                </button>
            </form>
        </div>
    </section>

    <!-- FAQ Section -->
    <section class="faq-section">
        <h2>Frequently Asked Questions</h2>
        <div class="faq-grid">
            <div class="faq-item">
                <h3>How do I compare products?</h3>
                <p>Simply browse our product catalog, select the items you want to compare, and click the "Compare" button. You can compare up to 4 products at once.</p>
            </div>
            
            <div class="faq-item">
                <h3>Are your product prices accurate?</h3>
                <p>We update our prices regularly, but they may vary from retailer to retailer. Always check the current price on the retailer's website before making a purchase.</p>
            </div>
            
            <div class="faq-item">
                <h3>How often do you add new products?</h3>
                <p>We add new products weekly and update existing product information regularly to ensure you have access to the latest tech products and specifications.</p>
            </div>
            
            <div class="faq-item">
                <h3>Can I request a specific product?</h3>
                <p>Absolutely! If you can't find a product you're looking for, contact us and we'll do our best to add it to our database.</p>
            </div>
            
            <div class="faq-item">
                <h3>Do you offer purchase recommendations?</h3>
                <p>While we provide detailed comparisons and specifications, we don't make direct purchase recommendations. Our goal is to give you the information you need to make informed decisions.</p>
            </div>
            
            <div class="faq-item">
                <h3>How can I stay updated on new products?</h3>
                <p>Follow us on social media or check our website regularly for the latest product additions and tech news.</p>
            </div>
            
            <div class="faq-item">
                <h3>How often can I submit support requests?</h3>
                <p>To ensure quality support for all customers, you may submit one inquiry per email address every 24 hours. This policy helps us provide faster and more personalized responses to everyone.</p>
            </div>
        </div>
    </section>

    <!-- Include Footer -->
    <div id="footer-placeholder"></div>

    <!-- JavaScript -->
    <script src="js/theme.js"></script>
    <script src="js/connection-error-handler.js"></script>
    <script src="js/main.js"></script>
    <script>
        // Load header and footer
        loadTemplate('header-placeholder', 'templates/header.html');
        loadTemplate('footer-placeholder', 'templates/footer.html');

        // Toast Notification System
        function showToast(message, type = 'info', duration = 5000) {
            console.log('showToast called:', message, type); // Debug log
            
            // Remove any existing toasts
            const existingToasts = document.querySelectorAll('.toast-notification');
            existingToasts.forEach(toast => toast.remove());
            
            // Create toast element
            const toast = document.createElement('div');
            toast.className = 'toast-notification';
            toast.setAttribute('data-type', type);
            
            // Set toast content and styling
            const backgroundColor = {
                'success': 'linear-gradient(135deg, #28a745, #20c997)',
                'error': 'linear-gradient(135deg, #dc3545, #c82333)',
                'warning': 'linear-gradient(135deg, #ffc107, #fd7e14)',
                'info': 'linear-gradient(135deg, #17a2b8, #6f42c1)'
            };
            
            const iconClass = {
                'success': 'fas fa-check-circle',
                'error': 'fas fa-exclamation-circle',
                'warning': 'fas fa-exclamation-triangle',
                'info': 'fas fa-info-circle'
            };
            
            toast.style.cssText = `
                position: fixed;
                top: 20px;
                right: 20px;
                background: ${backgroundColor[type] || backgroundColor['info']};
                color: white;
                padding: 16px 20px;
                border-radius: 8px;
                box-shadow: 0 4px 12px rgba(0,0,0,0.15), 0 2px 6px rgba(0,0,0,0.1);
                z-index: 10000;
                max-width: 400px;
                min-width: 300px;
                font-family: inherit;
                font-size: 14px;
                line-height: 1.4;
                animation: toastSlideIn 0.3s ease-out;
                cursor: pointer;
                word-wrap: break-word;
            `;
            
            toast.innerHTML = `
                <div style="display: flex; align-items: flex-start; gap: 12px;">
                    <i class="${iconClass[type] || iconClass['info']}" style="font-size: 18px; margin-top: 2px; flex-shrink: 0;"></i>
                    <div style="flex: 1;">
                        <div style="font-weight: 500; margin-bottom: 4px;">
                            ${type.charAt(0).toUpperCase() + type.slice(1)}
                        </div>
                        <div style="opacity: 0.95;">
                            ${message}
                        </div>
                    </div>
                    <button onclick="this.parentElement.parentElement.remove()" 
                            style="background: none; border: none; color: white; font-size: 18px; cursor: pointer; padding: 0; margin-left: 8px; opacity: 0.8; line-height: 1;">
                        ×
                    </button>
                </div>
            `;
            
            // Add CSS animations if not already present
            if (!document.querySelector('#toast-animations')) {
                const style = document.createElement('style');
                style.id = 'toast-animations';
                style.textContent = `
                    @keyframes toastSlideIn {
                        from { 
                            opacity: 0; 
                            transform: translateX(100%); 
                        }
                        to { 
                            opacity: 1; 
                            transform: translateX(0); 
                        }
                    }
                    @keyframes toastSlideOut {
                        from { 
                            opacity: 1; 
                            transform: translateX(0); 
                        }
                        to { 
                            opacity: 0; 
                            transform: translateX(100%); 
                        }
                    }
                    .toast-notification:hover {
                        transform: translateY(-2px);
                        box-shadow: 0 6px 16px rgba(0,0,0,0.2), 0 3px 8px rgba(0,0,0,0.15) !important;
                    }
                `;
                document.head.appendChild(style);
            }
            
            // Add to DOM
            document.body.appendChild(toast);
            
            // Auto remove after duration
            setTimeout(() => {
                if (toast.parentElement) {
                    toast.style.animation = 'toastSlideOut 0.3s ease-in forwards';
                    setTimeout(() => {
                        if (toast.parentElement) {
                            toast.remove();
                        }
                    }, 300);
                }
            }, duration);
            
            // Click to dismiss
            toast.addEventListener('click', function() {
                if (this.parentElement) {
                    this.style.animation = 'toastSlideOut 0.3s ease-in forwards';
                    setTimeout(() => {
                        if (this.parentElement) {
                            this.remove();
                        }
                    }, 300);
                }
            });
            
            return toast;
        }

        // Contact Form Handler
        document.getElementById('contactForm').addEventListener('submit', async function(e) {
            e.preventDefault();
            
            const submitBtn = document.getElementById('submitBtn');
            const submitText = document.getElementById('submitText');
            const successMessage = document.getElementById('successMessage');
            const errorMessage = document.getElementById('errorMessage');
            const errorText = document.getElementById('errorText');
            
            // Show loading state
            submitBtn.disabled = true;
            submitText.innerHTML = '<span class="loading"></span>Sending...';
            
            // Hide previous messages
            successMessage.style.display = 'none';
            errorMessage.style.display = 'none';
            
            try {
                const formData = new FormData(this);
                
                const response = await fetch('api/submit_contact.php', {
                    method: 'POST',
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest',
                        'Accept': 'application/json'
                    },
                    body: formData
                });
                
                // Check if response is actually JSON
                const contentType = response.headers.get('content-type');
                if (!contentType || !contentType.includes('application/json')) {
                    const htmlResponse = await response.text();
                    console.error('Expected JSON but got HTML:', htmlResponse.substring(0, 500));
                    throw new Error('Server returned HTML instead of JSON. Please check server configuration.');
                }
                
                const result = await response.json();
                
                // Check for database/connection errors specifically
                if (result.connection_error || result.code === 503 || 
                    (result.error && (
                        result.error.toLowerCase().includes('database') ||
                        result.error.toLowerCase().includes('connection') ||
                        result.error.toLowerCase().includes('service temporarily unavailable') ||
                        result.error.toLowerCase().includes('server unavailable')
                    ))) {
                    
                    console.log('Database/connection error detected, redirecting to connection error page...');
                    
                    // Show connection error toast
                    showToast('Database connection issue detected. Redirecting to connection error page...', 'warning');
                    
                    // Store error info for the connection error page
                    if (typeof(Storage) !== "undefined") {
                        sessionStorage.setItem('connectionErrorInfo', JSON.stringify({
                            type: 'database',
                            message: result.message || result.error || 'Database connection failed',
                            timestamp: Date.now(),
                            referring_page: window.location.href
                        }));
                    }
                    
                    // Detect current base path for proper redirection
                    const currentPath = window.location.pathname;
                    let basePath = '/';
                    if (currentPath.includes('/Smartphone-Accessories/')) {
                        basePath = '/Smartphone-Accessories/';
                    }
                    
                    // Redirect to connection error page after a brief delay
                    setTimeout(() => {
                        window.location.href = basePath + 'connection-error.php';
                    }, 1500);
                    
                    return; // Exit early, don't process as normal response
                }
                
                if (result.success) {
                    // Show success toast notification before redirect
                    showToast('Message sent successfully! Redirecting to tracking page...', 'success');
                    
                    // Small delay to show the toast before redirect
                    setTimeout(() => {
                        const params = new URLSearchParams({
                            id: result.submission_id,
                            email: formData.get('email')
                        });
                        
                        window.location.href = `contact-success.html?${params.toString()}`;
                    }, 2000);
                } else {
                    // Show error toast notification
                    const errorMsg = result.message || 'Something went wrong. Please try again.';
                    console.log('Showing error toast:', errorMsg); // Debug log
                    showToast(errorMsg, 'error');
                    
                    // Also show inline error message for fallback
                    errorText.textContent = errorMsg;
                    errorMessage.style.display = 'block';
                    errorMessage.scrollIntoView({ behavior: 'smooth' });
                }
            } catch (error) {
                console.error('Form submission error:', error);
                const networkError = 'Network error. Please check your connection and try again.';
                
                // Show error toast notification
                showToast(networkError, 'error');
                
                // Also show inline error message for fallback
                errorText.textContent = networkError;
                errorMessage.style.display = 'block';
                errorMessage.scrollIntoView({ behavior: 'smooth' });
            } finally {
                // Reset button state
                submitBtn.disabled = false;
                submitText.textContent = 'Send Message';
            }
        });

        // Form validation
        document.getElementById('contactForm').addEventListener('input', function(e) {
            const field = e.target;
            
            // Remove error styling when user starts typing
            if (field.classList.contains('error')) {
                field.classList.remove('error');
            }
            
            // Real-time validation
            if (field.type === 'email' && field.value) {
                const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
                if (!emailRegex.test(field.value)) {
                    field.style.borderColor = '#e74c3c';
                } else {
                    field.style.borderColor = '#28a745';
                }
            }
        });

        // Character count for message field
        const messageField = document.getElementById('message');
        const maxLength = 1000;
        
        // Create character counter
        const charCounter = document.createElement('div');
        charCounter.style.textAlign = 'right';
        charCounter.style.fontSize = '0.9rem';
        charCounter.style.color = '#666';
        charCounter.style.marginTop = '5px';
        messageField.parentNode.appendChild(charCounter);
        
        messageField.addEventListener('input', function() {
            const remaining = maxLength - this.value.length;
            charCounter.textContent = `${this.value.length}/${maxLength} characters`;
            
            if (remaining < 50) {
                charCounter.style.color = '#e74c3c';
            } else {
                charCounter.style.color = '#666';
            }
        });
        
        // Initialize character counter
        messageField.dispatchEvent(new Event('input'));
        
        // Smooth scroll function for quick links
        function scrollToForm() {
            document.getElementById('contactForm').scrollIntoView({ 
                behavior: 'smooth',
                block: 'start'
            });
        }
        
        // Highlight quick link based on URL parameters
        window.addEventListener('DOMContentLoaded', function() {
            const urlParams = new URLSearchParams(window.location.search);
            const action = urlParams.get('action');
            
            // If coming from success page or other source, highlight appropriate action
            if (action === 'check-response') {
                const checkResponseCard = document.querySelector('a[href="check-response"]');
                if (checkResponseCard) {
                    checkResponseCard.scrollIntoView({ behavior: 'smooth', block: 'center' });
                    checkResponseCard.style.animation = 'quickLinkPulse 3s ease-in-out 3';
                    
                    // Show helpful banner
                    showActionBanner('Looking to check your response status? Click the highlighted card above!', 'info');
                }
            }
            
            // Auto-expand contact form if user came to submit new inquiry
            if (action === 'new-inquiry') {
                setTimeout(() => {
                    scrollToForm();
                    showActionBanner('Ready to submit a new inquiry? The form is ready below!', 'success');
                }, 500);
            }
        });
        
        // Function to show action banner
        function showActionBanner(message, type = 'info') {
            const banner = document.createElement('div');
            banner.style.cssText = `
                position: fixed;
                top: 20px;
                left: 50%;
                transform: translateX(-50%);
                background: ${type === 'info' ? 'linear-gradient(135deg, #17a2b8, #138496)' : 'linear-gradient(135deg, #28a745, #20c997)'};
                color: white;
                padding: 15px 25px;
                border-radius: 25px;
                box-shadow: 0 5px 15px rgba(0,0,0,0.2);
                z-index: 1000;
                font-weight: 500;
                max-width: 90%;
                text-align: center;
                animation: bannerSlideIn 0.5s ease-out;
            `;
            banner.innerHTML = `<i class="fas fa-${type === 'info' ? 'info-circle' : 'check-circle'}"></i> ${message}`;
            
            document.body.appendChild(banner);
            
            // Remove banner after 5 seconds
            setTimeout(() => {
                banner.style.animation = 'bannerSlideOut 0.5s ease-in forwards';
                setTimeout(() => {
                    document.body.removeChild(banner);
                }, 500);
            }, 5000);
            
            // Add animations to CSS if not already present
            if (!document.querySelector('#banner-animations')) {
                const style = document.createElement('style');
                style.id = 'banner-animations';
                style.textContent = `
                    @keyframes bannerSlideIn {
                        from { opacity: 0; transform: translateX(-50%) translateY(-20px); }
                        to { opacity: 1; transform: translateX(-50%) translateY(0); }
                    }
                    @keyframes bannerSlideOut {
                        from { opacity: 1; transform: translateX(-50%) translateY(0); }
                        to { opacity: 0; transform: translateX(-50%) translateY(-20px); }
                    }
                `;
                document.head.appendChild(style);
            }
        }
        
        // Add click analytics for quick links (optional)
        document.querySelectorAll('.quick-link-card').forEach(card => {
            card.addEventListener('click', function(e) {
                const linkTitle = this.querySelector('.quick-link-title').textContent;
                console.log('Quick link clicked:', linkTitle);
                
                // Optional: Send analytics data
                // gtag('event', 'click', { 'event_category': 'quick_links', 'event_label': linkTitle });
            });
        });
    </script>
</body>
</html>