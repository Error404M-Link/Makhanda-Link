
<?php

session_start();

// Clear all session variables
$_SESSION = array();

// Destroy the session
session_destroy();

// Take the user to the homepage
header("Location: ../PUBLIC PAGES/login.php");
exit();

?>
