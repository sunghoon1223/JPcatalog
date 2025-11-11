<?php

declare(strict_types=1);

require_once __DIR__ . '/../utils/cors.php';
api_apply_cors_headers();
require_once __DIR__ . '/../utils/ai.php';
require_once __DIR__ . '/../supabase-config.php';

$method = strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET');
$context = ai_resolve_request_context();
$rate = ai_apply_rate_limit_headers('chatbot:history', 10, 60);
ai_abort_if_rate_limited($rate);

if ($method === 'GET') {
    $sessionId = $_GET['session_id'] ?? null;
    if ($sessionId === null || trim((string) $sessionId) === '') {
        ai_error('validation_error', 'session_id is required for history lookups.', 400);
    }

    $limit = isset($_GET['limit']) ? (int) $_GET['limit'] : 20;
    $limit = max(1, min(50, $limit));

    $messages = [
        [
            'role' => 'user',
            'content' => '안녕하세요, 추천 부탁해요.',
            'created_at' => date('c', strtotime('-2 minutes')),
        ],
        [
            'role' => 'assistant',
            'content' => 'WF-11A skeleton 응답입니다. 실제 모델과 연결하세요.',
            'created_at' => date('c', strtotime('-1 minute')),
        ],
    ];

    auth_json_response([
        'success' => true,
        'session_id' => $sessionId,
        'messages' => array_slice($messages, 0, $limit),
        'dev_bypass' => $context['dev_bypass'],
        'environment' => $context['environment'],
        'meta' => [
            'limit' => $limit,
            'ordered' => 'asc',
        ],
    ]);
}

if ($method === 'POST') {
    $body = ai_read_json_body();
    $sessionId = $body['session_id'] ?? null;
    $messages = $body['messages'] ?? [];

    if ($sessionId === null || trim((string) $sessionId) === '') {
        ai_error('validation_error', 'session_id is required to persist history.', 400);
    }

    if (!is_array($messages)) {
        ai_error('validation_error', 'messages must be an array.', 400);
    }

    auth_json_response([
        'success' => true,
        'session_id' => $sessionId,
        'stored_count' => count($messages),
        'dev_bypass' => $context['dev_bypass'],
        'environment' => $context['environment'],
        'todo' => 'Implement INSERT into ai_chat_messages and optionally generate embeddings per message.',
    ]);
}

http_response_code(405);
header('Allow: GET, POST');
ai_error('method_not_allowed', 'Supported methods: GET, POST.', 405);
