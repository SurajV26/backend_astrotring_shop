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
        'scheme' => 'https',
    ],

    'postmark' => [
        'token' => env('POSTMARK_TOKEN'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'smartflo' => [
        'caller_id' => env('SMARTFLO_CALLER_ID'),
    ],

    'sms' => [
        'base_url' => env('SMS_BASE_URL'),
        'username' => env('SMS_USERNAME'),
        'api_key' => env('SMS_API_KEY'),
        'sender' => env('SMS_SENDER'),
        'entity_id' => env('SMS_ENTITY_ID'),
        'template_id' => env('SMS_TEMPLATE_ID'),
    ],

    'cod_charge' => env('COD_CHARGE', 49),
    'cod_advance_amount' => env('COD_ADVANCE_AMOUNT', 199),
];