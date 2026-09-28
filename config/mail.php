<?php

return [
    'default' => env('MAIL_MAILER', 'smtp'),

    'mailers' => [
        'smtp' => [
            'transport' => 'smtp',
            'scheme' => env('MAIL_SCHEME'),
            'url' => env('MAIL_URL'),
            'host' => env('MAIL_HOST', 'smtp.gmail.com'),
            'port' => env('MAIL_PORT', 587),
            'username' => env('MAIL_USERNAME'),
            'password' => env('MAIL_PASSWORD'),
            'timeout' => null,
            'local_domain' => env('MAIL_EHLO_DOMAIN', parse_url((string) env('APP_URL', 'http://localhost'), PHP_URL_HOST)),
        ],
        'log' => [
            'transport' => 'log',
            'channel' => env('MAIL_LOG_CHANNEL'),
        ],
        // Uncomment-free opt-in: set MAIL_MAILER=resend and RESEND_API_KEY to
        // send as info@worldchoiceperfume.com. Cloudflare Email Routing only
        // receives — sending needs a provider that can sign the domain.
        'resend' => [
            'transport' => 'resend',
            'key' => env('RESEND_API_KEY'),
        ],
    ],

    'from' => [
        'address' => env('MAIL_FROM_ADDRESS', 'worldchoiceperfumes@gmail.com'),
        'name' => env('MAIL_FROM_NAME', env('APP_NAME', 'World Choice Perfume')),
    ],
];
