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

    /*
    |--------------------------------------------------------------------------
    | SMS Gateway
    |--------------------------------------------------------------------------
    |
    | "api_key" is the shared secret expected in the X-API-Key header for all
    | /api/v1/* routes. "firebase" holds the path to the Google service account
    | JSON used to authenticate against the FCM HTTP v1 API.
    |
    */

    'gateway' => [
        'api_key' => env('API_GATEWAY_KEY'),
    ],

    'firebase' => [
        // Absolute or storage-relative path to the service account JSON file.
        'credentials' => env('FIREBASE_CREDENTIALS_PATH'),
        // Optional: overrides the project_id read from the credentials file.
        'project_id' => env('FIREBASE_PROJECT_ID'),
    ],

];
