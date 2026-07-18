<?php
declare(strict_types=1);

namespace Tna\Config;

final class AppConfig
{
    /** @param list<string> $allowedOrigins */
    public function __construct(
        public readonly string $environment,
        public readonly bool $debug,
        public readonly string $baseUrl,
        public readonly string $timezone,
        public readonly int $maxBodyBytes,
        public readonly array $allowedOrigins,
        public readonly string $appSecret,
    ) {
    }

    /** @param array<string,mixed> $config */
    public static function fromArray(array $config): self
    {
        $app = is_array($config['app'] ?? null) ? $config['app'] : [];
        return new self(
            (string) ($app['environment'] ?? 'production'),
            filter_var($app['debug'] ?? false, FILTER_VALIDATE_BOOL),
            (string) ($app['base_url'] ?? ''),
            (string) ($app['timezone'] ?? 'UTC'),
            (int) ($app['max_body_bytes'] ?? 1048576),
            array_values(array_filter($app['allowed_origins'] ?? [], 'is_string')),
            (string) ($app['app_secret'] ?? '')
        );
    }

    public function isDebug(): bool
    {
        return $this->debug && $this->environment !== 'production';
    }
}
