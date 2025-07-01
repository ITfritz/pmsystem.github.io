<?php
session_start(); // Start the session if not already started

// 1. Regenerate session ID to prevent session fixation attacks
// Pass true to delete the old session file immediately
session_regenerate_id(true);

// 2. Unset all session variables
// This clears the $_SESSION superglobal array
$_SESSION = array();

// 3. Destroy the session on the server
// This deletes the session file/data associated with the session ID
session_destroy();

// 4. Invalidate the session cookie on the client's browser
// This ensures the client's browser discards the session cookie
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

// 5. Prevent caching of the logout page
// This ensures that the browser doesn't serve a cached version of the logout page
// and that the user is truly logged out and gets a fresh login page on redirect.
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");

// 6. Redirect to the login page
// Use an absolute path if login.php is not in the same directory or a sub-directory
// Example for your structure (if login.php is in projectmanagementsystem4/registration-form/)
header("Location: /projectmanagementsystem4/registration-form/login.php");
exit(); // Always call exit() after header redirects to ensure script termination
?>
