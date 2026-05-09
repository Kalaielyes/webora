<?php
$scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
$host   = $_SERVER['HTTP_HOST'] ?? 'localhost';
$script = $_SERVER['SCRIPT_NAME'] ?? '';
$base   = preg_replace('#/(views|controllers|models|index\.php).*$#', '', $script);
$app_url = $scheme . '://' . $host . $base;

echo "Script: " . $script . "\n";
echo "Base: " . $base . "\n";
echo "APP_URL: " . $app_url . "\n";
