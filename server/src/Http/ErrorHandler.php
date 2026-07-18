<?php
declare(strict_types=1);

namespace Tna\Http;

use Tna\Config\AppConfig;
use Tna\Support\Clock;
use Tna\Support\Logger;

final class ErrorHandler
{
    public function __construct(private readonly AppConfig $config, private readonly Clock $clock, private readonly Logger $logger)
    {
    }

    public function register(): void
    {
        set_error_handler(function (int $severity, string $message, string $file, int $line): bool {
            throw new \ErrorException($message, 0, $severity, $file, $line);
        });
    }

    public function handle(\Throwable $throwable, Request $request): Response
    {
        $status = $throwable instanceof ApiException ? $throwable->statusCode() : 500;
        $message = $status === 500 && !$this->config->isDebug() ? 'Internal server error.' : $throwable->getMessage();
        $this->logger->error('api_error', ['requestId' => $request->requestId(), 'status' => $status, 'exception' => $throwable::class, 'message' => $throwable->getMessage()]);
        $error = ['code' => $status, 'message' => $message];
        if ($status === 500 && $this->config->isDebug()) {
            $error['trace'] = $throwable->getTraceAsString();
        }
        return Response::envelope([], $request->requestId() ?? 'unavailable', $this->clock, $status, $error);
    }
}
