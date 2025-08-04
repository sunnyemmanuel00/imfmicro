<?php
/**
 * logout.php - FINAL VERSION
 * This script destroys the user's session and then uses a JavaScript redirect,
 * because it is loaded inside the main page template.
 */

// Start the session so we can access it.
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Unset all of the session variables.
$_SESSION = array();

// If you are using session cookies, delete the cookie.
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

// Finally, destroy the session data on the server.
session_destroy();

// Use a JavaScript redirect to go to the homepage, as you requested.
echo '<script>window.location.href = "index.php";</script>';

// Stop any further script execution.
exit;

?>