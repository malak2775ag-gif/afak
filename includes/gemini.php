<?php
declare(strict_types=1);


function afak_gemini_api_key(): string
{
    $k = getenv('GEMINI_API_KEY');
    if (is_string($k) && trim($k) !== '') {
        return trim($k);
    }
    return '';
}

/**
 * @param list<array{role: string, content: string}> $messages Ordered transcript (user / assistant).
 */
function afak_gemini_generate(array $messages, string $apiKey): ?string
{
    if ($apiKey === '' || $messages === []) {
        return null;
    }

    $contents = [];
    foreach ($messages as $m) {
        $role = ($m['role'] ?? '') === 'assistant' ? 'model' : 'user';
        $text = trim((string)($m['content'] ?? ''));
        if ($text === '') {
            continue;
        }
        $contents[] = ['role' => $role, 'parts' => [['text' => $text]]];
    }

    if ($contents === []) {
        return null;
    }

    $model = 'gemini-flash-latest';
    $url = 'https://generativelanguage.googleapis.com/v1beta/models/' . $model . ':generateContent';

    $payload = [
        'systemInstruction' => [
            'parts' => [[
                'text' => 'You are the AFAK Learning Platform assistant (students, instructors, admins). '
                    . 'Help with courses, enrollment, quizzes, certificates, progress, and payments in short, '
                    . 'friendly plain text. Do not use HTML. If you are unsure about a specific policy, suggest '
                    . 'the Courses page, Dashboard, or Profile.',
            ]],
        ],
        'contents' => $contents,
    ];

    $body = json_encode($payload, JSON_UNESCAPED_UNICODE);
    if ($body === false) {
        return null;
    }

    $headers = [
        'Content-Type: application/json',
        'X-goog-api-key: ' . $apiKey,
    ];

    $raw = afak_http_post_json($url, $body, $headers);
    if ($raw === null || $raw === '') {
        return null;
    }

    $data = json_decode($raw, true);
    if (!is_array($data)) {
        return null;
    }

    $text = $data['candidates'][0]['content']['parts'][0]['text'] ?? null;
    if (!is_string($text)) {
        return null;
    }
    $text = trim($text);
    return $text === '' ? null : $text;
}

/**
 * @param list<string> $headers Full "Name: value" header lines (e.g. Content-Type, X-goog-api-key).
 */
function afak_http_post_json(string $url, string $body, array $headers, int $timeout = 45): ?string
{
    if (function_exists('curl_init')) {
        $crl_setopt_array($ch, [
            CURLOPT_POST           => true,
            CURLOPT_HTTPHEADER     => $headers,
            CURLOPT_POSTFIELDS     => $body,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT        => $timeout,
        ]);

        if ($caPath && file_exists($caPath)) {
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
            curl_setopt($ch, CURLOPT_CAINFO, $caPath);
        } else {
            // Fallback for local environments without a CA bundle
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        }

        $raw = curl_exec($ch);
        $code = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        if ($raw === false || $code < 200 || $code >= 300) {
            return null;
        }
        return $raw;
    }

    $headerBlock = implode("\r\n", $headers);
    $ctx = stream_context_create([
        'http' => [
            'method'  => 'POST',
            'header'  => $headerBlock . "\r\n",
            'content' => $body,
            'timeout' => $timeout,
        ],
    ]);
    $raw = @file_get_contents($url, false, $ctx);
    return $raw === false ? null : $raw;
}
