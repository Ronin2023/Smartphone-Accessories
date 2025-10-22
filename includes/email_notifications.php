<?php
/**
 * Email Notification System for Contact Responses
 * Sends automatic email notifications when admin replies to user queries
 */

require_once 'config.php';
require_once 'db_connect.php';

class EmailNotifications {
    private $from_email;
    private $from_name;
    private $smtp_enabled;
    
    public function __construct() {
        $this->from_email = 'noreply@techcompare.com';
        $this->from_name = 'TechCompare Support';
        $this->smtp_enabled = false; // Set to true if you have SMTP configured
    }
    
    /**
     * Send email notification when admin responds to contact submission
     */
    public function sendResponseNotification($submission_id, $user_email, $user_name, $subject, $admin_response) {
        try {
            $email_subject = "Response to your inquiry: " . $subject;
            $email_body = $this->buildResponseEmail($user_name, $subject, $admin_response, $submission_id);
            
            if ($this->smtp_enabled) {
                return $this->sendSMTPEmail($user_email, $email_subject, $email_body);
            } else {
                return $this->sendPHPMail($user_email, $email_subject, $email_body);
            }
        } catch (Exception $e) {
            error_log("Email notification failed: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Build HTML email template for admin response
     */
    private function buildResponseEmail($user_name, $subject, $admin_response, $submission_id) {
        $tracking_url = SITE_URL . "/check-response.php?id=" . $submission_id . "&email=" . urlencode($user_email ?? '');
        
        $html = '
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset="UTF-8">
            <meta name="viewport" content="width=device-width, initial-scale=1.0">
            <title>Response to Your Inquiry</title>
            <style>
                body { 
                    font-family: Arial, sans-serif; 
                    line-height: 1.6; 
                    color: #333; 
                    margin: 0; 
                    padding: 0;
                    background-color: #f4f4f4;
                }
                .container { 
                    max-width: 600px; 
                    margin: 0 auto; 
                    background: white;
                    border-radius: 10px;
                    overflow: hidden;
                    box-shadow: 0 0 20px rgba(0,0,0,0.1);
                }
                .header { 
                    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); 
                    color: white; 
                    padding: 30px; 
                    text-align: center;
                }
                .header h1 { 
                    margin: 0; 
                    font-size: 24px;
                }
                .content { 
                    padding: 30px; 
                }
                .response-box {
                    background: #f8f9fa;
                    border-left: 4px solid #667eea;
                    padding: 20px;
                    margin: 20px 0;
                    border-radius: 5px;
                }
                .footer { 
                    background: #f8f9fa; 
                    padding: 20px; 
                    text-align: center; 
                    border-top: 1px solid #eee;
                }
                .button {
                    display: inline-block;
                    padding: 12px 25px;
                    background: #667eea;
                    color: white;
                    text-decoration: none;
                    border-radius: 5px;
                    margin: 15px 0;
                }
                .button:hover {
                    background: #5a67d8;
                    color: white;
                }
                .info-box {
                    background: #e3f2fd;
                    border: 1px solid #bbdefb;
                    padding: 15px;
                    border-radius: 5px;
                    margin: 15px 0;
                }
            </style>
        </head>
        <body>
            <div class="container">
                <div class="header">
                    <h1>📧 Response to Your Inquiry</h1>
                    <p>TechCompare Support Team</p>
                </div>
                
                <div class="content">
                    <h2>Hello ' . htmlspecialchars($user_name) . '!</h2>
                    
                    <p>Thank you for contacting TechCompare. We have reviewed your inquiry and our support team has responded to your question:</p>
                    
                    <div class="info-box">
                        <strong>Your Original Subject:</strong><br>
                        ' . htmlspecialchars($subject) . '
                    </div>
                    
                    <div class="response-box">
                        <h3>🎯 Our Response:</h3>
                        ' . nl2br(htmlspecialchars($admin_response)) . '
                    </div>
                    
                    <p>If you have any follow-up questions or need further assistance, please don\'t hesitate to contact us again.</p>
                    
                    <div style="text-align: center;">
                        <a href="' . $tracking_url . '" class="button">
                            View Full Response Details
                        </a>
                    </div>
                    
                    <div class="info-box">
                        <strong>💡 Quick Tips:</strong>
                        <ul>
                            <li>You can reply to this email if you need further clarification</li>
                            <li>Visit our FAQ section for common questions</li>
                            <li>Check our product comparison guides for detailed reviews</li>
                        </ul>
                    </div>
                </div>
                
                <div class="footer">
                    <p><strong>TechCompare Support Team</strong></p>
                    <p>📧 Email: support@techcompare.com | 🌐 Website: <a href="' . SITE_URL . '">techcompare.com</a></p>
                    <p><small>This is an automated response notification. Please do not reply to this email address.</small></p>
                    <p><small>Submission ID: #' . $submission_id . ' | Generated: ' . date('Y-m-d H:i:s') . '</small></p>
                </div>
            </div>
        </body>
        </html>';
        
        return $html;
    }
    
    /**
     * Send email using PHP mail() function
     */
    private function sendPHPMail($to_email, $subject, $html_body) {
        $headers = [
            'MIME-Version: 1.0',
            'Content-type: text/html; charset=UTF-8',
            'From: ' . $this->from_name . ' <' . $this->from_email . '>',
            'Reply-To: support@techcompare.com',
            'X-Mailer: PHP/' . phpversion(),
            'X-Priority: 3',
            'Return-Path: ' . $this->from_email
        ];
        
        return mail($to_email, $subject, $html_body, implode("\r\n", $headers));
    }
    
    /**
     * Send email using SMTP (requires PHPMailer or similar)
     * This is a placeholder for SMTP implementation
     */
    private function sendSMTPEmail($to_email, $subject, $html_body) {
        // TODO: Implement SMTP sending with PHPMailer
        // For now, fallback to PHP mail
        return $this->sendPHPMail($to_email, $subject, $html_body);
    }
    
    /**
     * Send immediate notification email
     */
    public function sendImmediateNotification($submission_data) {
        $message = "
        New Contact Submission Alert!
        
        Name: {$submission_data['name']}
        Email: {$submission_data['email']}
        Subject: {$submission_data['subject']}
        Priority: {$submission_data['priority']}
        
        Message:
        {$submission_data['message']}
        
        Time: " . date('Y-m-d H:i:s') . "
        IP: {$submission_data['ip_address']}
        
        Please respond promptly via the admin panel.
        ";
        
        return mail(
            'admin@techcompare.com', 
            'New Contact Form Submission - ' . $submission_data['subject'],
            $message,
            'From: noreply@techcompare.com'
        );
    }
    
    /**
     * Log email activity for tracking
     */
    public function logEmailActivity($submission_id, $email_type, $status, $recipient = null) {
        try {
            $db = getDB();
            $stmt = $db->prepare("
                INSERT INTO email_logs (submission_id, email_type, status, recipient, sent_at)
                VALUES (?, ?, ?, ?, NOW())
            ");
            $stmt->execute([$submission_id, $email_type, $status, $recipient]);
            return true;
        } catch (Exception $e) {
            error_log("Failed to log email activity: " . $e->getMessage());
            return false;
        }
    }
}

/**
 * Helper function to send notification when admin responds
 */
function notifyUserOfResponse($submission_id) {
    try {
        $db = getDB();
        $stmt = $db->prepare("
            SELECT name, email, subject, admin_response 
            FROM contact_submissions 
            WHERE id = ? AND admin_response IS NOT NULL AND admin_response != ''
        ");
        $stmt->execute([$submission_id]);
        $submission = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($submission) {
            $emailNotifier = new EmailNotifications();
            $success = $emailNotifier->sendResponseNotification(
                $submission_id,
                $submission['email'],
                $submission['name'],
                $submission['subject'],
                $submission['admin_response']
            );
            
            // Log the email attempt
            $emailNotifier->logEmailActivity(
                $submission_id, 
                'response_notification', 
                $success ? 'sent' : 'failed',
                $submission['email']
            );
            
            return $success;
        }
        
        return false;
    } catch (Exception $e) {
        error_log("Error sending response notification: " . $e->getMessage());
        return false;
    }
}

/**
 * Create email logs table if it doesn't exist
 */
function createEmailLogsTable() {
    try {
        $db = getDB();
        $db->exec("
            CREATE TABLE IF NOT EXISTS email_logs (
                id INT AUTO_INCREMENT PRIMARY KEY,
                submission_id INT NOT NULL,
                email_type VARCHAR(50) NOT NULL,
                status ENUM('sent', 'failed', 'pending') DEFAULT 'pending',
                recipient VARCHAR(255),
                sent_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                error_message TEXT,
                FOREIGN KEY (submission_id) REFERENCES contact_submissions(id) ON DELETE CASCADE,
                INDEX idx_submission_id (submission_id),
                INDEX idx_email_type (email_type),
                INDEX idx_status (status)
            )
        ");
        return true;
    } catch (Exception $e) {
        error_log("Failed to create email_logs table: " . $e->getMessage());
        return false;
    }
}

// Initialize email logs table
createEmailLogsTable();
?>