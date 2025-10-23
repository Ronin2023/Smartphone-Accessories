<?php
session_start();
require_once '../includes/config.php';
require_once '../includes/functions.php';

// Clear all session data
session_destroy();

// Clear any remember me cookies if they exist
if (isset($_COOKIE['remember_token'])) {
    setcookie('remember_token', '', time() - 3600, '/');
}

// Redirect to login page
redirect('index?logged_out=1');
?>
