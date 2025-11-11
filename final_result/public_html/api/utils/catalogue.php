<?php

declare(strict_types=1);

require_once __DIR__ . '/mb-compat.php';

/**
 * Shared helpers for catalogue APIs.
 * Provides Supabase fallback data loaded from the crawling snapshot.
 */

const CATALOGUE_CATEGORY_MAP = [
    'cat_agv' => [
        'id' => 'cat_agv',
        'name' => 'AGV 캐스터',
        'slug' => 'agv-casters',
        'description' => '무인 운반차량(AGV)과 로봇 시스템을 위한 고정밀 구동/지지 캐스터 솔루션.',
        'hero_image' => '/images/catalogue/agv-casters-hero.jpg',
        'sort_order' => 1,
    ],
    'cat_industrial' => [
        'id' => 'cat_industrial',
        'name' => '장비용 캐스터',
        'slug' => 'industrial-casters',
        'description' => '산업 장비와 생산 라인을 위한 고내구성 중량급 캐스터.',
        'hero_image' => '/images/catalogue/industrial-casters-hero.jpg',
        'sort_order' => 2,
    ],
    'cat_polyurethane' => [
        'id' => 'cat_polyurethane',
        'name' => '폴리우레탄 휠',
        'slug' => 'polyurethane-wheels',
        'description' => '저소음·고탄성 폴리우레탄 소재 휠과 모듈 제품.',
        'hero_image' => '/images/catalogue/polyurethane-wheels-hero.jpg',
        'sort_order' => 3,
    ],
    'cat_rubber' => [
        'id' => 'cat_rubber',
        'name' => '러버 휠',
        'slug' => 'rubber-wheels',
        'description' => '방진·저소음에 특화된 고무 소재 휠 및 캐스터.',
        'hero_image' => '/images/catalogue/rubber-wheels-hero.jpg',
        'sort_order' => 4,
    ],
];

const CATALOGUE_DATA_ROOT = __DIR__ . '/../../../새_프로젝트_폴더/data';
const AGV_SERIES_METADATA_PATH = __DIR__ . '/../../../product_data/agv_master_metadata.json';
const RUBBER_METADATA_PATH = __DIR__ . '/../../../product_data/rubber_master_metadata.json';
const POLYURETHANE_METADATA_PATH = __DIR__ . '/../../../product_data/polyurethane_master_metadata.json';
const EQUIPMENT_METADATA_PATH = __DIR__ . '/../../../product_data/equipment_master_metadata.json';
const RUBBER_METADATA_PATH = __DIR__ . '/../../../product_data/rubber_master_metadata.json';
const POLYURETHANE_METADATA_PATH = __DIR__ . '/../../../product_data/polyurethane_master_metadata.json';
const EQUIPMENT_METADATA_PATH = __DIR__ . '/../../../product_data/equipment_master_metadata.json';

const AGV_SERIES_LABELS = [
    'light-duty-caster-series' => 'Light Duty Caster Series',
    'light-to-medium-duty-caster-series' => 'Light to Medium Duty Caster Series',
    'medium-duty-caster-series' => 'Medium Duty Caster Series',
    'heavy-duty-caster-series-jqr062' => 'Heavy Duty Caster Series (JQR062)',
    'heavy-duty-caster-series-jqr063' => 'Heavy Duty Caster Series (JQR063)',
    'heavy-duty-caster-series-jqr082' => 'Heavy Duty Caster Series (JQR082)',
    'extra-heavy-duty-caster-series' => 'Extra Heavy Duty Caster Series',
    'medium-to-heavy-duty-shock-absorbers' => 'Medium & Heavy Duty Shock Absorbers Series',
    'heavy-duty-anti-vibration-series' => 'Heavy Duty Anti-Vibration Series',
    'double-rotary-casters' => 'Double Rotary Casters',
    'customization-series' => 'Customization Series',
];

/**
 * Resolves the absolute path to a data file inside the crawling snapshot.
 */
function catalogue_data_path(string $relativePath): string
{
    return CATALOGUE_DATA_ROOT . '/' . ltrim($relativePath, '/');
}

/**
 * Loads the local product snapshot (75 items).
 *
 * @return array<int, array<string, mixed>>
 */
function catalogue_load_local_products(): array
{
    static $cache = null;
    if ($cache !== null) {
        return $cache;
    }

    $path = catalogue_data_path('products/products.json');
    if (!is_file($path)) {
        return $cache = [];
    }

    $json = file_get_contents($path);
    $data = json_decode($json, true);
    if (!is_array($data)) {
        return $cache = [];
    }

    return $cache = $data;
}

/**
 * Normalises a media path to the public web root (leading slash).
 */
