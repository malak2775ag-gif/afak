<?php
require_once __DIR__ . '/config/ai.php';
if (file_exists(__DIR__ . '/config/ai.php')) {
    require_once __DIR__ . '/config/ai.php';
}
if (!defined('GROQ_API_KEY')) define('GROQ_API_KEY', getenv('GROQ_API_KEY') ?: '');
if (!defined('AFAK_AI_CHAT_MODEL')) define('AFAK_AI_CHAT_MODEL', getenv('AFAK_AI_CHAT_MODEL') ?: 'llama-3.3-70b-versatile');

require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/auth.php';

session_start();
requireLogin();

$pageTitle = "AI Chatbot";
$pageStylesheets = [
    'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.1.2/css/all.min.css'
];

require_once __DIR__ . '/includes/header.php';
?>

    <style>
        .chat-container {
            max-width: 800px;
            margin: 0 auto 30px;
            background: white;
            border-radius: 15px;
            overflow: hidden;
            display: flex;
            flex-direction: column;
            height: 500px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
        }

        .header {
            background: #2a5298;
            color: white;
            padding: 15px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .chat-box {
            flex: 1;
            padding: 15px;
            overflow-y: auto;
            display: flex;
            flex-direction: column;
            gap: 10px;
            background: #f9fbff;
        }

        .msg {
            padding: 10px 15px;
            border-radius: 15px;
            max-width: 85%;
            line-height: 1.4;
            word-wrap: break-word;
            position: relative;
            font-size: 0.95rem;
            box-shadow: 0 2px 5px rgba(0,0,0,0.05);
        }

        .user {
            background: #2a5298;
            color: white;
            align-self: flex-end;
        }

        .bot {
            background: white;
            border: 1px solid #ddd;
            align-self: flex-start;
        }

        .input-area {
            display: flex;
            padding: 10px;
            border-top: 1px solid #eee;
            background: #fff;
        }

        input {
            flex: 1;
            padding: 10px;
            border-radius: 25px;
            border: 1px solid #ddd;
            outline: none;
        }

        input:disabled, button:disabled {
            background: #f0f0f0;
            cursor: not-allowed;
        }

        button {
            margin-left: 10px;
            background: #2a5298;
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 25px;
            cursor: pointer;
            transition: background 0.2s;
        }

        button:hover:not(:disabled) {
            background: #1e3c72;
        }

        /* --- New Typing Indicator Animation --- */
        .typing-indicator {
            display: flex;
            gap: 4px;
            padding: 5px 10px;
            align-items: center;
        }

        .typing-indicator span {
            width: 8px;
            height: 8px;
            background: #a0a0a0;
            border-radius: 50%;
            animation: bounce 1.3s infinite both;
        }

        .typing-indicator span:nth-child(2) { animation-delay: 0.15s; }
        .typing-indicator span:nth-child(3) { animation-delay: 0.3s; }

        @keyframes bounce {
            0%, 80%, 100% { transform: scale(0); }
            40% { transform: scale(1); }
        }
    </style>
<div class="chat-container">

    <div class="header">
        <i class="fas fa-robot"></i>
        <h3>AI Assistant</h3>
    </div>

    <div id="chat" class="chat-box">
        <div class="msg bot">Hello &#x1f44b; How can I help you?</div>
    </div>

    <div class="input-area">
        <!-- Added onkeydown event to capture the Enter key -->
        <input id="input" placeholder="Type your message..." onkeydown="checkEnter(event)">
        <button id="sendBtn" onclick="send()">Send</button>
    </div>

</div>

<script>
function escapeHtml(text) {
    const div = document.createElement("div");
    div.textContent = text;
    return div.innerHTML;
}

function formatMessage(text) {
    // Escape HTML first for security, then replace newlines with <br>
    return escapeHtml(text).replace(/\n/g, '<br>');
}

// Automatically triggers send() when user hits Enter
function checkEnter(event) {
    if (event.key === "Enter") {
        send();
    }
}

function send() {
    const input = document.getElementById("input");
    const sendBtn = document.getElementById("sendBtn");
    const chat = document.getElementById("chat");

    const msg = input.value.trim();
    if (!msg) return;

    // 1. Append user message to UI
    chat.innerHTML += `<div class="msg user">${formatMessage(msg)}</div>`;
    input.value = "";
    
    // 2. Disable UI controls to prevent double-submits
    input.disabled = true;
    sendBtn.disabled = true;

    // 3. Append typing indicator
    const typingId = "typing-" + Date.now();
    chat.innerHTML += `
        <div id="${typingId}" class="msg bot">
            <div class="typing-indicator">
                <span></span><span></span><span></span>
            </div>
        </div>`;
    
    chat.scrollTop = chat.scrollHeight;

    // 4. Send API request
    fetch("includes/chat.php", {
        method: "POST",
        headers: {
            "Content-Type": "application/x-www-form-urlencoded"
        },
        body: "message=" + encodeURIComponent(msg)
    })
    .then(res => res.json())
    .then(data => {
        // Remove the typing indicator
        document.getElementById(typingId)?.remove();

        // 5. Append AI response or Error
        if (data.response) {
            chat.innerHTML += `<div class="msg bot">${formatMessage(data.response)}</div>`;
        } else {
            chat.innerHTML += `<div class="msg bot" style="color: red;">Error: ${escapeHtml(data.error)}</div>`;
        }
    })
    .catch(err => {
        document.getElementById(typingId)?.remove();
        chat.innerHTML += `<div class="msg bot" style="color: red;">Server error: Unable to reach the assistant.</div>`;
    })
    .finally(() => {
        // 6. Re-enable UI elements and scroll to bottom
        input.disabled = false;
        sendBtn.disabled = false;
        input.focus(); // Bring cursor focus back to input box
        chat.scrollTop = chat.scrollHeight;
    });
}
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>