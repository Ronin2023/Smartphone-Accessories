<?php
/**
 * User Dashboard
 * Dashboard for regular users to manage their account and preferences
 */

session_start();
require_once 'includes/config.php';
require_once 'includes/db_connect.php';
require_once 'includes/functions.php';

// Check if user is logged in and is a regular user
if (!isLoggedIn() || !isUser()) {
    redirect('user_login.php');
    exit();
}

$pdo = getDB();
$user_id = $_SESSION['user_id'];

// Get user data
$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

// Get user's contact submissions
$contact_stmt = $pdo->prepare("
    SELECT id, subject, message, status, priority, admin_response, created_at, updated_at, resolved_at
    FROM contact_submissions 
    WHERE email = ? OR user_id = ?
    ORDER BY created_at DESC
");
$contact_stmt->execute([$user['email'], $user_id]);
$contact_submissions = $contact_stmt->fetchAll(PDO::FETCH_ASSOC);

// Get contact statistics
$contact_stats = [
    'total' => count($contact_submissions),
    'pending' => count(array_filter($contact_submissions, fn($c) => in_array($c['status'], ['new', 'in_progress']))),
    'resolved' => count(array_filter($contact_submissions, fn($c) => $c['status'] === 'resolved')),
    'closed' => count(array_filter($contact_submissions, fn($c) => $c['status'] === 'closed'))
];

// Handle profile updates
$message = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    switch($action) {
        case 'update_profile':
            try {
                $first_name = sanitize($_POST['first_name']);
                $last_name = sanitize($_POST['last_name']);
                $email = sanitize($_POST['email']);
                
                if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                    throw new Exception('Invalid email format.');
                }
                
                // Check if email is already taken by another user
                $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ? AND id != ?");
                $stmt->execute([$email, $user_id]);
                if ($stmt->fetch()) {
                    throw new Exception('Email already exists.');
                }
                
                $stmt = $pdo->prepare("
                    UPDATE users 
                    SET first_name = ?, last_name = ?, email = ?, updated_at = NOW() 
                    WHERE id = ?
                ");
                $stmt->execute([$first_name, $last_name, $email, $user_id]);
                
                $_SESSION['user_email'] = $email;
                $_SESSION['user_first_name'] = $first_name;
                $_SESSION['user_last_name'] = $last_name;
                
                $message = 'Profile updated successfully!';
                
                // Refresh user data
                $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
                $stmt->execute([$user_id]);
                $user = $stmt->fetch(PDO::FETCH_ASSOC);
                
            } catch (Exception $e) {
                $error = $e->getMessage();
            }
            break;
            
        case 'change_password':
            try {
                $current_password = $_POST['current_password'];
                $new_password = $_POST['new_password'];
                $confirm_password = $_POST['confirm_password'];
                
                if (!password_verify($current_password, $user['password_hash'])) {
                    throw new Exception('Current password is incorrect.');
                }
                
                if (strlen($new_password) < 6) {
                    throw new Exception('New password must be at least 6 characters long.');
                }
                
                if ($new_password !== $confirm_password) {
                    throw new Exception('New passwords do not match.');
                }
                
                $password_hash = password_hash($new_password, PASSWORD_DEFAULT);
                $stmt = $pdo->prepare("UPDATE users SET password_hash = ?, updated_at = NOW() WHERE id = ?");
                $stmt->execute([$password_hash, $user_id]);
                
                $message = 'Password changed successfully!';
                
            } catch (Exception $e) {
                $error = $e->getMessage();
            }
            break;
    }
}

