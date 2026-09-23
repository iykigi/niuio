<?php

return [

    'postmark' => [
        'token' => env('POSTMARK_TOKEN'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    /*
     |--------------------------------------------------------------------
     | ACME / Let's Encrypt
     |--------------------------------------------------------------------
     */
    'acme' => [
        'directory' => env('PORTWAY_ACME_DIRECTORY', 'https://acme-v02.api.letsencrypt.org/directory'),
        'staging_directory' => 'https://acme-staging-v02.api.letsencrypt.org/directory',
        'staging' => (bool) env('PORTWAY_ACME_STAGING', true),
        'email' => env('PORTWAY_ACME_EMAIL'),
    ],

];
