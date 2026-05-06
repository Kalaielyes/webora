<?php
// test_email.php
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Test just the include
echo "Testing include...\n";
$include_path = __DIR__ . '/helpers/mailer.php';
echo "Looking for: " . $include_path . "\n";

if (file_exists($include_path)) {
    echo "File exists!\n";
    require_once $include_path;
    echo "Include successful!\n";
    
    if (function_exists('sendMail')) {
        echo "sendMail function exists!\n";
    } else {
        echo "sendMail function NOT found!\n";
    }
} else {
    echo "File NOT found!\n";
    echo "Current directory: " . __DIR__ . "\n";
    echo "Files in current directory: " . print_r(scandir(__DIR__), true) . "\n";
    
    if (is_dir(__DIR__ . '/helpers')) {
        echo "helpers directory exists. Files in helpers: " . print_r(scandir(__DIR__ . '/helpers'), true) . "\n";
    } else {
        echo "helpers directory does NOT exist!\n";
    }
}
?>