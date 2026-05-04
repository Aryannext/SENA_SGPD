<?php

declare(strict_types=1);

namespace App\Controllers;

use Core\Controller;
use App\Services\OllamaService;
use App\Services\DataContextService;
use App\Services\TTSService;

final class AIController extends Controller
{
    public function index(): void
    {
        $ollamaAvailable = (new OllamaService())->isAvailable();
        $ttsAvailable    = (new TTSService())->isAvailable();

        $this->view('chat.index', [
            'pageTitle'       => 'SENA-IA',
            'ollamaAvailable' => $ollamaAvailable,
            'ttsAvailable'    => $ttsAvailable,
            'extraCss'        => ['chat.css'],
            'extraJs'         => ['chat.js', 'voice.js'],
        ]);
    }

    /**
     * Streaming text chat via SSE.
     */
    public function send(): void
    {
        $body    = $this->getJsonBody();
        $message = trim($body['message'] ?? '');

        if (empty($message)) {
            $this->json(['error' => 'Mensaje vacío'], 400);
        }

        // Build conversation history from session
        if (!isset($_SESSION['chat_history'])) {
            $_SESSION['chat_history'] = [];
        }

        // Add user message
        $_SESSION['chat_history'][] = ['role' => 'user', 'content' => $message];

        // Keep only last 20 messages
        if (count($_SESSION['chat_history']) > 20) {
            $_SESSION['chat_history'] = array_slice($_SESSION['chat_history'], -20);
        }

        // Build data context from DB
        $contextService = new DataContextService();
        $contextData = $contextService->buildContextAndActions($message);
        
        $context = $contextData['context_string'];
        $actions = $contextData['actions'];

        // Stream response
        $ollama = new OllamaService();

        // Close session before streaming to avoid locking
        $history = $_SESSION['chat_history'];
        session_write_close();

        $ollama->chatStream($history, $context, $actions);

        // Note: assistant response is saved client-side via a follow-up call
        exit;
    }

    /**
     * Synthesize audio from text (used after streaming completes).
     */
    public function synthesize(): void
    {
        $body = $this->getJsonBody();
        $text = trim($body['text'] ?? '');

        if (empty($text)) {
            $this->json(['error' => 'Texto vacío'], 400);
        }

        $audioUrl = null;
        $tts = new TTSService();
        if ($tts->isAvailable()) {
            // Strip markdown, tags, and formatting for natural speech
            $ttsText = $text;
            $ttsText = preg_replace('/<think>.*?<\/think>/s', '', $ttsText);
            $ttsText = preg_replace('/\[CHART:.*?\]/s', '', $ttsText);
            $ttsText = preg_replace('/\[REPORT:.*?\]/s', '', $ttsText);
            $ttsText = preg_replace('/http[s]?:\/\/\S+/', 'enlace', $ttsText);
            $ttsText = preg_replace('/[\*\_\`\#\[\]]/', '', $ttsText);
            $ttsText = preg_replace('/^[\-\+]\s+/m', '', $ttsText);
            $ttsText = preg_replace('/(\d+)\/(\d+)/', '$1 de $2', $ttsText);
            $ttsText = preg_replace('/[\x{1F600}-\x{1F64F}\x{1F300}-\x{1F5FF}\x{1F680}-\x{1F6FF}\x{1F700}-\x{1F77F}\x{1F780}-\x{1F7FF}\x{1F800}-\x{1F8FF}\x{1F900}-\x{1F9FF}\x{1FA00}-\x{1FA6F}\x{1FA70}-\x{1FAFF}\x{2600}-\x{26FF}\x{2700}-\x{27BF}]/u', '', $ttsText);
            $ttsText = preg_replace('/\s+/', ' ', $ttsText);
            $ttsText = mb_substr(trim($ttsText), 0, 500);
            
            if (!empty($ttsText)) {
                $audioUrl = $tts->synthesize($ttsText);
            }
        }

        $this->json([
            'audio_url' => $audioUrl,
            'tts_available' => $audioUrl !== null,
        ]);
    }

    /**
     * Clear chat history.
     */
    public function clear(): void
    {
        $_SESSION['chat_history'] = [];
        $this->json(['success' => true, 'message' => 'Historial limpiado.']);
    }
}
