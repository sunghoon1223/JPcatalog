<?php

declare(strict_types=1);

const AUTH_EXPECTED_EMAIL = 'admin@jpcaster.local';
const AUTH_DEFAULT_ROLE = 'admin';

function auth_env(string $key, ?string $default = null): ?string
{
    $candidates = [
        $_ENV[$key] ?? null,
        $_SERVER[$key] ?? null,
        getenv($key) ?: null,
    ];

    foreach ($candidates as $value) {
        if ($value === null || $value === '') {
            continue;
        }
        return (string) $value;
    }

    return $default;
}

function auth_is_dev_environment(): bool
{
    $env = auth_env('SUPABASE_ENV', 'dev');
    return strtolower((string) $env) !== 'prod';
}

function auth_get_authorization_header(): ?string
{
    if (function_exists('getallheaders')) {
        $headers = getallheaders();
        if (isset($headers['Authorization'])) {
            return $headers['Authorization'];
        }
    }

    if (isset($_SERVER['HTTP_AUTHORIZATION'])) {
        return $_SERVER['HTTP_AUTHORIZATION'];
    }

    return null;
}

function auth_extract_bearer_token(): ?string
{
    $header = auth_get_authorization_header();
    if (!$header) {
        return null;
    }

    if (stripos($header, 'Bearer ') !== 0) {
        return null;
    }

    $token = trim(substr($header, 7));
    return $token !== '' ? $token : null;
}

function auth_decode_jwt(string $token): ?array
{
    $parts = explode('.', $token);
    if (count($parts) !== 3) {
        return null;
    }

    $payload = json_decode(auth_base64url_decode($parts[1]) ?: '', true);
    if (!is_array($payload)) {
        return null;
    }

    return $payload;
}

function auth_base64url_decode(string $value): string
{
    $remainder = strlen($value) % 4;
    if ($remainder) {
        $value .= str_repeat('=', 4 - $remainder);
    }
    $value = strtr($value, '-_', '+/');
    return base64_decode($value) ?: '';
}

function auth_json_response(array $payload, int $status = 200): void
{
    http_response_code($status);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}
