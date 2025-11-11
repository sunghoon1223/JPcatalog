<?php

declare(strict_types=1);

/**
 * Gemini API helper utilities.
 */

const GEMINI_DEFAULT_MODEL = 'gemini-1.5-flash';

/**
 * Returns the configured Gemini API key.
 */
function gemini_get_api_key(): string
{
    static $cached = null;
    if ($cached !== null) {
        return $cached;
    }

    $key = getenv('GEMINI_API_KEY')
        ?: getenv('NEXT_PUBLIC_GEMINI_API_KEY')
        ?: '';

    if (trim((string) $key) === '') {
        $rootPath = dirname(__DIR__, 3) . '/geminiAPI.env';
        if (is_file($rootPath)) {
            $fileContents = file_get_contents($rootPath);
            if ($fileContents !== false) {
                $key = $fileContents;
            }
        }
    }

    return $cached = trim((string) $key);
}

/**
 * Resolves the model name to use for Gemini requests.
 */
function gemini_get_model(): string
{
    $model = getenv('GEMINI_MODEL') ?: GEMINI_DEFAULT_MODEL;
    return trim((string) $model) ?: GEMINI_DEFAULT_MODEL;
}

/**
 * Issues a generateContent call against the Gemini API.
 *
 * @param array<int, array<string, mixed>> $contents
 * @param array<string, mixed> $options
 * @return array<string, mixed>
 */
function gemini_generate_content(array $contents, array $options = []): array
{
    $apiKey = gemini_get_api_key();
    if ($apiKey === '') {
        throw new RuntimeException('Gemini API key is not configured.');
    }

    if (empty($contents)) {
        throw new InvalidArgumentException('Gemini request requires at least one content message.');
    }

    $model = $options['model'] ?? gemini_get_model();
    $payload = [
        'contents' => $contents,
    ];

    if (isset($options['systemInstruction'])) {
        $payload['systemInstruction'] = $options['systemInstruction'];
    }

    if (isset($options['toolConfig'])) {
        $payload['toolConfig'] = $options['toolConfig'];
    }

    if (isset($options['tools'])) {
        $payload['tools'] = $options['tools'];
    }

    if (isset($options['safetySettings'])) {
        $payload['safetySettings'] = $options['safetySettings'];
    } else {
        $payload['safetySettings'] = [
            ['category' => 'HARM_CATEGORY_HARASSMENT', 'threshold' => 'BLOCK_MEDIUM_AND_ABOVE'],
            ['category' => 'HARM_CATEGORY_HATE_SPEECH', 'threshold' => 'BLOCK_MEDIUM_AND_ABOVE'],
            ['category' => 'HARM_CATEGORY_SEXUALLY_EXPLICIT', 'threshold' => 'BLOCK_MEDIUM_AND_ABOVE'],
            ['category' => 'HARM_CATEGORY_DANGEROUS_CONTENT', 'threshold' => 'BLOCK_MEDIUM_AND_ABOVE'],
        ];
    }

    if (isset($options['generationConfig'])) {
        $payload['generationConfig'] = $options['generationConfig'];
    } else {
        $payload['generationConfig'] = [
            'temperature' => 0.6,
            'topP' => 0.9,
            'topK' => 40,
            'maxOutputTokens' => 1024,
        ];
    }

    $query = http_build_query(['key' => $apiKey], '', '&', PHP_QUERY_RFC3986);
    $url = sprintf(
        'https://generativelanguage.googleapis.com/v1beta/models/%s:generateContent?%s',
        rawurlencode((string) $model),
        $query
    );

    $jsonPayload = json_encode($payload, JSON_UNESCAPED_UNICODE);
    if ($jsonPayload === false) {
        throw new RuntimeException('Failed to encode Gemini request payload.');
    }

    $headers = [
        'Content-Type: application/json',
    ];

    $responseBody = false;
    $httpCode = 0;

    if (function_exists('curl_init')) {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $jsonPayload);
        curl_setopt($ch, CURLOPT_TIMEOUT, 20);

        $responseBody = curl_exec($ch);
        $httpCode = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError = curl_error($ch);
        curl_close($ch);

        if ($responseBody === false) {
            throw new RuntimeException('Gemini request failed: ' . $curlError);
        }
    } else {
        $context = stream_context_create([
            'http' => [
                'method' => 'POST',
                'header' => implode("\r\n", $headers),
                'content' => $jsonPayload,
                'timeout' => 20,
                'ignore_errors' => true,
            ],
        ]);

        $responseBody = @file_get_contents($url, false, $context);

        $responseHeaders = isset($http_response_header) && is_array($http_response_header) ? $http_response_header : [];
        if ($responseHeaders) {
            $statusLine = $responseHeaders[0];
            if (preg_match('/HTTP\\/\\d\\.\\d\\s+(\\d+)/', $statusLine, $matches)) {
                $httpCode = (int) $matches[1];
            }
        }

        if ($responseBody === false) {
            $error = error_get_last();
            $message = $error['message'] ?? 'stream error';
            throw new RuntimeException('Gemini request failed: ' . $message);
        }
    }

    if ($httpCode >= 400) {
        throw new RuntimeException(sprintf('Gemini API error: HTTP %d - %s', $httpCode, (string) $responseBody));
    }

    $decoded = json_decode((string) $responseBody, true);
    if (!is_array($decoded)) {
        throw new RuntimeException('Failed to decode Gemini response: ' . json_last_error_msg());
    }

    return $decoded;
}
