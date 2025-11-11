<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

echo json_encode([
    'status' => 'API Debug Test',
    'timestamp' => date('Y-m-d H:i:s'),
    'server' => $_SERVER['SERVER_NAME'],
    'php_version' => phpversion(),
    'files_exist' => [
        'supabase-config' => file_exists(__DIR__ . '/config/supabase-config.php'),
        'database-config' => file_exists(__DIR__ . '/config/database.php')
    ],
    'get_params' => $_GET
]);
?>