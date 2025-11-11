<?php
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

$input = json_decode(file_get_contents('php://input'), true);

// 임시로 토글된 상태로 반환
echo json_encode([
    'success' => true,
    'data' => [
        'id' => $input['id'],
        'is_published' => true,
        'updated_at' => date('c')
    ]
], JSON_UNESCAPED_UNICODE);
?>