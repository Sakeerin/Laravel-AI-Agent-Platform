<?php

return [

    'postmark' => [
        'token' => env('POSTMARK_TOKEN'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'resend' => [
        'key' => env('RESEND_KEY'),
    ],

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],

    'anthropic' => [
        'api_key' => env('ANTHROPIC_API_KEY'),
    ],

    'openai' => [
        'api_key' => env('OPENAI_API_KEY'),
    ],

    'ollama' => [
        'host' => env('OLLAMA_HOST', 'http://localhost:11434'),
    ],

    'ai' => [
        'default_model' => env('AI_DEFAULT_MODEL', 'claude-sonnet'),
    ],

    'brave' => [
        'api_key' => env('BRAVE_API_KEY'),
    ],

    'google' => [
        'search_api_key' => env('GOOGLE_SEARCH_API_KEY'),
        'search_cx' => env('GOOGLE_SEARCH_CX'),
    ],

    'memory' => [
        'default_embedding_backend' => env('MEMORY_EMBEDDING_BACKEND', 'openai:text-embedding-3-small'),
        'extraction_model' => env('MEMORY_EXTRACTION_MODEL', 'gpt-4o-mini'),
        'heartbeat_model' => env('HEARTBEAT_MODEL', 'gpt-4o-mini'),
    ],

];
