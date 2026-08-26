<?php

return [
    'timeout_seconds' => (int) env('AI_HTTP_TIMEOUT', 60),

    'providers' => [
        'openai' => [
            'label' => 'OpenAI',
            'base_url' => 'https://api.openai.com/v1',
            'api_key' => env('OPENAI_API_KEY'),
        ],
    ],

    'features' => [
        'transcription' => [
            'label' => 'Transcripción de voz',
            'provider' => 'openai',
            'model' => 'gpt-transcribe',
            'allowed_models' => ['gpt-transcribe'],
            'enabled' => true,
            'reasoning_effort' => null,
            'fallback_model' => null,
            'fallback_reasoning_effort' => null,
        ],
        'idea_organization' => [
            'label' => 'Organización de ideas',
            'provider' => 'openai',
            'model' => 'gpt-5.6-luna',
            'allowed_models' => ['gpt-5.6-luna', 'gpt-5.6-terra'],
            'enabled' => true,
            'reasoning_effort' => 'low',
            'fallback_model' => 'gpt-5.6-terra',
            'fallback_reasoning_effort' => 'medium',
            'ambiguity_threshold' => 0.72,
        ],
        'idea_relations' => [
            'label' => 'Relaciones semánticas',
            'provider' => 'openai',
            'model' => 'gpt-5.6-luna',
            'allowed_models' => ['gpt-5.6-luna', 'gpt-5.6-terra'],
            'enabled' => true,
            'reasoning_effort' => 'low',
            'fallback_model' => 'gpt-5.6-terra',
            'fallback_reasoning_effort' => 'medium',
            'ambiguity_threshold' => 0.72,
        ],
    ],

    'limits' => [
        'audio_kilobytes' => 10240,
        'transcript_characters' => 20000,
        'context_ideas' => 60,
        'context_tags' => 80,
        'suggested_tags' => 7,
        'suggested_relations' => 5,
    ],

    'pricing_per_million_units' => [
        'gpt-5.6-luna' => ['input' => 0.20, 'output' => 1.20],
        'gpt-5.6-terra' => ['input' => 2.00, 'output' => 12.00],
    ],
];
