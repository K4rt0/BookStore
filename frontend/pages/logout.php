<?php
// pages/logout.php
session_start();

// Clear all session variables
$_SESSION = [];

// Destroy the session
session_destroy();

// Redirect to the homepage
header("Location: /"); // Updated redirect to new homepage URL
exit();