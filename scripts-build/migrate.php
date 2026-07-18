#!/usr/bin/env php
<?php
declare(strict_types=1);

use Tna\Config\DatabaseConfig;
use Tna\Database\ConnectionFactory;
use Tna\Database\MigrationRunner;
use Tna\Support\Environment;

$root = dirname(__DIR__);
$autoload = $root . '/server/vendor/autoload.php';
if (is_file($autoload)) {
    require $autoload;
} else {
    spl_autoload_register(static function (string $class) use ($root): void {
        $prefix = 'Tna\\';
        if (!str_starts_with($class, $prefix)) { return; }
        $file = $root . '/server/src/' . str_replace('\\', '/', substr($class, strlen($prefix))) . '.php';
        if (is_file($file)) { require $file; }
    });
}

$command = $argv[1] ?? '';
if (!in_array($command, ['status', 'dry-run', 'up', 'verify'], true)) {
    fwrite(STDERR, "Usage: php scripts-build/migrate.php <status|dry-run|up|verify>\n");
    exit(2);
}

$migrationsPath = $root . '/database/migrations';
$files = glob($migrationsPath . '/*.sql') ?: [];
sort($files, SORT_STRING);

function printMigrationFiles(array $files): void
{
    foreach ($files as $file) {
        printf("- %s %s\n", basename($file), hash_file('sha256', $file));
    }
}

try {
    $configPath = Environment::value('TNA_CONFIG_PATH') ?: Environment::value('TNA_PRIVATE_CONFIG_PATH') ?: $root . '/tna-config.php';
    $config = is_file($configPath) ? require $configPath : require $root . '/server/config.example.php';
    if (!is_array($config)) { throw new RuntimeException('Configuration must return an array.'); }

    if ($command === 'dry-run') {
        echo "Migration files and checksums:\n";
        printMigrationFiles($files);
    }

    $pdo = (new ConnectionFactory(DatabaseConfig::fromArray($config)))->getConnection();
    $runner = new MigrationRunner($pdo, $migrationsPath);

    if ($command === 'status') {
        foreach ($runner->status() as $row) {
            printf("%s %-34s %s %s\n", $row['applied'] ? '[applied]' : '[pending]', $row['filename'], $row['checksum'], $row['applied_at'] ?? '-');
        }
        exit(0);
    }
    if ($command === 'dry-run') {
        echo "Database plan:\n";
        foreach ($runner->dryRun() as $row) {
            printf("%s %s\n", $row['would_apply'] ? '[would apply]' : '[already applied]', $row['filename']);
        }
        exit(0);
    }
    if ($command === 'up') {
        foreach ($runner->up() as $row) {
            printf("%s %s (%d ms)\n", $row['applied'] ? '[applied]' : '[skipped]', $row['filename'], $row['execution_time_ms']);
        }
        exit(0);
    }

    $result = $runner->verify();
    foreach ($result['checks'] as $name => $ok) {
        printf("%s %s\n", $ok ? '[OK]' : '[FAIL]', $name);
    }
    exit($result['ok'] ? 0 : 1);
} catch (Throwable $throwable) {
    if ($command === 'dry-run') {
        fwrite(STDERR, "[WARN] Database unavailable; offline dry-run only. Migrations were not applied.\n");
        fwrite(STDERR, '[WARN] ' . $throwable->getMessage() . "\n");
        exit(0);
    }
    fwrite(STDERR, '[ERROR] ' . $throwable->getMessage() . "\n");
    exit(1);
}
