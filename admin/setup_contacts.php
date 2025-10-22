<?php
/**
 * Setup script to create contact_submissions table
 * Run this file once to set up the contact system
 */

require_once '../includes/config.php';
require_once '../includes/db_connect.php';

try {
    $pdo = getDB();
    
    // Create contact_submissions table
    $sql = "
    CREATE TABLE IF NOT EXISTS contact_submissions (
        id INT AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(100) NOT NULL,
        email VARCHAR(100) NOT NULL,
        phone VARCHAR(20),
        subject VARCHAR(255) NOT NULL,
        message TEXT NOT NULL,
        priority ENUM('low', 'medium', 'high', 'urgent') DEFAULT 'medium',
        status ENUM('new', 'in_progress', 'resolved', 'closed') DEFAULT 'new',
        admin_response TEXT,
        admin_id INT,
        ip_address VARCHAR(45),
        user_agent TEXT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        resolved_at TIMESTAMP NULL,
        INDEX idx_status (status),
        INDEX idx_priority (priority),
        INDEX idx_created_at (created_at),
        INDEX idx_email (email)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
    ";
    
    $pdo->exec($sql);
    echo "✅ Contact submissions table created successfully!<br>";
    
    // Check if table exists and has data
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM contact_submissions");
    $count = $stmt->fetch()['count'];
    
    echo "📊 Current submissions count: " . $count . "<br>";
    
    // Insert sample data if table is empty
    if ($count == 0) {
        $sampleData = [
            [
                'name' => 'John Doe',
                'email' => 'john.doe@email.com',
                'phone' => '555-0123',
                'subject' => 'Question about product comparison',
                'message' => 'Hi, I would like to know how to compare multiple smartwatches on your site. The interface is a bit confusing for me.',
                'priority' => 'medium'
            ],
            [
                'name' => 'Sarah Wilson',
                'email' => 'sarah.wilson@email.com',
                'phone' => '555-0456',
                'subject' => 'Product availability inquiry',
                'message' => 'I am looking for the latest iPhone model but cannot find it in your database. When will it be added?',
                'priority' => 'high'
            ],
            [
                'name' => 'Mike Johnson',
                'email' => 'mike.j@email.com',
                'phone' => null,
                'subject' => 'Technical issue with website',
                'message' => 'The search function is not working properly on mobile devices. It keeps showing "no results" even for popular products.',
                'priority' => 'urgent'
            ]
        ];
        
        $insertStmt = $pdo->prepare("
            INSERT INTO contact_submissions (name, email, phone, subject, message, priority, ip_address) 
            VALUES (?, ?, ?, ?, ?, ?, '127.0.0.1')
        ");
        
        foreach ($sampleData as $data) {
            $insertStmt->execute([
                $data['name'],
                $data['email'],
                $data['phone'],
                $data['subject'],
                $data['message'],
                $data['priority']
            ]);
        }
        
        echo "📝 Sample contact submissions added!<br>";
    }
    
    echo "<br>🎉 Contact system setup completed successfully!<br>";
    echo "<br>You can now:<br>";
    echo "• Visit the contact page: <a href='../contact.html'>Contact Us</a><br>";
    echo "• Access admin dashboard: <a href='../admin/contacts.php'>Manage Contacts</a><br>";
    echo "<br><strong>Note:</strong> Delete this setup file after running it once for security.";
    
} catch (Exception $e) {
    echo "❌ Error setting up contact system: " . $e->getMessage();
}
?>