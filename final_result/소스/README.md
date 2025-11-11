# 소스 파일 디렉토리 구조

이 디렉토리는 대표 이미지 Override 작업과 관련된 모든 소스 파일을 포함합니다.

---

## 📁 디렉토리 구조

```
소스/
├── 코드/           # 수정된 PHP 코드
├── 스크립트/       # Python 자동화 스크립트
├── 테스트/         # PHP 테스트 파일
├── 데이터/         # 설정 및 데이터 파일
└── 리포트/         # 이전 작업 리포트
```

---

## 📄 파일 설명

### 코드/ (수정된 PHP 코드)

#### `catalogue.php`
**경로**: `public_html/api/utils/catalogue.php`
**목적**: 제품 카탈로그 API 핵심 로직

**주요 수정 사항**:
1. **Line 894-919**: Override 이미지 필터링 로직
   ```php
   if ($overrideMeta) {
       // crawled product/gallery 이미지 제거
       // override 이미지 추가
       // 첫 번째 override 이미지를 main으로 설정
   }
   ```

2. **Line 1167-1172**: finalMain 우선순위 로직
   ```php
   if ($overrideMeta && $mainImage !== null) {
       $finalMain = $mainImage; // Override 우선
   } else {
       $finalMain = $supabaseMain !== null ? $supabaseMain : $mainImage;
   }
   ```

**사용법**:
```bash
# 원본 파일에 배포
cp 소스/코드/catalogue.php ../../public_html/api/utils/catalogue.php

# 또는 final_result에 배포
cp 소스/코드/catalogue.php ../public_html/api/utils/catalogue.php
```

**의존성**:
- `public_html/images/overrides/overrides.json`
- `public_html/api/supabase-config.php`
- `public_html/api/utils/cors.php`

---

### 스크립트/ (Python 자동화 스크립트)

#### `apply_supabase_images_to_overrides.py`
**경로**: `scripts/apply_supabase_images_to_overrides.py`
**목적**: Supabase의 main_image_url과 gallery를 기준으로 overrides 폴더에 이미지 복사 및 overrides.json 업데이트

**기능**:
- Supabase에서 제품 데이터 가져오기
- 이미지 파일을 `/images/overrides/files/{category}/{id}/` 경로로 복사
- `overrides.json` 업데이트 (product_images, drawing_images)
- 노이즈 이미지 필터링 (placeholder, lowres, qr, banners)

**사용법**:
```bash
# URL 파일 기준 실행
python 소스/스크립트/apply_supabase_images_to_overrides.py --file 소스/데이터/tmp_issue_urls.txt

# 특정 ID 실행
python 소스/스크립트/apply_supabase_images_to_overrides.py --id 165,167,318

# 전체 재처리 (강제)
python 소스/스크립트/apply_supabase_images_to_overrides.py --file 소스/데이터/tmp_issue_urls.txt --force
```

**출력**:
- `/images/overrides/files/{category}/{id}/` 폴더에 이미지 파일 생성
- `/images/overrides/overrides.json` 업데이트

**환경 요구사항**:
- Python 3.x
- `requests` 패키지
- Supabase API 접근 권한

---

### 테스트/ (PHP 테스트 파일)

#### `test_overrides_lookup.php`
**목적**: `catalogue_lookup_overrides()` 함수 단위 테스트

**테스트 내용**:
1. `catalogue_extract_numeric_id()` - ID 165 추출
2. `catalogue_load_overrides()` - overrides.json 로드
3. `catalogue_lookup_overrides()` - 제품 165의 override 메타데이터 조회

**사용법**:
```bash
php test_overrides_lookup.php
```

**예상 출력**:
```
Testing catalogue_extract_numeric_id:
  Result: 165

Testing catalogue_load_overrides:
  Loaded: yes
  AGV keys: 165, 167, ...

Testing catalogue_lookup_overrides:
  Result: Found
  Product images: 3
    First: /images/overrides/files/agv/165/agv_165_gallery_01.jpg
  Drawing images: 3
```

#### `test_api_direct.php`
**목적**: API 전체 흐름 테스트 (Supabase 조회 없이 로컬 스냅샷 기준)

**테스트 내용**:
- `$_GET['id'] = '165'` 시뮬레이션
- `api/products/get.php` 실행
- JSON 응답 검증

**사용법**:
```bash
php test_api_direct.php 2>&1 | grep "main_image_url\|gallery"
```

**예상 출력**:
```json
{
  "main_image_url": "/images/overrides/files/agv/165/agv_165_gallery_01.jpg",
  "gallery": [
    "/images/overrides/files/agv/165/agv_165_gallery_01.jpg",
    ...
  ]
}
```