function catalogue_normalize_media_path(?string $path): ?string
{
    if (!$path) {
        return null;
    }

    $trimmed = trim($path);
    if ($trimmed === '') {
        return null;
    }

    // Collapse absolute URLs that point at the local snapshot root
    if (preg_match('#^https?://[^/]+/(새_프로젝트_폴더/images/.*)$#u', $trimmed, $matches)) {
        $trimmed = $matches[1];
    }

    // Snapshots sometimes include the local folder prefix
    $trimmed = str_replace('/새_프로젝트_폴더/images/', 'images/', $trimmed);
    $trimmed = str_replace('새_프로젝트_폴더/images/', 'images/', $trimmed);

    // External URLs should be returned as-is
    if (strpos($trimmed, 'http://') === 0 || strpos($trimmed, 'https://') === 0) {
        return $trimmed;
    }

    // Resolve to an existing file under the web root, preferring crawled paths
    $webRoot = realpath(__DIR__ . '/../../'); // public_html
    $candidate = ltrim($trimmed, '/');

    // If the path starts with images/ but not images/crawled/, try both
    if (strpos($candidate, 'images/') === 0 && strpos($candidate, 'images/crawled/') !== 0) {
        $rest = substr($candidate, strlen('images/'));
        $crawled = 'images/crawled/' . $rest;
        $absCrawled = $webRoot !== false ? ($webRoot . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $crawled)) : null;
        if ($absCrawled && is_file($absCrawled)) {
            $candidate = $crawled;
        } else {
            // Keep original images/<...> if it exists
            $absOriginal = $webRoot !== false ? ($webRoot . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $candidate)) : null;
            if (!($absOriginal && is_file($absOriginal))) {
                // As a last resort, try jpcaster/images for legacy bundles
                $legacy = 'jpcaster/' . $candidate;
                $absLegacy = $webRoot !== false ? ($webRoot . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $legacy)) : null;
                if ($absLegacy && is_file($absLegacy)) {
                    $candidate = $legacy;
                }
            }
        }
    }

    // Block known-bad legacy assets (e.g., ABUI*.jpg from legacy bundles)
    if ($candidate !== '' && catalogue_is_blacklisted_image($candidate)) {
        return null;
    }

    // If still not a real file under the web root, fall back to a placeholder
    $absCandidate = $webRoot !== false ? ($webRoot . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $candidate)) : null;
    if (!($absCandidate && is_file($absCandidate))) {
        $placeholder = 'jpcaster/placeholder.svg';
        $absPlaceholder = $webRoot !== false ? ($webRoot . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $placeholder)) : null;
        if ($absPlaceholder && is_file($absPlaceholder)) {
            $candidate = $placeholder;
        }
    }

    $normalized = ltrim($candidate, '/');
    if ($normalized === '') {
        return null;
    }

    return '/' . $normalized;
}



/**
 * Returns true when an image path is known-bad and must be ignored.
 */
function catalogue_is_blacklisted_image(string $relativePath): bool
{
    static $patterns = null;
    if ($patterns === null) {
        $patterns = catalogue_get_denylist_patterns();
    }
    $check = '/' . ltrim($relativePath, '/');
    foreach ($patterns as $re) {
        if (@preg_match($re, $check)) {
            if (preg_match($re, $check) === 1) {
                return true;
            }
        }
    }
    return false;
}

/**
 * Compose denylist regex patterns. Default rules block legacy ABUI* images.
 * Optionally merges user-provided rules from public_html/images/blacklist.json.
 *
 * @return array<int, string> PCRE patterns
 */
function catalogue_get_denylist_patterns(): array
{
    $defaults = [
        '#/(?:images|jpcaster/images)/ABUI[^/]*\\.jpe?g$#i',
    ];

    $jsonPath = realpath(__DIR__ . '/../../images/blacklist.json');
    if ($jsonPath && is_file($jsonPath)) {
        $raw = file_get_contents($jsonPath);
        $data = json_decode($raw, true);
        if (is_array($data)) {
            foreach ($data as $pattern) {
                if (is_string($pattern) && $pattern !== '') {
                    $defaults[] = $pattern;
                }
            }
        }
    }

    return $defaults;
}

/**
 * Builds an index of AGV series metadata keyed by the original numeric product ID.
 *
 * @return array<int, array<string, mixed>>
 */
function catalogue_slugify(string $text): string
{
    $normalized = mb_strtolower($text);
    $normalized = preg_replace('/[^\p{L}\p{Nd}\s-]/u', '', $normalized);
    $normalized = preg_replace('/[\s_-]+/u', '-', $normalized);
    return trim((string) $normalized, '-');
}

function catalogue_resolve_series_label(string $slug, ?string $fallbackName = null): string
{
    if (isset(AGV_SERIES_LABELS[$slug])) {
        return AGV_SERIES_LABELS[$slug];
    }

    if ($fallbackName !== null && $fallbackName !== '') {
        return $fallbackName;
    }

    return ucwords(str_replace('-', ' ', $slug));
}

function catalogue_load_agv_series_meta(): array
{
    static $cache = null;
    if ($cache !== null) {
        return $cache;
    }

    if (!is_file(AGV_SERIES_METADATA_PATH)) {
        return $cache = [];
    }

    $json = file_get_contents(AGV_SERIES_METADATA_PATH);
    $data = json_decode($json, true);
    if (!is_array($data)) {
        return $cache = [];
    }

    $map = [];
    $seriesEntries = [];

    if (isset($data['categories']) && is_array($data['categories'])) {
        foreach ($data['categories'] as $seriesKey => $seriesPayload) {
            if (!is_array($seriesPayload)) {
                continue;
            }
            $seriesEntries[] = [
                'series_slug' => (string) $seriesKey,
                'series_name' => catalogue_resolve_series_label((string) $seriesKey),
                'category_url' => isset($seriesPayload['category_url']) ? (string) $seriesPayload['category_url'] : null,
                'products' => isset($seriesPayload['products']) && is_array($seriesPayload['products']) ? $seriesPayload['products'] : [],
            ];
        }
    } else {
        foreach ($data as $seriesEntry) {
            if (!is_array($seriesEntry)) {
                continue;
            }
            $seriesSlug = isset($seriesEntry['series_slug']) && is_string($seriesEntry['series_slug'])
                ? $seriesEntry['series_slug']
                : catalogue_slugify((string) ($seriesEntry['series_name'] ?? ''));

            $seriesEntries[] = [
                'series_slug' => $seriesSlug,
                'series_name' => catalogue_resolve_series_label($seriesSlug, $seriesEntry['series_name'] ?? null),
                'category_url' => isset($seriesEntry['category_url']) ? (string) $seriesEntry['category_url'] : null,
                'products' => isset($seriesEntry['products']) && is_array($seriesEntry['products']) ? $seriesEntry['products'] : [],
            ];
        }
    }

    foreach ($seriesEntries as $seriesPayload) {
        if (!isset($seriesPayload['products']) || !is_array($seriesPayload['products'])) {
            continue;
        }

        $seriesName = $seriesPayload['series_name'];
        $seriesSlug = $seriesPayload['series_slug'];
        $seriesUrl = isset($seriesPayload['category_url']) ? trim((string) $seriesPayload['category_url']) : null;

        foreach ($seriesPayload['products'] as $productEntry) {
            $originalId = isset($productEntry['id']) ? (int) $productEntry['id'] : null;
            if (!$originalId) {
                continue;
            }

            $productImages = [];
            if (isset($productEntry['images']) && is_array($productEntry['images'])) {
                foreach ($productEntry['images'] as $imagePath) {
                    $normalized = catalogue_normalize_media_path($imagePath);
                    if ($normalized !== null) {
                        $productImages[] = $normalized;
                    }
                }
            }

            $drawingImages = [];
            if (isset($productEntry['drawings']) && is_array($productEntry['drawings'])) {
                foreach ($productEntry['drawings'] as $drawingPath) {
                    $normalized = catalogue_normalize_media_path($drawingPath);
                    if ($normalized !== null) {
                        $drawingImages[] = $normalized;
                    }
                }
            }

            $map[$originalId] = [
                'series_key' => $seriesSlug,
                'series_slug' => $seriesSlug,
                'series_name' => $seriesName,
                'series_url' => $seriesUrl,
                'product_images' => $productImages,
                'drawing_images' => $drawingImages,
            ];
        }
    }

    return $cache = $map;
}

