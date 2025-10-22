<?php
/**
 * Check Special Access Tables
 */
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db_connect.php';

try {
    // Check for special access tables
    $stmt = $conn->query("SHOW TABLES LIKE 'special_access_%'");
    $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    echo "=== Special Access Tables ===\n";
    if (empty($tables)) {
        echo "❌ No special access tables found!\n";
    } else {
        echo "✅ Found " . count($tables) . " tables:\n";
        foreach ($tables as $table) {
            echo "   - $table\n";
            
            // Get row count
            $countStmt = $conn->query("SELECT COUNT(*) FROM $table");
            $count = $countStmt->fetchColumn();
            echo "     Rows: $count\n";
        }
    }
    
    // Check users table
    echo "\n=== Users Table (Admins & Editors) ===\n";
    $stmt = $conn->query("SELECT id, username, CONCAT(first_name, ' ', last_name) as full_name, email, role FROM users WHERE role IN ('admin', 'editor') ORDER BY role, username");
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (empty($users)) {
        echo "❌ No admin or editor users found!\n";
    } else {
        echo "✅ Found " . count($users) . " users:\n";
        foreach ($users as $user) {
            echo "   - ID: {$user['id']} | {$user['username']} ({$user['full_name']}) | {$user['role']} | {$user['email']}\n";
        }
    }
    
    // Check existing tokens
    if (in_array('special_access_tokens', $tables)) {
        echo "\n=== Existing Tokens ===\n";
        $stmt = $conn->query("SELECT id, name, email, passkey, is_active, usage_count, created_at FROM special_access_tokens ORDER BY created_at DESC");
        $tokens = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        if (empty($tokens)) {
            echo "✅ No tokens exist (clean state)\n";
        } else {
            echo "Found " . count($tokens) . " tokens:\n";
            foreach ($tokens as $token) {
                $status = $token['is_active'] ? '✅ Active' : '❌ Revoked';
                echo "   - ID: {$token['id']} | {$token['name']} | {$token['email']} | $status | Used: {$token['usage_count']}\n";
                echo "     Passkey: {$token['passkey']}\n";
            }
        }
    }
    
} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
}
