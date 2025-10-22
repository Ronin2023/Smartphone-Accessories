<?php
// Calculate today's admin key
$site_name = 'TechCompare';
$today = date('Y-m-d');
$admin_key = md5($site_name . $today);

echo "<h2>🔑 Admin Security Key for Today</h2>";
echo "<p><strong>Date:</strong> " . $today . "</p>";
echo "<p><strong>Site Name:</strong> " . $site_name . "</p>";
echo "<p><strong>Admin Key:</strong> <code style='background:#f0f0f0; padding:5px; font-size:16px;'>" . $admin_key . "</code></p>";

echo "<h3>📋 Quick Access URLs:</h3>";
echo "<ul>";
echo "<li><strong>Disable Maintenance:</strong><br><code>maintenance-control.php?action=disable&key=" . $admin_key . "</code></li>";
echo "<li><strong>Enable Maintenance:</strong><br><code>maintenance-control.php?action=enable&key=" . $admin_key . "</code></li>";
echo "<li><strong>Check Status:</strong><br><code>maintenance-control.php?action=status&key=" . $admin_key . "</code></li>";
echo "<li><strong>Admin Bypass:</strong><br><code>index.html?admin_bypass=1&key=" . $admin_key . "</code></li>";
echo "</ul>";

echo "<h3>🔄 Alternative Access Methods:</h3>";
echo "<ul>";
echo "<li><strong>Admin Dashboard:</strong> <a href='admin/dashboard.php'>admin/dashboard.php</a> (should work even during maintenance)</li>";
echo "<li><strong>Simple Bypass:</strong> <a href='index.html?admin_bypass=1'>index.html?admin_bypass=1</a></li>";
echo "<li><strong>Maintenance Control:</strong> <a href='maintenance-control.php'>maintenance-control.php</a></li>";
echo "</ul>";

echo "<p><em>💡 Note: The admin key changes daily for security. Bookmark this page to get the current key anytime.</em></p>";
?>