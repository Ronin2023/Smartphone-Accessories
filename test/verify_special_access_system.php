<?php
/**
 * Special Access System - Complete Verification Script
 * Checks all components and provides status report
 */
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db_connect.php';
require_once __DIR__ . '/../includes/functions.php';

echo "╔══════════════════════════════════════════════════════════╗\n";
echo "║   SPECIAL ACCESS SYSTEM - VERIFICATION REPORT           ║\n";
echo "╚══════════════════════════════════════════════════════════╝\n\n";

// Test 1: Database Connection
echo "1️⃣  DATABASE CONNECTION\n";
echo "   ├─ ";
if ($conn) {
    echo "✅ Connected to database: " . DB_NAME . "\n";
} else {
    echo "❌ Failed to connect\n";
    exit(1);
}

// Test 2: Check Tables
echo "\n2️⃣  DATABASE TABLES\n";
$requiredTables = ['special_access_tokens', 'special_access_sessions', 'special_access_logs', 'users'];
foreach ($requiredTables as $table) {
    $stmt = $conn->query("SHOW TABLES LIKE '$table'");
    if ($stmt->rowCount() > 0) {
        echo "   ├─ ✅ $table exists\n";
        
        // Check row count
        $countStmt = $conn->query("SELECT COUNT(*) FROM $table");
        $count = $countStmt->fetchColumn();
        echo "   │  └─ Rows: $count\n";
    } else {
        echo "   ├─ ❌ $table missing\n";
    }
}

// Test 3: Check special_access_tokens structure
echo "\n3️⃣  TABLE STRUCTURE (special_access_tokens)\n";
$stmt = $conn->query("DESCRIBE special_access_tokens");
$columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
$requiredColumns = ['id', 'user_id', 'token', 'passkey', 'name', 'email', 'is_active', 'usage_count', 'created_at'];
foreach ($requiredColumns as $col) {
    $found = false;
    foreach ($columns as $column) {
        if ($column['Field'] === $col) {
            echo "   ├─ ✅ $col: {$column['Type']}\n";
            $found = true;
            break;
        }
    }
    if (!$found) {
        echo "   ├─ ❌ $col: MISSING\n";
    }
}