---

### 데이터/ (설정 및 데이터 파일)

#### `overrides.json`
**경로**: `public_html/images/overrides/overrides.json`
**목적**: 카테고리별 제품 ID에 대한 override 이미지 매핑

**구조**:
```json
{
  "agv": {
    "165": {
      "product_images": [
        "/images/overrides/files/agv/165/agv_165_gallery_01.jpg",
        "/images/overrides/files/agv/165/agv_165_gallery_02.jpg",
        "/images/overrides/files/agv/165/agv_165_gallery_03.jpg"
      ],
      "drawing_images": [
        "/images/overrides/files/agv/165/agv_165_drawing_1.jpg",
        "/images/overrides/files/agv/165/agv_165_drawing_2.jpg",
        "/images/overrides/files/agv/165/agv_165_drawing_3.jpg"
      ]
    },
    "167": { ... }
  },
  "rubber": { ... },
  "polyurethane": { ... },
  "equipment": { ... }
}
```

**사용법**:
```bash
# 원본 위치로 복사
cp 소스/데이터/overrides.json ../../public_html/images/overrides/overrides.json

# JSON 유효성 검사
cat 소스/데이터/overrides.json | python -m json.tool > /dev/null
```

#### `tmp_issue_urls.txt`
**목적**: 문제가 있는 110개 제품 URL 목록

**형식**:
```
http://127.0.0.1:8000/products/agv-light-duty-caster-series-jqr013-165
http://127.0.0.1:8000/products/agv-light-duty-caster-series-jqr022-167
...
```

**사용법**:
```bash
# 줄 수 확인
wc -l 소스/데이터/tmp_issue_urls.txt

# ID 추출
cat 소스/데이터/tmp_issue_urls.txt | grep -oP '\d+$'

# Python 스크립트에 입력
python 소스/스크립트/apply_supabase_images_to_overrides.py --file 소스/데이터/tmp_issue_urls.txt
```

---

### 리포트/ (이전 작업 리포트)

#### `overrides_sync_report.md`
**목적**: 2025-11-11 이전 작업 기록

**내용**:
- Supabase 기준 overrides 복원 작업
- 처리된 38개 제품 목록
- 파일 구조 검증 결과
- PHP 서버 상태

---

## 🚀 빠른 시작 가이드

### 1. 코드 배포
```bash
cd C:/rebuild_e2e/final_result

# catalogue.php 배포
cp 소스/코드/catalogue.php public_html/api/utils/catalogue.php

# overrides.json 배포
cp 소스/데이터/overrides.json public_html/images/overrides/overrides.json
```

### 2. PHP 서버 시작
```bash
# final_result에서 서버 실행
cd C:/rebuild_e2e/final_result
C:/rebuild_e2e/php-8.3.27/php.exe -S 0.0.0.0:8001 -t public_html
```

### 3. API 테스트
```bash
# 제품 165 조회
curl "http://localhost:8001/api/products/get.php?id=165"

# main_image_url 확인
curl -s "http://localhost:8001/api/products/get.php?id=165" | grep main_image_url
```

### 4. 나머지 제품 처리
```bash
cd C:/rebuild_e2e

# tmp_issue_urls.txt의 모든 제품 처리
python scripts/apply_supabase_images_to_overrides.py --file final_result/소스/데이터/tmp_issue_urls.txt

# overrides.json 업데이트 확인
cat public_html/images/overrides/overrides.json | python -m json.tool | head -50
```

---

## 🔍 트러블슈팅

### 문제: "이미지가 여전히 crawled 경로"

**해결책**:
1. PHP 서버 재시작
2. 브라우저 캐시 삭제
3. API 응답 직접 확인
4. catalogue.php 배포 확인

### 문제: "overrides.json에 제품 없음"

**해결책**:
```bash
# 스크립트 재실행
python 소스/스크립트/apply_supabase_images_to_overrides.py --id 165 --force

# overrides.json 확인
cat 소스/데이터/overrides.json | grep -A 20 "\"165\""
```

### 문제: "이미지 파일 404 에러"

**해결책**:
```bash
# 이미지 파일 존재 확인
ls -la public_html/images/overrides/files/agv/165/

# 스크립트로 이미지 재복사
python 소스/스크립트/apply_supabase_images_to_overrides.py --id 165 --force
```

---

## 📞 추가 지원

상세한 작업 내역은 `대표이미지_수정_작업_보고서.md` 참조.

**작성일**: 2025-11-11
**작성자**: Claude Code