/**
 * Extracts the original numeric product ID for AGV records.
 */
function catalogue_extract_agv_original_id(array $record): ?int
{
    if (isset($record['match_info']['original_id'])) {
        $original = (int) $record['match_info']['original_id'];
        if ($original > 0) {
            return $original;
        }
    }

    if (isset($record['id']) && is_string($record['id']) && preg_match('/agv_(\d+)/i', $record['id'], $match) === 1) {
        return (int) $match[1];
    }

    if (isset($record['source_url']) && is_string($record['source_url'])) {
        $parts = parse_url($record['source_url']);
        if (isset($parts['query'])) {
            parse_str($parts['query'], $queryParams);
            if (isset($queryParams['id'])) {
                $candidate = (int) $queryParams['id'];
                if ($candidate > 0) {
                    return $candidate;
                }
            }
        }
    }

    return null;
}



/**
 * Resolves the AGV series metadata for a product record.
 */
function catalogue_lookup_agv_series_meta(array $record): ?array
{
    $originalId = catalogue_extract_agv_original_id($record);
    if ($originalId === null) {
        return null;
    }

    $map = catalogue_load_agv_series_meta();
    return $map[$originalId] ?? null;
}

/**
 * Extract a numeric product ID from common patterns like agv_123, rubber_269,
 * polyurethane_795, equipment_189 or from source_url?id=NNN.
 */
function catalogue_extract_numeric_id(array $record): ?int
{
    if (isset($record['match_info']['original_id'])) {
        $original = (int) $record['match_info']['original_id'];
        if ($original > 0) {
            return $original;
        }
    }

    $idStr = isset($record['id']) ? (string) $record['id'] : '';
    if ($idStr !== '') {
        if (preg_match('/(?:agv|rubber|polyurethane|equipment)_(\d+)/i', $idStr, $m) === 1) {
            return (int) $m[1];
        }
        if (preg_match('/^(\d+)$/', $idStr, $m) === 1) {
            return (int) $m[1];
        }
    }

    if (isset($record['source_url']) && is_string($record['source_url'])) {
        $parts = parse_url($record['source_url']);
        if (isset($parts['query'])) {
            parse_str($parts['query'], $qs);
            if (!empty($qs['id'])) {
                $candidate = (int) $qs['id'];
                if ($candidate > 0) {
                    return $candidate;
                }
            }
        }
    }

    return null;
}

/**
 * Load category master metadata (rubber, polyurethane, equipment) keyed by numeric id.
 *
 * @param string $which One of: rubber|polyurethane|equipment
 * @return array<int, array{product_images:array<int,string>, drawing_images:array<int,string>}>
 */
function catalogue_load_category_master_meta(string $which): array
{
    static $cache = [];
    if (isset($cache[$which])) {
        return $cache[$which];
    }

    $path = null;
    if ($which === 'rubber') $path = RUBBER_METADATA_PATH;
    elseif ($which === 'polyurethane') $path = POLYURETHANE_METADATA_PATH;
    elseif ($which === 'equipment') $path = EQUIPMENT_METADATA_PATH;

    if (!$path || !is_file($path)) {
        return $cache[$which] = [];
    }

    $json = file_get_contents($path);
    $data = json_decode($json, true);
    if (!is_array($data)) {
        return $cache[$which] = [];
    }

    $map = [];
    foreach ($data as $series) {
        if (!isset($series['products']) || !is_array($series['products'])) continue;
        foreach ($series['products'] as $p) {
            $pid = null;
            if (isset($p['id'])) $pid = (int) $p['id'];
            if (!$pid && isset($p['detail_url']) && is_string($p['detail_url'])) {
                $parts = parse_url($p['detail_url']);
                if (isset($parts['query'])) { parse_str($parts['query'], $qs); if (!empty($qs['id'])) $pid = (int) $qs['id']; }
            }
            if (!$pid) continue;

            $imgs = [];
            if (isset($p['image_urls']) && is_array($p['image_urls'])) $imgs = $p['image_urls'];
            elseif (isset($p['images']) && is_array($p['images'])) $imgs = $p['images'];
            $drws = isset($p['drawings']) && is_array($p['drawings']) ? $p['drawings'] : [];

            $normImgs = [];
            foreach ($imgs as $u) { $n = catalogue_normalize_media_path(is_string($u) ? $u : null); if ($n !== null) $normImgs[] = $n; }
            $normDrws = [];
            foreach ($drws as $u) { $n = catalogue_normalize_media_path(is_string($u) ? $u : null); if ($n !== null) $normDrws[] = $n; }

            $map[$pid] = [
                'product_images' => array_values(array_unique($normImgs)),
                'drawing_images' => array_values(array_unique($normDrws)),
            ];
        }
    }

    return $cache[$which] = $map;
}

