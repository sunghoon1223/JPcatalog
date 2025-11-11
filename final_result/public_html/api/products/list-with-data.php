<?php
/**
 * 제품 목록 API - 임시 데이터 포함 버전
 * 데이터베이스 연결 없이도 작동
 */

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');

// 임시 제품 데이터
$products = [
    [
        'id' => '1',
        'category_id' => '1',
        'category_name' => 'AGV 캐스터',
        'category_slug' => 'agv-caster',
        'name' => 'AGV 메카넘 휠 200mm',
        'name_en' => 'AGV Mecanum Wheel 200mm',
        'slug' => 'agv-mecanum-200',
        'model_number' => 'AGV-MC-200',
        'description' => '전방향 이동이 가능한 AGV용 메카넘 휠입니다. 정밀한 위치 제어와 부드러운 움직임을 제공합니다.',
        'price' => 450000,
        'currency' => 'KRW',
        'image_url' => '/images/products/agv-mecanum-200.jpg',
        'stock_quantity' => 10,
        'is_featured' => true,
        'is_published' => true,
        'specifications' => [
            '직경' => '200mm',
            '적재하중' => '150kg',
            '회전각도' => '360도',
            '재질' => '알루미늄 + 폴리우레탄'
        ],
        'features' => [
            '전방향 이동 가능',
            '정밀 위치 제어',
            '저소음 설계',
            '고내구성'
        ]
    ],
    [
        'id' => '2',
        'category_id' => '1',
        'category_name' => 'AGV 캐스터',
        'category_slug' => 'agv-caster',
        'name' => 'AGV 구동 모듈 DM-100',
        'name_en' => 'AGV Drive Module DM-100',
        'slug' => 'agv-drive-dm100',
        'model_number' => 'AGV-DM-100',
        'description' => '고성능 AGV 구동 모듈, 최대 적재 중량 100kg',
        'price' => 380000,
        'image_url' => '/images/products/agv-drive-100.jpg',
        'stock_quantity' => 15,
        'is_featured' => true,
        'is_published' => true,
        'specifications' => [
            '최대 적재중량' => '100kg',
            '구동 방식' => '서보 모터',
            '속도' => '0-2m/s',
            '사용 환경' => '실내'
        ]
    ],
    [
        'id' => '3',
        'category_id' => '2',
        'category_name' => '장비용 캐스터',
        'category_slug' => 'equipment-caster',
        'name' => '산업용 중량물 캐스터 200kg',
        'name_en' => 'Heavy Duty Caster 200kg',
        'slug' => 'heavy-duty-200',
        'model_number' => 'HD-200',
        'description' => '200kg 하중을 견디는 산업용 캐스터',
        'price' => 65000,
        'image_url' => '/images/products/heavy-duty-200.jpg',
        'stock_quantity' => 50,
        'is_featured' => false,
        'is_published' => true,
        'specifications' => [
            '적재중량' => '200kg',
            '휠 직경' => '150mm',
            '재질' => '강철 + 폴리우레탄',
            '브레이크' => '있음'
        ]
    ],
    [
        'id' => '4',
        'category_id' => '3',
        'category_name' => '폴리우레탄 휠',
        'category_slug' => 'polyurethane-wheel',
        'name' => '폴리우레탄 휠 75mm',
        'name_en' => 'Polyurethane Wheel 75mm',
        'slug' => 'pu-wheel-75',
        'model_number' => 'PU-75',
        'description' => '저소음 폴리우레탄 휠',
        'price' => 15000,
        'image_url' => '/images/products/pu-wheel-75.jpg',
        'stock_quantity' => 100,
        'is_featured' => false,
        'is_published' => true,
        'specifications' => [
            '직경' => '75mm',
            '폭' => '25mm',
            '경도' => 'Shore A 95',
            '내하중' => '80kg'
        ]
    ],
    [
        'id' => '5',
        'category_id' => '4',
        'category_name' => '러버 휠',
        'category_slug' => 'rubber-wheel',
        'name' => '고무 휠 100mm',
        'name_en' => 'Rubber Wheel 100mm',
        'slug' => 'rubber-wheel-100',
        'model_number' => 'RW-100',
        'description' => '충격 흡수 고무 휠',
        'price' => 18000,
        'image_url' => '/images/products/rubber-wheel-100.jpg',
        'stock_quantity' => 80,
        'is_featured' => false,
        'is_published' => true,
        'specifications' => [
            '직경' => '100mm',
            '폭' => '30mm',
            '재질' => '천연고무',
            '내하중' => '120kg'
        ]
    ],
    [
        'id' => '6',
        'category_id' => '1',
        'category_name' => 'AGV 캐스터',
        'category_slug' => 'agv-caster',
        'name' => 'AGV 스위블 캐스터 SC-150',
        'name_en' => 'AGV Swivel Caster SC-150',
        'slug' => 'agv-swivel-sc150',
        'model_number' => 'SC-150',
        'description' => 'AGV용 회전 캐스터, 정밀 베어링 적용',
        'price' => 285000,
        'image_url' => '/images/products/agv-swivel-150.jpg',
        'stock_quantity' => 25,
        'is_featured' => false,
        'is_published' => true
    ],
    [
        'id' => '7',
        'category_id' => '2',
        'category_name' => '장비용 캐스터',
        'category_slug' => 'equipment-caster',
        'name' => '스테인리스 캐스터 SS-100',
        'name_en' => 'Stainless Steel Caster SS-100',
        'slug' => 'stainless-ss100',
        'model_number' => 'SS-100',
        'description' => '식품 공장용 스테인리스 캐스터',
        'price' => 95000,
        'image_url' => '/images/products/stainless-100.jpg',
        'stock_quantity' => 30,
        'is_featured' => false,
        'is_published' => true
    ],
    [
        'id' => '8',
        'category_id' => '3',
        'category_name' => '폴리우레탄 휠',
        'category_slug' => 'polyurethane-wheel',
        'name' => '폴리우레탄 휠 100mm 고하중',
        'name_en' => 'Heavy Duty PU Wheel 100mm',
        'slug' => 'pu-wheel-100hd',
        'model_number' => 'PU-100HD',
        'description' => '고하중용 폴리우레탄 휠',
        'price' => 28000,
        'image_url' => '/images/products/pu-wheel-100hd.jpg',
        'stock_quantity' => 60,
        'is_featured' => false,
        'is_published' => true
    ]
];

// 페이지네이션 파라미터
$page = isset($_GET['page']) ? intval($_GET['page']) : 1;
$limit = isset($_GET['limit']) ? intval($_GET['limit']) : 20;
$category = $_GET['category'] ?? '';
$search = $_GET['search'] ?? '';

// 필터링
$filtered = $products;

if ($category) {
    $filtered = array_filter($filtered, function($p) use ($category) {
        return $p['category_slug'] === $category;
    });
}

if ($search) {
    $filtered = array_filter($filtered, function($p) use ($search) {
        return stripos($p['name'], $search) !== false || 
               stripos($p['name_en'], $search) !== false ||
               stripos($p['model_number'], $search) !== false;
    });
}

// 응답
echo json_encode([
    'success' => true,
    'data' => [
        'products' => array_values($filtered),
        'pagination' => [
            'total' => count($filtered),
            'page' => $page,
            'limit' => $limit,
            'pages' => ceil(count($filtered) / $limit)
        ]
    ]
], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
?>