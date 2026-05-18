<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Mailgun, Postmark, AWS and more. This file provides the de facto
    | location for this type of information, allowing packages to have
    | a conventional file to locate the various service credentials.
    |
    */

    'postmark' => [
        'key' => env('POSTMARK_API_KEY'),
    ],

    'resend' => [
        'key' => env('RESEND_API_KEY'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],

    'embeddings' => [
        'url' => env('EMBEDDINGS_URL', 'http://localhost:8001'),
    ],

    'anthropic' => [
        'key'        => env('ANTHROPIC_API_KEY'),
        'url'        => env('ANTHROPIC_API_URL', 'https://api.anthropic.com/v1/messages'),
        'version'    => env('ANTHROPIC_API_VERSION', '2023-06-01'),
        'model'      => env('ANTHROPIC_MODEL', 'claude-haiku-4-5-20251001'),
        'max_tokens' => (int) env('ANTHROPIC_MAX_TOKENS', 1200),
    ],

    'openrouter' => [
        'url'           => env('OPENROUTER_API_URL', 'https://openrouter.ai/api/v1/chat/completions'),
        'default_model' => env('OPENROUTER_DEFAULT_MODEL', 'anthropic/claude-haiku-4-5'),
        'max_tokens'    => (int) env('OPENROUTER_MAX_TOKENS', 1200),
    ],

];
