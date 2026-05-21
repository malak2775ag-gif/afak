<?php

if (file_exists(__DIR__ . '/../config/ai.php')) {
    require_once __DIR__ . '/../config/ai.php';
}
if (!defined('GROQ_API_KEY')) define('GROQ_API_KEY', getenv('GROQ_API_KEY') ?: '');
if (!defined('AFAK_AI_CHAT_MODEL')) define('AFAK_AI_CHAT_MODEL', getenv('AFAK_AI_CHAT_MODEL') ?: 'llama-3.3-70b-versatile');
if (!defined('AFAK_AI_FALLBACK_MODELS')) {
    define('AFAK_AI_FALLBACK_MODELS', [
        'llama-3.1-8b-instant',
        'mixtral-8x7b-32768'
    ]);
}

require_once __DIR__ . '/../config/db.php';

session_start();

header('Content-Type: application/json');

$message = trim($_POST['message'] ?? '');
$userId = $_SESSION['user_id'] ?? null;

if ($message === '' || !$userId) {
    echo json_encode(['error' => 'Empty message']);
    exit;
}

if (GROQ_API_KEY === '') {
    echo json_encode(['error' => 'AI Service is not configured (Missing API Key)']);
    exit;
}

// --- 1. Manage Conversation Session ---
$sessionId = session_id();
$stmt = $pdo->prepare("SELECT id FROM chatbot_conversations WHERE user_id = ? AND session_id = ? LIMIT 1");
$stmt->execute([$userId, $sessionId]);
$conversation = $stmt->fetch();

if (!$conversation) {
    $stmt = $pdo->prepare("INSERT INTO chatbot_conversations (user_id, session_id) VALUES (?, ?)");
    $stmt->execute([$userId, $sessionId]);
    $conversationId = (int)$pdo->lastInsertId();
} else {
    $conversationId = (int)$conversation['id'];
}

// --- 2. Log User Message ---
$pdo->prepare("INSERT INTO chatbot_messages (conversation_id, role, content) VALUES (?, 'user', ?)")
    ->execute([$conversationId, $message]);

/**
 * Helper function to call the Groq Cloud API
 */
function call_groq_chat(string $model, string $message, string $apiKey, string $systemContext = ''): array {
    // Official Groq API endpoint for chat completions
    $url = "https://api.groq.com/openai/v1/chat/completions";
    
    $messages = [];
    if ($systemContext) {
        $messages[] = ["role" => "system", "content" => $systemContext];
    }
    $messages[] = ["role" => "user", "content" => $message];

    $data = [
        "model" => $model,
        "messages" => $messages,
        "temperature" => 0.7
    ];

    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST           => true,
        CURLOPT_HTTPHEADER     => [
            "Authorization: Bearer " . $apiKey,
            "Content-Type: application/json"
        ],
        CURLOPT_POSTFIELDS     => json_encode($data),
        CURLOPT_TIMEOUT        => 30
    ]);

    // For local environments (WAMP), we disable verification.
    // On Render, the system bundle is used automatically.
    if (strpos($_SERVER['HTTP_HOST'] ?? '', 'localhost') !== false || strpos($_SERVER['HTTP_HOST'] ?? '', '127.0.0.1') !== false) {
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    }

    $response = curl_exec($ch);
    $status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);
    curl_close($ch);

    if ($error) {
        return ['success' => false, 'error' => $error];
    }

    $result = json_decode($response, true);
    
    if ($status !== 200) {
        $errMsg = ($result['error']['message'] ?? "API Error") . " (Status $status) on model: $model";
        return ['success' => false, 'error' => $errMsg];
    }

    $reply = $result['choices'][0]['message']['content'] ?? '';
    return ['success' => true, 'reply' => trim($reply)];
}

// --- 3. Fetch available course names to provide context to the AI ---
$stmtCourses = $pdo->query("SELECT title FROM courses WHERE status = 'published'");
$courseTitles = $stmtCourses->fetchAll(PDO::FETCH_COLUMN);
$systemContext = "You are an intelligent assistant for the AFAK learning platform. The courses currently available on the platform are: " . implode(", ", $courseTitles) . ". Use this information to answer user inquiries.";

// 1. Try the primary model
$outcome = call_groq_chat(AFAK_AI_CHAT_MODEL, $message, GROQ_API_KEY, $systemContext);

// 2. Switch to fallback models if the primary one fails
if (!$outcome['success'] && defined('AFAK_AI_FALLBACK_MODELS')) {
    foreach (AFAK_AI_FALLBACK_MODELS as $fallbackModel) {
        if ($fallbackModel === AFAK_AI_CHAT_MODEL) continue;
        
        $outcome = call_groq_chat($fallbackModel, $message, GROQ_API_KEY, $systemContext);
        if ($outcome['success']) break;
    }
}

// 3. Output the final result to the browser
if (!$outcome['success']) {
    echo json_encode([
        'error' => $outcome['error']
    ]);
} else {
    $reply = $outcome['reply'] ?: 'No response from AI.';
    
    // --- 3. Log Assistant Response ---
    $pdo->prepare("INSERT INTO chatbot_messages (conversation_id, role, content) VALUES (?, 'assistant', ?)")
        ->execute([$conversationId, $reply]);

    echo json_encode(['response' => $reply]);
}