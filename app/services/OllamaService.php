<?php

declare(strict_types=1);

namespace App\Services;

/**
 * Client for the Ollama API.
 * Handles both streaming and non-streaming chat completions.
 *
 * Single Responsibility: HTTP communication with Ollama only.
 */
final class OllamaService
{
    private string $baseUrl;
    private string $model;
    private string $systemPrompt;
    private float  $temperature;
    private int    $numCtx;

    public function __construct()
    {
        $config = require dirname(__DIR__, 2) . '/config/ollama.php';
        $this->baseUrl      = $config['base_url'];
        $this->model        = $config['model'];
        $this->systemPrompt = $config['system_prompt'];
        $this->temperature  = $config['temperature'];
        $this->numCtx       = $config['num_ctx'];
    }

    /**
     * Send a chat completion request (non-streaming).
     *
     * @param array  $messages  Conversation history [{role, content}, ...]
     * @param string $extraContext  Additional context to prepend to system prompt
     * @return string  The assistant's response text
     */
    public function chat(array $messages, string $extraContext = ''): string
    {
        $systemContent = $this->systemPrompt;
        if ($extraContext) {
            $systemContent .= "\n\n[DATOS_SISTEMA]\n" . $extraContext . "\n[/DATOS_SISTEMA]";
        }

        $payload = [
            'model'   => $this->model,
            'messages' => array_merge(
                [['role' => 'system', 'content' => $systemContent]],
                $messages
            ),
            'stream'  => false,
            'options' => [
                'temperature' => $this->temperature,
                'num_ctx'     => $this->numCtx,
            ],
        ];

        $ch = curl_init($this->baseUrl . '/api/chat');
        curl_setopt_array($ch, [
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => json_encode($payload),
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER     => ['Content-Type: application/json'],
            CURLOPT_TIMEOUT        => 120,
        ]);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($httpCode !== 200 || $response === false) {
            throw new \RuntimeException('Ollama API error: HTTP ' . $httpCode);
        }

        $data = json_decode($response, true);
        return $data['message']['content'] ?? 'Sin respuesta.';
    }

    /**
     * Stream a chat completion via Server-Sent Events.
     *
     * @param array  $messages
     * @param string $extraContext
     * @param array  $actions      Actions to append to the end of the stream
     */
    public function chatStream(array $messages, string $extraContext = '', array $actions = []): void
    {
        $systemContent = $this->systemPrompt;
        if ($extraContext) {
            $systemContent .= "\n\n[DATOS_SISTEMA]\n" . $extraContext . "\n[/DATOS_SISTEMA]";
        }

        $payload = [
            'model'   => $this->model,
            'messages' => array_merge(
                [['role' => 'system', 'content' => $systemContent]],
                $messages
            ),
            'stream'  => true,
            'options' => [
                'temperature' => $this->temperature,
                'num_ctx'     => $this->numCtx,
            ],
        ];

        // Set SSE headers
        header('Content-Type: text/event-stream');
        header('Cache-Control: no-cache');
        header('Connection: keep-alive');
        header('X-Accel-Buffering: no');

        // Disable output buffering
        while (ob_get_level()) ob_end_flush();

        $ch = curl_init($this->baseUrl . '/api/chat');
        curl_setopt_array($ch, [
            CURLOPT_POST       => true,
            CURLOPT_POSTFIELDS => json_encode($payload),
            CURLOPT_HTTPHEADER => ['Content-Type: application/json'],
            CURLOPT_TIMEOUT    => 120,
            CURLOPT_WRITEFUNCTION => function ($ch, $data) {
                $lines = explode("\n", $data);
                foreach ($lines as $line) {
                    $line = trim($line);
                    if (empty($line)) continue;

                    $json = json_decode($line, true);
                    if (!$json) continue;

                    $token = $json['message']['content'] ?? '';
                    $done  = $json['done'] ?? false;

                    if ($token !== '') {
                        echo "data: " . json_encode(['token' => $token, 'done' => false]) . "\n\n";
                        flush();
                    }

                    if ($done) {
                        echo "data: " . json_encode(['token' => '', 'done' => true]) . "\n\n";
                        
                        // Stream actions if any
                        foreach ($actions as $action) {
                            echo "data: " . json_encode(['action' => $action]) . "\n\n";
                        }
                        
                        flush();
                    }
                }
                return strlen($data);
            },
        ]);

        curl_exec($ch);
        curl_close($ch);
    }

    /**
     * Check if Ollama is reachable.
     */
    public function isAvailable(): bool
    {
        $ch = curl_init($this->baseUrl . '/api/tags');
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT        => 3,
            CURLOPT_CONNECTTIMEOUT => 2,
        ]);
        $result = curl_exec($ch);
        $code   = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        return $code === 200;
    }
}
