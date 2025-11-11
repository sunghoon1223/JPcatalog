# 대표 이미지 Override 빠른 실행 스크립트 (PowerShell)
# Claude Code 웹 버전에서 사용

Write-Host "=========================================" -ForegroundColor Cyan
Write-Host "대표 이미지 Override 작업 실행" -ForegroundColor Cyan
Write-Host "=========================================" -ForegroundColor Cyan
Write-Host ""

# 1. 코드 배포
Write-Host "[1/5] 코드 파일 배포 중..." -ForegroundColor Yellow
Copy-Item -Path "소스\코드\catalogue.php" -Destination "public_html\api\utils\catalogue.php" -Force
Copy-Item -Path "소스\데이터\overrides.json" -Destination "public_html\images\overrides\overrides.json" -Force
Write-Host "✓ 코드 배포 완료" -ForegroundColor Green
Write-Host ""

# 2. PHP 서버 상태 확인
Write-Host "[2/5] PHP 서버 확인 중..." -ForegroundColor Yellow
$phpProcess = Get-Process -Name php -ErrorAction SilentlyContinue
if ($phpProcess) {
    Write-Host "✓ PHP 서버 실행 중 (PID: $($phpProcess.Id))" -ForegroundColor Green
} else {
    Write-Host "⚠ PHP 서버가 실행되지 않음" -ForegroundColor Red
    Write-Host "다음 명령어로 서버를 시작하세요:" -ForegroundColor Yellow
    Write-Host "  cd C:\rebuild_e2e" -ForegroundColor White
    Write-Host "  C:\rebuild_e2e\php-8.3.27\php.exe -S 0.0.0.0:8000 -t public_html" -ForegroundColor White
}
Write-Host ""

# 3. API 테스트
Write-Host "[3/5] API 응답 테스트 중..." -ForegroundColor Yellow
try {
    $response = curl.exe -s "http://localhost:8000/api/products/get.php?id=165" 2>$null
    $json = $response | ConvertFrom-Json
    $mainImage = $json.data.main_image_url

    Write-Host "제품 165 main_image_url: $mainImage" -ForegroundColor White

    if ($mainImage -like "*/images/overrides/*") {
        Write-Host "✓ Override 이미지 정상 적용" -ForegroundColor Green
    } else {
        Write-Host "⚠ 여전히 crawled 이미지 사용 중" -ForegroundColor Red
    }
} catch {
    Write-Host "⚠ API 응답 없음 또는 오류: $($_.Exception.Message)" -ForegroundColor Red
}
Write-Host ""

# 4. 이미지 파일 확인
Write-Host "[4/5] 이미지 파일 존재 확인 중..." -ForegroundColor Yellow
$imagePath = "public_html\images\overrides\files\agv\165"
if (Test-Path $imagePath) {
    $fileCount = (Get-ChildItem -Path $imagePath).Count
    Write-Host "제품 165 이미지 파일: $fileCount 개" -ForegroundColor White
    if ($fileCount -ge 6) {
        Write-Host "✓ 이미지 파일 정상" -ForegroundColor Green
    } else {
        Write-Host "⚠ 이미지 파일 부족 (6개 이상 필요)" -ForegroundColor Red
    }
} else {
    Write-Host "⚠ Override 이미지 폴더 없음: $imagePath" -ForegroundColor Red
}
Write-Host ""

# 5. 최종 안내
Write-Host "[5/5] 작업 완료" -ForegroundColor Yellow
Write-Host ""
Write-Host "=========================================" -ForegroundColor Cyan
Write-Host "다음 단계:" -ForegroundColor Cyan
Write-Host "=========================================" -ForegroundColor Cyan
Write-Host ""
Write-Host "1. 브라우저 캐시 삭제:" -ForegroundColor Yellow
Write-Host "   - Chrome DevTools > Application > Clear storage" -ForegroundColor White
Write-Host "   - Service Workers > Unregister" -ForegroundColor White
Write-Host ""
Write-Host "2. 브라우저에서 확인:" -ForegroundColor Yellow
Write-Host "   http://localhost:8000/products/agv-light-duty-caster-series-jqr013-165" -ForegroundColor White
Write-Host ""
Write-Host "3. 나머지 제품 처리:" -ForegroundColor Yellow
Write-Host "   python scripts\apply_supabase_images_to_overrides.py --file final_result\소스\데이터\tmp_issue_urls.txt" -ForegroundColor White
Write-Host ""
Write-Host "4. Git 커밋:" -ForegroundColor Yellow
Write-Host "   git add ." -ForegroundColor White
Write-Host "   git commit -m 'fix: apply override images'" -ForegroundColor White
Write-Host "   git push origin master" -ForegroundColor White
Write-Host ""
Write-Host "상세 내역: 대표이미지_수정_작업_보고서.md" -ForegroundColor Cyan
Write-Host "=========================================" -ForegroundColor Cyan
