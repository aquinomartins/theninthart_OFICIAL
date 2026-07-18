<?php
declare(strict_types=1);

namespace Tna\Repository;

final class RepositoryJson
{
    public static function decode(?string $json): mixed
    {
        if ($json === null || $json === '') {
            return null;
        }
        return json_decode($json, true, 512, JSON_THROW_ON_ERROR);
    }
}
