<?php

declare(strict_types=1);

require_once __DIR__ . '/auth.php';

function ai_rate_limit_storage_path(): string
{
    $custom = auth_env('WF11_RATE_LIMIT_STORE');
    if ($custom !== null && $custom !== '') {
        return $custom;
    }

    return rtrim(sys_get_temp_dir(), DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . 'wf11_rate_limits.json';
}

function ai_rate_limit_consumer_key(): string
{
    $headers = ai_get_request_headers();

    $devBypass = ai_extract_dev_bypass_token($headers);
    if ($devBypass !== null) {
        return 'dev:' . substr(hash('sha256', $devBypass), 0, 16);
    }

    $bearer = auth_extract_bearer_token();
    if ($bearer !== null) {
        return 'jwt:' . substr(hash('sha256', $bearer), 0, 16);
    }

    $ip = $_SERVER['REMOTE_ADDR'] ?? 'cli';
    return 'ip:' . $ip;
}

function ai_rate_limit_consume(string $bucket, int $limit, int $windowSeconds): array
{
    $now = time();
    $limit = max(1, $limit);
    $windowSeconds = max(1, $windowSeconds);
    $key = $bucket . ':' . ai_rate_limit_consumer_key();
    $path = ai_rate_limit_storage_path();

    $state = [];
    $fp = fopen($path, 'c+');
    if ($fp === false) {
        return [
            'allowed' => true,
            'limit' => $limit,
            'remaining' => $limit - 1,
            'reset_at' => $now + $windowSeconds,
            'retry_after' => $windowSeconds,
        ];
    }

    if (!flock($fp, LOCK_EX)) {
        fclose($fp);
        return [
            'allowed' => true,
            'limit' => $limit,
            'remaining' => $limit - 1,
            'reset_at' => $now + $windowSeconds,
            'retry_after' => $windowSeconds,
        ];
    }

    $contents = stream_get_contents($fp);
    if ($contents !== false && $contents !== '') {
        $decoded = json_decode($contents, true);
        if (is_array($decoded)) {
            $state = $decoded;
        }
    }

    foreach ($state as $storedKey => $entry) {
        if (($entry['reset_at'] ?? 0) <= $now) {
            unset($state[$storedKey]);
        }
    }

    $entry = $state[$key] ?? [
        'count' => 0,
        'reset_at' => $now + $windowSeconds,
    ];

    if (($entry['reset_at'] ?? 0) <= $now) {
        $entry['count'] = 0;
        $entry['reset_at'] = $now + $windowSeconds;
    }

    $entry['count'] = (int) ($entry['count'] ?? 0) + 1;

    $allowed = $entry['count'] <= $limit;
    $remaining = $allowed ? max(0, $limit - $entry['count']) : 0;
    $retryAfter = max(1, $entry['reset_at'] - $now);

    $state[$key] = $entry;

    rewind($fp);
    ftruncate($fp, 0);
    fwrite($fp, json_encode($state, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
    fflush($fp);
    flock($fp, LOCK_UN);
    fclose($fp);

    return [
        'allowed' => $allowed,
        'limit' => $limit,
        'remaining' => $remaining,
        'reset_at' => $entry['reset_at'],
        'retry_after' => $retryAfter,
    ];
}
