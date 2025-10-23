<?php
/**
 * User Response Check Portal
 * Allows users to check admin responses to their contact submissions
 */

require_once 'includes/config.php';
require_once 'includes/db_connect.php';

// Get parameters
$submission_id = $_GET['id'] ?? '';
$email = $_GET['email'] ?? '';
$access_code = $_GET['code'] ?? '';

// Handle form submission for checking status
if ($_POST['email'] ?? false) {
    $check_email = trim($_POST['email']);
    $check_id = trim($_POST['submission_id'] ?? '');
    
    if ($check_email) {
        $redirect_params = ['email' => $check_email];
        if ($check_id) {
            $redirect_params['id'] = $check_id;
        }
        
        header("Location: check-response.php?" . http_build_query($redirect_params));
        exit;
    }
}

$submission = null;
$submissions = null;
$error_message = '';

// Initialize variables safely
$submission_id = $submission_id ?: '';
$email = $email ?: '';

// Debug: Uncomment these lines to debug the logic flow
// error_log("Debug: submission_id = '$submission_id', email = '$email'");
// error_log("Debug: submission = " . ($submission ? 'SET' : 'NULL'));
// error_log("Debug: submissions = " . ($submissions ? 'SET (' . count($submissions) . ' items)' : 'NULL'));
// error_log("Debug: error_message = '$error_message'");

