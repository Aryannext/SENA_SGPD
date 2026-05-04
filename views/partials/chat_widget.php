<!-- Chat Widget — Floating Bubble (available on all pages) -->
<div id="chat-widget-bubble" class="chat-bubble" onclick="toggleChatWidget()" title="Hablar con SENA-IA">
    <i class="fas fa-robot"></i>
</div>

<!-- Chat Widget Panel (iframe) -->
<div id="chat-widget-panel" class="chat-panel">
    <div class="chat-panel-header">
        <div style="display:flex;align-items:center;gap:10px;">
            <i class="fas fa-robot" style="font-size:20px;color:var(--accent);"></i>
            <span style="font-weight:700;font-size:15px;">SENA-IA</span>
        </div>
        <button class="chat-panel-close" onclick="toggleChatWidget()"><i class="fas fa-times"></i></button>
    </div>
    <!-- Iframe loads lazily to avoid blocking page load with ping timeouts -->
    <iframe data-src="/SENA_SGPD/chat?widget=true" id="chat-iframe" frameborder="0" allow="microphone"></iframe>
</div>

<style>
.chat-bubble {
    position: fixed;
    bottom: 28px;
    right: 28px;
    width: 60px;
    height: 60px;
    background: linear-gradient(135deg, var(--accent), #10b981);
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 24px;
    color: #fff;
    cursor: pointer;
    box-shadow: 0 4px 20px rgba(57, 211, 83, 0.35);
    z-index: 1000;
    transition: all 0.3s ease;
    animation: pulse-glow 3s infinite;
}
.chat-bubble:hover {
    transform: scale(1.1) translateY(-5px);
    box-shadow: 0 8px 30px rgba(57, 211, 83, 0.5);
}

.chat-panel {
    position: fixed;
    bottom: 100px;
    right: 28px;
    width: 420px;
    height: 600px;
    max-height: calc(100vh - 120px);
    background: var(--bg-secondary);
    border: 1px solid var(--border);
    border-radius: var(--radius-xl);
    box-shadow: 0 10px 40px rgba(0,0,0,0.5);
    z-index: 1000;
    display: flex;
    flex-direction: column;
    overflow: hidden;
    opacity: 0;
    visibility: hidden;
    transform: translateY(20px) scale(0.95);
    transition: all 0.3s cubic-bezier(0.175, 0.885, 0.32, 1.275);
    transform-origin: bottom right;
}

.chat-panel.show {
    opacity: 1;
    visibility: visible;
    transform: translateY(0) scale(1);
}

.chat-panel-header {
    background: var(--bg-card);
    padding: 16px 20px;
    border-bottom: 1px solid var(--border);
    display: flex;
    align-items: center;
    justify-content: space-between;
}

.chat-panel-close {
    background: transparent;
    border: none;
    color: var(--text-muted);
    font-size: 18px;
    cursor: pointer;
    transition: color 0.2s;
    display: flex;
    align-items: center;
    justify-content: center;
    width: 32px;
    height: 32px;
    border-radius: 50%;
}
.chat-panel-close:hover {
    color: var(--danger);
    background: rgba(239, 68, 68, 0.1);
}

#chat-iframe {
    flex: 1;
    width: 100%;
    height: 100%;
    background: var(--bg-secondary);
}

@media (max-width: 500px) {
    .chat-panel {
        width: calc(100vw - 40px);
        right: 20px;
        bottom: 90px;
    }
}
</style>

<script>
function toggleChatWidget() {
    const panel = document.getElementById('chat-widget-panel');
    const iframe = document.getElementById('chat-iframe');
    
    panel.classList.toggle('show');
    
    // If opening for the first time or the iframe is blank, ensure it's loaded
    if (panel.classList.contains('show')) {
        if (!iframe.src || iframe.src === window.location.href || iframe.getAttribute('src') === '') {
            iframe.src = iframe.getAttribute('data-src');
        }
        // Optional: auto focus input inside iframe
        setTimeout(() => {
            try {
                const innerInput = iframe.contentWindow.document.getElementById('chat-input');
                if (innerInput) innerInput.focus();
            } catch(e) {}
        }, 500);
    }
}
</script>
