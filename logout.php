<?php
session_unset();  // Unset all session variables
session_destroy();  // Destroy the session

// Redirect to index.html
header("Location: index.html");
exit;
?>
