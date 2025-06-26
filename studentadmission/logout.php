<!-- 
// logout.php
session_set_cookie_params(0);
session_start();
session_regenerate_id(true);

setcookie(session_name(), '', 1, '/');
// Unset all session variables
session_unset();

// Destroy the session
session_destroy();

// Prevent caching of the logout page
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");
header("Expires: 0");

// Redirect to login page
session_start();
session_regenerate_id(true);
header("Location: login.php");
exit; -->

<?php
session_start();
$_SESSION = [];
session_destroy();

if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params['path'], $params['domain'],
        $params['secure'], $params['httponly']
    );
}

header("Location: login.php");
exit();
?>