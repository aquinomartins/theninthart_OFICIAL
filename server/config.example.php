<?php
declare(strict_types=1);

return [
    'app' => [
        'environment' => 'production',
        'debug' => false,
        'base_url' => 'https://example.com',
        'timezone' => 'UTC',
        'max_body_bytes' => 1048576,
        'allowed_origins' => ['https://example.com'],
        'app_secret' => 'replace-with-private-random-secret-outside-web-root',
    ],
    'database' => [
        'host' => '127.0.0.1',
        'port' => 3306,
        'name' => 'the_ninth_art',
        'user' => 'database_user',
        'password' => 'database_password',
        'charset' => 'utf8mb4',
    ],
];
