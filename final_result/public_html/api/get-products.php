<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

// 카테고리별 제품 조회를 위한 전용 엔드포인트
// URL: /api/get-products.php?category=agv-caster

try {
    $json_path = __DIR__ . '/../public/data/products.json';
    
    if (!file_exists($json_path)) {
        throw new Exception('Products JSON file not found');
    }
    
    $json_content = file_get_contents($json_path);
    $all_products = json_decode($json_content, true);
    
    if (!$all_products) {
        throw new Exception('Failed to parse products JSON');
    }
    
    // 카테고리 슬러그 매핑
    $category_map = [
        'agv-caster' => 'cat_agv',
        'industrial-caster' => 'cat_industrial',
        'polyurethane-wheel' => 'cat_polyurethane', 
        'rubber-wheel' => 'cat_rubber'
    ];
    
    $category_slug = isset($_GET['category']) ? $_GET['category'] : null;
    $category_id = null;
    
    if ($category_slug && isset($category_map[$category_slug])) {
        $category_id = $category_map[$category_slug];
    }
    
    // 필터링
    if ($category_id) {
        $filtered_products = array_filter($all_products, function($product) use ($category_id) {
            return isset($product['category_id']) && $product['category_id'] === $category_id;
        });
        $products = array_values($filtered_products);
    } else {
        $products = $all_products;
    }
    
    // 통계 정보
    $stats = [
        'cat_agv' => 0,
        'cat_industrial' => 0, 
        'cat_polyurethane' => 0,
        'cat_rubber' => 0
    ];
    
    foreach ($all_products as $product) {
        if (isset($product['category_id']) && isset($stats[$product['category_id']])) {
            $stats[$product['category_id']]++;
        }
    }
    
    echo json_encode([
        'success' => true,
        'products' => $products,
        'total' => count($products),
        'category_requested' => $category_slug,
        'category_id' => $category_id,
        'statistics' => [
            'agv_count' => $stats['cat_agv'],
            'industrial_count' => $stats['cat_industrial'],
            'polyurethane_count' => $stats['cat_polyurethane'],
            'rubber_count' => $stats['cat_rubber'],
            'total_count' => array_sum($stats)
        ],
        'data_source' => 'json_direct',
        'timestamp' => date('Y-m-d H:i:s')
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage(),
        'timestamp' => date('Y-m-d H:i:s')
    ]);
}
?>