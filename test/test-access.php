<?php
echo "✅ Admin area is accessible!<br>";
echo "Current time: " . date('Y-m-d H:i:s') . "<br>";
echo "Request URI: " . $_SERVER['REQUEST_URI'] . "<br>";
echo "Document Root: " . $_SERVER['DOCUMENT_ROOT'] . "<br>";
echo "<a href='dashboard.php'>Go to Dashboard</a>";
?>