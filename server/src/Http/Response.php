<?php
declare(strict_types=1);

namespace Tna\Http;

use Tna\Support\Clock;
use Tna\Support\Json;

final class Response
{
    /** @param array<string,string> $headers */
    public function __construct(private readonly int $status, private readonly array $payload, private readonly array $headers = [])
    {
    }

    /** @param array<string,mixed> $data */
    public static function envelope(array $data, string $requestId, Clock $clock, int $status = 200, ?array $error = null, array $headers = []): self
    {
        $safeData = $data === [] ? (object) [] : $data;
        return new self($status, ['data' => $safeData, 'meta' => ['requestId' => $requestId, 'schemaVersion' => '1.0.0', 'mechanismVersion' => '1.0.0', 'timestamp' => $clock->nowIso8601()], 'error' => $error], $headers);
    }

    public function status(): int { return $this->status; }
    /** @return array<string,mixed> */ public function payload(): array { return $this->payload; }
    /** @return array<string,string> */ public function headers(): array { return $this->headers; }

    public function send(): void
    {
        http_response_code($this->status);
        header('Content-Type: application/json; charset=utf-8');
        foreach ($this->headers as $name => $value) { header($name . ': ' . $value); }
        if ($this->status === 304) {
            return;
        }
        echo Json::encode($this->payload);
    }
}
