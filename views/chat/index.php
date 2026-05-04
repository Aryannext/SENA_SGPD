<?php $isWidget = isset($_GET['widget']) && $_GET['widget'] === 'true'; ?>
<div class="chat-page animate-in" <?= $isWidget ? 'style="height: 100vh; max-height: 100vh; border-radius: 0; border: none;"' : '' ?>>
    <!-- Header -->
    <div class="chat-header">
        <div class="chat-header-left">
            <div class="chat-avatar">
                <i class="fas fa-robot"></i>
            </div>
            <div>
                <div class="chat-name">SENA-IA</div>
                <div class="chat-status">
                    <span class="status-dot <?= $ollamaAvailable ? 'online' : 'offline' ?>"></span>
                    <?= $ollamaAvailable ? 'En línea' : 'Desconectado' ?>
                    <?php if ($ttsAvailable): ?>
                        <span style="margin-left:12px;"><i class="fas fa-volume-up" style="color:var(--accent);"></i> Voz activa</span>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        <div class="chat-header-right">
            <!-- Mode Toggle -->
            <div class="mode-toggle">
                <button class="mode-btn active" id="mode-text" onclick="ChatMode.setMode('text')">
                    <i class="fas fa-keyboard"></i> Texto
                </button>
                <button class="mode-btn" id="mode-voice" onclick="ChatMode.setMode('voice')">
                    <i class="fas fa-microphone"></i> Voz
                </button>
            </div>
            <button class="btn btn-sm btn-secondary" onclick="Chat.clear()" title="Limpiar historial">
                <i class="fas fa-trash"></i>
            </button>
        </div>
    </div>

    <!-- Messages Area -->
    <div class="chat-messages" id="chat-messages">
        <div class="chat-welcome">
            <div class="welcome-icon"><i class="fas fa-robot"></i></div>
            <h3>SENA-IA</h3>
            <p>Asistente técnico del SGPD. Pregúntame sobre los datos del sistema.</p>
            <div class="chat-suggestions" id="chat-suggestions">
                <button class="suggestion-chip" onclick="Chat.sendSuggestion('¿Cuántos aprendices hay en la ficha?')">
                    <i class="fas fa-users"></i> ¿Cuántos aprendices hay?
                </button>
                <button class="suggestion-chip" onclick="Chat.sendSuggestion('¿Quién tiene más juicios pendientes?')">
                    <i class="fas fa-clock"></i> ¿Más pendientes?
                </button>
                <button class="suggestion-chip" onclick="Chat.sendSuggestion('¿Cuál es el avance global de la ficha?')">
                    <i class="fas fa-chart-line"></i> Avance global
                </button>
                <button class="suggestion-chip" onclick="Chat.sendSuggestion('¿Qué competencia tiene menor aprobación?')">
                    <i class="fas fa-graduation-cap"></i> Competencia más baja
                </button>
                <button class="suggestion-chip" onclick="Chat.sendSuggestion('¿Hay aprendices en riesgo académico?')">
                    <i class="fas fa-exclamation-triangle"></i> Aprendices en riesgo
                </button>
                <button class="suggestion-chip" onclick="Chat.sendSuggestion('Hazme un análisis completo del estado de la ficha')">
                    <i class="fas fa-search"></i> Análisis completo
                </button>
            </div>
        </div>
    </div>

    <!-- Input Area -->
    <div class="chat-input-area" id="chat-input-area">
        <!-- Text mode input -->
        <div class="chat-input-text" id="input-text-mode">
            <input type="text" class="chat-input" id="chat-input" placeholder="Escribe tu mensaje..." autocomplete="off"
                   onkeypress="if(event.key==='Enter')Chat.send()">
            <button class="chat-send-btn" onclick="Chat.send()" id="btn-send">
                <i class="fas fa-paper-plane"></i>
            </button>
        </div>

        <!-- Voice mode input -->
        <div class="chat-input-voice" id="input-voice-mode" style="display:none;">
            <button class="voice-btn" id="btn-mic" onclick="Voice.toggleRecording()">
                <i class="fas fa-microphone"></i>
            </button>
            <div class="voice-status" id="voice-status">Presiona el micrófono para hablar</div>
        </div>
    </div>
</div>
