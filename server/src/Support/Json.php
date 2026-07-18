<?php
declare(strict_types=1);

namespace Tna\Support;

final class Json
{
    /** @return array<string,mixed> */
    public static function decodeObject(string $json): array
    {
        try {
            $decoded = json_decode($json, true, 512, JSON_THROW_ON_ERROR);
        } catch (\JsonException $exception) {
            throw new \InvalidArgumentException('Invalid JSON body.', 0, $exception);
        }
        if (!is_array($decoded) || array_is_list($decoded)) {
            throw new \InvalidArgumentException('JSON body must be an object.');
        }
        return $decoded;
    }

    public static function encode(mixed $value): string
    {
        return json_encode($value, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR) . "\n";
    }
}