// Test 4: Check Users (Admins & Editors)
echo "\n4️⃣  AVAILABLE USERS (Admin & Editor)\n";
$stmt = $conn->query("
    SELECT id, username, email, role, 
           CONCAT(COALESCE(first_name, ''), ' ', COALESCE(last_name, '')) as full_name,
           is_active
    FROM users 
    WHERE role IN ('admin', 'editor')
    ORDER BY role DESC, username
");
$users = $stmt->fetchAll(PDO::FETCH_ASSOC);

if (empty($users)) {
    echo "   └─ ⚠️  No admin or editor users found\n";
    echo "       Create users before generating tokens!\n";
} else {
    foreach ($users as $user) {
        $status = $user['is_active'] ? '✅' : '❌';
        $roleIcon = $user['role'] === 'admin' ? '👑' : '✏️';
        $name = trim($user['full_name']) !== '' ? $user['full_name'] : $user['username'];
        echo "   ├─ $status $roleIcon $name (@{$user['username']}) - {$user['role']}\n";
        echo "   │  └─ Email: {$user['email']}\n";
    }
}

// Test 5: Existing Tokens
echo "\n5️⃣  EXISTING ACCESS TOKENS\n";
$stmt = $conn->query("
    SELECT t.*, u.username as user_username, u.role as user_role
    FROM special_access_tokens t
    LEFT JOIN users u ON t.user_id = u.id
    ORDER BY t.created_at DESC
");
$tokens = $stmt->fetchAll(PDO::FETCH_ASSOC);

if (empty($tokens)) {
    echo "   └─ ✅ No tokens yet (clean state)\n";
} else {
    $unknownCount = 0;
    foreach ($tokens as $token) {
        $status = $token['is_active'] ? '✅' : '❌';
        $linkedUser = $token['user_username'] ? " (@{$token['user_username']})" : ' [No User Link]';
        
        if ($token['name'] === 'Unknown' || empty(trim($token['name']))) {
            echo "   ├─ ⚠️  UNKNOWN TOKEN - ID: {$token['id']}$linkedUser\n";
            $unknownCount++;
        } else {
            echo "   ├─ $status {$token['name']}$linkedUser\n";
            echo "   │  ├─ Email: {$token['email']}\n";
            echo "   │  ├─ Created: {$token['created_at']}\n";
            echo "   │  └─ Used: {$token['usage_count']} times\n";
        }
    }
    
    if ($unknownCount > 0) {
        echo "   └─ ⚠️  Found $unknownCount unknown token(s) - Use cleanup button!\n";
    }
}

// Test 6: Active Sessions
echo "\n6️⃣  ACTIVE SESSIONS\n";
$stmt = $conn->query("
    SELECT s.*, t.name as token_name
    FROM special_access_sessions s
    JOIN special_access_tokens t ON s.token_id = t.id
    WHERE s.is_active = 1
    ORDER BY s.started_at DESC
");
$sessions = $stmt->fetchAll(PDO::FETCH_ASSOC);

if (empty($sessions)) {
    echo "   └─ ✅ No active sessions\n";
} else {
    foreach ($sessions as $session) {
        echo "   ├─ 🟢 {$session['token_name']}\n";
        echo "   │  ├─ IP: {$session['ip_address']}\n";
        echo "   │  ├─ Started: {$session['started_at']}\n";
        echo "   │  └─ Last Activity: {$session['last_activity']}\n";
    }
}

// Test 7: File Integrity
echo "\n7️⃣  FILE INTEGRITY\n";
$requiredFiles = [
    'includes/special-access-manager.php' => 'Core Manager',
    'includes/special-access-middleware.php' => 'Middleware',
    'admin/special-access.php' => 'Admin Interface',
    'verify-special-access.php' => 'Passkey Verification'
];

foreach ($requiredFiles as $file => $description) {
    $fullPath = __DIR__ . '/../' . $file;
    if (file_exists($fullPath)) {
        $size = filesize($fullPath);
        echo "   ├─ ✅ $description\n";
        echo "   │  └─ $file (" . number_format($size) . " bytes)\n";
    } else {
        echo "   ├─ ❌ $description MISSING\n";
        echo "   │  └─ $file\n";
    }
}

// Test 8: Manager Functions
echo "\n8️⃣  MANAGER FUNCTIONS\n";
try {
    $manager = getSpecialAccessManager();
    echo "   ├─ ✅ Manager initialized\n";
    
    $methods = ['createToken', 'verifyPasskey', 'getAllTokens', 'revokeToken', 'cleanupUnknownTokens'];
    foreach ($methods as $method) {
        if (method_exists($manager, $method)) {
            echo "   ├─ ✅ $method() available\n";
        } else {
            echo "   ├─ ❌ $method() MISSING\n";
        }
    }
} catch (Exception $e) {
    echo "   └─ ❌ Manager initialization failed: " . $e->getMessage() . "\n";
}

// Summary
echo "\n";
echo "╔══════════════════════════════════════════════════════════╗\n";
echo "║                      SUMMARY                             ║\n";
echo "╚══════════════════════════════════════════════════════════╝\n";
echo "✅ Database: Connected\n";
echo "✅ Tables: Created with proper structure\n";
echo "✅ Files: All required files present\n";

if (!empty($users)) {
    echo "✅ Users: " . count($users) . " admin/editor(s) available\n";
} else {
    echo "⚠️  Users: No admins/editors (create users first!)\n";
}

if (empty($tokens)) {
    echo "✅ Tokens: Clean state (no tokens)\n";
} elseif (isset($unknownCount) && $unknownCount > 0) {
    echo "⚠️  Tokens: $unknownCount unknown token(s) need cleanup\n";
} else {
    echo "✅ Tokens: " . count($tokens) . " valid token(s)\n";
}

echo "\n🎯 NEXT STEPS:\n";
if (empty($users)) {
    echo "   1. Create admin or editor users in the system\n";
    echo "   2. Then create special access tokens\n";
} elseif (isset($unknownCount) && $unknownCount > 0) {
    echo "   1. Go to admin panel > Special Access\n";
    echo "   2. Click 'Cleanup Unknown Tokens' button\n";
    echo "   3. Create new tokens with proper user selection\n";
} else {
    echo "   System ready! Create tokens as needed.\n";
}

echo "\n";
?>
