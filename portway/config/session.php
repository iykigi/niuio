<?php

use Illuminate\Support\Str;

return [

    'driver' => env('SESSION_DRIVER', 'file'),

    'lifetime' => (int) env('SESSION_LIFETIME', 120),

    'expire_on_close' => env('SESSION_EXPIRE_ON_CLOSE', false),

    'encrypt' => env('SESSION_ENCRYPT', true),

    'files' => storage_path('framework/sessions'),

    'connection' => env('SESSION_CONNECTION', 'default'),

    'table' => env('SESSION_TABLE', 'sessions'),

    'store' => env('SESSION_STORE'),

    'lottery' => [2, 100],

    'cookie' => env(
        'SESSION_COOKIE',
        Str::slug(env('APP_NAME', 'portway'), '_').'_session'
    ),

    'path' => env('SESSION_PATH', '/'),

    'domain' => env('SESSION_DOMAIN'),

    // Defaults to true (cookie only sent over HTTPS) in production:
    // Portway forces HTTPS there (see AppServiceProvider), so the session
    // cookie should never be issued without the Secure flag. Everywhere
    // else it defaults to false so `php artisan serve` over plain
    // http://127.0.0.1:8000 can keep you logged in. Set
    // SESSION_SECURE_COOKIE explicitly to override either way.
    'secure' => env('SESSION_SECURE_COOKIE', env('APP_ENV', 'production') === 'production'),

    'http_only' => env('SESSION_HTTP_ONLY', true),

    'same_site' => env('SESSION_SAME_SITE', 'lax'),

    'partitioned' => env('SESSION_PARTITIONED_COOKIE', false),

];
