<?php
// Simple test page WITHOUT middleware to test overlay functionality
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Direct Overlay Test - No Middleware</title>
</head>
<body>
    <h1>🧪 Direct Overlay Test</h1>
    <p>This page bypasses all middleware to test the overlay directly.</p>
    <p><strong>Expected:</strong> If you have ?special_access_token=XXX in the URL, the overlay should appear.</p>
    
    <div id="debug-info"></div>
    
    <h2>Normal Page Content</h2>
    <p>This content should be blurred when overlay appears.</p>
    <p>Lorem ipsum dolor sit amet, consectetur adipiscing elit.</p>

    <!-- Special Access Overlay (copied from index.php) -->
    <div id="special-access-overlay" class="special-access-overlay" style="display: none;">
        <div class="special-access-popup">
            <div class="popup-header">
                <i class="fas fa-key">🔑</i>
                <h3>Special Access Required</h3>
                <p>Enter your special access token to continue</p>
            </div>
            
            <form id="special-access-form" class="special-access-form">
                <div class="input-group">
                    <label for="access-token">Access Token</label>
                    <input type="text" id="access-token" name="token" placeholder="Enter your special access token..." required>
                    <div class="input-hint">Token should be 64 characters long</div>
                </div>
                
                <div class="form-actions">
                    <button type="submit" class="btn-submit">
                        <i class="fas fa-unlock">🔓</i>
                        Verify Token
                    </button>
                </div>
                
                <div id="token-error" class="error-message" style="display: none;"></div>
            </form>
            
            <div class="popup-footer">
                <p>Don't have a token? Contact your administrator for access.</p>
            </div>
        </div>
    </div>
    
    <!-- Exact JavaScript from index.php -->
    <script>
        // Special Access Token Detection and Overlay System
        (function() {
            'use strict';
            
            console.log('🔍 Starting overlay detection...');
            
            // Check if we have a special access token in URL
            const urlParams = new URLSearchParams(window.location.search);
            const specialToken = urlParams.get('special_access_token') || urlParams.get('special_access');
            
            console.log('URL params:', window.location.search);
            console.log('Special token found:', specialToken);
            
            // Update debug info
            document.getElementById('debug-info').innerHTML = `
                <div style="background: #f0f0f0; padding: 10px; margin: 10px 0; border-radius: 5px;">
                    <strong>Debug Info:</strong><br>
                    URL: ${window.location.href}<br>
                    Search: ${window.location.search}<br>
                    Token: ${specialToken || 'NOT FOUND'}<br>
                    Token Length: ${specialToken ? specialToken.length : 'N/A'}
                </div>
            `;
            
            if (specialToken) {
                console.log('✅ Token detected, showing overlay');
                // Show the overlay immediately
                showSpecialAccessOverlay();
                
                // Pre-fill the token field
                const tokenInput = document.getElementById('access-token');
                if (tokenInput) {
                    tokenInput.value = specialToken;
                    console.log('✅ Token pre-filled in input');
                }
            } else {
                console.log('❌ No special token found in URL');
            }
            
            function showSpecialAccessOverlay() {
                console.log('📱 Showing overlay...');
                const overlay = document.getElementById('special-access-overlay');
                if (overlay) {
                    overlay.style.display = 'flex';
                    document.body.style.overflow = 'hidden';
                    
                    // Add blur effect to main content
                    const mainContent = document.body;
                    mainContent.classList.add('blurred-background');
                    
                    console.log('✅ Overlay displayed');
                    
                    // Focus on token input
                    setTimeout(() => {
                        const tokenInput = document.getElementById('access-token');
                        if (tokenInput) {
                            tokenInput.focus();
                            console.log('✅ Focus set to token input');
                        }
                    }, 100);
                } else {
                    console.log('❌ Overlay element not found');
                }
            }
            
            // Handle form submission
            const form = document.getElementById('special-access-form');
            if (form) {
                form.addEventListener('submit', function(e) {
                    e.preventDefault();
                    console.log('📝 Form submitted');
                    alert('Test form submitted! Token: ' + document.getElementById('access-token').value);
                });
            }
        })();
    </script>
    
    <style>
        /* Copy all overlay styles from index.php */
        .special-access-overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.8);
            backdrop-filter: blur(10px);
            z-index: 10000;
            display: flex;
            align-items: center;
            justify-content: center;
            animation: fadeIn 0.3s ease-out;
        }
        
        .blurred-background > *:not(#special-access-overlay) {
            filter: blur(5px);
            transition: filter 0.3s ease;
        }
        
        .special-access-popup {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            padding: 2rem;
            border-radius: 15px;
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.3);
            max-width: 450px;
            width: 90%;
            text-align: center;
            color: white;
            animation: slideIn 0.3s ease-out;
            position: relative;
        }
        
        .popup-header {
            margin-bottom: 1.5rem;
        }
        
        .popup-header h3 {
            margin: 0 0 0.5rem 0;
            font-size: 1.5rem;
            font-weight: 600;
        }
        
        .popup-header p {
            margin: 0;
            opacity: 0.9;
            font-size: 0.95rem;
        }
        
        .special-access-form {
            margin-bottom: 1.5rem;
        }
        
        .input-group {
            margin-bottom: 1rem;
            text-align: left;
        }
        
        .input-group label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 500;
            font-size: 0.9rem;
        }
        
        .input-group input {
            width: 100%;
            padding: 0.75rem 1rem;
            border: 2px solid rgba(255, 255, 255, 0.3);
            border-radius: 8px;
            background: rgba(255, 255, 255, 0.1);
            color: white;
            font-size: 0.9rem;
            box-sizing: border-box;
            transition: all 0.3s ease;
        }
        
        .input-group input::placeholder {
            color: rgba(255, 255, 255, 0.7);
        }
        
        .input-group input:focus {
            outline: none;
            border-color: #ffd700;
            background: rgba(255, 255, 255, 0.15);
            box-shadow: 0 0 0 3px rgba(255, 215, 0, 0.2);
        }
        
        .input-hint {
            font-size: 0.8rem;
            margin-top: 0.25rem;
            opacity: 0.8;
        }
        
        .form-actions {
            margin-top: 1.5rem;
        }
        
        .btn-submit {
            background: linear-gradient(135deg, #ffd700 0%, #ffed4a 100%);
            color: #333;
            border: none;
            padding: 0.75rem 2rem;
            border-radius: 25px;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            box-shadow: 0 4px 15px rgba(255, 215, 0, 0.3);
        }
        
        .btn-submit:hover:not(:disabled) {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(255, 215, 0, 0.4);
        }
        
        .error-message {
            background: rgba(255, 0, 0, 0.2);
            border: 1px solid rgba(255, 0, 0, 0.5);
            color: #ffcccc;
            padding: 0.75rem;
            border-radius: 8px;
            margin-top: 1rem;
            font-size: 0.9rem;
        }
        
        .popup-footer {
            font-size: 0.85rem;
            opacity: 0.8;
            padding-top: 1rem;
            border-top: 1px solid rgba(255, 255, 255, 0.2);
        }
        
        .popup-footer p {
            margin: 0;
        }
        
        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }
        
        @keyframes slideIn {
            from { 
                opacity: 0;
                transform: scale(0.9) translateY(-20px);
            }
            to { 
                opacity: 1;
                transform: scale(1) translateY(0);
            }
        }
    </style>
</body>
</html>