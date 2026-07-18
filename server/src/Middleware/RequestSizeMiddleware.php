<?php
declare(strict_types=1);

namespace Tna\Middleware;

use Tna\Http\ApiException;
use Tna\Http\Request;

final class RequestSizeMiddleware
{
    public function __construct(private readonly int $maxBytes)
    {
    }

    public function handle(Request $request): void
    {
        $length = $request->header('content-length');
        if ($length !== null && ctype_digit($length) && (int) $length > $this->maxBytes) {
            throw new ApiException(413, 'Request body too large.');
        }
        if (strlen($request->body()) > $this->maxBytes) {
            throw new ApiException(413, 'Request body too large.');
        }
    }
}
