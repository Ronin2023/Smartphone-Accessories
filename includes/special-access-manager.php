<?php
/**
 * Special Access Manager
 * 
 * Manages special access tokens for bypassing maintenance mode
 * Features:
 * - Unique passkeys for each developer/editor
 * - Session-based full site access
 * - Admin can create, view, revoke tokens
 * - Token usage tracking
 * - Single active session per token
 * 
 * @version 1.0
 * @date October 20, 2025
 */

class SpecialAccessManager {
    private $db;
    
    public function __construct($dbConnection) {
        $this->db = $dbConnection;
        $this->ensureTablesExist();
    }
    
    /**
     * Create necessary database tables
     */
    private function ensureTablesExist() {
        try {
            // First, check if table exists and what structure it has
            $tableExists = false;
            try {
                $stmt = $this->db->query("SHOW TABLES LIKE 'special_access_tokens'");
                $tableExists = $stmt->rowCount() > 0;
            } catch (Exception $e) {
                $tableExists = false;
            }
            
            if ($tableExists) {
                // Table exists, check and migrate structure
                $this->migrateTableStructure();
            } else {
                // Create new table with proper structure
                $this->db->exec("
                    CREATE TABLE special_access_tokens (
                        id INT AUTO_INCREMENT PRIMARY KEY,
                        user_id INT,
                        token VARCHAR(64) UNIQUE NOT NULL,
                        passkey VARCHAR(255) NOT NULL,
                        name VARCHAR(100) NOT NULL,
                        email VARCHAR(255),
                        description TEXT,
                        is_active TINYINT(1) DEFAULT 1,
                        max_sessions INT DEFAULT 1,
                        created_by INT,
                        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                        last_used_at TIMESTAMP NULL,
                        expires_at TIMESTAMP NULL,
                        usage_count INT DEFAULT 0,
                        INDEX idx_token (token),
                        INDEX idx_passkey (passkey),
                        INDEX idx_active (is_active),
                        INDEX idx_user (user_id)
                    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
                ");
            }
            
            // Active sessions table
            $this->db->exec("
                CREATE TABLE IF NOT EXISTS special_access_sessions (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    token_id INT NOT NULL,
                    session_id VARCHAR(128) NOT NULL,
                    ip_address VARCHAR(45),
                    user_agent TEXT,
                    started_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    last_activity TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                    expires_at TIMESTAMP NULL,
                    is_active TINYINT(1) DEFAULT 1,
                    FOREIGN KEY (token_id) REFERENCES special_access_tokens(id) ON DELETE CASCADE,
                    INDEX idx_session (session_id),
                    INDEX idx_token (token_id),
                    INDEX idx_active (is_active)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
            ");
            
            // Access logs table
            $this->db->exec("
                CREATE TABLE IF NOT EXISTS special_access_logs (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    token_id INT,
                    session_id VARCHAR(128),
                    action VARCHAR(50),
                    page_url VARCHAR(500),
                    ip_address VARCHAR(45),
                    user_agent TEXT,
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    INDEX idx_token (token_id),
                    INDEX idx_created (created_at)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
            ");
            
        } catch (Exception $e) {
            error_log("Special Access Manager: Table creation failed - " . $e->getMessage());
        }
    }
    
    /**
     * Migrate existing table structure to new format
     */
    private function migrateTableStructure() {
        try {
            // Get current columns
            $stmt = $this->db->query("DESCRIBE special_access_tokens");
            $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
            $columnNames = array_column($columns, 'Field');
            
            // Add missing columns one by one
            $requiredColumns = [
                'passkey' => 'VARCHAR(255) NOT NULL DEFAULT ""',
                'name' => 'VARCHAR(100) NOT NULL DEFAULT ""',
                'email' => 'VARCHAR(255) DEFAULT NULL',
                'description' => 'TEXT DEFAULT NULL',
                'max_sessions' => 'INT DEFAULT 1',
                'created_by' => 'INT DEFAULT NULL',
                'last_used_at' => 'TIMESTAMP NULL',
                'usage_count' => 'INT DEFAULT 0'
            ];
            
            foreach ($requiredColumns as $column => $definition) {
                if (!in_array($column, $columnNames)) {
                    try {
                        $this->db->exec("ALTER TABLE special_access_tokens ADD COLUMN $column $definition");
                        error_log("Added column: $column");
                    } catch (Exception $e) {
                        error_log("Failed to add column $column: " . $e->getMessage());
                    }
                }
            }
            
            // Add indexes if they don't exist
            $indexes = [
                'idx_passkey' => 'passkey',
                'idx_user_special' => 'user_id'
            ];
            
            foreach ($indexes as $indexName => $column) {
                try {
                    $this->db->exec("CREATE INDEX $indexName ON special_access_tokens ($column)");
                } catch (Exception $e) {
                    // Index might already exist, that's okay
                }
            }
            
        } catch (Exception $e) {
            error_log("Table migration failed: " . $e->getMessage());
        }
    }
    
    /**
     * Generate a new special access token
     */
    public function createToken($name, $email = '', $description = '', $createdBy = null, $userId = null) {
        try {
            // Check table structure first
            $stmt = $this->db->query("DESCRIBE special_access_tokens");
            $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
            $columnNames = array_column($columns, 'Field');
            
            $token = $this->generateUniqueToken();
            $passkey = $this->generatePasskey();
            
            // Build insert query based on available columns
            $fields = ['token'];
            $values = [$token];
            $placeholders = ['?'];
            
            if (in_array('passkey', $columnNames)) {
                $fields[] = 'passkey';
                $values[] = $passkey;
                $placeholders[] = '?';
            }
            
            if (in_array('name', $columnNames)) {
                $fields[] = 'name';
                $values[] = $name;
                $placeholders[] = '?';
            }
            
            if (in_array('email', $columnNames)) {
                $fields[] = 'email';
                $values[] = $email;
                $placeholders[] = '?';
            }
            
            if (in_array('description', $columnNames)) {
                $fields[] = 'description';
                $values[] = $description;
                $placeholders[] = '?';
            }
            
            if (in_array('user_id', $columnNames) && $userId) {
                $fields[] = 'user_id';
                $values[] = $userId;
                $placeholders[] = '?';
            }
            
            if (in_array('created_by', $columnNames) && $createdBy) {
                $fields[] = 'created_by';
                $values[] = $createdBy;
                $placeholders[] = '?';
            }
            
            // If we have username/role columns (old structure), use them
            if (in_array('username', $columnNames) && $userId) {
                // Get username from user_id
                try {
                    $userStmt = $this->db->prepare("SELECT username, role FROM users WHERE id = ?");
                    $userStmt->execute([$userId]);
                    $user = $userStmt->fetch(PDO::FETCH_ASSOC);
                    
                    if ($user) {
                        $fields[] = 'username';
                        $values[] = $user['username'];
                        $placeholders[] = '?';
                        
                        if (in_array('role', $columnNames)) {
                            $fields[] = 'role';
                            $values[] = $user['role'];
                            $placeholders[] = '?';
                        }
                    }
                } catch (Exception $e) {
                    // Users table might not exist
                }
            }
            
            $query = "INSERT INTO special_access_tokens (" . implode(', ', $fields) . ") VALUES (" . implode(', ', $placeholders) . ")";
            $stmt = $this->db->prepare($query);
            $stmt->execute($values);
            
            return [
                'success' => true,
                'token' => $token,
                'passkey' => $passkey,
                'name' => $name
            ];
            
        } catch (Exception $e) {
            error_log("Create token failed: " . $e->getMessage());
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }
    
    /**
     * Generate unique token
     */
    private function generateUniqueToken() {
        do {
            $token = bin2hex(random_bytes(32));
            $stmt = $this->db->prepare("SELECT COUNT(*) FROM special_access_tokens WHERE token = ?");
            $stmt->execute([$token]);
            $exists = $stmt->fetchColumn() > 0;
        } while ($exists);
        
        return $token;
    }
    
    /**
     * Generate human-readable passkey
     */
    private function generatePasskey() {
        // Format: XXXX-XXXX-XXXX-XXXX (4 groups of 4 alphanumeric characters)
        $characters = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789'; // Exclude similar looking characters
        $parts = [];
        
        for ($i = 0; $i < 4; $i++) {
            $part = '';
            for ($j = 0; $j < 4; $j++) {
                $part .= $characters[random_int(0, strlen($characters) - 1)];
            }
            $parts[] = $part;
        }
        
        return implode('-', $parts);
    }
    
    /**
     * Verify token and passkey
     */
    public function verifyPasskey($token, $passkey) {
        try {
            $stmt = $this->db->prepare("
                SELECT id, name, email, max_sessions, is_active 
                FROM special_access_tokens 
                WHERE token = ? AND passkey = ? AND is_active = 1
            ");
            $stmt->execute([$token, $passkey]);
            $tokenData = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$tokenData) {
                return [
                    'success' => false,
                    'error' => 'Invalid passkey or token has been revoked'
                ];
            }
            
            // Check if maintenance mode is active
            $maintenanceStmt = $this->db->prepare("
                SELECT setting_value FROM settings WHERE setting_key = 'maintenance_enabled'
            ");
            $maintenanceStmt->execute();
            $maintenanceEnabled = $maintenanceStmt->fetchColumn();
            
            if (!$maintenanceEnabled) {
                return [
                    'success' => false,
                    'error' => 'Site is not in maintenance mode. Special access not required.'
                ];
            }
            
            // Check active sessions
            $activeSessionsStmt = $this->db->prepare("
                SELECT COUNT(*) FROM special_access_sessions 
                WHERE token_id = ? AND is_active = 1 AND expires_at > NOW()
            ");
            $activeSessionsStmt->execute([$tokenData['id']]);
            $activeSessions = $activeSessionsStmt->fetchColumn();
            
            if ($activeSessions >= $tokenData['max_sessions']) {
                return [
                    'success' => false,
                    'error' => 'Maximum active sessions reached. Please contact admin.'
                ];
            }
            
            // Create session
            $sessionId = session_id();
            $ipAddress = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
            $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? 'unknown';
            
            // Calculate expiration (when maintenance ends)
            $maintenanceEndStmt = $this->db->prepare("
                SELECT setting_value FROM settings WHERE setting_key = 'maintenance_end_time'
            ");
            $maintenanceEndStmt->execute();
            $maintenanceEndTime = $maintenanceEndStmt->fetchColumn();
            $expiresAt = $maintenanceEndTime ? date('Y-m-d H:i:s', $maintenanceEndTime) : null;
            
            // Insert session
            $insertSessionStmt = $this->db->prepare("
                INSERT INTO special_access_sessions 
                (token_id, session_id, ip_address, user_agent, expires_at) 
                VALUES (?, ?, ?, ?, ?)
            ");
            $insertSessionStmt->execute([
                $tokenData['id'], 
                $sessionId, 
                $ipAddress, 
                $userAgent, 
                $expiresAt
            ]);
            
            // Update token usage
            $updateTokenStmt = $this->db->prepare("
                UPDATE special_access_tokens 
                SET last_used_at = NOW(), usage_count = usage_count + 1 
                WHERE id = ?
            ");
            $updateTokenStmt->execute([$tokenData['id']]);
            
            // Log access
            $this->logAccess($tokenData['id'], $sessionId, 'passkey_verified', $_SERVER['REQUEST_URI'] ?? '/', $ipAddress, $userAgent);
            
            return [
                'success' => true,
                'token_id' => $tokenData['id'],
                'name' => $tokenData['name'],
                'session_id' => $sessionId,
                'expires_at' => $expiresAt
            ];
            
        } catch (Exception $e) {
            error_log("Verify passkey failed: " . $e->getMessage());
            return [
                'success' => false,
                'error' => 'System error. Please try again.'
            ];
        }
    }
    
    /**
     * Check if current session has special access
     */
    public function hasActiveSession() {
        if (!isset($_SESSION['special_access_verified']) || !$_SESSION['special_access_verified']) {
            return false;
        }
        
        try {
            $sessionId = session_id();
            
            $stmt = $this->db->prepare("
                SELECT s.id, s.token_id, t.name 
                FROM special_access_sessions s
                JOIN special_access_tokens t ON s.token_id = t.id
                WHERE s.session_id = ? 
                AND s.is_active = 1 
                AND (s.expires_at IS NULL OR s.expires_at > NOW())
                AND t.is_active = 1
            ");
            $stmt->execute([$sessionId]);
            $session = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($session) {
                // Update last activity
                $updateStmt = $this->db->prepare("
                    UPDATE special_access_sessions 
                    SET last_activity = NOW() 
                    WHERE id = ?
                ");
                $updateStmt->execute([$session['id']]);
                
                return true;
            }
            
            // Session expired or invalid
            unset($_SESSION['special_access_verified']);
            unset($_SESSION['special_access_token_id']);
            unset($_SESSION['special_access_name']);
            
            return false;
            
        } catch (Exception $e) {
            error_log("Check active session failed: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Revoke a token (admin action)
     */
    public function revokeToken($tokenId) {
        try {
            // Deactivate token
            $stmt = $this->db->prepare("
                UPDATE special_access_tokens 
                SET is_active = 0 
                WHERE id = ?
            ");
            $stmt->execute([$tokenId]);
            
            // Deactivate all sessions
            $sessionStmt = $this->db->prepare("
                UPDATE special_access_sessions 
                SET is_active = 0 
                WHERE token_id = ?
            ");
            $sessionStmt->execute([$tokenId]);
            
            // Log action
            $this->logAccess($tokenId, null, 'token_revoked', null, $_SERVER['REMOTE_ADDR'] ?? 'system', 'admin');
            
            return ['success' => true];
            
        } catch (Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }
    
    /**
     * Reactivate a revoked token
     */
    public function reactivateToken($tokenId) {
        try {
            $stmt = $this->db->prepare("
                UPDATE special_access_tokens 
                SET is_active = 1 
                WHERE id = ?
            ");
            $stmt->execute([$tokenId]);
            
            $this->logAccess($tokenId, null, 'token_reactivated', null, $_SERVER['REMOTE_ADDR'] ?? 'system', 'admin');
            
            return ['success' => true];
            
        } catch (Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }
    
    /**
     * Get all tokens
     */
    public function getAllTokens() {
        try {
            // First check table structure
            $stmt = $this->db->query("DESCRIBE special_access_tokens");
            $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
            $columnNames = array_column($columns, 'Field');
            
            // Build query based on available columns
            $selectFields = ['t.*'];
            $joinClauses = [];
            
            // Check if we have user_id and users table
            if (in_array('user_id', $columnNames)) {
                try {
                    // Check if users table exists
                    $this->db->query("DESCRIBE users");
                    $selectFields[] = 'u.username as user_username';
                    $selectFields[] = 'u.role as user_role';
                    $joinClauses[] = 'LEFT JOIN users u ON t.user_id = u.id';
                } catch (Exception $e) {
                    // Users table doesn't exist, use existing username/role columns if available
                    if (in_array('username', $columnNames)) {
                        $selectFields[] = 't.username as user_username';
                    }
                    if (in_array('role', $columnNames)) {
                        $selectFields[] = 't.role as user_role';
                    }
                }
            }
            
            // Check for sessions table
            try {
                $this->db->query("DESCRIBE special_access_sessions");
                $selectFields[] = 'COUNT(DISTINCT s.id) as active_sessions';
                $joinClauses[] = 'LEFT JOIN special_access_sessions s ON t.id = s.token_id AND s.is_active = 1 AND (s.expires_at IS NULL OR s.expires_at > NOW())';
            } catch (Exception $e) {
                // Sessions table doesn't exist, set default
                $selectFields[] = '0 as active_sessions';
            }
            
            $query = "
                SELECT " . implode(', ', $selectFields) . "
                FROM special_access_tokens t
                " . implode(' ', $joinClauses) . "
                GROUP BY t.id
                ORDER BY t.created_at DESC
            ";
            
            $stmt = $this->db->query($query);
            $tokens = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Fill in missing fields with defaults
            foreach ($tokens as &$token) {
                if (!isset($token['name'])) {
                    $token['name'] = $token['user_username'] ?? 'Unknown User';
                }
                if (!isset($token['email'])) {
                    $token['email'] = '';
                }
                if (!isset($token['usage_count'])) {
                    $token['usage_count'] = 0;
                }
                if (!isset($token['passkey'])) {
                    $token['passkey'] = 'Legacy Token';
                }
                if (!isset($token['description'])) {
                    $token['description'] = '';
                }
                if (!isset($token['last_used_at'])) {
                    $token['last_used_at'] = null;
                }
            }
            
            return $tokens;
            
        } catch (Exception $e) {
            error_log("Get all tokens failed: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Get token details
     */
    public function getTokenDetails($tokenId) {
        try {
            $stmt = $this->db->prepare("
                SELECT t.*, 
                    COUNT(DISTINCT s.id) as active_sessions,
                    MAX(s.last_activity) as last_session_activity
                FROM special_access_tokens t
                LEFT JOIN special_access_sessions s ON t.id = s.token_id AND s.is_active = 1
                WHERE t.id = ?
                GROUP BY t.id
            ");
            $stmt->execute([$tokenId]);
            
            return $stmt->fetch(PDO::FETCH_ASSOC);
            
        } catch (Exception $e) {
            return null;
        }
    }
    
    /**
     * Terminate specific session
     */
    public function terminateSession($sessionId) {
        try {
            $stmt = $this->db->prepare("
                UPDATE special_access_sessions 
                SET is_active = 0 
                WHERE session_id = ?
            ");
            $stmt->execute([$sessionId]);
            
            return ['success' => true];
            
        } catch (Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }
    
    /**
     * Log access event
     */
    private function logAccess($tokenId, $sessionId, $action, $pageUrl, $ipAddress, $userAgent) {
        try {
            $stmt = $this->db->prepare("
                INSERT INTO special_access_logs 
                (token_id, session_id, action, page_url, ip_address, user_agent) 
                VALUES (?, ?, ?, ?, ?, ?)
            ");
            $stmt->execute([$tokenId, $sessionId, $action, $pageUrl, $ipAddress, $userAgent]);
        } catch (Exception $e) {
            error_log("Log access failed: " . $e->getMessage());
        }
    }
    
    /**
     * Get access logs for a token
     */
    public function getAccessLogs($tokenId, $limit = 50) {
        try {
            $stmt = $this->db->prepare("
                SELECT * FROM special_access_logs 
                WHERE token_id = ? 
                ORDER BY created_at DESC 
                LIMIT ?
            ");
            $stmt->execute([$tokenId, $limit]);
            
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
            
        } catch (Exception $e) {
            return [];
        }
    }
    
    /**
     * Clean expired sessions
     */
    public function cleanExpiredSessions() {
        try {
            $stmt = $this->db->prepare("
                UPDATE special_access_sessions 
                SET is_active = 0 
                WHERE expires_at IS NOT NULL AND expires_at < NOW()
            ");
            $stmt->execute();
            
            return $stmt->rowCount();
            
        } catch (Exception $e) {
            error_log("Clean expired sessions failed: " . $e->getMessage());
            return 0;
        }
    }
    
    /**
     * Delete tokens with empty or 'Unknown' names (cleanup function)
     */
    public function cleanupUnknownTokens() {
        try {
            // First check if 'name' column exists
            $stmt = $this->db->query("DESCRIBE special_access_tokens");
            $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
            $columnNames = array_column($columns, 'Field');
            
            if (in_array('name', $columnNames)) {
                // Clean up based on name column
                $stmt = $this->db->prepare("
                    DELETE FROM special_access_tokens 
                    WHERE name = 'Unknown' OR name IS NULL OR name = '' OR TRIM(name) = ''
                ");
            } else {
                // Fallback: Clean up old tokens from different table structure
                // Delete tokens that don't have passkey (indicates old system)
                if (in_array('passkey', $columnNames)) {
                    $stmt = $this->db->prepare("
                        DELETE FROM special_access_tokens 
                        WHERE passkey IS NULL OR passkey = '' OR TRIM(passkey) = ''
                    ");
                } else {
                    // Very old table structure, delete all and let user recreate
                    $stmt = $this->db->prepare("
                        DELETE FROM special_access_tokens 
                        WHERE created_at < DATE_SUB(NOW(), INTERVAL 1 DAY)
                    ");
                }
            }
            
            $stmt->execute();
            $deletedCount = $stmt->rowCount();
            
            return [
                'success' => true,
                'deleted' => $deletedCount,
                'message' => $deletedCount > 0 ? "Cleaned up $deletedCount invalid token(s)" : "No cleanup needed - all tokens are valid"
            ];
            
        } catch (Exception $e) {
            error_log("Cleanup unknown tokens failed: " . $e->getMessage());
            return [
                'success' => false,
                'error' => "Cleanup failed: " . $e->getMessage()
            ];
        }
    }
}
?>
