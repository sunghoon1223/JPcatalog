<?php
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

$input = json_decode(file_get_contents('php://input'), true);

$updatedProduct = array_merge($input, [
    'updated_at' => date('c')
]);

echo json_encode([
    'success' => true,
    'data' => $updatedProduct
], JSON_UNESCAPED_UNICODE);
?>