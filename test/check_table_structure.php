<?php
/**
 * Check Table Structure for Special Access Tokens
 */
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db_connect.php';

try {
    $db = getDB();
    
    echo "=== CHECKING TABLE STRUCTURE ===\n";
    
    // Check if table exists
    $stmt = $db->query("SHOW TABLES LIKE 'special_access_tokens'");
    if ($stmt->rowCount() == 0) {
        echo "❌ Table 'special_access_tokens' does not exist!\n";
        exit(1);
    }
    
    echo "✅ Table 'special_access_tokens' exists\n\n";
    
    // Check table structure
    echo "=== TABLE COLUMNS ===\n";
    $stmt = $db->query("DESCRIBE special_access_tokens");
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    foreach ($columns as $column) {
        echo "   {$column['Field']} - {$column['Type']} - {$column['Null']} - {$column['Key']}\n";
    }
    
    // Check for specific columns we need
    echo "\n=== COLUMN CHECK ===\n";
    $requiredColumns = ['id', 'token', 'passkey', 'name', 'email', 'is_active', 'usage_count', 'created_at'];
    $existingColumns = array_column($columns, 'Field');
    
    foreach ($requiredColumns as $col) {
        if (in_array($col, $existingColumns)) {
            echo "✅ $col exists\n";
        } else {
            echo "❌ $col MISSING\n";
        }
    }
    
    // Show sample data
    echo "\n=== SAMPLE DATA ===\n";
    $stmt = $db->query("SELECT * FROM special_access_tokens LIMIT 3");
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (empty($rows)) {
        echo "✅ No data in table (clean state)\n";
    } else {
        foreach ($rows as $row) {
            echo "Row ID {$row['id']}:\n";
            foreach ($row as $key => $value) {
                echo "   $key: " . (is_null($value) ? 'NULL' : $value) . "\n";
            }
            echo "\n";
        }
    }
    
} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
}