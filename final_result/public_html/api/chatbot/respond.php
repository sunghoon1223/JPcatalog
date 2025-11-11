<?php

declare(strict_types=1);

require_once __DIR__ . '/../utils/cors.php';
api_apply_cors_headers();
require_once __DIR__ . '/../config/gemini.php';
require_once __DIR__ . '/../utils/catalogue.php';
require_once __DIR__ . '/../utils/ai.php';

header('Content-Type: application/json; charset=utf-8');
$rate = ai_apply_rate_limit_headers('chatbot:respond', 30, 60);
ai_abort_if_rate_limited($rate);

/**
 * Sends a JSON response and terminates execution.
 *
 * @param int $status
 * @param array<string, mixed> $payload
 */
function chatbot_json_response(int $status, array $payload): void
{
    http_response_code($status);
    echo json_encode($payload, JSON_UNESCAPED_UNICODE);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    chatbot_json_response(405, [
        'success' => false,
        'error' => 'Method not allowed. Use POST.',
    ]);
}

$rawInput = file_get_contents('php://input');
$decodedInput = json_decode((string) $rawInput, true);

if (!is_array($decodedInput)) {
    chatbot_json_response(400, [
        'success' => false,
        'error' => 'Invalid JSON payload.',
    ]);
}

$question = trim((string) ($decodedInput['question'] ?? ''));
if ($question === '') {
    chatbot_json_response(400, [
        'success' => false,
        'error' => '질문 내용을 입력해주세요.',
    ]);
}

$contextPrompt = trim((string) ($decodedInput['context_prompt'] ?? ''));

$messagesInput = $decodedInput['messages'] ?? [];
$conversationHistory = [];
if (is_array($messagesInput)) {
    foreach ($messagesInput as $message) {
        if (!is_array($message)) {
            continue;
        }
        $role = isset($message['role']) ? (string) $message['role'] : '';
        $content = isset($message['content']) ? trim((string) $message['content']) : '';
        if ($content === '') {
            continue;
        }
        if ($role !== 'user' && $role !== 'assistant') {
            continue;
        }
        $conversationHistory[] = [
            'role' => $role,
            'content' => $content,
        ];
    }
}

$currentProduct = null;
if (isset($decodedInput['current_product']) && is_array($decodedInput['current_product'])) {
    $currentProduct = [
        'name' => isset($decodedInput['current_product']['name']) ? (string) $decodedInput['current_product']['name'] : null,
        'slug' => isset($decodedInput['current_product']['slug']) ? (string) $decodedInput['current_product']['slug'] : null,
        'category' => isset($decodedInput['current_product']['category']) ? (string) $decodedInput['current_product']['category'] : null,
        'description' => isset($decodedInput['current_product']['description']) ? (string) $decodedInput['current_product']['description'] : null,
        'price' => isset($decodedInput['current_product']['price']) ? (string) $decodedInput['current_product']['price'] : null,
    ];
}

$productDataset = chatbot_load_product_dataset();
$rankedProducts = chatbot_rank_products($question, $productDataset, $currentProduct, 6);

$historyForGemini = [];
if (!empty($conversationHistory)) {
    $historyCount = count($conversationHistory);
    $startIndex = max(0, $historyCount - 6);
    for ($i = $startIndex; $i < $historyCount; $i++) {
        $entry = $conversationHistory[$i];
        $historyForGemini[] = [
            'role' => $entry['role'] === 'assistant' ? 'model' : 'user',
            'parts' => [
                ['text' => chatbot_truncate($entry['content'], 800)],
            ],
        ];
    }
}

$promptSections = [];
$promptSections[] = <<<TXT
당신은 산업용 캐스터 제조사 JPCaster의 AI 고객 상담 챗봇입니다. 모든 답변은 한국어로 작성하고, 전문성과 친절함을 함께 유지하세요. 고객이 제시한 요구사항을 분석한 뒤, 조건에 맞는 제품을 추천하거나 필요 정보를 안내합니다. 제시된 제품 후보를 우선 참고하되, 조건에 적합하지 않으면 추가 정보를 요청하세요.
TXT;

if ($contextPrompt !== '') {
    $promptSections[] = "[사이트 기본 안내]\n" . chatbot_truncate($contextPrompt, 1200);
}

