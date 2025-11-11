<?php

declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../utils/cors.php';
api_apply_cors_headers();

$rawBody = file_get_contents('php://input');
$payload = json_decode($rawBody ?: '[]', true);
$email = is_string($payload['email'] ?? null) ? trim($payload['email']) : '';
$password = is_string($payload['password'] ?? null) ? trim($payload['password']) : '';

if ($email === '' || $password === '') {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => 'INVALID_CREDENTIALS',
        'message' => '이메일과 비밀번호를 모두 입력해 주세요.',
    ], JSON_UNESCAPED_UNICODE);
    return;
}

$expiresAt = time() + 86400;
$refreshExpiresAt = time() + (86400 * 7);

$response = [
    'success' => true,
    'data' => [
        'user' => [
            'id' => 'dev-preview-admin',
            'email' => $email,
            'full_name' => 'Developer Admin',
            'phone' => null,
            'address' => null,
            'role' => 'admin',
            'created_at' => date('c'),
        ],
        'token' => 'dev-preview-token',
        'refresh_token' => 'dev-preview-refresh',
        'expires_in' => 86400,
        'refresh_expires_in' => 86400 * 7,
        'issued_at' => time(),
        'environment' => 'dev-preview',
        'dev_bypass' => true,
    ],
    'meta' => [
        'expires_at' => $expiresAt,
        'refresh_expires_at' => $refreshExpiresAt,
    ],
];

echo json_encode($response, JSON_UNESCAPED_UNICODE);
