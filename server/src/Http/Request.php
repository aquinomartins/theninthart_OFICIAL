<?php
declare(strict_types=1);

namespace Tna\Http;

final class Request
{
    /** @param array<string,string> $headers @param array<string,string> $query */
    public function __construct(
        private readonly string $method,
        private readonly string $path,
        private readonly array $headers,
        private readonly array $query,
        private readonly string $body,
        private ?string $requestId = null,
    ) {
    }

    public static function fromGlobals(): self
    {
        $method = strtoupper((string) ($_SERVER['REQUEST_METHOD'] ?? 'GET'));
        $headers = self::headersFromGlobals();
        $query = [];
        foreach ($_GET as $key => $value) {
            if (is_scalar($value)) {
                $query[(string) $key] = (string) $value;
            }
        }
        $path = $query['route'] ?? self::pathFromServer();
        $body = file_get_contents('php://input') ?: '';
        return new self($method, self::normalizePath($path), $headers, $query, $body);
    }

    public function method(): string { return $this->method; }
    public function path(): string { return $this->path; }
    public function body(): string { return $this->body; }
    public function requestId(): ?string { return $this->requestId; }
    public function withRequestId(string $requestId): self { $clone = clone $this; $clone->requestId = $requestId; return $clone; }
    public function header(string $name): ?string { return $this->headers[strtolower($name)] ?? null; }

    private static function pathFromServer(): string
    {
        $uri = (string) ($_SERVER['REQUEST_URI'] ?? '/');
        $path = (string) (parse_url($uri, PHP_URL_PATH) ?: '/');
        $script = str_replace('\\', '/', dirname((string) ($_SERVER['SCRIPT_NAME'] ?? '')));
        if ($script !== '/' && str_starts_with($path, $script . '/')) {
            $path = substr($path, strlen($script));
        }
        if (str_starts_with($path, '/api/')) {
            $path = substr($path, 4);
        }
        return $path;
    }

    private static function normalizePath(string $path): string
    {
        $path = '/' . ltrim($path, '/');
        return rtrim($path, '/') ?: '/';
    }

    /** @return array<string,string> */
    private static function headersFromGlobals(): array
    {
        $headers = [];
        foreach ($_SERVER as $key => $value) {
            if (!is_string($value)) { continue; }
            if (str_starts_with($key, 'HTTP_')) {
                $name = strtolower(str_replace('_', '-', substr($key, 5)));
                $headers[$name] = $value;
            }
        }
        if (isset($_SERVER['CONTENT_TYPE'])) { $headers['content-type'] = (string) $_SERVER['CONTENT_TYPE']; }
        if (isset($_SERVER['CONTENT_LENGTH'])) { $headers['content-length'] = (string) $_SERVER['CONTENT_LENGTH']; }
        return $headers;
    }
}
