<?php
declare(strict_types=1);

namespace Tna\Config;

final class DatabaseConfig
{
    public function __construct(
        public readonly string $host,
        public readonly int $port,
        public readonly string $name,
        public readonly string $user,
        public readonly string $password,
        public readonly string $charset,
    ) {
    }

    /** @param array<string,mixed> $config */
    public static function fromArray(array $config): self
    {
        $database = is_array($config['database'] ?? null) ? $config['database'] : [];
        return new self(
            (string) ($database['host'] ?? '127.0.0.1'),
            (int) ($database['port'] ?? 3306),
            (string) ($database['name'] ?? ''),
            (string) ($database['user'] ?? ''),
            (string) ($database['password'] ?? ''),
            (string) ($database['charset'] ?? 'utf8mb4')
        );
    }
}
