<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Simulate what the API does
$_GET['id'] = '165'; // String, like HTTP query params

try {
    include __DIR__ . '/public_html/api/products/get.php';
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
    echo "File: " . $e->getFile() . ":" . $e->getLine() . "\n";
    echo $e->getTraceAsString();
}
