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

    'mailgun' => [
        'domain' => env('MAILGUN_DOMAIN'),
        'secret' => env('MAILGUN_SECRET'),
        'endpoint' => env('MAILGUN_ENDPOINT', 'api.mailgun.net'),
    ],

    'postmark' => [
        'token' => env('POSTMARK_TOKEN'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'revenuecat' => [
        'webhook_auth' => env('REVENUECAT_WEBHOOK_AUTH'),
        'webhook_debug' => env('REVENUECAT_WEBHOOK_DEBUG', false),
    ],

    'store_iap' => [
        'google_package_name' => env('GOOGLE_PLAY_PACKAGE_NAME'),
        'google_service_account_json' => env('GOOGLE_PLAY_SERVICE_ACCOUNT_JSON'),
        'apple_shared_secret' => env('APPLE_IAP_SHARED_SECRET'),
        'media_token_secret' => env('MEDIA_ACCESS_TOKEN_SECRET', env('APP_KEY')),
    ],

];
