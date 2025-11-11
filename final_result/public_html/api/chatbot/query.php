<?php

declare(strict_types=1);

require_once __DIR__ . '/../utils/cors.php';
api_apply_cors_headers();
require_once __DIR__ . '/../utils/ai.php';
require_once __DIR__ . '/../supabase-config.php';
require_once __DIR__ . '/../recommendations/helpers.php';

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
    http_response_code(405);
    header('Allow: POST');
    ai_error('method_not_allowed', 'Use POST for chatbot queries.', 405);
}

$context = ai_resolve_request_context();
$rate = ai_apply_rate_limit_headers('chatbot:query', 30, 60);
ai_abort_if_rate_limited($rate);

$body = ai_read_json_body();
$message = trim((string) ($body['message'] ?? ''));
if ($message === '') {
    ai_error('validation_error', 'message field is required.', 400);
}

$sessionId = $body['session_id'] ?? bin2hex(random_bytes(12));
$locale = ai_normalize_locale($body['locale'] ?? null);
$contextProducts = isset($body['context_products']) && is_array($body['context_products'])
    ? $body['context_products']
    : [];

$recommendations = ai_recommendations_from_snapshot(null, null, 3);
$reply = sprintf(
    '(%s) Placeholder reply: asked "%s". Connect to Supabase functions to get real answers.',
    strtoupper($locale),
    $message
);

$response = [
    'success' => true,
    'session_id' => $sessionId,
    'reply' => $reply,
    'suggested_product_ids' => array_column($recommendations, 'product_id'),
    'recommended_items' => $recommendations,
    'tokens_used' => [
        'prompt' => (function_exists('mb_strlen') ? mb_strlen($message) : strlen($message)),
        'completion' => (function_exists('mb_strlen') ? mb_strlen($reply) : strlen($reply)),
    ],
    'dev_bypass' => $context['dev_bypass'],
    'environment' => $context['environment'],
    'meta' => [
        'locale' => $locale,
        'context_products' => $contextProducts,
        'stream' => (bool) ($body['stream'] ?? false),
    ],
    'todo' => [
        'Persist message to ai_chat_messages + vector store once schema is migrated.',
    ],
];

auth_json_response($response);
