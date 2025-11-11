<?php
/**
 * 관리자 제품 관리 API - 75개 제품 완전 버전
 */

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

// 75개 제품 데이터
$all_products = [
    // AGV 캐스터 (20개)
    ['id' => '1', 'category_id' => '1', 'category_name' => 'AGV 캐스터', 'name' => 'AGV 메카넘 휠 200mm', 
     'model_number' => 'AGV-MC-200', 'price' => 450000, 'stock_quantity' => 10, 'is_featured' => true, 'is_published' => true],
    ['id' => '2', 'category_id' => '1', 'category_name' => 'AGV 캐스터', 'name' => 'AGV 구동 모듈 DM-100',
     'model_number' => 'AGV-DM-100', 'price' => 380000, 'stock_quantity' => 15, 'is_featured' => true, 'is_published' => true],
    ['id' => '3', 'category_id' => '1', 'category_name' => 'AGV 캐스터', 'name' => 'AGV 스위블 캐스터 SC-150',
     'model_number' => 'SC-150', 'price' => 285000, 'stock_quantity' => 25, 'is_featured' => false, 'is_published' => true],
    ['id' => '4', 'category_id' => '1', 'category_name' => 'AGV 캐스터', 'name' => 'AGV 고정 캐스터 FC-200',
     'model_number' => 'FC-200', 'price' => 265000, 'stock_quantity' => 20, 'is_featured' => false, 'is_published' => true],
    ['id' => '5', 'category_id' => '1', 'category_name' => 'AGV 캐스터', 'name' => 'AGV 브레이크 캐스터 BC-180',
     'model_number' => 'BC-180', 'price' => 320000, 'stock_quantity' => 18, 'is_featured' => false, 'is_published' => true],
    ['id' => '6', 'category_id' => '1', 'category_name' => 'AGV 캐스터', 'name' => 'AGV 충격흡수 캐스터 SC-250',
     'model_number' => 'SC-250', 'price' => 420000, 'stock_quantity' => 12, 'is_featured' => false, 'is_published' => true],
    ['id' => '7', 'category_id' => '1', 'category_name' => 'AGV 캐스터', 'name' => 'AGV 정밀 캐스터 PC-100',
     'model_number' => 'PC-100', 'price' => 480000, 'stock_quantity' => 8, 'is_featured' => false, 'is_published' => true],
    ['id' => '8', 'category_id' => '1', 'category_name' => 'AGV 캐스터', 'name' => 'AGV 저소음 캐스터 LC-150',
     'model_number' => 'LC-150', 'price' => 340000, 'stock_quantity' => 22, 'is_featured' => false, 'is_published' => true],
    ['id' => '9', 'category_id' => '1', 'category_name' => 'AGV 캐스터', 'name' => 'AGV 내열 캐스터 HC-200',
     'model_number' => 'HC-200', 'price' => 390000, 'stock_quantity' => 14, 'is_featured' => false, 'is_published' => true],
    ['id' => '10', 'category_id' => '1', 'category_name' => 'AGV 캐스터', 'name' => 'AGV 방수 캐스터 WC-180',
     'model_number' => 'WC-180', 'price' => 360000, 'stock_quantity' => 16, 'is_featured' => false, 'is_published' => true],
    ['id' => '11', 'category_id' => '1', 'category_name' => 'AGV 캐스터', 'name' => 'AGV 고속 캐스터 HS-220',
     'model_number' => 'HS-220', 'price' => 520000, 'stock_quantity' => 10, 'is_featured' => false, 'is_published' => true],
    ['id' => '12', 'category_id' => '1', 'category_name' => 'AGV 캐스터', 'name' => 'AGV 중량물 캐스터 HD-300',
     'model_number' => 'HD-300', 'price' => 580000, 'stock_quantity' => 6, 'is_featured' => false, 'is_published' => true],
    ['id' => '13', 'category_id' => '1', 'category_name' => 'AGV 캐스터', 'name' => 'AGV 컴팩트 캐스터 CC-80',
     'model_number' => 'CC-80', 'price' => 220000, 'stock_quantity' => 30, 'is_featured' => false, 'is_published' => true],
    ['id' => '14', 'category_id' => '1', 'category_name' => 'AGV 캐스터', 'name' => 'AGV 다목적 캐스터 MC-150',
     'model_number' => 'MC-150', 'price' => 310000, 'stock_quantity' => 24, 'is_featured' => false, 'is_published' => true],
    ['id' => '15', 'category_id' => '1', 'category_name' => 'AGV 캐스터', 'name' => 'AGV 탄성 캐스터 EC-200',
     'model_number' => 'EC-200', 'price' => 350000, 'stock_quantity' => 18, 'is_featured' => false, 'is_published' => true],
    ['id' => '16', 'category_id' => '1', 'category_name' => 'AGV 캐스터', 'name' => 'AGV 안전 캐스터 SC-175',
     'model_number' => 'SC-175', 'price' => 410000, 'stock_quantity' => 15, 'is_featured' => false, 'is_published' => true],
    ['id' => '17', 'category_id' => '1', 'category_name' => 'AGV 캐스터', 'name' => 'AGV 듀얼 캐스터 DC-250',
     'model_number' => 'DC-250', 'price' => 460000, 'stock_quantity' => 12, 'is_featured' => false, 'is_published' => true],
    ['id' => '18', 'category_id' => '1', 'category_name' => 'AGV 캐스터', 'name' => 'AGV 스마트 캐스터 IC-200',
     'model_number' => 'IC-200', 'price' => 620000, 'stock_quantity' => 8, 'is_featured' => false, 'is_published' => true],
    ['id' => '19', 'category_id' => '1', 'category_name' => 'AGV 캐스터', 'name' => 'AGV 모듈형 캐스터 MC-180',
     'model_number' => 'MC-180', 'price' => 380000, 'stock_quantity' => 20, 'is_featured' => false, 'is_published' => true],
    ['id' => '20', 'category_id' => '1', 'category_name' => 'AGV 캐스터', 'name' => 'AGV 특수 캐스터 SP-200',
     'model_number' => 'SP-200', 'price' => 490000, 'stock_quantity' => 10, 'is_featured' => false, 'is_published' => true],
    
    // 장비용 캐스터 (20개)
    ['id' => '21', 'category_id' => '2', 'category_name' => '장비용 캐스터', 'name' => '산업용 중량물 캐스터 200kg',
     'model_number' => 'HD-200', 'price' => 65000, 'stock_quantity' => 50, 'is_featured' => false, 'is_published' => true],
    ['id' => '22', 'category_id' => '2', 'category_name' => '장비용 캐스터', 'name' => '스테인리스 캐스터 SS-100',
     'model_number' => 'SS-100', 'price' => 95000, 'stock_quantity' => 30, 'is_featured' => false, 'is_published' => true],
    ['id' => '23', 'category_id' => '2', 'category_name' => '장비용 캐스터', 'name' => '의료장비 캐스터 MC-75',
     'model_number' => 'MC-75', 'price' => 85000, 'stock_quantity' => 25, 'is_featured' => false, 'is_published' => true],
    ['id' => '24', 'category_id' => '2', 'category_name' => '장비용 캐스터', 'name' => '항균 캐스터 AC-100',
     'model_number' => 'AC-100', 'price' => 78000, 'stock_quantity' => 35, 'is_featured' => false, 'is_published' => true],
    ['id' => '25', 'category_id' => '2', 'category_name' => '장비용 캐스터', 'name' => '충격흡수 캐스터 SA-150',
     'model_number' => 'SA-150', 'price' => 92000, 'stock_quantity' => 28, 'is_featured' => false, 'is_published' => true],
    ['id' => '26', 'category_id' => '2', 'category_name' => '장비용 캐스터', 'name' => '회전 브레이크 캐스터 RB-125',
     'model_number' => 'RB-125', 'price' => 88000, 'stock_quantity' => 32, 'is_featured' => false, 'is_published' => true],
    ['id' => '27', 'category_id' => '2', 'category_name' => '장비용 캐스터', 'name' => '저온용 캐스터 LC-100',
     'model_number' => 'LC-100', 'price' => 105000, 'stock_quantity' => 20, 'is_featured' => false, 'is_published' => true],
    ['id' => '28', 'category_id' => '2', 'category_name' => '장비용 캐스터', 'name' => '고온용 캐스터 HC-150',
     'model_number' => 'HC-150', 'price' => 115000, 'stock_quantity' => 18, 'is_featured' => false, 'is_published' => true],
    ['id' => '29', 'category_id' => '2', 'category_name' => '장비용 캐스터', 'name' => '정전기방지 캐스터 EC-100',
     'model_number' => 'EC-100', 'price' => 98000, 'stock_quantity' => 24, 'is_featured' => false, 'is_published' => true],
    ['id' => '30', 'category_id' => '2', 'category_name' => '장비용 캐스터', 'name' => '대차용 캐스터 TC-200',
     'model_number' => 'TC-200', 'price' => 72000, 'stock_quantity' => 40, 'is_featured' => false, 'is_published' => true],
    ['id' => '31', 'category_id' => '2', 'category_name' => '장비용 캐스터', 'name' => '컨베이어 캐스터 CC-150',
     'model_number' => 'CC-150', 'price' => 82000, 'stock_quantity' => 30, 'is_featured' => false, 'is_published' => true],
    ['id' => '32', 'category_id' => '2', 'category_name' => '장비용 캐스터', 'name' => '플랫폼 캐스터 PC-175',
     'model_number' => 'PC-175', 'price' => 76000, 'stock_quantity' => 35, 'is_featured' => false, 'is_published' => true],
    ['id' => '33', 'category_id' => '2', 'category_name' => '장비용 캐스터', 'name' => '산업용 경량 캐스터 LC-50',
     'model_number' => 'LC-50', 'price' => 45000, 'stock_quantity' => 60, 'is_featured' => false, 'is_published' => true],
    ['id' => '34', 'category_id' => '2', 'category_name' => '장비용 캐스터', 'name' => '듀얼록 캐스터 DL-125',
     'model_number' => 'DL-125', 'price' => 94000, 'stock_quantity' => 26, 'is_featured' => false, 'is_published' => true],
    ['id' => '35', 'category_id' => '2', 'category_name' => '장비용 캐스터', 'name' => '방향고정 캐스터 DC-100',
     'model_number' => 'DC-100', 'price' => 68000, 'stock_quantity' => 38, 'is_featured' => false, 'is_published' => true],
    ['id' => '36', 'category_id' => '2', 'category_name' => '장비용 캐스터', 'name' => '쇼핑카트 캐스터 SC-75',
     'model_number' => 'SC-75', 'price' => 35000, 'stock_quantity' => 80, 'is_featured' => false, 'is_published' => true],
    ['id' => '37', 'category_id' => '2', 'category_name' => '장비용 캐스터', 'name' => '가구용 캐스터 FC-50',
     'model_number' => 'FC-50', 'price' => 28000, 'stock_quantity' => 100, 'is_featured' => false, 'is_published' => true],
    ['id' => '38', 'category_id' => '2', 'category_name' => '장비용 캐스터', 'name' => '산업용 특수 캐스터 SC-300',
     'model_number' => 'SC-300', 'price' => 125000, 'stock_quantity' => 15, 'is_featured' => false, 'is_published' => true],
    ['id' => '39', 'category_id' => '2', 'category_name' => '장비용 캐스터', 'name' => '중량물 이동 캐스터 HM-250',
     'model_number' => 'HM-250', 'price' => 110000, 'stock_quantity' => 22, 'is_featured' => false, 'is_published' => true],
    ['id' => '40', 'category_id' => '2', 'category_name' => '장비용 캐스터', 'name' => '범용 산업 캐스터 UC-100',
     'model_number' => 'UC-100', 'price' => 58000, 'stock_quantity' => 45, 'is_featured' => false, 'is_published' => true],
    
    // 폴리우레탄 휠 (20개)
    ['id' => '41', 'category_id' => '3', 'category_name' => '폴리우레탄 휠', 'name' => '폴리우레탄 휠 75mm',
     'model_number' => 'PU-75', 'price' => 15000, 'stock_quantity' => 100, 'is_featured' => false, 'is_published' => true],
    ['id' => '42', 'category_id' => '3', 'category_name' => '폴리우레탄 휠', 'name' => '폴리우레탄 휠 100mm 고하중',
     'model_number' => 'PU-100HD', 'price' => 28000, 'stock_quantity' => 60, 'is_featured' => false, 'is_published' => true],
    ['id' => '43', 'category_id' => '3', 'category_name' => '폴리우레탄 휠', 'name' => '폴리우레탄 휠 125mm',
     'model_number' => 'PU-125', 'price' => 32000, 'stock_quantity' => 55, 'is_featured' => false, 'is_published' => true],
    ['id' => '44', 'category_id' => '3', 'category_name' => '폴리우레탄 휠', 'name' => '폴리우레탄 휠 150mm',
     'model_number' => 'PU-150', 'price' => 38000, 'stock_quantity' => 45, 'is_featured' => false, 'is_published' => true],
    ['id' => '45', 'category_id' => '3', 'category_name' => '폴리우레탄 휠', 'name' => '폴리우레탄 휠 200mm',
     'model_number' => 'PU-200', 'price' => 48000, 'stock_quantity' => 35, 'is_featured' => false, 'is_published' => true],
    ['id' => '46', 'category_id' => '3', 'category_name' => '폴리우레탄 휠', 'name' => '폴리우레탄 휠 50mm 소형',
     'model_number' => 'PU-50', 'price' => 12000, 'stock_quantity' => 120, 'is_featured' => false, 'is_published' => true],
    ['id' => '47', 'category_id' => '3', 'category_name' => '폴리우레탄 휠', 'name' => '내마모 폴리우레탄 휠 100mm',
     'model_number' => 'PU-WR-100', 'price' => 35000, 'stock_quantity' => 50, 'is_featured' => false, 'is_published' => true],
    ['id' => '48', 'category_id' => '3', 'category_name' => '폴리우레탄 휠', 'name' => '탄성 폴리우레탄 휠 125mm',
     'model_number' => 'PU-EL-125', 'price' => 40000, 'stock_quantity' => 40, 'is_featured' => false, 'is_published' => true],
    ['id' => '49', 'category_id' => '3', 'category_name' => '폴리우레탄 휠', 'name' => '정밀 폴리우레탄 휠 75mm',
     'model_number' => 'PU-PR-75', 'price' => 22000, 'stock_quantity' => 70, 'is_featured' => false, 'is_published' => true],
    ['id' => '50', 'category_id' => '3', 'category_name' => '폴리우레탄 휠', 'name' => '컬러 폴리우레탄 휠 100mm',
     'model_number' => 'PU-CL-100', 'price' => 30000, 'stock_quantity' => 65, 'is_featured' => false, 'is_published' => true],
    ['id' => '51', 'category_id' => '3', 'category_name' => '폴리우레탄 휠', 'name' => '저온용 폴리우레탄 휠 150mm',
     'model_number' => 'PU-LT-150', 'price' => 45000, 'stock_quantity' => 30, 'is_featured' => false, 'is_published' => true],
    ['id' => '52', 'category_id' => '3', 'category_name' => '폴리우레탄 휠', 'name' => '고온용 폴리우레탄 휠 125mm',
     'model_number' => 'PU-HT-125', 'price' => 42000, 'stock_quantity' => 32, 'is_featured' => false, 'is_published' => true],
    ['id' => '53', 'category_id' => '3', 'category_name' => '폴리우레탄 휠', 'name' => '충격흡수 폴리우레탄 휠 100mm',
     'model_number' => 'PU-SH-100', 'price' => 33000, 'stock_quantity' => 48, 'is_featured' => false, 'is_published' => true],
    ['id' => '54', 'category_id' => '3', 'category_name' => '폴리우레탄 휠', 'name' => '방수 폴리우레탄 휠 75mm',
     'model_number' => 'PU-WP-75', 'price' => 25000, 'stock_quantity' => 75, 'is_featured' => false, 'is_published' => true],
    ['id' => '55', 'category_id' => '3', 'category_name' => '폴리우레탄 휠', 'name' => '항균 폴리우레탄 휠 100mm',
     'model_number' => 'PU-AB-100', 'price' => 36000, 'stock_quantity' => 55, 'is_featured' => false, 'is_published' => true],
    ['id' => '56', 'category_id' => '3', 'category_name' => '폴리우레탄 휠', 'name' => '듀얼 폴리우레탄 휠 150mm',
     'model_number' => 'PU-DL-150', 'price' => 52000, 'stock_quantity' => 28, 'is_featured' => false, 'is_published' => true],
    ['id' => '57', 'category_id' => '3', 'category_name' => '폴리우레탄 휠', 'name' => '경량 폴리우레탄 휠 125mm',
     'model_number' => 'PU-LG-125', 'price' => 34000, 'stock_quantity' => 42, 'is_featured' => false, 'is_published' => true],
    ['id' => '58', 'category_id' => '3', 'category_name' => '폴리우레탄 휠', 'name' => '강화 폴리우레탄 휠 200mm',
     'model_number' => 'PU-RF-200', 'price' => 58000, 'stock_quantity' => 25, 'is_featured' => false, 'is_published' => true],
    ['id' => '59', 'category_id' => '3', 'category_name' => '폴리우레탄 휠', 'name' => '특수 폴리우레탄 휠 175mm',
     'model_number' => 'PU-SP-175', 'price' => 50000, 'stock_quantity' => 30, 'is_featured' => false, 'is_published' => true],
    ['id' => '60', 'category_id' => '3', 'category_name' => '폴리우레탄 휠', 'name' => '범용 폴리우레탄 휠 100mm',
     'model_number' => 'PU-UN-100', 'price' => 26000, 'stock_quantity' => 80, 'is_featured' => false, 'is_published' => true],
    
    // 러버 휠 (15개)
    ['id' => '61', 'category_id' => '4', 'category_name' => '러버 휠', 'name' => '고무 휠 100mm',
     'model_number' => 'RW-100', 'price' => 18000, 'stock_quantity' => 80, 'is_featured' => false, 'is_published' => true],
    ['id' => '62', 'category_id' => '4', 'category_name' => '러버 휠', 'name' => '천연고무 휠 125mm',
     'model_number' => 'RW-NR-125', 'price' => 22000, 'stock_quantity' => 65, 'is_featured' => false, 'is_published' => true],
    ['id' => '63', 'category_id' => '4', 'category_name' => '러버 휠', 'name' => '합성고무 휠 150mm',
     'model_number' => 'RW-SR-150', 'price' => 25000, 'stock_quantity' => 55, 'is_featured' => false, 'is_published' => true],
    ['id' => '64', 'category_id' => '4', 'category_name' => '러버 휠', 'name' => '실리콘 고무 휠 75mm',
     'model_number' => 'RW-SI-75', 'price' => 28000, 'stock_quantity' => 45, 'is_featured' => false, 'is_published' => true],
    ['id' => '65', 'category_id' => '4', 'category_name' => '러버 휠', 'name' => '내열 고무 휠 100mm',
     'model_number' => 'RW-HR-100', 'price' => 30000, 'stock_quantity' => 40, 'is_featured' => false, 'is_published' => true],
    ['id' => '66', 'category_id' => '4', 'category_name' => '러버 휠', 'name' => '내한 고무 휠 125mm',
     'model_number' => 'RW-CR-125', 'price' => 32000, 'stock_quantity' => 38, 'is_featured' => false, 'is_published' => true],
    ['id' => '67', 'category_id' => '4', 'category_name' => '러버 휠', 'name' => '발포 고무 휠 200mm',
     'model_number' => 'RW-FM-200', 'price' => 35000, 'stock_quantity' => 35, 'is_featured' => false, 'is_published' => true],
    ['id' => '68', 'category_id' => '4', 'category_name' => '러버 휠', 'name' => '솔리드 고무 휠 150mm',
     'model_number' => 'RW-SD-150', 'price' => 27000, 'stock_quantity' => 50, 'is_featured' => false, 'is_published' => true],
    ['id' => '69', 'category_id' => '4', 'category_name' => '러버 휠', 'name' => '공압 고무 휠 250mm',
     'model_number' => 'RW-PN-250', 'price' => 45000, 'stock_quantity' => 25, 'is_featured' => false, 'is_published' => true],
    ['id' => '70', 'category_id' => '4', 'category_name' => '러버 휠', 'name' => '논슬립 고무 휠 100mm',
     'model_number' => 'RW-NS-100', 'price' => 24000, 'stock_quantity' => 60, 'is_featured' => false, 'is_published' => true],
    ['id' => '71', 'category_id' => '4', 'category_name' => '러버 휠', 'name' => '정전기방지 고무 휠 125mm',
     'model_number' => 'RW-ESD-125', 'price' => 34000, 'stock_quantity' => 35, 'is_featured' => false, 'is_published' => true],
    ['id' => '72', 'category_id' => '4', 'category_name' => '러버 휠', 'name' => '의료용 고무 휠 75mm',
     'model_number' => 'RW-MD-75', 'price' => 26000, 'stock_quantity' => 48, 'is_featured' => false, 'is_published' => true],
    ['id' => '73', 'category_id' => '4', 'category_name' => '러버 휠', 'name' => '산업용 고무 휠 175mm',
     'model_number' => 'RW-ID-175', 'price' => 38000, 'stock_quantity' => 32, 'is_featured' => false, 'is_published' => true],
    ['id' => '74', 'category_id' => '4', 'category_name' => '러버 휠', 'name' => '특수 고무 휠 200mm',
     'model_number' => 'RW-SP-200', 'price' => 42000, 'stock_quantity' => 28, 'is_featured' => false, 'is_published' => true],
    ['id' => '75', 'category_id' => '4', 'category_name' => '러버 휠', 'name' => '범용 고무 휠 100mm',
     'model_number' => 'RW-UN-100', 'price' => 20000, 'stock_quantity' => 75, 'is_featured' => false, 'is_published' => true]
];

// GET 요청 처리
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    echo json_encode([
        'success' => true,
        'data' => $all_products,
        'total' => count($all_products),
        'message' => 'JP Caster 전체 75개 제품'
    ], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    exit;
}

// POST, PUT, DELETE 처리
echo json_encode([
    'success' => true,
    'message' => '읽기 전용 모드입니다. 실제 데이터베이스 연결이 필요합니다.'
], JSON_UNESCAPED_UNICODE);
?>