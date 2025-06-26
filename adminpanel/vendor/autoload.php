<?php
spl_autoload_register(function ($class) {
    // Handle PhpOffice namespace
    if (strpos($class, 'PhpOffice\\PhpSpreadsheet\\') === 0) {
        $base_dir = 'C:\\wamp64\\www\\admin panel\\vendor\\phpoffice\\PhpSpreadsheet-4.2.0\\PhpSpreadsheet-4.2.0\\src\\';
        $relative_class = substr($class, strlen('PhpOffice\\PhpSpreadsheet\\'));
        $file = $base_dir . str_replace('\\', '/', $relative_class) . '.php';
        
        if (file_exists($file)) {
            require $file;
            return;
        }
    }

    // Handle Psr namespace
    if (strpos($class, 'Psr\\SimpleCache\\') === 0) {
        $base_dir = 'C:\\wamp64\\www\\admin panel\\vendor\\phpoffice\\PhpSpreadsheet-4.2.0\\PhpSpreadsheet-4.2.0\\src\\';
        $relative_class = substr($class, strlen('Psr\\SimpleCache\\'));
        $file = $base_dir . 'Psr/SimpleCache/' . str_replace('\\', '/', $relative_class) . '.php';
        
        if (file_exists($file)) {
            require $file;
            return;
        }
    }
});
