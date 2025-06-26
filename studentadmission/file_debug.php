<?php
// file_debug.php - Standalone debug script
?>
<!DOCTYPE html>
<html>
<head>
    <title>File Debug Test</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        .debug-box { background: #f0f0f0; padding: 15px; margin: 10px 0; border-radius: 5px; }
        .error { color: red; }
        .success { color: green; }
        img { border: 1px solid #ccc; margin: 10px 0; max-width: 200px; }
    </style>
</head>
<body>
    <h1>File Debug Information</h1>
    
    <?php
    // Test your specific file paths
    $test_files = [
        'uploads/photos/6833e2fcbd971.jpg',
        'uploads/id_proofs/6833e34d91fa0.jpg'
    ];
    
    echo '<div class="debug-box">';
    echo '<h3>Server Information:</h3>';
    echo 'Current directory: ' . __DIR__ . '<br>';
    echo 'Document root: ' . $_SERVER['DOCUMENT_ROOT'] . '<br>';
    echo 'Script name: ' . $_SERVER['SCRIPT_NAME'] . '<br>';
    echo 'HTTP Host: ' . $_SERVER['HTTP_HOST'] . '<br>';
    echo '</div>';
    
    foreach ($test_files as $file_path) {
        echo '<div class="debug-box">';
        echo '<h3>Testing: ' . htmlspecialchars($file_path) . '</h3>';
        
        $full_path = __DIR__ . '/' . $file_path;
        $normalized_path = realpath($full_path);
        
        echo 'Full path: ' . $full_path . '<br>';
        echo 'Normalized path: ' . ($normalized_path ?: 'NULL') . '<br>';
        echo 'File exists: ' . (file_exists($full_path) ? '<span class="success">YES</span>' : '<span class="error">NO</span>') . '<br>';
        echo 'Is readable: ' . (is_readable($full_path) ? '<span class="success">YES</span>' : '<span class="error">NO</span>') . '<br>';
        echo 'File size: ' . (file_exists($full_path) ? filesize($full_path) . ' bytes' : 'N/A') . '<br>';
        
        if (file_exists($full_path)) {
            $mime = mime_content_type($full_path);
            echo 'MIME type: ' . $mime . '<br>';
            
            // Test direct access
            $direct_url = '/' . $file_path;
            echo 'Direct URL test: <a href="' . $direct_url . '" target="_blank">' . $direct_url . '</a><br>';
            
            // Test serve_file.php access
            $serve_url = 'serve_file.php?file=' . urlencode(str_replace('uploads/', '', $file_path));
            echo 'Serve file URL: <a href="' . $serve_url . '" target="_blank">' . $serve_url . '</a><br>';
            echo 'Debug serve file: <a href="' . $serve_url . '&debug=1" target="_blank">Debug info</a><br>';
            
            // Try to display image
            if (strpos($mime, 'image/') === 0) {
                echo '<br>Image preview (direct):<br>';
                echo '<img src="' . $direct_url . '" alt="Direct access" onerror="this.style.display=\'none\'; this.nextSibling.style.display=\'block\';">';
                echo '<div style="display:none; color:red;">Direct access failed</div>';
                
                echo '<br>Image preview (serve_file.php):<br>';
                echo '<img src="' . $serve_url . '" alt="Serve file access" onerror="this.style.display=\'none\'; this.nextSibling.style.display=\'block\';">';
                echo '<div style="display:none; color:red;">Serve file access failed</div>';
            }
        }
        
        echo '</div>';
    }
    ?>
    
    <div class="debug-box">
        <h3>Directory Listing:</h3>
        <?php
        $uploads_dir = __DIR__ . '/uploads';
        if (is_dir($uploads_dir)) {
            echo '<strong>uploads/ directory contents:</strong><br>';
            $files = scandir($uploads_dir);
            foreach ($files as $file) {
                if ($file !== '.' && $file !== '..') {
                    $file_path = $uploads_dir . '/' . $file;
                    echo '- ' . $file;
                    if (is_dir($file_path)) {
                        echo ' (directory)<br>';
                        $sub_files = scandir($file_path);
                        foreach ($sub_files as $sub_file) {
                            if ($sub_file !== '.' && $sub_file !== '..') {
                                echo '&nbsp;&nbsp;- ' . $sub_file . '<br>';
                            }
                        }
                    } else {
                        echo ' (' . filesize($file_path) . ' bytes)<br>';
                    }
                }
            }
        } else {
            echo '<span class="error">uploads/ directory not found!</span>';
        }
        ?>
    </div>
</body>
</html>