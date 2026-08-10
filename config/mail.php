<?php
return [
    'default' => env('MAIL_MAILER', 'log'),
    'mailers' => [
        'smtp' => ['transport' => 'smtp', 'host' => env('MAIL_HOST', 'localhost'), 'port' => env('MAIL_PORT', 587), 'encryption' => env('MAIL_ENCRYPTION', 'tls'), 'username' => env('MAIL_USERNAME'), 'password' => env('MAIL_PASSWORD')],
        'log'  => ['transport' => 'log', 'channel' => null],
    ],
    'from' => ['address' => env('MAIL_FROM_ADDRESS', 'hello@lenspic.in'), 'name' => env('MAIL_FROM_NAME', 'LensPic')],
];