if ($currentProduct && ($currentProduct['name'] ?? null)) {
    $currentLines = [
        '제품명: ' . $currentProduct['name'],
    ];
    if (!empty($currentProduct['category'])) {
        $currentLines[] = '카테고리: ' . $currentProduct['category'];
    }
    if (!empty($currentProduct['description'])) {
        $currentLines[] = '설명: ' . chatbot_truncate($currentProduct['description'], 200);
    }
    if (!empty($currentProduct['price'])) {
        $currentLines[] = '판매가: ' . $currentProduct['price'];
    }
    $promptSections[] = "[현재 고객이 보고 있는 제품]\n" . implode("\n", $currentLines);
}

if (!empty($rankedProducts)) {
    $productLines = [];
    foreach ($rankedProducts as $index => $product) {
        $lineParts = [];
        $lineParts[] = ($index + 1) . ". " . $product['name'];
        if (!empty($product['category'])) {
            $lineParts[] = '(' . $product['category'] . ')';
        }
        if (!empty($product['url'])) {
            $lineParts[] = $product['url'];
        }
        $productLines[] = implode(' ', $lineParts);

        if (!empty($product['features'])) {
            $productLines[] = '   주요 특징: ' . implode(', ', array_slice($product['features'], 0, 3));
        }
        if (!empty($product['description'])) {
            $productLines[] = '   요약: ' . chatbot_truncate($product['description'], 180);
        }
    }
    $promptSections[] = "[추천 후보 제품 목록]\n" . implode("\n", $productLines);
} else {
    $promptSections[] = "[추천 후보 제품 목록]\n- 현재 데이터셋에서 관련 후보를 찾지 못했습니다. 고객에게 추가 정보를 요청하세요.";
}

$promptSections[] = <<<TXT
[응답 지침]
- 고객의 질문 또는 요구조건을 먼저 재확인하고, 조건에 적합한 제품을 1~3개 제안하세요.
- 각 제품에는 핵심 사양, 활용 분야, 장점(예: 하중, 휠 직경, 재질)을 짧게 첨부하세요.
- 제품 URL이 있다면 `/products/{slug}` 형태로 안내하세요.
- 조건과 맞지 않는다면 더 필요한 정보를 정중하게 요청하세요.
- 안전, 납기, 커스터마이징 문의 시 대응 방안을 안내하세요.
TXT;

$promptSections[] = "[고객 질문]\n" . $question;

$finalPrompt = implode("\n\n", array_filter($promptSections));

$contents = array_merge($historyForGemini, [
    [
        'role' => 'user',
        'parts' => [
            ['text' => $finalPrompt],
        ],
    ],
]);

try {
    $generationConfig = [
        'temperature' => 0.55,
        'topP' => 0.9,
        'topK' => 32,
        'maxOutputTokens' => 768,
    ];

    $response = gemini_generate_content($contents, [
        'generationConfig' => $generationConfig,
    ]);

    $answerText = chatbot_extract_answer($response);
    if ($answerText === null) {
        throw new RuntimeException('Gemini 응답을 파싱하지 못했습니다.');
    }

    chatbot_json_response(200, [
        'success' => true,
        'answer' => $answerText,
        'model' => gemini_get_model(),
        'context_products' => $rankedProducts,
    ]);
} catch (Throwable $e) {
    $fallback = [];
    $fallback[] = '⚠️ 현재 AI 서비스 연결이 원활하지 않아 로컬 데이터 기준으로 추천을 제공합니다.';

    if (!empty($rankedProducts)) {
        $fallback[] = '';
        $fallback[] = '추천 후보:';
        foreach (array_slice($rankedProducts, 0, 3) as $index => $product) {
            $line = ($index + 1) . '. ' . $product['name'];
            if (!empty($product['category'])) {
                $line .= ' (' . $product['category'] . ')';
            }
            if (!empty($product['url'])) {
                $line .= ' - ' . $product['url'];
            }
            $fallback[] = $line;

            if (!empty($product['features'])) {
                $fallback[] = '   주요 특징: ' . implode(', ', array_slice($product['features'], 0, 2));
            }
            if (!empty($product['description'])) {
                $fallback[] = '   요약: ' . chatbot_truncate($product['description'], 160);
            }
        }
    } else {
        $fallback[] = '';
        $fallback[] = '현재 로컬 데이터에서도 일치하는 제품을 찾지 못했습니다. 필요한 사양(하중, 휠 직경, 재질 등)을 알려주시면 더 정확히 찾아드릴 수 있습니다.';
    }

    $fallback[] = '';
    $fallback[] = '자세한 상담이 필요하시면 고객센터(1588-1234)로 문의해주세요.';

    chatbot_json_response(200, [
        'success' => true,
        'answer' => implode("\n", $fallback),
        'model' => 'fallback-local',
        'context_products' => $rankedProducts,
    ]);
}

