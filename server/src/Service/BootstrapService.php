<?php
declare(strict_types=1);
namespace Tna\Service;
use Tna\Database\ConnectionFactory; use Tna\Repository\{StoryControlRepository,StoryWidgetRepository,StoryVersionRepository,QuadrantRepository,QuadrantVariantRepository,PublicSnapshotRepository};
final class BootstrapService
{
    public function __construct(private readonly ConnectionFactory $connections){}
    public function data(): array
    { $pdo=$this->connections->getConnection(); return ['controls'=>(new StoryControlRepository($pdo))->allPublic(),'widgets'=>(new StoryWidgetRepository($pdo))->allPublic(),'versions'=>(new StoryVersionRepository($pdo))->allPublic(),'quadrants'=>(new QuadrantRepository($pdo))->allPublic(),'slots'=>(new QuadrantVariantRepository($pdo))->allPublicSlots(),'publicSnapshot'=>(new PublicSnapshotRepository($pdo))->latestPublic(),'capabilities'=>$this->capabilities()]; }
    public function capabilities(): array { return ['anonymousSessions'=>false,'googleAuth'=>false,'websocket'=>false,'eventBatch'=>false,'storyResolutionMode'=>'not-available']; }
}
