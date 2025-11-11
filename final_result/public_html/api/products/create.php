<?php
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

$input = json_decode(file_get_contents('php://input'), true);

$newProduct = array_merge($input, [
    'id' => uniqid(),
    'created_at' => date('c'),
    'updated_at' => date('c')
]);

echo json_encode([
    'success' => true,
    'data' => $newProduct
], JSON_UNESCAPED_UNICODE);
?>