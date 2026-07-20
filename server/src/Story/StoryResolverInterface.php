<?php
declare(strict_types=1);

namespace Tna\Story;

use PDO;

interface StoryResolverInterface
{
    /** @return array<string,mixed> */
    public function resolve(PDO $pdo, int $seed, array $controls = [], array $widgets = [], int $revision = 1): array;
}
