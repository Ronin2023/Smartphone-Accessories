<?php
/**
 * Get Contact Submission Details API
 * 
 * Retrieves detailed information about a specific contact submission
 * for admin review and response.
 */

header('Content-Type: application/json');

// Start session and check admin authentication
session_start();
require_once '../includes/config.php';
require_once '../includes/db_connect.php';
require_once '../includes/functions.php';

// Check if admin or editor is logged in
if (!isLoggedIn() || !hasAdminAccess()) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit();
}

// Get database connection
$pdo = getDB();

// Validate input
$submission_id = (int)($_GET['id'] ?? 0);

if ($submission_id <= 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid submission ID']);
    exit();
}

try {
    // Fetch submission details with admin information
    $stmt = $pdo->prepare("
        SELECT cs.*, u.username as admin_username
        FROM contact_submissions cs
        LEFT JOIN users u ON cs.admin_id = u.id
        WHERE cs.id = ?
    ");
    
    $stmt->execute([$submission_id]);
    $submission = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$submission) {
        echo json_encode(['success' => false, 'message' => 'Submission not found']);
        exit();
    }
    
    // Return the submission data
    echo json_encode([
        'success' => true,
        'submission' => $submission
    ]);
    
} catch (PDOException $e) {
    error_log("Contact submission fetch error: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Database error occurred'
    ]);
}
?>