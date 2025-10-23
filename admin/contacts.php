<?php
/**
 * Admin Contact Management Page
 * 
 * Allows admins to view, respond to, and manage contact form submissions
 */

session_start();
require_once '../includes/config.php';
require_once '../includes/db_connect.php';
require_once '../includes/functions.php';
require_once '../includes/email_notifications.php';

// Check if admin or editor is logged in
if (!isLoggedIn() || !hasAdminAccess()) {
    redirect('index');
    exit();
}

// Get database connection
$pdo = getDB();

// Handle status updates
if ($_POST['action'] ?? '' === 'update_status') {
    $submission_id = (int)($_POST['submission_id'] ?? 0);
    $status = $_POST['status'] ?? '';
    $admin_response = trim($_POST['admin_response'] ?? '');
    
    if ($submission_id > 0 && in_array($status, ['new', 'in_progress', 'resolved', 'closed'])) {
        // Get current submission data for email notification
        $current_stmt = $pdo->prepare("SELECT * FROM contact_submissions WHERE id = ?");
        $current_stmt->execute([$submission_id]);
        $current_submission = $current_stmt->fetch(PDO::FETCH_ASSOC);
        
        $stmt = $pdo->prepare("
            UPDATE contact_submissions 
            SET status = ?, admin_response = ?, admin_id = ?, updated_at = NOW()
            " . ($status === 'resolved' ? ", resolved_at = NOW()" : "") . "
            WHERE id = ?
        ");
        
        $stmt->execute([$status, $admin_response, $_SESSION['user_id'] ?? $_SESSION['admin_user_id'] ?? 1, $submission_id]);
        
        // Send email notification if admin response was added/updated
        if (!empty($admin_response) && (!$current_submission || $current_submission['admin_response'] !== $admin_response)) {
            try {
                $email_sent = notifyUserOfResponse($submission_id);
                if ($email_sent) {
                    $_SESSION['flash_message'] = "Contact submission updated successfully! Email notification sent to user.";
                } else {
                    $_SESSION['flash_message'] = "Contact submission updated successfully! (Note: Email notification failed to send)";
                }
            } catch (Exception $e) {
                error_log("Email notification error: " . $e->getMessage());
                $_SESSION['flash_message'] = "Contact submission updated successfully! (Note: Email notification unavailable)";
            }
        } else {
            $_SESSION['flash_message'] = "Contact submission updated successfully!";
        }
        
        header('Location: contacts.php');
        exit();
    }
}

// Pagination
$page = max(1, (int)($_GET['page'] ?? 1));
$per_page = 20;
$offset = ($page - 1) * $per_page;

// Filters
$status_filter = $_GET['status'] ?? '';
$priority_filter = $_GET['priority'] ?? '';
$search = trim($_GET['search'] ?? '');

// Build query
$where_conditions = [];
$params = [];

if ($status_filter && in_array($status_filter, ['new', 'in_progress', 'resolved', 'closed'])) {
    $where_conditions[] = "cs.status = ?";
    $params[] = $status_filter;
}

if ($priority_filter && in_array($priority_filter, ['low', 'medium', 'high', 'urgent'])) {
    $where_conditions[] = "cs.priority = ?";
    $params[] = $priority_filter;
}

if ($search) {
    $where_conditions[] = "(cs.name LIKE ? OR cs.email LIKE ? OR cs.subject LIKE ? OR cs.message LIKE ?)";
    $search_param = "%{$search}%";
    $params = array_merge($params, [$search_param, $search_param, $search_param, $search_param]);
}

$where_clause = $where_conditions ? 'WHERE ' . implode(' AND ', $where_conditions) : '';

// Get total count
$count_stmt = $pdo->prepare("
    SELECT COUNT(*) 
    FROM contact_submissions cs
    LEFT JOIN users u ON cs.admin_id = u.id
    {$where_clause}
");
$count_stmt->execute($params);
$total_submissions = $count_stmt->fetchColumn();
$total_pages = ceil($total_submissions / $per_page);

// Get submissions
$stmt = $pdo->prepare("
    SELECT cs.*, u.username as admin_username
    FROM contact_submissions cs
    LEFT JOIN users u ON cs.admin_id = u.id
    {$where_clause}
    ORDER BY 
        CASE cs.priority 
            WHEN 'urgent' THEN 1 
            WHEN 'high' THEN 2 
            WHEN 'medium' THEN 3 
            WHEN 'low' THEN 4 
        END,
        cs.created_at DESC
    LIMIT {$per_page} OFFSET {$offset}
");
$stmt->execute($params);
$submissions = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get statistics
$stats_stmt = $pdo->prepare("
    SELECT 
        status,
        COUNT(*) as count,
        AVG(TIMESTAMPDIFF(HOUR, created_at, COALESCE(resolved_at, NOW()))) as avg_response_time
    FROM contact_submissions 
    GROUP BY status
");
$stats_stmt->execute();
$stats = $stats_stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Contact Management - TechCompare Admin</title>
    <link rel="stylesheet" href="../css/admin.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        /* Custom Contact Admin Styles - Compatible with admin.css */
        .contacts-container {
            padding: 0;
        }
        
        .page-header {
            margin-bottom: 2rem;
        }
        
        .page-header h1 {
            margin: 0 0 0.5rem 0;
            color: var(--text-dark);
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }
        
        .page-header p {
            color: var(--text-light);
            margin: 0;
        }
        
        .alert {
            padding: 1rem;
            border-radius: var(--border-radius);
            margin-bottom: 1.5rem;
            border: 1px solid transparent;
        }
        
        .alert-success {
            background: #d4edda;
            color: #155724;
            border-color: #c3e6cb;
        }
        
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1.5rem;
            margin-bottom: 2rem;
        }
        
        .stat-card {
            background: white;
            padding: 1.5rem;
            border-radius: var(--border-radius);
            box-shadow: var(--shadow);
            text-align: center;
            transition: var(--transition);
        }
        
        .stat-card:hover {
            transform: translateY(-2px);
            box-shadow: var(--shadow-lg);
        }
        
        .stat-card h3 {
            margin: 0 0 0.5rem 0;
            color: var(--text-dark);
            font-size: 1rem;
            font-weight: 600;
        }
        
        .stat-card .number {
            font-size: 2rem;
            font-weight: bold;
            color: var(--primary-color);
            margin: 0;
        }
        
        .stat-card small {
            color: var(--text-light);
            font-size: 0.85rem;
        }
        
        .filters {
            background: white;
            padding: 1.5rem;
            border-radius: var(--border-radius);
            margin-bottom: 1.5rem;
            box-shadow: var(--shadow);
            display: flex;
            gap: 1rem;
            align-items: end;
            flex-wrap: wrap;
        }
        
        .filter-group {
            display: flex;
            flex-direction: column;
            gap: 0.5rem;
            min-width: 150px;
        }
        
        .filter-group label {
            font-weight: 500;
            color: var(--text-dark);
            font-size: 0.9rem;
        }
        
        .filter-group select,
        .filter-group input {
            padding: 0.5rem;
            border: 1px solid var(--border-color);
            border-radius: var(--border-radius);
            font-size: 0.9rem;
            transition: var(--transition);
        }
        
        .filter-group select:focus,
        .filter-group input:focus {
            outline: none;
            border-color: var(--primary-color);
            box-shadow: 0 0 0 3px rgba(67, 97, 238, 0.1);
        }
        
        .submissions-table {
            background: white;
            border-radius: var(--border-radius);
            overflow: hidden;
            box-shadow: var(--shadow);
        }
        
        .submissions-table table {
            width: 100%;
            border-collapse: collapse;
        }
        
        .submissions-table th,
        .submissions-table td {
            padding: 1rem;
            text-align: left;
            border-bottom: 1px solid var(--border-color);
        }
        
        .submissions-table th {
            background: var(--background-alt);
            font-weight: 600;
            color: var(--text-dark);
            font-size: 0.9rem;
        }
        
        .submissions-table tbody tr:hover {
            background: var(--background-alt);
        }
        
        .priority-badge,
        .status-badge {
            padding: 0.25rem 0.5rem;
            border-radius: 12px;
            font-size: 0.75rem;
            font-weight: 500;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        .priority-urgent { background: #fee; color: #dc3545; }
        .priority-high { background: #fff3cd; color: #f57c00; }
        .priority-medium { background: #e3f2fd; color: #1976d2; }
        .priority-low { background: #e8f5e8; color: #388e3c; }
        
        .status-new { background: #fee; color: #dc3545; }
        .status-in_progress { background: #fff3cd; color: #f57c00; }
        .status-resolved { background: #e8f5e8; color: #388e3c; }
        .status-closed { background: #f5f5f5; color: #666; }
        
        .action-buttons {
            display: flex;
            gap: 0.5rem;
        }
        
        .btn-sm {
            padding: 0.25rem 0.5rem;
            font-size: 0.75rem;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 0.25rem;
            transition: var(--transition);
        }
        
        .btn-primary { 
            background: var(--primary-color); 
            color: white; 
        }
        
        .btn-primary:hover { 
            background: #3451e6; 
            transform: translateY(-1px);
        }
        
        .btn-success { 
            background: var(--success-color); 
            color: white; 
        }
        
        .btn-success:hover { 
            background: #218838; 
            transform: translateY(-1px);
        }
        
        .btn-secondary {
            background: #6c757d;
            color: white;
        }
        
        .btn-secondary:hover {
            background: #5a6268;
        }
        
        .modal {
            display: none;
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0,0,0,0.5);
        }
        
        .modal-content {
            background-color: white;
            margin: 5% auto;
            padding: 2rem;
            border-radius: var(--border-radius);
            width: 90%;
            max-width: 600px;
            max-height: 80vh;
            overflow-y: auto;
            box-shadow: var(--shadow-lg);
        }
        
        .close {
            color: #aaa;
            float: right;
            font-size: 28px;
            font-weight: bold;
            cursor: pointer;
            line-height: 1;
        }
        
        .close:hover {
            color: var(--text-dark);
        }
        
        .pagination {
            display: flex;
            justify-content: center;
            gap: 0.5rem;
            margin-top: 2rem;
        }
        
        .pagination a,
        .pagination span {
            padding: 0.5rem 0.75rem;
            text-decoration: none;
            border: 1px solid var(--border-color);
            border-radius: 4px;
            color: var(--text-dark);
            transition: var(--transition);
        }
        
        .pagination a:hover {
            background: var(--background-alt);
        }
        
        .pagination .current {
            background: var(--primary-color);
            color: white;
            border-color: var(--primary-color);
        }
        
        /* Responsive Design */
        @media (max-width: 1024px) {
            .filters {
                flex-direction: column;
                align-items: stretch;
            }
            
            .filter-group {
                min-width: auto;
            }
        }
        
        @media (max-width: 768px) {
            .admin-content {
                padding: 1rem;
            }
            
            .submissions-table {
                overflow-x: auto;
            }
            
            .submissions-table table {
                min-width: 600px;
            }
            
            .action-buttons {
                flex-direction: column;
            }
            
            .stats-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <div class="admin-container">
        <!-- Sidebar -->
        <aside class="admin-sidebar">
            <div class="sidebar-header">
                <div class="logo">
                    <i class="fas fa-chart-line"></i>
                    TechCompare Admin
                </div>
            </div>
            <nav class="sidebar-nav">
                <ul>
                    <li class="nav-item">
                        <a href="dashboard" class="nav-link">
                            <i class="fas fa-tachometer-alt"></i>
                            <span>Dashboard</span>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="products" class="nav-link">
                            <i class="fas fa-box"></i>
                            <span>Products</span>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="categories" class="nav-link">
                            <i class="fas fa-tags"></i>
                            <span>Categories</span>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="brands" class="nav-link">
                            <i class="fas fa-award"></i>
                            <span>Brands</span>
                        </a>
                    </li>
                    <?php if (isAdmin()): ?>
                    <li class="nav-item">
                        <a href="users" class="nav-link">
                            <i class="fas fa-users"></i>
                            <span>Users</span>
                        </a>
                    </li>
                    <?php endif; ?>
                    <li class="nav-item active">
                        <a href="contacts" class="nav-link">
                            <i class="fas fa-envelope"></i>
                            <span>Contact Messages</span>
                        </a>
                    </li>
                    <?php if (isAdmin()): ?>
                    <li class="nav-item">
                        <a href="settings" class="nav-link">
                            <i class="fas fa-cog"></i>
                            <span>Settings</span>
                        </a>
                    </li>
                    <?php endif; ?>
                </ul>
                
                <div class="sidebar-footer">
                    <a href="../index" class="nav-link" target="_blank">
                        <i class="fas fa-external-link-alt"></i>
                        <span>View Site</span>
                    </a>
                    <a href="logout" class="nav-link logout">
                        <i class="fas fa-sign-out-alt"></i>
                        <span>Logout</span>
                    </a>
                </div>
            </nav>
        </aside>

        <!-- Main Content -->
        <main class="admin-main">
            <header class="admin-header">
                <div class="header-left">
                    <button class="sidebar-toggle" onclick="toggleSidebar()">
                        <i class="fas fa-bars"></i>
                    </button>
                    <h1 class="page-title">Contact Management</h1>
                </div>
                <div class="header-right">
                    <div class="header-actions">
                        <span class="admin-user">
                            <i class="fas fa-user-circle"></i>
                            Welcome, Admin
                        </span>
                    </div>
                </div>
            </header>

            <div class="admin-content">
                <!-- Page Header -->
                <div class="page-header">
                    <h1><i class="fas fa-envelope"></i> Contact Management</h1>
                    <p>Manage and respond to customer inquiries</p>
                </div>

                <!-- Rate Limit Information -->
                <div style="background: #e8f4fd; border: 1px solid #b8daff; border-radius: 8px; padding: 15px; margin-bottom: 20px; color: #004085;">
                    <i class="fas fa-info-circle" style="color: #0056b3; margin-right: 8px;"></i>
                    <strong>Rate Limiting Policy:</strong> Users can submit only one inquiry per email address every 24 hours. This reduces spam and improves response quality. Duplicate submissions within 24 hours are automatically blocked.
                </div>

                <?php if (isset($_SESSION['flash_message'])): ?>
                    <div class="alert alert-success">
                        <?php echo htmlspecialchars($_SESSION['flash_message']); ?>
                        <?php unset($_SESSION['flash_message']); ?>
                    </div>
                <?php endif; ?>

                <!-- Statistics -->
                <div class="stats-grid">
                    <?php foreach ($stats as $stat): ?>
                        <div class="stat-card">
                            <h3><?php echo ucfirst(str_replace('_', ' ', $stat['status'])); ?></h3>
                            <div class="number"><?php echo $stat['count']; ?></div>
                            <?php if ($stat['avg_response_time']): ?>
                                <small>Avg: <?php echo round($stat['avg_response_time'], 1); ?>h</small>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                </div>

                <!-- Filters -->
                <form class="filters" method="GET">
                    <div class="filter-group">
                        <label>Status</label>
                        <select name="status">
                            <option value="">All Statuses</option>
                            <option value="new" <?php echo $status_filter === 'new' ? 'selected' : ''; ?>>New</option>
                            <option value="in_progress" <?php echo $status_filter === 'in_progress' ? 'selected' : ''; ?>>In Progress</option>
                            <option value="resolved" <?php echo $status_filter === 'resolved' ? 'selected' : ''; ?>>Resolved</option>
                            <option value="closed" <?php echo $status_filter === 'closed' ? 'selected' : ''; ?>>Closed</option>
                        </select>
                    </div>

                    <div class="filter-group">
                        <label>Priority</label>
                        <select name="priority">
                            <option value="">All Priorities</option>
                            <option value="urgent" <?php echo $priority_filter === 'urgent' ? 'selected' : ''; ?>>Urgent</option>
                            <option value="high" <?php echo $priority_filter === 'high' ? 'selected' : ''; ?>>High</option>
                            <option value="medium" <?php echo $priority_filter === 'medium' ? 'selected' : ''; ?>>Medium</option>
                            <option value="low" <?php echo $priority_filter === 'low' ? 'selected' : ''; ?>>Low</option>
                        </select>
                    </div>

                    <div class="filter-group">
                        <label>Search</label>
                        <input type="text" name="search" value="<?php echo htmlspecialchars($search); ?>" placeholder="Name, email, subject...">
                    </div>

                    <div class="filter-group">
                        <label>&nbsp;</label>
                        <button type="submit" class="btn btn-primary">Filter</button>
                    </div>

                    <div class="filter-group">
                        <label>&nbsp;</label>
                        <a href="contacts" class="btn btn-secondary">Clear</a>
                    </div>
                </form>

                <!-- Submissions Table -->
                <div class="submissions-table">
                    <table>
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Name</th>
                                <th>Email</th>
                                <th>Subject</th>
                                <th>Priority</th>
                                <th>Status</th>
                                <th>Date</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($submissions as $submission): ?>
                                <tr>
                                    <td>#<?php echo $submission['id']; ?></td>
                                    <td><?php echo htmlspecialchars($submission['name']); ?></td>
                                    <td><?php echo htmlspecialchars($submission['email']); ?></td>
                                    <td title="<?php echo htmlspecialchars($submission['subject']); ?>">
                                        <?php echo htmlspecialchars(substr($submission['subject'], 0, 30)); ?>
                                        <?php if (strlen($submission['subject']) > 30): ?>...<?php endif; ?>
                                    </td>
                                    <td>
                                        <span class="priority-badge priority-<?php echo $submission['priority']; ?>">
                                            <?php echo ucfirst($submission['priority']); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <span class="status-badge status-<?php echo $submission['status']; ?>">
                                            <?php echo ucfirst(str_replace('_', ' ', $submission['status'])); ?>
                                        </span>
                                    </td>
                                    <td><?php echo date('M j, Y', strtotime($submission['created_at'])); ?></td>
                                    <td>
                                        <div class="action-buttons">
                                            <button class="btn-sm btn-primary" onclick="viewSubmission(<?php echo $submission['id']; ?>)">
                                                <i class="fas fa-eye"></i> View
                                            </button>
                                            <button class="btn-sm btn-success" onclick="respondToSubmission(<?php echo $submission['id']; ?>)">
                                                <i class="fas fa-reply"></i> Respond
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

                <!-- Pagination -->
                <?php if ($total_pages > 1): ?>
                    <div class="pagination">
                        <?php if ($page > 1): ?>
                            <a href="?page=<?php echo $page - 1; ?>&<?php echo http_build_query($_GET); ?>">&laquo; Previous</a>
                        <?php endif; ?>
                        
                        <?php for ($i = max(1, $page - 2); $i <= min($total_pages, $page + 2); $i++): ?>
                            <?php if ($i == $page): ?>
                                <span class="current"><?php echo $i; ?></span>
                            <?php else: ?>
                                <a href="?page=<?php echo $i; ?>&<?php echo http_build_query($_GET); ?>"><?php echo $i; ?></a>
                            <?php endif; ?>
                        <?php endfor; ?>
                        
                        <?php if ($page < $total_pages): ?>
                            <a href="?page=<?php echo $page + 1; ?>&<?php echo http_build_query($_GET); ?>">Next &raquo;</a>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- View Modal -->
    <div id="viewModal" class="modal">
        <div class="modal-content">
            <span class="close" onclick="closeModal('viewModal')">&times;</span>
            <div id="viewContent"></div>
        </div>
    </div>

    <!-- Respond Modal -->
    <div id="respondModal" class="modal">
        <div class="modal-content">
            <span class="close" onclick="closeModal('respondModal')">&times;</span>
            <div id="respondContent"></div>
        </div>
    </div>

    <script>
        // Modal functions
        function viewSubmission(id) {
            // Fetch and display submission details
            fetch(`../api/get_contact_submission.php?id=${id}`)
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        const submission = data.submission;
                        document.getElementById('viewContent').innerHTML = `
                            <h2>Contact Submission #${submission.id}</h2>
                            <div style="margin: 20px 0;">
                                <strong>Name:</strong> ${submission.name}<br>
                                <strong>Email:</strong> ${submission.email}<br>
                                <strong>Phone:</strong> ${submission.phone || 'Not provided'}<br>
                                <strong>Subject:</strong> ${submission.subject}<br>
                                <strong>Priority:</strong> <span class="priority-badge priority-${submission.priority}">${submission.priority}</span><br>
                                <strong>Status:</strong> <span class="status-badge status-${submission.status}">${submission.status.replace('_', ' ')}</span><br>
                                <strong>Date:</strong> ${new Date(submission.created_at).toLocaleString()}<br>
                                <strong>IP Address:</strong> ${submission.ip_address}
                            </div>
                            <div style="margin: 20px 0;">
                                <strong>Message:</strong>
                                <div style="background: #f8f9fa; padding: 15px; border-radius: 5px; margin-top: 10px;">
                                    ${submission.message.replace(/\n/g, '<br>')}
                                </div>
                            </div>
                            ${submission.admin_response ? `
                            <div style="margin: 20px 0;">
                                <strong>Admin Response:</strong>
                                <div style="background: #e8f4fd; padding: 15px; border-radius: 5px; margin-top: 10px;">
                                    ${submission.admin_response.replace(/\n/g, '<br>')}
                                </div>
                                <small>Responded by: ${submission.admin_username || 'Unknown'}</small>
                            </div>
                            ` : ''}
                        `;
                        document.getElementById('viewModal').style.display = 'block';
                    }
                })
                .catch(error => console.error('Error:', error));
        }

        function respondToSubmission(id) {
            // Fetch submission details for response form
            fetch(`../api/get_contact_submission.php?id=${id}`)
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        const submission = data.submission;
                        document.getElementById('respondContent').innerHTML = `
                            <h2>Respond to Contact #${submission.id}</h2>
                            <div style="background: #f8f9fa; padding: 15px; border-radius: 5px; margin: 20px 0;">
                                <strong>From:</strong> ${submission.name} (${submission.email})<br>
                                <strong>Subject:</strong> ${submission.subject}<br>
                                <strong>Message:</strong><br>
                                <div style="margin-top: 10px; font-style: italic;">
                                    ${submission.message.replace(/\n/g, '<br>')}
                                </div>
                            </div>
                            <form method="POST" action="contacts.php">
                                <input type="hidden" name="action" value="update_status">
                                <input type="hidden" name="submission_id" value="${submission.id}">
                                
                                <div style="margin: 20px 0;">
                                    <label><strong>Status:</strong></label>
                                    <select name="status" required style="width: 100%; padding: 8px; margin-top: 5px;">
                                        <option value="new" ${submission.status === 'new' ? 'selected' : ''}>New</option>
                                        <option value="in_progress" ${submission.status === 'in_progress' ? 'selected' : ''}>In Progress</option>
                                        <option value="resolved" ${submission.status === 'resolved' ? 'selected' : ''}>Resolved</option>
                                        <option value="closed" ${submission.status === 'closed' ? 'selected' : ''}>Closed</option>
                                    </select>
                                </div>
                                
                                <div style="margin: 20px 0;">
                                    <label><strong>Admin Response:</strong></label>
                                    <textarea name="admin_response" rows="6" style="width: 100%; padding: 10px; margin-top: 5px;" placeholder="Enter your response to the customer...">${submission.admin_response || ''}</textarea>
                                </div>
                                
                                <div style="margin: 20px 0;">
                                    <button type="submit" class="btn btn-primary">Update & Save Response</button>
                                    <button type="button" class="btn btn-secondary" onclick="closeModal('respondModal')">Cancel</button>
                                </div>
                            </form>
                        `;
                        document.getElementById('respondModal').style.display = 'block';
                    }
                })
                .catch(error => console.error('Error:', error));
        }

        function closeModal(modalId) {
            document.getElementById(modalId).style.display = 'none';
        }

        // Close modal when clicking outside
        window.onclick = function(event) {
            const viewModal = document.getElementById('viewModal');
            const respondModal = document.getElementById('respondModal');
            if (event.target === viewModal) {
                viewModal.style.display = 'none';
            }
            if (event.target === respondModal) {
                respondModal.style.display = 'none';
            }
        }
    </script>

    <script>
        // Sidebar toggle functionality
        function toggleSidebar() {
            const sidebar = document.querySelector('.admin-sidebar');
            sidebar.classList.toggle('collapsed');
        }
    </script>
</body>
</html>