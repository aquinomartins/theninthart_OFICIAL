<?php
declare(strict_types=1);

namespace Tna\Support;

final class Environment
{
    public static function value(string $key): ?string
    {
        $value = getenv($key);
        if ($value === false || $value === '') {
            return null;
        }
        return $value;
    }
}