// Verify access and fetch submission(s)
if ($submission_id && $email) {
    // Search by both email and submission ID
    try {
        $db = getDB();
        $stmt = $db->prepare("
            SELECT cs.*, u.username as admin_name
            FROM contact_submissions cs
            LEFT JOIN users u ON cs.admin_id = u.id
            WHERE cs.id = ? AND cs.email = ?
        ");
        $stmt->execute([$submission_id, $email]);
        $submission = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$submission) {
            $submission = null; // Ensure it's null, not false
            $error_message = "No submission found with ID #{$submission_id} and email address '{$email}'. Please check your details and try again.";
        }
    } catch (Exception $e) {
        $submission = null;
        $error_message = "Error retrieving submission details. Please try again later.";
        error_log("Response check error: " . $e->getMessage());
    }
} elseif ($email && !$submission_id) {
    // Search by email only - show all submissions for this email
    try {
        $db = getDB();
        
        // First try exact email match
        $stmt = $db->prepare("
            SELECT cs.*, u.username as admin_name
            FROM contact_submissions cs
            LEFT JOIN users u ON cs.admin_id = u.id
            WHERE cs.email = ?
            ORDER BY cs.created_at DESC
            LIMIT 10
        ");
        $stmt->execute([$email]);
        $submissions = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // If no exact match found, try case-insensitive search
        if (empty($submissions)) {
            $stmt = $db->prepare("
                SELECT cs.*, u.username as admin_name
                FROM contact_submissions cs
                LEFT JOIN users u ON cs.admin_id = u.id
                WHERE LOWER(cs.email) = LOWER(?)
                ORDER BY cs.created_at DESC
                LIMIT 10
            ");
            $stmt->execute([$email]);
            $submissions = $stmt->fetchAll(PDO::FETCH_ASSOC);
        }
        
        // If still no match, suggest similar emails
        if (empty($submissions)) {
            $submissions = null; // Ensure it's null, not empty array
            $stmt = $db->prepare("
                SELECT DISTINCT email
                FROM contact_submissions 
                WHERE email LIKE ? 
                ORDER BY email
                LIMIT 5
            ");
            $email_pattern = '%' . substr($email, 0, strpos($email, '@')) . '%';
            $stmt->execute([$email_pattern]);
            $similar_emails = $stmt->fetchAll(PDO::FETCH_COLUMN);
            
            if (!empty($similar_emails)) {
                $error_message = "No submissions found for '{$email}'. Did you mean one of these: " . implode(', ', $similar_emails) . "?";
            } else {
                $error_message = "No submissions found for email address '{$email}'. Please check your email address and try again.";
            }
        } else {
            // If only one submission, show it directly
            if (count($submissions) === 1) {
                $submission = $submissions[0];
                $submissions = null; // Clear submissions to show single view
            }
            // If multiple submissions, keep $submissions set and $submission null
        }
    } catch (Exception $e) {
        $submissions = null;
        $submission = null;
        $error_message = "Error retrieving submissions. Please try again later.";
        error_log("Response check error: " . $e->getMessage());
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Check Response Status - TechCompare</title>
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="css/theme.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        body {
            padding-top: 80px; /* Add space for fixed navbar */
            background: #f5f7fa;
        }
        
        .response-portal {
            max-width: 800px;
            margin: 50px auto;
            padding: 20px;
            background: white;
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
        }
        
        .portal-header {
            text-align: center;
            margin-bottom: 30px;
            padding: 30px 20px 20px;
            border-bottom: 2px solid #f0f0f0;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border-radius: 15px 15px 0 0;
            margin: -20px -20px 30px -20px;
        }
        
        .portal-header h1 {
            color: white;
            margin-bottom: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 15px;
            font-size: 2rem;
        }
        
        .portal-header p {
            color: rgba(255, 255, 255, 0.95);
            font-size: 1.1rem;
        }
        
        .check-form {
            background: #f8f9fa;
            padding: 25px;
            border-radius: 10px;
            margin-bottom: 30px;
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: #333;
        }
        
        .form-group input {
            width: 100%;
            padding: 12px 15px;
            border: 2px solid #e0e0e0;
            border-radius: 8px;
            font-size: 1rem;
            transition: border-color 0.3s;
        }
        
        .form-group input:focus {
            outline: none;
            border-color: #667eea;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
        }
        
        .btn-check {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 12px 30px;
            border: none;
            border-radius: 8px;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            transition: transform 0.2s;
            width: 100%;
        }
        
        .btn-check:hover {
            transform: translateY(-2px);
        }
        
        .submission-details {
            background: #fff;
            border: 2px solid #e9ecef;
            border-radius: 10px;
            overflow: hidden;
            margin-bottom: 20px;
        }
        
        .detail-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 20px;
            display: flex;
            align-items: center;
            justify-content: between;
        }
        
        .detail-content {
            padding: 25px;
        }
        
        .status-badge {
            padding: 8px 15px;
            border-radius: 20px;
            font-weight: 600;
            text-transform: uppercase;
            font-size: 0.8rem;
            letter-spacing: 1px;
        }
        
        .status-new { background: #fee; color: #dc3545; }
        .status-in_progress { background: #fff3cd; color: #f57c00; }
        .status-resolved { background: #e8f5e8; color: #388e3c; }
        .status-closed { background: #f5f5f5; color: #666; }
        
        .priority-badge {
            padding: 6px 12px;
            border-radius: 15px;
            font-weight: 500;
            font-size: 0.8rem;
        }
        
        .priority-urgent { background: #ffe6e6; color: #d63031; }
        .priority-high { background: #fff3e0; color: #e17055; }
        .priority-medium { background: #e8f4fd; color: #0984e3; }
        .priority-low { background: #e8f5e8; color: #00b894; }
        
        .response-section {
            background: #f8fff8;
            border: 2px solid #c8e6c9;
            border-radius: 10px;
            padding: 25px;
            margin-top: 20px;
        }
        
        .response-section h3 {
            color: #2e7d32;
            margin-bottom: 15px;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .response-content {
            background: white;
            padding: 20px;
            border-radius: 8px;
            border-left: 4px solid #4caf50;
            line-height: 1.6;
        }
        
        .error-message {
            background: #f8d7da;
            color: #721c24;
            padding: 15px;
            border-radius: 8px;
            border: 1px solid #f5c6cb;
            margin-bottom: 20px;
        }
        
        .success-message {
            background: #d1ecf1;
            color: #0c5460;
            padding: 15px;
            border-radius: 8px;
            border: 1px solid #bee5eb;
            margin-bottom: 20px;
        }
        
        .submission-meta {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
            margin-bottom: 20px;
        }
        
        .meta-item {
            background: #f8f9fa;
            padding: 15px;
            border-radius: 8px;
            border-left: 4px solid #667eea;
        }
        
        .meta-label {
            font-weight: 600;
            color: #555;
            font-size: 0.9rem;
            margin-bottom: 5px;
        }
        
        .meta-value {
            color: #333;
            font-size: 1rem;
        }
        
        .original-message {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 8px;
            border-left: 4px solid #6c757d;
            margin: 20px 0;
        }
        
        .back-link {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            color: #667eea;
            text-decoration: none;
            font-weight: 500;
            margin-top: 20px;
        }
        
        .back-link:hover {
            text-decoration: underline;
        }
        
        @media (max-width: 768px) {
            body {
                padding-top: 70px; /* Adjust for smaller navbar on mobile */
            }
            
            .response-portal {
                margin: 20px;
                padding: 15px;
            }
            
            .portal-header h1 {
                font-size: 1.5rem;
                flex-direction: column;
                gap: 10px;
            }
            
            .submission-meta {
                grid-template-columns: 1fr;
            }
        }
        
        /* Submissions List Styles */
        .submissions-list {
            max-width: 900px;
            margin: 0 auto;
            padding: 20px;
        }
        
        .submission-summary {
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        
        .submission-summary:hover {
            box-shadow: 0 5px 20px rgba(102, 126, 234, 0.2);
        }
        
        @media (max-width: 768px) {
            .submissions-list {
                padding: 10px;
            }
            
            .submission-summary div[style*="grid-template-columns"] {
                grid-template-columns: 1fr !important;
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
                    <a href="contact" class="nav-link">Contact</a>
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
    
    <div class="response-portal">
        <div class="portal-header">
            <h1>
                <i class="fas fa-search"></i>
                Check Response Status
            </h1>
            <p>Track the status of your inquiry and view admin responses</p>
            <div style="margin-top: 15px;">
                <a href="contact" style="color: white; text-decoration: none; padding: 8px 20px; background: rgba(255,255,255,0.2); border-radius: 20px; display: inline-flex; align-items: center; gap: 8px; transition: all 0.3s; border: 1px solid rgba(255,255,255,0.3);" onmouseover="this.style.background='rgba(255,255,255,0.3)'" onmouseout="this.style.background='rgba(255,255,255,0.2)'">
                    <i class="fas fa-arrow-left"></i>
                    Back to Contact
                </a>
            </div>
        </div>
        
        <?php if (!$submission && !isset($submissions)): ?>
            <div class="check-form">
                <h3 style="margin-bottom: 20px;">
                    <i class="fas fa-envelope-open"></i>
                    Enter Your Details
                </h3>
                
                <?php if ($error_message): ?>
                    <div class="error-message">
                        <i class="fas fa-exclamation-triangle"></i>
                        <?php echo htmlspecialchars($error_message); ?>
                    </div>
                <?php endif; ?>
                
                <form method="POST">
                    <div class="form-group">
                        <label for="email">
                            <i class="fas fa-envelope"></i>
                            Email Address *
                        </label>
                        <input type="email" id="email" name="email" 
                               value="<?php echo htmlspecialchars($email); ?>" 
                               placeholder="Enter the email you used to contact us" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="submission_id">
                            <i class="fas fa-hashtag"></i>
                            Submission ID (Optional)
                        </label>
                        <input type="text" id="submission_id" name="submission_id" 
                               value="<?php echo htmlspecialchars($submission_id); ?>"
                               placeholder="Enter your submission ID if you have it">
                        <small style="color: #666; font-size: 0.9rem; display: block; margin-top: 5px;">
                            💡 Leave empty to see all your submissions
                        </small>
                    </div>
                    
                    <button type="submit" class="btn-check">
                        <i class="fas fa-search"></i>
                        Check Status
                    </button>
                </form>
                
                <div style="margin-top: 25px; padding: 20px; background: #f8f9fa; border-radius: 8px; border-left: 4px solid #28a745;">
                    <h4 style="margin: 0 0 15px 0; color: #155724;">
                        <i class="fas fa-lightbulb"></i>
                        Try the Demo
                    </h4>
                    <p style="margin: 0 0 15px 0; color: #155724; font-size: 0.95rem;">
                        Want to see how this works? Try these demo submissions:
                    </p>
                    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 15px;">
                        <button type="button" class="demo-btn" onclick="fillDemo('john.doe@email.com', '')" 
                                style="background: #e3f2fd; border: 1px solid #90caf9; padding: 10px; border-radius: 5px; cursor: pointer; text-align: left;">
                            <strong>📧 john.doe@email.com</strong><br>
                            <small style="color: #666;">Multiple submissions</small>
                        </button>
                        <button type="button" class="demo-btn" onclick="fillDemo('mike.j@email.com', '3')" 
                                style="background: #e8f5e8; border: 1px solid #a5d6a7; padding: 10px; border-radius: 5px; cursor: pointer; text-align: left;">
                            <strong>📧 mike.j@email.com</strong><br>
                            <small style="color: #666;">ID: #3 - Resolved</small>
                        </button>
                    </div>
                </div>
                
                <div style="margin-top: 20px; padding: 15px; background: #e3f2fd; border-radius: 8px;">
                    <h4 style="margin: 0 0 10px 0; color: #1976d2;">
                        <i class="fas fa-info-circle"></i>
                        How to Find Your Submission
                    </h4>
                    <ul style="margin: 0; padding-left: 20px; color: #555;">
                        <li>Enter the email address you used to contact us</li>
                        <li>If you have multiple submissions, enter the submission ID (optional)</li>
                        <li>Check your email for submission confirmation with tracking details</li>
                        <li>Your submission ID was provided when you first contacted us</li>
                    </ul>
                    
                    <div style="margin-top: 15px; padding: 10px; background: #fff3cd; border-radius: 5px; border-left: 4px solid #ffc107;">
                        <strong style="color: #856404;">💡 Tip:</strong>
                        <span style="color: #856404;">If you can't find your submission ID, just enter your email address and we'll show all your recent inquiries.</span>
                    </div>
                </div>
            </div>
        <?php elseif (isset($submissions) && count($submissions) > 1): ?>
            <!-- Multiple submissions found - show list -->
            <div class="submissions-list">
                <h2 style="text-align: center; margin-bottom: 30px; color: #333;">
                    <i class="fas fa-list"></i>
                    Your Submissions for <?php echo htmlspecialchars($email); ?>
                </h2>
                
                <div style="background: #e8f5e8; padding: 15px; border-radius: 8px; margin-bottom: 25px; text-align: center;">
                    <i class="fas fa-check-circle" style="color: #28a745; margin-right: 10px;"></i>
                    Found <?php echo count($submissions); ?> submission<?php echo count($submissions) > 1 ? 's' : ''; ?> for your email address.
                </div>
                
                <?php foreach ($submissions as $sub): ?>
                <div class="submission-summary" style="background: white; border: 2px solid #e9ecef; border-radius: 10px; margin-bottom: 20px; overflow: hidden; transition: all 0.3s ease;" 
                     onmouseover="this.style.borderColor='#667eea'; this.style.transform='translateY(-2px)'"
                     onmouseout="this.style.borderColor='#e9ecef'; this.style.transform='translateY(0)'">
                    
                    <div style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 15px; display: flex; justify-content: between; align-items: center;">
                        <div>
                            <h3 style="margin: 0; font-size: 1.1rem;">
                                <i class="fas fa-ticket-alt"></i>
                                #<?php echo $sub['id']; ?> - <?php echo htmlspecialchars($sub['subject']); ?>
                            </h3>
                            <small style="opacity: 0.9;">
                                <?php echo date('M j, Y \a\t g:i A', strtotime($sub['created_at'])); ?>
                            </small>
                        </div>
                        <span class="status-badge status-<?php echo $sub['status']; ?>" style="margin-left: 15px;">
                            <?php echo ucfirst(str_replace('_', ' ', $sub['status'])); ?>
                        </span>
                    </div>
                    
                    <div style="padding: 20px;">
                        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(150px, 1fr)); gap: 15px; margin-bottom: 15px;">
                            <div>
                                <strong style="color: #555; display: block; margin-bottom: 5px;">Priority:</strong>
                                <span class="priority-badge priority-<?php echo $sub['priority']; ?>">
                                    <?php echo ucfirst($sub['priority']); ?>
                                </span>
                            </div>
                            <?php if ($sub['phone']): ?>
                            <div>
                                <strong style="color: #555; display: block; margin-bottom: 5px;">Phone:</strong>
                                <?php echo htmlspecialchars($sub['phone']); ?>
                            </div>
                            <?php endif; ?>
                            <?php if ($sub['admin_response']): ?>
                            <div>
                                <strong style="color: #555; display: block; margin-bottom: 5px;">Response:</strong>
                                <span style="color: #28a745; font-weight: 600;">
                                    <i class="fas fa-check-circle"></i> Responded
                                </span>
                            </div>
                            <?php else: ?>
                            <div>
                                <strong style="color: #555; display: block; margin-bottom: 5px;">Response:</strong>
                                <span style="color: #ffc107; font-weight: 600;">
                                    <i class="fas fa-clock"></i> Pending
                                </span>
                            </div>
                            <?php endif; ?>
                        </div>
                        
                        <p style="color: #666; margin: 10px 0; line-height: 1.5;">
                            <strong>Message:</strong> 
                            <?php 
                            $message = htmlspecialchars($sub['message']);
                            echo strlen($message) > 150 ? substr($message, 0, 150) . '...' : $message; 
                            ?>
                        </p>
                        
                        <div style="text-align: center; margin-top: 15px;">
                            <a href="?id=<?php echo $sub['id']; ?>&email=<?php echo urlencode($email); ?>" 
                               class="btn-primary" style="text-decoration: none; padding: 8px 20px; border-radius: 5px; display: inline-block;">
                                <i class="fas fa-eye"></i>
                                View Full Details
                            </a>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
                
                <div style="text-align: center; margin-top: 30px;">
                    <a href="check-response" class="back-link">
                        <i class="fas fa-search"></i>
                        Search Again
                    </a>
                    <span style="margin: 0 20px; color: #ccc;">|</span>
                    <a href="contact.html?action=new-inquiry" class="back-link">
                        <i class="fas fa-envelope"></i>
                        Submit New Inquiry
                    </a>
                </div>
            </div>
        <?php elseif ($submission): ?>
            <div class="submission-details">
                <div class="detail-header">
                    <div>
                        <h2 style="margin: 0; display: flex; align-items: center; gap: 10px;">
                            <i class="fas fa-ticket-alt"></i>
                            Submission #<?php echo $submission['id']; ?>
                        </h2>
                        <p style="margin: 5px 0 0 0; opacity: 0.9;">
                            Submitted on <?php echo date('F j, Y \a\t g:i A', strtotime($submission['created_at'])); ?>
                        </p>
                    </div>
                    <div style="text-align: right;">
                        <span class="status-badge status-<?php echo $submission['status']; ?>">
                            <?php echo ucfirst(str_replace('_', ' ', $submission['status'])); ?>
                        </span>
                    </div>
                </div>
                
                <div class="detail-content">
                    <div class="submission-meta">
                        <div class="meta-item">
                            <div class="meta-label">Name</div>
                            <div class="meta-value"><?php echo htmlspecialchars($submission['name']); ?></div>
                        </div>
                        <div class="meta-item">
                            <div class="meta-label">Email</div>
                            <div class="meta-value"><?php echo htmlspecialchars($submission['email']); ?></div>
                        </div>
                        <div class="meta-item">
                            <div class="meta-label">Priority</div>
                            <div class="meta-value">
                                <span class="priority-badge priority-<?php echo $submission['priority']; ?>">
                                    <?php echo ucfirst($submission['priority']); ?>
                                </span>
                            </div>
                        </div>
                        <?php if ($submission['phone']): ?>
                        <div class="meta-item">
                            <div class="meta-label">Phone</div>
                            <div class="meta-value"><?php echo htmlspecialchars($submission['phone']); ?></div>
                        </div>
                        <?php endif; ?>
                    </div>
                    
                    <h3 style="color: #333; margin-bottom: 15px;">
                        <i class="fas fa-comment-dots"></i>
                        Subject: <?php echo htmlspecialchars($submission['subject']); ?>
                    </h3>
                    
                    <div class="original-message">
                        <h4 style="margin: 0 0 10px 0; color: #555;">
                            <i class="fas fa-envelope"></i>
                            Your Original Message:
                        </h4>
                        <p style="margin: 0; line-height: 1.6;">
                            <?php echo nl2br(htmlspecialchars($submission['message'])); ?>
                        </p>
                    </div>
                    
                    <?php if ($submission['admin_response']): ?>
                        <div class="response-section">
                            <h3>
                                <i class="fas fa-reply"></i>
                                Admin Response
                                <?php if ($submission['admin_name']): ?>
                                    <small style="font-weight: normal; color: #666;">
                                        by <?php echo htmlspecialchars($submission['admin_name']); ?>
                                    </small>
                                <?php endif; ?>
                            </h3>
                            
                            <div class="response-content">
                                <?php echo nl2br(htmlspecialchars($submission['admin_response'])); ?>
                            </div>
                            
                            <?php if ($submission['updated_at'] != $submission['created_at']): ?>
                                <p style="margin: 15px 0 0 0; color: #666; font-size: 0.9rem;">
                                    <i class="fas fa-clock"></i>
                                    Responded on <?php echo date('F j, Y \a\t g:i A', strtotime($submission['updated_at'])); ?>
                                </p>
                            <?php endif; ?>
                        </div>
                    <?php else: ?>
                        <div style="background: #fff3cd; border: 2px solid #ffeaa7; padding: 20px; border-radius: 10px; text-align: center;">
                            <h3 style="margin: 0 0 10px 0; color: #856404;">
                                <i class="fas fa-hourglass-half"></i>
                                Response Pending
                            </h3>
                            <p style="margin: 0; color: #856404;">
                                We're reviewing your inquiry and will respond soon. Thank you for your patience!
                            </p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
            
            <div style="text-align: center; margin-top: 30px;">
                <a href="contact.html?action=new-inquiry" class="back-link">
                    <i class="fas fa-envelope"></i>
                    Submit New Inquiry
                </a>
                <span style="margin: 0 20px; color: #ccc;">|</span>
                <a href="contact.html?action=check-response" class="back-link">
                    <i class="fas fa-search"></i>
                    Check Another Response
                </a>
                <span style="margin: 0 20px; color: #ccc;">|</span>
                <a href="index" class="back-link">
                    <i class="fas fa-home"></i>
                    Back to Home
                </a>
            </div>
        <?php else: ?>
            <!-- Fallback: Show form again with error message -->
            <div class="check-form">
                <h3 style="margin-bottom: 20px;">
                    <i class="fas fa-envelope-open"></i>
                    Enter Your Details
                </h3>
                
                <?php if ($error_message): ?>
                    <div class="error-message">
                        <i class="fas fa-exclamation-triangle"></i>
                        <?php echo htmlspecialchars($error_message); ?>
                    </div>
                <?php endif; ?>
                
                <form method="POST">
                    <div class="form-group">
                        <label for="email">
                            <i class="fas fa-envelope"></i>
                            Email Address *
                        </label>
                        <input type="email" id="email" name="email" 
                               value="<?php echo htmlspecialchars($email); ?>" 
                               placeholder="Enter the email you used to contact us" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="submission_id">
                            <i class="fas fa-hashtag"></i>
                            Submission ID (Optional)
                        </label>
                        <input type="text" id="submission_id" name="submission_id" 
                               value="<?php echo htmlspecialchars($submission_id); ?>"
                               placeholder="Enter your submission ID if you have it">
                        <small style="color: #666; font-size: 0.9rem; display: block; margin-top: 5px;">
                            💡 Leave empty to see all your submissions
                        </small>
                    </div>
                    
                    <button type="submit" class="btn-check">
                        <i class="fas fa-search"></i>
                        Try Again
                    </button>
                </form>
                
                <div style="margin-top: 25px; padding: 20px; background: #f8f9fa; border-radius: 8px; border-left: 4px solid #28a745;">
                    <h4 style="margin: 0 0 15px 0; color: #155724;">
                        <i class="fas fa-lightbulb"></i>
                        Try the Demo
                    </h4>
                    <p style="margin: 0 0 15px 0; color: #155724; font-size: 0.95rem;">
                        Want to see how this works? Try these demo submissions:
                    </p>
                    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 15px;">
                        <button type="button" class="demo-btn" onclick="fillDemo('john.doe@email.com', '')" 
                                style="background: #e3f2fd; border: 1px solid #90caf9; padding: 10px; border-radius: 5px; cursor: pointer; text-align: left;">
                            <strong>📧 john.doe@email.com</strong><br>
                            <small style="color: #666;">Multiple submissions</small>
                        </button>
                        <button type="button" class="demo-btn" onclick="fillDemo('mike.j@email.com', '3')" 
                                style="background: #e8f5e8; border: 1px solid #a5d6a7; padding: 10px; border-radius: 5px; cursor: pointer; text-align: left;">
                            <strong>📧 mike.j@email.com</strong><br>
                            <small style="color: #666;">ID: #3 - Resolved</small>
                        </button>
                    </div>
                </div>

                <div style="margin-top: 20px; padding: 15px; background: #e3f2fd; border-radius: 8px;">
                    <h4 style="margin: 0 0 10px 0; color: #1976d2;">
                        <i class="fas fa-info-circle"></i>
                        Troubleshooting
                    </h4>
                    <ul style="margin: 0; padding-left: 20px; color: #555;">
                        <li>Double-check your email address for typos</li>
                        <li>Try searching without the submission ID</li>
                        <li>Check if you used a different email address</li>
                        <li>Contact us if you still can't find your submission</li>
                    </ul>
                </div>
            </div>
        <?php endif; ?>
    </div>
    
    <?php include 'templates/footer.html'; ?>
    
    <script>
        // Demo function to fill form with sample data
        function fillDemo(email, submissionId = '') {
            document.getElementById('email').value = email;
            document.getElementById('submission_id').value = submissionId;
            
            // Add visual feedback
            const emailField = document.getElementById('email');
            const idField = document.getElementById('submission_id');
            
            emailField.style.background = '#e8f5e8';
            idField.style.background = submissionId ? '#e8f5e8' : '#fff3cd';
            
            setTimeout(() => {
                emailField.style.background = '';
                idField.style.background = '';
            }, 2000);
            
            // Show helpful message
            const message = submissionId 
                ? `Demo data loaded! This will show submission #${submissionId} for ${email}`
                : `Demo data loaded! This will show all submissions for ${email}`;
            
            showNotification(message, 'success');
        }
        
        // Show notification function
        function showNotification(message, type = 'info') {
            // Remove any existing notifications
            const existing = document.querySelector('.notification');
            if (existing) {
                existing.remove();
            }
            
            const notification = document.createElement('div');
            notification.className = 'notification';
            notification.style.cssText = `
                position: fixed;
                top: 20px;
                right: 20px;
                padding: 15px 20px;
                border-radius: 8px;
                color: white;
                font-weight: 500;
                z-index: 1000;
                max-width: 400px;
                box-shadow: 0 4px 12px rgba(0,0,0,0.2);
                animation: slideInRight 0.3s ease-out;
                background: ${type === 'success' ? 'linear-gradient(135deg, #28a745, #20c997)' : 
                           type === 'error' ? 'linear-gradient(135deg, #dc3545, #c82333)' : 
                           'linear-gradient(135deg, #17a2b8, #138496)'};
            `;
            
            notification.innerHTML = `
                <div style="display: flex; align-items: center; gap: 10px;">
                    <i class="fas fa-${type === 'success' ? 'check-circle' : type === 'error' ? 'exclamation-triangle' : 'info-circle'}"></i>
                    <span>${message}</span>
                    <button onclick="this.parentElement.parentElement.remove()" 
                            style="background: none; border: none; color: white; font-size: 18px; cursor: pointer; margin-left: auto;">
                        ×
                    </button>
                </div>
            `;
            
            // Add animation styles if not present
            if (!document.querySelector('#notification-styles')) {
                const style = document.createElement('style');
                style.id = 'notification-styles';
                style.textContent = `
                    @keyframes slideInRight {
                        from { opacity: 0; transform: translateX(100%); }
                        to { opacity: 1; transform: translateX(0); }
                    }
                `;
                document.head.appendChild(style);
            }
            
            document.body.appendChild(notification);
            
            // Auto remove after 5 seconds
            setTimeout(() => {
                if (notification.parentElement) {
                    notification.style.animation = 'slideInRight 0.3s ease-out reverse';
                    setTimeout(() => notification.remove(), 300);
                }
            }, 5000);
        }
        
        // Add hover effects to demo buttons
        document.addEventListener('DOMContentLoaded', function() {
            const demoBtns = document.querySelectorAll('.demo-btn');
            demoBtns.forEach(btn => {
                btn.addEventListener('mouseenter', function() {
                    this.style.transform = 'translateY(-2px)';
                    this.style.boxShadow = '0 4px 12px rgba(0,0,0,0.15)';
                });
                
                btn.addEventListener('mouseleave', function() {
                    this.style.transform = 'translateY(0)';
                    this.style.boxShadow = 'none';
                });
            });
        });
    </script>
    
    <!-- Dark Mode / Light Mode Script -->
    <script src="js/theme.js"></script>
</body>
</html>