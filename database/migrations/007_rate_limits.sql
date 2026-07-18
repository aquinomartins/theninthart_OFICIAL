CREATE TABLE api_rate_limits (
    bucket_key CHAR(64) NOT NULL,
    route_key VARCHAR(100) NOT NULL,
    window_started_at DATETIME NOT NULL,
    request_count INT UNSIGNED NOT NULL DEFAULT 0,
    updated_at DATETIME NOT NULL,
    PRIMARY KEY (bucket_key, route_key, window_started_at),
    KEY idx_api_rate_limits_updated_at (updated_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
