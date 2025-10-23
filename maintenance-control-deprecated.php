<?php
/**
 * Maintenance Mode Control - DEPRECATED
 * 
 * ⚠️ SECURITY NOTICE: This file has been moved to admin area for security reasons
 * 
 * REDIRECT: This page now redirects to admin/settings.php
 * Please update your bookmarks and links
 */

session_start();
require_once 'includes/config.php';
require_once 'includes/functions.php';

// Check if user is logged in admin
if (function_exists('isLoggedIn') && function_exists('hasAdminAccess')) {
    if (isLoggedIn() && hasAdminAccess()) {
        // Admin is logged in, redirect to admin settings
        header('Location: admin/settings.php');
        exit;
    }
}

// Not logged in or not admin - show security message
http_response_code(403);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Access Denied - Maintenance Control</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, Cantarell, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 2rem;
        }
        
        .container {
            max-width: 600px;
            background: white;
            border-radius: 16px;
            padding: 3rem;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
            text-align: center;
        }
        
        .icon {
            font-size: 5rem;
            color: #ef4444;
            margin-bottom: 1.5rem;
            animation: shake 1s ease-in-out infinite;
        }
        
        @keyframes shake {
            0%, 100% { transform: rotate(0deg); }
            25% { transform: rotate(-10deg); }
            75% { transform: rotate(10deg); }
        }
        
        h1 {
            font-size: 2rem;
            color: #1f2937;
            margin-bottom: 1rem;
        }
        
        .subtitle {
            font-size: 1.25rem;
            color: #6b7280;
            margin-bottom: 2rem;
        }
        
        .notice {
            background: #fef2f2;
            border: 2px solid #fecaca;
            border-radius: 8px;
            padding: 1.5rem;
            margin-bottom: 2rem;
            text-align: left;
        }
        
        .notice h3 {
            color: #dc2626;
            margin-bottom: 0.75rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        
        .notice ul {
            color: #991b1b;
            margin-left: 1.5rem;
            line-height: 1.8;
        }
        
        .info-box {
            background: #eff6ff;
            border: 2px solid #bfdbfe;
            border-radius: 8px;
            padding: 1.5rem;
            margin-bottom: 2rem;
            text-align: left;
        }
        
        .info-box h3 {
            color: #1e40af;
            margin-bottom: 0.75rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        
        .info-box p {
            color: #1e3a8a;
            line-height: 1.8;
        }
        
        .btn {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            padding: 1rem 2rem;
            background: #3b82f6;
            color: white;
            text-decoration: none;
            border-radius: 8px;
            font-weight: 600;
            transition: all 0.3s ease;
            margin: 0.5rem;
        }
        
        .btn:hover {
            background: #2563eb;
            transform: translateY(-2px);
            box-shadow: 0 10px 20px rgba(59, 130, 246, 0.3);
        }
        
        .btn-secondary {
            background: #6b7280;
        }
        
        .btn-secondary:hover {
            background: #4b5563;
        }
        
        .code {
            background: #f3f4f6;
            padding: 0.25rem 0.5rem;
            border-radius: 4px;
            font-family: monospace;
            color: #374151;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="icon">
            <i class="fas fa-shield-alt"></i>
        </div>
        
        <h1>⚠️ Access Restricted</h1>
        <p class="subtitle">Maintenance Control Has Been Secured</p>
        
        <div class="notice">
            <h3>
                <i class="fas fa-exclamation-triangle"></i>
                Security Update
            </h3>
            <ul>
                <li><strong>This page is no longer accessible from the root folder</strong></li>
                <li>Maintenance control has been moved to the admin area</li>
                <li>Only logged-in administrators can access it</li>
                <li>This prevents unauthorized users from controlling maintenance mode</li>
            </ul>
        </div>
        
        <div class="info-box">
            <h3>
                <i class="fas fa-info-circle"></i>
                For Administrators
            </h3>
            <p>
                To manage maintenance mode, please:
            </p>
            <ol style="margin-left: 1.5rem; margin-top: 1rem; line-height: 1.8;">
                <li>Log in to the admin panel</li>
                <li>Navigate to <span class="code">Settings</span></li>
                <li>Use the <strong>Maintenance Mode</strong> tab</li>
            </ol>
        </div>
        
        <div>
            <a href="admin/login.php" class="btn">
                <i class="fas fa-sign-in-alt"></i>
                Admin Login
            </a>
            <a href="index.php" class="btn btn-secondary">
                <i class="fas fa-home"></i>
                Go Home
            </a>
        </div>
        
        <p style="margin-top: 2rem; color: #6b7280; font-size: 0.875rem;">
            <i class="fas fa-lock"></i> This change improves security by preventing unauthorized access
        </p>
    </div>
</body>
</html>
