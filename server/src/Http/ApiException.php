<?php
declare(strict_types=1);

namespace Tna\Http;

class ApiException extends \RuntimeException
{
    public function __construct(
        private readonly int $statusCode,
        string $message
    ) {
        parent::__construct($message);
    }

    public function statusCode(): int
    {
        return $this->statusCode;
    }
}