/**
 * Extracts the first candidate text from Gemini's response.
 *
 * @param array<string, mixed> $response
 */
function chatbot_extract_answer(array $response): ?string
{
    if (!isset($response['candidates']) || !is_array($response['candidates'])) {
        return null;
    }

    foreach ($response['candidates'] as $candidate) {
        if (!is_array($candidate)) {
            continue;
        }
        if (!isset($candidate['content']['parts']) || !is_array($candidate['content']['parts'])) {
            continue;
        }
        foreach ($candidate['content']['parts'] as $part) {
            if (is_array($part) && isset($part['text']) && is_string($part['text'])) {
                $text = trim($part['text']);
                if ($text !== '') {
                    return $text;
                }
            }
        }
    }

    return null;
}

/**
 * Loads the product dataset from the Supabase snapshot (JSON) or falls back to the crawling snapshot.
 *
 * @return array<int, array<string, mixed>>
 */
function chatbot_load_product_dataset(): array
{
    $listPath = __DIR__ . '/../products/list.json';
    if (is_file($listPath)) {
        $json = file_get_contents($listPath);
        if ($json !== false) {
            $decoded = json_decode($json, true);
            if (is_array($decoded) && isset($decoded['data']) && is_array($decoded['data'])) {
                return $decoded['data'];
            }
        }
    }

    $fallback = catalogue_load_local_products();
    return is_array($fallback) ? $fallback : [];
}

/**
 * Scores and selects the most relevant products.
 *
 * @param array<int, array<string, mixed>> $products
 * @param array<string, mixed>|null $currentProduct
 * @return array<int, array<string, mixed>>
 */
function chatbot_rank_products(string $question, array $products, ?array $currentProduct, int $limit = 5): array
{
    $keywords = chatbot_extract_keywords($question);
    $boostKeywords = $keywords;

    if ($currentProduct) {
        if (!empty($currentProduct['name'])) {
            $boostKeywords = array_merge($boostKeywords, chatbot_extract_keywords((string) $currentProduct['name']));
        }
        if (!empty($currentProduct['category'])) {
            $boostKeywords = array_merge($boostKeywords, chatbot_extract_keywords((string) $currentProduct['category']));
        }
    }

    $boostKeywords = array_values(array_unique($boostKeywords));

    $scored = [];
    foreach ($products as $product) {
        if (!is_array($product)) {
            continue;
        }

        $name = chatbot_normalize_string($product['name'] ?? $product['english_name'] ?? '');
        if ($name === '') {
            continue;
        }

        $slug = isset($product['slug']) ? (string) $product['slug'] : null;
        $description = chatbot_normalize_string(
            $product['description']
                ?? $product['summary']
                ?? $product['short_description']
                ?? ''
        );
        $category = chatbot_resolve_category($product);
        $url = chatbot_resolve_product_url($product, $slug);

        $featureStrings = chatbot_collect_feature_strings($product);

        $haystack = chatbot_lower(implode(' ', array_filter([
            $name,
            $description,
            $category,
            implode(' ', $featureStrings),
            chatbot_normalize_string($product['sku'] ?? $product['model_number'] ?? ''),
            chatbot_normalize_string($product['tags'] ?? ''),
        ])));

        $score = chatbot_score_text($haystack, $boostKeywords);

        if ($currentProduct && $slug !== null && isset($currentProduct['slug']) && $slug === $currentProduct['slug']) {
            $score += 5.0;
        }

        if ($score <= 0) {
            continue;
        }

        $scored[] = [
            'name' => $name,
            'slug' => $slug,
            'url' => $url,
            'category' => $category,
            'description' => $description,
            'features' => array_slice($featureStrings, 0, 5),
            'score' => round($score, 4),
        ];
    }

    usort($scored, static function (array $a, array $b): int {
        $scoreCompare = $b['score'] <=> $a['score'];
        if ($scoreCompare !== 0) {
            return $scoreCompare;
        }
        return strcmp($a['name'], $b['name']);
    });

    return array_slice($scored, 0, $limit);
}

/**
 * Resolves the readable category name.
 *
 * @param array<string, mixed> $product
 */
function chatbot_resolve_category(array $product): ?string
{
    if (isset($product['category']) && is_array($product['category']) && isset($product['category']['name'])) {
        return chatbot_normalize_string($product['category']['name']);
    }

    if (isset($product['category_name'])) {
        return chatbot_normalize_string($product['category_name']);
    }

    if (isset($product['category_id']) && isset(CATALOGUE_CATEGORY_MAP[$product['category_id']])) {
        return (string) CATALOGUE_CATEGORY_MAP[$product['category_id']]['name'];
    }

    if (isset($product['category_breadcrumb']) && is_array($product['category_breadcrumb']) && isset($product['category_breadcrumb'][0])) {
        return chatbot_normalize_string($product['category_breadcrumb'][0]);
    }

    return null;
}

