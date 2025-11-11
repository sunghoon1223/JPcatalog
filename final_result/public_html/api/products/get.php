<?php

declare(strict_types=1);

require_once __DIR__ . '/../utils/cors.php';
api_apply_cors_headers();
require_once __DIR__ . '/../supabase-config.php';
require_once __DIR__ . '/../utils/catalogue.php';

$id = $_GET['id'] ?? null;
$slug = $_GET['slug'] ?? null;

$identifier = $id ?? $slug;

if (!$identifier) {
    echo catalogue_api_error('제품 ID 또는 slug가 필요합니다.', 400);
    exit;
}

$categoryMap = catalogue_categories_map();

try {
    $product = supabaseFetchProductByIdOrSlug($identifier);
    if (!$product) {
        throw new Exception('not_found');
    }

    echo catalogue_api_success(
        catalogue_format_product($product, $categoryMap),
        ['source' => 'supabase'],
        'product'
    );
} catch (Throwable $e) {
    $fallback = catalogue_local_find_product($identifier);
    if ($fallback !== null) {
        echo catalogue_api_success($fallback, [
            'source' => 'snapshot',
            'error' => $e->getMessage(),
        ], 'product');
        return;
    }

    echo catalogue_api_error('해당 제품을 찾을 수 없습니다.', 404, [
        'requested' => $identifier,
        'error' => $e->getMessage(),
    ]);
}
?>
