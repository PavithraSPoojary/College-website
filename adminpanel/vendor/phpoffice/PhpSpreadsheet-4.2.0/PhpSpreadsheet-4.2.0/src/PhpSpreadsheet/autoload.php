<?php
spl_autoload_register(function ($class) {
    // Base directory for the PhpSpreadsheet classes
    $base_dir = __DIR__;
    
    // Convert namespace to directory path
    $class = str_replace('\\', '/', $class);
    
    // Check if the class is in the PhpSpreadsheet namespace
    if (strpos($class, 'PhpOffice/PhpSpreadsheet/') === 0) {
        $file = $base_dir . '/' . substr($class, strlen('PhpOffice/PhpSpreadsheet/')) . '.php';
        if (file_exists($file)) {
            require $file;
            return true;
        }
    }
    
    // Check if the class is in the Psr namespace
    if (strpos($class, 'Psr/SimpleCache/') === 0) {
        $file = $base_dir . '/../Psr/SimpleCache/' . substr($class, strlen('Psr/SimpleCache/')) . '.php';
        if (file_exists($file)) {
            require $file;
            return true;
        }
    }
    
    // Check if the class is in the Composer Pcre namespace
    if (strpos($class, 'Composer/Pcre/') === 0) {
        $file = $base_dir . '/../../../../composer/pcre/' . substr($class, strlen('Composer/Pcre/')) . '.php';
        if (file_exists($file)) {
            require $file;
            return true;
        }
    }
    
    return false;
});
