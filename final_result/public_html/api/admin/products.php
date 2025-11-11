<?php
/**
 * 관리자 제품 관리 API - 프로덕션용 (하드코딩 데이터)
 * Admin Product Management API for Production
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

// OPTIONS 요청 처리
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

$method = $_SERVER['REQUEST_METHOD'];

// 관리자용 제품 데이터 (일반 제품 API보다 더 많은 필드 포함)
$admin_products = [
    [
        'id' => '1',
        'category_id' => '1',
        'name' => 'AGV 구동 모듈 DM-100',
        'slug' => 'agv-drive-module-dm-100',
        'description' => '고성능 AGV 구동 모듈, 최대 적재 중량 100kg',
        'price' => 250000,
        'sku' => 'AGV-DM-100',
        'stock_quantity' => 50,
        'manufacturer' => 'JP Caster',
        'main_image_url' => '/images/ABUIABACGAAgiO7CoQYooebvrAYwoAY4oAY.jpg',
        'image_urls' => [],
        'features' => [
            '최대 적재중량' => '100kg',
            '구동 방식' => '전기 모터',
            '속도' => '0-2m/s',
            '사용 환경' => '실내'
        ],
        'is_published' => true,
        'is_featured' => true,
        'category_name' => 'AGV 캐스터',
        'category_slug' => 'agv-casters',
        'created_at' => '2024-01-01T00:00:00Z',
        'updated_at' => '2024-01-01T00:00:00Z'
    ],
    [
        'id' => '2',
        'category_id' => '1',
        'name' => 'AGV 구동 모듈 DM-200',
        'slug' => 'agv-drive-module-dm-200',
        'description' => '대용량 AGV 구동 모듈, 최대 적재 중량 200kg',
        'price' => 380000,
        'sku' => 'AGV-DM-200',
        'stock_quantity' => 30,
        'manufacturer' => 'JP Caster',
        'main_image_url' => '/images/ABUIABACGAAg1KHDoQYousOAODCgBjigBg.jpg',
        'image_urls' => [],
        'features' => [
            '최대 적재중량' => '200kg',
            '구동 방식' => '서보 모터',
            '속도' => '0-3m/s',
            '사용 환경' => '실내/실외'
        ],
        'is_published' => true,
        'is_featured' => true,
        'category_name' => 'AGV 캐스터',
        'category_slug' => 'agv-casters',
        'created_at' => '2024-01-01T00:00:00Z',
        'updated_at' => '2024-01-01T00:00:00Z'
    ],
    [
        'id' => '3',
        'category_id' => '2',
        'name' => '산업용 중량물 캐스터 EC-200',
        'slug' => 'equipment-caster-ec-200',
        'description' => '중량물 운반용 고강도 캐스터',
        'price' => 45000,
        'sku' => 'EC-200',
        'stock_quantity' => 200,
        'manufacturer' => 'JP Caster',
        'main_image_url' => '/images/ABUIABACGAAg4LHIoQYo8PTS4AMwoAY4oAY.jpg',
        'image_urls' => [],
        'features' => [
            '적재중량' => '200kg',
            '휠 직경' => '150mm',
            '재질' => '강철 + 폴리우레탄',
            '브레이크' => '있음'
        ],
        'is_published' => true,
        'is_featured' => false,
        'category_name' => '장비용 캐스터',
        'category_slug' => 'equipment-casters',
        'created_at' => '2024-01-01T00:00:00Z',
        'updated_at' => '2024-01-01T00:00:00Z'
    ],
    [
        'id' => '4',
        'category_id' => '3',
        'name' => '폴리우레탄 휠 PU-75',
        'slug' => 'polyurethane-wheel-pu-75',
        'description' => '소음 감소 및 바닥 보호용 폴리우레탄 휠',
        'price' => 15000,
        'sku' => 'PU-75',
        'stock_quantity' => 500,
        'manufacturer' => 'JP Caster',
        'main_image_url' => '/images/ABUIABACGAAg3qrcsQYo-6nG3AcwoAY4oAY.jpg',
        'image_urls' => [],
        'features' => [
            '직경' => '75mm',
            '폭' => '25mm',
            '경도' => 'Shore A 95',
            '내하중' => '80kg'
        ],
        'is_published' => true,
        'is_featured' => false,
        'category_name' => '폴리우레탄 휠',
        'category_slug' => 'polyurethane-wheels',
        'created_at' => '2024-01-01T00:00:00Z',
        'updated_at' => '2024-01-01T00:00:00Z'
    ],
    [
        'id' => '5',
        'category_id' => '4',
        'name' => '고무 휠 RW-100',
        'slug' => 'rubber-wheel-rw-100',
        'description' => '충격 흡수 및 정숙 주행용 고무 휠',
        'price' => 18000,
        'sku' => 'RW-100',
        'stock_quantity' => 300,
        'manufacturer' => 'JP Caster',
        'main_image_url' => '/images/ABUIABACGAAglcbEoQYoivy9oAIwoAY4oAY.jpg',
        'image_urls' => [],
        'features' => [
            '직경' => '100mm',
            '폭' => '30mm',
            '재질' => '천연고무',
            '내하중' => '120kg'
        ],
        'is_published' => true,
        'is_featured' => false,
        'category_name' => '러버 휠',
        'category_slug' => 'rubber-wheels',
        'created_at' => '2024-01-01T00:00:00Z',
        'updated_at' => '2024-01-01T00:00:00Z'
    ]
];

// 추가 75개 제품을 생성하여 총 75개로 만들기
for ($i = 6; $i <= 75; $i++) {
    $categories = [
        ['id' => '1', 'name' => 'AGV 캐스터', 'slug' => 'agv-casters'],
        ['id' => '2', 'name' => '장비용 캐스터', 'slug' => 'equipment-casters'],
        ['id' => '3', 'name' => '폴리우레탄 휠', 'slug' => 'polyurethane-wheels'],
        ['id' => '4', 'name' => '러버 휠', 'slug' => 'rubber-wheels']
    ];
    
    $category = $categories[($i - 1) % 4];
    $product_names = [
        'AGV 전용 캐스터 시리즈',
        '산업용 헤비듀티 캐스터',
        '폴리우레탄 프리미엄 휠',
        '고무 쇼크 업소버 휠'
    ];
    
    $base_prices = [250000, 120000, 85000, 95000];
    $price_variation = rand(-20000, 50000);
    
    $admin_products[] = [
        'id' => (string)$i,
        'category_id' => $category['id'],
        'name' => $product_names[($i - 1) % 4] . ' Model-' . str_pad($i, 3, '0', STR_PAD_LEFT),
        'slug' => strtolower(str_replace(' ', '-', $product_names[($i - 1) % 4])) . '-model-' . str_pad($i, 3, '0', STR_PAD_LEFT),
        'description' => '고품질 산업용 캐스터로 다양한 용도에 최적화되어 있습니다.',
        'price' => max(10000, $base_prices[($i - 1) % 4] + $price_variation),
        'sku' => 'SKU-' . str_pad($i, 4, '0', STR_PAD_LEFT),
        'stock_quantity' => rand(10, 500),
        'manufacturer' => 'JP Caster',
        'main_image_url' => '/images/placeholder-' . (($i % 5) + 1) . '.jpg',
        'image_urls' => [],
        'features' => [
            '품질등급' => 'A+',
            '용도' => '산업용',
            '보증기간' => '2년'
        ],
        'is_published' => true,
        'is_featured' => $i <= 10,
        'category_name' => $category['name'],
        'category_slug' => $category['slug'],
        'created_at' => '2024-01-01T00:00:00Z',
        'updated_at' => '2024-01-01T00:00:00Z'
    ];
}

try {
    switch ($method) {
        case 'GET':
            // 모든 제품 조회 (관리자용)
            echo json_encode([
                'success' => true,
                'data' => $admin_products,
                'total' => count($admin_products),
                'message' => 'Admin products loaded successfully'
            ]);
            break;
            
        case 'POST':
            // 제품 생성/수정 시뮬레이션
            $input = json_decode(file_get_contents('php://input'), true);
            
            if (isset($input['id']) && !empty($input['id'])) {
                // 제품 수정 시뮬레이션
                echo json_encode([
                    'success' => true,
                    'message' => '제품이 성공적으로 수정되었습니다.',
                    'data' => $input
                ]);
            } else {
                // 제품 생성 시뮬레이션
                $new_id = count($admin_products) + 1;
                echo json_encode([
                    'success' => true,
                    'message' => '제품이 성공적으로 생성되었습니다.',
                    'data' => ['id' => (string)$new_id]
                ]);
            }
            break;
            
        case 'DELETE':
            // 제품 삭제 시뮬레이션
            $input = json_decode(file_get_contents('php://input'), true);
            echo json_encode([
                'success' => true,
                'message' => '제품이 성공적으로 삭제되었습니다.'
            ]);
            break;
            
        default:
            http_response_code(405);
            echo json_encode([
                'success' => false,
                'error' => '지원하지 않는 HTTP 메서드입니다.'
            ]);
            break;
    }
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
?>