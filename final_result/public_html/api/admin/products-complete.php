<?php
/**
 * 관리자 제품 관리 API - 완전한 버전
 */

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

// 개발 모드 체크
$dev_mode = isset($_GET['dev']) && $_GET['dev'] === 'true';

// 데이터베이스 연결 시도
$db_connected = false;
$pdo = null;

// 데이터베이스 설정 파일 확인
$config_files = [
    __DIR__ . '/../config/db-config-auto.php',
    __DIR__ . '/../config/database.php',
    __DIR__ . '/../config/db-config.php'
];

foreach ($config_files as $config_file) {
    if (file_exists($config_file)) {
        include $config_file;
        if (isset($db_config)) {
            try {
                $pdo = new PDO(
                    "mysql:host={$db_config['host']};dbname={$db_config['database']};charset={$db_config['charset']}",
                    $db_config['username'],
                    $db_config['password'],
                    [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
                );
                $db_connected = true;
                break;
            } catch (PDOException $e) {
                // 연결 실패, 다음 설정 시도
            }
        }
    }
}

// 데이터베이스 연결 안되면 임시 데이터 사용
if (!$db_connected) {
    // 임시 제품 데이터
    $products = [
        [
            'id' => '1',
            'category_id' => '1',
            'category_name' => 'AGV 캐스터',
            'name' => 'AGV 메카넘 휠 200mm',
            'name_en' => 'AGV Mecanum Wheel 200mm',
            'slug' => 'agv-mecanum-200',
            'model_number' => 'AGV-MC-200',
            'description' => '전방향 이동이 가능한 AGV용 메카넘 휠',
            'price' => 450000,
            'stock_quantity' => 10,
            'is_featured' => true,
            'is_published' => true,
            'created_at' => date('Y-m-d H:i:s')
        ],
        [
            'id' => '2',
            'category_id' => '1',
            'category_name' => 'AGV 캐스터',
            'name' => 'AGV 구동 모듈 DM-100',
            'name_en' => 'AGV Drive Module DM-100',
            'slug' => 'agv-drive-dm100',
            'model_number' => 'AGV-DM-100',
            'description' => '고성능 AGV 구동 모듈',
            'price' => 380000,
            'stock_quantity' => 15,
            'is_featured' => true,
            'is_published' => true,
            'created_at' => date('Y-m-d H:i:s')
        ],
        [
            'id' => '3',
            'category_id' => '2',
            'category_name' => '장비용 캐스터',
            'name' => '산업용 중량물 캐스터 200kg',
            'name_en' => 'Heavy Duty Caster 200kg',
            'slug' => 'heavy-duty-200',
            'model_number' => 'HD-200',
            'description' => '200kg 하중을 견디는 산업용 캐스터',
            'price' => 65000,
            'stock_quantity' => 50,
            'is_featured' => false,
            'is_published' => true,
            'created_at' => date('Y-m-d H:i:s')
        ],
        [
            'id' => '4',
            'category_id' => '3',
            'category_name' => '폴리우레탄 휠',
            'name' => '폴리우레탄 휠 75mm',
            'name_en' => 'Polyurethane Wheel 75mm',
            'slug' => 'pu-wheel-75',
            'model_number' => 'PU-75',
            'description' => '저소음 폴리우레탄 휠',
            'price' => 15000,
            'stock_quantity' => 100,
            'is_featured' => false,
            'is_published' => true,
            'created_at' => date('Y-m-d H:i:s')
        ],
        [
            'id' => '5',
            'category_id' => '4',
            'category_name' => '러버 휠',
            'name' => '고무 휠 100mm',
            'name_en' => 'Rubber Wheel 100mm',
            'slug' => 'rubber-wheel-100',
            'model_number' => 'RW-100',
            'description' => '충격 흡수 고무 휠',
            'price' => 18000,
            'stock_quantity' => 80,
            'is_featured' => false,
            'is_published' => true,
            'created_at' => date('Y-m-d H:i:s')
        ]
    ];
    
    // 메소드별 처리
    $method = $_SERVER['REQUEST_METHOD'];
    
    if ($method === 'GET') {
        // 제품 목록 반환
        echo json_encode([
            'success' => true,
            'data' => $products,
            'total' => count($products),
            'message' => '데이터베이스 미연결 - 임시 데이터 사용'
        ], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
        exit;
    }
}

// 데이터베이스 연결된 경우
$method = $_SERVER['REQUEST_METHOD'];

switch ($method) {
    case 'GET':
        // 제품 목록 조회
        try {
            $sql = "SELECT 
                        p.*,
                        c.name as category_name,
                        c.slug as category_slug
                    FROM products p
                    LEFT JOIN categories c ON p.category_id = c.id
                    ORDER BY p.created_at DESC";
            
            $stmt = $pdo->query($sql);
            $products = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // boolean 값 변환
            foreach ($products as &$product) {
                $product['is_featured'] = (bool)$product['is_featured'];
                $product['is_published'] = (bool)$product['is_published'];
                $product['price'] = floatval($product['price']);
                $product['stock_quantity'] = intval($product['stock_quantity']);
                
                // JSON 필드 파싱
                if (!empty($product['specifications'])) {
                    $product['specifications'] = json_decode($product['specifications'], true);
                }
                if (!empty($product['features'])) {
                    $product['features'] = json_decode($product['features'], true);
                }
                if (!empty($product['images'])) {
                    $product['images'] = json_decode($product['images'], true);
                }
            }
            
            echo json_encode([
                'success' => true,
                'data' => $products,
                'total' => count($products)
            ], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
        } catch (PDOException $e) {
            echo json_encode([
                'success' => false,
                'message' => '데이터 조회 실패: ' . $e->getMessage()
            ], JSON_UNESCAPED_UNICODE);
        }
        break;
        
    case 'POST':
        // 제품 추가
        $data = json_decode(file_get_contents('php://input'), true);
        
        try {
            $sql = "INSERT INTO products (
                        id, category_id, name, name_en, slug, model_number,
                        description, description_en, specifications, features,
                        price, currency, image_url, images, stock_quantity,
                        is_featured, is_published, sort_order
                    ) VALUES (
                        UUID(), :category_id, :name, :name_en, :slug, :model_number,
                        :description, :description_en, :specifications, :features,
                        :price, :currency, :image_url, :images, :stock_quantity,
                        :is_featured, :is_published, :sort_order
                    )";
            
            $stmt = $pdo->prepare($sql);
            
            // JSON 필드 인코딩
            $specifications = !empty($data['specifications']) ? json_encode($data['specifications'], JSON_UNESCAPED_UNICODE) : null;
            $features = !empty($data['features']) ? json_encode($data['features'], JSON_UNESCAPED_UNICODE) : null;
            $images = !empty($data['images']) ? json_encode($data['images'], JSON_UNESCAPED_UNICODE) : null;
            
            $stmt->execute([
                ':category_id' => $data['category_id'],
                ':name' => $data['name'],
                ':name_en' => $data['name_en'] ?? null,
                ':slug' => $data['slug'],
                ':model_number' => $data['model_number'] ?? null,
                ':description' => $data['description'] ?? null,
                ':description_en' => $data['description_en'] ?? null,
                ':specifications' => $specifications,
                ':features' => $features,
                ':price' => $data['price'] ?? 0,
                ':currency' => $data['currency'] ?? 'KRW',
                ':image_url' => $data['image_url'] ?? null,
                ':images' => $images,
                ':stock_quantity' => $data['stock_quantity'] ?? 0,
                ':is_featured' => $data['is_featured'] ?? false,
                ':is_published' => $data['is_published'] ?? true,
                ':sort_order' => $data['sort_order'] ?? 0
            ]);
            
            echo json_encode([
                'success' => true,
                'message' => '제품이 추가되었습니다.',
                'id' => $pdo->lastInsertId()
            ], JSON_UNESCAPED_UNICODE);
        } catch (PDOException $e) {
            echo json_encode([
                'success' => false,
                'message' => '제품 추가 실패: ' . $e->getMessage()
            ], JSON_UNESCAPED_UNICODE);
        }
        break;
        
    case 'PUT':
        // 제품 수정
        $data = json_decode(file_get_contents('php://input'), true);
        $id = $_GET['id'] ?? null;
        
        if (!$id) {
            echo json_encode(['success' => false, 'message' => '제품 ID가 필요합니다.'], JSON_UNESCAPED_UNICODE);
            exit;
        }
        
        try {
            $sql = "UPDATE products SET 
                        category_id = :category_id,
                        name = :name,
                        name_en = :name_en,
                        slug = :slug,
                        model_number = :model_number,
                        description = :description,
                        description_en = :description_en,
                        specifications = :specifications,
                        features = :features,
                        price = :price,
                        currency = :currency,
                        image_url = :image_url,
                        images = :images,
                        stock_quantity = :stock_quantity,
                        is_featured = :is_featured,
                        is_published = :is_published,
                        sort_order = :sort_order,
                        updated_at = CURRENT_TIMESTAMP
                    WHERE id = :id";
            
            $stmt = $pdo->prepare($sql);
            
            // JSON 필드 인코딩
            $specifications = !empty($data['specifications']) ? json_encode($data['specifications'], JSON_UNESCAPED_UNICODE) : null;
            $features = !empty($data['features']) ? json_encode($data['features'], JSON_UNESCAPED_UNICODE) : null;
            $images = !empty($data['images']) ? json_encode($data['images'], JSON_UNESCAPED_UNICODE) : null;
            
            $stmt->execute([
                ':id' => $id,
                ':category_id' => $data['category_id'],
                ':name' => $data['name'],
                ':name_en' => $data['name_en'] ?? null,
                ':slug' => $data['slug'],
                ':model_number' => $data['model_number'] ?? null,
                ':description' => $data['description'] ?? null,
                ':description_en' => $data['description_en'] ?? null,
                ':specifications' => $specifications,
                ':features' => $features,
                ':price' => $data['price'] ?? 0,
                ':currency' => $data['currency'] ?? 'KRW',
                ':image_url' => $data['image_url'] ?? null,
                ':images' => $images,
                ':stock_quantity' => $data['stock_quantity'] ?? 0,
                ':is_featured' => $data['is_featured'] ?? false,
                ':is_published' => $data['is_published'] ?? true,
                ':sort_order' => $data['sort_order'] ?? 0
            ]);
            
            echo json_encode([
                'success' => true,
                'message' => '제품이 수정되었습니다.'
            ], JSON_UNESCAPED_UNICODE);
        } catch (PDOException $e) {
            echo json_encode([
                'success' => false,
                'message' => '제품 수정 실패: ' . $e->getMessage()
            ], JSON_UNESCAPED_UNICODE);
        }
        break;
        
    case 'DELETE':
        // 제품 삭제
        $id = $_GET['id'] ?? null;
        
        if (!$id) {
            echo json_encode(['success' => false, 'message' => '제품 ID가 필요합니다.'], JSON_UNESCAPED_UNICODE);
            exit;
        }
        
        try {
            $sql = "DELETE FROM products WHERE id = :id";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([':id' => $id]);
            
            echo json_encode([
                'success' => true,
                'message' => '제품이 삭제되었습니다.'
            ], JSON_UNESCAPED_UNICODE);
        } catch (PDOException $e) {
            echo json_encode([
                'success' => false,
                'message' => '제품 삭제 실패: ' . $e->getMessage()
            ], JSON_UNESCAPED_UNICODE);
        }
        break;
        
    default:
        echo json_encode([
            'success' => false,
            'message' => '지원하지 않는 메소드입니다.'
        ], JSON_UNESCAPED_UNICODE);
}
?>