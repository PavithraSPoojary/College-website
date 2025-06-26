<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Define paths
$tcpdf_path = 'C:/wamp64/www/mgmec1/adminpanel/admin.php/tcpdf/TCPDF-6.4.2/tcpdf.php';
$font_dir = 'C:/wamp64/www/mgmec1/fonts/';
$regular_ttf = $font_dir . 'Montserrat-Regular.ttf';
$bold_ttf = $font_dir . 'Montserrat-Bold.ttf';
$tcpdf_fonts_dir = 'C:/wamp64/www/mgmec1/adminpanel/admin.php/tcpdf/TCPDF-6.4.2/fonts/';

// Check if TCPDF exists
if (!file_exists($tcpdf_path)) {
    die("Error: TCPDF library not found at $tcpdf_path. Please verify the path or install TCPDF.");
}

// Include TCPDF
require_once($tcpdf_path);

// Check if .ttf files exist
if (!file_exists($regular_ttf)) {
    die("Error: Montserrat-Regular.ttf not found at $regular_ttf. Please place the file in $font_dir.");
}
if (!file_exists($bold_ttf)) {
    die("Error: Montserrat-Bold.ttf not found at $bold_ttf. Please place the file in $font_dir.");
}

// Check if fonts directory is writable
if (!is_writable($tcpdf_fonts_dir)) {
    die("Error: TCPDF fonts directory ($tcpdf_fonts_dir) is not writable. Please grant write permissions.");
}

try {
    $regular = TCPDF_FONTS::addTTFfont($regular_ttf, 'TrueTypeUnicode', '', 32);
    echo "Regular Font converted: $regular<br>";
} catch (Exception $e) {
    echo "Regular Font conversion failed: " . $e->getMessage() . "<br>";
}

try {
    $bold = TCPDF_FONTS::addTTFfont($bold_ttf, 'TrueTypeUnicode', '', 32);
    echo "Bold Font converted: $bold<br>";
} catch (Exception $e) {
    echo "Bold Font conversion failed: " . $e->getMessage() . "<br>";
}

// Verify generated files
$montserrat_file = $tcpdf_fonts_dir . 'montserrat.php';
$montserratb_file = $tcpdf_fonts_dir . 'montserratb.php';
echo file_exists($montserrat_file) ? "montserrat.php exists<br>" : "montserrat.php not found<br>";
echo file_exists($montserratb_file) ? "montserratb.php exists<br>" : "montserratb.php not found<br>";
?>