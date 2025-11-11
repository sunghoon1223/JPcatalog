<?php

declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../utils/cors.php';
api_apply_cors_headers();

require_once __DIR__ . '/../supabase-config.php';
require_once __DIR__ . '/../utils/catalogue.php';

function preview_normalize_category_slug(?string $categoryParam): ?string
{
    if ($categoryParam === null) {
        return null;
    }

    $normalized = mb_strtolower(trim($categoryParam));
    if ($normalized === '') {
        return null;
    }

    foreach (catalogue_categories_map() as $meta) {
        if (mb_strtolower($meta['id']) === $normalized || mb_strtolower($meta['slug']) === $normalized) {
            return mb_strtolower($meta['slug']);
        }
    }

    return $normalized;
}

function preview_product_identifier(array $product): string
{
    $parts = [];
    foreach (['slug', 'sku', 'name'] as $key) {
        if (!empty($product[$key]) && is_string($product[$key])) {
            $parts[] = $product[$key];
        }
    }

    if (isset($product['features']) && is_array($product['features'])) {
        foreach (['english_name', 'model_number', 'series', 'series_slug'] as $key) {
            if (!empty($product['features'][$key]) && is_string($product['features'][$key])) {
                $parts[] = $product['features'][$key];
            }
        }
    }

    return mb_strtolower(implode(' ', $parts));
}

function preview_apply_category_filters(array $products, ?string $categoryParam): array
{
    $normalizedSlug = preview_normalize_category_slug($categoryParam);
    if ($normalizedSlug === null) {
        return $products;
    }

    if ($normalizedSlug === 'agv-casters') {
        return array_values(array_filter($products, function ($product) {
            return strpos(preview_product_identifier($product), 'me263') === false;
        }));
    }

    if ($normalizedSlug === 'industrial-casters') {
        $allowed = [
            'eq053tj',
            'eq053',
            'eq053cyfw',
            'eq053cyzd',
            'eq053zdfw',
            'eq063',
            'eq093',
        ];

        return array_values(array_filter($products, function ($product) use ($allowed) {
            $identifier = preview_product_identifier($product);
            foreach ($allowed as $token) {
                if ($identifier !== '' && strpos($identifier, $token) !== false) {
                    return true;
                }
            }
            return false;
        }));
    }

    return $products;
}

$categoryParam = isset($_GET['category']) ? trim((string) $_GET['category']) : null;
if ($categoryParam === '') {
    $categoryParam = null;
}

$searchParam = isset($_GET['search']) ? trim((string) $_GET['search']) : null;
if ($searchParam === '') {
    $searchParam = null;
}

$featuredParam = $_GET['featured'] ?? null;
$featuredFilter = $featuredParam !== null && filter_var($featuredParam, FILTER_VALIDATE_BOOLEAN);

$limit = isset($_GET['limit']) ? max(1, min((int) $_GET['limit'], 500)) : 100;
$page = isset($_GET['page']) ? max(1, (int) $_GET['page']) : 1;
$offset = ($page - 1) * $limit;

$categoryResolved = catalogue_resolve_supabase_category($categoryParam);

$forceSnapshot = false;
$snapshotReason = null;

if ($categoryParam !== null && !$categoryResolved['query']) {
    $forceSnapshot = true;
    $snapshotReason = 'unresolved_category';
}

if ($forceSnapshot) {
    $fallback = catalogue_local_query($categoryParam, $searchParam, $limit, $offset);
    $filteredSnapshot = preview_apply_category_filters($fallback, $categoryParam);
    echo catalogue_api_success($filteredSnapshot, [
        'page' => $page,
        'limit' => $limit,
        'source' => 'snapshot',
        'forced_snapshot' => true,
        'snapshot_reason' => $snapshotReason,
        'filtered_count' => count($filteredSnapshot),
    ], 'products');
    return;
}

$queryParts = [];
if ($categoryResolved['query']) {
    $queryParts[] = $categoryResolved['query'];
}

if ($searchParam !== null) {
    $term = $searchParam;
    $or = '(name.ilike.*' . $term . '*,description.ilike.*' . $term . '*,sku.ilike.*' . $term . '*)';
    $queryParts[] = 'or=' . rawurlencode($or);
}

if ($featuredFilter) {
    $queryParts[] = 'is_featured=eq.true';
}

$queryParts[] = 'limit=' . $limit;
$queryParts[] = 'offset=' . $offset;

$categoryMap = catalogue_categories_map();

try {
    $products = supabaseFetchProducts(implode('&', $queryParts));

    if (!is_array($products) || empty($products)) {
        throw new Exception('Supabase returned empty dataset');
    }

    if (($categoryResolved['id'] ?? null) === 'cat_agv' && ($searchParam === null || trim($searchParam) === '') && count($products) < 13) {
        throw new Exception('Supabase returned incomplete AGV dataset');
    }

    $formatted = array_map(function (array $product) use ($categoryMap) {
        $normalised = catalogue_format_product($product, $categoryMap);
        if (!array_key_exists('match_info', $normalised)) {
            $normalised['match_info'] = $product['match_info'] ?? null;
        }

        return $normalised;
    }, $products);

    $filteredProducts = preview_apply_category_filters($formatted, $categoryParam);

    echo catalogue_api_success($filteredProducts, [
        'page' => $page,
        'limit' => $limit,
        'source' => 'supabase',
        'forced_snapshot' => false,
        'snapshot_reason' => null,
        'filtered_count' => count($filteredProducts),
    ], 'products');
} catch (Throwable $e) {
    $fallback = catalogue_local_query($categoryParam, $searchParam, $limit, $offset);
    $filteredFallback = preview_apply_category_filters($fallback, $categoryParam);
    $fallbackReason = $snapshotReason;

    if ($fallbackReason === null) {
        $message = $e->getMessage();
        if (stripos($message, 'agv') !== false) {
            $fallbackReason = 'agv_incomplete_supabase';
        } else {
            $fallbackReason = 'supabase_error';
        }
    }

    echo catalogue_api_success($filteredFallback, [
        'page' => $page,
        'limit' => $limit,
        'source' => 'snapshot',
        'forced_snapshot' => true,
        'snapshot_reason' => $fallbackReason,
        'error' => $e->getMessage(),
        'filtered_count' => count($filteredFallback),
    ], 'products');
}
?>
