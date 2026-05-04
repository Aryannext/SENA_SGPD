<?php

declare(strict_types=1);

namespace App\Services;

/**
 * Client for the Python TTS microservice (Coqui XTTS v2).
 * Sends text → receives WAV audio with the cloned voice.
 *
 * Single Responsibility: HTTP communication with TTS server only.
 */
final class TTSService
{
    private string $baseUrl;
    private string $cacheDir;
    private int    $maxTextLen;

    public function __construct()
    {
        $config = require dirname(__DIR__, 2) . '/config/tts.php';
        $this->baseUrl    = $config['base_url'];
        $this->cacheDir   = $config['cache_dir'];
        $this->maxTextLen = $config['max_text_len'];

        if (!is_dir($this->cacheDir)) {
            mkdir($this->cacheDir, 0755, true);
        }
    }

    /**
     * Synthesize speech from text using the cloned voice.
     * Returns the relative URL to the generated audio file.
     *
     * @param string $text Text to synthesize (max 500 chars)
     * @return string|null URL path to the audio file, or null on failure
     */
    public function synthesize(string $text): ?string
    {
        $text = mb_substr(trim($text), 0, $this->maxTextLen);
        if (empty($text)) return null;

        // Check cache
        $hash     = md5($text);
        $filename = "tts_{$hash}.wav";
        $filePath = $this->cacheDir . $filename;

        if (file_exists($filePath)) {
            return '/SENA_SGPD/public/audio/' . $filename;
        }

        // Call TTS server
        $ch = curl_init($this->baseUrl . '/synthesize');
        curl_setopt_array($ch, [
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => json_encode(['text' => $text, 'language' => 'es']),
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER     => ['Content-Type: application/json'],
            CURLOPT_TIMEOUT        => 60,
        ]);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $contentType = curl_getinfo($ch, CURLINFO_CONTENT_TYPE);
        curl_close($ch);

        if ($httpCode !== 200 || $response === false) {
            return null;
        }

        // Check if response is audio
        if (str_contains($contentType ?? '', 'audio') || strlen($response) > 1000) {
            file_put_contents($filePath, $response);
            return '/SENA_SGPD/public/audio/' . $filename;
        }

        return null;
    }

    /**
     * Check if the TTS server is running.
     */
    public function isAvailable(): bool
    {
        $ch = curl_init($this->baseUrl . '/health');
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT        => 3,
            CURLOPT_CONNECTTIMEOUT => 2,
        ]);
        curl_exec($ch);
        $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        return $code === 200;
    }
}
