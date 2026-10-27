<?php

return [

    // Provider behind the AddressNormalizer port: "ollama" (local) for now.
    'provider' => env('AI_PROVIDER', 'ollama'),
    'model' => env('AI_MODEL', 'qwen2.5:7b'),

    // Local Ollama server. A local model needs no API key.
    'ollama_url' => env('OLLAMA_URL', 'http://localhost:11434'),

    // Seconds to wait for the model; a 7B model on a laptop can be slow.
    'timeout' => (int) env('AI_TIMEOUT', 60),

    // Published schema the model output must match before it is used.
    'schema_path' => base_path('direccion-normalizada.schema.json'),

];
