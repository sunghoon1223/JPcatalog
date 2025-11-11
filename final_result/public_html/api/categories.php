<?php

declare(strict_types=1);

require_once __DIR__ . '/utils/mb-compat.php';
require_once __DIR__ . '/supabase-config.php';
require_once __DIR__ . '/utils/catalogue.php';

if (function_exists('mb_internal_encoding')) {
    mb_internal_encoding('UTF-8');
}

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Accept');

if (isset($_SERVER['REQUEST_METHOD']) && $_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

/**
 * Normalises a single category row.
 *
 * @param array<string, mixed> $row
 * @return array<string, mixed>
 */
function catalogue_prepare_category(array $row): array
{
    $normalized = [
        'id' => $row['id'] ?? null,
        'name' => $row['name'] ?? null,
        'slug' => $row['slug'] ?? null,
        'description' => $row['description'] ?? null,
        'hero_image' => null,
        'sort_order' => isset($row['sort_order']) ? (int) $row['sort_order'] : null,
        'is_active' => isset($row['is_active']) ? (bool) $row['is_active'] : null,
    ];

    if (isset($row['hero_image']) && is_string($row['hero_image'])) {
        $normalized['hero_image'] = catalogue_normalize_media_path($row['hero_image']);
    }

    return $normalized;
}

/**
 * Loads categories from the bundled snapshot.
 *
 * @return array<int, array<string, mixed>>
 */
function catalogue_load_snapshot_categories(): array
{
    return array_map('catalogue_prepare_category', array_values(CATALOGUE_CATEGORY_MAP));
}

$categories = [];
$meta = [
    'total' => 0,
    'timestamp' => date('c'),
    'source' => 'snapshot',
];

try {
    $supabaseCategories = supabaseFetchCategories();
    if (is_array($supabaseCategories) && $supabaseCategories !== []) {
        $categories = array_map('catalogue_prepare_category', $supabaseCategories);
        $meta['source'] = 'supabase';
    }
} catch (Throwable $e) {
    error_log('[categories.php] Supabase fetch failed: ' . $e->getMessage());
}

if ($categories === []) {
    $categories = catalogue_load_snapshot_categories();
}

$meta['total'] = count($categories);

if (isset($_GET['slug']) && $_GET['slug'] !== '') {
    $slug = trim((string) $_GET['slug']);
    foreach ($categories as $category) {
        if (($category['slug'] ?? null) === $slug) {
            echo json_encode([
                'success' => true,
                'category' => $category,
                'meta' => $meta + ['matched' => 'slug'],
            ], JSON_UNESCAPED_UNICODE);
            return;
        }
    }

    http_response_code(404);
    echo json_encode([
        'success' => false,
        'error' => 'Category not found',
        'meta' => $meta + ['matched' => 'slug'],
    ], JSON_UNESCAPED_UNICODE);
    return;
}

echo json_encode([
    'success' => true,
    'categories' => $categories,
    'meta' => $meta,
], JSON_UNESCAPED_UNICODE);
