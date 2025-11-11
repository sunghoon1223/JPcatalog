<?php

require_once __DIR__ . '/public_html/api/utils/catalogue.php';

// Test record for ID 165
$record = [
    'id' => 165,
    'category_id' => 'cat_agv',
    'category' => ['slug' => 'agv-casters'],
    'match_info' => ['original_id' => 165]
];

echo "Testing catalogue_extract_numeric_id:\n";
$numId = catalogue_extract_numeric_id($record);
echo "  Result: " . ($numId !== null ? $numId : 'null') . "\n\n";

echo "Testing catalogue_load_overrides:\n";
$overrides = catalogue_load_overrides();
echo "  Loaded: " . (empty($overrides) ? 'empty' : 'yes') . "\n";
if (!empty($overrides) && isset($overrides['agv'])) {
    echo "  AGV keys: " . implode(', ', array_keys($overrides['agv'])) . "\n\n";
} else {
    echo "  No AGV data\n\n";
}

echo "Testing catalogue_lookup_overrides:\n";
$meta = catalogue_lookup_overrides($record);
if ($meta === null) {
    echo "  Result: NULL\n";
} else {
    echo "  Result: Found\n";
    echo "  Product images: " . count($meta['product_images'] ?? []) . "\n";
    if (!empty($meta['product_images'])) {
        echo "    First: " . $meta['product_images'][0] . "\n";
    }
    echo "  Drawing images: " . count($meta['drawing_images'] ?? []) . "\n";
}