/**
 * Lookup overrides for non-AGV categories using master metadata.
 */
function catalogue_lookup_category_meta(array $record): ?array
{
    $catId = $record['category_id'] ?? null;
    $catSlug = isset($record['category']['slug']) ? $record['category']['slug'] : null;
    $pid = catalogue_extract_numeric_id($record);
    if ($pid === null) return null;

    if ($catId === 'cat_rubber' || $catSlug === 'rubber-wheels') {
        $m = catalogue_load_category_master_meta('rubber');
        return $m[$pid] ?? null;
    }
    if ($catId === 'cat_polyurethane' || $catSlug === 'polyurethane-wheels') {
        $m = catalogue_load_category_master_meta('polyurethane');
        return $m[$pid] ?? null;
    }
    if ($catId === 'cat_industrial' || $catSlug === 'industrial-casters') {
        $m = catalogue_load_category_master_meta('equipment');
        return $m[$pid] ?? null;
    }
    return null;
}

/**
 * Load per-product image overrides generated from product_data/* into
 * public_html/images/overrides/overrides.json.
 * Structure:
 *   { "rubber": { "269": { product_images:[], drawing_images:[] } }, ... }
 */
function catalogue_load_overrides(): array
{
    static $cache = null;
    if ($cache !== null) return $cache;
    $path = realpath(__DIR__ . '/../../images/overrides/overrides.json');
    if (!$path || !is_file($path)) return $cache = [];
    $raw = file_get_contents($path);
    $data = json_decode($raw, true);
    if (!is_array($data)) return $cache = [];
    return $cache = $data;
}

function catalogue_lookup_overrides(array $record): ?array
{
    $overrides = catalogue_load_overrides();
    if (!$overrides) return null;

    $pid = catalogue_extract_numeric_id($record);
    if ($pid === null) return null;

    $catKey = null;
    $catId = $record['category_id'] ?? null;
    $catSlug = isset($record['category']['slug']) ? $record['category']['slug'] : null;
    if ($catId === 'cat_agv' || $catSlug === 'agv-casters') $catKey = 'agv';
    elseif ($catId === 'cat_rubber' || $catSlug === 'rubber-wheels') $catKey = 'rubber';
    elseif ($catId === 'cat_polyurethane' || $catSlug === 'polyurethane-wheels') $catKey = 'polyurethane';
    elseif ($catId === 'cat_industrial' || $catSlug === 'industrial-casters') $catKey = 'equipment';
    else return null;

    if (!isset($overrides[$catKey]) || !isset($overrides[$catKey][(string)$pid])) return null;
    return $overrides[$catKey][(string)$pid];
}

/**
 * Determines if the given identifier matches the provided product record.
 */
function catalogue_match_product_identifier(array $product, string $identifier): bool
{
    $identifier = trim(mb_strtolower($identifier));
    if ($identifier === '') {
        return false;
    }

    $productId = isset($product['id']) ? (string) $product['id'] : null;
    $productSlug = isset($product['slug']) && is_string($product['slug']) ? mb_strtolower($product['slug']) : null;
    $originalId = null;
    if (isset($product['match_info']['original_id'])) {
        $originalId = (string) $product['match_info']['original_id'];
    }

    if ($productId !== null && $identifier === $productId) {
        return true;
    }

    if ($originalId !== null && $identifier === $originalId) {
        return true;
    }

    if ($productSlug !== null && $identifier === $productSlug) {
        return true;
    }

    if ($productSlug !== null && preg_match('/^([a-z0-9._-]+)-(\\d+)$/i', $productSlug, $slugMatch)) {
        if ($identifier === $slugMatch[1] || $identifier === $slugMatch[2]) {
            return true;
        }
    }

    if (preg_match('/^(?:[a-z]+_)?(\\d+)$/', $identifier, $idMatch)) {
        if ($productId !== null && $idMatch[1] === $productId) {
            return true;
        }
        if ($originalId !== null && $idMatch[1] === $originalId) {
            return true;
        }
    }

    if ($productSlug !== null && strpos($productSlug, '/') !== false) {
        $slugTail = mb_strtolower(basename($productSlug));
        if ($slugTail === $identifier) {
            return true;
        }
    }

    return false;
}

/**
 * Returns the canonical category map.
 *
 * @return array<string, array<string, mixed>>
 */
function catalogue_categories_map(): array
{
    return CATALOGUE_CATEGORY_MAP;
}

/**
 * Loads optional catalogue config (filters, etc.).
 * @return array<string, mixed>
 */
function catalogue_load_config(): array
{
    $configPath = __DIR__ . '/catalogue-config.json';
    if (is_file($configPath)) {
        $raw = @file_get_contents($configPath);
        if (is_string($raw) && $raw !== '') {
            $json = json_decode($raw, true);
            if (is_array($json)) {
                return $json;
            }
        }
    }
    return [];
}

/**
 * Applies include/exclude rules from config to a product.
 */
