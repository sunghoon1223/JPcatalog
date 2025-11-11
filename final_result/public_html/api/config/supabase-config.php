<?php
/**
 * Supabase Configuration - 임시 사용
 * 호스팅거 MySQL 준비될 때까지 Supabase 유지
 */

// Supabase 설정
$supabase_url = 'https://bjqadhzkoxdwyfsglrvq.supabase.co';
$supabase_key = 'eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9.eyJpc3MiOiJzdXBhYmFzZSIsInJlZiI6ImJqcWFkaHprb3hkd3lmc2dscnZxIiwicm9sZSI6ImFub24iLCJpYXQiOjE3MjIwMDk4NzQsImV4cCI6MjAzNzU4NTg3NH0.VB5rRXKa6aGjZZHJO-vQJFR5-_vJe3pGCtxP7RG3Wok';

// API 요청 함수
function supabaseRequest($endpoint, $method = 'GET', $data = null) {
    global $supabase_url, $supabase_key;
    
    $url = $supabase_url . $endpoint;
    
    $headers = [
        'Content-Type: application/json',
        'Authorization: Bearer ' . $supabase_key,
        'apikey: ' . $supabase_key,
        'Prefer: return=representation'
    ];
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
    
    if ($data && ($method === 'POST' || $method === 'PUT' || $method === 'PATCH')) {
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    }
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    if ($httpCode >= 400) {
        throw new Exception("Supabase API Error: HTTP $httpCode - $response");
    }
    
    return json_decode($response, true);
}

// 제품 목록 가져오기
function getProducts($category_id = null) {
    $endpoint = '/rest/v1/products?select=*';
    
    if ($category_id) {
        $endpoint .= '&category_id=eq.' . urlencode($category_id);
    }
    
    $endpoint .= '&is_published=eq.true&order=created_at.desc';
    
    return supabaseRequest($endpoint);
}

// 카테고리 목록 가져오기
function getCategories() {
    $endpoint = '/rest/v1/categories?select=*&is_active=eq.true&order=sort_order';
    return supabaseRequest($endpoint);
}

// 단일 제품 가져오기
function getProduct($id) {
    $endpoint = '/rest/v1/products?select=*&id=eq.' . urlencode($id) . '&is_published=eq.true';
    $products = supabaseRequest($endpoint);
    return !empty($products) ? $products[0] : null;
}

?>