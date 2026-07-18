<?php
declare(strict_types=1);

return [
    'app' => [
        'environment' => 'production',
        'debug' => false,
        'base_url' => 'https://example.com',
        'api_base' => '/api/v1',
        'timezone' => 'UTC',
        'max_body_bytes' => 1048576,
        'allowed_origins' => ['https://example.com'],
        'app_secret' => 'replace-with-private-random-secret-outside-web-root',
        'log_dir' => __DIR__ . '/../logs',
        'limits' => [
            'events_per_batch' => 100,
            'pending_operations' => 60,
            'rate_limit_window_seconds' => 60,
        ],
        'features' => [
            'GOOGLE_AUTH_ENABLED' => false,
            'REALTIME_MODE' => 'none',
            'REALTIME_URL' => '',
        ],
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
