<?php
declare(strict_types=1);

namespace Tna\Middleware;

use Tna\Http\ApiException;
use Tna\Http\Request;

final class JsonContentTypeMiddleware
{
    public function handle(Request $request): void
    {
        if (in_array($request->method(), ['POST', 'PUT', 'PATCH'], true)) {
            $type = strtolower((string) $request->header('content-type'));
            if (!str_starts_with($type, 'application/json')) {
                throw new ApiException(415, 'Unsupported media type.');
            }
        }
    }
}
