<?php
declare(strict_types=1);

namespace Tna\Database;

use PDO;
use RuntimeException;
use Throwable;

final class MigrationRunner
{
    private const LOCK_NAME = 'tna_schema_migrations';

    public function __construct(private readonly PDO $pdo, private readonly string $migrationsPath)
    {
    }

    /** @return array<int,array{version:string,filename:string,path:string,checksum:string,applied:bool,execution_time_ms:?int,applied_at:?string}> */
    public function status(): array
    {
        $applied = $this->appliedMigrationsIfTableExists();
        return array_map(static function (array $migration) use ($applied): array {
            $record = $applied[$migration['version']] ?? null;
            return $migration + [
                'applied' => $record !== null,
                'execution_time_ms' => $record !== null ? (int) $record['execution_time_ms'] : null,
                'applied_at' => $record['applied_at'] ?? null,
            ];
        }, $this->migrationFiles());
    }

    /** @return array<int,array{version:string,filename:string,checksum:string,would_apply:bool}> */
    public function dryRun(): array
    {
        $applied = $this->appliedMigrationsIfTableExists();
        return array_map(static function (array $migration) use ($applied): array {
            if (isset($applied[$migration['version']]) && $applied[$migration['version']]['checksum'] !== $migration['checksum']) {
                throw new RuntimeException("Checksum changed for applied migration {$migration['filename']}.");
            }
            return [
                'version' => $migration['version'],
                'filename' => $migration['filename'],
                'checksum' => $migration['checksum'],
                'would_apply' => !isset($applied[$migration['version']]),
            ];
        }, $this->migrationFiles());
    }

    /** @return array<int,array{version:string,filename:string,applied:bool,execution_time_ms:int}> */
    public function up(): array
    {
        return $this->withLock(function (): array {
            $results = [];
            foreach ($this->migrationFiles() as $migration) {
                $applied = $this->appliedMigrationsIfTableExists();
                if (isset($applied[$migration['version']])) {
                    if ($applied[$migration['version']]['checksum'] !== $migration['checksum']) {
                        throw new RuntimeException("Checksum changed for applied migration {$migration['filename']}.");
                    }
                    $results[] = ['version' => $migration['version'], 'filename' => $migration['filename'], 'applied' => false, 'execution_time_ms' => (int) $applied[$migration['version']]['execution_time_ms']];
                    continue;
                }

                $started = microtime(true);
                try {
                    $this->pdo->beginTransaction();
                    $this->pdo->exec((string) file_get_contents($migration['path']));
                    $elapsed = (int) round((microtime(true) - $started) * 1000);
                    $insert = $this->pdo->prepare('INSERT INTO schema_migrations (version, filename, checksum, applied_at, execution_time_ms) VALUES (:version, :filename, :checksum, UTC_TIMESTAMP(), :execution_time_ms)');
                    $insert->execute(['version' => $migration['version'], 'filename' => $migration['filename'], 'checksum' => $migration['checksum'], 'execution_time_ms' => $elapsed]);
                    $this->pdo->commit();
                    $results[] = ['version' => $migration['version'], 'filename' => $migration['filename'], 'applied' => true, 'execution_time_ms' => $elapsed];
                } catch (Throwable $throwable) {
                    if ($this->pdo->inTransaction()) {
                        $this->pdo->rollBack();
                    }
                    throw $throwable;
                }
            }
            return $results;
        });
    }

    /** @return array{ok:bool,checks:array<string,bool>} */
    public function verify(): array
    {
        $this->dryRun();
        $checks = [];
        $applied = $this->appliedMigrationsIfTableExists();
        foreach ($this->migrationFiles() as $migration) {
            $checks['migration:' . $migration['version']] = isset($applied[$migration['version']]);
        }
        foreach (['story_controls','story_widgets','story_versions','quadrants','quadrant_variants','media_assets'] as $table) {
            $checks["table:$table"] = $this->tableEngine($table) === 'InnoDB';
            $checks["charset:$table"] = $this->tableCharset($table) === 'utf8mb4';
        }
        foreach (['uq_story_controls_stable_key','uq_story_controls_position','uq_story_widgets_stable_key','uq_story_widgets_position','uq_story_widgets_panel_id','uq_story_widgets_activation_button_id','uq_story_versions_stable_key','uq_story_versions_version_number','uq_story_versions_position','uq_quadrants_stable_key','uq_quadrants_number','uq_quadrants_position','uq_quadrant_variants_slot_key','uq_quadrant_variants_quadrant_version','uq_quadrant_variants_position','uq_media_assets_quadrant_variant_id'] as $index) {
            $checks["unique:$index"] = $this->uniqueIndexExists($index);
        }
        foreach (['fk_quadrant_variants_quadrant','fk_quadrant_variants_story_version','fk_media_assets_quadrant_variant'] as $fk) {
            $checks["fk:$fk"] = $this->foreignKeyExists($fk);
        }
        return ['ok' => !in_array(false, $checks, true), 'checks' => $checks];
    }

