<?php

declare(strict_types=1);

/**
 * Lightweight Supabase client helpers.
 */

$supabase_url = rtrim(
    getenv('SUPABASE_URL')
        ?: getenv('NEXT_PUBLIC_SUPABASE_URL')
        ?: 'https://bjqadhzkoxdwyfsglrvq.supabase.co',
    '/'
);

$supabase_key = getenv('SUPABASE_ANON_KEY')
    ?: getenv('NEXT_PUBLIC_SUPABASE_ANON_KEY')
    ?: getenv('NEXT_PUBLIC_SUPABASE_PUBLISHABLE_DEFAULT_KEY')
    ?: 'sb_publishable_NRzhcehsa_tDtdXOOt4q9w_7mwWmgTB';

/**
 * Performs a Supabase REST request.
 *
 * @param string $endpoint e.g. /rest/v1/products?select=*
 * @param string $method HTTP method
 * @param array<string, mixed>|null $data Optional payload
 * @return mixed
 * @throws Exception when the Supabase request fails
 */
function supabaseRequest(string $endpoint, string $method = 'GET', ?array $data = null)
{
    global $supabase_url, $supabase_key;

    if (!$supabase_key) {
        throw new Exception('Supabase key is not configured');
    }

    $url = $supabase_url . $endpoint;

    $headers = [
        'Content-Type: application/json',
        'Authorization: Bearer ' . $supabase_key,
        'apikey: ' . $supabase_key,
        'Prefer: return=representation',
    ];

    if (strtoupper($method) === 'GET') {
        $headers[] = 'Prefer: count=exact';
    }

    $payload = null;
    if ($data !== null && in_array(strtoupper($method), ['POST', 'PUT', 'PATCH'], true)) {
        $payload = json_encode($data, JSON_UNESCAPED_UNICODE);
    }

    $response = null;
    $httpCode = 0;

    if (function_exists('curl_init')) {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);

        if ($payload !== null) {
            curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
        }

        $response = curl_exec($ch);
        $httpCode = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError = curl_error($ch);
        curl_close($ch);

        if ($response === false) {
            throw new Exception('Supabase request failed: ' . $curlError);
        }
    } else {
        $contextOptions = [
            'http' => [
                'method' => $method,
                'header' => implode("\r\n", $headers),
                'ignore_errors' => true,
                'timeout' => 15,
            ],
        ];

        if ($payload !== null) {
            $contextOptions['http']['content'] = $payload;
        }

        $context = stream_context_create($contextOptions);
        $response = @file_get_contents($url, false, $context);

        $responseHeaders = isset($http_response_header) && is_array($http_response_header) ? $http_response_header : [];
        if ($responseHeaders) {
            $statusLine = $responseHeaders[0];
            if (preg_match('/HTTP\/\d\.\d\s+(\d+)/', $statusLine, $matches)) {
                $httpCode = (int) $matches[1];
            }
        }

        if ($response === false) {
            $error = error_get_last();
            $message = $error['message'] ?? 'stream error';
            throw new Exception('Supabase request failed: ' . $message);
        }
    }

    if ($httpCode >= 400) {
        throw new Exception("Supabase API Error: HTTP {$httpCode} - {$response}", $httpCode);
    }

    $decoded = json_decode($response, true);
    if ($decoded === null && json_last_error() !== JSON_ERROR_NONE) {
        throw new Exception('Failed to decode Supabase response: ' . json_last_error_msg());
    }

    return $decoded;
}

/**
 * Fetches published categories ordered by sort order.
 *
 * @return array<int, array<string, mixed>>
 */
function supabaseFetchCategories(): array
{
    return supabaseRequest('/rest/v1/categories?select=*&is_active=eq.true&order=sort_order.asc');
}

/**
 * Fetches products with optional filters.
 *
 * @param string|null $query Additional query string (without leading &)
 * @return array<int, array<string, mixed>>
 */
function supabaseFetchProducts(?string $query = null): array
{
    $base = '/rest/v1/products?select=*,category:category_id!inner(id,name,slug,description)&is_published=eq.true&order=created_at.desc';
    if ($query) {
        $base .= '&' . ltrim($query, '&');
    }

    return supabaseRequest($base);
}

/**
 * Fetches a single product by id or slug.
 *
 * @param string $identifier Product ID or slug.
 * @return array<string, mixed>|null
 */
function supabaseFetchProductByIdOrSlug(string $identifier): ?array
{
    $or = '(id.eq.' . $identifier . ',slug.eq.' . $identifier . ')';
    $endpoint = '/rest/v1/products?select=*,category:category_id!inner(id,name,slug,description)&is_published=eq.true'
        . '&or=' . rawurlencode($or)
        . '&limit=1';

    $result = supabaseRequest($endpoint);
    return isset($result[0]) ? $result[0] : null;
}