function catalogue_product_allowed(array $product, array $configFilters): bool
{
    $catId = $product['category_id'] ?? null;
    if (!$catId || !isset($configFilters[$catId])) return true;

    $rules = $configFilters[$catId];
    $slug = isset($product['slug']) && is_string($product['slug']) ? mb_strtolower($product['slug']) : '';
    $name = isset($product['name']) && is_string($product['name']) ? $product['name'] : '';

    $containsAny = function (?array $needles, string $hay): bool {
        if (!is_array($needles) || $hay === '') return false;
        $lh = mb_strtolower($hay);
        foreach ($needles as $n) {
            if (!is_string($n) || $n === '') continue;
            if (mb_stripos($lh, mb_strtolower($n)) !== false) return true;
        }
        return false;
    };

    // Include filters: if present, must match at least one
    $hasInclude = (isset($rules['include_slug_contains']) && is_array($rules['include_slug_contains']))
        || (isset($rules['include_name_contains']) && is_array($rules['include_name_contains']));
    if ($hasInclude) {
        $ok = false;
        if ($containsAny($rules['include_slug_contains'] ?? null, $slug)) $ok = true;
        if ($containsAny($rules['include_name_contains'] ?? null, $name)) $ok = true;
        if (!$ok) return false;
    }

    // Exclude filters: if match, reject
    if ($containsAny($rules['exclude_slug_contains'] ?? null, $slug)) return false;
    if ($containsAny($rules['exclude_name_contains'] ?? null, $name)) return false;

    return true;
}

/**
 * Normalises a product record (from Supabase or the local snapshot)
 * to the shape consumed by the frontend.
 *
 * @param array<string, mixed> $record
 * @param array<string, array<string, mixed>> $categoryMap
 */
