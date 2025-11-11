<?php
/**
 * 장바구니 목록 조회 API (프로덕션 테스트용 하드코딩 버전)
 * GET /api/cart/list.php
 */

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit();
}

try {
    // 프로덕션 테스트용 하드코딩된 장바구니 데이터
    $cart_items = [
        [
            'id' => 'cart_001',
            'product_id' => '1',
            'user_id' => null,
            'session_id' => 'test_session',
            'name' => 'AGV 구동 모듈 DM-100',
            'slug' => 'agv-drive-module-dm-100',
            'price' => 250000.0,
            'quantity' => 2,
            'main_image_url' => '/images/products/agv-module-1.jpg',
            'stock_quantity' => 50,
            'subtotal' => 500000.0,
            'in_stock' => true,
            'created_at' => date('Y-m-d H:i:s')
        ],
        [
            'id' => 'cart_002', 
            'product_id' => '15',
            'user_id' => null,
            'session_id' => 'test_session',
            'name' => '메카넘 휠 MW-150',
            'slug' => 'mecanum-wheel-mw-150',
            'price' => 180000.0,
            'quantity' => 1,
            'main_image_url' => '/images/products/mecanum-wheel-1.jpg',
            'stock_quantity' => 30,
            'subtotal' => 180000.0,
            'in_stock' => true,
            'created_at' => date('Y-m-d H:i:s', strtotime('-5 minutes'))
        ]
    ];

    // 총합 계산
    $total_amount = 0;
    $total_items = 0;

    foreach ($cart_items as $item) {
        $total_amount += $item['subtotal'];
        $total_items += $item['quantity'];
    }

    $response = [
        'success' => true,
        'data' => [
            'items' => $cart_items,
            'summary' => [
                'total_items' => $total_items,
                'total_amount' => $total_amount,
                'item_count' => count($cart_items)
            ]
        ]
    ];

    echo json_encode($response, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Internal server error',
        'message' => $e->getMessage()
    ]);
}
?>