/**
 * Resolves the preferred product URL.
 *
 * @param array<string, mixed> $product
 */
function chatbot_resolve_product_url(array $product, ?string $slug): ?string
{
    if ($slug) {
        return '/products/' . ltrim($slug, '/');
    }

    if (!empty($product['source_url']) && is_string($product['source_url'])) {
        return trim($product['source_url']);
    }

    if (!empty($product['url']) && is_string($product['url'])) {
        return trim($product['url']);
    }

    return null;
}

/**
 * Flattens feature/spec arrays into readable strings.
 *
 * @param array<string, mixed> $product
 * @return array<int, string>
 */
function chatbot_collect_feature_strings(array $product): array
{
    $featureSources = ['features', 'technical_specs', 'specifications', 'performance'];
    $collected = [];

    foreach ($featureSources as $key) {
        if (!isset($product[$key])) {
            continue;
        }

        $value = $product[$key];
        if (is_array($value)) {
            foreach ($value as $k => $v) {
                if (is_array($v)) {
                    $v = implode(' ', array_map('strval', $v));
                }
                if (!is_string($v)) {
                    continue;
                }
                $v = chatbot_normalize_string($v);
                if ($v === '') {
                    continue;
                }

                if (is_string($k) && $k !== '' && !is_numeric($k)) {
                    $collected[] = sprintf('%s: %s', $k, $v);
                } else {
                    $collected[] = $v;
                }
            }
        } elseif (is_string($value)) {
            $normalized = chatbot_normalize_string($value);
            if ($normalized !== '') {
                $collected[] = $normalized;
            }
        }
    }

    return array_values(array_unique($collected));
}

/**
 * Extracts keywords from user queries.
 *
 * @return array<int, string>
 */
function chatbot_extract_keywords(string $text): array
{
    $normalized = chatbot_lower($text);
    $tokens = preg_split('/[^\\p{L}\\p{N}\\+\\.\\-]+/u', $normalized, -1, PREG_SPLIT_NO_EMPTY);
    if (!is_array($tokens)) {
        return [];
    }

    return array_values(array_unique(array_filter($tokens, static function (string $token): bool {
        return chatbot_strlen($token) >= 2;
    })));
}

/**
 * Calculates a relevance score based on keyword matches.
 *
 * @param array<int, string> $keywords
 */
function chatbot_score_text(string $haystack, array $keywords): float
{
    $score = 0.0;

    foreach ($keywords as $keyword) {
        if ($keyword === '') {
            continue;
        }

        if (strpos($haystack, $keyword) !== false) {
            $score += preg_match('/\\d/', $keyword) ? 2.0 : 1.0;
        }
    }

    return $score;
}

/**
 * Normalizes arbitrary scalar values to trimmed strings.
 */
function chatbot_normalize_string($value): string
{
    if (is_string($value)) {
        return trim($value);
    }

    if (is_numeric($value)) {
        return trim((string) $value);
    }

    return '';
}

/**
 * Truncates long strings while preserving multi-byte characters.
 */
function chatbot_truncate(string $text, int $limit): string
{
    if (chatbot_strlen($text) <= $limit) {
        return $text;
    }

    return chatbot_substr($text, 0, $limit - 1) . '…';
}

/**
 * Multibyte-safe lowercase helper.
 */
function chatbot_lower(string $text): string
{
    static $hasMb = null;
    if ($hasMb === null) {
        $hasMb = function_exists('mb_strtolower');
    }

    return $hasMb ? mb_strtolower($text) : strtolower($text);
}

/**
 * Multibyte-safe length helper.
 */
function chatbot_strlen(string $text): int
{
    static $hasMb = null;
    if ($hasMb === null) {
        $hasMb = function_exists('mb_strlen');
    }

    return $hasMb ? mb_strlen($text) : strlen($text);
}

/**
 * Multibyte-safe substring helper.
 */
function chatbot_substr(string $text, int $start, ?int $length = null): string
{
    static $hasMb = null;
    if ($hasMb === null) {
        $hasMb = function_exists('mb_substr');
    }

    if ($hasMb) {
        return $length === null ? mb_substr($text, $start) : mb_substr($text, $start, $length);
    }

    return $length === null ? substr($text, $start) : substr($text, $start, $length);
}
