<?php
declare(strict_types=1);

namespace Tna\Support;

final class Logger
{
    private const REDACTED_KEYS = ['password', 'token', 'secret', 'app_secret', 'authorization', 'cookie'];

    public function __construct(private readonly Clock $clock)
    {
    }

    /** @param array<string,mixed> $context */
    public function info(string $message, array $context = []): void
    {
        $this->write('info', $message, $context);
    }

    /** @param array<string,mixed> $context */
    public function error(string $message, array $context = []): void
    {
        $this->write('error', $message, $context);
    }

    /** @param array<string,mixed> $context */
    private function write(string $level, string $message, array $context): void
    {
        $record = ['timestamp' => $this->clock->nowIso8601(), 'level' => $level, 'message' => $message, 'context' => $this->redact($context)];
        error_log(Json::encode($record));
    }

    private function redact(mixed $value): mixed
    {
        if (!is_array($value)) {
            return $value;
        }
        $safe = [];
        foreach ($value as $key => $item) {
            $keyString = strtolower((string) $key);
            $safe[$key] = in_array($keyString, self::REDACTED_KEYS, true) ? '[redacted]' : $this->redact($item);
        }
        return $safe;
    }
}
