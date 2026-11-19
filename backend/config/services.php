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

    // Staff mailboxes on the cPanel account (UAPI). Empty URL/token = feature off.
    // The token can manage the whole cPanel account: keep it in .env only.
    'cpanel' => [
        'url' => env('CPANEL_URL'),                  // e.g. https://server.example.com:2083
        'user' => env('CPANEL_USER'),
        'token' => env('CPANEL_API_TOKEN'),
        'webmail_url' => env('CPANEL_WEBMAIL_URL'),  // e.g. https://server.example.com:2096
        'mail_domain' => env('MAILBOX_DOMAIN'),      // e.g. pngiep.com
        'quota_mb' => (int) env('MAILBOX_QUOTA_MB', 1024),
        'verify_ssl' => (bool) env('CPANEL_VERIFY_SSL', true),
        'timeout' => 20,
    ],

];
