<?php

declare(strict_types=1);

function api_apply_cors_headers(): void
{
    // Build a safe allowlist that always includes the current host
    // and the same port on localhost/127.0.0.1 for local testing.
    $defaultOrigins = [
        'http://127.0.0.1:4200',
        'https://127.0.0.1:4200',
        'http://localhost:4200',
        'https://localhost:4200',
    ];

    $httpHost = $_SERVER['HTTP_HOST'] ?? '';
    if ($httpHost !== '') {
        $defaultOrigins[] = 'http://' . $httpHost;
        $defaultOrigins[] = 'https://' . $httpHost;

        // Extract the port from HTTP_HOST, if any, and explicitly allow
        // localhost/127.0.0.1 on the same port to avoid CORS mismatch
        // between http://localhost:<port> and http://127.0.0.1:<port>.
        $port = null;
        if (strpos($httpHost, ':') !== false) {
            $parts = explode(':', $httpHost, 2);
            $port = $parts[1] ?? null;
        }
        if ($port !== null && $port !== '') {
            $defaultOrigins[] = 'http://localhost:' . $port;
            $defaultOrigins[] = 'https://localhost:' . $port;
            $defaultOrigins[] = 'http://127.0.0.1:' . $port;
            $defaultOrigins[] = 'https://127.0.0.1:' . $port;
        }
    }

    // If an explicit origin is provided, prefer echoing it back when it is a
    // local origin or matches the allowlist. This avoids the common dev case
    // where the page is on http://localhost:8000 but the server resolves as
    // http://127.0.0.1:8000, causing preflight failure.
    $origin = $_SERVER['HTTP_ORIGIN'] ?? null;
    $allowedOrigins = array_values(array_unique(array_filter($defaultOrigins)));
    $matchedOrigin = null;

    if ($origin !== null && $origin !== '') {
        if (in_array($origin, $allowedOrigins, true)) {
            $matchedOrigin = $origin;
        } else {
            // Consider any localhost/127.0.0.1 origin as allowed for local runs
            if (preg_match('#^https?://(localhost|127\\.0\\.0\\.1)(?::\\d+)?$#i', $origin)) {
                $matchedOrigin = $origin;
            }
        }
    }

    if ($matchedOrigin === null) {
        $fallbackHost = $httpHost !== '' ? $httpHost : '127.0.0.1:8000';
        $matchedOrigin = 'http://' . $fallbackHost;
    }

    header('Access-Control-Allow-Origin: ' . $matchedOrigin);
    header('Access-Control-Allow-Credentials: true');
    header('Vary: Origin');
    header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With, X-Dev-Bypass-Token, X-Auth-Dev-Bypass');
    header('Access-Control-Allow-Methods: GET, POST, OPTIONS');

    if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'OPTIONS') {
        http_response_code(204);
        exit;
    }
}
?>
