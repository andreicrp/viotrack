<?php
session_start();

// Clear all session variables
session_unset();

// Destroy the session
session_destroy();

// Clear all remember me cookies
setcookie('viotrack_email', '', time() - 3600, '/');
setcookie('viotrack_password', '', time() - 3600, '/');
setcookie('viotrack_remember', '', time() - 3600, '/');
setcookie('viotrack_user_id', '', time() - 3600, '/');

// Redirect to login page with logout confirmation
header('Location: login.php?logged_out=1');
exit();
?>