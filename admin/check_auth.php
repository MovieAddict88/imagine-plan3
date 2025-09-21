<?php
session_start();

// Check if the user is logged in.
// If not, redirect them to the login page.
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

// Optional: You can also add activity-based session timeout logic here
// For example, update a 'last_activity' timestamp and check it.
?>
