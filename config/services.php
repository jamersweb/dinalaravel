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

    'store_iap' => [
        'google_package_name' => env('GOOGLE_PLAY_PACKAGE_NAME'),
        'google_service_account_json' => env('GOOGLE_PLAY_SERVICE_ACCOUNT_JSON'),
        'apple_shared_secret' => env('APPLE_IAP_SHARED_SECRET'),
        'media_token_secret' => env('MEDIA_ACCESS_TOKEN_SECRET', env('APP_KEY')),
    ],

    'firebase' => [
        'project_id' => env('FIREBASE_PROJECT_ID'),
        'service_account_json' => env('FIREBASE_SERVICE_ACCOUNT_JSON'),
    ],

    'ollama' => [
        'base_url' => env('OLLAMA_BASE_URL', 'http://127.0.0.1:11434'),
        'model' => env('OLLAMA_MODEL', 'qwen2.5vl:7b'),
        'timeout' => (int) env('OLLAMA_TIMEOUT', 120),
        'num_ctx' => (int) env('OLLAMA_NUM_CTX', 8192),
        'num_predict' => (int) env('OLLAMA_NUM_PREDICT', 1024),
        'use_images' => filter_var(env('OLLAMA_USE_IMAGES', true), FILTER_VALIDATE_BOOLEAN),
    ],

];
