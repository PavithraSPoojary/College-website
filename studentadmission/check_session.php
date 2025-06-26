<?php
// check_session.php
header('Content-Type: application/json');
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");
header("Expires: 0");

session_start();

$response = ['logged_in' => isset($_SESSION['user_id']) && !empty($_SESSION['user_id'])];

echo json_encode($response);
exit();