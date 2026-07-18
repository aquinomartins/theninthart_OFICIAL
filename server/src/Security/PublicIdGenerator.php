<?php
declare(strict_types=1);

namespace Tna\Security;

final class PublicIdGenerator
{
    public function generate(string $prefix): string
    {
        return $prefix . '_' . bin2hex(random_bytes(16));
    }
}
