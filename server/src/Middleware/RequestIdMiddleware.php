<?php
declare(strict_types=1);

namespace Tna\Middleware;

use Tna\Http\Request;
use Tna\Security\PublicIdGenerator;

final class RequestIdMiddleware
{
    public function __construct(private readonly PublicIdGenerator $generator)
    {
    }

    public function handle(Request $request): Request
    {
        $incoming = $request->header('x-request-id');
        if (is_string($incoming) && preg_match('/^[A-Za-z0-9._:-]{8,128}$/', $incoming) === 1) {
            return $request->withRequestId($incoming);
        }
        return $request->withRequestId($this->generator->generate('req'));
    }
}
