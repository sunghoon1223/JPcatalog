<?php

declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../utils/cors.php';
api_apply_cors_headers();

echo json_encode([
    'success' => true,
    'data' => [
        'id' => 'dev-preview-admin',
        'email' => 'api-admin@jpcaster.com',
        'full_name' => 'Developer Admin',
        'phone' => null,
        'address' => null,
        'role' => 'admin',
        'created_at' => date('c'),
    ],
], JSON_UNESCAPED_UNICODE);