closeDB($pdo);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Dashboard - TechCompare</title>
    <link rel="stylesheet" href="css/style.css">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        :root {
            --primary-color: #2c3e50;
            --secondary-color: #3498db;
            --success-color: #27ae60;
            --warning-color: #f39c12;
            --danger-color: #e74c3c;
            --light-bg: #ecf0f1;
            --dark-text: #2c3e50;
            --light-text: #7f8c8d;
            --white: #ffffff;
            --shadow: 0 2px 10px rgba(0,0,0,0.1);
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: #f8f9fa;
            margin: 0;
            padding: 0;
            line-height: 1.6;
        }

        .dashboard-header {
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            color: white;
            padding: 2rem 0;
            position: relative;
            z-index: 1;
        }

        .header-content {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 1rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 1rem;
        }

        .header-welcome h1 {
            margin: 0;
            font-size: 2.5rem;
            font-weight: 700;
        }

        .header-welcome p {
            margin: 0.5rem 0 0 0;
            opacity: 0.9;
            font-size: 1.1rem;
        }

        .header-actions {
            display: flex;
            gap: 1rem;
            align-items: center;
        }

        .header-actions a {
            color: white;
            text-decoration: none;
            padding: 0.75rem 1.5rem;
            border: 2px solid rgba(255,255,255,0.3);
            border-radius: 8px;
            transition: all 0.3s ease;
            font-weight: 500;
        }

        .header-actions a:hover {
            background: rgba(255,255,255,0.2);
            border-color: rgba(255,255,255,0.6);
        }

        .dashboard-container {
            max-width: 1200px;
            margin: 2rem auto;
            padding: 0 1rem;
            position: relative;
            z-index: 2;
        }

        .dashboard-nav {
            background: white;
            border-radius: 12px;
            box-shadow: var(--shadow);
            margin-bottom: 2rem;
            overflow: hidden;
            position: relative;
        }

        .nav-tabs {
            display: flex;
            background: #f8f9fa;
            border-bottom: 1px solid #e9ecef;
        }

        .nav-tab {
            flex: 1;
            padding: 1rem 1.5rem;
            background: none;
            border: none;
            cursor: pointer;
            font-size: 1rem;
            font-weight: 500;
            color: var(--light-text);
            transition: all 0.3s ease;
            position: relative;
        }

        .nav-tab.active {
            color: var(--primary-color);
            background: white;
        }

        .nav-tab.active::after {
            content: '';
            position: absolute;
            bottom: 0;
            left: 0;
            right: 0;
            height: 3px;
            background: var(--primary-color);
        }

        .nav-tab:hover:not(.active) {
            background: #e9ecef;
            color: var(--dark-text);
        }

        .nav-tab i {
            margin-right: 0.5rem;
        }

        .dashboard-content {
            background: white;
            border-radius: 12px;
            box-shadow: var(--shadow);
            padding: 2rem;
            position: relative;
            z-index: 1;
        }

        .tab-content {
            display: none;
            position: relative;
        }

        .tab-content.active {
            display: block;
        }

        .welcome-section {
            text-align: center;
            padding: 3rem 2rem;
        }

        .welcome-section h2 {
            color: var(--primary-color);
            font-size: 2rem;
            margin-bottom: 1rem;
        }

        .welcome-section p {
            color: var(--light-text);
            font-size: 1.1rem;
            margin-bottom: 2rem;
        }

        .quick-stats {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1.5rem;
            margin-top: 2rem;
        }

        .stat-card {
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: white;
            padding: 2rem;
            border-radius: 12px;
            text-align: center;
        }

        .stat-card i {
            font-size: 3rem;
            margin-bottom: 1rem;
            opacity: 0.8;
        }

        .stat-card h3 {
            font-size: 2rem;
            margin: 0;
        }

        .stat-card p {
            margin: 0.5rem 0 0 0;
            opacity: 0.9;
        }

        .form-section {
            max-width: 600px;
        }

        .form-section h3 {
            color: var(--primary-color);
            margin-bottom: 1.5rem;
            font-size: 1.5rem;
        }

        .form-group {
            margin-bottom: 1.5rem;
        }

        .form-group label {
            display: block;
            margin-bottom: 0.5rem;
            color: var(--dark-text);
            font-weight: 500;
        }

        .form-group input {
            width: 100%;
            padding: 1rem;
            border: 2px solid #e0e6ed;
            border-radius: 8px;
            font-size: 1rem;
            transition: all 0.3s ease;
        }

        .form-group input:focus {
            outline: none;
            border-color: var(--secondary-color);
            box-shadow: 0 0 0 3px rgba(52, 152, 219, 0.1);
        }

        .btn {
            padding: 1rem 2rem;
            border: none;
            border-radius: 8px;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            text-decoration: none;
            display: inline-block;
            text-align: center;
        }

        .btn-primary {
            background: var(--secondary-color);
            color: white;
        }

        .btn-primary:hover {
            background: #2980b9;
            transform: translateY(-2px);
        }

        .btn-secondary {
            background: var(--light-text);
            color: white;
        }

        .btn-secondary:hover {
            background: #6c757d;
        }

        .alert {
            padding: 1rem;
            border-radius: 8px;
            margin-bottom: 1.5rem;
            border: 1px solid;
            display: flex;
            align-items: center;
        }

        .alert i {
            margin-right: 0.5rem;
        }

        .alert-success {
            background: #d4edda;
            border-color: #c3e6cb;
            color: #155724;
        }

        .alert-error {
            background: #f8d7da;
            border-color: #f5c6cb;
            color: #721c24;
        }

        .profile-info {
            background: #f8f9fa;
            padding: 1.5rem;
            border-radius: 8px;
            margin-bottom: 2rem;
        }

        .profile-info h4 {
            margin: 0 0 1rem 0;
            color: var(--primary-color);
        }

        .info-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 1rem;
        }

        .info-item {
            display: flex;
            align-items: center;
        }

        .info-item i {
            margin-right: 1rem;
            color: var(--secondary-color);
            width: 20px;
        }

        /* Support Tickets Styles */
        .notification-badge {
            background: var(--danger-color);
            color: white;
            border-radius: 50%;
            padding: 0.2rem 0.5rem;
            font-size: 0.7rem;
            margin-left: 0.5rem;
            min-width: 1.2rem;
            text-align: center;
        }

        .support-section {
            max-width: 100%;
        }

        .support-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 2rem;
            flex-wrap: wrap;
            gap: 1rem;
        }

        .support-header h3 {
            color: var(--primary-color);
            font-size: 1.5rem;
        }

        .support-stats {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1rem;
            margin-bottom: 2rem;
        }

        .support-stat-card {
            background: white;
            border: 1px solid #e9ecef;
            border-radius: 8px;
            padding: 1.5rem;
            display: flex;
            align-items: center;
            gap: 1rem;
            transition: var(--transition);
        }

        .support-stat-card:hover {
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
        }

        .stat-icon {
            width: 50px;
            height: 50px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
            color: white;
        }

        .stat-icon.total { background: var(--secondary-color); }
        .stat-icon.pending { background: var(--warning-color); }
        .stat-icon.resolved { background: var(--success-color); }
        .stat-icon.closed { background: var(--light-text); }

        .stat-info h4 {
            margin: 0;
            font-size: 1.5rem;
            color: var(--primary-color);
        }

        .stat-info p {
            margin: 0;
            color: var(--light-text);
            font-size: 0.9rem;
        }

        .no-tickets {
            text-align: center;
            padding: 3rem 2rem;
            background: #f8f9fa;
            border-radius: 8px;
        }

        .no-tickets-icon {
            font-size: 4rem;
            color: var(--light-text);
            margin-bottom: 1rem;
        }

        .no-tickets h4 {
            color: var(--primary-color);
            margin-bottom: 1rem;
        }

        .no-tickets p {
            color: var(--light-text);
            margin-bottom: 2rem;
        }

        .tickets-list {
            display: flex;
            flex-direction: column;
            gap: 1.5rem;
        }

        .ticket-card {
            background: white;
            border: 1px solid #e9ecef;
            border-radius: 12px;
            overflow: hidden;
            transition: var(--transition);
        }

        .ticket-card:hover {
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
        }

        .ticket-header {
            padding: 1.5rem;
            background: #f8f9fa;
            border-bottom: 1px solid #e9ecef;
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            flex-wrap: wrap;
            gap: 1rem;
        }

        .ticket-info h4 {
            margin: 0 0 0.5rem 0;
            color: var(--primary-color);
            font-size: 1.2rem;
        }

        .ticket-meta {
            display: flex;
            gap: 1rem;
            flex-wrap: wrap;
            align-items: center;
            font-size: 0.9rem;
        }

        .ticket-id {
            background: var(--primary-color);
            color: white;
            padding: 0.2rem 0.5rem;
            border-radius: 4px;
            font-weight: 500;
        }

        .ticket-priority {
            padding: 0.2rem 0.5rem;
            border-radius: 4px;
            font-weight: 500;
            color: white;
        }

        .priority-low { background: #6c757d; }
        .priority-medium { background: var(--secondary-color); }
        .priority-high { background: var(--warning-color); }
        .priority-urgent { background: var(--danger-color); }

        .ticket-date {
            color: var(--light-text);
            display: flex;
            align-items: center;
            gap: 0.3rem;
        }

        .status-badge {
            padding: 0.4rem 0.8rem;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 500;
        }

        .status-new {
            background: #e3f2fd;
            color: #1976d2;
        }

        .status-in-progress {
            background: #fff3e0;
            color: #f57c00;
        }

        .status-resolved {
            background: #e8f5e8;
            color: #2e7d32;
        }

        .status-closed {
            background: #f3e5f5;
            color: #7b1fa2;
        }

        .ticket-content {
            padding: 1.5rem;
        }

        .ticket-message {
            margin-bottom: 1.5rem;
        }

        .ticket-message h5,
        .admin-response h5 {
            margin: 0 0 0.5rem 0;
            color: var(--primary-color);
            font-size: 1rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .ticket-message p,
        .admin-response p {
            margin: 0;
            line-height: 1.6;
            color: var(--text-dark);
        }

        .admin-response {
            background: #f0f8ff;
            padding: 1rem;
            border-radius: 8px;
            border-left: 4px solid var(--secondary-color);
        }

        .response-date {
            color: var(--light-text);
            font-size: 0.8rem;
            margin-top: 0.5rem;
            display: flex;
            align-items: center;
            gap: 0.3rem;
        }

        .ticket-resolved {
            background: var(--success-color);
            color: white;
            padding: 0.75rem 1.5rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
            font-weight: 500;
        }

        @media (max-width: 768px) {
            .header-content {
                flex-direction: column;
                text-align: center;
            }

            .dashboard-container {
                margin-top: 1rem;
                padding: 0 0.5rem;
            }

            .nav-tabs {
                flex-direction: column;
            }

            .nav-tab {
                text-align: left;
                padding: 1rem;
                border-bottom: 1px solid #e9ecef;
            }

            .nav-tab:last-child {
                border-bottom: none;
            }

            .dashboard-content {
                padding: 1.5rem;
                border-radius: 8px;
            }

            .quick-stats {
                grid-template-columns: 1fr;
            }

            .support-stats {
                grid-template-columns: repeat(2, 1fr);
            }

            .support-header {
                flex-direction: column;
                align-items: stretch;
            }

            .ticket-header {
                flex-direction: column;
                align-items: stretch;
            }

            .ticket-meta {
                justify-content: flex-start;
            }

            .notification-badge {
                position: static;
                margin-left: 0.5rem;
            }
        }

        @media (max-width: 480px) {
            .support-stats {
                grid-template-columns: 1fr;
            }

            .ticket-meta {
                flex-direction: column;
                gap: 0.5rem;
                align-items: flex-start;
            }

            .stat-icon {
                width: 40px;
                height: 40px;
                font-size: 1.2rem;
            }

            .support-stat-card {
                padding: 1rem;
            }
        }
    </style>
</head>
<body>
    <header class="dashboard-header">
        <div class="header-content">
            <div class="header-welcome">
                <h1>Welcome, <?php echo htmlspecialchars($user['first_name'] ?: $user['username']); ?>!</h1>
                <p>Manage your TechCompare account and preferences</p>
            </div>
            <div class="header-actions">
                <a href="index.php"><i class="fas fa-home"></i> Home</a>
                <a href="user_logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a>
            </div>
        </div>
    </header>

    <div class="dashboard-container">
        <nav class="dashboard-nav">
            <div class="nav-tabs">
                <button class="nav-tab active" onclick="switchTab('overview')">
                    <i class="fas fa-tachometer-alt"></i> Overview
                </button>
                <button class="nav-tab" onclick="switchTab('profile')">
                    <i class="fas fa-user"></i> Profile
                </button>
                <button class="nav-tab" onclick="switchTab('support')">
                    <i class="fas fa-life-ring"></i> Support Tickets
                    <?php if ($contact_stats['pending'] > 0): ?>
                        <span class="notification-badge"><?php echo $contact_stats['pending']; ?></span>
                    <?php endif; ?>
                </button>
                <button class="nav-tab" onclick="switchTab('security')">
                    <i class="fas fa-shield-alt"></i> Security
                </button>
                <button class="nav-tab" onclick="switchTab('preferences')">
                    <i class="fas fa-cogs"></i> Preferences
                </button>
            </div>
        </nav>

        <main class="dashboard-content">
            <!-- Messages -->
            <?php if ($message): ?>
                <div class="alert alert-success">
                    <i class="fas fa-check-circle"></i>
                    <?php echo htmlspecialchars($message); ?>
                </div>
            <?php endif; ?>

            <?php if ($error): ?>
                <div class="alert alert-error">
                    <i class="fas fa-exclamation-triangle"></i>
                    <?php echo htmlspecialchars($error); ?>
                </div>
            <?php endif; ?>

            <!-- Overview Tab -->
            <div class="tab-content active" id="overview">
                <div class="welcome-section">
                    <h2>Dashboard Overview</h2>
                    <p>Here's a quick overview of your TechCompare account activity</p>
                    
                    <div class="quick-stats">
                        <div class="stat-card">
                            <i class="fas fa-user-check"></i>
                            <h3>Active</h3>
                            <p>Account Status</p>
                        </div>
                        <div class="stat-card">
                            <i class="fas fa-calendar-alt"></i>
                            <h3><?php echo date('M j, Y', strtotime($user['created_at'])); ?></h3>
                            <p>Member Since</p>
                        </div>
                        <div class="stat-card">
                            <i class="fas fa-clock"></i>
                            <h3><?php echo $user['last_login'] ? date('M j, Y', strtotime($user['last_login'])) : 'First time'; ?></h3>
                            <p>Last Login</p>
                        </div>
                        <div class="stat-card">
                            <i class="fas fa-life-ring"></i>
                            <h3><?php echo $contact_stats['total']; ?></h3>
                            <p>Support Tickets</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Profile Tab -->
            <div class="tab-content" id="profile">
                <div class="form-section">
                    <h3><i class="fas fa-user"></i> Profile Information</h3>
                    
                    <div class="profile-info">
                        <h4>Current Information</h4>
                        <div class="info-grid">
                            <div class="info-item">
                                <i class="fas fa-user"></i>
                                <span><strong>Username:</strong> <?php echo htmlspecialchars($user['username']); ?></span>
                            </div>
                            <div class="info-item">
                                <i class="fas fa-envelope"></i>
                                <span><strong>Email:</strong> <?php echo htmlspecialchars($user['email']); ?></span>
                            </div>
                            <div class="info-item">
                                <i class="fas fa-id-card"></i>
                                <span><strong>Name:</strong> <?php echo htmlspecialchars(trim($user['first_name'] . ' ' . $user['last_name'])) ?: 'Not set'; ?></span>
                            </div>
                            <div class="info-item">
                                <i class="fas fa-user-tag"></i>
                                <span><strong>Role:</strong> <?php echo ucfirst($user['role']); ?></span>
                            </div>
                        </div>
                    </div>
                    
                    <form method="POST">
                        <input type="hidden" name="action" value="update_profile">
                        
                        <div class="form-group">
                            <label for="first_name">First Name</label>
                            <input type="text" id="first_name" name="first_name" value="<?php echo htmlspecialchars($user['first_name']); ?>" maxlength="50">
                        </div>
                        
                        <div class="form-group">
                            <label for="last_name">Last Name</label>
                            <input type="text" id="last_name" name="last_name" value="<?php echo htmlspecialchars($user['last_name']); ?>" maxlength="50">
                        </div>
                        
                        <div class="form-group">
                            <label for="email">Email Address</label>
                            <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($user['email']); ?>" required maxlength="100">
                        </div>
                        
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save"></i> Update Profile
                        </button>
                    </form>
                </div>
            </div>

            <!-- Support Tickets Tab -->
            <div class="tab-content" id="support">
                <div class="support-section">
                    <div class="support-header">
                        <h3><i class="fas fa-life-ring"></i> Support Tickets</h3>
                        <a href="contact.php" class="btn btn-primary">
                            <i class="fas fa-plus"></i> Create New Ticket
                        </a>
                    </div>
                    
                    <div class="support-stats">
                        <div class="support-stat-card">
                            <div class="stat-icon total">
                                <i class="fas fa-ticket-alt"></i>
                            </div>
                            <div class="stat-info">
                                <h4><?php echo $contact_stats['total']; ?></h4>
                                <p>Total Tickets</p>
                            </div>
                        </div>
                        <div class="support-stat-card">
                            <div class="stat-icon pending">
                                <i class="fas fa-clock"></i>
                            </div>
                            <div class="stat-info">
                                <h4><?php echo $contact_stats['pending']; ?></h4>
                                <p>Pending</p>
                            </div>
                        </div>
                        <div class="support-stat-card">
                            <div class="stat-icon resolved">
                                <i class="fas fa-check-circle"></i>
                            </div>
                            <div class="stat-info">
                                <h4><?php echo $contact_stats['resolved']; ?></h4>
                                <p>Resolved</p>
                            </div>
                        </div>
                        <div class="support-stat-card">
                            <div class="stat-icon closed">
                                <i class="fas fa-times-circle"></i>
                            </div>
                            <div class="stat-info">
                                <h4><?php echo $contact_stats['closed']; ?></h4>
                                <p>Closed</p>
                            </div>
                        </div>
                    </div>

                    <?php if (empty($contact_submissions)): ?>
                        <div class="no-tickets">
                            <div class="no-tickets-icon">
                                <i class="fas fa-inbox"></i>
                            </div>
                            <h4>No Support Tickets</h4>
                            <p>You haven't submitted any support tickets yet. If you need help, feel free to contact our support team.</p>
                            <a href="contact.php" class="btn btn-primary">
                                <i class="fas fa-plus"></i> Create Your First Ticket
                            </a>
                        </div>
                    <?php else: ?>
                        <div class="tickets-list">
                            <?php foreach ($contact_submissions as $ticket): ?>
                                <div class="ticket-card">
                                    <div class="ticket-header">
                                        <div class="ticket-info">
                                            <h4><?php echo htmlspecialchars($ticket['subject']); ?></h4>
                                            <div class="ticket-meta">
                                                <span class="ticket-id">#<?php echo $ticket['id']; ?></span>
                                                <span class="ticket-priority priority-<?php echo $ticket['priority']; ?>">
                                                    <?php echo ucfirst($ticket['priority']); ?>
                                                </span>
                                                <span class="ticket-date">
                                                    <i class="fas fa-calendar"></i>
                                                    <?php echo date('M j, Y H:i', strtotime($ticket['created_at'])); ?>
                                                </span>
                                            </div>
                                        </div>
                                        <div class="ticket-status">
                                            <span class="status-badge status-<?php echo $ticket['status']; ?>">
                                                <?php echo ucfirst(str_replace('_', ' ', $ticket['status'])); ?>
                                            </span>
                                        </div>
                                    </div>
                                    
                                    <div class="ticket-content">
                                        <div class="ticket-message">
                                            <h5><i class="fas fa-user"></i> Your Message:</h5>
                                            <p><?php echo nl2br(htmlspecialchars($ticket['message'])); ?></p>
                                        </div>
                                        
                                        <?php if (!empty($ticket['admin_response'])): ?>
                                            <div class="admin-response">
                                                <h5><i class="fas fa-headset"></i> Support Response:</h5>
                                                <p><?php echo nl2br(htmlspecialchars($ticket['admin_response'])); ?></p>
                                                <small class="response-date">
                                                    <i class="fas fa-clock"></i>
                                                    Responded on <?php echo date('M j, Y H:i', strtotime($ticket['updated_at'])); ?>
                                                </small>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                    
                                    <?php if ($ticket['status'] === 'resolved' && $ticket['resolved_at']): ?>
                                        <div class="ticket-resolved">
                                            <i class="fas fa-check-circle"></i>
                                            Resolved on <?php echo date('M j, Y H:i', strtotime($ticket['resolved_at'])); ?>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Security Tab -->
            <div class="tab-content" id="security">
                <div class="form-section">
                    <h3><i class="fas fa-shield-alt"></i> Change Password</h3>
                    
                    <form method="POST">
                        <input type="hidden" name="action" value="change_password">
                        
                        <div class="form-group">
                            <label for="current_password">Current Password</label>
                            <input type="password" id="current_password" name="current_password" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="new_password">New Password</label>
                            <input type="password" id="new_password" name="new_password" required minlength="6">
                        </div>
                        
                        <div class="form-group">
                            <label for="confirm_password">Confirm New Password</label>
                            <input type="password" id="confirm_password" name="confirm_password" required minlength="6">
                        </div>
                        
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-key"></i> Change Password
                        </button>
                    </form>
                </div>
            </div>

            <!-- Preferences Tab -->
            <div class="tab-content" id="preferences">
                <div class="form-section">
                    <h3><i class="fas fa-cogs"></i> Account Preferences</h3>
                    <p>Coming soon! This section will allow you to customize your TechCompare experience.</p>
                    
                    <div class="quick-stats">
                        <div class="stat-card">
                            <i class="fas fa-bell"></i>
                            <h3>Notifications</h3>
                            <p>Email alerts & updates</p>
                        </div>
                        <div class="stat-card">
                            <i class="fas fa-heart"></i>
                            <h3>Favorites</h3>
                            <p>Save your favorite products</p>
                        </div>
                        <div class="stat-card">
                            <i class="fas fa-chart-line"></i>
                            <h3>Comparisons</h3>
                            <p>Your comparison history</p>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <script>
        function switchTab(tabName) {
            // Hide all tab contents
            document.querySelectorAll('.tab-content').forEach(content => {
                content.classList.remove('active');
            });
            
            // Remove active class from all tabs
            document.querySelectorAll('.nav-tab').forEach(tab => {
                tab.classList.remove('active');
            });
            
            // Show selected tab content
            document.getElementById(tabName).classList.add('active');
            
            // Add active class to clicked tab
            event.target.classList.add('active');
        }

        // Form validation for password change
        document.querySelector('form[action="change_password"]').addEventListener('submit', function(e) {
            const newPassword = document.getElementById('new_password').value;
            const confirmPassword = document.getElementById('confirm_password').value;
            
            if (newPassword !== confirmPassword) {
                e.preventDefault();
                alert('New passwords do not match!');
                return;
            }
            
            if (newPassword.length < 6) {
                e.preventDefault();
                alert('New password must be at least 6 characters long!');
                return;
            }
        });

        // Auto-hide alerts after 5 seconds
        document.addEventListener('DOMContentLoaded', function() {
            const alerts = document.querySelectorAll('.alert');
            alerts.forEach(alert => {
                setTimeout(() => {
                    alert.style.opacity = '0';
                    alert.style.transition = 'opacity 0.5s';
                    setTimeout(() => alert.remove(), 500);
                }, 5000);
            });
        });
    </script>
</body>
</html>