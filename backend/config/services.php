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

    'pddikti' => [
        'base_url' => env('PDDIKTI_BASE_URL', 'https://pddikti.fastapicloud.dev/api'),
        'semester' => env('PDDIKTI_SEMESTER', '20241'),
        'cache_ttl' => env('PDDIKTI_CACHE_TTL', 86400), // 24 jam
    ],

    'simpeg' => [
        'base_url' => env('SIMPEG_BASE_URL', 'https://simpeg.bkpsdmcloud.com'),
        'username' => env('SIMPEG_USERNAME', 'admin'),
        'password' => env('SIMPEG_PASSWORD', 'Admin123'),
        'timeout' => env('SIMPEG_TIMEOUT', 30),
    ],

];