function catalogue_format_product(array $record, array $categoryMap): array
{
    $categoryId = $record['category_id'] ?? null;
    $category = $record['category'] ?? null;
    $rawSlug = isset($record['slug']) && is_string($record['slug']) ? trim($record['slug']) : null;
    $matchInfo = isset($record['match_info']) && is_array($record['match_info']) ? $record['match_info'] : null;

    if (!$category && $categoryId && isset($categoryMap[$categoryId])) {
        $category = [
            'id' => $categoryMap[$categoryId]['id'],
            'name' => $categoryMap[$categoryId]['name'],
            'slug' => $categoryMap[$categoryId]['slug'],
            'description' => $categoryMap[$categoryId]['description'],
            'hero_image' => $categoryMap[$categoryId]['hero_image'],
        ];
    } elseif (is_array($category)) {
        $category = [
            'id' => $category['id'] ?? ($categoryId ?? null),
            'name' => $category['name'] ?? ($categoryId && isset($categoryMap[$categoryId]) ? $categoryMap[$categoryId]['name'] : null),
            'slug' => $category['slug'] ?? ($categoryId && isset($categoryMap[$categoryId]) ? $categoryMap[$categoryId]['slug'] : null),
            'description' => $category['description'] ?? ($categoryId && isset($categoryMap[$categoryId]) ? $categoryMap[$categoryId]['description'] : null),
            'hero_image' => $category['hero_image'] ?? ($categoryId && isset($categoryMap[$categoryId]) ? $categoryMap[$categoryId]['hero_image'] : null),
        ];
    }

    $seriesMeta = null;
    $isAgvCatalogue = ($categoryId === 'cat_agv')
        || (($category['slug'] ?? null) === ($categoryMap['cat_agv']['slug'] ?? null));
    $agvOriginalId = null;

    if ($isAgvCatalogue) {
        $agvOriginalId = catalogue_extract_agv_original_id($record);
        $seriesMeta = catalogue_lookup_agv_series_meta($record);
        if ($seriesMeta) {
            $agvCategory = $categoryMap['cat_agv'] ?? null;

            $category = [
                'id' => $agvCategory['id'] ?? $categoryId,
                'name' => $seriesMeta['series_name'],
                'slug' => $agvCategory['slug'] ?? ($category['slug'] ?? null),
                'description' => $agvCategory['description'] ?? ($category['description'] ?? null),
                'hero_image' => $agvCategory['hero_image'] ?? ($category['hero_image'] ?? null),
                'series' => [
                    'key' => $seriesMeta['series_key'],
                    'slug' => $seriesMeta['series_slug'],
                    'name' => $seriesMeta['series_name'],
                    'url' => $seriesMeta['series_url'],
                ],
            ];

            if ($agvCategory) {
                $category['parent'] = [
                    'id' => $agvCategory['id'],
                    'name' => $agvCategory['name'],
                    'slug' => $agvCategory['slug'],
                    'description' => $agvCategory['description'],
                    'hero_image' => $agvCategory['hero_image'],
                ];
            }
        }
    }

    if ($agvOriginalId !== null) {
        if (!is_array($matchInfo)) {
            $matchInfo = [];
        }
        if (!isset($matchInfo['original_id'])) {
            $matchInfo['original_id'] = $agvOriginalId;
        }
    }
    $computedSlug = $rawSlug !== null && $rawSlug !== '' ? $rawSlug : null;
    if ($computedSlug === null || $computedSlug === '') {
        if ($agvOriginalId !== null) {
            $computedSlug = 'product-' . $agvOriginalId;
        } elseif (isset($record['id']) && $record['id'] !== null) {
            $computedSlug = 'product-' . $record['id'];
        }
    }

    if ($computedSlug !== null && $computedSlug !== '') {
        $baseSlug = preg_replace('/-\\d+$/', '', $computedSlug);
        if ($baseSlug === '') {
            $baseSlug = isset($record['name']) ? preg_replace('/\\s+/', '-', strtolower(trim($record['name']))) : null;
            if ($baseSlug === null || $baseSlug === '') {
                $baseSlug = 'product';
            }
        }
        if ($agvOriginalId !== null) {
            $computedSlug = rtrim($baseSlug, '-') . '-' . $agvOriginalId;
        } elseif (isset($record['id']) && $record['id'] !== null) {
            $computedSlug = rtrim($baseSlug, '-') . '-' . $record['id'];
        }
        $computedSlug = strtolower($computedSlug);
    } else {
        $computedSlug = null;
    }

    if (is_array($matchInfo) && empty($matchInfo)) {
        $matchInfo = null;
    }

    $imageUrls = [];

    $primaryCandidates = [
        $record['primary_image'] ?? null,
        $record['primary_image_url'] ?? null,
        $record['main_image_url'] ?? null,
    ];

    foreach ($primaryCandidates as $candidate) {
        $normalized = catalogue_normalize_media_path(is_string($candidate) ? $candidate : null);
        if ($normalized !== null) {
            $imageUrls[] = $normalized;
        }
    }

    $originalImageUrls = $record['image_urls'] ?? $record['imageUrls'] ?? [];
    if (is_array($originalImageUrls)) {
        foreach ($originalImageUrls as $url) {
            $normalized = catalogue_normalize_media_path(is_string($url) ? $url : null);
            if ($normalized !== null) {
                $imageUrls[] = $normalized;
            }
        }
    }

    $tags = $record['tags'] ?? [];
    if (!is_array($tags)) {
        $tags = [];
    }

    $rawMainImage = is_string($record['main_image_url'] ?? null) ? $record['main_image_url'] : null;
    $mainImage = catalogue_normalize_media_path($rawMainImage);
    if ($mainImage === null) {
        $mainImage = $imageUrls[0] ?? null;
    }
    $normalizedGallery = $imageUrls;

    if ($mainImage !== null) {
        if (!isset($normalizedGallery[0]) || $normalizedGallery[0] !== $mainImage) {
            array_unshift($normalizedGallery, $mainImage);
        }
    }

    $categoryMeta = null;
    $overrideMeta = catalogue_lookup_overrides($record);

    // Merge AGV series images or non-AGV category master images
    if ($seriesMeta) {
        $normalizedGallery = array_merge(
            $normalizedGallery,
            $seriesMeta['product_images'],
            $seriesMeta['drawing_images']
        );
    } else {
        $categoryMeta = catalogue_lookup_category_meta($record);
        if ($categoryMeta) {
            $normalizedGallery = array_merge(
                $normalizedGallery,
                $categoryMeta['product_images'],
                $categoryMeta['drawing_images']
            );
        }
    }

    if ($overrideMeta) {
        $normalizedGallery = array_merge(
            $normalizedGallery,
            $overrideMeta['product_images'] ?? [],
            $overrideMeta['drawing_images'] ?? []
        );
    }

    // Remove preview/small/qr/banner thumbnails before dedupe
    $isPreviewSmall = function (string $url): bool {
        $u = strtolower($url);
        if (strpos($u, '/_filtered_out/lowres/') !== false) return true;
        if (strpos($u, '/lowres/') !== false) return true;
        if (strpos($u, 'thumbnail') !== false || strpos($u, 'thumb_') !== false || strpos($u, 'thumb-') !== false) return true;
        if (strpos($u, '/_filtered_out/qr/') !== false || strpos($u, '/qr/') !== false) return true;
        if (strpos($u, '/_filtered_out/banners/') !== false || strpos($u, '/banners/') !== false) return true;
        return false;
    };
    $normalizedGallery = array_values(array_filter($normalizedGallery, function($u) use ($isPreviewSmall) {
        return !$isPreviewSmall((string)$u);
    }));

    // Deduplicate by file basename, preferring sources in this order:
    // 1) /images/overrides/files/
    // 2) /images/crawled/.../product/
    // 3) /images/crawled/.../images/gallery/
    // 4) everything else (lowest)
    $rankFor = function (string $url): int {
        $u = strtolower($url);
        if (strpos($u, '/images/overrides/files/') !== false) return 0;
        if (preg_match('#/images/crawled/.*/product/#i', $u)) return 1;
        if (preg_match('#/images/crawled/.*/images/gallery/#i', $u)) return 2;
        return 3;
    };

    $byBase = [];
    $order = [];
    foreach ($normalizedGallery as $url) {
        $path = parse_url($url, PHP_URL_PATH);
        $base = is_string($path) ? basename($path) : basename($url);
        $r = $rankFor($url);
        if (!isset($byBase[$base])) {
            $byBase[$base] = ['url' => $url, 'rank' => $r];
            $order[] = $base;
        } else {
            if ($r < $byBase[$base]['rank']) {
                $byBase[$base] = ['url' => $url, 'rank' => $r];
            }
        }
    }
    $normalizedGallery = [];
    foreach ($order as $base) {
        $normalizedGallery[] = $byBase[$base]['url'];
    }

    // If main image is still a placeholder, promote first real image from gallery
    $isPlaceholder = function (?string $url): bool {
        if ($url === null) return true;
        $u = strtolower($url);
        return strpos($u, '/placeholder.svg') !== false;
    };
    if ($isPlaceholder($mainImage)) {
        foreach ($normalizedGallery as $url) {
            if (!$isPlaceholder($url)) {
                $mainImage = $url;
                break;
            }
        }
    }

    // Ensure main image is first in gallery
    if ($mainImage !== null) {
        $normalizedGallery = array_values(array_unique(array_merge([$mainImage], $normalizedGallery)));
    }

    $imagesMap = [];
    foreach ($normalizedGallery as $url) {
        $imagesMap[$url] = ['url' => $url];
    }

    if ($seriesMeta) {
        foreach ($seriesMeta['product_images'] as $url) {
            if (!isset($imagesMap[$url])) {
                $imagesMap[$url] = ['url' => $url];
            }
            if (!isset($imagesMap[$url]['role'])) {
                $imagesMap[$url]['role'] = 'product';
            }
        }

        foreach ($seriesMeta['drawing_images'] as $url) {
            if (!isset($imagesMap[$url])) {
                $imagesMap[$url] = ['url' => $url];
            }
            $imagesMap[$url]['role'] = 'drawing';
        }
    } else {
        if ($categoryMeta) {
            foreach ($categoryMeta['product_images'] as $url) {
                if (!isset($imagesMap[$url])) {
                    $imagesMap[$url] = ['url' => $url];
                }
                if (!isset($imagesMap[$url]['role'])) {
                    $imagesMap[$url]['role'] = 'product';
                }
            }

            foreach ($categoryMeta['drawing_images'] as $url) {
                if (!isset($imagesMap[$url])) {
                    $imagesMap[$url] = ['url' => $url];
                }
                $imagesMap[$url]['role'] = 'drawing';
            }
        }
    }

    if ($overrideMeta) {
        foreach (($overrideMeta['product_images'] ?? []) as $url) {
            if (!isset($imagesMap[$url])) {
                $imagesMap[$url] = ['url' => $url];
            }
            if (!isset($imagesMap[$url]['role'])) {
                $imagesMap[$url]['role'] = 'product';
            }
        }
        foreach (($overrideMeta['drawing_images'] ?? []) as $url) {
            if (!isset($imagesMap[$url])) {
                $imagesMap[$url] = ['url' => $url];
            }
            $imagesMap[$url]['role'] = 'drawing';
        }
    }

    $matchesProductGallery = function (string $url): bool {
        $lower = strtolower($url);
        if (preg_match('#/images/(?:[^/]+/)*images/gallery/#i', $lower)) {
            return true;
        }
        if (strpos($lower, '_product_') !== false) {
            return true;
        }
        $supabaseIndicators = ['/gallery/', '/product/', '-gallery-', '-product-'];
        if (strpos($lower, 'supabase') !== false) {
            foreach ($supabaseIndicators as $indicator) {
                if (strpos($lower, $indicator) !== false) {
                    return true;
                }
            }
        }
        return false;
    };

    $matchesDrawingGallery = function (string $url): bool {
        $lower = strtolower($url);
        if (preg_match('#/images/(?:[^/]+/)*images/drawing/#i', $lower)) {
            return true;
        }
        if (strpos($lower, '_drawing_') !== false) {
            return true;
        }
        $supabaseIndicators = ['/drawing/', '-drawing-'];
        if (strpos($lower, 'supabase') !== false) {
            foreach ($supabaseIndicators as $indicator) {
                if (strpos($lower, $indicator) !== false) {
                    return true;
                }
            }
        }
        return false;
    };

    foreach ($imagesMap as $url => &$entry) {
        if (isset($entry['role'])) {
            continue;
        }
        if ($isPlaceholder($url)) {
            continue;
        }
        if ($matchesProductGallery($url)) {
            $entry['role'] = 'product';
            continue;
        }
        if ($matchesDrawingGallery($url)) {
            $entry['role'] = 'drawing';
        }
    }
    unset($entry);

    // Prefer a product photo as main image if available
    $chooseMain = function(array $gallery, array $map, ?string $current) use ($isPlaceholder) {
        $scoreImage = function (?string $url) use ($map, $isPlaceholder): ?int {
            if ($url === null) {
                return null;
            }
            if ($isPlaceholder($url)) {
                return 3;
            }
            $role = $map[$url]['role'] ?? null;
            if ($role === 'product') {
                return 0;
            }
            if ($role === 'drawing') {
                return 2;
            }
            return 1;
        };

        $candidates = $gallery;
        if ($current !== null && !in_array($current, $gallery, true)) {
            array_unshift($candidates, $current);
        }

        $bestUrl = null;
        $bestScore = PHP_INT_MAX;
        foreach ($candidates as $url) {
            $candidateScore = $scoreImage($url);
            if ($candidateScore === null) {
                continue;
            }
            if ($candidateScore < $bestScore) {
                $bestScore = $candidateScore;
                $bestUrl = $url;
                if ($candidateScore === 0) {
                    break;
                }
            }
        }

        return $bestUrl ?? $current;
    };

    $mainImage = $chooseMain($normalizedGallery, $imagesMap, $mainImage);

    if ($mainImage !== null && !$isPlaceholder($mainImage)) {
        if (!isset($imagesMap[$mainImage])) {
            $imagesMap[$mainImage] = ['url' => $mainImage];
        }
        if (!isset($imagesMap[$mainImage]['role'])) {
            $imagesMap[$mainImage]['role'] = 'product';
        }
    }
    $images = array_values($imagesMap);

    $rawDescription = $record['description'] ?? '';
    if (!is_string($rawDescription)) {
        $rawDescription = '';
    }
    $normalizedDescription = str_replace(
        ["\r\n", "\r", "\n"],
        "<br />",
        trim($rawDescription)
    );

    return [
        'id' => $record['id'] ?? null,
        'category_id' => $categoryId,
        'name' => $record['name'] ?? null,
        'slug' => $computedSlug,
        'description' => $normalizedDescription,
        'price' => isset($record['price']) && $record['price'] !== null ? (float) $record['price'] : 0.0,
        'sale_price' => isset($record['sale_price']) && $record['sale_price'] !== null ? (float) $record['sale_price'] : null,
        'sku' => $record['sku'] ?? null,
        'stock_quantity' => $record['stock_quantity'] ?? 0,
        'stock_status' => $record['stock_status'] ?? 'instock',
        'weight' => $record['weight'] ?? null,
        'manufacturer' => $record['manufacturer'] ?? null,
        'main_image_url' => $mainImage,
        'mainImageUrl' => $mainImage,
        'primary_image_url' => $mainImage,
        'primaryImageUrl' => $mainImage,
        'image_urls' => $normalizedGallery,
        'imageUrls' => $normalizedGallery,
        'gallery' => $normalizedGallery,
        'galleryImages' => $normalizedGallery,
        'image_count' => count($normalizedGallery),
        'images' => $images,
        'tags' => $tags,
        'features' => $record['features'] ?? [],
        'dimensions' => $record['dimensions'] ?? [],
        'technical_specs' => $record['technical_specs'] ?? [],
        'pricing' => $record['pricing'] ?? [],
        'quality' => $record['quality'] ?? [],
        'shipping' => $record['shipping'] ?? [],
        'is_featured' => in_array('featured', $tags, true) || ($record['is_featured'] ?? false),
        'is_published' => $record['is_published'] ?? true,
        'source_url' => $record['source_url'] ?? '',
        'created_at' => $record['created_at'] ?? null,
        'updated_at' => $record['updated_at'] ?? null,
        'category' => $category,
        'match_info' => $matchInfo,
    ];
}

