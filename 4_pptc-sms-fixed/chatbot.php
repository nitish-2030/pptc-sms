<?php
// ============================================================
// chatbot.php — PPTC Admin AI Assistant (Feature 4)
// Rule-based chatbot with insights access + activity logging
// ============================================================
require_once 'config/db.php';
require_once 'config/activity_helper.php';
$pageTitle = 'AI Assistant';
$baseUrl   = '';

// Log that admin viewed the chatbot
log_activity($conn, 'admin', 'Viewed AI Chatbot', 'Admin opened the chatbot assistant page');

include 'includes/header.php';
?>

<style>
/* ── Page Layout ─────────────────────────────────────── */
.chatbot-page {
    max-width: 820px;
    margin: 0 auto;
    padding: 1.5rem 1rem 3rem;
}

/* ── Hero Header ─────────────────────────────────────── */
.cb-hero {
    display: flex;
    align-items: center;
    gap: 1.25rem;
    margin-bottom: 1.5rem;
    padding: 1.4rem 1.75rem;
    background: linear-gradient(135deg, #1a0000 0%, #5C0000 50%, #8B0000 100%);
    border-radius: 16px;
    box-shadow: 0 8px 32px rgba(139,0,0,0.25);
    position: relative;
    overflow: hidden;
}
.cb-hero::before {
    content: '';
    position: absolute;
    top: -40px; right: -40px;
    width: 180px; height: 180px;
    border-radius: 50%;
    background: rgba(201,168,76,0.06);
    pointer-events: none;
}
.cb-orb {
    width: 52px; height: 52px;
    border-radius: 50%;
    background: radial-gradient(circle at 35% 35%, #C9A84C, #8B0000);
    box-shadow: 0 0 0 3px rgba(201,168,76,0.25), 0 4px 20px rgba(0,0,0,0.4);
    flex-shrink: 0;
    animation: orbPulse 3s ease-in-out infinite;
}
@keyframes orbPulse {
    0%,100% { box-shadow: 0 0 0 3px rgba(201,168,76,0.25), 0 4px 20px rgba(0,0,0,0.4); }
    50%      { box-shadow: 0 0 0 8px rgba(201,168,76,0.12), 0 4px 28px rgba(201,168,76,0.3); }
}
.cb-hero-text h1 {
    font-family: 'Cinzel', serif;
    font-size: 1.15rem;
    color: #E8C76A;
    margin: 0 0 0.2rem;
    letter-spacing: 0.04em;
}
.cb-hero-text p {
    font-size: 0.78rem;
    color: rgba(255,255,255,0.55);
    margin: 0;
}
.cb-hero-status {
    margin-left: auto;
    display: flex;
    align-items: center;
    gap: 0.4rem;
    font-size: 0.72rem;
    color: rgba(255,255,255,0.5);
    font-weight: 700;
    letter-spacing: 0.06em;
}
.cb-status-dot {
    width: 8px; height: 8px;
    border-radius: 50%;
    background: #22c55e;
    animation: blink 1.5s ease-in-out infinite;
}
@keyframes blink { 0%,100%{opacity:1;} 50%{opacity:0.3;} }

/* ── Chat Window ─────────────────────────────────────── */
.cb-window {
    background: #fff;
    border: 1px solid rgba(139,0,0,0.1);
    border-radius: 16px;
    box-shadow: 0 4px 24px rgba(139,0,0,0.07);
    overflow: hidden;
    display: flex;
    flex-direction: column;
    height: 520px;
}

/* ── Messages Area ───────────────────────────────────── */
.cb-messages {
    flex: 1;
    overflow-y: auto;
    padding: 1.25rem 1.25rem 0.5rem;
    display: flex;
    flex-direction: column;
    gap: 0.9rem;
    scroll-behavior: smooth;
}
.cb-messages::-webkit-scrollbar { width: 5px; }
.cb-messages::-webkit-scrollbar-track { background: #fafafa; }
.cb-messages::-webkit-scrollbar-thumb { background: #e0cece; border-radius: 10px; }

/* ── Message Bubbles ─────────────────────────────────── */
.cb-msg {
    display: flex;
    gap: 0.6rem;
    align-items: flex-end;
    animation: fadeUp 0.3s ease forwards;
    opacity: 0;
}
@keyframes fadeUp {
    from { opacity: 0; transform: translateY(10px); }
    to   { opacity: 1; transform: translateY(0); }
}
.cb-msg.user  { flex-direction: row-reverse; }
.cb-msg.bot   { flex-direction: row; }

.cb-avatar {
    width: 30px; height: 30px;
    border-radius: 50%;
    flex-shrink: 0;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 0.8rem;
    font-weight: 700;
}
.cb-msg.user .cb-avatar {
    background: linear-gradient(135deg, #8B0000, #5C0000);
    color: #E8C76A;
}
.cb-msg.bot .cb-avatar {
    background: linear-gradient(135deg, #C9A84C, #8B0000);
    color: #fff;
}

.cb-bubble {
    max-width: 78%;
    padding: 0.75rem 1rem;
    border-radius: 14px;
    font-size: 0.855rem;
    line-height: 1.65;
    white-space: pre-wrap;
    word-break: break-word;
}
.cb-msg.user .cb-bubble {
    background: linear-gradient(135deg, #5C0000, #8B0000);
    color: #fff;
    border-bottom-right-radius: 4px;
}
.cb-msg.bot .cb-bubble {
    background: #fafafa;
    color: #1a0a0a;
    border: 1px solid #f0e8e8;
    border-bottom-left-radius: 4px;
}
.cb-time {
    font-size: 0.62rem;
    color: #bbb;
    text-align: right;
    margin-top: 0.25rem;
    padding: 0 0.35rem;
}
.cb-msg.user .cb-time { text-align: right; }
.cb-msg.bot  .cb-time { text-align: left; }

/* ── Typing Indicator ────────────────────────────────── */
.cb-typing .cb-bubble {
    padding: 0.65rem 1rem;
}
.typing-dots {
    display: flex;
    gap: 4px;
    align-items: center;
    height: 16px;
}
.typing-dots span {
    width: 6px; height: 6px;
    border-radius: 50%;
    background: #C9A84C;
    animation: typingDot 1.2s ease-in-out infinite;
}
.typing-dots span:nth-child(2) { animation-delay: 0.2s; }
.typing-dots span:nth-child(3) { animation-delay: 0.4s; }
@keyframes typingDot {
    0%,60%,100% { transform: translateY(0); opacity: 0.4; }
    30%          { transform: translateY(-5px); opacity: 1; }
}

/* ── Quick Suggestions ───────────────────────────────── */
.cb-suggestions-wrap {
    padding: 0.75rem 1.25rem;
    border-top: 1px solid #f5ebe0;
    background: #fdf9f5;
}
.cb-suggestions-label {
    font-size: 0.66rem;
    color: #bbb;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.08em;
    margin-bottom: 0.5rem;
}
.cb-suggestions {
    display: flex;
    flex-wrap: wrap;
    gap: 0.4rem;
}
.cb-chip {
    padding: 0.28rem 0.75rem;
    background: #fff;
    border: 1.5px solid rgba(139,0,0,0.18);
    border-radius: 20px;
    font-size: 0.75rem;
    font-weight: 700;
    color: #5C0000;
    cursor: pointer;
    transition: all 0.18s;
    font-family: 'Nunito', sans-serif;
}
.cb-chip:hover {
    background: #8B0000;
    color: #fff;
    border-color: #8B0000;
    transform: translateY(-1px);
    box-shadow: 0 3px 10px rgba(139,0,0,0.2);
}

/* ── Input Area ──────────────────────────────────────── */
.cb-input-area {
    display: flex;
    gap: 0.6rem;
    padding: 0.9rem 1.25rem;
    border-top: 1px solid #f0e8e8;
    background: #fff;
    align-items: flex-end;
}
.cb-input {
    flex: 1;
    padding: 0.65rem 1rem;
    border: 1.5px solid #e8d8c0;
    border-radius: 24px;
    font-family: 'Nunito', sans-serif;
    font-size: 0.875rem;
    color: #1a0a0a;
    outline: none;
    resize: none;
    max-height: 100px;
    line-height: 1.5;
    transition: border-color 0.2s;
    background: #fdf9f5;
}
.cb-input:focus { border-color: #8B0000; background: #fff; }
.cb-input::placeholder { color: #ccc; }

.cb-send {
    width: 40px; height: 40px;
    border-radius: 50%;
    background: linear-gradient(135deg, #8B0000, #5C0000);
    border: none;
    color: #E8C76A;
    font-size: 1rem;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
    transition: all 0.2s;
    box-shadow: 0 3px 10px rgba(139,0,0,0.3);
}
.cb-send:hover { transform: scale(1.1); box-shadow: 0 5px 16px rgba(139,0,0,0.4); }
.cb-send:active { transform: scale(0.95); }
.cb-send:disabled { opacity: 0.5; cursor: not-allowed; transform: none; }

/* ── Shortcut nav ────────────────────────────────────── */
.cb-shortcuts {
    display: flex;
    gap: 0.5rem;
    margin-bottom: 1rem;
    flex-wrap: wrap;
}
.cb-shortcut {
    padding: 0.35rem 0.9rem;
    background: #fff;
    border: 1.5px solid rgba(139,0,0,0.15);
    border-radius: 8px;
    font-size: 0.77rem;
    font-weight: 700;
    color: #5C0000;
    cursor: pointer;
    text-decoration: none;
    transition: all 0.18s;
    font-family: 'Nunito', sans-serif;
}
.cb-shortcut:hover {
    background: #fdecea;
    border-color: #8B0000;
    color: #8B0000;
}

/* ── Responsive ──────────────────────────────────────── */
@media (max-width: 600px) {
    .cb-window { height: 440px; }
    .cb-bubble { max-width: 88%; font-size: 0.82rem; }
    .cb-hero { padding: 1rem 1.25rem; }
    .cb-hero-status { display: none; }
}
</style>

<div class="chatbot-page">

    <!-- Hero Header -->
    <div class="cb-hero">
        <div class="cb-orb"></div>
        <div class="cb-hero-text">
            <h1>PPTC Admin Assistant</h1>
            <p>Ask me about fees, students, emails, risks, or insights</p>
        </div>
        <div class="cb-hero-status">
            <div class="cb-status-dot"></div>
            ONLINE
        </div>
    </div>

    <!-- Quick Nav Shortcuts -->
    <div class="cb-shortcuts">
        <a href="dashboard.php"   class="cb-shortcut">🏠 Dashboard</a>
        <a href="fee_report.php"  class="cb-shortcut">📊 Fee Report</a>
        <a href="warnings.php"    class="cb-shortcut">⚠️ Warnings</a>
        <a href="insights.php"    class="cb-shortcut">📈 Insights</a>
    </div>

    <!-- Chat Window -->
    <div class="cb-window">

        <!-- Messages -->
        <div class="cb-messages" id="cbMessages">
            <!-- Welcome message injected by JS -->
        </div>

        <!-- Suggestions -->
        <div class="cb-suggestions-wrap" id="cbSuggestWrap">
            <div class="cb-suggestions-label">Quick Questions</div>
            <div class="cb-suggestions" id="cbSuggestions">
                <button class="cb-chip" data-q="Total Collection This Month">📊 Collection This Month</button>
                <button class="cb-chip" data-q="Show me high risk students">⚠️ High Risk Students</button>
                <button class="cb-chip" data-q="How many emails sent today">📧 Emails Sent Today</button>
                <button class="cb-chip" data-q="Students with due above 5000">💰 Due > ₹5,000</button>
                <button class="cb-chip" data-q="Show full insights summary">📈 Insights Summary</button>
                <button class="cb-chip" data-q="Admin activity today">👤 Admin Activity</button>
            </div>
        </div>

        <!-- Input -->
        <div class="cb-input-area">
            <textarea class="cb-input" id="cbInput"
                      placeholder="Ask about fees, risks, emails..."
                      rows="1" maxlength="300"></textarea>
            <button class="cb-send" id="cbSend" title="Send">&#10148;</button>
        </div>
    </div>

</div>

<script>
(function () {
    const messagesEl  = document.getElementById('cbMessages');
    const inputEl     = document.getElementById('cbInput');
    const sendBtn     = document.getElementById('cbSend');
    const suggestWrap = document.getElementById('cbSuggestWrap');
    const suggestions = document.querySelectorAll('.cb-chip');

    let isWaiting = false;

    // ── Helpers ──────────────────────────────────────────────
    function scrollToBottom() {
        messagesEl.scrollTop = messagesEl.scrollHeight;
    }

    function timeNow() {
        return new Date().toLocaleTimeString('en-US', { hour: '2-digit', minute: '2-digit' });
    }

    function formatText(text) {
        // Bold **text** → <strong>
        return text
            .replace(/\*\*(.*?)\*\*/g, '<strong>$1</strong>')
            .replace(/\n/g, '<br>');
    }

    // ── Append message ───────────────────────────────────────
    function appendMsg(type, text, time) {
        const wrap = document.createElement('div');
        wrap.className = `cb-msg ${type}`;

        const avatar = document.createElement('div');
        avatar.className = 'cb-avatar';
        avatar.textContent = type === 'user' ? 'A' : '🤖';

        const inner = document.createElement('div');

        const bubble = document.createElement('div');
        bubble.className = 'cb-bubble';
        bubble.innerHTML = formatText(text);

        const ts = document.createElement('div');
        ts.className = 'cb-time';
        ts.textContent = time || timeNow();

        inner.appendChild(bubble);
        inner.appendChild(ts);

        wrap.appendChild(avatar);
        wrap.appendChild(inner);
        messagesEl.appendChild(wrap);
        scrollToBottom();
        return bubble;
    }

    // ── Typing indicator ─────────────────────────────────────
    function showTyping() {
        const wrap = document.createElement('div');
        wrap.className = 'cb-msg bot cb-typing';
        wrap.id = 'cbTyping';

        const avatar = document.createElement('div');
        avatar.className = 'cb-avatar';
        avatar.textContent = '🤖';

        const inner = document.createElement('div');
        const bubble = document.createElement('div');
        bubble.className = 'cb-bubble';
        bubble.innerHTML = '<div class="typing-dots"><span></span><span></span><span></span></div>';

        inner.appendChild(bubble);
        wrap.appendChild(avatar);
        wrap.appendChild(inner);
        messagesEl.appendChild(wrap);
        scrollToBottom();
    }

    function removeTyping() {
        const t = document.getElementById('cbTyping');
        if (t) t.remove();
    }

    // ── Send message ─────────────────────────────────────────
    async function sendMessage(text) {
        if (isWaiting || !text.trim()) return;

        // Hide suggestions after first real use
        suggestWrap.style.display = 'none';

        isWaiting = true;
        sendBtn.disabled = true;

        appendMsg('user', text);
        inputEl.value = '';
        autoResize();

        showTyping();

        // Realistic delay: 800–1400ms
        const delay = 800 + Math.random() * 600;

        try {
            const res = await fetch('chatbot_api.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ message: text })
            });
            const data = await res.json();

            await new Promise(r => setTimeout(r, delay));
            removeTyping();

            appendMsg('bot', data.reply, data.timestamp);

            // Show suggestions again if fallback
            if (data.suggestions) {
                suggestWrap.style.display = 'block';
            }

        } catch (err) {
            await new Promise(r => setTimeout(r, delay));
            removeTyping();
            appendMsg('bot', '⚠️ Connection error. Please check your server and try again.');
        }

        isWaiting = false;
        sendBtn.disabled = false;
        inputEl.focus();
        scrollToBottom();
    }

    // ── Auto-resize textarea ─────────────────────────────────
    function autoResize() {
        inputEl.style.height = 'auto';
        inputEl.style.height = Math.min(inputEl.scrollHeight, 100) + 'px';
    }

    // ── Events ───────────────────────────────────────────────
    sendBtn.addEventListener('click', () => sendMessage(inputEl.value.trim()));

    inputEl.addEventListener('keydown', (e) => {
        if (e.key === 'Enter' && !e.shiftKey) {
            e.preventDefault();
            sendMessage(inputEl.value.trim());
        }
    });

    inputEl.addEventListener('input', autoResize);

    suggestions.forEach(chip => {
        chip.addEventListener('click', () => sendMessage(chip.dataset.q));
    });

    // ── Welcome message on load ──────────────────────────────
    setTimeout(() => {
        appendMsg('bot',
            "👋 Hello, Admin! I'm your PPTC AI Assistant.\n\nI have access to live data from your system. You can ask me about:\n\n📊 Fee collection (today / monthly / total)\n⚠️ High-risk & defaulting students\n📧 Email automation activity\n📈 Financial insights & efficiency\n👤 Admin activity logs\n\nTap a quick question below or type anything!",
            timeNow()
        );
    }, 400);

})();
</script>

<?php include 'includes/footer.php'; ?>
