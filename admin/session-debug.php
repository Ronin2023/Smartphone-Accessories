<?php
session_start();
require_once '../includes/config.php';
require_once '../includes/functions.php';

echo "<h1>Session Debug Information</h1>";
echo "<pre>";

echo "=== Session Data ===\n";
print_r($_SESSION);

echo "\n=== Check Functions ===\n";
echo "isLoggedIn(): " . (isLoggedIn() ? 'YES' : 'NO') . "\n";
echo "hasAdminAccess(): " . (hasAdminAccess() ? 'YES' : 'NO') . "\n";
echo "isAdmin(): " . (isAdmin() ? 'YES' : 'NO') . "\n";
echo "isEditor(): " . (isEditor() ? 'YES' : 'NO') . "\n";

echo "\n=== Expected Session Variables ===\n";
echo "user_id: " . ($_SESSION['user_id'] ?? 'NOT SET') . "\n";
echo "username: " . ($_SESSION['username'] ?? 'NOT SET') . "\n";
echo "user_role: " . ($_SESSION['user_role'] ?? 'NOT SET') . "\n";

echo "</pre>";

echo "<hr>";
echo "<h2>Actions</h2>";
if (!isLoggedIn()) {
    echo "<p>You are not logged in.</p>";
    echo "<a href='login.php'>Go to Login Page</a>";
} else {
    echo "<p>You are logged in as: <strong>" . htmlspecialchars($_SESSION['username']) . "</strong></p>";
    echo "<p>Role: <strong>" . htmlspecialchars($_SESSION['user_role']) . "</strong></p>";
    echo "<br>";
    echo "<a href='special-access.php'>Go to Special Access Page</a> | ";
    echo "<a href='maintenance-manager.php'>Go to Maintenance Manager</a> | ";
    echo "<a href='dashboard.php'>Go to Dashboard</a> | ";
    echo "<a href='logout.php'>Logout</a>";
}
?>