/**
 * Filters the local catalogue snapshot for fallback responses.
 *
 * @param string|null $categoryParam category slug or ID
 * @param string|null $search free text search
 * @param int $limit
 * @param int $offset
 * @return array<int, array<string, mixed>>
 */
function catalogue_local_query(?string $categoryParam, ?string $search, int $limit, int $offset): array
{
    $products = catalogue_load_local_products();
    $categories = catalogue_categories_map();
    $cfg = catalogue_load_config();
    $filtersCfg = $cfg['filters'] ?? [];

    $categoryParam = $categoryParam ? trim($categoryParam) : null;
    $searchTerm = $search ? trim(mb_strtolower($search)) : null;

    $filtered = array_filter($products, function ($product) use ($categoryParam, $searchTerm, $categories, $filtersCfg) {
        $categoryOk = true;
        if ($categoryParam) {
            if (isset($categories[$categoryParam])) {
                $categoryOk = ($product['category_id'] ?? '') === $categoryParam;
            } else {
                $categoryOk = false;
                foreach ($categories as $cat) {
                    if ($cat['slug'] === $categoryParam) {
                        $categoryOk = ($product['category_id'] ?? '') === $cat['id'];
                        break;
                    }
                }
            }
        }

        if (!$categoryOk) {
            return false;
        }

        if ($searchTerm) {
            $haystack = mb_strtolower(($product['name'] ?? '') . ' ' . ($product['description'] ?? '') . ' ' . ($product['sku'] ?? ''));
            if (strpos($haystack, $searchTerm) === false) {
                return false;
            }
        }

        if (($product['is_published'] ?? true) !== true) return false;

        // Apply additional include/exclude filters
        if (!catalogue_product_allowed($product, $filtersCfg)) return false;

        return true;
    });

    $slice = array_slice(array_values($filtered), $offset, $limit);

    return array_map(function ($product) use ($categories) {
        return catalogue_format_product($product, $categories);
    }, $slice);
}

