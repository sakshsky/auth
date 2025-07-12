<?php

return [
    'server_email' => env('SAKSH_AUTH_SERVER_EMAIL', 'your-email@example.com'),
    'email_config' => [
        'protocol' => env('SAKSH_AUTH_PROTOCOL', 'imap'),
        'host' => env('SAKSH_AUTH_HOST', 'imap.gmail.com'),
        'port' => env('SAKSH_AUTH_PORT', 993),
        'user' => env('SAKSH_AUTH_USER', ''),
        'pass' => env('SAKSH_AUTH_PASS', ''),
        'poll_interval' => env('SAKSH_AUTH_POLL_INTERVAL', 30000),
    ],
];