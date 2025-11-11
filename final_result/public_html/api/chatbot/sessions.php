<?php

declare(strict_types=1);

require_once __DIR__ . '/../utils/cors.php';
api_apply_cors_headers();
require_once __DIR__ . '/../utils/ai.php';
require_once __DIR__ . '/../supabase-config.php';

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'GET') {
    http_response_code(405);
    header('Allow: GET');
    ai_error('method_not_allowed', 'Only GET is supported for sessions listing.', 405);
}

$context = ai_resolve_request_context();
$rate = ai_apply_rate_limit_headers('chatbot:sessions', 10, 60);
ai_abort_if_rate_limited($rate);

$sessions = [
    [
        'session_id' => 'wf11a-sample-1',
        'last_message_at' => date('c', strtotime('-5 minutes')),
        'locale' => 'ko-KR',
        'channel' => 'web',
        'message_count' => 2,
    ],
];

auth_json_response([
    'success' => true,
    'sessions' => $sessions,
    'dev_bypass' => $context['dev_bypass'],
    'environment' => $context['environment'],
    'todo' => 'JOIN ai_chat_sessions with ai_chat_messages to include preview text.',
]);
