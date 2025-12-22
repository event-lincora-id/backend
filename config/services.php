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
        'token' => env('POSTMARK_TOKEN'),
    ],

    'resend' => [
        'key' => env('RESEND_KEY'),
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

    'xendit' => [
        'secret_key' => env('XENDIT_SECRET_KEY'),
        'public_key' => env('XENDIT_PUBLIC_KEY'),
        'webhook_token' => env('XENDIT_WEBHOOK_TOKEN'),
        'callback_url' => env('XENDIT_CALLBACK_URL'),
        'redirect_url' => env('XENDIT_REDIRECT_URL'),

        // Platform fee settings
        'platform_fee_percentage' => env('PLATFORM_FEE_PERCENTAGE', 5), // Default 5%
        'minimum_withdrawal_amount' => env('MINIMUM_WITHDRAWAL_AMOUNT', 50000), // Default Rp 50,000
    ],
'groq' => [
    'api_key' => env('GROQ_API_KEY'),
    'model' => env('GROQ_MODEL'),
    'api_url' => env('GROQ_API_URL'),
],


];
