<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

$file = $_GET['file'] ?? '';
if (empty($file)) {
    http_response_code(404);
    echo "File not found.";
    exit;
}

$base_dir = 'C:/wamp64/www/mgmec1/studentadmission/uploads';
$base_dir = realpath($base_dir);
if ($base_dir === false) {
    http_response_code(500);
    echo "Base directory does not exist.";
    exit;
}

$debug = $_GET['debug'] ?? false;
// Normalize and sanitize path
$requested_path = str_replace(['..', '\\'], ['', '/'], $file);
// Remove leading 'uploads/' if present
if (strpos($requested_path, 'uploads/') === 0) {
    $requested_path = substr($requested_path, strlen('uploads/'));
}

$target = $base_dir . DIRECTORY_SEPARATOR . $requested_path;
$resolved_target = realpath($target);

if ($debug) {
    echo "<h3>Debug Information:</h3>";
    echo "Requested file: " . htmlspecialchars($file) . "<br>";
    echo "Base directory: " . $base_dir . "<br>";
    echo "Current directory: " . __DIR__ . "<br>";
    echo "Base dir exists: " . (is_dir($base_dir) ? 'YES' : 'NO') . "<br>";
    echo "Sanitized path: " . htmlspecialchars($requested_path) . "<br>";
    echo "Target path: " . $target . "<br>";
    echo "Resolved target: " . ($resolved_target ?: 'NULL') . "<br>";
    echo "Target exists: " . (file_exists($target) ? 'YES' : 'NO') . "<br>";
    echo "Is file: " . (is_file($target) ? 'YES' : 'NO') . "<br>";
    echo "Is readable: " . (is_readable($target) ? 'YES' : 'NO') . "<br>";
    if (is_dir($base_dir)) {
        echo "<br><strong>Files in uploads directory:</strong><br>";
        $files = scandir($base_dir);
        foreach ($files as $f) {
            if ($f !== '.' && $f !== '..') {
                $full_path = $base_dir . DIRECTORY_SEPARATOR . $f;
                echo "- " . $f . " (is_file: " . (is_file($full_path) ? 'YES' : 'NO') . ")<br>";
                if (is_dir($full_path)) {
                    $sub_files = scandir($full_path);
                    foreach ($sub_files as $sf) {
                        if ($sf !== '.' && $sf !== '..') {
                            echo "  - " . $f . "/" . $sf . "<br>";
                        }
                    }
                }
            }
        }
    }
    exit;
}

if ($resolved_target && strpos($resolved_target, $base_dir) === 0 && is_file($resolved_target)) {
    $mime = mime_content_type($resolved_target) ?: 'application/octet-stream';
    $filesize = filesize($resolved_target);
    $filename = basename($resolved_target);
    
    header('Content-Type: ' . $mime);
    header('Content-Length: ' . $filesize);
    if ($mime === 'application/pdf') {
        header('Content-Disposition: inline; filename="' . $filename . '"');
    }
    if (strpos($mime, 'image/') === 0) {
        header('Cache-Control: public, max-age=31536000');
        header('Expires: ' . gmdate('D, d M Y H:i:s', time() + 31536000) . ' GMT');
    }
    header('X-Content-Type-Options: nosniff');
    
    readfile($resolved_target);
    exit;
}

http_response_code(404);
echo "File not found or access denied.<br>";
echo "Requested: " . htmlspecialchars($file) . "<br>";
echo "Looking for: " . htmlspecialchars($target) . "<br>";
?>