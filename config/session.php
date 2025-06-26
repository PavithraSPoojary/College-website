<?php
// Set session cookie parameters before starting the session
ini_set('session.cookie_httponly', 1);
ini_set('session.use_only_cookies', 1);
ini_set('session.cookie_secure', 1);

// Start the session
session_start();

// Set session timeout (30 minutes)
$session_timeout = 1800; // 30 minutes in seconds

// Check if session has expired
if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity'] > $session_timeout)) {
    session_unset();
    session_destroy();
    header("Location: " . BASE_URL . "/pages/login.php?timeout=1");
    exit();
}

// Update last activity timestamp
$_SESSION['last_activity'] = time();

// Function to check if user is logged in
function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

// Function to require login
function requireLogin() {
    if (!isLoggedIn()) {
        header("Location: " . BASE_URL . "/pages/login.php");
        exit();
    }
}

// Function to logout user
function logout() {
    session_unset();
    session_destroy();
    header("Location: " . BASE_URL . "/pages/login.php");
    exit();
}
?> 