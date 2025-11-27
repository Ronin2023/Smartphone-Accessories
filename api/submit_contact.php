<?php
/**
 * Contact Form Submission API
 * 
 * Handles contact form submissions and stores them in the database
 * for admin review and response.
 */

// Include required files first
require_once '../includes/config.php';
require_once '../includes/db_connect.php';
require_once '../includes/functions.php';

// Check if this is an AJAX request or direct browser access
$isAjaxRequest = (
    (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && 
     strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') ||
    (!empty($_SERVER['HTTP_ACCEPT']) && 
     strpos($_SERVER['HTTP_ACCEPT'], 'application/json') !== false) ||
    (!empty($_SERVER['CONTENT_TYPE']) && 
     strpos($_SERVER['CONTENT_TYPE'], 'application/json') !== false)
);

// If not AJAX and accessed directly via browser, show HTML page instead of JSON
if (!$isAjaxRequest && $_SERVER['REQUEST_METHOD'] === 'GET') {
    showDirectAccessPage();
    exit();
}

// Set headers for API response ONLY for AJAX requests
if ($isAjaxRequest) {
    header('Content-Type: application/json');
    header('Access-Control-Allow-Origin: *');
    header('Access-Control-Allow-Methods: POST');
    header('Access-Control-Allow-Headers: Content-Type');
}

// Get database connection
// The Database class will automatically redirect to connection-error.php if database is unavailable
// This applies to ALL request types (AJAX and regular forms) for consistent user experience
try {
    $pdo = getDB();
} catch (Exception $e) {
    // This should not be reached as getDB() will trigger Database class redirect
    // But included as fallback
    error_log("Fallback error in submit_contact.php: " . $e->getMessage());
    
    // Detect the correct base path
    $currentPath = $_SERVER['REQUEST_URI'] ?? '/';
    $basePath = '/';
    if (strpos($currentPath, '/Smartphone-Accessories/') !== false) {
        $basePath = '/Smartphone-Accessories/';
    }
    
    // Store error information in session
    if (session_status() == PHP_SESSION_NONE) {
        session_start();
    }
    $_SESSION['connection_error'] = [
        'type' => 'database',
        'message' => 'Database connection failed during form submission',
        'timestamp' => time(),
        'referring_page' => $_SERVER['HTTP_REFERER'] ?? $basePath . 'contact.html'
    ];
    
    // Redirect to connection error page for ALL request types
    header("Location: " . $basePath . "connection-error.php");
    exit();
}

// Only allow POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    if ($isAjaxRequest) {
        http_response_code(405);
        echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    } else {
        showDirectAccessPage();
    }
    exit();
}

// Validate CSRF token for POST requests
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$csrfToken = $_POST['csrf_token'] ?? '';
if (!validateCSRFToken($csrfToken)) {
    if ($isAjaxRequest) {
        http_response_code(403);
        echo json_encode([
            'success' => false,
            'error' => 'Invalid security token',
            'message' => 'Your session has expired. Please refresh the page and try again.',
            'code' => 'CSRF_VALIDATION_FAILED'
        ]);
    } else {
        showResultPage(false, 'Invalid security token. Please refresh the page and try again.');
    }
    exit();
}

