<?php
session_start();  // Must start session to destroy it

// Clear all session data
$_SESSION = [];

// Destroy the session cookie if it exists
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

// Completely destroy the session
session_destroy();

// Redirect to login or home page
header("Location: login.php");  // or index.php if you prefer
exit;
?>