    /** @return array<string,array{checksum:string,execution_time_ms:int,applied_at:string}> */
    private function appliedMigrationsIfTableExists(): array
    {
        if (!$this->tableExists('schema_migrations')) {
            return [];
        }
        $rows = $this->pdo->query('SELECT version, checksum, execution_time_ms, applied_at FROM schema_migrations')->fetchAll();
        $out = [];
        foreach ($rows as $row) { $out[(string) $row['version']] = $row; }
        return $out;
    }

    /** @return array<int,array{version:string,filename:string,path:string,checksum:string}> */
    private function migrationFiles(): array
    {
        $files = glob(rtrim($this->migrationsPath, '/') . '/*.sql') ?: [];
        sort($files, SORT_STRING);
        return array_map(static function (string $path): array {
            $filename = basename($path);
            $version = preg_replace('/\.sql$/', '', $filename) ?: $filename;
            return ['version' => $version, 'filename' => $filename, 'path' => $path, 'checksum' => hash_file('sha256', $path) ?: ''];
        }, $files);
    }

    /** @template T @param callable():T $callback @return T */
    private function withLock(callable $callback): mixed
    {
        $stmt = $this->pdo->prepare('SELECT GET_LOCK(:name, 10) AS locked');
        $stmt->execute(['name' => self::LOCK_NAME]);
        if ((int) ($stmt->fetch()['locked'] ?? 0) !== 1) { throw new RuntimeException('Could not acquire migration lock.'); }
        try { return $callback(); } finally { $this->pdo->prepare('SELECT RELEASE_LOCK(:name)')->execute(['name' => self::LOCK_NAME]); }
    }

    private function tableExists(string $table): bool
    { $s=$this->pdo->prepare('SELECT COUNT(*) FROM information_schema.TABLES WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = :t'); $s->execute(['t'=>$table]); return (int)$s->fetchColumn()===1; }
    private function tableEngine(string $table): ?string
    { $s=$this->pdo->prepare('SELECT ENGINE FROM information_schema.TABLES WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = :t'); $s->execute(['t'=>$table]); $v=$s->fetchColumn(); return $v===false?null:(string)$v; }
    private function tableCharset(string $table): ?string
    { $s=$this->pdo->prepare('SELECT CCSA.CHARACTER_SET_NAME FROM information_schema.TABLES T JOIN information_schema.COLLATION_CHARACTER_SET_APPLICABILITY CCSA ON CCSA.COLLATION_NAME=T.TABLE_COLLATION WHERE T.TABLE_SCHEMA=DATABASE() AND T.TABLE_NAME=:t'); $s->execute(['t'=>$table]); $v=$s->fetchColumn(); return $v===false?null:(string)$v; }
    private function uniqueIndexExists(string $index): bool
    { $s=$this->pdo->prepare('SELECT COUNT(*) FROM information_schema.STATISTICS WHERE TABLE_SCHEMA=DATABASE() AND INDEX_NAME=:i AND NON_UNIQUE=0'); $s->execute(['i'=>$index]); return (int)$s->fetchColumn()>0; }
    private function foreignKeyExists(string $fk): bool
    { $s=$this->pdo->prepare('SELECT COUNT(*) FROM information_schema.REFERENTIAL_CONSTRAINTS WHERE CONSTRAINT_SCHEMA=DATABASE() AND CONSTRAINT_NAME=:f'); $s->execute(['f'=>$fk]); return (int)$s->fetchColumn()===1; }
}
