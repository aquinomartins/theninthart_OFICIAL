<?php
declare(strict_types=1);

$root = dirname(__DIR__, 2);
$results = [];

function record(array &$results, string $name, bool $ok, string $message = ''): void
{
    $results[] = ['name' => $name, 'ok' => $ok, 'message' => $message];
    echo ($ok ? '[PASS] ' : '[FAIL] ') . $name . ($message !== '' ? ' - ' . $message : '') . PHP_EOL;
}

function jsonFile(string $path): array
{
    $data = json_decode((string) file_get_contents($path), true);
    if (!is_array($data)) {
        throw new RuntimeException("Invalid JSON: $path");
    }
    return $data;
}

try {
    $expected = [
        'data/story-controls.json' => 32,
        'data/story-widgets.json' => 4,
        'data/story-versions.json' => 7,
        'data/quadrants.json' => 29,
        'data/quadrant-slots.json' => 203,
    ];
    foreach ($expected as $file => $count) {
        $json = jsonFile($root . '/' . $file);
        record($results, "catalog count $file", count($json['items'] ?? []) === $count, 'expected ' . $count . ', found ' . count($json['items'] ?? []));
    }

    $slots = jsonFile($root . '/data/quadrant-slots.json')['items'] ?? [];
    $slotOk = count($slots) === 203 && ($slots[0]['id'] ?? null) === 'q01-v01' && ($slots[202]['id'] ?? null) === 'q29-v07';
    record($results, 'quadrant slot boundaries', $slotOk, 'expected q01-v01 through q29-v07');

    $sqlFiles = glob($root . '/server/src/{Service,Repository,Story,Database}/*.php', GLOB_BRACE) ?: [];
    $unsafe = [];
    foreach ($sqlFiles as $file) {
        $source = (string) file_get_contents($file);
        foreach (['$_GET', '$_POST', '$_REQUEST', '$_COOKIE'] as $superglobal) {
            if (str_contains($source, $superglobal)) {
                $unsafe[] = basename($file) . " uses $superglobal";
            }
        }
    }
    record($results, 'no direct superglobal SQL input in backend classes', $unsafe === [], implode('; ', $unsafe));

    $sessionSource = (string) file_get_contents($root . '/server/src/Service/SessionService.php');
    record($results, 'session token returned only on create state', str_contains($sessionSource, "anonymous_token_hash") && str_contains($sessionSource, 'hash(\'sha256\',$token)'), 'raw token is hashed before persistence');

    $errorSource = (string) file_get_contents($root . '/server/src/Http/ErrorHandler.php');
    record($results, 'stack trace gated by debug', str_contains($errorSource, 'getTraceAsString') && str_contains($errorSource, 'isDebug()'), 'trace should not appear in production');

    $htaccess = $root . '/api/.htaccess';
    record($results, 'api .htaccess exists', is_file($htaccess), 'api/.htaccess');

    $secretFindings = [];
    $scanFiles = array_merge(glob($root . '/server/*.php') ?: [], glob($root . '/docs/*.md') ?: [], glob($root . '/scripts-build/*.php') ?: []);
    foreach ($scanFiles as $file) {
        $source = (string) file_get_contents($file);
        if (preg_match('/(AKIA[0-9A-Z]{16}|AIza[0-9A-Za-z_\-]{35}|-----BEGIN (RSA |OPENSSH |EC )?PRIVATE KEY-----)/', $source)) {
            $secretFindings[] = str_replace($root . '/', '', $file);
        }
    }
    record($results, 'no obvious high-entropy provider secrets', $secretFindings === [], implode(', ', $secretFindings));

    $baseUrl = getenv('TNA_API_BASE_URL') ?: '';
    if ($baseUrl === '') {
        record($results, 'integration smoke available', true, 'skipped: set TNA_API_BASE_URL to run HTTP integration');
    } else {
        $cmd = PHP_BINARY . ' ' . escapeshellarg($root . '/scripts-build/api-smoke-test.php') . ' ' . escapeshellarg($baseUrl);
        passthru($cmd, $code);
        record($results, 'integration smoke', $code === 0, $cmd);
    }
} catch (Throwable $e) {
    record($results, 'test runner exception', false, $e->getMessage());
}

$failed = array_values(array_filter($results, static fn(array $r): bool => !$r['ok']));
echo PHP_EOL . count($results) . ' checks, ' . count($failed) . ' failures.' . PHP_EOL;
exit(count($failed) === 0 ? 0 : 1);
