<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Сторонние сервисы
    |--------------------------------------------------------------------------
    |
    | Этот файл предназначен для хранения учётных данных сторонних сервисов,
    | таких как Resend, Postmark, AWS и других. Он служит стандартным местом
    | для такой информации, чтобы пакеты могли по общепринятому соглашению
    | находить учётные данные различных сервисов.
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

    'marketplace' => [
        'webhook_secret' => env('MARKETPLACE_WEBHOOK_SECRET'),
        'webhook_tolerance' => env('MARKETPLACE_WEBHOOK_TOLERANCE', 300),
    ],

];
