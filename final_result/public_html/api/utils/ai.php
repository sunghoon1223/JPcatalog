<?php

declare(strict_types=1);

require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/rate_limit_store.php';

function ai_get_request_headers(): array
{
    if (function_exists('getallheaders')) {
        $headers = getallheaders();
        if (is_array($headers)) {
            return $headers;
        }
    }

    $headers = [];
    foreach ($_SERVER as $key => $value) {
        if (strpos($key, 'HTTP_') !== 0) {
            continue;
        }
        $normalized = str_replace(' ', '-', ucwords(strtolower(str_replace('_', ' ', substr($key, 5)))));
        $headers[$normalized] = $value;
    }

    return $headers;
}

function ai_extract_dev_bypass_token(?array $headers = null): ?string
{
    if ($headers === null) {
        $headers = ai_get_request_headers();
    }

    $candidates = [
        $headers['X-Dev-Bypass-Token'] ?? null,
        $headers['X-Auth-Dev-Bypass'] ?? null,
        $headers['x-dev-bypass-token'] ?? null,
        $headers['x-auth-dev-bypass'] ?? null,
    ];

    foreach ($candidates as $value) {
        if ($value === null) {
            continue;
        }
        $trimmed = trim((string) $value);
        if ($trimmed === '') {
            continue;
        }
        return $trimmed;
    }

    return null;
}

function ai_header_matches_dev_bypass(array $headers): bool
{
    $expected = auth_env('DEV_BYPASS_TOKEN') ?? 'dev-bypass-placeholder';
    $candidate = ai_extract_dev_bypass_token($headers);

    if ($candidate === null || $candidate === '') {
        return false;
    }

    return hash_equals($expected, $candidate) || ($candidate === '1' && auth_is_dev_environment());
}

function ai_resolve_request_context(): array
{
    $headers = ai_get_request_headers();
    $token = auth_extract_bearer_token();

    if ($token !== null) {
        $claims = auth_decode_jwt($token);
        if ($claims !== null) {
            return [
                'user' => [
                    'email' => $claims['sub'] ?? AUTH_EXPECTED_EMAIL,
                    'role' => $claims['role'] ?? AUTH_DEFAULT_ROLE,
                    'name' => $claims['name'] ?? 'API Admin',
                ],
                'environment' => $claims['env'] ?? (auth_is_dev_environment() ? 'dev' : 'prod'),
                'dev_bypass' => ($claims['env'] ?? null) === 'dev',
            ];
        }
    }

    if (auth_is_dev_environment() && ai_header_matches_dev_bypass($headers)) {
        return [
            'user' => [
                'email' => 'dev-bypass@jpcaster.local',
                'role' => 'developer',
                'name' => 'Dev Bypass',
            ],
            'environment' => 'dev',
            'dev_bypass' => true,
        ];
    }

    auth_json_response([
        'success' => false,
        'code' => 'auth_invalid',
        'message' => 'Missing or invalid access token. Provide dev bypass header or JWT.',
    ], 401);
}

function ai_apply_rate_limit_headers(string $bucket, int $limit, int $windowSeconds): array
{
    $result = ai_rate_limit_consume($bucket, $limit, $windowSeconds);

    header('X-RateLimit-Limit: ' . $result['limit']);
    header('X-RateLimit-Remaining: ' . $result['remaining']);
    header('X-RateLimit-Reset: ' . $result['reset_at']);
    header('Retry-After: ' . $result['retry_after']);

    return $result;
}

function ai_abort_if_rate_limited(array $result): void
{
    if (!empty($result['allowed'])) {
        return;
    }

    auth_json_response([
        'success' => false,
        'code' => 'rate_limited',
        'message' => 'Rate limit exceeded. Please try again later.',
        'meta' => [
            'retry_after' => $result['retry_after'] ?? 60,
            'reset_at' => $result['reset_at'] ?? (time() + 60),
        ],
    ], 429);
}

function ai_read_json_body(): array
{
    $input = file_get_contents('php://input');
    if ($input === false || $input === '') {
        return [];
    }

    $decoded = json_decode($input, true);
    if (!is_array($decoded)) {
        auth_json_response([
            'success' => false,
            'code' => 'validation_error',
            'message' => 'Request body must be valid JSON.',
        ], 400);
    }

    return $decoded;
}

function ai_normalize_locale(?string $candidate): string
{
    if ($candidate === null) {
        return 'ko-KR';
    }

    $trimmed = trim($candidate);
    if ($trimmed === '') {
        return 'ko-KR';
    }

    $normalized = str_replace('_', '-', $trimmed);
    $parts = explode('-', $normalized);
    $language = strtolower($parts[0] ?? 'ko');
    $region = strtoupper($parts[1] ?? 'KR');

    return $language . '-' . $region;
}

function ai_error(string $code, string $message, int $status = 400, array $extra = []): void
{
    auth_json_response(array_merge([
        'success' => false,
        'code' => $code,
        'message' => $message,
    ], $extra), $status);
}
