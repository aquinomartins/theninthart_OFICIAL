<?php
declare(strict_types=1);

namespace Tna\Support;

final class Clock
{
    public function nowIso8601(): string
    {
        return (new \DateTimeImmutable('now', new \DateTimeZone('UTC')))->format(\DateTimeInterface::ATOM);
    }
}
