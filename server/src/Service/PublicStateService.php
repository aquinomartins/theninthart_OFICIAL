<?php
declare(strict_types=1);
namespace Tna\Service;
use Tna\Database\ConnectionFactory; use Tna\Repository\PublicSnapshotRepository;
final class PublicStateService
{
    public function __construct(private readonly ConnectionFactory $connections){}
    public function latest(): array { return ['publicSnapshot'=>(new PublicSnapshotRepository($this->connections->getConnection()))->latestPublic()]; }
}
