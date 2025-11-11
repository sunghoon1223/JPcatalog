<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('log_errors', 1);

ob_start();

try {
    echo json_encode([
        'step' => 1,
        'status' => 'Starting debug...',
        'current_dir' => __DIR__,
        'config_path' => __DIR__ . '/config/supabase-config.php',
        'file_exists' => file_exists(__DIR__ . '/config/supabase-config.php')
    ]);
    
    if (!file_exists(__DIR__ . '/config/supabase-config.php')) {
        throw new Exception('Config file not found');
    }
    
    require_once __DIR__ . '/config/supabase-config.php';
    
    echo json_encode([
        'step' => 2,
        'status' => 'Config loaded',
        'functions' => [
            'supabaseRequest' => function_exists('supabaseRequest'),
            'getProducts' => function_exists('getProducts'),
            'getCategories' => function_exists('getCategories')
        ]
    ]);
    
    // Test simple Supabase call
    $test_endpoint = '/rest/v1/products?select=id,name,price&limit=3';
    $result = supabaseRequest($test_endpoint);
    
    echo json_encode([
        'step' => 3,
        'status' => 'Supabase test successful',
        'result_count' => count($result),
        'sample_product' => isset($result[0]) ? $result[0] : null
    ]);
    
} catch (Exception $e) {
    $output = ob_get_clean();
    echo json_encode([
        'error' => true,
        'message' => $e->getMessage(),
        'file' => $e->getFile(),
        'line' => $e->getLine(),
        'output_buffer' => $output
    ]);
} catch (Error $e) {
    $output = ob_get_clean();
    echo json_encode([
        'fatal_error' => true,
        'message' => $e->getMessage(),
        'file' => $e->getFile(),
        'line' => $e->getLine(),
        'output_buffer' => $output
    ]);
}
?>