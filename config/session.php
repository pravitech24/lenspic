<?php
use Illuminate\Support\Str;
return [
    'driver'          => env('SESSION_DRIVER', 'file'),
    'lifetime'        => env('SESSION_LIFETIME', 120),
    'expire_on_close' => false,
    'encrypt'         => filter_var(env('SESSION_ENCRYPT', false), FILTER_VALIDATE_BOOL),
    'files'           => storage_path('framework/sessions'),
    'connection'      => null,
    'table'           => 'sessions',
    'store'           => null,
    'lottery'         => [2, 100],
    'cookie'          => 'lenspic_session',
    'path'            => '/',
    'domain'          => env('SESSION_DOMAIN'),
    'secure'          => env('SESSION_SECURE_COOKIE'),
    'http_only'       => filter_var(env('SESSION_HTTP_ONLY', true), FILTER_VALIDATE_BOOL),
    'same_site'       => env('SESSION_SAME_SITE', 'lax'),
    'partitioned'     => false,
];
