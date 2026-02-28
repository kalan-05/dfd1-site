<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
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

    'hcaptcha' => [
        'site_key' => env('HCAPTCHA_SITEKEY'),
        'secret' => env('HCAPTCHA_SECRET'),
        'required' => filter_var(env('HCAPTCHA_REQUIRED', false), FILTER_VALIDATE_BOOL),
        'verify_url' => env('HCAPTCHA_VERIFY_URL', 'https://hcaptcha.com/siteverify'),
    ],

];
