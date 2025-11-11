<?php

declare(strict_types=1);

function ai_recommendations_snapshot_path(): string
{
    $root = dirname(__DIR__, 3);
    $candidates = [
        $root . '/reports/data/ai_features_20251109.json',
        $root . '/새_프로젝트_폴더/data/products/products.json',
        $root . '/public_html/data/products.json',
    ];

    foreach ($candidates as $path) {
        if (is_readable($path)) {
            return $path;
        }
    }

    return '';
}

function ai_load_products_snapshot(): array
{
    static $cache;
    if ($cache !== null) {
        return $cache;
    }

    $path = ai_recommendations_snapshot_path();
    if ($path === '') {
        return $cache = [];
    }

    $raw = file_get_contents($path);
    if ($raw === false || $raw === '') {
        return $cache = [];
    }

    $decoded = json_decode($raw, true);
    if (!is_array($decoded)) {
        return $cache = [];
    }

    if (!empty($decoded) && isset($decoded[0]['metadata'])) {
        $products = [];
        foreach ($decoded as $entry) {
            if (!is_array($entry)) {
                continue;
            }
            if (($entry['source_type'] ?? null) !== 'product') {
                continue;
            }
            $meta = $entry['metadata'] ?? [];
            $slug = $meta['slug'] ?? null;
            if ($slug === null) {
                continue;
            }
            $chunk = (string) ($entry['chunk'] ?? '');
            $products[] = [
                'id' => $meta['product_id'] ?? $slug,
                'slug' => $slug,
                'category_slug' => $meta['category'] ?? null,
                'name' => ai_snapshot_extract_field($chunk, '제품명') ?? strtoupper($slug),
                'description' => ai_snapshot_extract_field($chunk, '설명') ?? $chunk,
                'features' => ai_snapshot_extract_features($chunk),
                'match_info' => [
                    'series_slug' => $meta['series_slug'] ?? null,
                ],
            ];
        }
        return $cache = $products;
    }

    return $cache = $decoded;
}

function ai_snapshot_extract_field(string $chunk, string $label): ?string
{
    $pattern = sprintf('/%s\s*:\s*(.+)/u', preg_quote($label, '/'));
    if (preg_match($pattern, $chunk, $matches)) {
        return trim($matches[1]);
    }

    return null;
}

function ai_snapshot_extract_features(string $chunk): array
{
    $lines = [];
    foreach (explode("\n", $chunk) as $line) {
        $line = trim($line);
        if ($line === '') {
            continue;
        }
        if (stripos($line, '주요 특징') !== false || stripos($line, '특징:') !== false) {
            $lines[] = $line;
        }
    }

    return $lines;
}

function ai_recommendations_from_snapshot(?string $categorySlug, ?string $seriesSlug, int $limit): array
{
    $limit = max(1, min(12, $limit));
    $items = [];
    foreach (ai_load_products_snapshot() as $product) {
        if (!is_array($product)) {
            continue;
        }

        if ($categorySlug && isset($product['category_slug']) && $product['category_slug'] !== $categorySlug) {
            continue;
        }

        if ($seriesSlug) {
            $candidate = $product['match_info']['series_slug'] ?? $product['series_slug'] ?? null;
            if ($candidate !== $seriesSlug) {
                continue;
            }
        }

        $items[] = ai_transform_product_to_recommendation($product);
        if (count($items) >= $limit) {
            break;
        }
    }

    return $items;
}

function ai_find_product_from_snapshot(string $identifier): ?array
{
    $identifier = trim($identifier);
    if ($identifier === '') {
        return null;
    }

    foreach (ai_load_products_snapshot() as $product) {
        if (!is_array($product)) {
            continue;
        }

        $id = (string) ($product['id'] ?? '');
        $slug = (string) ($product['slug'] ?? '');

        if ($identifier === $id || $identifier === $slug) {
            return $product;
        }
    }

    return null;
}

function ai_transform_product_to_recommendation(array $product): array
{
    $productId = (string) ($product['id'] ?? $product['slug'] ?? uniqid('product-', true));
    $slug = $product['slug'] ?? null;

    return [
        'product_id' => $productId,
        'slug' => $slug,
        'title' => $product['name'] ?? ($slug ? strtoupper($slug) : 'Unnamed Product'),
        'score' => 0.5,
        'reason' => 'Snapshot fallback result. Connect Supabase RPC for production.',
        'hero_media' => $product['hero_media'] ?? null,
        'badges' => ['ai.recommendation.badge.prototype'],
        'metadata' => [
            'category_id' => $product['category_id'] ?? $product['category_slug'] ?? null,
            'series_slug' => $product['match_info']['series_slug'] ?? $product['series_slug'] ?? null,
            'source' => 'fallback_snapshot',
        ],
    ];
}

function ai_build_content_blocks_from_product(array $product): array
{
    $blocks = [];

    if (!empty($product['description'])) {
        $blocks[] = [
            'key' => 'description',
            'i18n_key' => 'ai.recommendation.section.description',
            'markdown' => (string) $product['description'],
        ];
    }

    if (!empty($product['features'])) {
        $features = $product['features'];
        if (is_array($features)) {
            $markdown = '- ' . implode("\n- ", array_filter(array_map('trim', $features)));
        } else {
            $markdown = trim((string) $features);
        }
        $blocks[] = [
            'key' => 'features',
            'i18n_key' => 'ai.recommendation.section.features',
            'markdown' => $markdown,
        ];
    }

    return $blocks;
}
