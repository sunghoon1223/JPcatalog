#!/bin/bash
# 대표 이미지 Override 빠른 실행 스크립트
# Claude Code 웹 버전에서 사용

set -e

echo "========================================="
echo "대표 이미지 Override 작업 실행"
echo "========================================="
echo ""

# 1. 코드 배포
echo "[1/5] 코드 파일 배포 중..."
cp 소스/코드/catalogue.php public_html/api/utils/catalogue.php
cp 소스/데이터/overrides.json public_html/images/overrides/overrides.json
echo "✓ 코드 배포 완료"
echo ""

# 2. PHP 서버 상태 확인
echo "[2/5] PHP 서버 확인 중..."
if netstat -ano 2>/dev/null | grep -q ":8000"; then
    echo "✓ PHP 서버 실행 중 (포트 8000)"
else
    echo "⚠ PHP 서버가 실행되지 않음"
    echo "다음 명령어로 서버를 시작하세요:"
    echo "  cd C:/rebuild_e2e"
    echo "  C:/rebuild_e2e/php-8.3.27/php.exe -S 0.0.0.0:8000 -t public_html"
fi
echo ""

# 3. API 테스트
echo "[3/5] API 응답 테스트 중..."
if command -v curl >/dev/null 2>&1; then
    RESPONSE=$(curl -s "http://localhost:8000/api/products/get.php?id=165" 2>/dev/null)
    if echo "$RESPONSE" | grep -q "main_image_url"; then
        MAIN_IMAGE=$(echo "$RESPONSE" | grep -oP '"main_image_url":"[^"]+' | cut -d'"' -f4)
        echo "제품 165 main_image_url: $MAIN_IMAGE"

        if echo "$MAIN_IMAGE" | grep -q "/images/overrides/"; then
            echo "✓ Override 이미지 정상 적용"
        else
            echo "⚠ 여전히 crawled 이미지 사용 중"
        fi
    else
        echo "⚠ API 응답 없음 또는 오류"
    fi
else
    echo "⚠ curl 명령어를 찾을 수 없음"
fi
echo ""

# 4. 이미지 파일 확인
echo "[4/5] 이미지 파일 존재 확인 중..."
if [ -d "public_html/images/overrides/files/agv/165" ]; then
    FILE_COUNT=$(ls public_html/images/overrides/files/agv/165/ 2>/dev/null | wc -l)
    echo "제품 165 이미지 파일: $FILE_COUNT 개"
    if [ "$FILE_COUNT" -ge 6 ]; then
        echo "✓ 이미지 파일 정상"
    else
        echo "⚠ 이미지 파일 부족 (6개 이상 필요)"
    fi
else
    echo "⚠ Override 이미지 폴더 없음"
fi
echo ""

# 5. 최종 안내
echo "[5/5] 작업 완료"
echo ""
echo "========================================="
echo "다음 단계:"
echo "========================================="
echo ""
echo "1. 브라우저 캐시 삭제:"
echo "   - Chrome DevTools > Application > Clear storage"
echo "   - Service Workers > Unregister"
echo ""
echo "2. 브라우저에서 확인:"
echo "   http://localhost:8000/products/agv-light-duty-caster-series-jqr013-165"
echo ""
echo "3. 나머지 제품 처리:"
echo "   python scripts/apply_supabase_images_to_overrides.py --file final_result/소스/데이터/tmp_issue_urls.txt"
echo ""
echo "4. Git 커밋:"
echo "   git add ."
echo "   git commit -m 'fix: apply override images'"
echo "   git push origin master"
echo ""
echo "상세 내역: 대표이미지_수정_작업_보고서.md"
echo "========================================="
