<?php
declare(strict_types=1);

namespace Tna\Middleware;

final class SecurityHeadersMiddleware
{
    public function send(): void
    {
        header('X-Content-Type-Options: nosniff');
        header('Referrer-Policy: strict-origin-when-cross-origin');
        header('Permissions-Policy: camera=(), microphone=(), geolocation=()');
    }
}
