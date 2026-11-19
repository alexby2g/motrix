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

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'resend' => [
        'key' => env('RESEND_KEY'),
    ],



    'motrix_sms' => [
        // Credenciales solo en el .env del backend Hostinger; nunca en la APK.
        'twilio_account_sid' => env('MOTRIX_TWILIO_ACCOUNT_SID'),
        'twilio_auth_token' => env('MOTRIX_TWILIO_AUTH_TOKEN'),
        'twilio_from' => env('MOTRIX_TWILIO_FROM'),
        'twilio_messaging_service_sid' => env('MOTRIX_TWILIO_MESSAGING_SERVICE_SID'),
        // Alternativa: proveedor de SMS propio con respuesta HTTP 2xx.
        'webhook_url' => env('MOTRIX_SMS_WEBHOOK_URL'),
        'token' => env('MOTRIX_SMS_WEBHOOK_TOKEN'),
    ],

    'firebase' => [
        'credentials' => env('MOTRIX_FIREBASE_CREDENTIALS'),
        'project_id' => env('MOTRIX_FIREBASE_PROJECT_ID'),
        'client_email' => env('MOTRIX_FIREBASE_CLIENT_EMAIL'),
        'private_key' => env('MOTRIX_FIREBASE_PRIVATE_KEY'),
    ],

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],

];
