<?php
/**
 * Special Access Passkey Verification
 * 
 * This page appears when user accesses site via special access link
 * Prompts for passkey to grant full site access during maintenance
 */

session_start();
require_once 'includes/config.php';
require_once 'includes/db_connect.php';
require_once 'includes/functions.php';
require_once 'includes/special-access-manager.php';

// Check if already verified
if (isset($_SESSION['special_access_verified']) && $_SESSION['special_access_verified']) {
    $manager = getSpecialAccessManager();
    if ($manager && $manager->hasActiveSession()) {
        // Already verified, redirect to index
        header('Location: index.php');
        exit;
    }
}

// Handle token validation request from overlay
if (isset($_GET['action']) && $_GET['action'] === 'validate_token') {
    header('Content-Type: application/json');
    
    $token = $_GET['token'] ?? '';
    if (empty($token)) {
        echo json_encode(['valid' => false, 'error' => 'No token provided']);
        exit;
    }
    
    try {
        $pdo = getDB();
        $stmt = $pdo->prepare("SELECT id FROM special_access_tokens WHERE token = ? AND is_active = 1");
        $stmt->execute([$token]);
        $result = $stmt->fetch();
        
        if ($result) {
            echo json_encode(['valid' => true]);
        } else {
            echo json_encode(['valid' => false, 'error' => 'Invalid or expired token']);
        }
    } catch (Exception $e) {
        echo json_encode(['valid' => false, 'error' => 'Database error']);
    }
    exit;
}

// Get token from URL
$token = $_GET['token'] ?? '';

if (empty($token)) {
    header('Location: maintenance.php');
    exit;
}

// Store token in session for verification
$_SESSION['special_access_token'] = $token;

$error = '';
$success = '';

