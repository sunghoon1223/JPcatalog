<?php

declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../utils/cors.php';
api_apply_cors_headers();

require_once __DIR__ . '/../supabase-config.php';
require_once __DIR__ . '/../utils/catalogue.php';

$categoryMap = catalogue_categories_map();
$fallback = array_values(array_map(function ($meta) {
    return [
        'id' => $meta['id'],
        'name' => $meta['name'],
        'slug' => $meta['slug'],
        'description' => $meta['description'],
        'hero_image' => $meta['hero_image'],
        'sort_order' => $meta['sort_order'],
    ];
}, $categoryMap));

try {
    $categories = supabaseFetchCategories();
    if (!is_array($categories) || empty($categories)) {
        throw new Exception('empty');
    }

    $normalised = array_map(function (array $category) use ($categoryMap) {
        $id = $category['id'] ?? null;
        if ($id && isset($categoryMap[$id])) {
            $defaults = $categoryMap[$id];
        } else {
            $defaults = [
                'id' => $id,
                'name' => $category['name'] ?? null,
                'slug' => $category['slug'] ?? null,
                'description' => $category['description'] ?? null,
                'hero_image' => $category['hero_image'] ?? null,
                'sort_order' => $category['sort_order'] ?? 99,
            ];
        }

        return [
            'id' => $defaults['id'],
            'name' => $category['name'] ?? $defaults['name'],
            'slug' => $category['slug'] ?? $defaults['slug'],
            'description' => $category['description'] ?? $defaults['description'],
            'hero_image' => $category['hero_image'] ?? $defaults['hero_image'],
            'sort_order' => $category['sort_order'] ?? $defaults['sort_order'],
        ];
    }, $categories);

    usort($normalised, function ($a, $b) {
        return ($a['sort_order'] ?? 0) <=> ($b['sort_order'] ?? 0);
    });

    echo catalogue_api_success($normalised, ['source' => 'supabase'], 'categories');
} catch (Throwable $e) {
    echo catalogue_api_success($fallback, [
        'source' => 'snapshot',
        'error' => $e->getMessage(),
    ], 'categories');
}
?>
