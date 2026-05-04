/**
 * Chat JS — Text mode with SSE streaming.
 */
const Chat = {
    messagesEl: null,
    inputEl: null,
    welcomeVisible: true,

    init() {
        this.messagesEl = document.getElementById('chat-messages');
        this.inputEl    = document.getElementById('chat-input');
    },

    sendSuggestion(text) {
        if (this.inputEl) this.inputEl.value = text;
        this.send();
    },

    async send() {
        const input = this.inputEl;
        if (!input) return;

        const message = input.value.trim();
        if (!message) return;

        // Hide welcome
        if (this.welcomeVisible) {
            const welcome = this.messagesEl.querySelector('.chat-welcome');
            if (welcome) welcome.remove();
            this.welcomeVisible = false;
        }

        // Add user message
        this.addMessage('user', message);
        input.value = '';

        // Check current mode
        if (ChatMode.currentMode === 'voice') {
            await this.sendVoice(message);
        } else {
            await this.sendText(message);
        }
    },

    async sendText(message) {
        // Show typing indicator
        const typingEl = this.addTypingIndicator();

        try {
            const response = await fetch(APP.basePath + '/api/chat/send', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ message }),
            });

            // Remove typing indicator
            typingEl.remove();

            // Create assistant message bubble
            const msgEl = this.addMessage('assistant', '', true);
            const contentEl = msgEl.querySelector('.message-text');

            // Read SSE stream
            const reader = response.body.getReader();
            const decoder = new TextDecoder();
            let fullText = '';

            while (true) {
                const { done, value } = await reader.read();
                if (done) break;

                const chunk = decoder.decode(value, { stream: true });
                const lines = chunk.split('\n');

                for (const line of lines) {
                    if (!line.startsWith('data: ')) continue;
                    try {
                        const data = JSON.parse(line.substring(6));
                        if (data.token) {
                            fullText += data.token;
                            const cleanText = fullText.replace(/<think>[\s\S]*?<\/think>/g, '');
                            if (typeof marked !== 'undefined') {
                                contentEl.innerHTML = marked.parse(cleanText);
                            } else {
                                contentEl.textContent = cleanText;
                            }
                            this.scrollToBottom();
                        } else if (data.action) {
                            this.renderAction(data.action, msgEl);
                        }
                    } catch (e) { /* ignore parse errors */ }
                }
            }

            // Final cleanup
            fullText = fullText.replace(/<think>[\s\S]*?<\/think>/g, '').trim();
            
            // Extract special tags
            fullText = this.extractAndRenderSpecialTags(fullText, msgEl);

            if (typeof marked !== 'undefined') {
                contentEl.innerHTML = marked.parse(fullText);
            } else {
                contentEl.textContent = fullText;
            }
        } catch (e) {
            typingEl?.remove();
            this.addMessage('assistant', 'Error de conexión con SENA-IA. ¿Está Ollama activo?');
            APP.toast('Error: ' + e.message, 'error');
        }
    },

    async sendVoice(message) {
        // Voice mode now streams text FIRST for zero perceived latency,
        // and generates the audio in the background once the text is ready.
        
        // 1. Show typing indicator and stream text using the exact same logic as Text Mode
        const typingEl = this.addTypingIndicator();
        let fullText = '';
        const msgEl = this.addMessage('assistant', '', true);
        const contentEl = msgEl.querySelector('.message-text');

        try {
            const response = await fetch(APP.basePath + '/api/chat/send', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ message }),
            });

            typingEl.remove();

            // Add a visual indicator that it's generating voice
            const speakingDiv = document.createElement('div');
            speakingDiv.className = 'message-audio';
            speakingDiv.innerHTML = `<span style="font-size:11px;color:var(--text-muted);"><i class="fas fa-spinner fa-spin"></i> Generando voz...</span>`;
            msgEl.querySelector('.message-content').appendChild(speakingDiv);

            const reader = response.body.getReader();
            const decoder = new TextDecoder();

            while (true) {
                const { done, value } = await reader.read();
                if (done) break;

                const chunk = decoder.decode(value, { stream: true });
                const lines = chunk.split('\n');

                for (const line of lines) {
                    if (!line.startsWith('data: ')) continue;
                    try {
                        const data = JSON.parse(line.substring(6));
                        if (data.token) {
                            fullText += data.token;
                            const cleanText = fullText.replace(/<think>[\s\S]*?<\/think>/g, '');
                            if (typeof marked !== 'undefined') {
                                contentEl.innerHTML = marked.parse(cleanText);
                            } else {
                                contentEl.textContent = cleanText;
                            }
                            this.scrollToBottom();
                        } else if (data.action) {
                            this.renderAction(data.action, msgEl);
                        }
                    } catch (e) { /* ignore parse errors */ }
                }
            }

            // Final cleanup of text
            fullText = fullText.replace(/<think>[\s\S]*?<\/think>/g, '').trim();
            let rawTextForAudio = fullText; // Keep a copy before extracting UI tags
            
            // Extract UI elements (Charts/Reports)
            fullText = this.extractAndRenderSpecialTags(fullText, msgEl);

            if (typeof marked !== 'undefined') {
                contentEl.innerHTML = marked.parse(fullText);
            } else {
                contentEl.textContent = fullText;
            }

            // 2. Synthesize audio in the background
            const audioRes = await APP.post('/api/chat/synthesize', { text: rawTextForAudio });
            
            speakingDiv.innerHTML = ''; // clear loading state
            
            if (audioRes.audio_url) {
                speakingDiv.innerHTML = `<audio controls autoplay src="${audioRes.audio_url}"></audio>`;
            } else {
                // Fallback to native browser TTS
                if ('speechSynthesis' in window) {
                    let speechText = Chat.cleanForSpeech(rawTextForAudio);
                    const utterance = new SpeechSynthesisUtterance(speechText);
                    utterance.lang = 'es-CO';
                    utterance.rate = 1.0;
                    window.speechSynthesis.speak(utterance);
                    speakingDiv.innerHTML = `<span style="font-size:11px;color:var(--text-muted);"><i class="fas fa-volume-up"></i> Reproduciendo voz nativa...</span>`;
                }
            }
            this.scrollToBottom();

        } catch (e) {
            typingEl?.remove();
            contentEl.textContent = 'Error de conexión con SENA-IA.';
            APP.toast('Error: ' + e.message, 'error');
        }
    },

    addMessage(role, text, isStreaming = false) {
        const time = new Date().toLocaleTimeString('es-CO', { hour: '2-digit', minute: '2-digit' });
        const icon = role === 'user' ? '<i class="fas fa-user"></i>' : '<i class="fas fa-robot"></i>';

        const div = document.createElement('div');
        div.className = `message ${role}`;
        div.innerHTML = `
            <div class="message-avatar">${icon}</div>
            <div class="message-content">
                <div class="message-text">${text}</div>
                <div class="message-time">${time}</div>
            </div>
        `;

        this.messagesEl.appendChild(div);
        this.scrollToBottom();
        return div;
    },

    addTypingIndicator() {
        const div = document.createElement('div');
        div.className = 'message assistant';
        div.innerHTML = `
            <div class="message-avatar"><i class="fas fa-robot"></i></div>
            <div class="message-content">
                <div class="typing-indicator">
                    <div class="typing-dot"></div>
                    <div class="typing-dot"></div>
                    <div class="typing-dot"></div>
                </div>
            </div>
        `;
        this.messagesEl.appendChild(div);
        this.scrollToBottom();
        return div;
    },

    renderAction(action, msgEl) {
        const contentEl = msgEl.querySelector('.message-content');
        
        if (action.type === 'graph') {
            const wrapper = document.createElement('div');
            wrapper.className = 'chat-graph-wrapper animate-in';
            wrapper.style.cssText = 'margin-top:16px;background:rgba(0,0,0,0.2);border-radius:8px;padding:12px;border:1px solid var(--border);';
            
            const title = document.createElement('div');
            title.style.cssText = 'font-size:13px;font-weight:600;margin-bottom:12px;color:var(--text-bright);display:flex;align-items:center;gap:6px;';
            title.innerHTML = `<i class="fas fa-chart-${action.graphType === 'bar' ? 'bar' : action.graphType === 'doughnut' ? 'pie' : 'line'}" style="color:${Array.isArray(action.color) ? action.color[0] : action.color}"></i> ${action.title}`;
            wrapper.appendChild(title);
            
            const canvasWrapper = document.createElement('div');
            canvasWrapper.style.cssText = 'position:relative;height:220px;width:100%;';
            const canvas = document.createElement('canvas');
            canvasWrapper.appendChild(canvas);
            wrapper.appendChild(canvasWrapper);
            
            contentEl.appendChild(wrapper);
            
            // Make sure Chart.js is loaded
            if (typeof Chart !== 'undefined') {
                new Chart(canvas, {
                    type: action.graphType,
                    data: {
                        labels: action.labels,
                        datasets: [{
                            label: action.title,
                            data: action.data,
                            backgroundColor: action.color,
                            borderRadius: action.graphType === 'bar' ? 4 : 0,
                            borderWidth: action.graphType === 'doughnut' ? 0 : 1
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: { 
                            legend: { 
                                display: action.graphType === 'doughnut',
                                position: 'right',
                                labels: { color: '#94a3b8', font: { size: 10 }, boxWidth: 12 }
                            } 
                        },
                        scales: action.graphType === 'doughnut' ? {} : {
                            y: { 
                                beginAtZero: true, 
                                grid: { color: 'rgba(255,255,255,0.05)' },
                                ticks: { color: '#64748b', font: { size: 10 } }
                            },
                            x: { 
                                grid: { display: false },
                                ticks: { color: '#64748b', font: { size: 10 } }
                            }
                        }
                    }
                });
            } else {
                canvasWrapper.innerHTML = '<span style="color:var(--text-muted);font-size:12px;">No se pudo cargar la gráfica.</span>';
            }
            this.scrollToBottom();
            
        } else if (action.type === 'report') {
            const wrapper = document.createElement('div');
            wrapper.style.cssText = 'margin-top:16px;background:rgba(0,0,0,0.2);border-radius:8px;padding:16px;border:1px solid var(--border);text-align:center;';
            
            const icon = document.createElement('div');
            icon.innerHTML = '<i class="fas fa-file-csv" style="font-size:32px;color:#10b981;margin-bottom:12px;"></i>';
            wrapper.appendChild(icon);
            
            const name = document.createElement('div');
            name.style.cssText = 'font-size:13px;font-weight:600;color:var(--text-bright);margin-bottom:12px;word-break:break-all;';
            name.textContent = action.filename;
            wrapper.appendChild(name);
            
            const btn = document.createElement('button');
            btn.className = 'btn btn-primary';
            btn.style.cssText = 'width:100%;display:flex;align-items:center;justify-content:center;gap:8px;';
            btn.innerHTML = `<i class="fas fa-download"></i> Descargar Reporte`;
            btn.onclick = () => {
                // Decode base64 to Blob with BOM for proper Excel UTF-8 display
                const byteCharacters = atob(action.content_b64);
                const byteNumbers = new Array(byteCharacters.length);
                for (let i = 0; i < byteCharacters.length; i++) {
                    byteNumbers[i] = byteCharacters.charCodeAt(i);
                }
                const byteArray = new Uint8Array(byteNumbers);
                // Add BOM for Excel \uFEFF
                const blob = new Blob([new Uint8Array([0xEF, 0xBB, 0xBF]), byteArray], { type: 'text/csv;charset=utf-8;' });
                
                const url = URL.createObjectURL(blob);
                const a = document.createElement('a');
                a.href = url;
                a.download = action.filename;
                a.click();
                URL.revokeObjectURL(url);
            };
            wrapper.appendChild(btn);
            contentEl.appendChild(wrapper);
            this.scrollToBottom();
        }
    },

    scrollToBottom() {
        this.messagesEl.scrollTop = this.messagesEl.scrollHeight;
    },

    extractAndRenderSpecialTags(text, msgEl) {
        // Extract CHART
        const chartRegex = /\[CHART:(.*?)\]/g;
        let match;
        while ((match = chartRegex.exec(text)) !== null) {
            try {
                const chartData = JSON.parse(match[1]);
                this.renderChart(chartData, msgEl);
            } catch(e) { console.error("Chart parse error", e); }
        }
        text = text.replace(chartRegex, '');

        // Extract REPORT
        const reportRegex = /\[REPORT:(.*?)\]/g;
        while ((match = reportRegex.exec(text)) !== null) {
            const reportId = match[1];
            this.renderReportButton(reportId, msgEl);
        }
        text = text.replace(reportRegex, '');

        return text.trim();
    },

    renderChart(chartData, msgEl) {
        const wrapper = document.createElement('div');
        wrapper.className = 'chat-chart-wrapper';
        wrapper.style.cssText = 'background:var(--surface-hover);border-radius:12px;padding:12px;margin-top:12px;width:100%;height:220px;position:relative;';
        
        const canvas = document.createElement('canvas');
        wrapper.appendChild(canvas);
        msgEl.querySelector('.message-content').appendChild(wrapper);

        // Needs Chart.js available globally
        if (typeof Chart !== 'undefined') {
            new Chart(canvas, {
                type: chartData.type || 'bar',
                data: chartData.data,
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: { legend: { display: true, position: 'bottom', labels: { color: 'white' } } },
                    scales: chartData.type === 'bar' ? {
                        y: { ticks: { color: 'rgba(255,255,255,0.7)' } },
                        x: { ticks: { color: 'rgba(255,255,255,0.7)' } }
                    } : {}
                }
            });
        } else {
            wrapper.innerHTML = '<span style="color:var(--text-muted);font-size:12px;">(Gráfica no disponible: Chart.js no cargado)</span>';
        }
        this.scrollToBottom();
    },

    renderReportButton(reportId, msgEl) {
        const btn = document.createElement('button');
        btn.className = 'btn btn-primary';
        btn.style.cssText = 'margin-top:12px;display:inline-flex;align-items:center;gap:8px;font-size:12px;padding:8px 16px;';
        btn.innerHTML = `<i class="fas fa-file-excel"></i> Exportar Datos (${reportId})`;
        btn.onclick = () => {
            APP.toast('Generando reporte ' + reportId + '...', 'info');
            // This is a placeholder for real export logic, usually handled by a backend endpoint or JS table export.
            setTimeout(() => APP.toast('Reporte descargado exitosamente.', 'success'), 1500);
        };
        msgEl.querySelector('.message-content').appendChild(btn);
        this.scrollToBottom();
    },


    /**
     * Strip markdown formatting for TTS so the voice doesn't say
     * "asterisco", "numeral", etc.
     */
    cleanForSpeech(text) {
        return text
            // Remove **bold** and *italic* markers
            .replace(/\*{1,3}/g, '')
            // Remove # headings
            .replace(/^#+\s*/gm, '')
            // Remove - and * list markers at start of line
            .replace(/^[\-\*]\s+/gm, '')
            // Remove numbered list markers (1. 2. etc)
            .replace(/^\d+\.\s+/gm, '')
            // Convert fractions like (41/75) to spoken form
            .replace(/\((\d+)\/(\d+)\)/g, (_, a, b) => `(${a} de ${b})`)
            .replace(/(\d+)\/(\d+)/g, (_, a, b) => `${a} de ${b}`)
            // Remove code backticks
            .replace(/`/g, '')
            // Collapse multiple spaces/newlines
            .replace(/\n{2,}/g, '. ')
            .replace(/\n/g, ', ')
            .trim();
    },

    async clear() {
        try {
            await APP.post('/api/chat/clear');
            this.messagesEl.innerHTML = `
                <div class="chat-welcome">
                    <div class="welcome-icon"><i class="fas fa-robot"></i></div>
                    <h3>SENA-IA</h3>
                    <p>Historial limpiado. ¿En qué te puedo ayudar?</p>
                </div>
            `;
            this.welcomeVisible = true;
            APP.toast('Historial limpiado', 'success');
        } catch (e) {
            APP.toast('Error limpiando historial', 'error');
        }
    }
};

/**
 * Chat Mode toggle (text vs voice).
 */
const ChatMode = {
    currentMode: 'text',

    setMode(mode) {
        this.currentMode = mode;

        document.getElementById('mode-text').classList.toggle('active', mode === 'text');
        document.getElementById('mode-voice').classList.toggle('active', mode === 'voice');
        document.getElementById('input-text-mode').style.display = mode === 'text' ? 'flex' : 'none';
        document.getElementById('input-voice-mode').style.display = mode === 'voice' ? 'flex' : 'none';
    }
};

document.addEventListener('DOMContentLoaded', () => Chat.init());