// Handle passkey submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validate CSRF token
    if (!validateCSRFToken($_POST['csrf_token'] ?? '')) {
        $error = 'Invalid security token. Please refresh the page and try again.';
    } else {
        $passkey = strtoupper(trim($_POST['passkey'] ?? ''));
    
    if (empty($passkey)) {
        $error = 'Please enter your passkey';
    } else {
        $manager = getSpecialAccessManager();
        if ($manager) {
            $result = $manager->verifyPasskey($token, $passkey);
            
            if ($result['success']) {
                // Set session variables
                $_SESSION['special_access_verified'] = true;
                $_SESSION['special_access_token_id'] = $result['token_id'];
                $_SESSION['special_access_name'] = $result['name'];
                $_SESSION['special_access_expires'] = $result['expires_at'];
                
                $success = 'Access granted! Redirecting...';
                
                // Redirect after 1 second to index.php (not index.html)
                header('Refresh: 1; URL=index.php');
            } else {
                $error = $result['error'];
            }
        } else {
            $error = 'System error. Please try again.';
        }
    }
    } // Close CSRF validation else block
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Special Access - Enter Passkey</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }

        .container {
            background: white;
            padding: 50px 40px;
            border-radius: 20px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
            max-width: 500px;
            width: 100%;
            animation: slideIn 0.5s ease-out;
        }

        @keyframes slideIn {
            from {
                opacity: 0;
                transform: translateY(-30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .icon-container {
            text-align: center;
            margin-bottom: 30px;
        }

        .icon-container i {
            font-size: 60px;
            color: #667eea;
            animation: pulse 2s infinite;
        }

        @keyframes pulse {
            0%, 100% {
                transform: scale(1);
            }
            50% {
                transform: scale(1.1);
            }
        }

        h1 {
            text-align: center;
            color: #333;
            margin-bottom: 10px;
            font-size: 28px;
        }

        .subtitle {
            text-align: center;
            color: #666;
            margin-bottom: 30px;
            font-size: 14px;
            line-height: 1.6;
        }

        .alert {
            padding: 15px 20px;
            border-radius: 10px;
            margin-bottom: 25px;
            font-size: 14px;
            animation: fadeIn 0.3s;
        }

        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }

        .alert-error {
            background: #fee;
            border-left: 4px solid #e74c3c;
            color: #c0392b;
        }

        .alert-success {
            background: #efffef;
            border-left: 4px solid #27ae60;
            color: #1e8449;
        }

        .alert i {
            margin-right: 10px;
        }

        .form-group {
            margin-bottom: 25px;
        }

        label {
            display: block;
            margin-bottom: 8px;
            color: #555;
            font-weight: 500;
            font-size: 14px;
        }

        .passkey-input-group {
            display: flex;
            gap: 10px;
            justify-content: center;
        }

        .passkey-input {
            width: 70px;
            height: 60px;
            text-align: center;
            font-size: 20px;
            font-weight: bold;
            text-transform: uppercase;
            border: 2px solid #ddd;
            border-radius: 10px;
            transition: all 0.3s;
            letter-spacing: 2px;
        }

        .passkey-input:focus {
            outline: none;
            border-color: #667eea;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
            transform: scale(1.05);
        }

        .passkey-input::placeholder {
            color: #ccc;
        }

        .or-divider {
            text-align: center;
            margin: 20px 0;
            color: #999;
            font-size: 12px;
        }

        .full-passkey-input {
            width: 100%;
            padding: 15px;
            font-size: 16px;
            text-transform: uppercase;
            letter-spacing: 2px;
            border: 2px solid #ddd;
            border-radius: 10px;
            transition: all 0.3s;
            text-align: center;
        }

        .full-passkey-input:focus {
            outline: none;
            border-color: #667eea;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
        }

        .submit-btn {
            width: 100%;
            padding: 15px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            border-radius: 10px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
        }

        .submit-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 20px rgba(102, 126, 234, 0.4);
        }

        .submit-btn:active {
            transform: translateY(0);
        }

        .submit-btn:disabled {
            background: #ccc;
            cursor: not-allowed;
            transform: none;
        }

        .info-box {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 10px;
            margin-top: 25px;
            font-size: 13px;
            color: #666;
            line-height: 1.6;
        }

        .info-box h3 {
            color: #333;
            font-size: 14px;
            margin-bottom: 10px;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .info-box ul {
            margin-left: 20px;
            margin-top: 10px;
        }

        .info-box li {
            margin-bottom: 5px;
        }

        .help-text {
            text-align: center;
            margin-top: 20px;
            font-size: 13px;
            color: #999;
        }

        .help-text a {
            color: #667eea;
            text-decoration: none;
            font-weight: 500;
        }

        .help-text a:hover {
            text-decoration: underline;
        }

        @media (max-width: 600px) {
            .container {
                padding: 30px 20px;
            }

            .passkey-input {
                width: 55px;
                height: 50px;
                font-size: 18px;
            }

            h1 {
                font-size: 24px;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="icon-container">
            <i class="fas fa-key"></i>
        </div>

        <h1>🔐 Special Access</h1>
        <p class="subtitle">
            Enter your unique passkey to access the site during maintenance mode.<br>
            This access will remain active for the duration of the maintenance.
        </p>

        <?php if ($error): ?>
            <div class="alert alert-error">
                <i class="fas fa-exclamation-circle"></i>
                <?php echo htmlspecialchars($error); ?>
            </div>
        <?php endif; ?>

        <?php if ($success): ?>
            <div class="alert alert-success">
                <i class="fas fa-check-circle"></i>
                <?php echo htmlspecialchars($success); ?>
            </div>
        <?php endif; ?>

        <form method="POST" id="passkeyForm">
            <div class="form-group">
                <label>Enter your passkey (Format: XXXX-XXXX-XXXX-XXXX)</label>
                <input 
                    type="text" 
                    name="passkey" 
                    id="passkeyInput"
                    class="full-passkey-input" 
                    placeholder="XXXX-XXXX-XXXX-XXXX"
                    maxlength="19"
                    autocomplete="off"
                    required
                    <?php echo $success ? 'disabled' : ''; ?>
                >
            </div>

            <button type="submit" class="submit-btn" <?php echo $success ? 'disabled' : ''; ?>>
                <i class="fas fa-unlock"></i>
                <?php echo $success ? 'Access Granted' : 'Verify Passkey'; ?>
            </button>
        </form>

        <div class="info-box">
            <h3><i class="fas fa-info-circle"></i> Important Information</h3>
            <ul>
                <li>Your passkey is unique and should not be shared</li>
                <li>Only one active session per passkey is allowed</li>
                <li>Access expires when maintenance mode ends</li>
                <li>Admin can revoke access at any time</li>
                <li>All activity is logged for security</li>
            </ul>
        </div>

        <div class="help-text">
            Don't have a passkey? Contact the administrator
        </div>
    </div>

    <script>
        // Auto-format passkey input
        const passkeyInput = document.getElementById('passkeyInput');
        
        passkeyInput.addEventListener('input', function(e) {
            let value = e.target.value.replace(/[^A-Z0-9]/gi, '').toUpperCase();
            let formatted = '';
            
            for (let i = 0; i < value.length && i < 16; i++) {
                if (i > 0 && i % 4 === 0) {
                    formatted += '-';
                }
                formatted += value[i];
            }
            
            e.target.value = formatted;
        });

        // Prevent form spam
        const form = document.getElementById('passkeyForm');
        form.addEventListener('submit', function(e) {
            const button = form.querySelector('button[type="submit"]');
            button.disabled = true;
            button.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Verifying...';
        });

        // Focus on input
        if (!document.querySelector('.alert-success')) {
            passkeyInput.focus();
        }
    </script>
</body>
</html>
