<?php

declare(strict_types=1);

require_once __DIR__ . '/../utils/cors.php';
api_apply_cors_headers();
require_once __DIR__ . '/../utils/ai.php';
require_once __DIR__ . '/../supabase-config.php';
require_once __DIR__ . '/helpers.php';

$context = ai_resolve_request_context();
$rate = ai_apply_rate_limit_headers('recommendations:show', 60, 60);
ai_abort_if_rate_limited($rate);

$identifier = $_GET['product_id'] ?? $_GET['slug'] ?? null;
if ($identifier === null || trim((string) $identifier) === '') {
    ai_error('validation_error', 'Provide product_id or slug query parameter.', 400);
}

$product = ai_find_product_from_snapshot((string) $identifier);
if ($product === null) {
    ai_error('not_found', 'Recommendation not found in fallback snapshot.', 404);
}

$response = [
    'success' => true,
    'item' => [
        'product_id' => (string) ($product['id'] ?? $product['slug']),
        'slug' => $product['slug'] ?? null,
        'title' => $product['name'] ?? ($product['slug'] ?? 'Unnamed Product'),
        'description' => $product['description'] ?? null,
        'score' => 0.5,
        'reason' => 'Snapshot fallback result.',
        'content_blocks' => ai_build_content_blocks_from_product($product),
        'metadata' => [
            'category_id' => $product['category_id'] ?? null,
            'series_slug' => $product['match_info']['series_slug'] ?? null,
            'source' => 'fallback_snapshot',
        ],
    ],
    'environment' => $context['environment'],
    'dev_bypass' => $context['dev_bypass'],
];

auth_json_response($response);
