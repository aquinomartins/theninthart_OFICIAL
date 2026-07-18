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
    public static function envelope(array $data, string $requestId, Clock $clock, int $status = 200, ?array $error = null): self
    {
        $safeData = $data === [] ? (object) [] : $data;
        return new self($status, ['data' => $safeData, 'meta' => ['requestId' => $requestId, 'schemaVersion' => '1.0.0', 'mechanismVersion' => '1.0.0', 'timestamp' => $clock->nowIso8601()], 'error' => $error]);
    }

    public function send(): void
    {
        http_response_code($this->status);
        header('Content-Type: application/json; charset=utf-8');
        foreach ($this->headers as $name => $value) { header($name . ': ' . $value); }
        echo Json::encode($this->payload);
    }
}