/**
 * Finds a single product in the local snapshot (fallback).
 *
 * @param string $identifier Product ID or slug.
 */
function catalogue_local_find_product(string $identifier): ?array
{
    $products = catalogue_load_local_products();
    $categories = catalogue_categories_map();

    foreach ($products as $product) {
        if (catalogue_match_product_identifier($product, $identifier)) {
            return catalogue_format_product($product, $categories);
        }
    }

    return null;
}

/**
 * Resolves a category filter (id or slug) against Supabase.
 *
 * @return array{query:string|null, id:string|null}
 */
function catalogue_resolve_supabase_category(?string $categoryParam): array
{
    if (!$categoryParam) {
        return ['query' => null, 'id' => null, 'slug' => null];
    }

    $categoryParam = trim($categoryParam);
    if ($categoryParam === '') {
        return ['query' => null, 'id' => null, 'slug' => null];
    }

    if (isset(CATALOGUE_CATEGORY_MAP[$categoryParam])) {
        $meta = CATALOGUE_CATEGORY_MAP[$categoryParam];
        return [
            'query' => 'category.slug=eq.' . rawurlencode($meta['slug']),
            'id' => $meta['id'],
            'slug' => $meta['slug'],
        ];
    }

    foreach (CATALOGUE_CATEGORY_MAP as $meta) {
        if ($meta['slug'] === $categoryParam) {
            return [
                'query' => 'category.slug=eq.' . rawurlencode($meta['slug']),
                'id' => $meta['id'],
                'slug' => $meta['slug'],
            ];
        }
    }

    if (preg_match('/^[0-9a-fA-F-]{36}$/', $categoryParam) === 1) {
        return [
            'query' => 'category_id=eq.' . rawurlencode($categoryParam),
            'id' => null,
            'slug' => null,
        ];
    }

    return ['query' => null, 'id' => null, 'slug' => null];
}

/**
 * Builds a standard API success payload.
 *
 * @param array<int, mixed>|array<string, mixed>|null $data
 * @param array<string, mixed> $meta
 */
function catalogue_api_success($data, array $meta = [], ?string $resourceKey = null): string
{
    $response = [
        'success' => true,
        'data' => $data,
        'meta' => $meta,
    ];

    if ($resourceKey !== null && $resourceKey !== '') {
        $response[$resourceKey] = $data;
    }

    return json_encode($response, JSON_UNESCAPED_UNICODE);
}

/**
 * Builds a standard API error payload.
 */
function catalogue_api_error(string $message, int $status = 500, array $context = []): string
{
    http_response_code($status);
    return json_encode([
        'success' => false,
        'error' => [
            'message' => $message,
            'context' => $context,
        ],
    ], JSON_UNESCAPED_UNICODE);
}
