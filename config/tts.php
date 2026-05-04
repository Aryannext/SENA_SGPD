<?php

declare(strict_types=1);

return [
    'base_url'      => 'http://127.0.0.1:5050',
    'voice_ref'     => dirname(__DIR__) . '/tts_server/reference_voice/voice.wav',
    'language'      => 'es',
    'cache_dir'     => dirname(__DIR__) . '/public/audio/',
    'max_text_len'  => 500,
];
