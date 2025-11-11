#!/usr/bin/env python3
"""
제품 데이터 크롤링 및 Supabase 업로드 스크립트
"""

import requests
import json
import time
import re
from urllib.parse import urlparse

# Supabase 설정
SUPABASE_URL = "https://bjqadhzkoxdwyfsglrvq.supabase.co"
SUPABASE_KEY = "eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9.eyJpc3MiOiJzdXBhYmFzZSIsInJlZiI6ImJqcWFkaHprb3hkd3lmc2dscnZxIiwicm9sZSI6ImFub24iLCJpYXQiOjE3NTE5ODE4MjksImV4cCI6MjA2NzU1NzgyOX0.aOWT_5FrDBxGADHeziRVFusvo6YGW_-IDbgib-rSQlg"

# API 설정
LOCAL_API_BASE = "http://localhost:8000/api"

def read_urls(file_path):
    """URL 파일 읽기"""
    with open(file_path, 'r', encoding='utf-8') as f:
        return [line.strip() for line in f if line.strip() and '/products/' in line]

def extract_product_id_from_url(url):
    """URL에서 제품 ID 추출"""
    # http://127.0.0.1:8000/products/agv-light-duty-caster-series-jqr013-165
    # 마지막 숫자가 ID
    match = re.search(r'-(\d+)$', url)
    if match:
        return match.group(1)
    return None

def fetch_product_from_local_api(product_id):
    """로컬 API에서 제품 정보 가져오기"""
    try:
        url = f"{LOCAL_API_BASE}/products/get.php?id={product_id}"
        response = requests.get(url, timeout=10)

        if response.status_code == 200:
            data = response.json()
            if data.get('success'):
                return data.get('data')

        print(f"  ⚠️  API 응답 실패: {product_id} - {response.status_code}")
        return None
    except Exception as e:
        print(f"  ❌ API 요청 오류: {product_id} - {str(e)}")
        return None

def upload_to_supabase(product_data):
    """Supabase에 제품 데이터 업로드"""
    try:
        headers = {
            "apikey": SUPABASE_KEY,
            "Authorization": f"Bearer {SUPABASE_KEY}",
            "Content-Type": "application/json",
            "Prefer": "return=representation"
        }

        # 제품 데이터 정리
        payload = {
            "id": product_data.get('id'),
            "name": product_data.get('name'),
            "slug": product_data.get('slug'),
            "description": product_data.get('description'),
            "main_image_url": product_data.get('main_image_url'),
            "gallery": product_data.get('gallery', []),
            "category_id": product_data.get('category_id'),
            "is_published": product_data.get('is_published', True),
            "price": product_data.get('price'),
            "features": product_data.get('features'),
            "technical_specs": product_data.get('technical_specs'),
        }

        # Supabase에 UPSERT (없으면 생성, 있으면 업데이트)
        url = f"{SUPABASE_URL}/rest/v1/products"
        response = requests.post(
            url,
            headers=headers,
            json=payload,
            timeout=10
        )

        if response.status_code in [200, 201]:
            print(f"  ✅ 업로드 성공: {product_data.get('id')} - {product_data.get('name')}")
            return True
        else:
            print(f"  ❌ 업로드 실패: {product_data.get('id')} - {response.status_code}")
            print(f"     응답: {response.text[:200]}")
            return False

    except Exception as e:
        print(f"  ❌ 업로드 오류: {product_data.get('id')} - {str(e)}")
        return False

def main():
    """메인 실행 함수"""
    print("=" * 60)
    print("🚀 제품 크롤링 및 Supabase 업로드 시작")
    print("=" * 60)

    # URL 파일 읽기
    url_file = "final_result/소스/데이터/tmp_issue_urls.txt"
    urls = read_urls(url_file)

    print(f"\n📋 총 {len(urls)}개 URL 발견")

    success_count = 0
    fail_count = 0

    for idx, url in enumerate(urls, 1):
        product_id = extract_product_id_from_url(url)

        if not product_id:
            print(f"\n[{idx}/{len(urls)}] ⚠️  ID 추출 실패: {url}")
            fail_count += 1
            continue

        print(f"\n[{idx}/{len(urls)}] 처리 중: 제품 ID {product_id}")

        # 로컬 API에서 제품 정보 가져오기
        product_data = fetch_product_from_local_api(product_id)

        if not product_data:
            print(f"  ⚠️  제품 데이터 없음: {product_id}")
            fail_count += 1
            continue

        # Supabase에 업로드
        if upload_to_supabase(product_data):
            success_count += 1
        else:
            fail_count += 1

        # API 요청 간격 (과부하 방지)
        time.sleep(0.5)

    # 결과 출력
    print("\n" + "=" * 60)
    print("📊 크롤링 완료!")
    print("=" * 60)
    print(f"✅ 성공: {success_count}개")
    print(f"❌ 실패: {fail_count}개")
    print(f"📋 전체: {len(urls)}개")
    print("=" * 60)

if __name__ == "__main__":
    main()
