<?php
// logout.php - Logout script
session_start();

// Check if user is logged in
if (isset($_SESSION['authenticated'])) {
    // Destroy all session data
    session_unset();
    session_destroy();
    
    // Redirect to login page
    header('Location: login.php');
    exit;
} else {
    // If not logged in, redirect to login page
    header('Location: login.php');
    exit;
}
?>