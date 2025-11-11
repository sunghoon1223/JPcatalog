<?php

declare(strict_types=1);

require_once __DIR__ . '/../utils/cors.php';
api_apply_cors_headers();
require_once __DIR__ . '/../utils/ai.php';
require_once __DIR__ . '/../supabase-config.php';
require_once __DIR__ . '/helpers.php';

$context = ai_resolve_request_context();
$rate = ai_apply_rate_limit_headers('recommendations:list', 60, 60);
ai_abort_if_rate_limited($rate);

$limitParam = isset($_GET['limit']) ? (int) $_GET['limit'] : 6;
$limit = max(1, min(12, $limitParam));
$categorySlug = isset($_GET['category']) ? trim((string) $_GET['category']) : null;
$seriesSlug = isset($_GET['series_slug']) ? trim((string) $_GET['series_slug']) : null;
$locale = ai_normalize_locale($_GET['locale'] ?? null);

$items = ai_recommendations_from_snapshot($categorySlug ?: null, $seriesSlug ?: null, $limit);

$response = [
    'success' => true,
    'items' => $items,
    'source' => $items ? 'fallback_snapshot' : 'supabase_unavailable',
    'environment' => $context['environment'],
    'dev_bypass' => $context['dev_bypass'],
    'meta' => [
        'category' => $categorySlug,
        'series_slug' => $seriesSlug,
        'locale' => $locale,
        'limit' => $limit,
        'context' => $_GET['context'] ?? null,
    ],
    'warnings' => [
        'WF-11 skeleton endpoint. Connect Supabase RPC for production.',
    ],
];

auth_json_response($response);
