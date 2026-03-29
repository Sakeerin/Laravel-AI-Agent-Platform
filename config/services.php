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

];
