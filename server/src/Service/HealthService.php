<?php
declare(strict_types=1);
namespace Tna\Service;
use PDO; use Throwable; use Tna\Database\ConnectionFactory; use Tna\Database\MigrationRunner; use Tna\Http\ApiException; use Tna\Repository\{StoryControlRepository,StoryWidgetRepository,StoryVersionRepository,QuadrantRepository,QuadrantVariantRepository};
final class HealthService
{
    public function __construct(private readonly ConnectionFactory $connections, private readonly string $migrationsPath){}
    public function check(): array
    { try { $pdo=$this->connections->getConnection(); $pdo->query('SELECT 1')->fetchColumn(); $migration=(new MigrationRunner($pdo,$this->migrationsPath))->verify(); $counts=['controls'=>(new StoryControlRepository($pdo))->count(),'widgets'=>(new StoryWidgetRepository($pdo))->count(),'versions'=>(new StoryVersionRepository($pdo))->count(),'quadrants'=>(new QuadrantRepository($pdo))->count(),'variants'=>(new QuadrantVariantRepository($pdo))->count()]; $expected=['controls'=>32,'widgets'=>4,'versions'=>7,'quadrants'=>29,'variants'=>203]; $ok=$migration['ok'] && $counts===$expected; return ['status'=>$ok?'ok':'degraded','application'=>'the-ninth-art-api','database'=>['status'=>'ok'],'migrations'=>['status'=>$migration['ok']?'ok':'degraded'],'counts'=>$counts]; } catch (Throwable $e) { throw new ApiException(503,'Service unavailable.'); } }
}
