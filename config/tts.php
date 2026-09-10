<?php

declare(strict_types=1);

use Core\App;
use Core\Env;

/**
 * Microservicio de sintesis de voz (Coqui XTTS v2).
 *
 * Como Ollama, en un VPS compartido lo normal es que no este disponible.
 * El audio generado va a storage/, fuera de la raiz web.
 */
return [
    'base_url'      => Env::get('TTS_URL', 'http://127.0.0.1:5050'),
    'voice_ref'     => dirname(__DIR__) . '/tts_server/reference_voice/voice.wav',
    'language'      => Env::get('TTS_LANGUAGE', 'es'),
    'cache_dir'     => App::audioPath(),
    'max_text_len'  => Env::int('TTS_MAX_TEXT_LEN', 500),
];