try {
    // Validate and sanitize input data
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $subject = trim($_POST['subject'] ?? '');
    $message = trim($_POST['message'] ?? '');
    $priority = $_POST['priority'] ?? 'medium';
    
    // Validation
    $errors = [];
    
    if (empty($name)) {
        $errors[] = 'Name is required';
    } elseif (strlen($name) > 100) {
        $errors[] = 'Name must be less than 100 characters';
    }
    
    if (empty($email)) {
        $errors[] = 'Email is required';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Please enter a valid email address';
    } elseif (strlen($email) > 100) {
        $errors[] = 'Email must be less than 100 characters';
    }
    
    if (!empty($phone) && strlen($phone) > 20) {
        $errors[] = 'Phone number must be less than 20 characters';
    }
    
    if (empty($subject)) {
        $errors[] = 'Subject is required';
    } elseif (strlen($subject) > 255) {
        $errors[] = 'Subject must be less than 255 characters';
    }
    
    if (empty($message)) {
        $errors[] = 'Message is required';
    } elseif (strlen($message) > 5000) {
        $errors[] = 'Message must be less than 5000 characters';
    }
    
    if (!in_array($priority, ['low', 'medium', 'high', 'urgent'])) {
        $priority = 'medium'; // Default fallback
    }
    
    // Check for spam patterns
    $spamKeywords = ['viagra', 'casino', 'lottery', 'winner', 'congratulations', 'urgent money'];
    $contentToCheck = strtolower($subject . ' ' . $message);
    
    foreach ($spamKeywords as $keyword) {
        if (strpos($contentToCheck, $keyword) !== false) {
            $errors[] = 'Message contains inappropriate content';
            break;
        }
    }
    
    // Rate limiting - check if same email submitted recently (24-hour limit)
    if (empty($errors)) {
        $stmt = $pdo->prepare("
            SELECT id, created_at FROM contact_submissions 
            WHERE email = ? AND created_at > DATE_SUB(NOW(), INTERVAL 24 HOUR)
            ORDER BY created_at DESC 
            LIMIT 1
        ");
        $stmt->execute([$email]);
        $recentSubmission = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($recentSubmission) {
            // Calculate time remaining until next submission is allowed
            $submissionTime = new DateTime($recentSubmission['created_at']);
            $now = new DateTime();
            
            // Calculate the exact time when next submission is allowed (24 hours from submission)
            $allowedTime = clone $submissionTime;
            $allowedTime->add(new DateInterval('PT24H'));
            
            // Check if current time is before the allowed time
            if ($now < $allowedTime) {
                // Format the allowed time in 12-hour format
                $allowedTimeFormatted = $allowedTime->format('g:i A \o\n M j, Y');
                
                // Debug logging to help identify issues
                error_log("Rate limit debug - Email: {$email}, Submission: {$recentSubmission['created_at']}, Now: " . $now->format('Y-m-d H:i:s') . ", Allowed: " . $allowedTime->format('Y-m-d H:i:s'));
                
                $errors[] = "You can only submit one request per 24 hours. You can request another submission after {$allowedTimeFormatted}. Your previous submission ID was #{$recentSubmission['id']}.";
            }
        }
    }
    
    if (!empty($errors)) {
        if ($isAjaxRequest) {
            echo json_encode([
                'success' => false,
                'message' => implode(', ', $errors)
            ]);
        } else {
            // Show HTML page with error toast
            showResultPage(false, implode(', ', $errors));
        }
        exit();
    }
    
    // Get client information
    $ip_address = $_SERVER['HTTP_X_FORWARDED_FOR'] ?? $_SERVER['REMOTE_ADDR'] ?? 'unknown';
    $user_agent = $_SERVER['HTTP_USER_AGENT'] ?? 'unknown';
    
    // Insert into database
    $stmt = $pdo->prepare("
        INSERT INTO contact_submissions 
        (name, email, phone, subject, message, priority, ip_address, user_agent) 
        VALUES (?, ?, ?, ?, ?, ?, ?, ?)
    ");
    
    $result = $stmt->execute([
        $name,
        $email,
        $phone,
        $subject,
        $message,
        $priority,
        $ip_address,
        $user_agent
    ]);
    
    if ($result) {
        $submission_id = $pdo->lastInsertId();
        
        // Send notification email to admin (optional)
        $admin_email = 'admin@techcompare.com';
        $email_subject = "New Contact Submission - Priority: " . ucfirst($priority);
        $email_body = "
        New contact form submission received:
        
        ID: {$submission_id}
        Name: {$name}
        Email: {$email}
        Phone: {$phone}
        Subject: {$subject}
        Priority: {$priority}
        
        Message:
        {$message}
        
        IP Address: {$ip_address}
        Submitted: " . date('Y-m-d H:i:s') . "
        
        Please log into the admin dashboard to respond.
        ";
        
        // Send email notification (uncomment if mail server is configured)
        // mail($admin_email, $email_subject, $email_body);
        
        // Log the submission
        error_log("Contact submission received: ID {$submission_id}, Email: {$email}, Priority: {$priority}");
        
        // Prepare tracking URL for user
        $tracking_url = SITE_URL . "/check-response.php?id=" . $submission_id . "&email=" . urlencode($email);
        
        if ($isAjaxRequest) {
            echo json_encode([
                'success' => true,
                'message' => 'Thank you for your message! We will get back to you within 24 hours.',
                'submission_id' => $submission_id,
                'tracking_info' => [
                    'url' => $tracking_url,
                    'email' => $email,
                    'instructions' => 'You can check the status of your inquiry anytime using the tracking URL above or by visiting our response portal with your email address.'
                ]
            ]);
        } else {
            // Show HTML page with success toast
            showResultPage(true, 'Thank you for your message! We will get back to you within 24 hours.', $submission_id, $email);
        }
        
    } else {
        throw new Exception('Failed to save submission to database');
    }
    
} catch (PDOException $e) {
    error_log("Contact form database error: " . $e->getMessage());
    
    // Check if this is a connection error
    if (strpos($e->getMessage(), 'connection') !== false || 
        strpos($e->getMessage(), 'server has gone away') !== false || 
        $e->getCode() == 2002 || $e->getCode() == 2006) {
        
        if ($isAjaxRequest) {
            http_response_code(503);
            echo json_encode([
                'success' => false,
                'error' => 'Database connection failed. Please try again later.',
                'message' => 'Service temporarily unavailable',
                'code' => 503,
                'connection_error' => true
            ]);
        } else {
            showResultPage(false, 'Database connection failed. Please try again later.');
        }
    } else {
        if ($isAjaxRequest) {
            echo json_encode([
                'success' => false,
                'message' => 'Database error. Please try again later.'
            ]);
        } else {
            showResultPage(false, 'Database error. Please try again later.');
        }
    }
    
} catch (Exception $e) {
    error_log("Contact form error: " . $e->getMessage());
    
    // Check if this is a database connection exception
    if (strpos($e->getMessage(), 'Database connection not available') !== false) {
        if ($isAjaxRequest) {
            http_response_code(503);
            echo json_encode([
                'success' => false,
                'error' => 'Database connection failed. Please try again later.',
                'message' => 'Service temporarily unavailable',
                'code' => 503,
                'connection_error' => true
            ]);
        } else {
            showResultPage(false, 'Database connection failed. Please try again later.');
        }
    } else {
        if ($isAjaxRequest) {
            echo json_encode([
                'success' => false,
                'message' => 'Something went wrong. Please try again later.'
            ]);
        } else {
            showResultPage(false, 'Something went wrong. Please try again later.');
        }
    }
}

// Function to show HTML page for direct access
function showDirectAccessPage() {
    ?>
    <!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Contact Form API - Smartphone Accessories</title>
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
        <style>
            body {
                font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
                background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
                margin: 0;
                padding: 40px;
                min-height: 100vh;
                display: flex;
                align-items: center;
                justify-content: center;
            }
            .container {
                background: white;
                padding: 40px;
                border-radius: 12px;
                box-shadow: 0 10px 30px rgba(0,0,0,0.1);
                max-width: 500px;
                text-align: center;
            }
            .icon {
                font-size: 48px;
                color: #e74c3c;
                margin-bottom: 20px;
            }
            h1 {
                color: #333;
                margin-bottom: 15px;
            }
            p {
                color: #666;
                margin-bottom: 20px;
                line-height: 1.6;
            }
            .btn {
                display: inline-block;
                background: linear-gradient(135deg, #667eea, #764ba2);
                color: white;
                padding: 12px 24px;
                text-decoration: none;
                border-radius: 6px;
                margin: 10px;
                transition: all 0.3s ease;
            }
            .btn:hover {
                transform: translateY(-2px);
                box-shadow: 0 5px 15px rgba(102, 126, 234, 0.4);
            }
        </style>
    </head>
    <body>
        <div class="container">
            <div class="icon">
                <i class="fas fa-exclamation-triangle"></i>
            </div>
            <h1>Direct API Access</h1>
            <p>This is an API endpoint for form submissions. To submit a contact form, please use our contact page.</p>
            <p>If you're looking to:</p>
            <ul style="text-align: left; color: #666;">
                <li>Submit a contact form - use our contact page</li>
                <li>Check response status - use our response portal</li>
                <li>Access admin panel - use the admin dashboard</li>
            </ul>
            <a href="../contact" class="btn">
                <i class="fas fa-envelope"></i> Contact Form
            </a>
            <a href="../check-response" class="btn">
                <i class="fas fa-search"></i> Check Response
            </a>
        </div>
    </body>
    </html>
    <?php
    return;
}

// Function to show result page with toast notifications
function showResultPage($success, $message, $submissionId = null, $email = null) {
    ?>
    <!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title><?php echo $success ? 'Success' : 'Error'; ?> - Contact Form Submission</title>
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
        <style>
            body {
                font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
                background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
                margin: 0;
                padding: 40px;
                min-height: 100vh;
                display: flex;
                align-items: center;
                justify-content: center;
            }
            .container {
                background: white;
                padding: 40px;
                border-radius: 12px;
                box-shadow: 0 10px 30px rgba(0,0,0,0.1);
                max-width: 600px;
                text-align: center;
            }
            .icon.success {
                font-size: 48px;
                color: #28a745;
                margin-bottom: 20px;
            }
            .icon.error {
                font-size: 48px;
                color: #e74c3c;
                margin-bottom: 20px;
            }
            h1 {
                color: #333;
                margin-bottom: 15px;
            }
            p {
                color: #666;
                margin-bottom: 20px;
                line-height: 1.6;
            }
            .submission-info {
                background: #f8f9fa;
                border: 1px solid #dee2e6;
                border-radius: 8px;
                padding: 20px;
                margin: 20px 0;
                text-align: left;
            }
            .submission-info h3 {
                color: #495057;
                margin-top: 0;
                margin-bottom: 15px;
            }
            .info-item {
                margin-bottom: 10px;
                padding: 8px 12px;
                background: white;
                border-radius: 4px;
                border-left: 4px solid #28a745;
            }
            .info-item strong {
                color: #333;
            }
            .btn {
                display: inline-block;
                background: linear-gradient(135deg, #667eea, #764ba2);
                color: white;
                padding: 12px 24px;
                text-decoration: none;
                border-radius: 6px;
                margin: 10px;
                transition: all 0.3s ease;
            }
            .btn:hover {
                transform: translateY(-2px);
                box-shadow: 0 5px 15px rgba(102, 126, 234, 0.4);
            }
            .btn.success {
                background: linear-gradient(135deg, #28a745, #20c997);
            }
        </style>
    </head>
    <body>
        <div class="container">
            <div class="icon <?php echo $success ? 'success' : 'error'; ?>">
                <i class="fas fa-<?php echo $success ? 'check-circle' : 'exclamation-circle'; ?>"></i>
            </div>
            <h1><?php echo $success ? 'Message Sent Successfully!' : 'Submission Error'; ?></h1>
            <p><?php echo htmlspecialchars($message); ?></p>
            
            <?php if ($success && $submissionId && $email): ?>
            <div class="submission-info">
                <h3><i class="fas fa-info-circle"></i> Important - Save This Information</h3>
                <div class="info-item">
                    <strong>Submission ID:</strong> #<?php echo htmlspecialchars($submissionId); ?>
                </div>
                <div class="info-item">
                    <strong>Email Address:</strong> <?php echo htmlspecialchars($email); ?>
                </div>
                <div class="info-item">
                    <strong>Submitted:</strong> <?php echo date('F j, Y \a\t g:i A'); ?>
                </div>
                <p style="margin-top: 15px; color: #856404; background: #fff3cd; padding: 10px; border-radius: 4px; border-left: 4px solid #ffc107;">
                    <i class="fas fa-exclamation-triangle"></i> <strong>Please note down your Submission ID and Email</strong> for future reference. You can use these to check the status of your inquiry.
                </p>
            </div>
            <?php endif; ?>
            
            <a href="../contact" class="btn">
                <i class="fas fa-envelope"></i> Submit Another Message
            </a>
            
            <?php if ($success): ?>
            <a href="../check-response?email=<?php echo urlencode($email ?? ''); ?>" class="btn success">
                <i class="fas fa-search"></i> Check Response Status
            </a>
            <?php endif; ?>
            
            <a href="../index" class="btn">
                <i class="fas fa-home"></i> Back to Home
            </a>
        </div>

        <script>
            // Toast Notification System (same as contact.html)
            function showToast(message, type = 'info', duration = 8000) {
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

            // Show toast notification based on page type
            <?php if ($success && $submissionId && $email): ?>
            document.addEventListener('DOMContentLoaded', function() {
                showToast('Message submitted successfully! Your submission ID is #<?php echo $submissionId; ?> and email is <?php echo htmlspecialchars($email); ?>. Please note these details for future reference.', 'success', 10000);
            });
            <?php elseif (!$success): ?>
            document.addEventListener('DOMContentLoaded', function() {
                showToast('<?php echo addslashes(htmlspecialchars($message)); ?>', 'error');
            });
            <?php endif; ?>
        </script>
    </body>
    </html>
    <?php
    return;
}
?>