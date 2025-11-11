<?php

declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../utils/cors.php';
api_apply_cors_headers();

$schemaPath = __DIR__ . '/../../assets/api/navigation/menu-schema.php';

if (is_file($schemaPath)) {
    $payload = file_get_contents($schemaPath);
    if ($payload !== false) {
        echo $payload;
        return;
    }
}

http_response_code(500);
echo json_encode([
    'success' => false,
    'message' => 'Navigation schema unavailable',
], JSON_UNESCAPED_UNICODE);
?>
