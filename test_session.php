<?php
session_start();
require_once 'includes/config.php';

// Set a test error
$_SESSION['error'] = 'This is a test error message';

// Redirect to user_login using the redirect function
require_once 'includes/functions.php';
redirect('user_login');
?>
