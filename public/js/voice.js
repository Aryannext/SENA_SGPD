/**
 * Voice JS — Speech Recognition (STT) + Audio Playback for cloned voice.
 * Uses browser's native SpeechRecognition API for input,
 * and the Python TTS server for output with cloned voice.
 */
const Voice = {
    recognition: null,
    isRecording: false,

    init() {
        const SpeechRecognition = window.SpeechRecognition || window.webkitSpeechRecognition;
        if (!SpeechRecognition) {
            console.warn('SpeechRecognition not supported');
            const btn = document.getElementById('btn-mic');
            if (btn) btn.title = 'Tu navegador no soporta reconocimiento de voz';
            return;
        }

        this.recognition = new SpeechRecognition();
        this.recognition.lang = 'es-CO';
        this.recognition.continuous = false;
        this.recognition.interimResults = true;

        this.recognition.onresult = (event) => {
            let transcript = '';
            for (let i = event.resultIndex; i < event.results.length; i++) {
                transcript += event.results[i][0].transcript;
            }

            const statusEl = document.getElementById('voice-status');
            if (statusEl) statusEl.textContent = transcript || 'Escuchando...';

            // If final result
            if (event.results[event.results.length - 1].isFinal) {
                this.stopRecording();
                if (transcript.trim()) {
                    // Send to chat in voice mode
                    const input = document.getElementById('chat-input');
                    if (input) input.value = transcript;
                    Chat.send();
                }
            }
        };

        this.recognition.onend = () => {
            this.stopRecording();
        };

        this.recognition.onerror = (event) => {
            console.error('Speech recognition error:', event.error);
            this.stopRecording();
            const statusEl = document.getElementById('voice-status');
            if (statusEl) statusEl.textContent = 'Error: ' + event.error;
        };
    },

    toggleRecording() {
        if (this.isRecording) {
            this.stopRecording();
        } else {
            this.startRecording();
        }
    },

    startRecording() {
        if (!this.recognition) {
            APP.toast('Reconocimiento de voz no disponible en este navegador', 'error');
            return;
        }

        this.isRecording = true;
        const btn = document.getElementById('btn-mic');
        const statusEl = document.getElementById('voice-status');

        if (btn) btn.classList.add('recording');
        if (statusEl) statusEl.textContent = 'Escuchando...';

        try {
            this.recognition.start();
        } catch (e) {
            // Already started
        }
    },

    stopRecording() {
        this.isRecording = false;
        const btn = document.getElementById('btn-mic');
        const statusEl = document.getElementById('voice-status');

        if (btn) btn.classList.remove('recording');
        if (statusEl) statusEl.textContent = 'Presiona el micrófono para hablar';

        try {
            this.recognition?.stop();
        } catch (e) { /* ignore */ }
    }
};

document.addEventListener('DOMContentLoaded', () => Voice.init());
